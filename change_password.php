<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password === '' || $confirm_password === '') {
        $error_message = 'Please fill in both password fields.';
    } elseif (strlen($new_password) < 8) {
        $error_message = 'Your new password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'The passwords do not match.';
    } else {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE User SET password_hash = ?, must_change_password = 0 WHERE user_id = ?');
        $stmt->bind_param('si', $password_hash, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $role = $_SESSION['role'];
            switch ($role) {
                case 'patient': $destination = 'modules/patient/views/patient_dashboard.php'; break;
                case 'doctor': $destination = 'modules/doctor/views/doctor_dashboard.php'; break;
                case 'staff': $destination = 'modules/staff/views/dashboard.php'; break;
                case 'pharmacist': $destination = 'modules/pharmacist/views/dashboard.php'; break;
                case 'benefactor': $destination = 'modules/benefactor/views/dashboard.php'; break;
                case 'admin': $destination = 'modules/admin/views/admin_dashboard.php'; break;
                default: $destination = 'index.php'; break;
            }

            header('Location: ' . $destination);
            exit();
        }

        $error_message = 'Could not update your password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - CancerCare</title>
    <link rel="stylesheet" href="public/css/base.css">
    <style>
        :root {
            --navy: #101a33;
            --blue: #4f6ef7;
            --bg: #f2f4f9;
            --card: #ffffff;
            --border: #e7e9f2;
            --text: #14162b;
            --muted: #6b7280;
            --red: #e0435c;
        }

        *, *::before, *::after { box-sizing: border-box; }
        body {
            display: grid;
            place-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .password-card {
            width: min(100%, 460px);
            padding: 32px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(16, 24, 40, 0.12);
        }
        h1 { margin: 0 0 8px; font-size: 24px; }
        .intro { margin: 0 0 24px; color: var(--muted); line-height: 1.5; }
        label { display: block; margin: 16px 0 6px; font-size: 13px; font-weight: 600; }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font: inherit;
        }
        button {
            width: 100%;
            margin-top: 24px;
            padding: 12px;
            border: 0;
            border-radius: 8px;
            background: var(--blue);
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        .error { margin: 0 0 12px; color: var(--red); font-size: 14px; }
    </style>
</head>
<body>
    <main class="password-card">
        <h1>Set a New Password</h1>
        <p class="intro">For security, you must create a new password before continuing.</p>

        <?php if ($error_message !== ''): ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="new_password">New Password</label>
            <input id="new_password" type="password" name="new_password" minlength="8" required>

            <label for="confirm_password">Confirm New Password</label>
            <input id="confirm_password" type="password" name="confirm_password" minlength="8" required>

            <button type="submit">Save New Password</button>
        </form>
    </main>
</body>
</html>
