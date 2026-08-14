<?php
// ==========================================
// 1. SETUP & CONFIGURATION
// ==========================================
session_start();
require_once 'config/database.php'; // Adjust path if your file is elsewhere

// If user is already logged in, redirect them to their correct dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'patient': header("Location: modules/patient/views/dashboard.php"); break;
        case 'doctor': header("Location: modules/doctor/views/dashboard.php"); break;
        case 'staff': header("Location: modules/staff/views/dashboard.php"); break;
        case 'pharmacist': header("Location: modules/pharmacist/views/dashboard.php"); break;
        case 'benefactor': header("Location: modules/benefactor/views/dashboard.php"); break;
        case 'admin': header("Location: modules/admin/views/dashboard.php"); break;
        default: header("Location: index.php"); break;
    }
    exit();
}

$error_message = "";

// ==========================================
// 2. HANDLE LOGIN FORM SUBMISSION
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = trim($_POST['login_identifier']);
    $password = $_POST['password'];

    if (!empty($login_input) && !empty($password)) {
        // Query to check Username, User ID, OR Email
        $sql = "SELECT user_id, username, email, password_hash, role, status 
                FROM User 
                WHERE username = ? OR user_id = ? OR email = ? 
                LIMIT 1";
                
        $stmt = $conn->prepare($sql);
        
        // Bind parameters (using "sss" for string, string, string)
        $stmt->bind_param("sss", $login_input, $login_input, $login_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Check if account is active
            if ($user['status'] !== 'active') {
                $error_message = "Your account is inactive or suspended. Please contact the administrator.";
            } 
            // Verify the password securely
            elseif (password_verify($password, $user['password_hash'])) {
                
                // Success! Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on role
                switch ($user['role']) {
                    case 'patient': header("Location: modules/patient/views/dashboard.php"); break;
                    case 'doctor': header("Location: modules/doctor/views/dashboard.php"); break;
                    case 'staff': header("Location: modules/staff/views/dashboard.php"); break;
                    case 'pharmacist': header("Location: modules/pharmacist/views/dashboard.php"); break;
                    case 'benefactor': header("Location: modules/benefactor/views/dashboard.php"); break;
                    case 'admin': header("Location: modules/admin/views/dashboard.php"); break;
                    default: header("Location: index.php"); break;
                }
                exit();
            } else {
                $error_message = "Invalid password. Please try again.";
            }
        } else {
            $error_message = "No account found with that Username, User ID, or Email.";
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cancer Patient Care System</title>
    <!-- Importing the same font used in your dashboard -->
    <style>
        /* Reusing your exact CSS variables for consistency */
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #dbeafe;
            --teal: #0d9488;
            --navy: #0f172a;
            --navy-soft: #1e293b;
            --white: #ffffff;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-500: #64748b;
            --gray-600: #475569;
            --danger: #ef4444;
            --radius: 10px;
            --radius-lg: 16px;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        
        body {
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            display: flex;
            width: 100%;
            max-width: 900px;
            overflow: hidden;
        }

        /* Left Side: Branding */
        .login-branding {
            flex: 1;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-soft) 100%);
            color: var(--white);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
        }

        .login-branding h1 { font-size: 2rem; font-weight: 700; margin-bottom: 16px; }
        .login-branding p { font-size: 1rem; opacity: 0.8; line-height: 1.6; margin-bottom: 40px; }
        .branding-features { list-style: none; }
        .branding-features li { margin-bottom: 12px; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; opacity: 0.9; }
        .branding-features li::before { content: '✓'; color: var(--teal); font-weight: bold; }

        /* Right Side: Form */
        .login-form-wrapper {
            flex: 1;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form-wrapper h2 { font-size: 1.5rem; color: var(--navy); margin-bottom: 8px; }
        .login-form-wrapper .subtitle { color: var(--gray-500); font-size: 0.9rem; margin-bottom: 32px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--gray-600); margin-bottom: 8px; }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 0.95rem;
            transition: 0.2s ease;
        }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
            margin-top: 10px;
        }
        .btn-login:hover { background: var(--primary-dark); }

        .error-message {
            background: #fee2e2;
            color: var(--danger);
            padding: 12px 16px;
            border-radius: var(--radius);
            font-size: 0.85rem;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
        }

        .footer-links { text-align: center; margin-top: 24px; font-size: 0.85rem; color: var(--gray-500); }
        .footer-links a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .footer-links a:hover { text-decoration: underline; }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container { flex-direction: column; }
            .login-branding { padding: 40px 30px; }
            .login-form-wrapper { padding: 40px 30px; }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <!-- Left Side: Branding & Info -->
        <div class="login-branding">
            <h1>Cancer Care System</h1>
            <p>Welcome to the Apeksha Hospital digital management platform. Securely access your personalized dashboard.</p>
            <ul class="branding-features">
                <li>Patient & Doctor Portals</li>
                <li>Real-time Appointment Scheduling</li>
                <li>Secure Medical Records</li>
                <li>Pharmacy & Benefactor Management</li>
            </ul>
        </div>

        <!-- Right Side: Login Form -->
        <div class="login-form-wrapper">
            <h2>Sign In</h2>
            <p class="subtitle">Enter your credentials to access your account</p>

            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="login_identifier">Username, User ID, or Email</label>
                    <input type="text" id="login_identifier" name="login_identifier" placeholder="e.g. 100001, dr_noel, or email@hospital.lk" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn-login">Login to Dashboard</button>
            </form>

            <div class="footer-links">
                <p>Need help? <a href="#">Contact IT Support</a></p>
                <p style="margin-top: 8px;">Not registered? <a href="register.php">Create an account</a></p>
            </div>
        </div>
    </div>

</body>
</html>