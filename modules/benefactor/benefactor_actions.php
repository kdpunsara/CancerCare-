<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register_benefactor') {
        // 1. Capture and sanitize inputs
        $username          = trim($_POST['username'] ?? '');
        $email             = trim($_POST['email'] ?? '');
        $password          = $_POST['password'] ?? '';
        $confirm_password  = $_POST['confirm_password'] ?? '';
        $phone             = trim($_POST['phone'] ?? '');
        
        $first_name        = trim($_POST['first_name'] ?? '');
        $last_name         = trim($_POST['last_name'] ?? '');
        $benefactor_type   = $_POST['benefactor_type'] ?? 'local';
        $organization_name = trim($_POST['organization_name'] ?? '');
        $country           = trim($_POST['country'] ?? '');
        $city              = trim($_POST['city'] ?? '');
        $address           = trim($_POST['address'] ?? '');

        // Combine city and address for storage if your DB only has an 'address' column
        $full_address = $city ? $city . ', ' . $address : $address;

        // 2. Basic Validation
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($country)) {
            $_SESSION['reg_error'] = "Please fill in all required fields.";
            header("Location: views/register.php");
            exit();
        }

        if ($password !== $confirm_password) {
            $_SESSION['reg_error'] = "Passwords do not match. Please try again.";
            header("Location: views/register.php");
            exit();
        }

        if (strlen($password) < 6) {
            $_SESSION['reg_error'] = "Password must be at least 6 characters long.";
            header("Location: views/register.php");
            exit();
        }

        // 3. Hash Password securely
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // 4. Database Insertion (Using Transaction for data integrity)
        $conn->begin_transaction();
        try {
            // Step A: Insert into the base User table
            $sql_user = "INSERT INTO User (username, email, password_hash, phone, role, status) 
                         VALUES (?, ?, ?, ?, 'benefactor', 'active')";
            $stmt_user = $conn->prepare($sql_user);
            $stmt_user->bind_param("ssss", $username, $email, $password_hash, $phone);
            $stmt_user->execute();
            
            // Get the newly created user_id
            $new_user_id = $conn->insert_id;

            // Step B: Insert into the Benefactor profile table
            // Note: Adjust column names here if your Benefactor table has 'city' as a separate column
            $sql_benefactor = "INSERT INTO Benefactor (user_id, benefactor_type, organization_name, first_name, last_name, address, country) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_benefactor = $conn->prepare($sql_benefactor);
            $stmt_benefactor->bind_param("issssss", $new_user_id, $benefactor_type, $organization_name, $first_name, $last_name, $full_address, $country);
            $stmt_benefactor->execute();

            // Commit the transaction if both queries succeed
            $conn->commit();
            
            // Success: Redirect to login page with a success message
            header("Location: ../../login.php?msg=registration_success");
            exit();

        } catch (Exception $e) {
            // Rollback the transaction if anything fails
            $conn->rollback();
            
            // Check for duplicate username/email error (MySQL error code 1062)
            if ($conn->errno === 1062) {
                $_SESSION['reg_error'] = "Username or Email already exists. Please choose another.";
            } else {
                // Log the actual error for debugging (optional, but good practice)
                error_log("Benefactor Registration Error: " . $e->getMessage());
                $_SESSION['reg_error'] = "A database error occurred. Please try again later.";
            }
            
            header("Location: views/register.php");
            exit();
        }
    } elseif ($action === 'update_profile') {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'benefactor') {
            header("Location: ../../login.php");
            exit();
        }

        $benefactor_id = (int) $_SESSION['user_id'];
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: views/benefactor_profile.php?error=invalid_email");
            exit();
        }

        if ($password !== '' && strlen($password) < 6) {
            header("Location: views/benefactor_profile.php?error=short_password");
            exit();
        }

        if ($password !== $confirm_password) {
            header("Location: views/benefactor_profile.php?error=password_mismatch");
            exit();
        }

        try {
            if ($password !== '') {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE User SET email = ?, phone = ?, password_hash = ? WHERE user_id = ? AND role = 'benefactor'");
                $stmt_update->bind_param("sssi", $email, $phone, $password_hash, $benefactor_id);
            } else {
                $stmt_update = $conn->prepare("UPDATE User SET email = ?, phone = ? WHERE user_id = ? AND role = 'benefactor'");
                $stmt_update->bind_param("ssi", $email, $phone, $benefactor_id);
            }

            $stmt_update->execute();
            header("Location: views/benefactor_profile.php?msg=profile_updated");
            exit();
        } catch (Exception $e) {
            error_log("Benefactor Profile Update Error: " . $e->getMessage());
            header("Location: views/benefactor_profile.php?error=update_failed");
            exit();
        }
    } elseif ($action === 'submit_donation') {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'benefactor') {
            header("Location: ../../login.php");
            exit();
        }

        $benefactor_id = (int) $_SESSION['user_id'];
        $need_id = !empty($_POST['need_id']) ? (int) $_POST['need_id'] : null;
        $donation_type = $_POST['donation_type'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        $patient_user_id = null;

        if ($need_id !== null) {
            $stmt_need = $conn->prepare("SELECT patient_user_id FROM PatientNeed WHERE need_id = ?");
            $stmt_need->bind_param("i", $need_id);
            $stmt_need->execute();
            $need = $stmt_need->get_result()->fetch_assoc();

            if (!$need) {
                $_SESSION['donation_error'] = "The selected patient need could not be found.";
                header("Location: views/make_donation.php");
                exit();
            }

            $patient_user_id = (int) $need['patient_user_id'];
        }

        $amount = null;
        $currency = 'LKR';
        $item_name = null;
        $quantity = null;
        $return_url = "views/make_donation.php" . ($need_id !== null ? "?need=" . $need_id : "");

        if ($donation_type === 'Financial Aid') {
            $amount = !empty($_POST['amount']) ? (float) $_POST['amount'] : null;
            $currency = $_POST['currency'] ?? 'LKR';

            if ($amount === null || $amount <= 0) {
                $_SESSION['donation_error'] = "Please enter a valid donation amount.";
                header("Location: $return_url");
                exit();
            }
        } elseif ($donation_type === 'Equipment') {
            $item_name = trim($_POST['item_name'] ?? '');
            $quantity = !empty($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

            if ($item_name === '' || $quantity <= 0) {
                $_SESSION['donation_error'] = "Please specify the item and a valid quantity.";
                header("Location: $return_url");
                exit();
            }
        } else {
            $_SESSION['donation_error'] = "Invalid donation type selected.";
            header("Location: $return_url");
            exit();
        }

        $bank_slip_path = null;
        if (!isset($_FILES['bank_slip']) || $_FILES['bank_slip']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['donation_error'] = "Bank slip upload is required.";
            header("Location: $return_url");
            exit();
        }

        $upload_dir = __DIR__ . '/../../public/uploads/bank_slips/';
        $file_extension = strtolower(pathinfo($_FILES['bank_slip']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($file_extension, $allowed_types, true)) {
            $_SESSION['donation_error'] = "Invalid file type. Only JPG, PNG, and PDF are allowed.";
            header("Location: $return_url");
            exit();
        }

        if ($_FILES['bank_slip']['size'] > 5 * 1024 * 1024) {
            $_SESSION['donation_error'] = "File size exceeds the 5MB limit.";
            header("Location: $return_url");
            exit();
        }

        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
            $_SESSION['donation_error'] = "Unable to prepare the upload directory.";
            header("Location: $return_url");
            exit();
        }

        $safe_filename = 'slip_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
        if (!move_uploaded_file($_FILES['bank_slip']['tmp_name'], $upload_dir . $safe_filename)) {
            $_SESSION['donation_error'] = "Failed to upload bank slip. Please try again.";
            header("Location: $return_url");
            exit();
        }
        $bank_slip_path = 'uploads/bank_slips/' . $safe_filename;

        $conn->begin_transaction();
        try {
            $status = 'Pending Verification';
            $sql = "INSERT INTO Donation (
                        benefactor_user_id, patient_user_id, need_id, donation_type,
                        amount, currency, bank_slip_path, item_name, quantity,
                        status, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "iiisdsssiss",
                $benefactor_id,
                $patient_user_id,
                $need_id,
                $donation_type,
                $amount,
                $currency,
                $bank_slip_path,
                $item_name,
                $quantity,
                $status,
                $notes
            );
            $stmt->execute();
            $conn->commit();

            header("Location: views/dashboard.php?msg=donation_submitted");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Donation Submission Error: " . $e->getMessage());
            $_SESSION['donation_error'] = "A database error occurred. Please try again later.";
            header("Location: $return_url");
            exit();
        }
    }
}

// If accessed directly without a valid POST request, redirect to login
header("Location: ../../login.php");
exit();
?>