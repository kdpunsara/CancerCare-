<?php
// ==========================================
// 1. PHP LOGIC (Database & Security)
// ==========================================
$current_page = 'dashboard';
$page_title   = 'Pharmacy Management';
require_once __DIR__ . '/../../../includes/pharmacist_init.php';

// Always load the latest stock values after an update.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ---- Load this pharmacist's inventory, one row per medicine ----
// Expired batches are NOT counted as available stock.
$sql = "SELECT m.medicine_id, m.medicine_name, m.generic_name, m.category, m.manufacturer,
               COALESCE(SUM(CASE WHEN i.expiry_date >= CURDATE() THEN i.stock_quantity ELSE 0 END), 0) AS available_qty,
               COALESCE(SUM(CASE WHEN i.expiry_date <  CURDATE() THEN i.stock_quantity ELSE 0 END), 0) AS expired_qty,
               GROUP_CONCAT(i.batch_number ORDER BY i.expiry_date SEPARATOR ', ') AS batches
        FROM PharmacyInventory i
        JOIN Medicine m ON m.medicine_id = i.medicine_id
                WHERE i.pharmacist_user_id = ?
                    AND i.expiry_date >= CURDATE()
        GROUP BY m.medicine_id, m.medicine_name, m.generic_name, m.category, m.manufacturer
        ORDER BY m.medicine_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pharmacist_id);
$stmt->execute();
$all_drugs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---- KPI cards (always based on the full inventory, not the search result) ----
$total_types = count($all_drugs);
$low_alerts  = 0;
$total_qty   = 0;
$categories  = [];
foreach ($all_drugs as $d) {
    $total_qty += (int) $d['available_qty'];
    if ((int) $d['available_qty'] <= LOW_STOCK_THRESHOLD) {
        $low_alerts++;
    }
    if (!empty($d['category'])) {
        $categories[$d['category']] = true;
    }
}
ksort($categories);

// ---- Search & category filter ----
$q        = trim($_GET['q'] ?? '');
$cat      = trim($_GET['category'] ?? '');
$drugs    = array_filter($all_drugs, function ($d) use ($q, $cat) {
    if ($cat !== '' && $d['category'] !== $cat) {
        return false;
    }
    if ($q === '') {
        return true;
    }
    $haystack = implode(' ', [
        $d['medicine_name'], $d['generic_name'], $d['manufacturer'],
        $d['batches'], drug_code($d['medicine_id']),
    ]);
    return stripos($haystack, $q) !== false;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Dashboard - CancerCare</title>
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
                    <div class="kpi-icon blue">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.5 16.5c-1.5-1.5-2.5-3.5-2.5-6s1-4.5 2.5-6 3.5-2.5 6-2.5 4.5 1 6 2.5l-12 12zM19.5 7.5c1.5 1.5 2.5 3.5 2.5 6s-1 4.5-2.5 6-3.5 2.5-6 2.5-4.5-1-6-2.5l12-12z"/></svg>
                    </div>
                    <div class="kpi-body">
                        <div class="kpi-label">Total Drug Types</div>
                        <div class="kpi-value"><?php echo number_format($total_types); ?></div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon orange">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </div>
                    <div class="kpi-body">
                        <div class="kpi-label">Low / Out of Stock Alerts</div>
                        <div class="kpi-value"><?php echo number_format($low_alerts); ?></div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon teal">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg>
                    </div>
                    <div class="kpi-body">
                        <div class="kpi-label">Total Items Qty</div>
                        <div class="kpi-value"><?php echo number_format($total_qty); ?></div>
                    </div>
                </div>
            </section>

            <section class="widget">
                <div class="widget-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
                    <h3>Medicine Inventory Management</h3>

                    <!-- Search & Filter Area -->
                    <form action="dashboard.php" method="GET" style="display: flex; gap: 8px; flex-wrap: wrap; flex: 1; max-width: 800px; margin-left: auto;">
                        <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search drug name, ID, or lot..." style="flex: 1; padding: 10px 14px; border: 1px solid var(--gray-200); border-radius: 6px; font-size: 0.875rem;">
                        <select name="category" style="padding: 8px 12px; border: 1px solid var(--gray-200); border-radius: 6px; font-size: 0.875rem; background: white;">
                            <option value="">All Categories</option>
                            <?php foreach (array_keys($categories) as $c): ?>
                                <option value="<?php echo e($c); ?>" <?php echo $c === $cat ? 'selected' : ''; ?>><?php echo e($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary" style="padding: 8px 14px;">Search</button>
                        <?php if ($q !== '' || $cat !== ''): ?>
                            <a href="dashboard.php" class="btn btn-outline" style="padding: 8px 14px;">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Drug ID</th>
                                <th>Medicine Details</th>
                                <th>Category</th>
                                <th>Manufacturer</th>
                                <th>Stock Status</th>
                                <th style="text-align: right;">Available Qty</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($drugs)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:28px; color:var(--gray-500);">
                                        <?php echo $total_types === 0
                                            ? 'Your inventory is empty. Use "Add New Drug" to register your first medicine.'
                                            : 'No medicines match your search.'; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($drugs as $d):
                                [$pill_class, $pill_label] = stock_status((int) $d['available_qty']);
                                $detail = trim(($d['generic_name'] ?: '') . ($d['batches'] ? ' (' . $d['batches'] . ')' : ''));
                            ?>
                            <tr>
                                <td><strong><?php echo e(drug_code($d['medicine_id'])); ?></strong></td>
                                <td>
                                    <div>
                                        <strong><?php echo e($d['medicine_name']); ?></strong>
                                        <?php if ($detail !== ''): ?>
                                            <span style="font-size:0.75rem; color:var(--gray-500); display:block;"><?php echo e($detail); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo e($d['category'] ?: '—'); ?></td>
                                <td><?php echo e($d['manufacturer'] ?: '—'); ?></td>
                                <td><span class="status-pill <?php echo $pill_class; ?>"><?php echo $pill_label; ?></span></td>
                                <td style="text-align: right; font-weight:700;">
                                    <?php echo number_format((int) $d['available_qty']); ?>
                                    <?php if ((int) $d['expired_qty'] > 0): ?>
                                        <span class="muted-note">+<?php echo number_format((int) $d['expired_qty']); ?> expired</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="edit-drug.php?medicine_id=<?php echo (int) $d['medicine_id']; ?>" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.75rem;" title="Edit / Update">Edit</a>
                                        <form action="../pharmacist_action.php" method="POST" style="display: inline;"
                                              data-name="<?php echo e($d['medicine_name']); ?>"
                                              onsubmit="return confirm('Remove ' + this.dataset.name + ' and all of its batches from your inventory?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_inventory">
                                            <input type="hidden" name="medicine_id" value="<?php echo (int) $d['medicine_id']; ?>">
                                            <button type="submit" style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;" class="btn-danger" title="Delete Drug">Delete</button>
                                        </form>
                                    </div>
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
