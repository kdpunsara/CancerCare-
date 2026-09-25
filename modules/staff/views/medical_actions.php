<?php
// ==========================================
// MEDICAL ACTIONS HANDLER
// Forms walin enna actions okkoma methanin handle karanawa
// ==========================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'staff') {
    header("Location: ../../../login.php");
    exit();
}

// DB connection eka
require_once __DIR__ . '/../../../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ---------- PATIENTS ----------
    case 'register_patient':
        registerPatient($conn);
        break;

    case 'update_patient':
        updatePatient($conn);
        break;

    case 'delete_patient':
        deletePatient($conn);
        break;

    // ---------- APPOINTMENTS ----------
    case 'create_appointment':
        createAppointment($conn);
        break;

    case 'delete_appointment':
        deleteAppointment($conn);
        break;

    // ---------- BENEFACTOR DONATIONS ----------
    case 'update_donation_status':
        updateDonationStatus($conn);
        break;

    case 'update_staff_profile':
        updateStaffProfile($conn);
        break;

    default:
        header("Location: staff_dashboard.php");
        exit;
}


// ==========================================
// HELPER FUNCTIONS
// ==========================================

// Register form ekata error ekak ekka ayeth yawanna
function backWithError($message) {
    $_SESSION['error'] = $message;
    $_SESSION['old']   = $_POST;
    unset($_SESSION['old']['password']); // password eka session eke save karanna epa
    header("Location: Register_patient.php");
    exit;
}

// Patients list page ekata message ekak ekka yawanna
function backToPatients($msg, $reason = '') {
    $url = 'staff_patient.php?msg=' . urlencode($msg);
    if ($reason !== '') {
        $url .= '&reason=' . urlencode($reason);
    }
    header("Location: " . $url);
    exit;
}

// Appointments page ekata message ekak ekka yawanna
function backToAppointments($msg, $reason = '') {
    $url = 'staff_appointment.php?msg=' . urlencode($msg);
    if ($reason !== '') {
        $url .= '&reason=' . urlencode($reason);
    }
    header("Location: " . $url);
    exit;
}

// Benefactor requests page ekata message ekak ekka yawanna
function backToBenefactor($msg, $reason = '') {
    $url = 'benefactor.php?msg=' . urlencode($msg);
    if ($reason !== '') {
        $url .= '&reason=' . urlencode($reason);
    }
    header("Location: " . $url);
    exit;
}

function backToStaffProfile($query) {
    header("Location: staff_profile.php?" . $query);
    exit;
}

