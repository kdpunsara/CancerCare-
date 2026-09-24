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
    $full_name = trim(isset($_POST['full_name']) ? $_POST['full_name'] : '');
    $name_parts = preg_split('/\s+/', $full_name, 2);
    $first = trim(isset($name_parts[0]) ? $name_parts[0] : '');
    $last = trim(isset($name_parts[1]) ? $name_parts[1] : '');
    $nic = trim(isset($_POST['nic']) ? $_POST['nic'] : '');
    $dob = isset($_POST['dob']) ? $_POST['dob'] : '';
    $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if ($first === '' || $last === '' || $nic === '' || $dob === '' || $phone === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $password !== $confirm) {
        header("Location: views/patient_register.php?error=invalid_registration"); exit();
    }

    $username = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', $first . '.' . $last));
    $username = trim($username, '.');
    $password_hash = function_exists('password_hash')
        ? password_hash($password, PASSWORD_DEFAULT)
        : crypt($password, '$2y$10$' . substr(str_replace('+', '.', base64_encode(sha1(uniqid(mt_rand(), true), true))), 0, 22));
    $conn->begin_transaction();
    try {
        $st = $conn->prepare("INSERT INTO User (username, email, password_hash, phone, role, status) VALUES (?, ?, ?, ?, 'patient', 'active')");
        $st->bind_param("ssss", $username, $email, $password_hash, $phone);
        $st->execute();
        $new_patient_id = $conn->insert_id;
        $st = $conn->prepare("INSERT INTO Patient (user_id, nic, first_name, last_name, dob) VALUES (?, ?, ?, ?, ?)");
        $st->bind_param("issss", $new_patient_id, $nic, $first, $last, $dob);
        $st->execute();
        $conn->commit();
        header("Location: ../../login.php?msg=registration_success");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: views/patient_register.php?error=registration_failed");
    }
    exit();

case 'update_profile':
    $email=trim(isset($_POST['email']) ? $_POST['email'] : ''); $phone=trim(isset($_POST['phone']) ? $_POST['phone'] : '');
    $full_name=trim(isset($_POST['full_name']) ? $_POST['full_name'] : '');
    $name_parts=preg_split('/\s+/', $full_name, 2);
    $first=trim(isset($name_parts[0]) ? $name_parts[0] : ''); $last=trim(isset($name_parts[1]) ? $name_parts[1] : '');
    $dob=isset($_POST['dob']) ? $_POST['dob'] : ''; $gender=isset($_POST['gender']) ? $_POST['gender'] : '';
    $nic=trim(isset($_POST['nic']) ? $_POST['nic'] : ''); $address=trim(isset($_POST['address']) ? $_POST['address'] : '');
    $city=trim(isset($_POST['city']) ? $_POST['city'] : '');
    if ($email==='' || $first==='' || $last==='' || $dob==='' || $nic==='') {
        header("Location: views/patient_profile_edit.php?error=invalid_profile"); exit();
    }
    $conn->begin_transaction();
    try {
        $st=$conn->prepare("UPDATE User SET email=?, phone=? WHERE user_id=?");
        $st->bind_param("ssi",$email,$phone,$patient_id); $st->execute();
        $st=$conn->prepare("UPDATE Patient SET nic=?, first_name=?, last_name=?, dob=?, gender=?, address=?, city=? WHERE user_id=?");
        $st->bind_param("sssssssi",$nic,$first,$last,$dob,$gender,$address,$city,$patient_id); $st->execute();
        $conn->commit();
        header("Location: views/patient_profile.php?msg=profile_updated");
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
