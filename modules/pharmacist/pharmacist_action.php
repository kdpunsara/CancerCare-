<?php
session_start();
require_once __DIR__ . '/../../includes/pharmacist_init.php';

function pharmacist_redirect(string $path): void
{
    header('Location: ' . $path);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pharmacist_redirect('views/dashboard.php');
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'delete_inventory':
        if (!csrf_valid()) {
            flash_set('error', 'Security check failed. Please try again.');
            pharmacist_redirect('views/dashboard.php');
        }

        $medicine_id = (int) ($_POST['medicine_id'] ?? 0);
        try {
            $stmt = $conn->prepare("SELECT medicine_name FROM Medicine WHERE medicine_id = ?");
            $stmt->bind_param("i", $medicine_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM PharmacyInventory WHERE pharmacist_user_id = ? AND medicine_id = ?");
            $stmt->bind_param("ii", $pharmacist_id, $medicine_id);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            if ($row && $deleted > 0) {
                log_action($conn, $pharmacist_id, 'INVENTORY_DELETE', 'PharmacyInventory', $medicine_id,
                    "Removed {$row['medicine_name']} ({$deleted} batch record(s)) from pharmacy inventory");
                flash_set('success', $row['medicine_name'] . ' was removed from your inventory.');
            } else {
                flash_set('error', 'That medicine is not in your inventory.');
            }
        } catch (mysqli_sql_exception $ex) {
            error_log($ex->getMessage());
            flash_set('error', 'Could not delete the medicine. Please try again.');
        }
        pharmacist_redirect('views/dashboard.php');
        break;

    case 'add_drug':
        $errors = [];
        $selected_medicine_id = (int) ($_POST['medicine_id'] ?? 0);
        $fields = [
            'medicine_name' => trim($_POST['medicine_name'] ?? ''),
            'generic_name' => trim($_POST['generic_name'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'manufacturer' => trim($_POST['manufacturer'] ?? ''),
            'initial_stock' => trim($_POST['initial_stock'] ?? ''),
            'batch_number' => trim($_POST['batch_number'] ?? ''),
            'expiry_date' => trim($_POST['expiry_date'] ?? ''),
        ];

        if (!csrf_valid()) {
            $errors[] = 'Security check failed. Please reload the page and try again.';
        }
        if ($fields['medicine_name'] === '' || text_len($fields['medicine_name']) > 200) {
            $errors[] = 'Drug name is required (max 200 characters).';
        }
        if (text_len($fields['generic_name']) > 200) {
            $errors[] = 'Generic name is too long (max 200 characters).';
        }
        if ($fields['category'] === '' || text_len($fields['category']) > 100) {
            $errors[] = 'Category is required (max 100 characters).';
        }
        if (text_len($fields['manufacturer']) > 150) {
            $errors[] = 'Manufacturer is too long (max 150 characters).';
        }
        if ($fields['initial_stock'] === '' || !ctype_digit($fields['initial_stock'])) {
            $errors[] = 'Initial stock must be a whole number (0 or more).';
        }
        if ($fields['batch_number'] === '' || text_len($fields['batch_number']) > 50) {
            $errors[] = 'Batch / lot number is required (max 50 characters).';
        }

        $exp = DateTime::createFromFormat('Y-m-d', $fields['expiry_date']);
        if (!$exp || $exp->format('Y-m-d') !== $fields['expiry_date']) {
            $errors[] = 'A valid expiry date is required.';
        } elseif ($fields['expiry_date'] < date('Y-m-d')) {
            $errors[] = 'Expiry date is in the past. Expired stock cannot be registered.';
        }

        if ($errors) {
            flash_set('error', $errors[0]);
            pharmacist_redirect('views/add-drug.php');
        }

        try {
            $qty = (int) $fields['initial_stock'];
            $conn->begin_transaction();

            if ($selected_medicine_id > 0) {
                $stmt = $conn->prepare(
                    "SELECT medicine_id, medicine_name, generic_name, category, manufacturer
                     FROM Medicine WHERE medicine_id = ? LIMIT 1 FOR UPDATE"
                );
                $stmt->bind_param("i", $selected_medicine_id);
            } else {
                $stmt = $conn->prepare(
                    "SELECT medicine_id, medicine_name, generic_name, category, manufacturer FROM Medicine
                     WHERE LOWER(medicine_name) = LOWER(?) AND LOWER(COALESCE(generic_name, '')) = LOWER(?)
                     LIMIT 1"
                );
                $stmt->bind_param("ss", $fields['medicine_name'], $fields['generic_name']);
            }
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                $medicine_id = (int) $existing['medicine_id'];

                if ($selected_medicine_id > 0) {
                    $fields['medicine_name'] = $existing['medicine_name'];
                    $fields['generic_name'] = $existing['generic_name'] ?? '';
                    $fields['category'] = $existing['category'];
                    $fields['manufacturer'] = $existing['manufacturer'] ?? '';
                }

                $stmt = $conn->prepare("SELECT 1 FROM PharmacyInventory WHERE pharmacist_user_id = ? AND medicine_id = ? LIMIT 1");
                $stmt->bind_param("ii", $pharmacist_id, $medicine_id);
                $stmt->execute();
                $already = $stmt->get_result()->num_rows > 0;
                $stmt->close();

                if ($already) {
                    $conn->rollback();
                    flash_set('error', 'This medicine is already in your inventory. Edit the existing medicine from the dashboard.');
                    pharmacist_redirect('views/add-drug.php');
                }
            } else {
                $generic = $fields['generic_name'] !== '' ? $fields['generic_name'] : null;
                $maker = $fields['manufacturer'] !== '' ? $fields['manufacturer'] : null;
                $stmt = $conn->prepare("INSERT INTO Medicine (medicine_name, generic_name, category, manufacturer) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $fields['medicine_name'], $generic, $fields['category'], $maker);
                $stmt->execute();
                $medicine_id = $conn->insert_id;
                $stmt->close();
            }

            $stmt = $conn->prepare(
                "INSERT INTO PharmacyInventory (pharmacist_user_id, medicine_id, stock_quantity, batch_number, expiry_date)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("iiiss", $pharmacist_id, $medicine_id, $qty, $fields['batch_number'], $fields['expiry_date']);
            $stmt->execute();
            $stmt->close();
            $conn->commit();

            log_action($conn, $pharmacist_id, 'INVENTORY_ADD', 'Medicine', $medicine_id,
                "Registered {$fields['medicine_name']} with batch {$fields['batch_number']} (qty {$qty}, expires {$fields['expiry_date']})");
            flash_set('success', $fields['medicine_name'] . ' was added to the catalog and your inventory.');
            pharmacist_redirect('views/dashboard.php');
        } catch (mysqli_sql_exception $ex) {
            $conn->rollback();
            error_log($ex->getMessage());
            flash_set('error', 'Database error: the medicine could not be saved. Please try again.');
            pharmacist_redirect('views/add-drug.php');
        }
        break;

    case 'update_drug':
        $errors = [];
        $medicine_id = (int) ($_POST['medicine_id'] ?? 0);
        $inventory_id = (int) ($_POST['inventory_id'] ?? 0);
        $medicine_name = trim($_POST['medicine_name'] ?? '');
        $generic_name = trim($_POST['generic_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $manufacturer = trim($_POST['manufacturer'] ?? '');
        $quantity = trim($_POST['quantity'] ?? '');
        $batch_number = trim($_POST['batch_number'] ?? '');
        $expiry_date = trim($_POST['expiry_date'] ?? '');

        if (!csrf_valid()) {
            $errors[] = 'Security check failed. Please reload the page and try again.';
        }
        if ($medicine_name === '' || text_len($medicine_name) > 200) {
            $errors[] = 'Medicine name is required (max 200 characters).';
        }
        if (text_len($generic_name) > 200) {
            $errors[] = 'Generic name is too long (max 200 characters).';
        }
        if ($category === '' || text_len($category) > 100) {
            $errors[] = 'Category is required (max 100 characters).';
        }
        if (text_len($manufacturer) > 150) {
            $errors[] = 'Manufacturer is too long (max 150 characters).';
        }
        if ($quantity === '' || !ctype_digit($quantity)) {
            $errors[] = 'Quantity must be a whole number (0 or more).';
        }
        if ($batch_number === '' || text_len($batch_number) > 50) {
            $errors[] = 'Batch number is required (max 50 characters).';
        }

        $exp = DateTime::createFromFormat('Y-m-d', $expiry_date);
        if (!$exp || $exp->format('Y-m-d') !== $expiry_date) {
            $errors[] = 'A valid expiry date is required.';
        }
        if ($inventory_id < 1) {
            $errors[] = 'Please select a stock batch.';
        }

        if ($errors) {
            flash_set('error', $errors[0]);
            $target = 'views/edit-drug.php?medicine_id=' . $medicine_id . '&inventory_id=' . $inventory_id;
            pharmacist_redirect($target);
        }

        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare("SELECT inventory_id FROM PharmacyInventory WHERE inventory_id = ? AND pharmacist_user_id = ? AND medicine_id = ? LIMIT 1 FOR UPDATE");
            $stmt->bind_param("iii", $inventory_id, $pharmacist_id, $medicine_id);
            $stmt->execute();
            $stock_row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$stock_row) {
                throw new RuntimeException('The selected stock batch was not found.');
            }

            $generic_db = $generic_name !== '' ? $generic_name : null;
            $manufacturer_db = $manufacturer !== '' ? $manufacturer : null;

            $stmt = $conn->prepare("UPDATE Medicine SET medicine_name = ?, generic_name = ?, category = ?, manufacturer = ? WHERE medicine_id = ?");
            $stmt->bind_param("ssssi", $medicine_name, $generic_db, $category, $manufacturer_db, $medicine_id);
            $stmt->execute();
            $stmt->close();

            $qty = (int) $quantity;
            $stmt = $conn->prepare("UPDATE PharmacyInventory SET stock_quantity = ?, batch_number = ?, expiry_date = ? WHERE inventory_id = ? AND pharmacist_user_id = ? AND medicine_id = ?");
            $stmt->bind_param("issiii", $qty, $batch_number, $expiry_date, $inventory_id, $pharmacist_id, $medicine_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            log_action($conn, $pharmacist_id, 'INVENTORY_EDIT', 'Medicine', $medicine_id,
                "Updated {$medicine_name}: qty {$qty}, batch {$batch_number}, expiry {$expiry_date}");
            flash_set('success', 'Medicine details updated successfully.');
            pharmacist_redirect('views/dashboard.php');
        } catch (mysqli_sql_exception $ex) {
            $conn->rollback();
            error_log($ex->getMessage());
            flash_set('error', 'Database error: the medicine could not be updated.');
            $target = 'views/edit-drug.php?medicine_id=' . $medicine_id . '&inventory_id=' . $inventory_id;
            pharmacist_redirect($target);
        } catch (RuntimeException $ex) {
            $conn->rollback();
            flash_set('error', $ex->getMessage());
            $target = 'views/edit-drug.php?medicine_id=' . $medicine_id . '&inventory_id=' . $inventory_id;
            pharmacist_redirect($target);
        }
        break;

    case 'support_ticket':
        $errors = [];
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (!csrf_valid()) {
            $errors[] = 'Security check failed. Please reload the page and try again.';
        }
        if ($subject === '' || text_len($subject) > 150) {
            $errors[] = 'Please enter a subject (max 150 characters).';
        }
        if ($message === '' || text_len($message) > 2000) {
            $errors[] = 'Please describe the problem (max 2000 characters).';
        }

        if ($errors) {
            flash_set('error', $errors[0]);
            pharmacist_redirect('views/help.php');
        }

        log_action($conn, $pharmacist_id, 'SUPPORT_TICKET', 'User', $pharmacist_id,
            "Subject: {$subject} | Message: {$message}");
        flash_set('success', 'Your help desk ticket has been submitted. An administrator will review it.');
        pharmacist_redirect('views/help.php');
        break;

    case 'update_profile':
        $errors = [];
        $form = [
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
        ];
        $current_pwd = $_POST['current_password'] ?? '';
        $new_pwd = $_POST['new_password'] ?? '';
        $confirm_pwd = $_POST['confirm_password'] ?? '';

        if (!csrf_valid()) {
            $errors[] = 'Security check failed. Please reload the page and try again.';
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || text_len($form['email']) > 100) {
            $errors[] = 'Please enter a valid email address (max 100 characters).';
        }
        if (text_len($form['phone']) > 20 || ($form['phone'] !== '' && !preg_match('/^[0-9+\-\s()]+$/', $form['phone']))) {
            $errors[] = 'Phone number may only contain digits, spaces, + - ( ) and be at most 20 characters.';
        }

        $change_password = ($new_pwd !== '' || $confirm_pwd !== '');
        if ($change_password) {
            if (strlen($new_pwd) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            }
            if ($new_pwd !== $confirm_pwd) {
                $errors[] = 'New password and confirmation do not match.';
            }
            if ($current_pwd === '') {
                $errors[] = 'Enter your current password to set a new one.';
            }
        }

        if ($errors) {
            flash_set('error', $errors[0]);
            pharmacist_redirect('views/profile.php');
        }

        try {
            $stmt = $conn->prepare("SELECT 1 FROM User WHERE email = ? AND user_id <> ? LIMIT 1");
            $stmt->bind_param("si", $form['email'], $pharmacist_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = 'That email address is already used by another account.';
            }
            $stmt->close();

            if ($change_password && !$errors) {
                $stmt = $conn->prepare("SELECT password_hash FROM User WHERE user_id = ?");
                $stmt->bind_param("i", $pharmacist_id);
                $stmt->execute();
                $hash = $stmt->get_result()->fetch_assoc()['password_hash'] ?? '';
                $stmt->close();
                if (!password_verify($current_pwd, $hash)) {
                    $errors[] = 'Your current password is incorrect.';
                }
            }

            if ($errors) {
                flash_set('error', $errors[0]);
                pharmacist_redirect('views/profile.php');
            }

            $phone = $form['phone'] !== '' ? $form['phone'] : null;
            $address = $form['address'] !== '' ? $form['address'] : null;

            $conn->begin_transaction();
            $stmt = $conn->prepare("UPDATE User SET email = ?, phone = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $form['email'], $phone, $pharmacist_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE Pharmacist SET address = ? WHERE user_id = ?");
            $stmt->bind_param("si", $address, $pharmacist_id);
            $stmt->execute();
            $stmt->close();

            if ($change_password) {
                $new_hash = password_hash($new_pwd, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE User SET password_hash = ? WHERE user_id = ?");
                $stmt->bind_param("si", $new_hash, $pharmacist_id);
                $stmt->execute();
                $stmt->close();
                session_regenerate_id(true);
            }
            $conn->commit();

            log_action($conn, $pharmacist_id, 'PROFILE_UPDATE', 'User', $pharmacist_id,
                'Pharmacist updated profile' . ($change_password ? ' and changed password' : ''));
            flash_set('success', 'Profile updated successfully.');
            pharmacist_redirect('views/profile.php');
        } catch (mysqli_sql_exception $ex) {
            $conn->rollback();
            error_log($ex->getMessage());
            flash_set('error', 'Database error: your profile could not be saved. Please try again.');
            pharmacist_redirect('views/profile.php');
        }
        break;

    default:
        flash_set('error', 'Invalid action.');
        pharmacist_redirect('views/dashboard.php');
        break;
}
