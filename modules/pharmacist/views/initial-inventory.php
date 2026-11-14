<?php
$current_page = 'initial-inventory';
$page_title = 'Initial Inventory Registration';
require_once __DIR__ . '/../../../includes/pharmacist_init.php';

if ($initial_inventory_completed) {
    header('Location: dashboard.php');
    exit();
}

$medicine_options = [];
$res = $conn->query(
    "SELECT medicine_id, medicine_name, generic_name, category, manufacturer
     FROM Medicine ORDER BY medicine_name ASC, medicine_id ASC"
);
if ($res) {
    $medicine_options = $res->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial Inventory Registration - CancerCare</title>
    <link rel="stylesheet" href="../../../public/css/pharmacist.css">
    <style>
        .inventory-row { border: 1px solid var(--gray-200); border-radius: var(--radius); padding: 18px; margin-bottom: 14px; }
        .inventory-row-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .progress-wrap { margin: 14px 0 22px; }
        .progress-track { height: 10px; background: var(--gray-200); border-radius: 50px; overflow: hidden; }
        .progress-bar { height: 100%; width: 0; background: var(--teal); transition: width .2s ease; }
        .progress-label { display: block; font-weight: 700; margin-bottom: 8px; }
        .remove-row { color: var(--danger); font-size: .85rem; font-weight: 600; }
    </style>
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/../../../includes/pharmacist_sidebar.php'; ?>
    <main class="main">
        <?php include __DIR__ . '/../../../includes/pharmacist_topbar.php'; ?>
        <div class="content">
            <?php flash_render(); ?>
            <div class="widget">
                <div class="widget-header">
                    <h3>Register Your Initial Pharmacy Inventory</h3>
                    <p class="field-hint">Register at least 10 different, unexpired medicines to unlock the pharmacy dashboard. You can add the rest of your inventory later.</p>
                </div>
                <form class="pure-form" action="../pharmacist_action.php" method="POST" id="initial-inventory-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="initial_register">
                    <div class="progress-wrap">
                        <span class="progress-label" id="progress-label">0/10 medicines registered</span>
                        <div class="progress-track"><div class="progress-bar" id="progress-bar"></div></div>
                    </div>
                    <div id="inventory-rows">
                        <div class="inventory-row">
                            <div class="inventory-row-head"><strong>Medicine 1</strong><button type="button" class="remove-row" hidden>Remove</button></div>
                            <div class="form-grid cols-2">
                                <div class="form-group"><label>Medicine Master Record <span class="req">*</span></label>
                                    <select name="medicine_id[]" required>
                                        <option value="">Select a medicine</option>
                                        <?php foreach ($medicine_options as $medicine): ?>
                                            <option value="<?php echo (int) $medicine['medicine_id']; ?>">
                                                <?php echo e($medicine['medicine_name']); ?><?php echo $medicine['generic_name'] ? ' - ' . e($medicine['generic_name']) : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group"><label>Batch Number <span class="req">*</span></label><input name="batch_number[]" maxlength="50" required></div>
                                <div class="form-group"><label>Expiry Date <span class="req">*</span></label><input type="date" name="expiry_date[]" min="<?php echo date('Y-m-d'); ?>" required></div>
                                <div class="form-group"><label>Initial Stock Quantity <span class="req">*</span></label><input type="number" name="initial_stock[]" min="0" step="1" required></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions-inline">
                        <button type="button" class="btn btn-outline" id="add-medicine">Add Another Medicine</button>
                        <button type="submit" class="btn btn-primary">Submit Initial Inventory</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
<script>
const rows = document.getElementById('inventory-rows');
const progressLabel = document.getElementById('progress-label');
const progressBar = document.getElementById('progress-bar');
const today = new Date().toISOString().slice(0, 10);

function updateProgress() {
    const validRows = Array.from(rows.querySelectorAll('.inventory-row')).filter(row => {
        const required = row.querySelectorAll('[required]');
        return Array.from(required).every(input => input.value.trim() !== '')
            && row.querySelector('[name="expiry_date[]"]').value >= today;
    }).length;
    progressLabel.textContent = `${validRows}/10 medicines registered`;
    progressBar.style.width = `${Math.min(validRows, 10) * 10}%`;
}

function refreshRows() {
    rows.querySelectorAll('.inventory-row').forEach((row, index) => {
        row.querySelector('strong').textContent = `Medicine ${index + 1}`;
        row.querySelector('.remove-row').hidden = rows.children.length === 1;
    });
    updateProgress();
}

document.getElementById('add-medicine').addEventListener('click', () => {
    const row = rows.firstElementChild.cloneNode(true);
    row.querySelectorAll('input').forEach(input => { input.value = ''; });
    rows.appendChild(row);
    refreshRows();
});

rows.addEventListener('click', event => {
    if (event.target.classList.contains('remove-row')) {
        event.target.closest('.inventory-row').remove();
        refreshRows();
    }
});
rows.addEventListener('input', updateProgress);
updateProgress();
</script>
</body>
</html>
