<?php
// ==========================================
// 1. PHP LOGIC (Database & Security)
// ==========================================
$current_page = 'expiry-tracking';
$page_title   = 'Expiry Tracking';
require_once __DIR__ . '/../../../includes/pharmacist_init.php';

// Remove one expired or soon-to-expire batch from this pharmacist's inventory.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_expiry') {
    if (!csrf_valid()) {
        flash_set('error', 'Security check failed. Please try again.');
    } else {
        $inventory_id = (int) ($_POST['inventory_id'] ?? 0);
        [$removed, $message] = remove_expiry_batch($conn, $pharmacist_id, $inventory_id);
        flash_set($removed ? 'success' : 'error', $message);
    }
    header('Location: expiry-tracking.php', true, 303);
    exit();
}

// ---- Load expired and soon-to-expire batches, soonest expiry first ----
$stmt = $conn->prepare(
    "SELECT i.inventory_id, i.batch_number, i.stock_quantity, i.expiry_date,
            m.medicine_id, m.medicine_name
     FROM PharmacyInventory i
     JOIN Medicine m ON m.medicine_id = i.medicine_id
     WHERE i.pharmacist_user_id = ?
             AND i.expiry_date <= DATE_ADD(CURDATE(), INTERVAL " . EXPIRY_WARNING_DAYS . " DAY)
     ORDER BY i.expiry_date ASC, m.medicine_name ASC"
);
$stmt->bind_param("i", $pharmacist_id);
$stmt->execute();
$all_batches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---- Status per batch + KPI counters ----
$counts = ['expired' => 0, 'soon' => 0, 'ok' => 0];
foreach ($all_batches as &$b) {
    $b['status'] = expiry_status($b['expiry_date']);
    $counts[$b['status'][0]]++;
}
unset($b);

// ---- Status filter ----
$filter = $_GET['status'] ?? '';
if (!in_array($filter, ['expired', 'soon'], true)) {
    $filter = '';
}
$batches = array_filter($all_batches, fn($b) => $filter === '' || $b['status'][0] === $filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expiry Tracking - CancerCare</title>
    <link rel="stylesheet" href="../../../public/css/pharmacist.css">
</head>
<body>

<div class="app">
<?php include __DIR__ . '/../../../includes/pharmacist_sidebar.php'; ?>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="main">
<?php include __DIR__ . '/../../../includes/pharmacist_topbar.php'; ?>

        <div class="content">
            <?php flash_render(); ?>

            <section class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon teal">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                    <div class="kpi-body">
                        <div class="kpi-label">Batches Tracked</div>
                        <div class="kpi-value"><?php echo number_format(count($all_batches)); ?></div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon orange">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </div>
                    <div class="kpi-body">
                        <div class="kpi-label">Expiring Within <?php echo EXPIRY_WARNING_DAYS; ?> Days</div>
                        <div class="kpi-value"><?php echo $counts['soon']; ?></div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon blue" style="background:#fee2e2; color:var(--danger);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    </div>
                    <div class="kpi-body">
                        <div class="kpi-label">Already Expired</div>
                        <div class="kpi-value"><?php echo $counts['expired']; ?></div>
                    </div>
                </div>
            </section>

            <section class="widget">
                <div class="widget-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <h3>Batch Expiry Register</h3>
                    <form action="expiry-tracking.php" method="GET" style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <select name="status" style="padding: 8px 12px; border: 1px solid var(--gray-200); border-radius: 6px; font-size: 0.875rem; background: white;">
                            <option value="">Expired and Expiring Soon</option>
                            <option value="expired" <?php echo $filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="soon" <?php echo $filter === 'soon' ? 'selected' : ''; ?>>Expiring Soon</option>
                        </select>
                        <button type="submit" class="btn btn-primary" style="padding: 8px 14px;">Filter</button>
                    </form>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Drug ID</th>
                                <th>Medicine Details</th>
                                <th>Batch / Lot No.</th>
                                <th>Expiry Date</th>
                                <th>Qty in Batch</th>
                                <th>Status</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($batches)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:28px; color:var(--gray-500);">
                                        <?php echo empty($all_batches) ? 'No batches are being tracked yet.' : 'No batches match this filter.'; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($batches as $b):
                                [, $pill_class, $pill_label, $days] = $b['status'];
                                $review = 'edit-drug.php?medicine_id=' . (int) $b['medicine_id']
                                    . '&inventory_id=' . (int) $b['inventory_id'];
                            ?>
                            <tr>
                                <td><strong><?php echo e(drug_code($b['medicine_id'])); ?></strong></td>
                                <td><strong><?php echo e($b['medicine_name']); ?></strong></td>
                                <td><?php echo e($b['batch_number'] ?: '—'); ?></td>
                                <td>
                                    <?php echo e($b['expiry_date']); ?>
                                    <span class="muted-note">
                                        <?php echo $days < 0 ? abs($days) . ' day(s) ago' : ($days === 0 ? 'today' : 'in ' . $days . ' day(s)'); ?>
                                    </span>
                                </td>
                                <td style="font-weight:700;"><?php echo number_format((int) $b['stock_quantity']); ?></td>
                                <td><span class="status-pill <?php echo $pill_class; ?>"><?php echo $pill_label; ?></span></td>
                                <td style="text-align: center;">
                                    <?php if (in_array($b['status'][0], ['expired', 'soon'], true)): ?>
                                        <form action="expiry-tracking.php" method="POST" style="display:inline;" onsubmit="return confirm('Remove this expiry-risk batch from inventory?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="remove_expiry">
                                            <input type="hidden" name="inventory_id" value="<?php echo (int) $b['inventory_id']; ?>">
                                            <button type="submit" class="btn" style="background:#dc2626;color:#fff;border:0;padding:4px 8px;font-size:0.75rem;cursor:pointer;">Remove</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="<?php echo e($review); ?>" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.75rem;">Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>
<script>
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}
</script>
</body>
</html>
