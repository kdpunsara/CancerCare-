<?php
// ==========================================
// 1. PHP LOGIC (Database & Security)
// ==========================================
session_start();
require_once __DIR__ . '/../../../config/database.php';

//if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
//    header("Location: ../../../index.php");
//    exit();
//}

$doctor_id = $_SESSION['user_id'];

// --- Fetch Dashboard Statistics ---
// 1. Today's Appointments Count
$stmt_today = $conn->prepare("SELECT COUNT(*) as count FROM Appointment WHERE doctor_user_id = ? AND appointment_date = CURDATE()");
$stmt_today->bind_param("i", $doctor_id);
$stmt_today->execute();
$today_appts = $stmt_today->get_result()->fetch_assoc()['count'];

// 2. Total Patients Count (Distinct patients seen by this doctor)
$stmt_patients = $conn->prepare("SELECT COUNT(DISTINCT patient_user_id) as count FROM Appointment WHERE doctor_user_id = ?");
$stmt_patients->bind_param("i", $doctor_id);
$stmt_patients->execute();
$total_patients = $stmt_patients->get_result()->fetch_assoc()['count'];

// 3. Active Prescriptions Count
$stmt_rx = $conn->prepare("SELECT COUNT(*) as count FROM Prescription WHERE doctor_user_id = ? AND status = 'active'");
$stmt_rx->bind_param("i", $doctor_id);
$stmt_rx->execute();
$active_rx = $stmt_rx->get_result()->fetch_assoc()['count'];

// --- Fetch Today's Appointments List ---
$sql_list = "SELECT a.appointment_id, a.appointment_time,
                    p.user_id as patient_user_id, p.first_name, p.last_name, p.dob
             FROM Appointment a
             JOIN Patient p ON a.patient_user_id = p.user_id
             WHERE a.doctor_user_id = ? AND a.appointment_date = CURDATE()
             ORDER BY a.appointment_time ASC";

$stmt_list = $conn->prepare($sql_list);
$stmt_list->bind_param("i", $doctor_id);
$stmt_list->execute();
$today_list = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);

// Helper functions
function calculateAge($dob) {
    return date_diff(date_create($dob), date_create('today'))->y;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Cancer Care</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
</head>
<body>
    <div class="app">
        <?php
// 1. Tell the sidebar which page is active so it can highlight the correct link
$current_page = 'dashboard'; // Change this to 'dashboard', 'prescriptions', etc. on other pages
?>

<!-- ... your <div class="app"> opening tag ... -->

<!-- 2. Inject the sidebar -->
<?php require_once __DIR__ . '/../../../includes/doctor_sidebar.php'; ?>

<!-- ... the rest of your <main> content ... -->

        <main class="main">
            <header class="topbar">
                <div class="topbar-left">
                    <div>
                        <h2>Doctor Dashboard</h2>
                        <p class="date">Welcome back, Doctor</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <button class="signout-btn">Sign Out</button>
                </div>
            </header>

            <div class="content">
                
                <!-- Statistics Cards -->
                <div class="stat-grid">
                    <div class="card stat-card">
                        <div class="stat-icon stat-icon-blue">
                            <span class="icon icon-appointments"></span>
                        </div>
                        <p class="stat-label">Today's Appointments</p>
                        <h3 class="stat-value"><?php echo $today_appts; ?></h3>
                    </div>

                    <div class="card stat-card">
                        <div class="stat-icon stat-icon-teal">
                            <span class="icon icon-profile"></span>
                        </div>
                        <p class="stat-label">Total Patients</p>
                        <h3 class="stat-value"><?php echo $total_patients; ?></h3>
                    </div>

                    <div class="card stat-card">
                        <div class="stat-icon stat-icon-amber">
                            <span class="icon icon-prescriptions"></span>
                        </div>
                        <p class="stat-label">Active Prescriptions</p>
                        <h3 class="stat-value"><?php echo $active_rx; ?></h3>
                    </div>
                </div>

                <!-- Today's Appointments List -->
                <div class="bottom-grid">
                    <div class="card list-card">
                        <div class="list-card-header">
                            <h3>Today's Appointments</h3>
                            <a href="doctor_appointments.php" class="view-all">View All</a>
                        </div>
                        
                        <div class="appointment-list">
                            <?php if (empty($today_list)): ?>
                                <p style="color: var(--text-muted); font-size: 13.5px; padding: 10px 0;">No appointments scheduled for today.</p>
                            <?php else: ?>
                                <?php foreach ($today_list as $appt): ?>
                                    <div class="appointment-item">
                                        <div class="appointment-time">
                                            <span class="time"><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></span>
                                            <span class="date">Today</span>
                                        </div>
                                        <div class="appointment-info">
                                            <p class="appointment-name"><?php echo $appt['first_name'] . ' ' . $appt['last_name']; ?></p>
                                            <p class="appointment-desc">P-<?php echo $appt['patient_user_id']; ?> • Age: <?php echo calculateAge($appt['dob']); ?> • <?php echo htmlspecialchars($appt['appointment_type']); ?></p>
                                        </div>
                                        <a href="view_appointment.php?id=<?php echo $appt['appointment_id']; ?>" class="link-action">View</a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>