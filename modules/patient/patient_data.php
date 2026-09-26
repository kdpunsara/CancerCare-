<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
if (isset($_SESSION['user_id']) && (isset($_SESSION['role']) ? $_SESSION['role'] : '') === 'patient') {
    $session_patient_id = (int) $_SESSION['user_id'];
    $session_check = $conn->prepare("SELECT user_id FROM Patient WHERE user_id = ?");
    $session_check->bind_param('i', $session_patient_id);
    $session_check->execute();
    if ($session_check->get_result()->num_rows === 0) {
        unset($_SESSION['user_id'], $_SESSION['role']);
    }
}
if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) ? $_SESSION['role'] : '') !== 'patient') {
    $demo_patient = $conn->query("SELECT user_id FROM Patient ORDER BY user_id LIMIT 1");
    $demo_patient_row = $demo_patient ? $demo_patient->fetch_assoc() : false;
    $_SESSION['user_id'] = $demo_patient_row ? (int) $demo_patient_row['user_id'] : 0;
    $_SESSION['role'] = 'patient';
}
$patient_id = (int) $_SESSION['user_id'];

function patient_query_rows($conn, $sql, $patient_id = null, $types = '') {
    $stmt = $conn->prepare($sql);
    if ($patient_id !== null) {
        $stmt->bind_param($types ?: 'i', $patient_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

$patient_stmt = $conn->prepare(
    "SELECT u.user_id, u.username, u.email, u.phone, p.nic, p.first_name, p.last_name,
    p.dob, p.gender, p.address, p.city, p.blood_group, p.allergies
    FROM User u JOIN Patient p ON p.user_id = u.user_id
    WHERE u.user_id = ?"
);
$patient_stmt->bind_param('i', $patient_id);
$patient_stmt->execute();
$patient = $patient_stmt->get_result()->fetch_assoc();

if (!$patient) {
    header("Location: ../../logout.php");
    exit();
}

$full_name = trim($patient['first_name'] . ' ' . $patient['last_name']);

// --- UPDATED APPOINTMENT SEARCH LOGIC ---
$appointment_search_doctor = isset($_GET['doctor']) ? trim((string) $_GET['doctor']) : '';
$appointment_search_date = isset($_GET['date']) ? trim((string) $_GET['date']) : '';

$appointment_sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.reason,
d.first_name AS doctor_first_name, d.last_name AS doctor_last_name,
CASE WHEN a.appointment_date < CURDATE() THEN 'completed' ELSE 'upcoming' END AS appointment_status
FROM Appointment a
JOIN Doctor d ON d.user_id = a.doctor_user_id
WHERE a.patient_user_id = ?";

$appointment_params = [$patient_id];
$appointment_types = 'i';

if ($appointment_search_doctor !== '') {
    $appointment_sql .= " AND (d.first_name LIKE ? OR d.last_name LIKE ? OR CONCAT(d.first_name, ' ', d.last_name) LIKE ?)";
    $doctor_term = '%' . $appointment_search_doctor . '%';
    $appointment_params[] = $doctor_term;
    $appointment_params[] = $doctor_term;
    $appointment_params[] = $doctor_term;
    $appointment_types .= 'sss';
}

if ($appointment_search_date !== '') {
    $appointment_sql .= " AND a.appointment_date = ?";
    $appointment_params[] = $appointment_search_date;
    $appointment_types .= 's';
}

$appointment_sql .= " ORDER BY a.appointment_date, a.appointment_time";

$stmt_appointments = $conn->prepare($appointment_sql);
$stmt_appointments->bind_param($appointment_types, ...$appointment_params);
$stmt_appointments->execute();
$result_appointments = $stmt_appointments->get_result();
$appointments = [];
while ($row = $result_appointments->fetch_assoc()) {
    $appointments[] = $row;
}
// --- END UPDATED APPOINTMENT SEARCH LOGIC ---

$medical_records = patient_query_rows($conn,
    "SELECT r.record_id, r.record_date, r.diagnosis, r.cancer_stage,
    r.treatment_plan, r.clinical_notes,
    d.first_name AS doctor_first_name, d.last_name AS doctor_last_name
    FROM MedicalRecord r JOIN Doctor d ON d.user_id = r.doctor_user_id
    WHERE r.patient_user_id = ?
    ORDER BY r.record_date DESC", $patient_id);

$medical_reports = patient_query_rows($conn,
    "SELECT report_id, report_type, report_title, file_path,
<<<<<<< HEAD
    uploaded_at AS upload_date, report_details AS notes
    FROM MedicalReport WHERE patient_user_id = ? ORDER BY uploaded_at DESC", $patient_id);
=======
         upload_date, notes
     FROM MedicalReport WHERE patient_user_id = ? ORDER BY upload_date DESC", $patient_id);
>>>>>>> b7824610d4819e0c50116b94c79860a5ca41483a

$prescriptions = patient_query_rows($conn,
    "SELECT p.prescription_id, p.prescription_date, p.status, p.diagnosis_notes,
    d.first_name AS doctor_first_name, d.last_name AS doctor_last_name,
    m.medicine_name, i.dosage, i.frequency, i.duration, i.instructions
    FROM Prescription p
    JOIN PrescriptionItem i ON i.prescription_id = p.prescription_id
    JOIN Medicine m ON m.medicine_id = i.medicine_id
    JOIN Doctor d ON d.user_id = p.doctor_user_id
    WHERE p.patient_user_id = ?
    ORDER BY p.prescription_date DESC, p.prescription_id DESC", $patient_id);

$meal_plans = patient_query_rows($conn,
    "SELECT plan_date, meal_type, menu_description, dietary_restrictions,
    nutritional_notes, d.first_name AS doctor_first_name, d.last_name AS doctor_last_name
    FROM MealPlan m JOIN Doctor d ON d.user_id = m.doctor_user_id
    WHERE m.patient_user_id = ? ORDER BY plan_date, FIELD(meal_type, 'breakfast', 'lunch', 'snack', 'dinner')", $patient_id);

$motivation_resources = patient_query_rows($conn,
    "SELECT resource_id, title, content_type, content_url, description, category, published_date
    FROM MotivationResource WHERE is_active = TRUE ORDER BY published_date DESC, resource_id DESC", null);

$transport_search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
if ($transport_search !== '') {
    $transport_stmt = $conn->prepare(
        "SELECT schedule_id, route_name, departure_location, arrival_location,
        departure_time, arrival_time, vehicle_type, capacity, operating_days
        FROM TransportSchedule
        WHERE status = 'active'
        AND (departure_location LIKE ? OR arrival_location LIKE ? OR route_name LIKE ?)
        ORDER BY departure_time"
    );
    $transport_term = '%' . $transport_search . '%';
    $transport_stmt->bind_param('sss', $transport_term, $transport_term, $transport_term);
    $transport_stmt->execute();
    $transport_result = $transport_stmt->get_result();
    $transport_schedules = array();
    while ($transport_row = $transport_result->fetch_assoc()) {
        $transport_schedules[] = $transport_row;
    }
} else {
    $transport_schedules = patient_query_rows($conn,
        "SELECT schedule_id, route_name, departure_location, arrival_location,
        departure_time, arrival_time, vehicle_type, capacity, operating_days
        FROM TransportSchedule WHERE status = 'active' ORDER BY departure_time", null);
}