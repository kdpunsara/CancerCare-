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
        case 'patient': header("Location: modules/patient/views/patient_dashboard.php"); break;
        case 'doctor': header("Location: modules/doctor/views/doctor_dashboard.php"); break;
        case 'staff': header("Location: modules/staff/views/dashboard.php"); break;
        case 'pharmacist': header("Location: modules/pharmacist/views/dashboard.php"); break;
        case 'benefactor': header("Location: modules/benefactor/views/dashboard.php"); break;
        case 'admin': header("Location: modules/admin/views/admin_dashboard.php"); break;
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
        $sql = "SELECT user_id, username, email, password_hash, must_change_password, role, status
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

                if ((int) $user['must_change_password'] === 1) {
                    header("Location: change_password.php");
                    exit();
                }
                
                // Redirect based on role
                switch ($user['role']) {
                    case 'patient': header("Location: modules/patient/views/patient_dashboard.php"); break;
                    case 'doctor': header("Location: modules/doctor/views/doctor_dashboard.php"); break;
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
    <title>Login — Cancer Patient Care System</title>
    <link rel="stylesheet" href="public/css/base.css">
    <style>
        /* ── Design tokens aligned with base.css ── */
        :root {
            --navy:       #101a33;
            --navy-soft:  #16213f;
            --blue:       #4f6ef7;
            --blue-dark:  #3d59d6;
            --teal:       #14b8a6;
            --red:        #e0435c;
            --bg:         #f2f4f9;
            --card:       #ffffff;
            --border:     #e7e9f2;
            --text:       #14162b;
            --text-muted: #6b7280;
            --text-faint: #9aa0b4;
            --radius-lg:  18px;
            --radius-md:  12px;
            --radius-sm:  8px;
            --shadow-lg:  0 20px 60px rgba(16, 24, 40, 0.14);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Inter, Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: var(--bg);
        }

        a { text-decoration: none; color: inherit; }

        /* ─────────────────────────────────────────
           LEFT PANEL — branding
        ───────────────────────────────────────── */
        .left-panel {
            flex: 0 0 46%;
            background: linear-gradient(145deg, var(--navy) 0%, #1a2d5a 55%, #1a3a4a 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 64px 60px;
            position: relative;
            overflow: hidden;
        }

        /* Decorative background blobs */
        .left-panel::before,
        .left-panel::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }
        .left-panel::before {
            width: 420px; height: 420px;
            background: var(--teal);
            opacity: 0.07;
            top: -120px; right: -140px;
        }
        .left-panel::after {
            width: 280px; height: 280px;
            background: var(--blue);
            opacity: 0.07;
            bottom: -80px; left: -80px;
        }

        .deco-circle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }
        .deco-circle-1 {
            width: 180px; height: 180px;
            background: var(--blue);
            opacity: 0.05;
            bottom: 160px; right: -30px;
        }
        .deco-circle-2 {
            width: 90px; height: 90px;
            background: var(--teal);
            opacity: 0.09;
            top: 60px; left: 60px;
        }

        /* Brand mark */
        .brand-mark {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 52px;
            position: relative;
            z-index: 1;
        }
        .brand-icon {
            width: 48px; height: 48px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--blue), var(--teal));
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .brand-icon svg { width: 24px; height: 24px; }
        .brand-name {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            line-height: 1.25;
        }
        .brand-name span {
            display: block;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.4);
            margin-top: 3px;
        }

        /* Headline copy */
        .panel-headline {
            position: relative;
            z-index: 1;
        }
        .panel-headline h1 {
            font-size: 2.25rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.18;
            letter-spacing: -0.02em;
            margin-bottom: 18px;
        }
        .panel-headline h1 em {
            font-style: normal;
            background: linear-gradient(90deg, var(--teal), #6ee7d9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .panel-headline p {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.55);
            line-height: 1.7;
            max-width: 340px;
            margin-bottom: 44px;
        }

        /* Feature list */
        .feature-list {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .feature-dot {
            width: 32px; height: 32px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.08);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .feature-dot svg { width: 15px; height: 15px; }
        .feature-item p {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.65);
            line-height: 1.4;
            margin: 0;
        }

        /* ─────────────────────────────────────────
           RIGHT PANEL — form
        ───────────────────────────────────────── */
        .right-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 32px;
            background: var(--bg);
        }

        .form-card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 44px 40px 40px;
        }

        /* Card header */
        .form-header { margin-bottom: 32px; }

        .welcome-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eef2fe;
            color: var(--blue);
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 999px;
            margin-bottom: 14px;
            letter-spacing: 0.02em;
        }
        .form-header h2 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }
        .form-header p {
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Error banner */
        .error-banner {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #fef3f5;
            border: 1px solid #fac5cd;
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            margin-bottom: 22px;
        }
        .error-banner svg { flex-shrink: 0; margin-top: 1px; }
        .error-banner p {
            font-size: 0.84rem;
            color: #c53040;
            line-height: 1.45;
        }

        /* Form fields */
        .field { margin-bottom: 18px; }
        .field label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 7px;
        }
        .input-wrap { position: relative; }

        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            pointer-events: none;
        }
        .input-icon svg { width: 16px; height: 16px; }

        .field input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text);
            background: #fafbfd;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            transition: border-color .15s, box-shadow .15s, background .15s;
            outline: none;
            -webkit-appearance: none;
        }
        .field input::placeholder { color: var(--text-faint); }
        .field input:focus {
            border-color: var(--blue);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(79, 110, 247, 0.12);
        }

        /* Password toggle button */
        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: var(--text-faint);
            display: flex;
            align-items: center;
            transition: color .15s;
        }
        .pw-toggle:hover { color: var(--text-muted); }
        .pw-toggle svg { width: 16px; height: 16px; }
        #password { padding-right: 42px; }

        /* Submit button */
        .btn-login {
            width: 100%;
            margin-top: 8px;
            padding: 13px;
            background: var(--blue);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background .15s, box-shadow .15s, transform .1s;
        }
        .btn-login:hover  { background: var(--blue-dark); box-shadow: 0 4px 14px rgba(79, 110, 247, 0.35); }
        .btn-login:active { transform: translateY(1px); }

        /* Footer links */
        .form-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-footer p { font-size: 13px; color: var(--text-muted); }
        .form-footer a { color: var(--blue); font-weight: 600; transition: color .15s; }
        .form-footer a:hover { color: var(--blue-dark); text-decoration: underline; }

        /* ─────────────────────────────────────────
           RESPONSIVE
        ───────────────────────────────────────── */
        @media (max-width: 900px) {
            .left-panel { display: none; }
            .right-panel { padding: 32px 20px; }
        }
        @media (max-width: 480px) {
            .form-card { padding: 32px 24px 28px; }
        }
    </style>
