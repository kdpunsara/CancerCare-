<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'staff') {
    header("Location: ../../../login.php");
    exit();
}

$staff_id = (int) $_SESSION['user_id'];
$stmt = $conn->prepare(
    "SELECT u.user_id, u.username, u.email, u.phone,
            s.first_name, s.last_name, s.designation, s.department, s.employee_id
     FROM User u
     INNER JOIN Medical_Staff s ON s.user_id = u.user_id
     WHERE u.user_id = ?"
);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    header("Location: ../../../logout.php");
    exit();
}

$full_name = trim($profile['first_name'] . ' ' . $profile['last_name']);
$initials = strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1));
$error_messages = [
    'short_password' => 'Password must be at least 6 characters long.',
    'password_mismatch' => 'The passwords do not match.',
    'update_failed' => 'Could not update your profile. Please try again.'
];
$error_message = $error_messages[$_GET['error'] ?? ''] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Profile - Cancer Care</title>
    <link rel="stylesheet" href="../../../public/css/styles.css">
    <link rel="stylesheet" href="../../../public/css/modules.css">
    <style>
        .profile-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .profile-form .wide {
            grid-column: 1 / -1;
        }

        .profile-form input {
            width: 100%;
            box-sizing: border-box;
        }

        .profile-form input[readonly] {
            background: var(--gray-50);
            color: var(--gray-600);
        }

        .profile-note {
            color: var(--gray-500);
            font-size: 0.84rem;
            margin: 0 0 20px;
        }

        .alert {
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 18px;
            padding: 12px 16px;
        }

        .alert-ok { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }

        @media (max-width: 640px) {
            .profile-form { grid-template-columns: 1fr; }
            .profile-form .wide { grid-column: auto; }
        }
    </style>
</head>
<body>
<div class="app">
    <?php $activePage = 'profile'; require __DIR__ . '/sidebar.php'; ?>

    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <h1>My Profile</h1>
                <p>View your staff account details and update contact information.</p>
            </div>
            <div class="topbar-right">
                <a href="../../../logout.php" class="btn btn-outline">Sign Out</a>
                <button class="sidebar-toggle" id="sidebarToggle" type="button">☰</button>
            </div>
        </header>

        <div class="content">
            <?php if (($_GET['msg'] ?? '') === 'profile_updated'): ?>
                <div class="alert alert-ok">Profile updated successfully.</div>
            <?php elseif ($error_message !== ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="widget">
                <div class="widget-header">
                    <div>
                        <h2>Staff Account</h2>
                        <p class="profile-note">Only your phone number and password can be changed.</p>
                    </div>
                    <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                </div>

                <form method="POST" action="medical_actions.php" class="profile-form">
                    <input type="hidden" name="action" value="update_staff_profile">

                    <div class="form-group">
                        <label for="user_id">User ID</label>
                        <input id="user_id" class="form-control" type="text" value="<?php echo (int) $profile['user_id']; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input id="username" class="form-control" type="text" value="<?php echo htmlspecialchars($profile['username']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input id="email" class="form-control" type="email" value="<?php echo htmlspecialchars($profile['email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input id="first_name" class="form-control" type="text" value="<?php echo htmlspecialchars($profile['first_name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input id="last_name" class="form-control" type="text" value="<?php echo htmlspecialchars($profile['last_name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="designation">Designation</label>
                        <input id="designation" class="form-control" type="text" value="<?php echo htmlspecialchars($profile['designation'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input id="department" class="form-control" type="text" value="<?php echo htmlspecialchars($profile['department'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="employee_id">Employee ID</label>
                        <input id="employee_id" class="form-control" type="text" value="<?php echo htmlspecialchars($profile['employee_id'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input id="phone" class="form-control" type="text" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input id="password" class="form-control" type="password" name="password" minlength="6" placeholder="Leave blank to keep current password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input id="confirm_password" class="form-control" type="password" name="confirm_password" minlength="6" placeholder="Repeat new password">
                    </div>
                    <div class="form-actions wide">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
<script>
    const toggleButton = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggleButton && sidebar) toggleButton.addEventListener('click', () => sidebar.classList.toggle('open'));
</script>
</body>
</html>
