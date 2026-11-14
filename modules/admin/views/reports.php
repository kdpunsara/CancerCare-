<?php
require_once __DIR__ . '/../admin_init.php';
$current_page = 'reports';
$type = $_GET['report_type'] ?? 'summary';
$format = $_GET['format'] ?? 'html';
$allowed_types = ['summary', 'users', 'donations', 'medical_reports'];
if (!in_array($type, $allowed_types, true)) $type = 'summary';
$title = ['summary' => 'System Summary', 'users' => 'User Report', 'donations' => 'Donation Report', 'medical_reports' => 'Medical Report Activity'][$type];
$rows = [];
if ($type === 'users') {
    $result = $conn->query("SELECT user_id, username, email, role, status, created_at FROM User ORDER BY user_id DESC");
    while ($result && ($row = $result->fetch_assoc())) $rows[] = $row;
} elseif ($type === 'donations') {
    $result = $conn->query("SELECT donation_id, benefactor_user_id, donation_type, amount, currency, status FROM Donation ORDER BY donation_id DESC");
    while ($result && ($row = $result->fetch_assoc())) $rows[] = $row;
} elseif ($type === 'medical_reports') {
    $result = $conn->query("SELECT report_id, patient_user_id, uploaded_at FROM MedicalReport ORDER BY uploaded_at DESC");
    while ($result && ($row = $result->fetch_assoc())) $rows[] = $row;
} else {
    $rows[] = ['Metric' => 'Registered Users', 'Value' => admin_count($conn, "SELECT COUNT(*) total FROM User")];
    $rows[] = ['Metric' => 'Donations', 'Value' => admin_count($conn, "SELECT COUNT(*) total FROM Donation")];
    $rows[] = ['Metric' => 'Medical Reports', 'Value' => admin_count($conn, "SELECT COUNT(*) total FROM MedicalReport")];
}
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . str_replace(' ', '-', strtolower($title)) . '.csv"');
    $out = fopen('php://output', 'w');
    if ($rows) { fputcsv($out, array_keys($rows[0])); foreach ($rows as $row) fputcsv($out, $row); }
    fclose($out);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports - CancerCare</title><link rel="stylesheet" href="../../../public/css/base.css"></head>
<body><div class="app"><?php require_once __DIR__ . '/../../../includes/admin_sidebar.php'; ?><main class="main">
<header class="topbar"><div><h2>Report Generation</h2><p class="date">Generate operational and analytical reports</p></div><a href="../../../logout.php" class="signout-btn">Sign Out</a></header>
<div class="content"><div class="card form-card wide-card"><form method="get" class="form-grid"><div class="form-field"><label>Report</label><select name="report_type"><option value="summary">System Summary</option><option value="users">Users</option><option value="donations">Donations</option><option value="medical_reports">Medical Reports</option></select></div><div class="form-field"><label>Format</label><select name="format"><option value="html">View on screen</option><option value="csv">Download CSV</option></select></div><div class="form-actions form-field-wide"><button class="btn-primary" type="submit">Generate Report</button></div></form></div>
<div class="card list-card wide-card"><div class="list-card-header"><h3><?php echo admin_escape($title); ?></h3></div><div class="table-wrap"><table class="data-table"><?php if ($rows): ?><tr><?php foreach (array_keys($rows[0]) as $heading): ?><th><?php echo admin_escape($heading); ?></th><?php endforeach; ?></tr><?php foreach ($rows as $row): ?><tr><?php foreach ($row as $value): ?><td><?php echo admin_escape($value); ?></td><?php endforeach; ?></tr><?php endforeach; ?><?php else: ?><tr><td>No data found.</td></tr><?php endif; ?></table></div></div></div>
</main></div></body></html>