// Doctor kenek Doctor table eke thiyenawada
function doctorExists($conn, $doctorId) {
    $stmt = $conn->prepare("SELECT user_id FROM `Doctor` WHERE user_id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Patient kenek Patient table eke thiyenawada
function patientExists($conn, $patientId) {
    $stmt = $conn->prepare("SELECT user_id FROM `Patient` WHERE user_id = ?");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Appointment eka create karana staff member ge user_id eka
// (login session eke thiyenawanam eka, nathnam mulinma thiyena staff user)
function currentStaffId($conn) {
    foreach (['user_id', 'staff_id', 'uid'] as $key) {
        if (!empty($_SESSION[$key])) {
            return (int)$_SESSION[$key];
        }
    }

    $res = $conn->query("SELECT user_id FROM `user` WHERE role = 'staff' ORDER BY user_id ASC LIMIT 1");
    $row = $res ? $res->fetch_assoc() : null;

    return $row ? (int)$row['user_id'] : 0;
}


// ==========================================
// REGISTER PATIENT
// `user` table (login details) + `Patient` table (medical details)
// ==========================================
function registerPatient($conn) {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: Register_patient.php");
        exit;
    }

    // ---- Form data ganna ----
    $first_name        = trim($_POST['first_name'] ?? '');
    $last_name         = trim($_POST['last_name'] ?? '');
    $password_plain    = $_POST['password'] ?? '';
    $dob               = trim($_POST['dob'] ?? '');
    $gender            = $_POST['gender'] ?? '';
    $nic               = trim($_POST['nic'] ?? '');
    $phone             = trim($_POST['phone'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $address           = trim($_POST['address'] ?? '');
    $city              = trim($_POST['city'] ?? '');
    $blood_group       = $_POST['blood_group'] ?? '';
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $cancer_type       = $_POST['cancer_type'] ?? '';
    $stage             = $_POST['stage'] ?? '';
    $assigned_doctor   = (int)($_POST['assigned_doctor'] ?? 0);
    $treatment_plan    = trim($_POST['treatment_plan'] ?? '');
    $allergies         = trim($_POST['allergies'] ?? '');
    $status            = $_POST['status'] ?? 'active';

    // ---- Validation ----
    if ($first_name === '' || $last_name === '' || $nic === '' || $phone === '' || $email === '' || $password_plain === '') {
        backWithError("Please fill all required fields.");
    }

    // '!' dana nisa welawa 00:00 wenawa (adama upan patiyekuth hari wenawa)
    $dobDate = DateTime::createFromFormat('!Y-m-d', $dob);
    if (!$dobDate || $dobDate->format('Y-m-d') !== $dob || $dobDate > new DateTime('today')) {
        backWithError("Please enter a valid date of birth.");
    }
    $age = $dobDate->diff(new DateTime('today'))->y; // DOB eken age eka hadanawa

    if (!in_array($gender, ['male', 'female', 'other'], true)) {
        backWithError("Please select a valid gender.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        backWithError("Please enter a valid email address.");
    }
    if ($cancer_type === '' || $stage === '' || $assigned_doctor <= 0) {
        backWithError("Please fill all medical information fields.");
    }
    if (!in_array($status, ['active', 'critical', 'stable', 'scheduled'], true)) {
        $status = 'active';
    }

    try {
        // ---- NIC duplicate check ----
        $check = $conn->prepare("SELECT user_id FROM `Patient` WHERE nic = ?");
        $check->bind_param("s", $nic);
        $check->execute();
        $check->store_result();
        $nicExists = $check->num_rows > 0;
        $check->close();

        if ($nicExists) {
            backWithError("This NIC is already registered.");
        }

        // ---- Doctor kenek thiyenawada balanna ----
        if (!doctorExists($conn, $assigned_doctor)) {
            backWithError("Selected doctor was not found. Please choose a valid doctor.");
        }

        $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

        // ---- Transaction: user + Patient dekatama insert wenawa, nathnam kisima ekak nemei ----
        $conn->begin_transaction();

        // Aluth user_id eka (last user_id + 1)
        $res    = $conn->query("SELECT COALESCE(MAX(user_id), 100000) + 1 AS next_id FROM `user` FOR UPDATE");
        $userId = (int)$res->fetch_assoc()['next_id'];

        // 1) `user` table ekata login details (username = NIC)
        $stmt = $conn->prepare(
            "INSERT INTO `user`
                (user_id, username, email, password_hash, must_change_password, phone, role, status)
             VALUES (?, ?, ?, ?, 1, ?, 'patient', 'active')"
        );
        $stmt->bind_param("issss", $userId, $nic, $email, $password_hash, $phone);
        $stmt->execute();
        $stmt->close();

        // 2) `Patient` table ekata medical details
        $stmt = $conn->prepare(
            "INSERT INTO `Patient`
                (user_id, nic, first_name, last_name, dob, gender, address, city, blood_group,
                 allergies, age, emergency_contact, cancer_type, stage, assigned_doctor,
                 treatment_plan, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "isssssssssisssiss",
            $userId, $nic, $first_name, $last_name, $dob, $gender, $address, $city, $blood_group,
            $allergies, $age, $emergency_contact, $cancer_type, $stage, $assigned_doctor,
            $treatment_plan, $status
        );
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        backToPatients('registered');

    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignore) {}

        if ($e instanceof mysqli_sql_exception && (int)$e->getCode() === 1062) {
            backWithError("This NIC or email is already registered.");
        }
        backWithError("Registration failed: " . $e->getMessage());
    }
}


// ==========================================
// UPDATE PATIENT
// ==========================================
function updatePatient($conn) {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: staff_patient.php");
        exit;
    }

    $id              = (int)($_POST['id'] ?? 0);
    $first_name      = trim($_POST['first_name'] ?? '');
    $last_name       = trim($_POST['last_name'] ?? '');
    $age             = trim($_POST['age'] ?? '');
    $cancer_type     = trim($_POST['cancer_type'] ?? '');
    $stage           = trim($_POST['stage'] ?? '');
    $status          = trim($_POST['status'] ?? '');
    $assigned_doctor = (int)($_POST['assigned_doctor'] ?? 0);

    if ($id <= 0) {
        backToPatients('error', 'Invalid patient.');
    }
    if ($first_name === '' || $last_name === '') {
        backToPatients('error', 'First name and last name are required.');
    }
    if (!ctype_digit($age) || (int)$age > 120) {
        backToPatients('error', 'Age must be a number between 0 and 120.');
    }
    $age = (int)$age;

    if ($cancer_type === '') {
        backToPatients('error', 'Please select a cancer type.');
    }
    if (!in_array($stage, ['I', 'II', 'III', 'IV'], true)) {
        backToPatients('error', 'Please select a valid stage.');
    }
    if (!in_array($status, ['active', 'critical', 'stable', 'scheduled'], true)) {
        backToPatients('error', 'Please select a valid status.');
    }

    try {
        if (!doctorExists($conn, $assigned_doctor)) {
            backToPatients('error', 'Selected doctor was not found.');
        }

        $stmt = $conn->prepare(
            "UPDATE `Patient`
                SET first_name = ?, last_name = ?, age = ?, cancer_type = ?,
                    stage = ?, status = ?, assigned_doctor = ?
              WHERE user_id = ?"
        );
        $stmt->bind_param(
            "ssisssii",
            $first_name, $last_name, $age, $cancer_type,
            $stage, $status, $assigned_doctor, $id
        );
        $stmt->execute();
        $stmt->close();

        backToPatients('updated');

    } catch (Throwable $e) {
        backToPatients('error', 'Update failed: ' . $e->getMessage());
    }
}


// ==========================================
// DELETE PATIENT
// `Patient` row eka + `user` row eka dekama delete wenawa
// ==========================================
function deletePatient($conn) {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: staff_patient.php");
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        backToPatients('error', 'Invalid patient.');
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("DELETE FROM `Patient` WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted === 0) {
            $conn->rollback();
            backToPatients('error', 'Patient not found.');
        }

        $stmt = $conn->prepare("DELETE FROM `user` WHERE user_id = ? AND role = 'patient'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        backToPatients('deleted');

    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignore) {}

        // 1451 = wenath tables (appointments, reports...) me patient ta link wela thiyenawa
        if ($e instanceof mysqli_sql_exception && (int)$e->getCode() === 1451) {
            backToPatients(
                'error',
                'This patient has related records (appointments, reports, etc.) and cannot be deleted.'
            );
        }
        backToPatients('error', 'Delete failed: ' . $e->getMessage());
    }
}


// ==========================================
// CREATE APPOINTMENT
// Appointment(appointment_id, patient_user_id, doctor_user_id, staff_user_id,
//             appointment_date, appointment_time, reason)
// ==========================================
function createAppointment($conn) {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: staff_appointment.php");
        exit;
    }

    // ---- Form data ganna ----
    $patient_id       = (int)($_POST['patient_id'] ?? 0);
    $doctor_id        = (int)($_POST['doctor_id'] ?? 0);
    $appointment_date = trim($_POST['appointment_date'] ?? '');
    $appointment_time = trim($_POST['appointment_time'] ?? '');
    $type             = trim($_POST['type'] ?? '');
    $status           = trim($_POST['status'] ?? 'pending');

    // ---- Validation ----
    if ($patient_id <= 0) {
        backToAppointments('error', 'Please select a patient.');
    }
    if ($doctor_id <= 0) {
        backToAppointments('error', 'Please select a doctor.');
    }
    if ($appointment_date === '') {
        backToAppointments('error', 'Please select an appointment date.');
    }
    if ($appointment_time === '') {
        backToAppointments('error', 'Please select an appointment time.');
    }

    $validTypes = ['Consultation', 'Follow-up', 'Treatment', 'Check-up', 'Screening'];
    if (!in_array($type, $validTypes, true)) {
        backToAppointments('error', 'Please select a valid appointment type.');
    }

    $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!in_array($status, $validStatuses, true)) {
        $status = 'pending';
    }

    // Date eka hariyata thiyenawada, ithuru ada wada passe wenna ba
    $dateObject = DateTime::createFromFormat('!Y-m-d', $appointment_date);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $appointment_date) {
        backToAppointments('error', 'Invalid appointment date.');
    }
    if ($dateObject < new DateTime('today')) {
        backToAppointments('error', 'Appointment date cannot be in the past.');
    }

    $timeObject = DateTime::createFromFormat('H:i', $appointment_time);
    if (!$timeObject || $timeObject->format('H:i') !== $appointment_time) {
        backToAppointments('error', 'Invalid appointment time.');
    }

    try {
        if (!patientExists($conn, $patient_id)) {
            backToAppointments('error', 'Selected patient does not exist.');
        }
        if (!doctorExists($conn, $doctor_id)) {
            backToAppointments('error', 'Selected doctor does not exist.');
        }

        // Me appointment eka hadanne kawuda (staff member)
        $staff_id = currentStaffId($conn);
        if ($staff_id <= 0) {
            backToAppointments('error', 'No staff user found to create the appointment.');
        }

        // Doctor ta ekama welawata thawa appointment ekak thiyenawada
        $stmt = $conn->prepare(
            "SELECT appointment_id FROM `Appointment`
              WHERE doctor_user_id = ? AND appointment_date = ?
                                AND appointment_time = ?"
        );
        $stmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
        $stmt->execute();
        $stmt->store_result();
        $busy = $stmt->num_rows > 0;
        $stmt->close();

        if ($busy) {
            backToAppointments('error', 'This doctor already has an appointment at that date and time.');
        }

        // ---- Insert ----
        $conn->begin_transaction();

        // Aluth appointment_id eka (last id + 1)
        $res           = $conn->query("SELECT COALESCE(MAX(appointment_id), 0) + 1 AS next_id FROM `Appointment` FOR UPDATE");
        $appointmentId = (int)$res->fetch_assoc()['next_id'];

        $stmt = $conn->prepare(
            "INSERT INTO `Appointment`
                (appointment_id, patient_user_id, doctor_user_id, staff_user_id,
                 appointment_date, appointment_time, reason)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "iiiisss",
            $appointmentId, $patient_id, $doctor_id, $staff_id,
            $appointment_date, $appointment_time, $type
        );
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        backToAppointments('created');

    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignore) {}

        // 1452 = patient / doctor / staff reference eka hariyata nemei
        if ($e instanceof mysqli_sql_exception && (int)$e->getCode() === 1452) {
            backToAppointments('error', 'Invalid patient, doctor or staff reference. Please check the selected values.');
        }
        backToAppointments('error', 'Appointment creation failed: ' . $e->getMessage());
    }
}


