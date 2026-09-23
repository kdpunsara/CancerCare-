<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'patient') {
    header("Location: ../../login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: views/patient_dashboard.php");
    exit();
}
$patient_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

switch ($action) {
case 'update_profile':
    $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??'');
    $first=trim($_POST['first_name']??''); $last=trim($_POST['last_name']??'');
    $dob=$_POST['dob']??''; $gender=$_POST['gender']??'';
    $nic=trim($_POST['nic']??''); $address=trim($_POST['address']??'');
    $city=trim($_POST['city']??'');
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
    } catch (Throwable $e) {
        $conn->rollback();
        header("Location: views/patient_profile_edit.php?error=update_failed");
    }
    exit();

case 'add_reminder':
    $title=trim($_POST['reminder_title']??''); $date=$_POST['reminder_date']??''; $time=$_POST['reminder_time']??null;
    if ($title==='' || $date==='') { header("Location: views/patient_appointments.php?error=invalid_reminder"); exit(); }
    if ($time==='') $time=null;
    $st=$conn->prepare("INSERT INTO Reminder(patient_user_id,reminder_title,reminder_date,reminder_time) VALUES(?,?,?,?)");
    $st->bind_param("isss",$patient_id,$title,$date,$time); $st->execute();
    header("Location: views/patient_appointments.php?msg=reminder_added"); exit();

case 'delete_reminder':
    $id=(int)($_POST['reminder_id']??0);
    $st=$conn->prepare("DELETE FROM Reminder WHERE reminder_id=? AND patient_user_id=?");
    $st->bind_param("ii",$id,$patient_id); $st->execute();
    header("Location: views/patient_appointments.php?msg=reminder_deleted"); exit();

default:
    header("Location: views/patient_dashboard.php?error=invalid_action"); exit();
}
