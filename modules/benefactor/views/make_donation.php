<?php
session_start();
require_once __DIR__ .  '/../../../config/database.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'benefactor') {
    header("Location: ../../../index.php");
    exit();
}

$benefactor_id = $_SESSION['user_id'];

// ---- Fetch Benefactor Profile for Sidebar ----
$stmt_profile = $conn->prepare("SELECT first_name, last_name, benefactor_type FROM Benefactor WHERE user_id = ?");
$stmt_profile->bind_param("i", $benefactor_id);
$stmt_profile->execute();
$profile = $stmt_profile->get_result()->fetch_assoc();

if (!$profile) {
    $profile = ['first_name' => 'Benefactor', 'last_name' => '', 'benefactor_type' => 'local'];
}
$full_name = $profile['first_name'] . ' ' . $profile['last_name'];
$initials = strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1));

// ---- Fetch Active Patient Needs for Dropdown ----
$sql_needs = "SELECT pn.need_id, pn.need_type, pn.title, pn.target_amount, pn.current_amount,
                     u.first_name, u.last_name
              FROM PatientNeed pn
              JOIN Patient u ON pn.patient_user_id = u.user_id
              WHERE pn.current_amount < pn.target_amount
              ORDER BY pn.created_at DESC";
$needs = $conn->query($sql_needs)->fetch_all(MYSQLI_ASSOC);

// Pre-select patient if passed via URL (e.g., from "Patients Needing Support" page)
$preselected_need_id = isset($_GET['need']) ? intval($_GET['need']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make a Donation - CancerCare</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
</head>
<body>
    <div class="app">
        <?php 
        $current_page = 'donate'; 
        require_once __DIR__ . '/../../../includes/benefactor_sidebar.php'; 
        ?>

        <main class="main">
            <header class="topbar">
                <div class="topbar-left">
                    <div>
                        <h2>Submit a Donation</h2>
                        <p class="date">Record your donation details and upload your bank transfer slip.</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <a href="../../../logout.php" class="signout-btn">Log Out</a>
                </div>
            </header>

            <div class="content">
                <?php if (isset($_SESSION['donation_error'])): ?>
                    <div class="card panel" style="border-left: 4px solid var(--red); margin-bottom: 20px;">
                        <p class="panel-note" style="color: var(--red);"><?php echo htmlspecialchars($_SESSION['donation_error']); unset($_SESSION['donation_error']); ?></p>
                    </div>
                <?php endif; ?>

                <div class="card form-card" style="max-width: 800px; margin: 0 auto;">
                    <h3 class="form-title">Donation Form</h3>
                    <p class="form-subtitle">Please fill out the details below and attach a clear image or PDF of your bank transfer slip.</p>

                    <form method="POST" action="../benefactor_actions.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="submit_donation">

                        <p class="form-section-label">Recipient Details</p>
                        <div class="form-grid">
                            <div class="form-field form-field-wide">
                                <label>Select Patient Need *</label>
                                <select name="need_id" id="need_select" required onchange="updateDonationType()">
                                    <option value="">-- General Fund / Not Assigned --</option>
                                    <?php foreach ($needs as $need): ?>
                                        <option value="<?php echo $need['need_id']; ?>" 
                                                data-type="<?php echo htmlspecialchars($need['need_type']); ?>"
                                                <?php echo ($preselected_need_id == $need['need_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($need['first_name'] . ' ' . $need['last_name']); ?> - <?php echo htmlspecialchars($need['title']); ?> (<?php echo htmlspecialchars($need['need_type']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <p class="form-section-label">Donation Details</p>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Donation Type *</label>
                                <select name="donation_type" id="donation_type" required onchange="toggleFields()">
                                    <option value="Financial Aid">Financial Aid</option>
                                    <option value="Equipment">Equipment / Medication</option>
                                </select>
                            </div>
                            
                            <!-- Financial Aid Fields -->
                            <div class="form-field" id="amount_field">
                                <label>Donated Amount *</label>
                                <input type="number" step="0.01" name="amount" placeholder="e.g. 50000">
                            </div>
                            <div class="form-field" id="currency_field">
                                <label>Currency</label>
                                <select name="currency">
                                    <option value="LKR" selected>LKR</option>
                                    <option value="USD">USD</option>
                                    <option value="GBP">GBP</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>

                            <!-- Equipment Fields (Hidden by default) -->
                            <div class="form-field" id="item_field" style="display: none;">
                                <label>Item / Medication Name *</label>
                                <input type="text" name="item_name" placeholder="e.g. Doxorubicin, Wheelchair">
                            </div>
                            <div class="form-field" id="quantity_field" style="display: none;">
                                <label>Quantity</label>
                                <input type="number" name="quantity" placeholder="e.g. 10">
                            </div>
                        </div>

                        <p class="form-section-label">Proof of Transfer</p>
                        <div class="form-grid">
                            <div class="form-field form-field-wide">
                                <label>Upload Bank Slip (Image or PDF) *</label>
                                <input type="file" name="bank_slip" accept=".jpg,.jpeg,.png,.pdf" required style="padding: 10px; border: 1px dashed var(--border); background: var(--bg);">
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">Accepted formats: JPG, PNG, PDF. Max size: 5MB.</p>
                            </div>
                            <div class="form-field form-field-wide">
                                <label>Notes / Reference Number</label>
                                <textarea name="notes" rows="2" placeholder="e.g. Bank reference number, specific instructions..."></textarea>
                            </div>
                        </div>

                        <div class="form-actions" style="margin-top: 24px;">
                            <a href="dashboard.php" class="btn-secondary">Cancel</a>
                            <button type="submit" class="btn-primary">Submit Donation Record</button>
                        </div>
                    </form>
                </div>

                <div class="card panel" style="max-width: 800px; margin: 24px auto 0; background: #f8f9fa; border-left: 4px solid var(--teal);">
                    <p class="panel-note" style="color: var(--text); font-size: 13.5px;">
                        <strong>Next Steps:</strong> Once submitted, your donation will be marked as "Pending Verification". Our hospital staff will verify the bank slip or received equipment and update the status to "Received".
                    </p>
                </div>
            </div>
        </main>
    </div>

    <!-- Simple JS to toggle fields based on selection -->
    <script>
        function updateDonationType() {
            const select = document.getElementById('need_select');
            const typeSelect = document.getElementById('donation_type');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption.value !== "") {
                typeSelect.value = selectedOption.getAttribute('data-type');
                toggleFields();
            }
        }

        function toggleFields() {
            const type = document.getElementById('donation_type').value;
            const amountField = document.getElementById('amount_field');
            const currencyField = document.getElementById('currency_field');
            const itemField = document.getElementById('item_field');
            const quantityField = document.getElementById('quantity_field');

            if (type === 'Financial Aid') {
                amountField.style.display = 'block';
                currencyField.style.display = 'block';
                itemField.style.display = 'none';
                quantityField.style.display = 'none';
                
                // Make amount required, item not
                document.querySelector('input[name="amount"]').required = true;
                document.querySelector('input[name="item_name"]').required = false;
            } else {
                amountField.style.display = 'none';
                currencyField.style.display = 'none';
                itemField.style.display = 'block';
                quantityField.style.display = 'block';
                
                // Make item required, amount not
                document.querySelector('input[name="amount"]').required = false;
                document.querySelector('input[name="item_name"]').required = true;
            }
        }
        
        // Initialize on load
        document.addEventListener('DOMContentLoaded', toggleFields);
    </script>
</body>
</html>