// ==========================================
// DELETE APPOINTMENT
// ==========================================
function deleteAppointment($conn) {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: staff_appointment.php");
        exit;
    }

    $appointment_id = (int)($_POST['appointment_id'] ?? 0);

    if ($appointment_id <= 0) {
        backToAppointments('error', 'Invalid appointment.');
    }

    try {
        $stmt = $conn->prepare("DELETE FROM `Appointment` WHERE appointment_id = ?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted === 0) {
            backToAppointments('error', 'Appointment not found.');
        }

        backToAppointments('deleted');

    } catch (Throwable $e) {

        // 1451 = wenath records (prescriptions, reports...) me appointment ekata link wela thiyenawa
        if ($e instanceof mysqli_sql_exception && (int)$e->getCode() === 1451) {
            backToAppointments(
                'error',
                'This appointment cannot be deleted because other records are linked to it.'
            );
        }
        backToAppointments('error', 'Appointment delete failed: ' . $e->getMessage());
    }
}


// ==========================================
// UPDATE BENEFACTOR DONATION STATUS
// ==========================================
function updateDonationStatus($conn) {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: benefactor.php");
        exit;
    }

    if (($_SESSION['role'] ?? '') !== 'staff') {
        header("Location: ../../../login.php");
        exit;
    }

    $donation_id = (int)($_POST['donation_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $staff_id = (int)($_SESSION['user_id'] ?? 0);

    if ($donation_id <= 0 || !in_array($status, ['Received', 'Rejected'], true)) {
        backToBenefactor('error', 'Invalid donation status request.');
    }

    if ($staff_id <= 0) {
        $staff_id = currentStaffId($conn);
    }

    if ($staff_id <= 0) {
        backToBenefactor('error', 'No staff user could be identified.');
    }

    try {
        if ($status === 'Received') {
            $stmt = $conn->prepare(
                "UPDATE Donation
                 SET status = ?, received_by_staff_id = ?, received_date = CURDATE()
                 WHERE donation_id = ? AND status = 'Pending Verification'"
            );
        } else {
            $stmt = $conn->prepare(
                "UPDATE Donation
                 SET status = ?, received_by_staff_id = ?, received_date = NULL
                 WHERE donation_id = ? AND status = 'Pending Verification'"
            );
        }

        $stmt->bind_param("sii", $status, $staff_id, $donation_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            backToBenefactor('error', 'Donation request was not found or is already updated.');
        }

        backToBenefactor('donation_updated');
    } catch (Throwable $e) {
        backToBenefactor('error', 'Could not update donation request: ' . $e->getMessage());
    }
}


// ==========================================
// UPDATE STAFF PROFILE
// ==========================================
function updateStaffProfile($conn) {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SESSION['role'] ?? '') !== 'staff') {
        header("Location: ../../../login.php");
        exit;
    }

    $staff_id = (int)($_SESSION['user_id'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($staff_id <= 0) {
        header("Location: ../../../logout.php");
        exit;
    }

    if ($password !== '' && strlen($password) < 6) {
        backToStaffProfile('error=short_password');
    }

    if ($password !== $confirm_password) {
        backToStaffProfile('error=password_mismatch');
    }

    try {
        if ($password !== '') {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE User SET phone = ?, password_hash = ? WHERE user_id = ? AND role = 'staff'");
            $stmt->bind_param("ssi", $phone, $password_hash, $staff_id);
        } else {
            $stmt = $conn->prepare("UPDATE User SET phone = ? WHERE user_id = ? AND role = 'staff'");
            $stmt->bind_param("si", $phone, $staff_id);
        }

        $stmt->execute();
        backToStaffProfile('msg=profile_updated');
    } catch (Throwable $e) {
        error_log("Staff Profile Update Error: " . $e->getMessage());
        backToStaffProfile('error=update_failed');
    }
}
?>