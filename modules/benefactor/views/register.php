<?php
// Start session to show error/success messages
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benefactor Registration - CancerCare</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
    <link rel="stylesheet" href="../../../public/css/register.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-shell">
            <aside class="brand-panel">
                <div class="brand-top">
                    <div class="brand-icon">CC</div>
                    <div class="brand-name">
                        CancerCare
                        <span>Apeksha Hospital</span>
                    </div>
                </div>

                <div class="panel-copy">
                    <div class="eyebrow">Community Care</div>
                    <h1>Support healing <span>with every gift.</span></h1>
                    <p>
                        Join a compassionate network of benefactors helping cancer patients receive timely care,
                        treatment support, and hope throughout their recovery journey.
                    </p>
                </div>

                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-bullet">✓</span>
                        <span>Patient care assistance</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-bullet">✓</span>
                        <span>Medical support programs</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-bullet">✓</span>
                        <span>Trusted hospital partnerships</span>
                    </div>
                </div>
            </aside>

            <main class="form-panel">
                <div class="form-card">
                    <?php if (isset($_SESSION['reg_error'])): ?>
                        <div class="panel-note">
                            <?php echo htmlspecialchars($_SESSION['reg_error']); ?>
                        </div>
                        <?php unset($_SESSION['reg_error']); ?>
                    <?php endif; ?>

                    <h2>Create Benefactor Account</h2>
                    <p>Join us in supporting cancer patients and medical research.</p>

                    <form method="POST" action="../benefactor_actions.php">
                        <input type="hidden" name="action" value="register_benefactor">

                        <p class="form-section-label">Account Credentials</p>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Username *</label>
                                <input type="text" name="username" required>
                            </div>
                            <div class="form-field">
                                <label>Email Address *</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="form-field">
                                <label>Password *</label>
                                <input type="password" name="password" required minlength="6">
                            </div>
                            <div class="form-field">
                                <label>Confirm Password *</label>
                                <input type="password" name="confirm_password" required minlength="6">
                            </div>
                            <div class="form-field form-field-wide">
                                <label>Phone Number</label>
                                <input type="text" name="phone" placeholder="e.g. +94 77 123 4567">
                            </div>
                        </div>

                        <p class="form-section-label">Benefactor Details</p>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>First Name *</label>
                                <input type="text" name="first_name" required>
                            </div>
                            <div class="form-field">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" required>
                            </div>
                            <div class="form-field">
                                <label>Benefactor Type *</label>
                                <select name="benefactor_type" required>
                                    <option value="local">Local</option>
                                    <option value="international">International</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Organization Name</label>
                                <input type="text" name="organization_name" placeholder="If representing an organization">
                            </div>
                            <div class="form-field">
                                <label>Country *</label>
                                <input type="text" name="country" required>
                            </div>
                            <div class="form-field">
                                <label>City / Region</label>
                                <input type="text" name="city">
                            </div>
                            <div class="form-field form-field-wide">
                                <label>Full Address</label>
                                <textarea name="address" rows="2" placeholder="Street address, postal code..."></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Register Account</button>
                        </div>
                    </form>

                    <div class="login-link">
                        Already have an account? <a href="../../../login.php">Sign In Here</a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>