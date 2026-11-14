<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS AdminSetting (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
)");

function admin_count($conn, $sql) {
    $result = $conn->query($sql);
    return $result ? (int) $result->fetch_assoc()['total'] : 0;
}

function admin_escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
