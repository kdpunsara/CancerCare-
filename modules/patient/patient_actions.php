<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';
if ($action !== 'register_patient' && (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) ? $_SESSION['role'] : '') !== 'patient')) {
    header("Location: ../../login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: views/patient_dashboard.php");
    exit();
}
$patient_id = (int)(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);

switch ($action) {
case 'register_patient':
    $first = trim(isset($_POST['first_name']) ? $_POST['first_name'] : '');
    $last = trim(isset($_POST['last_name']) ? $_POST['last_name'] : '');
    $nic = trim(isset($_POST['nic']) ? $_POST['nic'] : '');
    $dob = isset($_POST['dob']) ? $_POST['dob'] : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $address = trim(isset($_POST['address']) ? $_POST['address'] : '');
    $city = trim(isset($_POST['city']) ? $_POST['city'] : '');
    $blood_group = isset($_POST['blood_group']) ? $_POST['blood_group'] : '';
    $emergency_contact = trim(isset($_POST['emergency_contact']) ? $_POST['emergency_contact'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    $date = DateTime::createFromFormat('!Y-m-d', $dob);
    $validBloodGroups = ['', 'O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
    if ($first === '' || $last === '' || strlen($first) > 100 || strlen($last) > 100 ||
        $nic === '' || strlen($nic) > 20 || $phone === '' || strlen($phone) > 20 ||
        strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !in_array($gender, ['male', 'female', 'other'], true) ||
        !in_array($blood_group, $validBloodGroups, true) ||
        strlen($emergency_contact) > 20 || strlen($city) > 100 ||
        !$date || $date->format('Y-m-d') !== $dob || $date > new DateTime('today') ||
        strlen($password) < 8 || $password !== $confirm) {
        header("Location: views/patient_register.php?error=invalid_registration"); exit();
    }

    $username = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', $first . '.' . $last));
    $username = substr(trim($username, '.'), 0, 32) . '.' . bin2hex(random_bytes(4));
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $age = $date->diff(new DateTime('today'))->y;

    try {
        $conn->begin_transaction();

        $st = $conn->prepare("INSERT INTO User (username, email, password_hash, must_change_password, phone, role, status) VALUES (?, ?, ?, 0, ?, 'patient', 'active')");
        $st->bind_param("ssss", $username, $email, $password_hash, $phone);
        $st->execute();
        $new_patient_id = $conn->insert_id;
        $st->close();

        $st = $conn->prepare("INSERT INTO Patient (user_id, nic, first_name, last_name, dob, gender, address, city, blood_group, age, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $st->bind_param("issssssssis", $new_patient_id, $nic, $first, $last, $dob, $gender, $address, $city, $blood_group, $age, $emergency_contact);
        $st->execute();
        $st->close();

        $conn->commit();
        header("Location: ../../login.php?msg=registration_success");
    } catch (Throwable $e) {
        $conn->rollback();
        if ($e instanceof mysqli_sql_exception && (int)$e->getCode() === 1062) {
            header("Location: views/patient_register.php?error=duplicate");
        } else {
            error_log("Patient self-registration failed: " . $e->getMessage());
            header("Location: views/patient_register.php?error=registration_failed");
        }
    }
    exit();

case 'update_profile':
    $email=trim(isset($_POST['email']) ? $_POST['email'] : ''); $phone=trim(isset($_POST['phone']) ? $_POST['phone'] : '');
    $password=isset($_POST['password']) ? $_POST['password'] : ''; $confirm=isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $full_name=trim(isset($_POST['full_name']) ? $_POST['full_name'] : '');
    $name_parts=preg_split('/\s+/', $full_name, 2);
    $first=trim(isset($name_parts[0]) ? $name_parts[0] : ''); $last=trim(isset($name_parts[1]) ? $name_parts[1] : '');
    $dob=isset($_POST['dob']) ? $_POST['dob'] : ''; $gender=isset($_POST['gender']) ? $_POST['gender'] : '';
    $nic=trim(isset($_POST['nic']) ? $_POST['nic'] : ''); $address=trim(isset($_POST['address']) ? $_POST['address'] : '');
    $city=trim(isset($_POST['city']) ? $_POST['city'] : '');
    if ($email==='' || $first==='' || $last==='' || $dob==='' || $nic==='') {
        header("Location: views/patient_profile_edit.php?error=invalid_profile"); exit();
    }
    if ($password !== '' && strlen($password) < 8) {
        header("Location: views/patient_profile_edit.php?error=password_short"); exit();
    }
    if ($password !== $confirm) {
        header("Location: views/patient_profile_edit.php?error=password_mismatch"); exit();
    }
    $conn->begin_transaction();
    try {
        if ($password !== '') {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $st=$conn->prepare("UPDATE User SET email=?, phone=?, password_hash=?, must_change_password=0 WHERE user_id=?");
            $st->bind_param("sssi",$email,$phone,$password_hash,$patient_id);
        } else {
            $st=$conn->prepare("UPDATE User SET email=?, phone=? WHERE user_id=?");
            $st->bind_param("ssi",$email,$phone,$patient_id);
        }
        $st->execute();
        $st=$conn->prepare("UPDATE Patient SET nic=?, first_name=?, last_name=?, dob=?, gender=?, address=?, city=? WHERE user_id=?");
        $st->bind_param("sssssssi",$nic,$first,$last,$dob,$gender,$address,$city,$patient_id); $st->execute();
        $conn->commit();
        header("Location: views/patient_profile_edit.php?msg=profile_updated");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: views/patient_profile_edit.php?error=update_failed");
    }
    exit();

case 'add_reminder':
    $title=trim(isset($_POST['reminder_title']) ? $_POST['reminder_title'] : ''); $date=isset($_POST['reminder_date']) ? $_POST['reminder_date'] : ''; $time=isset($_POST['reminder_time']) ? $_POST['reminder_time'] : null;
    if ($title==='' || $date==='') { header("Location: views/patient_appointments.php?error=invalid_reminder"); exit(); }
    if ($time==='') $time=null;
    $st=$conn->prepare("INSERT INTO Reminder(patient_user_id,reminder_title,reminder_date,reminder_time) VALUES(?,?,?,?)");
    $st->bind_param("isss",$patient_id,$title,$date,$time); $st->execute();
    header("Location: views/patient_appointments.php?msg=reminder_added"); exit();

case 'delete_reminder':
    $id=(int)(isset($_POST['reminder_id']) ? $_POST['reminder_id'] : 0);
    $st=$conn->prepare("DELETE FROM Reminder WHERE reminder_id=? AND patient_user_id=?");
    $st->bind_param("ii",$id,$patient_id); $st->execute();
    header("Location: views/patient_appointments.php?msg=reminder_deleted"); exit();

default:
    header("Location: views/patient_dashboard.php?error=invalid_action"); exit();
}
