<?php
session_start();
require_once __DIR__ .'/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        
               // --- ACTION: ADD NEW USER ---
        case 'add_user':
            $username   = trim($_POST['username'] ?? '');
            $email      = trim($_POST['email'] ?? '');
            $password   = $_POST['password'] ?? '';
            $phone      = trim($_POST['phone'] ?? '');
            $role       = $_POST['role'] ?? '';
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name  = trim($_POST['last_name'] ?? '');

            if (in_array($role, ['patient', 'benefactor'], true)) {
                header("Location: views/user_management.php?add=1&add_role=doctor&error=role_not_allowed");
                exit();
            }

            if (empty($username) || empty($email) || empty($password) || empty($role) || empty($first_name) || empty($last_name)) {
                header("Location: views/user_management.php?add=1&add_role=" . urlencode($role) . "&error=invalid_user");
                exit();
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $conn->begin_transaction();
            try {
                // 1. Insert into User table (Common for all)
                $sql_user = "INSERT INTO User (username, email, password_hash, must_change_password, phone, role, status) VALUES (?, ?, ?, 1, ?, ?, 'active')";
                $stmt_user = $conn->prepare($sql_user);
                $stmt_user->bind_param("sssss", $username, $email, $password_hash, $phone, $role);
                $stmt_user->execute();
                $new_user_id = $conn->insert_id;

                // 2. Insert into the specific profile table based on role
                if ($role === 'doctor') {
                    $specialization   = trim($_POST['specialization'] ?? '');
                    $qualification    = trim($_POST['qualification'] ?? '');
                    $license_no       = trim($_POST['license_no'] ?? '');
                    
                    $sql_profile = "INSERT INTO Doctor (user_id, first_name, last_name, specialization, qualification, license_no) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_p = $conn->prepare($sql_profile);
                    $stmt_p->bind_param("isssss", $new_user_id, $first_name, $last_name, $specialization, $qualification, $license_no);
                    $stmt_p->execute();
                    
                } elseif ($role === 'patient') {
                    $nic         = trim($_POST['nic'] ?? '');
                    $dob         = $_POST['dob'] ?? '2000-01-01';
                    $gender      = $_POST['gender'] ?? 'other';
                    $address     = trim($_POST['address'] ?? '');
                    $city        = trim($_POST['city'] ?? '');
                    $blood_group = trim($_POST['blood_group'] ?? '');
                    $allergies   = trim($_POST['allergies'] ?? '');
                    $age         = ($_POST['age'] ?? '') !== '' ? intval($_POST['age']) : null;
                    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
                    $cancer_type = trim($_POST['cancer_type'] ?? '');
                    $stage = trim($_POST['stage'] ?? '');
                    $assigned_doctor = trim($_POST['assigned_doctor'] ?? '');
                    $treatment_plan = trim($_POST['treatment_plan'] ?? '');
                    $patient_status = $_POST['patient_status'] ?? 'active';
                    if (!in_array($patient_status, ['active', 'scheduled', 'stable'], true)) {
                        $patient_status = 'active';
                    }
                    
                    $sql_profile = "INSERT INTO Patient (user_id, nic, first_name, last_name, dob, gender, address, city, blood_group, allergies, age, emergency_contact, cancer_type, stage, assigned_doctor, treatment_plan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt_p = $conn->prepare($sql_profile);
                    $stmt_p->bind_param('i' . str_repeat('s', 9) . 'i' . str_repeat('s', 6), $new_user_id, $nic, $first_name, $last_name, $dob, $gender, $address, $city, $blood_group, $allergies, $age, $emergency_contact, $cancer_type, $stage, $assigned_doctor, $treatment_plan, $patient_status);
                    $stmt_p->execute();
                    
                } elseif ($role === 'staff') {
                    $designation = trim($_POST['designation'] ?? '');
                    $department  = trim($_POST['department'] ?? '');
                    $employee_id = trim($_POST['employee_id'] ?? '');
                    
                    $sql_profile = "INSERT INTO Medical_Staff (user_id, first_name, last_name, designation, department, employee_id) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_p = $conn->prepare($sql_profile);
                    $stmt_p->bind_param("isssss", $new_user_id, $first_name, $last_name, $designation, $department, $employee_id);
                    $stmt_p->execute();
                    
                } elseif ($role === 'pharmacist') {
                    $pharmacy_name = trim($_POST['pharmacy_name'] ?? '');
                    $address       = trim($_POST['address'] ?? '');
                    $license_no    = trim($_POST['license_no'] ?? '');
                    
                    $sql_profile = "INSERT INTO Pharmacist (user_id, first_name, last_name, pharmacy_name, address, license_no, initial_inventory_completed) VALUES (?, ?, ?, ?, ?, ?, 0)";
                    $stmt_p = $conn->prepare($sql_profile);
                    $stmt_p->bind_param("isssss", $new_user_id, $first_name, $last_name, $pharmacy_name, $address, $license_no);
                    $stmt_p->execute();
                    
                } elseif ($role === 'benefactor') {
                    $benefactor_type   = $_POST['benefactor_type'] ?? 'local';
                    $organization_name = trim($_POST['organization_name'] ?? '');
                    $address           = trim($_POST['address'] ?? '');
                    $country           = trim($_POST['country'] ?? '');
                    
                    $sql_profile = "INSERT INTO Benefactor (user_id, benefactor_type, organization_name, first_name, last_name, address, country) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt_p = $conn->prepare($sql_profile);
                    $stmt_p->bind_param("issssss", $new_user_id, $benefactor_type, $organization_name, $first_name, $last_name, $address, $country);
                    $stmt_p->execute();
                    
                } elseif ($role === 'admin') {
                    $admin_level = $_POST['admin_level'] ?? 'regular';
                    
                    $sql_profile = "INSERT INTO Admin (user_id, first_name, last_name, admin_level) VALUES (?, ?, ?, ?)";
                    $stmt_p = $conn->prepare($sql_profile);
                    $stmt_p->bind_param("isss", $new_user_id, $first_name, $last_name, $admin_level);
                    $stmt_p->execute();
                }

                $conn->commit();
                header("Location: views/user_management.php?msg=user_added");
            } catch (Exception $e) {
                $conn->rollback();
                header("Location: views/user_management.php?add=1&add_role=" . urlencode($role) . "&error=db_error");
            }
            exit();

        // --- ACTION: UPDATE USER ---
        case 'update_user':
            $user_id    = intval($_POST['user_id'] ?? 0);
            $username   = trim($_POST['username'] ?? '');
            $email      = trim($_POST['email'] ?? '');
            $password   = $_POST['password'] ?? '';
            $phone      = trim($_POST['phone'] ?? '');
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name  = trim($_POST['last_name'] ?? '');

            $stmt_role = $conn->prepare("SELECT role FROM User WHERE user_id = ?");
            $stmt_role->bind_param("i", $user_id);
            $stmt_role->execute();
            $user_record = $stmt_role->get_result()->fetch_assoc();

            if (!$user_record || $username === '' || $email === '' || $first_name === '' || $last_name === '') {
                header("Location: views/user_management.php?edit=" . $user_id . "&error=invalid_user");
                exit();
            }

            $role = $user_record['role'];
            $conn->begin_transaction();
            try {
                if ($password !== '') {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_user = $conn->prepare("UPDATE User SET username = ?, email = ?, password_hash = ?, must_change_password = 1, phone = ? WHERE user_id = ?");
                    $stmt_user->bind_param("ssssi", $username, $email, $password_hash, $phone, $user_id);
                } else {
                    $stmt_user = $conn->prepare("UPDATE User SET username = ?, email = ?, phone = ? WHERE user_id = ?");
                    $stmt_user->bind_param("sssi", $username, $email, $phone, $user_id);
                }
                $stmt_user->execute();

                if ($role === 'doctor') {
                    $specialization = trim($_POST['specialization'] ?? '');
                    $qualification = trim($_POST['qualification'] ?? '');
                    $license_no = trim($_POST['license_no'] ?? '');
                    $stmt_profile = $conn->prepare("UPDATE Doctor SET first_name = ?, last_name = ?, specialization = ?, qualification = ?, license_no = ? WHERE user_id = ?");
                    $stmt_profile->bind_param("sssssi", $first_name, $last_name, $specialization, $qualification, $license_no, $user_id);
                } elseif ($role === 'patient') {
                    $nic = trim($_POST['nic'] ?? '');
                    $dob = $_POST['dob'] ?? '';
                    $gender = $_POST['gender'] ?? 'other';
                    $address = trim($_POST['address'] ?? '');
                    $city = trim($_POST['city'] ?? '');
                    $age = ($_POST['age'] ?? '') !== '' ? intval($_POST['age']) : null;
                    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
                    $assigned_doctor = trim($_POST['assigned_doctor'] ?? '');
                    $patient_status = $_POST['patient_status'] ?? 'active';
                    if (!in_array($patient_status, ['active', 'scheduled', 'stable'], true)) {
                        $patient_status = 'active';
                    }

                    $stmt_medical = $conn->prepare(
                        "SELECT blood_group, allergies, cancer_type, stage, treatment_plan
                         FROM Patient WHERE user_id = ?"
                    );
                    $stmt_medical->bind_param("i", $user_id);
                    $stmt_medical->execute();
                    $medical_data = $stmt_medical->get_result()->fetch_assoc();
                    if (!$medical_data) {
                        throw new Exception('Patient record not found.');
                    }
                    $blood_group = $medical_data['blood_group'];
                    $allergies = $medical_data['allergies'];
                    $cancer_type = $medical_data['cancer_type'];
                    $stage = $medical_data['stage'];
                    $treatment_plan = $medical_data['treatment_plan'];

                    $stmt_profile = $conn->prepare("UPDATE Patient SET nic = ?, first_name = ?, last_name = ?, dob = ?, gender = ?, address = ?, city = ?, blood_group = ?, allergies = ?, age = ?, emergency_contact = ?, cancer_type = ?, stage = ?, assigned_doctor = ?, treatment_plan = ?, status = ? WHERE user_id = ?");
                    $stmt_profile->bind_param(str_repeat('s', 9) . 'i' . str_repeat('s', 6) . 'i', $nic, $first_name, $last_name, $dob, $gender, $address, $city, $blood_group, $allergies, $age, $emergency_contact, $cancer_type, $stage, $assigned_doctor, $treatment_plan, $patient_status, $user_id);
                } elseif ($role === 'staff') {
                    $designation = trim($_POST['designation'] ?? '');
                    $department = trim($_POST['department'] ?? '');
                    $employee_id = trim($_POST['employee_id'] ?? '');
                    $stmt_profile = $conn->prepare("UPDATE Medical_Staff SET first_name = ?, last_name = ?, designation = ?, department = ?, employee_id = ? WHERE user_id = ?");
                    $stmt_profile->bind_param("sssssi", $first_name, $last_name, $designation, $department, $employee_id, $user_id);
                } elseif ($role === 'pharmacist') {
                    $pharmacy_name = trim($_POST['pharmacy_name'] ?? '');
                    $address = trim($_POST['address'] ?? '');
                    $license_no = trim($_POST['license_no'] ?? '');
                    $stmt_profile = $conn->prepare("UPDATE Pharmacist SET first_name = ?, last_name = ?, pharmacy_name = ?, address = ?, license_no = ? WHERE user_id = ?");
                    $stmt_profile->bind_param("sssssi", $first_name, $last_name, $pharmacy_name, $address, $license_no, $user_id);
                } elseif ($role === 'benefactor') {
                    $benefactor_type = $_POST['benefactor_type'] ?? 'local';
                    $organization_name = trim($_POST['organization_name'] ?? '');
                    $address = trim($_POST['address'] ?? '');
                    $country = trim($_POST['country'] ?? '');
                    $stmt_profile = $conn->prepare("UPDATE Benefactor SET benefactor_type = ?, organization_name = ?, first_name = ?, last_name = ?, address = ?, country = ? WHERE user_id = ?");
                    $stmt_profile->bind_param("ssssssi", $benefactor_type, $organization_name, $first_name, $last_name, $address, $country, $user_id);
                } elseif ($role === 'admin') {
                    $admin_level = $_POST['admin_level'] ?? 'regular';
                    $stmt_profile = $conn->prepare("UPDATE Admin SET first_name = ?, last_name = ?, admin_level = ? WHERE user_id = ?");
                    $stmt_profile->bind_param("sssi", $first_name, $last_name, $admin_level, $user_id);
                }

                if (isset($stmt_profile)) {
                    $stmt_profile->execute();
                }

                $conn->commit();
                header("Location: views/user_management.php?msg=user_updated");
            } catch (Exception $e) {
                $conn->rollback();
                header("Location: views/user_management.php?edit=" . $user_id . "&error=update_error");
            }
            exit();

        // --- ACTION: UPDATE ADMIN PROFILE ---
        case 'update_admin_profile':
            $admin_id   = $_SESSION['user_id'];
            $email      = trim($_POST['email'] ?? '');
            $phone      = trim($_POST['phone'] ?? '');
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name  = trim($_POST['last_name'] ?? '');
            $password   = $_POST['password'] ?? '';

            if ($email === '' || $first_name === '' || $last_name === '') {
                header("Location: views/admin_profile.php?error=invalid_profile");
                exit();
            }

            $conn->begin_transaction();
            try {
                if ($password !== '') {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_user = $conn->prepare("UPDATE User SET email = ?, phone = ?, password_hash = ? WHERE user_id = ?");
                    $stmt_user->bind_param("sssi", $email, $phone, $password_hash, $admin_id);
                } else {
                    $stmt_user = $conn->prepare("UPDATE User SET email = ?, phone = ? WHERE user_id = ?");
                    $stmt_user->bind_param("ssi", $email, $phone, $admin_id);
                }
                $stmt_user->execute();

                $stmt_admin = $conn->prepare("UPDATE Admin SET first_name = ?, last_name = ? WHERE user_id = ?");
                $stmt_admin->bind_param("ssi", $first_name, $last_name, $admin_id);
                $stmt_admin->execute();

                $conn->commit();
                header("Location: views/admin_profile.php?msg=profile_updated");
            } catch (Exception $e) {
                $conn->rollback();
                header("Location: views/admin_profile.php?error=update_error");
            }
            exit();

        // --- ACTION: TOGGLE USER STATUS (Activate/Deactivate) ---
        case 'toggle_status':
            $user_id = intval($_GET['user_id'] ?? 0);
            $user_id = intval($_POST['user_id'] ?? $_GET['user_id'] ?? 0);
            $current_status = $_POST['current_status'] ?? $_GET['current_status'] ?? '';
            if ($current_status === '') {
                $status_stmt = $conn->prepare("SELECT status FROM User WHERE user_id = ?");
                $status_stmt->bind_param("i", $user_id);
                $status_stmt->execute();
                $current_status = $status_stmt->get_result()->fetch_assoc()['status'] ?? '';
            }
            $new_status = ($current_status === 'active') ? 'inactive' : 'active';

            $sql = "UPDATE User SET status = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $new_status, $user_id);
            $stmt->execute();

            $return_to = ($_POST['return_to'] ?? '') === 'donor_management.php' ? 'donor_management.php' : 'user_management.php';
            header("Location: views/" . $return_to . "?msg=status_updated");
            exit();

        case 'save_settings':
            $settings = [
                'site_name' => trim($_POST['site_name'] ?? ''),
                'maintenance_mode' => $_POST['maintenance_mode'] ?? 'off',
                'default_currency' => $_POST['default_currency'] ?? 'LKR'
            ];
            if ($settings['site_name'] === '' || !in_array($settings['maintenance_mode'], ['on', 'off'], true) || !in_array($settings['default_currency'], ['LKR', 'USD'], true)) {
                header("Location: views/configuration.php?error=invalid");
                exit();
            }
            $conn->query("CREATE TABLE IF NOT EXISTS AdminSetting (setting_key VARCHAR(100) PRIMARY KEY, setting_value VARCHAR(255) NOT NULL)");
            $stmt_setting = $conn->prepare("INSERT INTO AdminSetting (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach ($settings as $key => $value) {
                $stmt_setting->bind_param("ss", $key, $value);
                $stmt_setting->execute();
            }
            header("Location: views/configuration.php?saved=1");
            exit();

        case 'update_donation':
            $donation_id = (int) ($_POST['donation_id'] ?? 0);
            $status = $_POST['status'] ?? 'Verified';
            if (!in_array($status, ['Verified', 'Rejected'], true) || $donation_id <= 0) {
                header("Location: views/donor_management.php?error=invalid_donation");
                exit();
            }
            $stmt_donation = $conn->prepare("UPDATE Donation SET status = ? WHERE donation_id = ? AND status = 'Pending Verification'");
            $stmt_donation->bind_param("si", $status, $donation_id);
            $stmt_donation->execute();
            header("Location: views/donor_management.php?" . ($stmt_donation->affected_rows ? 'msg=donation_updated' : 'error=donation_not_pending'));
            exit();

        default:
            header("Location: views/user_management.php");
            exit();
    }
}
?>