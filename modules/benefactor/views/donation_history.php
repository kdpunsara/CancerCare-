<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'benefactor') {
    header("Location: ../../../index.php");
    exit();
}

$benefactor_id = $_SESSION['user_id'];

// ---- 1. Fetch Benefactor Profile for Sidebar ----
$stmt_profile = $conn->prepare("SELECT first_name, last_name, benefactor_type FROM Benefactor WHERE user_id = ?");
$stmt_profile->bind_param("i", $benefactor_id);
$stmt_profile->execute();
$profile = $stmt_profile->get_result()->fetch_assoc();

if (!$profile) {
    $profile = ['first_name' => 'Benefactor', 'last_name' => '', 'benefactor_type' => 'local'];
}
$full_name = $profile['first_name'] . ' ' . $profile['last_name'];
$initials = strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1));

// ---- 2. Fetch Dashboard Statistics ----
$sql_stats = "SELECT 
                COUNT(DISTINCT CASE WHEN donation_type = 'Financial Aid' THEN patient_user_id END) AS fin_aid_patients,
                COUNT(CASE WHEN donation_type = 'Equipment' THEN 1 END) AS equip_count,
                COALESCE(SUM(CASE WHEN donation_type = 'Financial Aid' THEN amount ELSE 0 END), 0) AS total_fin_aid
              FROM Donation 
              WHERE benefactor_user_id = ?";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $benefactor_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// ---- 3. Fetch Full Donation History ----
$search = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';
$sql_history = "SELECT d.donation_id, d.donation_type, d.amount, d.item_name, d.quantity, d.currency,
                       d.status, d.created_at,
                       p.first_name AS p_first, p.last_name AS p_last
                FROM Donation d
                LEFT JOIN Patient p ON d.patient_user_id = p.user_id
                WHERE d.benefactor_user_id = ?";
if ($search !== '') {
    $sql_history .= " AND (
                        CONCAT_WS(' ', p.first_name, p.last_name) LIKE ?
                        OR CAST(d.donation_id AS CHAR) LIKE ?
                        OR CONCAT('DON-', LPAD(d.donation_id, 3, '0')) LIKE ?
                        OR DATE_FORMAT(d.created_at, '%Y-%m-%d') LIKE ?
                        OR DATE_FORMAT(d.created_at, '%d %b %Y') LIKE ?
                      )";
}
$sql_history .= " ORDER BY d.created_at DESC";
$stmt_history = $conn->prepare($sql_history);
if ($search !== '') {
    $search_pattern = '%' . $search . '%';
    $stmt_history->bind_param(
        "isssss",
        $benefactor_id,
        $search_pattern,
        $search_pattern,
        $search_pattern,
        $search_pattern,
        $search_pattern
    );
} else {
    $stmt_history->bind_param("i", $benefactor_id);
}
$stmt_history->execute();
$donations = $stmt_history->get_result()->fetch_all(MYSQLI_ASSOC);
$history_message = $_GET['msg'] ?? '';
$history_error = $_GET['error'] ?? '';

// Helper to format status badges
function getDonationBadge($status) {
    switch ($status) {
        case 'Received': return 'badge-active'; // Green/Teal
        case 'Pending Verification': return 'badge-pending'; // Red/Orange
        case 'Awaiting Customs': return 'badge-done'; // Blue
        case 'Rejected': return 'badge-cancelled'; // Grey
        default: return 'badge-pending';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation History - CancerCare</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
</head>
<body>
    <div class="app">
        <?php 
        $current_page = 'history'; 
        require_once __DIR__ . '/../../../includes/benefactor_sidebar.php'; 
        ?>

        <main class="main">
            <header class="topbar">
                <div class="topbar-left">
                    <div>
                        <h2>Donation History</h2>
                        <p class="date">Track the status and impact of every contribution you've made.</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <a href="../../../logout.php" class="signout-btn">Log Out</a>
                </div>
            </header>

            <div class="content">

                <?php if ($history_message === 'donation_deleted'): ?>
                    <div class="card panel" style="border-left: 4px solid var(--teal); margin-bottom: 20px;">
                        <p class="panel-note" style="color: var(--teal);">Donation deleted successfully.</p>
                    </div>
                <?php elseif ($history_error === 'donation_not_pending'): ?>
                    <div class="card panel" style="border-left: 4px solid var(--red); margin-bottom: 20px;">
                        <p class="panel-note" style="color: var(--red);">Only pending donations can be deleted.</p>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stat-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
                    <div class="card stat-card">
                        <p class="stat-label">Financial Aid Patients</p>
                        <h3 class="stat-value"><?php echo intval($stats['fin_aid_patients']); ?></h3>
                    </div>
                    <div class="card stat-card">
                        <p class="stat-label">Equipment / Medication Donations</p>
                        <h3 class="stat-value"><?php echo intval($stats['equip_count']); ?></h3>
                    </div>
                    <div class="card stat-card">
                        <p class="stat-label">Financial Aid Total (LKR)</p>
                        <h3 class="stat-value"><?php echo number_format($stats['total_fin_aid']); ?></h3>
                    </div>
                </div>

                <!-- Donations Table -->
                <div class="card wide-card">
                    <div class="list-card-header">
                        <h3>All Donations</h3>
                    </div>
                    <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; padding: 0 20px 16px;">
                        <input
                            type="search"
                            name="search"
                            value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Search patient, donation ID, or date"
                            aria-label="Search donations by patient, donation ID, or date"
                            style="flex: 1; min-width: 220px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 6px;"
                        >
                        <button type="submit" class="btn-secondary">Search</button>
                        <?php if ($search !== ''): ?>
                            <a href="donation_history.php" class="btn-secondary" style="text-decoration: none;">Clear</a>
                        <?php endif; ?>
                    </form>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Donation ID</th>
                                    <th>Patient</th>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($donations)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                            <?php if ($search !== ''): ?>
                                                No donations match "<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>".
                                            <?php else: ?>
                                                You haven't made any donations yet.
                                                <a href="make_donation.php" style="color: var(--blue); font-weight: 600;">Make your first donation!</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($donations as $don): 
                                        $patient_name = ($don['p_first'] && $don['p_last']) 
                                            ? htmlspecialchars($don['p_first'] . ' ' . $don['p_last']) 
                                            : 'General Fund';
                                        
                                        // Format details based on type
                                        if ($don['donation_type'] === 'Financial Aid') {
                                            $details = 'LKR ' . number_format($don['amount']);
                                        } else {
                                            $details = htmlspecialchars($don['item_name']) . ' x' . intval($don['quantity']);
                                        }
                                    ?>
                                        <tr>
                                            <td class="med-name">DON-<?php echo str_pad($don['donation_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo $patient_name; ?></td>
                                            <td><?php echo htmlspecialchars($don['donation_type']); ?></td>
                                            <td><strong><?php echo $details; ?></strong></td>
                                            <td><?php echo date('d M Y', strtotime($don['created_at'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo getDonationBadge($don['status']); ?>">
                                                    <?php echo htmlspecialchars($don['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($don['status'] === 'Pending Verification'): ?>
                                                    <form method="POST" action="../benefactor_actions.php" onsubmit="return confirm('Delete this pending donation?');">
                                                        <input type="hidden" name="action" value="delete_donation">
                                                        <input type="hidden" name="donation_id" value="<?php echo (int) $don['donation_id']; ?>">
                                                        <button type="submit" class="btn-secondary" style="padding: 6px 10px; color: var(--red);">Delete</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted);">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>