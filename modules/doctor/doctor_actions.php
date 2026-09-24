<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../../index.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: views/doctor_appointments.php");
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {

        // --- ACTION: UPDATE MEDICAL RECORD / DIAGNOSIS ---
        case 'update_diagnosis':
            $record_id            = intval($_POST['record_id'] ?? 0);
            $patient_user_id      = intval($_POST['patient_user_id'] ?? 0);
            $diagnosis            = trim($_POST['diagnosis'] ?? '');
            $cancer_stage         = trim($_POST['cancer_stage'] ?? '');
            $clinical_notes       = trim($_POST['clinical_notes'] ?? '');
            $treatment_plan       = trim($_POST['treatment_plan'] ?? '');
            $future_treatment_plan = trim($_POST['future_treatment_plan'] ?? '');

            // Basic validation
            if ($record_id <= 0 || $patient_user_id <= 0 || empty($diagnosis) || empty($cancer_stage) || empty($clinical_notes)) {
                header("Location: views/view_records.php?patient=" . $patient_user_id . "&record=" . $record_id . "&error=invalid_record");
                exit();
            }

            // Security: Verify this record belongs to this doctor and patient
            $chk = $conn->prepare("SELECT patient_user_id FROM MedicalRecord WHERE record_id = ? AND doctor_user_id = ?");
            $chk->bind_param("ii", $record_id, $doctor_id);
            $chk->execute();
            $authorized_record = $chk->get_result()->fetch_assoc();
            if (!$authorized_record || (int) $authorized_record['patient_user_id'] !== $patient_user_id) {
                header("Location: views/view_records.php?error=unauthorized");
                exit();
            }

            $sql = "INSERT INTO MedicalRecord
                        (patient_user_id, doctor_user_id, diagnosis, cancer_stage, clinical_notes,
                         treatment_plan, future_treatment_plan, record_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisssss", $patient_user_id, $doctor_id, $diagnosis, $cancer_stage, $clinical_notes, $treatment_plan, $future_treatment_plan);

            if ($stmt->execute()) {
                header("Location: views/view_records.php?patient=" . $patient_user_id . "&msg=record_saved");
            } else {
                header("Location: views/view_records.php?patient=" . $patient_user_id . "&record=" . $record_id . "&error=db_error");
            }
            exit();
    case 'update_profile':
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '') {
            header("Location: views/doctor_profile.php?error=invalid_profile");
            exit();
        }

        $conn->begin_transaction();
        try {
            if ($password !== '') {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt_user = $conn->prepare("UPDATE User SET email = ?, phone = ?, password_hash = ?, must_change_password = 0 WHERE user_id = ?");
                $stmt_user->bind_param("sssi", $email, $phone, $password_hash, $doctor_id);
            } else {
                $stmt_user = $conn->prepare("UPDATE User SET email = ?, phone = ? WHERE user_id = ?");
                $stmt_user->bind_param("ssi", $email, $phone, $doctor_id);
            }
            $stmt_user->execute();

            $conn->commit();
            header("Location: views/doctor_profile.php?msg=profile_updated");
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: views/doctor_profile.php?error=update_error");
        }
        exit();

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

    case 'search_patient':
        $pid = intval($_POST['patient_user_id']);
        $stmt = $conn->prepare("SELECT user_id FROM Patient WHERE user_id = ?");
        $stmt->bind_param("i", $pid);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['new_rx_patient_id'] = $pid;
            header("Location: views/doctor_prescription.php");
        } else {
            header("Location: views/doctor_prescription.php?error=patient_not_found");
        }
        exit();

    case 'cancel_patient_search':
        unset($_SESSION['new_rx_patient_id']);
        header("Location: views/doctor_prescription.php");
        exit();

    case 'delete_prescription':
        $prescription_id = intval($_POST['prescription_id'] ?? 0);

        if ($prescription_id <= 0) {
            header("Location: views/doctor_prescription.php?error=invalid_prescription");
            exit();
        }

        $conn->begin_transaction();
        try {
            $stmt_verify = $conn->prepare("SELECT prescription_id FROM Prescription WHERE prescription_id = ? AND doctor_user_id = ?");
            $stmt_verify->bind_param("ii", $prescription_id, $doctor_id);
            $stmt_verify->execute();

            if ($stmt_verify->get_result()->num_rows === 0) {
                throw new Exception('Prescription not found or not owned by doctor.');
            }

            $stmt_items = $conn->prepare("DELETE FROM PrescriptionItem WHERE prescription_id = ?");
            $stmt_items->bind_param("i", $prescription_id);
            $stmt_items->execute();

            $stmt_prescription = $conn->prepare("DELETE FROM Prescription WHERE prescription_id = ? AND doctor_user_id = ?");
            $stmt_prescription->bind_param("ii", $prescription_id, $doctor_id);
            $stmt_prescription->execute();

            $conn->commit();
            header("Location: views/doctor_prescription.php?msg=rx_deleted");
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: views/doctor_prescription.php?view=" . $prescription_id . "&error=delete_error");
        }
        exit();

    case 'remove_prescription_item':
        $prescription_id = intval($_POST['prescription_id'] ?? 0);
        $item_id = intval($_POST['item_id'] ?? 0);

        if ($prescription_id > 0 && $item_id > 0) {
            $stmt = $conn->prepare("DELETE pi FROM PrescriptionItem pi
                INNER JOIN Prescription p ON p.prescription_id = pi.prescription_id
                WHERE pi.item_id = ? AND pi.prescription_id = ? AND p.doctor_user_id = ?");
            $stmt->bind_param("iii", $item_id, $prescription_id, $doctor_id);
            $stmt->execute();
        }

        header("Location: views/doctor_prescription.php?view=" . $prescription_id);
        exit();

    case 'update_prescription':
        $prescription_id = intval($_POST['prescription_id'] ?? 0);
        $diagnosis_notes = $conn->real_escape_string($_POST['diagnosis_notes'] ?? '');

        if ($prescription_id <= 0) {
            header("Location: views/doctor_prescription.php?error=invalid_prescription");
            exit();
        }

        $stmt_verify = $conn->prepare("SELECT prescription_id FROM Prescription WHERE prescription_id = ? AND doctor_user_id = ?");
        $stmt_verify->bind_param("ii", $prescription_id, $doctor_id);
        $stmt_verify->execute();

        if ($stmt_verify->get_result()->num_rows === 0) {
            header("Location: views/doctor_prescription.php?error=invalid_prescription");
            exit();
        }

        $stmt_update = $conn->prepare("UPDATE Prescription SET diagnosis_notes = ? WHERE prescription_id = ? AND doctor_user_id = ?");
        $stmt_update->bind_param("sii", $diagnosis_notes, $prescription_id, $doctor_id);
        $stmt_update->execute();

        if (!empty($_POST['existing_item_id'])) {
            foreach ($_POST['existing_item_id'] as $index => $item_id) {
                $item_id = intval($item_id);
                if ($item_id <= 0) {
                    continue;
                }

                $med_name = trim($_POST['edit_medicine_name'][$index] ?? '');
                if ($med_name === '') {
                    continue;
                }

                $stmt_lookup = $conn->prepare("SELECT medicine_id FROM Medicine WHERE medicine_name = ?");
                $stmt_lookup->bind_param("s", $med_name);
                $stmt_lookup->execute();
                $lookup_result = $stmt_lookup->get_result();

                if ($lookup_result->num_rows > 0) {
                    $medicine_id = $lookup_result->fetch_assoc()['medicine_id'];
                } else {
                    $stmt_ins = $conn->prepare("INSERT INTO Medicine (medicine_name) VALUES (?)");
                    $stmt_ins->bind_param("s", $med_name);
                    $stmt_ins->execute();
                    $medicine_id = $conn->insert_id;
                }

                $dosage = $conn->real_escape_string($_POST['edit_dosage'][$index] ?? '');
                $frequency = $conn->real_escape_string($_POST['edit_frequency'][$index] ?? '');
                $duration = $conn->real_escape_string($_POST['edit_duration'][$index] ?? '');
                $instructions = $conn->real_escape_string($_POST['edit_instructions'][$index] ?? '');

                if ($medicine_id > 0 && $dosage !== '') {
                    $stmt_item = $conn->prepare("UPDATE PrescriptionItem
                        SET medicine_id = ?, dosage = ?, frequency = ?, duration = ?, instructions = ?
                        WHERE item_id = ? AND prescription_id = ?");
                    $stmt_item->bind_param("issssii", $medicine_id, $dosage, $frequency, $duration, $instructions, $item_id, $prescription_id);
                    $stmt_item->execute();
                }
            }
        }

        if (!empty($_POST['new_medicine_name'])) {
            foreach ($_POST['new_medicine_name'] as $index => $med_name) {
                $med_name = trim($med_name);
                if ($med_name === '') {
                    continue;
                }

                $dosage = trim($_POST['new_dosage'][$index] ?? '');
                if ($dosage === '') {
                    continue;
                }

                $stmt_lookup = $conn->prepare("SELECT medicine_id FROM Medicine WHERE medicine_name = ?");
                $stmt_lookup->bind_param("s", $med_name);
                $stmt_lookup->execute();
                $lookup_result = $stmt_lookup->get_result();

                if ($lookup_result->num_rows > 0) {
                    $medicine_id = $lookup_result->fetch_assoc()['medicine_id'];
                } else {
                    $stmt_ins = $conn->prepare("INSERT INTO Medicine (medicine_name) VALUES (?)");
                    $stmt_ins->bind_param("s", $med_name);
                    $stmt_ins->execute();
                    $medicine_id = $conn->insert_id;
                }

                $frequency = $conn->real_escape_string($_POST['new_frequency'][$index] ?? '');
                $duration = $conn->real_escape_string($_POST['new_duration'][$index] ?? '');
                $instructions = $conn->real_escape_string($_POST['new_instructions'][$index] ?? '');

                if ($medicine_id > 0) {
                    $stmt_insert = $conn->prepare("INSERT INTO PrescriptionItem (prescription_id, medicine_id, dosage, frequency, duration, instructions)
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_insert->bind_param("iissss", $prescription_id, $medicine_id, $dosage, $frequency, $duration, $instructions);
                    $stmt_insert->execute();
                }
            }
        }

        header("Location: views/doctor_prescription.php?msg=rx_updated");
        exit();

    case 'save_prescription':
        $patient_id = intval($_POST['patient_user_id']);
        $diagnosis_notes = $conn->real_escape_string($_POST['diagnosis_notes'] ?? '');

        $conn->begin_transaction();
        try {
            $sql = "INSERT INTO Prescription (patient_user_id, doctor_user_id, prescription_date, diagnosis_notes, status)
                    VALUES (?, ?, CURDATE(), ?, 'active')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $patient_id, $doctor_id, $diagnosis_notes);
            $stmt->execute();
            $new_rx_id = $conn->insert_id;

            if (!empty($_POST['medicine_name'])) {
                $sql_item = "INSERT INTO PrescriptionItem (prescription_id, medicine_id, dosage, frequency, duration, instructions)
                             VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_item = $conn->prepare($sql_item);

                foreach ($_POST['medicine_name'] as $i => $med_name) {
                    $med_name = trim($med_name);
                    if (empty($med_name)) {
                        continue;
                    }

                    $stmt_lookup = $conn->prepare("SELECT medicine_id FROM Medicine WHERE medicine_name = ?");
                    $stmt_lookup->bind_param("s", $med_name);
                    $stmt_lookup->execute();
                    $res = $stmt_lookup->get_result();

                    if ($res->num_rows > 0) {
                        $medicine_id = $res->fetch_assoc()['medicine_id'];
                    } else {
                        $stmt_ins = $conn->prepare("INSERT INTO Medicine (medicine_name) VALUES (?)");
                        $stmt_ins->bind_param("s", $med_name);
                        $stmt_ins->execute();
                        $medicine_id = $conn->insert_id;
                    }

                    $dosage = $conn->real_escape_string($_POST['dosage'][$i] ?? '');
                    $frequency = $conn->real_escape_string($_POST['frequency'][$i] ?? '');
                    $duration = $conn->real_escape_string($_POST['duration'][$i] ?? '');
                    $instructions = $conn->real_escape_string($_POST['instructions'][$i] ?? '');

                    if ($medicine_id > 0 && !empty($dosage)) {
                        $stmt_item->bind_param("iissss", $new_rx_id, $medicine_id, $dosage, $frequency, $duration, $instructions);
                        $stmt_item->execute();
                    }
                }
            }

            $conn->commit();
            unset($_SESSION['new_rx_patient_id']);
            header("Location: views/doctor_prescription.php?msg=rx_saved");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            header("Location: views/doctor_prescription.php?error=db_error");
            exit();
        }

    default:
        header("Location: views/doctor_appointments.php?error=invalid_action");
        exit();
}
