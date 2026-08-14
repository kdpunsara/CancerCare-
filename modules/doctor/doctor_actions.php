<?php
session_start();
require_once __DIR__ . '/../config/database.php';

//if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
//    header("Location: ../../index.php");
//    exit();
//}

$doctor_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_status':
            $appt_id = intval($_POST['appointment_id']);
            $new_status = $_POST['status']; 
            
            $sql = "UPDATE Appointment SET status = ? WHERE appointment_id = ? AND doctor_user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $new_status, $appt_id, $doctor_id);
            
            if ($stmt->execute()) {
                header("Location: views/doctor_appointments.php?msg=status_updated");
            } else {
                header("Location: views/doctor_appointments.php?error=db_error");
            }
            exit();

        case 'add_appointment':
            $patient_id = intval($_POST['patient_user_id']);
            $appt_date = $_POST['appointment_date'];
            $appt_time = $_POST['appointment_time'];
            $appt_type = $conn->real_escape_string($_POST['appointment_type']);
            $reason = $conn->real_escape_string($_POST['reason']);
            
            $sql = "INSERT INTO Appointment (patient_user_id, doctor_user_id, appointment_date, appointment_time, appointment_type, reason, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissss", $patient_id, $doctor_id, $appt_date, $appt_time, $appt_type, $reason);
            
            if ($stmt->execute()) {
                header("Location: views/doctor_appointments.php?msg=appointment_added");
            } else {
                header("Location: views/doctor_appointments.php?error=db_error");
            }
            exit();

        default:
            header("Location: views/doctor_appointments.php?error=invalid_action");
            exit();
    }
}

header("Location: views/doctor_appointments.php");
exit();
?>