<?php
require_once __DIR__ . '/../admin_init.php';
$current_page = 'donors';
$search = trim((string) ($_GET['search'] ?? ''));
$donation_status = trim((string) ($_GET['donation_status'] ?? ''));
$allowed_statuses = ['Pending Verification', 'Verified', 'Rejected'];
if (!in_array($donation_status, $allowed_statuses, true)) {
    $donation_status = '';
}

$donor_sql = "SELECT u.user_id, u.email, u.status, b.first_name, b.last_name,
    b.organization_name, b.country, COUNT(d.donation_id) AS donation_count,
    COALESCE(SUM(CASE WHEN d.status = 'Verified' AND d.donation_type = 'Financial Aid' THEN d.amount ELSE 0 END), 0) AS total_donated
    FROM User u
    JOIN Benefactor b ON b.user_id = u.user_id
    LEFT JOIN Donation d ON d.benefactor_user_id = u.user_id";
$donor_params = [];
$donor_types = '';
if ($search !== '') {
    $donor_sql .= " WHERE CONCAT_WS(' ', b.first_name, b.last_name, b.organization_name, u.email) LIKE ?";
    $donor_params[] = '%' . $search . '%';
    $donor_types = 's';
}
$donor_sql .= " GROUP BY u.user_id, u.email, u.status, b.first_name, b.last_name, b.organization_name, b.country
    ORDER BY u.user_id DESC";
$donor_stmt = $conn->prepare($donor_sql);
if ($donor_params) {
    $donor_search_param = $donor_params[0];
    $donor_stmt->bind_param($donor_types, $donor_search_param);
}
$donor_stmt->execute();
$donors = $donor_stmt->get_result();

$donation_sql = "SELECT d.donation_id, d.benefactor_user_id, d.donation_type, d.amount,
    d.currency, d.item_name, d.quantity, d.status, d.bank_slip_path, d.created_at,
    CONCAT_WS(' ', b.first_name, b.last_name) AS donor_name, b.organization_name
    FROM Donation d
    JOIN Benefactor b ON b.user_id = d.benefactor_user_id";
