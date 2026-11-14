<?php
require_once __DIR__ . '/../admin_init.php';
$current_page = 'monitoring';
$recent_users = $conn->query("SELECT user_id, username, role, status, created_at FROM User ORDER BY created_at DESC LIMIT 10");
$recent_reports = $conn->query("SELECT report_id, patient_user_id, uploaded_at FROM MedicalReport ORDER BY uploaded_at DESC LIMIT 10");
$recent_donations = $conn->query("SELECT donation_id, benefactor_user_id, amount, currency, status FROM Donation ORDER BY donation_id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Monitoring - CancerCare</title><link rel="stylesheet" href="../../../public/css/base.css"></head>
<body><div class="app"><?php require_once __DIR__ . '/../../../includes/admin_sidebar.php'; ?><main class="main">
<header class="topbar"><div><h2>System Monitoring</h2><p class="date">Review recent system activity</p></div><a href="../../../logout.php" class="signout-btn">Sign Out</a></header>
<div class="content">
<div class="stat-grid"><div class="card stat-card"><p class="stat-label">Users</p><h3 class="stat-value"><?php echo admin_count($conn, "SELECT COUNT(*) total FROM User"); ?></h3></div>
<div class="card stat-card"><p class="stat-label">Medical Reports</p><h3 class="stat-value"><?php echo admin_count($conn, "SELECT COUNT(*) total FROM MedicalReport"); ?></h3></div>
<div class="card stat-card"><p class="stat-label">Donations</p><h3 class="stat-value"><?php echo admin_count($conn, "SELECT COUNT(*) total FROM Donation"); ?></h3></div></div>
<div class="card list-card wide-card"><div class="list-card-header"><h3>Recent User Activity</h3></div><div class="table-wrap"><table class="data-table"><tr><th>User</th><th>Role</th><th>Status</th><th>Created</th></tr>
<?php while ($row = $recent_users->fetch_assoc()): ?><tr><td><?php echo admin_escape($row['username']); ?></td><td><?php echo admin_escape($row['role']); ?></td><td><?php echo admin_escape($row['status']); ?></td><td><?php echo admin_escape($row['created_at']); ?></td></tr><?php endwhile; ?></table></div></div>
<div class="card list-card wide-card"><div class="list-card-header"><h3>Recent Donations</h3></div><div class="table-wrap"><table class="data-table"><tr><th>ID</th><th>Benefactor</th><th>Amount</th><th>Status</th></tr>
<?php while ($row = $recent_donations->fetch_assoc()): ?><tr><td><?php echo (int) $row['donation_id']; ?></td><td><?php echo (int) $row['benefactor_user_id']; ?></td><td><?php echo admin_escape($row['amount'] . ' ' . $row['currency']); ?></td><td><?php echo admin_escape($row['status']); ?></td></tr><?php endwhile; ?></table></div></div>
</div></main></div></body></html>
