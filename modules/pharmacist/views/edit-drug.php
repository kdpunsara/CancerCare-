<?php
$current_page = 'dashboard';
$page_title   = 'Edit Medicine';
require_once __DIR__ . '/../../../includes/pharmacist_init.php';

$errors = [];
$medicine_id = (int) ($_GET['medicine_id'] ?? $_POST['medicine_id'] ?? 0);
$inventory_id = (int) ($_GET['inventory_id'] ?? $_POST['inventory_id'] ?? 0);
$medicine = null;
$batches = [];

if ($medicine_id > 0) {
    $stmt = $conn->prepare("SELECT medicine_id, medicine_name, generic_name, category, manufacturer FROM Medicine WHERE medicine_id = ? LIMIT 1");
    $stmt->bind_param("i", $medicine_id);
    $stmt->execute();
    $medicine = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($medicine) {
        $stmt = $conn->prepare("SELECT inventory_id, stock_quantity, batch_number, expiry_date FROM PharmacyInventory WHERE pharmacist_user_id = ? AND medicine_id = ? ORDER BY expiry_date ASC, inventory_id ASC");
        $stmt->bind_param("ii", $pharmacist_id, $medicine_id);
        $stmt->execute();
        $batches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

if (!$medicine) {
    flash_set('error', 'Medicine not found.');
    header('Location: dashboard.php');
    exit();
}

if ($inventory_id === 0 && !empty($batches)) {
    $inventory_id = (int) $batches[0]['inventory_id'];
}

// POST handling moved to modules/pharmacist/pharmacist_action.php.

$selected_batch = null;
foreach ($batches as $b) {
    if ((int)$b['inventory_id'] === $inventory_id) { $selected_batch = $b; break; }
}
if (!$selected_batch) $selected_batch = ['stock_quantity'=>'', 'batch_number'=>'', 'expiry_date'=>''];

$category_options = ['Chemotherapy', 'Hormone Therapy', 'Immunotherapy', 'Supportive Care'];
$res = $conn->query("SELECT DISTINCT category FROM Medicine WHERE category IS NOT NULL AND category <> ''");
foreach ($res->fetch_all(MYSQLI_ASSOC) as $r) $category_options[] = $r['category'];
$category_options = array_unique($category_options);
sort($category_options);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Medicine - CancerCare</title>
<link rel="stylesheet" href="../../../public/css/pharmacist.css">
</head>
<body>
<div class="app">
<?php include __DIR__ . '/../../../includes/pharmacist_sidebar.php'; ?>
<main class="main">
<?php include __DIR__ . '/../../../includes/pharmacist_topbar.php'; ?>
<div class="content">
<div class="widget" style="max-width:800px;margin:0 auto;">
<div class="widget-header"><h3>Edit Medicine Details</h3></div>
<?php foreach ($errors as $err): ?><div class="alert alert-error"><?php echo e($err); ?></div><?php endforeach; ?>
<form class="pure-form" action="../pharmacist_action.php" method="POST">
<?php echo csrf_field(); ?><input type="hidden" name="action" value="update_drug"><input type="hidden" name="medicine_id" value="<?php echo (int)$medicine_id; ?>">
<div class="form-group"><label for="drug-name">Medicine Name <span class="req">*</span></label><input type="text" id="drug-name" name="medicine_name" maxlength="200" value="<?php echo e($medicine['medicine_name']); ?>" required></div>
<div class="form-group"><label for="generic-name">Generic Name</label><input type="text" id="generic-name" name="generic_name" maxlength="200" value="<?php echo e($medicine['generic_name']); ?>"></div>
<div class="form-grid cols-2">
<div class="form-group"><label for="drug-category">Category <span class="req">*</span></label><input type="text" id="drug-category" name="category" maxlength="100" list="category-list" value="<?php echo e($medicine['category']); ?>" required><datalist id="category-list"><?php foreach ($category_options as $c): ?><option value="<?php echo e($c); ?>"></option><?php endforeach; ?></datalist></div>
<div class="form-group"><label for="manufacturer">Manufacturer</label><input type="text" id="manufacturer" name="manufacturer" maxlength="150" value="<?php echo e($medicine['manufacturer']); ?>"></div>
</div>
<?php if (count($batches) > 1): ?>
<div class="form-group"><label for="inventory-id">Batch to Edit <span class="req">*</span></label><select id="inventory-id" name="inventory_id" required><?php foreach ($batches as $b): ?><option value="<?php echo (int)$b['inventory_id']; ?>" <?php echo (int)$b['inventory_id'] === $inventory_id ? 'selected' : ''; ?>><?php echo e($b['batch_number'] ?: '(No batch)'); ?> - Qty: <?php echo (int)$b['stock_quantity']; ?> - Exp: <?php echo e($b['expiry_date']); ?></option><?php endforeach; ?></select></div>
<?php else: ?><input type="hidden" name="inventory_id" value="<?php echo (int)$inventory_id; ?>"><?php endif; ?>
<div class="form-grid cols-2">
<div class="form-group"><label for="quantity">Quantity <span class="req">*</span></label><input type="number" id="quantity" name="quantity" min="0" value="<?php echo e($_POST['quantity'] ?? $selected_batch['stock_quantity']); ?>" required></div>
<div class="form-group"><label for="batch-number">Batch / Lot Number <span class="req">*</span></label><input type="text" id="batch-number" name="batch_number" maxlength="50" value="<?php echo e($_POST['batch_number'] ?? $selected_batch['batch_number']); ?>" required></div>
</div>
<div class="form-group"><label for="expiry-date">Expiry Date <span class="req">*</span></label><input type="date" id="expiry-date" name="expiry_date" value="<?php echo e($_POST['expiry_date'] ?? $selected_batch['expiry_date']); ?>" required></div>
<div class="form-actions-inline"><a href="dashboard.php" class="btn btn-outline">Cancel</a><button type="submit" class="btn btn-primary">Save Changes</button></div>
</form>
</div></div></main></div>
<script>function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('show');}function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}</script>
</body></html>