</head>
<body>

    <!-- ════════════ LEFT PANEL ════════════ -->
    <div class="left-panel">
        <div class="deco-circle deco-circle-1"></div>
        <div class="deco-circle deco-circle-2"></div>

        <!-- Brand -->
        <div class="brand-mark">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 21s-7-4.4-9.5-9C.7 8 2 4 6 4c2 0 4 1.3 6 4 2-2.7 4-4 6-4 4 0 5.3 4 3.5 8-2.5 4.6-9.5 9-9.5 9z"/>
                </svg>
            </div>
            <div class="brand-name">
                CancerCare
                <span>Hospital Management System</span>
            </div>
        </div>

        <!-- Headline -->
        <div class="panel-headline">
            <h1>Compassionate Care,<br><em>Digitally Delivered.</em></h1>
            <p>The Apeksha Hospital secure digital platform — designed to connect patients, doctors, and staff in one unified system.</p>
        </div>

        <!-- Features -->
        <div class="feature-list">
            <div class="feature-item">
                <div class="feature-dot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#14b8a6" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>
                    </svg>
                </div>
                <p>Real-time Appointment Scheduling</p>
            </div>
            <div class="feature-item">
                <div class="feature-dot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#4f6ef7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 3h7l4 4v14H7z"/><path d="M9 12h6M9 16h6M9 8h2"/>
                    </svg>
                </div>
                <p>Secure Medical Records &amp; Prescriptions</p>
            </div>
            <div class="feature-item">
                <div class="feature-dot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#f5a524" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>
                    </svg>
                </div>
                <p>Patient, Doctor &amp; Benefactor Portals</p>
            </div>
            <div class="feature-item">
                <div class="feature-dot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#e0435c" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                </div>
                <p>Pharmacy &amp; Benefactor Management</p>
            </div>
        </div>
    </div>

    <!-- ════════════ RIGHT PANEL ════════════ -->
    <div class="right-panel">
        <div class="form-card">

            <!-- Header -->
            <div class="form-header">
                <div class="welcome-tag">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    Secure Portal
                </div>
                <h2>Welcome back</h2>
                <p>Sign in with your Username, User&nbsp;ID, or Email to access your dashboard.</p>
            </div>

            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
                <div class="error-banner">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c53040" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
                    </svg>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form action="login.php" method="POST">

                <div class="field">
                    <label for="login_identifier">Username, User ID, or Email</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#9aa0b4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="login_identifier"
                            name="login_identifier"
                            placeholder="e.g. 100001, dr_noel, email@hospital.lk"
                            required
                            autocomplete="username"
                        >
                    </div>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#9aa0b4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show or hide password">
                            <svg id="eyeShow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg id="eyeHide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    Sign In to Dashboard
                </button>
            </form>

            <!-- Footer links -->
            <div class="form-footer">
                <p><b>Forgot your password?</b> Please contact the Hospital Administration Desk at 071 1791923 or 
                    visit Room 43 for assistance. For security reasons, all password resets must be verified in person.</p>
                
            </div>
        </div>
    </div>

    <script>
        const toggle  = document.getElementById('pwToggle');
        const pwInput = document.getElementById('password');
        const eyeShow = document.getElementById('eyeShow');
        const eyeHide = document.getElementById('eyeHide');

        toggle.addEventListener('click', () => {
            const isPassword = pwInput.type === 'password';
            pwInput.type          = isPassword ? 'text'   : 'password';
            eyeShow.style.display = isPassword ? 'none'   : '';
            eyeHide.style.display = isPassword ? ''       : 'none';
        });
    </script>

</body>
</html>