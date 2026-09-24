<?php
// ==========================================
// 1. PHP LOGIC (Database & Security)
// ==========================================
$current_page = 'help';
$page_title   = 'Help Documentation';
require_once __DIR__ . '/../../../includes/pharmacist_init.php';

$errors  = [];
$subject = '';
$message = '';

// Support tickets are handled by modules/pharmacist/pharmacist_action.php.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help &amp; Support - CancerCare</title>
    <link rel="stylesheet" href="../../../public/css/pharmacist.css">
</head>
<body>

<div class="app">
<?php include __DIR__ . '/../../../includes/pharmacist_sidebar.php'; ?>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="main">
<?php include __DIR__ . '/../../../includes/pharmacist_topbar.php'; ?>

        <div class="content" style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">
            <?php flash_render(); ?>

            <!-- System FAQ Section -->
            <section class="widget">
                <div class="widget-header">
                    <h3>Frequently Asked Questions</h3>
                </div>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <strong style="display:block; margin-bottom: 4px; color: var(--primary);">How do I register a medicine that is not in the catalog?</strong>
                        <p style="font-size: 0.9rem; color: var(--gray-600);">Use "Add New Drug". Enter the brand name, optional generic name and manufacturer, a category, the opening stock, and the batch number and expiry date of that first batch. The medicine is added to the shared hospital catalog and to your inventory.</p>
                    </div>
                    <hr style="border: 0; border-top: 1px solid var(--gray-200);">
                    <div>
                        <strong style="display:block; margin-bottom: 4px; color: var(--primary);">What defines a "Low Stock Alert"?</strong>
                        <p style="font-size: 0.9rem; color: var(--gray-600);">A medicine is flagged "Low Stock" when its available quantity is <?php echo LOW_STOCK_THRESHOLD; ?> units or fewer, and "Out of Stock" at zero. Expired batches never count as available stock.</p>
                    </div>
                    <hr style="border: 0; border-top: 1px solid var(--gray-200);">
                    <div>
                        <strong style="display:block; margin-bottom: 4px; color: var(--primary);">How does removing / dispensing stock choose a batch?</strong>
                        <p style="font-size: 0.9rem; color: var(--gray-600);">If you enter a batch number, stock is removed from that batch only. If you leave it blank, stock is taken from the unexpired batches with the earliest expiry date first.</p>
                    </div>
                    <hr style="border: 0; border-top: 1px solid var(--gray-200);">
                    <div>
                        <strong style="display:block; margin-bottom: 4px; color: var(--primary);">What does "Delete" on the dashboard do?</strong>
                        <p style="font-size: 0.9rem; color: var(--gray-600);">It removes that medicine and all of its batches from your pharmacy inventory. The medicine stays in the shared catalog so existing prescriptions are not affected.</p>
                    </div>
                </div>
            </section>

            <!-- Technical Assistance Support Box -->
            <section class="widget">
                <div class="widget-header">
                    <h3>Contact System Support</h3>
                </div>
                <p style="font-size: 0.9rem; color: var(--gray-600); margin-bottom: 16px;">
                    If you encounter software errors or require administrative inventory privilege corrections, please send a technical ticket down below.
                </p>

                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-error"><?php echo e($err); ?></div>
                <?php endforeach; ?>

                <form class="pure-form" action="../pharmacist_action.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="support_ticket">
                    <div class="form-group">
                        <label for="ticket-subject">Issue Category / Subject</label>
                        <input type="text" id="ticket-subject" name="subject" maxlength="150" value="<?php echo e($subject); ?>" placeholder="e.g., Unable to edit batch serial error" required>
                    </div>
                    <div class="form-group" style="margin-top:16px;">
                        <label for="ticket-msg">Detailed Problem Description</label>
                        <textarea id="ticket-msg" name="message" rows="4" maxlength="2000" placeholder="Describe the error tracking details clearly..." required><?php echo e($message); ?></textarea>
                    </div>
                    <div class="form-actions-inline">
                        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Submit Help Desk Ticket</button>
                    </div>
                </form>
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
