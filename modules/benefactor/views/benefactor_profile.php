<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'benefactor') {
    header("Location: ../../../login.php");
    exit();
}

$benefactor_id = (int) $_SESSION['user_id'];
$stmt_profile = $conn->prepare("SELECT u.user_id, u.username, u.email, u.phone,
                                       b.first_name, b.last_name, b.benefactor_type,
                                       b.organization_name, b.address, b.country
                                FROM User u
                                INNER JOIN Benefactor b ON u.user_id = b.user_id
                                WHERE u.user_id = ?");
$stmt_profile->bind_param("i", $benefactor_id);
$stmt_profile->execute();
$profile = $stmt_profile->get_result()->fetch_assoc();

if (!$profile) {
    header("Location: ../../../logout.php");
    exit();
}

$full_name = $profile['first_name'] . ' ' . $profile['last_name'];
$initials = strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1));
$role_display = ucfirst($profile['benefactor_type']) . ' Benefactor';

$error_messages = [
    'invalid_email' => 'Please enter a valid email address.',
    'short_password' => 'Password must be at least 6 characters long.',
    'password_mismatch' => 'The passwords do not match.',
    'update_failed' => 'Could not update your profile. Please check whether the email is already in use.'
];
$error_message = $error_messages[$_GET['error'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CancerCare</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
</head>
<body>
    <div class="app">
        <?php
        $current_page = 'profile';
        require_once __DIR__ . '/../../../includes/benefactor_sidebar.php';
        ?>

        <main class="main">
            <header class="topbar">
                <div class="topbar-left">
                    <div>
                        <h2>My Profile</h2>
                        <p class="date">View your benefactor details and update your account contact information.</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <a href="../../../logout.php" class="signout-btn">Sign Out</a>
                </div>
            </header>

            <div class="content">
                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'profile_updated'): ?>
                    <div class="card panel">
                        <p class="panel-note" style="color: var(--teal);">Profile updated successfully.</p>
                    </div>
                <?php elseif ($error_message !== ''): ?>
                    <div class="card panel">
                        <p class="panel-note" style="color: var(--red);"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                <?php endif; ?>

                <div class="card form-card">
                    <div class="list-card-header">
                        <h3>Account Information</h3>
                    </div>
                    <form method="POST" action="../benefactor_actions.php" class="form-grid">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-field">
                            <label>User ID</label>
                            <input type="text" value="<?php echo (int) $profile['user_id']; ?>" readonly>
                        </div>
                        <div class="form-field">
                            <label>Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($profile['username']); ?>" readonly>
                        </div>
                        <div class="form-field">
                            <label>Benefactor Type</label>
                            <input type="text" value="<?php echo htmlspecialchars($role_display); ?>" readonly>
                        </div>
                        <div class="form-field">
                            <label>First Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($profile['first_name']); ?>" readonly>
                        </div>
                        <div class="form-field">
                            <label>Last Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($profile['last_name']); ?>" readonly>
                        </div>
                        <div class="form-field">
                            <label>Email Address *</label>
                            <input type="email" name="email" required value="<?php echo htmlspecialchars($profile['email']); ?>">
                        </div>
                        <div class="form-field">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-field">
                            <label>Organization Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($profile['organization_name'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-field">
                            <label>Country</label>
                            <input type="text" value="<?php echo htmlspecialchars($profile['country'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-field form-field-wide">
                            <label>Address</label>
                            <textarea rows="2" readonly><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-field">
                            <label>New Password</label>
                            <input type="password" name="password" minlength="6" placeholder="Leave blank to keep current password">
                        </div>
                        <div class="form-field">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" minlength="6" placeholder="Repeat new password">
                        </div>

                        <div class="form-actions form-field-wide">
                            <button type="submit" class="btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