$donation_params = [];
$donation_types = '';
$conditions = [];
if ($search !== '') {
    $conditions[] = "(CONCAT_WS(' ', b.first_name, b.last_name, b.organization_name) LIKE ? OR CAST(d.donation_id AS CHAR) LIKE ?)";
    $search_pattern = '%' . $search . '%';
    $donation_params[] = $search_pattern;
    $donation_params[] = $search_pattern;
    $donation_types = 'ss';
}
if ($donation_status !== '') {
    $conditions[] = "d.status = ?";
    $donation_params[] = $donation_status;
    $donation_types .= 's';
}
if ($conditions) {
    $donation_sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$donation_sql .= " ORDER BY d.created_at DESC, d.donation_id DESC LIMIT 100";
$donation_stmt = $conn->prepare($donation_sql);
if (count($donation_params) === 3) {
    $donation_search_name = $donation_params[0];
    $donation_search_id = $donation_params[1];
    $donation_status_param = $donation_params[2];
    $donation_stmt->bind_param('sss', $donation_search_name, $donation_search_id, $donation_status_param);
} elseif (count($donation_params) === 2) {
    $donation_search_name = $donation_params[0];
    $donation_search_id = $donation_params[1];
    $donation_stmt->bind_param('ss', $donation_search_name, $donation_search_id);
} elseif (count($donation_params) === 1) {
    $donation_status_param = $donation_params[0];
    $donation_stmt->bind_param('s', $donation_status_param);
}
$donation_stmt->execute();
$donations = $donation_stmt->get_result();

$stats = $conn->query("SELECT
    COUNT(*) AS contribution_count,
    SUM(status = 'Pending Verification') AS pending_count,
    COUNT(DISTINCT benefactor_user_id) AS active_donors,
    COALESCE(SUM(CASE WHEN status = 'Verified' AND donation_type = 'Financial Aid' THEN amount ELSE 0 END), 0) AS verified_total
    FROM Donation")->fetch_assoc();
$message = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Benefactor Management - CancerCare</title><link rel="stylesheet" href="../../../public/css/base.css"></head>
<body><div class="app"><?php require_once __DIR__ . '/../../../includes/admin_sidebar.php'; ?><main class="main">
<header class="topbar"><div><h2>Benefactor Management</h2><p class="date">Oversee benefactors and their contributions</p></div><a href="../../../logout.php" class="signout-btn">Sign Out</a></header>
<div class="content">
<?php if ($message === 'donation_updated'): ?><div class="card panel" style="border-left:4px solid var(--teal);margin-bottom:20px"><p class="panel-note" style="color:var(--teal)">Contribution status updated.</p></div><?php elseif ($error !== ''): ?><div class="card panel" style="border-left:4px solid var(--red);margin-bottom:20px"><p class="panel-note" style="color:var(--red)">Unable to update that contribution.</p></div><?php endif; ?>
<div class="stat-grid">
<div class="card stat-card"><p class="stat-label">Registered Benefactors</p><h3 class="stat-value"><?php echo number_format((int) $stats['active_donors']); ?></h3></div>
<div class="card stat-card"><p class="stat-label">Contributions</p><h3 class="stat-value"><?php echo number_format((int) $stats['contribution_count']); ?></h3></div>
<div class="card stat-card"><p class="stat-label">Pending Review</p><h3 class="stat-value"><?php echo number_format((int) $stats['pending_count']); ?></h3></div>
<div class="card stat-card"><p class="stat-label">Verified Financial Aid</p><h3 class="stat-value"><?php echo number_format((float) $stats['verified_total'], 2); ?></h3></div>
</div>
<div class="card form-card wide-card"><form method="get" class="form-grid">
<div class="form-field"><label for="search">Search benefactors or contributions</label><input id="search" type="search" name="search" value="<?php echo admin_escape($search); ?>" placeholder="Name, organization, email or ID"></div>
<div class="form-field"><label for="donation-status">Contribution status</label><select id="donation-status" name="donation_status"><option value="">All statuses</option><?php foreach ($allowed_statuses as $status): ?><option value="<?php echo admin_escape($status); ?>" <?php echo $donation_status === $status ? 'selected' : ''; ?>><?php echo admin_escape($status); ?></option><?php endforeach; ?></select></div>
<div class="form-actions form-field-wide"><button class="btn-primary" type="submit">Apply Filters</button> <a class="btn-secondary" href="donor_management.php">Clear</a></div>
</form></div>
<div class="card list-card wide-card"><div class="list-card-header"><h3>Registered Benefactors</h3></div><div class="table-wrap"><table class="data-table">
<tr><th>Benefactor</th><th>Organization</th><th>Country</th><th>Contributions</th><th>Verified Total</th><th>Account</th><th>Action</th></tr>
<?php if ($donors->num_rows === 0): ?><tr><td colspan="7">No benefactors match the current search.</td></tr><?php endif; ?>
<?php while ($row = $donors->fetch_assoc()): ?><tr><td><?php echo admin_escape(trim($row['first_name'] . ' ' . $row['last_name'])); ?><br><small><?php echo admin_escape($row['email']); ?></small></td><td><?php echo admin_escape($row['organization_name'] ?: '-'); ?></td><td><?php echo admin_escape($row['country'] ?: '-'); ?></td><td><?php echo (int) $row['donation_count']; ?></td><td><?php echo admin_escape(number_format((float) $row['total_donated'], 2)); ?></td><td><?php echo admin_escape($row['status']); ?></td><td><form method="post" action="../admin_actions.php"><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="user_id" value="<?php echo (int) $row['user_id']; ?>"><input type="hidden" name="return_to" value="donor_management.php"><button class="btn-secondary" type="submit"><?php echo $row['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button></form></td></tr><?php endwhile; ?>
</table></div></div>
<div class="card list-card wide-card"><div class="list-card-header"><h3>Contributions</h3><p class="table-caption">Showing the 100 most recent contributions.</p></div><div class="table-wrap"><table class="data-table"><tr><th>Contribution</th><th>Benefactor</th><th>Details</th><th>Amount</th><th>Submitted</th><th>Status</th><th>Action</th></tr>
<?php if ($donations->num_rows === 0): ?><tr><td colspan="7">No contributions match the current filters.</td></tr><?php endif; ?>
<?php while ($row = $donations->fetch_assoc()): ?><tr><td>#<?php echo (int) $row['donation_id']; ?><br><small><?php echo admin_escape($row['donation_type']); ?></small></td><td><?php echo admin_escape($row['donor_name']); ?><?php if ($row['organization_name']): ?><br><small><?php echo admin_escape($row['organization_name']); ?></small><?php endif; ?></td><td><?php echo admin_escape($row['item_name'] ?: ($row['quantity'] ? 'Quantity: ' . $row['quantity'] : '-')); ?></td><td><?php echo $row['amount'] !== null ? admin_escape(number_format((float) $row['amount'], 2) . ' ' . $row['currency']) : '-'; ?></td><td><?php echo admin_escape($row['created_at']); ?><?php if ($row['bank_slip_path']): ?><br><a class="link-action" target="_blank" rel="noopener" href="../../../public/<?php echo admin_escape($row['bank_slip_path']); ?>">View receipt</a><?php endif; ?></td><td><?php echo admin_escape($row['status']); ?></td><td><?php if ($row['status'] === 'Pending Verification'): ?><form method="post" action="../admin_actions.php"><input type="hidden" name="action" value="update_donation"><input type="hidden" name="donation_id" value="<?php echo (int) $row['donation_id']; ?>"><button class="btn-primary" name="status" value="Verified" type="submit">Verify</button> <button class="btn-secondary" name="status" value="Rejected" type="submit">Reject</button></form><?php else: ?>-<?php endif; ?></td></tr><?php endwhile; ?>
</table></div></div></div></main></div></body></html>
