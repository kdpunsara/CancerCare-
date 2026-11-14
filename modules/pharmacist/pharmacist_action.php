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
    case 'initial_register':
        if ($initial_inventory_completed) {
            pharmacist_redirect('views/dashboard.php');
        }

        $medicine_ids = $_POST['medicine_id'] ?? [];
        $batches = $_POST['batch_number'] ?? [];
        $expiries = $_POST['expiry_date'] ?? [];
        $quantities = $_POST['initial_stock'] ?? [];
        $entries = [];
        $errors = [];
        $row_count = is_array($medicine_ids) ? count($medicine_ids) : 0;

        if (!csrf_valid()) {
            $errors[] = 'Security check failed. Please reload the page and try again.';
        } elseif ($row_count > 100) {
            $errors[] = 'Please submit no more than 100 medicine records at once.';
        }

        for ($i = 0; $i < $row_count; $i++) {
            $values = [
                'medicine_id' => (int) ($medicine_ids[$i] ?? 0),
                'batch_number' => trim((string) ($batches[$i] ?? '')),
                'expiry_date' => trim((string) ($expiries[$i] ?? '')),
                'initial_stock' => trim((string) ($quantities[$i] ?? '')),
            ];
            $is_empty = $values['medicine_id'] === 0 && $values['batch_number'] === ''
                && $values['expiry_date'] === '' && $values['initial_stock'] === '';
            if ($is_empty) {
                continue;
            }
            if ($values['medicine_id'] < 1 || $values['batch_number'] === ''
                || text_len($values['batch_number']) > 50) {
                $errors[] = 'Each medicine must be selected from the Medicine Master and have a valid batch number.';
                break;
            }
            if ($values['initial_stock'] === '' || !ctype_digit($values['initial_stock'])) {
                $errors[] = 'Initial stock quantities must be whole numbers of 0 or more.';
                break;
            }
            $expiry = DateTime::createFromFormat('Y-m-d', $values['expiry_date']);
            if (!$expiry || $expiry->format('Y-m-d') !== $values['expiry_date'] || $values['expiry_date'] < date('Y-m-d')) {
                $errors[] = 'Expired or invalid medicines cannot be registered.';
                break;
            }
            $entries[] = $values;
        }

        if (count($entries) < 10 && !$errors) {
            $errors[] = 'At least 10 valid medicine records are required.';
        }

        $seen_batches = [];
        $seen_medicines = [];
        foreach ($entries as $entry) {
            $medicine_key = (string) $entry['medicine_id'];
            $batch_key = $medicine_key . "\0" . strtolower($entry['batch_number']);
            if (isset($seen_batches[$batch_key])) {
                $errors[] = 'Duplicate medicine and batch entries are not allowed.';
                break;
            }
            $seen_batches[$batch_key] = true;
            $seen_medicines[$medicine_key] = true;
        }
        if (count($seen_medicines) < 10 && !$errors) {
            $errors[] = 'At least 10 different medicines are required. Different batches do not count as different medicines.';
        }

        if ($errors) {
            flash_set('error', $errors[0]);
            pharmacist_redirect('views/initial-inventory.php');
        }

        try {
            $conn->begin_transaction();
            foreach ($entries as $entry) {
                $stmt = $conn->prepare("SELECT medicine_id, medicine_name FROM Medicine WHERE medicine_id = ? LIMIT 1 FOR UPDATE");
                $stmt->bind_param("i", $entry['medicine_id']);
                $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$existing) {
                    throw new RuntimeException('One or more selected medicines no longer exists in the Medicine Master.');
                }
                $medicine_id = (int) $existing['medicine_id'];

                $stmt = $conn->prepare(
                    "SELECT inventory_id FROM PharmacyInventory
                     WHERE pharmacist_user_id = ? AND medicine_id = ? AND LOWER(batch_number) = LOWER(?)
                     LIMIT 1"
                );
                $stmt->bind_param("iis", $pharmacist_id, $medicine_id, $entry['batch_number']);
                $stmt->execute();
                $duplicate = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($duplicate) {
                    $quantity = (int) $entry['initial_stock'];
                    $stmt = $conn->prepare(
                        "UPDATE PharmacyInventory SET stock_quantity = stock_quantity + ?, expiry_date = ?
                         WHERE inventory_id = ? AND pharmacist_user_id = ?"
                    );
                    $stmt->bind_param("isii", $quantity, $entry['expiry_date'], $duplicate['inventory_id'], $pharmacist_id);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $quantity = (int) $entry['initial_stock'];
                    $stmt = $conn->prepare(
                        "INSERT INTO PharmacyInventory (pharmacist_user_id, medicine_id, stock_quantity, batch_number, expiry_date)
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param("iiiss", $pharmacist_id, $medicine_id, $quantity, $entry['batch_number'], $entry['expiry_date']);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $stmt = $conn->prepare(
                "SELECT COUNT(DISTINCT medicine_id) AS medicine_count
                 FROM PharmacyInventory WHERE pharmacist_user_id = ?"
            );
            $stmt->bind_param("i", $pharmacist_id);
            $stmt->execute();
            $medicine_count = (int) $stmt->get_result()->fetch_assoc()['medicine_count'];
            $stmt->close();
            if ($medicine_count < 10) {
                throw new RuntimeException('At least 10 different medicines are required.');
            }

            $stmt = $conn->prepare(
                "UPDATE Pharmacist
                 SET initial_inventory_completed = 1, initial_inventory_completed_at = NOW()
                 WHERE user_id = ?"
            );
            $stmt->bind_param("i", $pharmacist_id);
            $stmt->execute();
            $stmt->close();
            $conn->commit();

            log_action($conn, $pharmacist_id, 'INITIAL_INVENTORY_COMPLETE', 'Pharmacist', $pharmacist_id,
                "Completed initial inventory registration with {$medicine_count} different medicines");
            flash_set('success', 'Initial inventory completed. Welcome to your pharmacy dashboard.');
            pharmacist_redirect('views/dashboard.php');
        } catch (mysqli_sql_exception $ex) {
            $conn->rollback();
            error_log($ex->getMessage());
            flash_set('error', 'Database error: the initial inventory could not be saved.');
            pharmacist_redirect('views/initial-inventory.php');
        } catch (RuntimeException $ex) {
            $conn->rollback();
            flash_set('error', $ex->getMessage());
            pharmacist_redirect('views/initial-inventory.php');
        }
        break;

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
        $fields = [
            'medicine_id' => (int) ($_POST['medicine_id'] ?? 0),
            'initial_stock' => trim($_POST['initial_stock'] ?? ''),
            'batch_number' => trim($_POST['batch_number'] ?? ''),
            'expiry_date' => trim($_POST['expiry_date'] ?? ''),
        ];

        if (!csrf_valid()) {
            $errors[] = 'Security check failed. Please reload the page and try again.';
        }
        if ($fields['medicine_id'] < 1) {
            $errors[] = 'Please select a medicine from the central Medicine Master.';
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

            $stmt = $conn->prepare("SELECT medicine_id, medicine_name FROM Medicine WHERE medicine_id = ? LIMIT 1 FOR UPDATE");
            $stmt->bind_param("i", $fields['medicine_id']);
            $stmt->execute();
            $medicine = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$medicine) {
                throw new RuntimeException('The selected medicine does not exist in the central Medicine Master.');
            }
            $medicine_id = (int) $medicine['medicine_id'];

            $stmt = $conn->prepare(
                "SELECT inventory_id FROM PharmacyInventory
                 WHERE pharmacist_user_id = ? AND medicine_id = ? AND LOWER(batch_number) = LOWER(?)
                 LIMIT 1"
            );
            $stmt->bind_param("iis", $pharmacist_id, $medicine_id, $fields['batch_number']);
            $stmt->execute();
            $duplicate = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($duplicate) {
                $stmt = $conn->prepare(
                    "UPDATE PharmacyInventory SET stock_quantity = stock_quantity + ?, expiry_date = ?
                     WHERE inventory_id = ? AND pharmacist_user_id = ?"
                );
                $stmt->bind_param("isii", $qty, $fields['expiry_date'], $duplicate['inventory_id'], $pharmacist_id);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO PharmacyInventory (pharmacist_user_id, medicine_id, stock_quantity, batch_number, expiry_date)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("iiiss", $pharmacist_id, $medicine_id, $qty, $fields['batch_number'], $fields['expiry_date']);
                $stmt->execute();
                $stmt->close();
            }
            $conn->commit();

            log_action($conn, $pharmacist_id, 'INVENTORY_ADD', 'Medicine', $medicine_id,
                "Registered {$medicine['medicine_name']} with batch {$fields['batch_number']} (qty {$qty}, expires {$fields['expiry_date']})");
            flash_set('success', $medicine['medicine_name'] . ' was added to your inventory.');
            pharmacist_redirect('views/dashboard.php');
        } catch (mysqli_sql_exception $ex) {
            $conn->rollback();
            error_log($ex->getMessage());
            flash_set('error', 'Database error: the medicine could not be saved. Please try again.');
            pharmacist_redirect('views/add-drug.php');
        } catch (RuntimeException $ex) {
            $conn->rollback();
            flash_set('error', $ex->getMessage());
            pharmacist_redirect('views/add-drug.php');
        }
        break;

    case 'update_drug':
        $errors = [];
        $medicine_id = (int) ($_POST['medicine_id'] ?? 0);
        $inventory_id = (int) ($_POST['inventory_id'] ?? 0);
        $quantity = trim($_POST['quantity'] ?? '');
        $batch_number = trim($_POST['batch_number'] ?? '');
        $expiry_date = trim($_POST['expiry_date'] ?? '');

        if (!csrf_valid()) {
            $errors[] = 'Security check failed. Please reload the page and try again.';
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
        } elseif ($expiry_date < date('Y-m-d')) {
            $errors[] = 'Expired stock cannot be saved.';
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

            $stmt = $conn->prepare(
                "SELECT inventory_id FROM PharmacyInventory
                 WHERE pharmacist_user_id = ? AND medicine_id = ? AND LOWER(batch_number) = LOWER(?)
                   AND inventory_id <> ? LIMIT 1"
            );
            $stmt->bind_param("iisi", $pharmacist_id, $medicine_id, $batch_number, $inventory_id);
            $stmt->execute();
            $duplicate = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($duplicate) {
                throw new RuntimeException('That medicine and batch already exists in your inventory.');
            }

            $qty = (int) $quantity;
            $stmt = $conn->prepare("UPDATE PharmacyInventory SET stock_quantity = ?, batch_number = ?, expiry_date = ? WHERE inventory_id = ? AND pharmacist_user_id = ? AND medicine_id = ?");
            $stmt->bind_param("issiii", $qty, $batch_number, $expiry_date, $inventory_id, $pharmacist_id, $medicine_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            log_action($conn, $pharmacist_id, 'INVENTORY_EDIT', 'Medicine', $medicine_id,
                "Updated pharmacy stock: qty {$qty}, batch {$batch_number}, expiry {$expiry_date}");
            flash_set('success', 'Pharmacy stock updated successfully.');
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
