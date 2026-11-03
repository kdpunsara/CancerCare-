<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create a patient account</title>
    <link rel="stylesheet" href="../../../public/css/auth.css">
    <style>
        body {
            min-height: 100vh;
            padding: 24px;
            background: #f2f4f9;
            font-family: Arial, sans-serif;
        }
        main {
            width: 100%;
            max-width: 720px;
            margin: 0 auto;
            padding: 32px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 12px 36px rgba(16, 24, 40, .1);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        label {
            display: block;
            margin-top: 16px;
            font-weight: 600;
        }
        input, select {
            box-sizing: border-box;
            width: 100%;
            margin-top: 6px;
            padding: 12px;
            border: 1px solid #cbd2e0;
            border-radius: 6px;
            font: inherit;
        }
        .full-width { grid-column: 1 / -1; }
        button {
            width: 100%;
            margin-top: 22px;
            padding: 14px;
            border: 0;
            border-radius: 6px;
            background: #243e85;
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        .notice {
            margin: 16px 0;
            padding: 12px 14px;
            border-radius: 6px;
            background: #fff1f0;
            color: #9f1d20;
        }
        @media (max-width: 560px) {
            body { padding: 12px; }
            main { padding: 22px; }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: auto; }
        }
    </style>
</head>
<body>
<main>
    <h1>Create a patient account</h1>
    <p>Register your contact and personal details to access your care online. Medical information and doctor assignment can be completed by your care team.</p>

    <?php
    $error = isset($_GET['error']) ? $_GET['error'] : '';
    $errorMessages = [
        'invalid_registration' => 'Check your details. Passwords must match and be at least 8 characters long.',
        'duplicate' => 'That email address or NIC is already registered. Try signing in or contact the care team.',
        'registration_failed' => 'We could not create your account. Please try again or contact the care team.'
    ];
    if (isset($errorMessages[$error])):
    ?>
        <p class="notice" role="alert"><?= htmlspecialchars($errorMessages[$error], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form action="../patient_actions.php" method="post">
        <input type="hidden" name="action" value="register_patient">

        <div class="form-grid">
            <label>
                First name
                <input type="text" name="first_name" maxlength="100" autocomplete="given-name" required>
            </label>
            <label>
                Last name
                <input type="text" name="last_name" maxlength="100" autocomplete="family-name" required>
            </label>
            <label>
                NIC number
                <input type="text" name="nic" maxlength="20" required>
            </label>
            <label>
                Date of birth
                <input type="date" name="dob" max="<?= date('Y-m-d') ?>" autocomplete="bday" required>
            </label>
            <label>
                Gender
                <select name="gender" required>
                    <option value="">Select gender</option>
                    <option value="female">Female</option>
                    <option value="male">Male</option>
                    <option value="other">Other</option>
                </select>
            </label>
            <label>
                Phone number
                <input type="tel" name="phone" maxlength="20" autocomplete="tel" required>
            </label>
            <label class="full-width">
                Email address
                <input type="email" name="email" maxlength="100" autocomplete="email" required>
            </label>
            <label>
                Address
                <input type="text" name="address" autocomplete="street-address">
            </label>
            <label>
                City
                <input type="text" name="city" maxlength="100" autocomplete="address-level2">
            </label>
            <label>
                Blood group (optional)
                <select name="blood_group">
                    <option value="">Select blood group</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                </select>
            </label>
            <label>
                Emergency contact (optional)
                <input type="tel" name="emergency_contact" maxlength="20">
            </label>
        </div>

        <label>
            Password
            <input type="password" name="password" minlength="8" autocomplete="new-password" required>
        </label>
        <label>
            Confirm password
            <input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>
        </label>
        <button type="submit">Create account</button>
    </form>

    <p>Already registered? <a href="../../../login.php">Sign in</a></p>
</main>
</body>
</html>
