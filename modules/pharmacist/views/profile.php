<?php
// ==========================================
// 1. PHP LOGIC (Database & Security)
// ==========================================
$current_page = 'profile';
$page_title   = 'User Profile';
require_once __DIR__ . '/../../../includes/pharmacist_init.php';

$errors = [];
// Form values (start from the database, overwritten by a failed submit so nothing is lost)
$form = [
    'email'   => $pharmacist['email'],
    'phone'   => $pharmacist['phone'] ?? '',
    'address' => $pharmacist['address'] ?? '',
];

// Profile updates are handled by modules/pharmacist/pharmacist_action.php.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - CancerCare</title>
    <link rel="stylesheet" href="../../../public/css/pharmacist.css">
</head>
<body>

<div class="app">
<?php include __DIR__ . '/../../../includes/pharmacist_sidebar.php'; ?>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="main">
<?php include __DIR__ . '/../../../includes/pharmacist_topbar.php'; ?>

        <div class="content">
            <div class="widget" style="max-width: 700px; margin: 0 auto;">
                <div style="text-align: center; margin-bottom: 24px;">
                    <!-- Circular User Badge Layout -->
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--teal), var(--primary)); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; color: white; margin: 0 auto 12px;"><?php echo e($pharmacist_initials); ?></div>
                    <h2><?php echo e($pharmacist_name); ?></h2>
                    <p style="color: var(--gray-500); font-size: 0.9rem;"><?php echo e($pharmacist['pharmacy_name'] ?? 'Pharmacist'); ?></p>
                </div>

                <?php flash_render(); ?>
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-error"><?php echo e($err); ?></div>
                <?php endforeach; ?>

                <form class="pure-form" action="../pharmacist_action.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="update_profile">
                    <?php $ro = 'background-color: var(--gray-100); color: var(--gray-500);'; ?>
                    <div class="form-grid cols-2">
                        <div class="form-group">
                            <label for="user-id">Pharmacist ID</label>
                            <input type="text" id="user-id" value="<?php echo (int) $pharmacist_id; ?>" disabled style="<?php echo $ro; ?>">
                        </div>
                        <div class="form-group">
                            <label for="user-license">License No.</label>
                            <input type="text" id="user-license" value="<?php echo e($pharmacist['license_no'] ?? '—'); ?>" disabled style="<?php echo $ro; ?>">
                        </div>
                    </div>

                    <div class="form-grid cols-2" style="margin-top:16px;">
                        <div class="form-group">
                            <label for="user-username">Username</label>
                            <input type="text" id="user-username" value="<?php echo e($pharmacist['username']); ?>" disabled style="<?php echo $ro; ?>">
                        </div>
                        <div class="form-group">
                            <label for="user-role">Assigned System Role</label>
                            <input type="text" id="user-role" value="Pharmacist" disabled style="<?php echo $ro; ?>">
                        </div>
                    </div>

                    <div class="form-grid cols-2" style="margin-top:16px;">
                        <div class="form-group">
                            <label for="user-email">Email Address <span class="req">*</span></label>
                            <input type="email" id="user-email" name="email" maxlength="100" value="<?php echo e($form['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="user-phone">Phone</label>
                            <input type="text" id="user-phone" name="phone" maxlength="20" value="<?php echo e($form['phone']); ?>">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:16px;">
                        <label for="user-address">Pharmacy Address</label>
                        <input type="text" id="user-address" name="address" value="<?php echo e($form['address']); ?>">
                    </div>

                    <div class="form-group" style="margin-top:16px;">
                        <label for="user-pwd-cur">Current Password</label>
                        <input type="password" id="user-pwd-cur" name="current_password" placeholder="Only needed to change your password" autocomplete="current-password">
                    </div>

                    <div class="form-grid cols-2" style="margin-top:16px;">
                        <div class="form-group">
                            <label for="user-pwd">Update Password</label>
                            <input type="password" id="user-pwd" name="new_password" placeholder="••••••••" autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label for="user-pwd-conf">Confirm New Password</label>
                            <input type="password" id="user-pwd-conf" name="confirm_password" placeholder="••••••••" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="form-actions-inline">
                        <button type="submit" class="btn btn-primary">Save Profile Changes</button>
                    </div>
                </form>
            </div>
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
