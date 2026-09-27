<?php
// ==========================================
// PHARMACIST MODULE - SHARED BOOTSTRAP
// Included at the top of every page in modules/pharmacist/views/
// Plain PHP + MySQLi only (no frameworks, no external APIs).
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// ---- Business rules (no matching column in the schema, so kept here) ----
const LOW_STOCK_THRESHOLD = 20;   // available qty at/below this => "Low Stock"
const EXPIRY_WARNING_DAYS = 30;   // batches expiring within this many days => "Expiring Soon"

// ---- Authentication: only logged-in pharmacists may enter ----
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pharmacist') {
    header("Location: ../../../login.php");
    exit();
}

$pharmacist_id = (int) $_SESSION['user_id'];

// ---- Load pharmacist profile (User + Pharmacist tables) ----
$stmt = $conn->prepare(
    "SELECT u.username, u.email, u.phone, u.status,
            p.first_name, p.last_name, p.pharmacy_name, p.address, p.license_no
     FROM User u
     LEFT JOIN Pharmacist p ON p.user_id = u.user_id
     WHERE u.user_id = ? AND u.role = 'pharmacist'
     LIMIT 1"
);
$stmt->bind_param("i", $pharmacist_id);
$stmt->execute();
$pharmacist = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Account removed or deactivated since login -> force logout
if (!$pharmacist || $pharmacist['status'] !== 'active') {
    session_unset();
    session_destroy();
    header("Location: ../../../login.php");
    exit();
}

$pharmacist['first_name'] = $pharmacist['first_name'] ?? $pharmacist['username'];
$pharmacist['last_name']  = $pharmacist['last_name'] ?? '';
$pharmacist_name     = trim($pharmacist['first_name'] . ' ' . $pharmacist['last_name']);
$pharmacist_initials = strtoupper(first_char($pharmacist['first_name']) . first_char($pharmacist['last_name']));

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/** Character count that works with or without the mbstring extension. */
function text_len(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s) : (int) preg_match_all('/./us', $s);
}

/** First character (UTF-8 safe) or '' for an empty string. */
function first_char(string $s): string
{
    return preg_match('/^./us', $s, $m) ? $m[0] : '';
}

/** Escape output for HTML. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Medicine id -> display code, e.g. 4 => DRG-004 */
function drug_code($medicine_id): string
{
    return 'DRG-' . str_pad((string) $medicine_id, 3, '0', STR_PAD_LEFT);
}

/** Stock level -> [css class, label] */
function stock_status(int $available_qty): array
{
    if ($available_qty <= 0) {
        return ['critical', 'Out of Stock'];
    }
    if ($available_qty <= LOW_STOCK_THRESHOLD) {
        return ['scheduled', 'Low Stock'];
    }
    return ['active', 'Good Stock'];
}

/** Expiry date -> [key, css class, label, days left] */
function expiry_status(string $expiry_date): array
{
    $today = new DateTime('today');
    $exp   = new DateTime($expiry_date);
    $days  = (int) $today->diff($exp)->format('%r%a');

    if ($days < 0) {
        return ['expired', 'critical', 'Expired', $days];
    }
    if ($days <= EXPIRY_WARNING_DAYS) {
        return ['soon', 'scheduled', 'Expiring Soon', $days];
    }
    return ['ok', 'active', 'Valid', $days];
}

/** Write an entry to the SystemLog table (admin monitoring). Never breaks the page if it fails. */
function log_action(mysqli $conn, int $user_id, string $action, string $table, ?int $record_id, string $description): void
{
    try {
        $ip   = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $conn->prepare(
            "INSERT INTO SystemLog (user_id, action_type, table_name, record_id, action_description, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ississ", $user_id, $action, $table, $record_id, $description, $ip);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $ex) {
        error_log('SystemLog write failed: ' . $ex->getMessage());
    }
}

/** Remove one expired or soon-to-expire batch owned by this pharmacist. */
function remove_expiry_batch(mysqli $conn, int $pharmacist_id, int $inventory_id): array
{
    $stmt = $conn->prepare(
        "SELECT i.batch_number, m.medicine_name
         FROM PharmacyInventory i
         JOIN Medicine m ON m.medicine_id = i.medicine_id
         WHERE i.inventory_id = ? AND i.pharmacist_user_id = ?
           AND i.expiry_date <= DATE_ADD(CURDATE(), INTERVAL " . EXPIRY_WARNING_DAYS . " DAY)
         LIMIT 1"
    );
    $stmt->bind_param("ii", $inventory_id, $pharmacist_id);
    $stmt->execute();
    $batch = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$batch) {
        return [false, 'That batch is not expired or near expiry.'];
    }

    $stmt = $conn->prepare("DELETE FROM PharmacyInventory WHERE inventory_id = ? AND pharmacist_user_id = ?");
    $stmt->bind_param("ii", $inventory_id, $pharmacist_id);
    $stmt->execute();
    $removed = $stmt->affected_rows === 1;
    $stmt->close();

    if (!$removed) {
        return [false, 'The expiry batch could not be removed.'];
    }

    log_action($conn, $pharmacist_id, 'INVENTORY_DELETE', 'PharmacyInventory', $inventory_id,
        "Removed expiry batch {$batch['batch_number']} of {$batch['medicine_name']}");
    return [true, 'Expiry batch removed from inventory.'];
}

// ---- One-time flash messages (survive a redirect) ----
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $message];
}

function flash_render(): void
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($f['type']) . '">' . e($f['msg']) . '</div>';
    }
}

// ---- CSRF protection for every POST form ----
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token']);
}
