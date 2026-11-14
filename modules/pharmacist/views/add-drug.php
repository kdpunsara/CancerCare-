<?php
// ==========================================
// 1. PHP LOGIC (Database & Security)
// ==========================================
$current_page = 'add-drug';
$page_title   = 'Add New Medicine';
require_once __DIR__ . '/../../../includes/pharmacist_init.php';

$errors = [];
$old = ['medicine_name' => '', 'generic_name' => '', 'category' => '', 'manufacturer' => '',
        'initial_stock' => '', 'batch_number' => '', 'expiry_date' => ''];

// The form submits to the centralized pharmacist action handler.

// Category suggestions = defaults + whatever already exists in the shared catalog
$category_options = ['Chemotherapy', 'Hormone Therapy', 'Immunotherapy', 'Supportive Care'];
$res = $conn->query("SELECT DISTINCT category FROM Medicine WHERE category IS NOT NULL AND category <> ''");
foreach ($res->fetch_all(MYSQLI_ASSOC) as $r) {
    $category_options[] = $r['category'];
}
$category_options = array_unique($category_options);
sort($category_options);

// Existing catalog medicines can be searched and selected to reuse their details.
$medicine_options = [];
$res = $conn->query("SELECT medicine_id, medicine_name, generic_name, category, manufacturer FROM Medicine ORDER BY medicine_name ASC");
foreach ($res->fetch_all(MYSQLI_ASSOC) as $medicine) {
    $medicine_options[] = $medicine;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Drug - CancerCare</title>
    <link rel="stylesheet" href="../../../public/css/pharmacist.css">
</head>
<body>

<div class="app">
<?php include __DIR__ . '/../../../includes/pharmacist_sidebar.php'; ?>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="main">
<?php include __DIR__ . '/../../../includes/pharmacist_topbar.php'; ?>

        <div class="content">
            <div class="widget" style="max-width: 800px; margin: 0 auto;">
                <div class="widget-header">
                    <h3>Drug Registration Form</h3>
                </div>

                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-error"><?php echo e($err); ?></div>
                <?php endforeach; ?>

                <form class="pure-form" action="../pharmacist_action.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="add_drug">
                    <input type="hidden" id="medicine-id" name="medicine_id" value="">
                    <div class="form-group">
                        <label for="drug-name">Drug Name / Brand <span class="req">*</span></label>
                        <input type="text" id="drug-name" name="medicine_name" maxlength="200" list="medicine-list" value="<?php echo e($old['medicine_name']); ?>" placeholder="Search or enter a new medicine" required>
                        <datalist id="medicine-list">
                            <?php foreach ($medicine_options as $medicine): ?>
                                <option value="<?php echo e($medicine['medicine_name']); ?>" data-medicine-id="<?php echo (int) $medicine['medicine_id']; ?>" data-generic-name="<?php echo e($medicine['generic_name']); ?>" data-category="<?php echo e($medicine['category']); ?>" data-manufacturer="<?php echo e($medicine['manufacturer']); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <span class="field-hint">Choose an existing medicine to fill its catalog details automatically, or enter a new one.</span>
                    </div>

                    <div class="form-group">
                        <label for="generic-name">Generic Chemical Name</label>
                        <input type="text" id="generic-name" name="generic_name" maxlength="200" value="<?php echo e($old['generic_name']); ?>" placeholder="e.g., Cisplatin injection">
                    </div>

                    <div class="form-grid cols-2">
                        <div class="form-group">
                            <label for="drug-category">Category <span class="req">*</span></label>
                            <input type="text" id="drug-category" name="category" maxlength="100" list="category-list" value="<?php echo e($old['category']); ?>" placeholder="Select or type a category" required>
                            <datalist id="category-list">
                                <?php foreach ($category_options as $c): ?>
                                    <option value="<?php echo e($c); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label for="manufacturer">Manufacturer</label>
                            <input type="text" id="manufacturer" name="manufacturer" maxlength="150" value="<?php echo e($old['manufacturer']); ?>" placeholder="e.g., Cipla Ltd">
                        </div>
                    </div>

                    <div class="form-grid cols-2">
                        <div class="form-group">
                            <label for="initial-stock">Initial Stock Level <span class="req">*</span></label>
                            <input type="number" id="initial-stock" name="initial_stock" min="0" value="<?php echo e($old['initial_stock']); ?>" placeholder="0" required>
                        </div>
                        <div class="form-group">
                            <label for="batch-number">Batch / Lot Number <span class="req">*</span></label>
                            <input type="text" id="batch-number" name="batch_number" maxlength="50" value="<?php echo e($old['batch_number']); ?>" placeholder="e.g., LOT-2026-X9" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="expiry-date">Expiry Date <span class="req">*</span></label>
                        <input type="date" id="expiry-date" name="expiry_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo e($old['expiry_date']); ?>" required>
                        <span class="field-hint">Low-stock warnings trigger automatically at <?php echo LOW_STOCK_THRESHOLD; ?> units or fewer.</span>
                    </div>

                    <div class="form-actions-inline">
                        <button type="reset" class="btn btn-outline">Clear Fields</button>
                        <button type="submit" class="btn btn-primary">Save to Catalog</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
<script>
const drugNameInput = document.getElementById('drug-name');
const medicineIdInput = document.getElementById('medicine-id');
const medicineOptions = Array.from(document.querySelectorAll('#medicine-list option'));

drugNameInput.addEventListener('input', function () {
    const selected = medicineOptions.find(option => option.value === drugNameInput.value);

    medicineIdInput.value = selected ? selected.dataset.medicineId : '';
    if (!selected) {
        return;
    }

    document.getElementById('generic-name').value = selected.dataset.genericName || '';
    document.getElementById('drug-category').value = selected.dataset.category || '';
    document.getElementById('manufacturer').value = selected.dataset.manufacturer || '';
});

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
