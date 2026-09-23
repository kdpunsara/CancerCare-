<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../../../index.php");
    exit();
}


$doctor_id = $_SESSION['user_id'];
$stmt_profile = $conn->prepare("SELECT u.username, u.email, u.phone,
                                      d.first_name, d.last_name, d.specialization, d.qualification, d.license_no
                               FROM User u
                               INNER JOIN Doctor d ON u.user_id = d.user_id
                               WHERE u.user_id = ?");
$stmt_profile->bind_param("i", $doctor_id);
$stmt_profile->execute();
$profile = $stmt_profile->get_result()->fetch_assoc();

if (!$profile) {
    header("Location: ../../../logout.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Cancer Care</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
</head>
<body>
    <div class="app">
        <?php $current_page = 'profile'; require_once __DIR__ . '/../../../includes/doctor_sidebar.php'; ?>

        <main class="main">
            <header class="topbar">
                <div class="topbar-left">
                    <div>
                        <h2>My Profile</h2>
                        <p class="date">View and update your doctor account</p>
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
                <?php elseif (isset($_GET['error'])): ?>
                    <div class="card panel">
                        <p class="panel-note" style="color: var(--red);">Could not update your profile. Please check the values and try again.</p>
                    </div>
                <?php endif; ?>

                <div class="card form-card">
                    <div class="list-card-header">
                        <h3>Account Information</h3>
                    </div>
                    <form method="POST" action="../doctor_actions.php" class="form-grid">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-field">
                            <label>Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($profile['username']); ?>" disabled>
                        </div>
                        <div class="form-field">
                            <label>License No *</label>
                            <input type="text" value="<?php echo htmlspecialchars($profile['license_no']); ?>" readonly>
                        </div>
                        <div class="form-field">
                            <label>First Name *</label>
                            <input type="text" value="<?php echo htmlspecialchars($profile['first_name']); ?>" readonly>
                        </div>
                        <div class="form-field">
                            <label>Last Name *</label>
                            <input type="text" value="<?php echo htmlspecialchars($profile['last_name']); ?>" readonly>
                        </div>
                        <div class="form-field">
                            <label>Email *</label>
                            <input type="email" name="email" required value="<?php echo htmlspecialchars($profile['email']); ?>">
                        </div>
                        <div class="form-field">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-field">
                            <label>Specialization</label>
                            <input type="text" value="<?php echo htmlspecialchars($profile['specialization'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-field">
                            <label>Qualification</label>
                            <input type="text" value="<?php echo htmlspecialchars($profile['qualification'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-field form-field-wide">
                            <label>New Password</label>
                            <input type="password" name="password" placeholder="Leave blank to keep your current password">
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
