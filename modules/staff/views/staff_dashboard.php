<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'staff') {
    header("Location: ../../../login.php");
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config/database.php';

function dashboard_count($conn, $sql) {
    $result = $conn->query($sql);
    return $result ? (int) $result->fetch_assoc()['total'] : 0;
}

$patient_count = dashboard_count($conn, "SELECT COUNT(*) AS total FROM Patient");
$doctor_count = dashboard_count($conn, "SELECT COUNT(*) AS total FROM Doctor");
$upcoming_appointments = dashboard_count($conn, "SELECT COUNT(*) AS total FROM Appointment WHERE appointment_date >= CURDATE()");
$report_count = dashboard_count($conn, "SELECT COUNT(*) AS total FROM MedicalReport");
$open_needs = dashboard_count($conn, "SELECT COUNT(*) AS total FROM PatientNeed WHERE current_amount < target_amount");
$pending_donations = dashboard_count($conn, "SELECT COUNT(*) AS total FROM Donation WHERE status = 'Pending Verification'");

$appointments = [];
$result = $conn->query(
    "SELECT a.appointment_date, a.appointment_time, a.reason,
            CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
            CONCAT('Dr. ', d.first_name, ' ', d.last_name) AS doctor_name
     FROM Appointment a
     LEFT JOIN Patient p ON p.user_id = a.patient_user_id
     LEFT JOIN Doctor d ON d.user_id = a.doctor_user_id
     WHERE a.appointment_date >= CURDATE()
     ORDER BY a.appointment_date, a.appointment_time
     LIMIT 5"
);
if ($result) {
    $appointments = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Cancer Care</title>
    <link rel="stylesheet" href="../../../public/css/styles.css">
    <link rel="stylesheet" href="../../../public/css/modules.css">
    <style>
        .dashboard-intro { display:flex; justify-content:space-between; gap:20px; margin-bottom:24px; }
        .dashboard-intro h1 { margin:0 0 6px; font-size:1.6rem; }
        .dashboard-intro p, .dashboard-date { color:var(--gray-500); margin:0; }
        .stat-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:18px; margin-bottom:24px; }
        .stat-card { background:var(--white); border-radius:var(--radius-lg); box-shadow:var(--shadow-md); padding:22px; }
        .stat-label { color:var(--gray-500); font-size:.82rem; margin:0 0 8px; }
        .stat-value { color:var(--navy); font-size:1.8rem; margin:0; }
        .stat-note { color:var(--gray-500); font-size:.78rem; margin:8px 0 0; }
        .dashboard-columns { display:grid; grid-template-columns:minmax(0,1.45fr) minmax(280px,.75fr); gap:24px; }
        .widget-header h2 { margin:0; font-size:1.05rem; }
        .widget-header p { color:var(--gray-500); font-size:.82rem; margin:4px 0 0; }
        .quick-links { display:grid; gap:10px; }
        .quick-link { display:flex; justify-content:space-between; align-items:center; padding:13px 14px; background:var(--gray-50); border:1px solid var(--gray-200); border-radius:var(--radius); }
        .quick-link strong, .quick-link span { display:block; }
        .quick-link strong { font-size:.88rem; }
        .quick-link span { color:var(--gray-500); font-size:.77rem; }
        .empty-state { color:var(--gray-500); padding:24px; text-align:center; }
        @media (max-width:900px) { .stat-grid, .dashboard-columns { grid-template-columns:1fr 1fr; } .dashboard-columns > :first-child { grid-column:1 / -1; } }
        @media (max-width:620px) { .dashboard-intro, .stat-grid, .dashboard-columns { display:block; } .dashboard-date { display:block; margin-top:10px; } .stat-card, .widget { margin-bottom:16px; } }
    </style>
</head>
<body>
<div class="app">
    <?php $activePage = 'dashboard'; require __DIR__ . '/sidebar.php'; ?>

    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <h1>Staff Dashboard</h1>
                <p><?php echo date('l, F j, Y'); ?></p>
            </div>
            <div class="topbar-right">
                <a href="../../../logout.php" class="btn btn-outline">Sign Out</a>
                <button class="sidebar-toggle" id="sidebarToggle" type="button">☰</button>
            </div>
        </header>

        <div class="content">
            <div class="dashboard-intro">
                <div>
                    <h1>Good morning, Medical Staff</h1>
                    <p>Here is the current operational picture for Cancer Care.</p>
                </div>
                <span class="dashboard-date">Live system summary</span>
            </div>

            <div class="stat-grid">
                <div class="stat-card"><p class="stat-label">Registered Patients</p><h2 class="stat-value"><?php echo $patient_count; ?></h2><p class="stat-note">Patients in the care system</p></div>
                <div class="stat-card"><p class="stat-label">Available Doctors</p><h2 class="stat-value"><?php echo $doctor_count; ?></h2><p class="stat-note">Assigned care providers</p></div>
                <div class="stat-card"><p class="stat-label">Upcoming Appointments</p><h2 class="stat-value"><?php echo $upcoming_appointments; ?></h2><p class="stat-note">Scheduled from today onward</p></div>
                <div class="stat-card"><p class="stat-label">Medical Reports</p><h2 class="stat-value"><?php echo $report_count; ?></h2><p class="stat-note">Uploaded patient reports</p></div>
                <div class="stat-card"><p class="stat-label">Open Patient Needs</p><h2 class="stat-value"><?php echo $open_needs; ?></h2><p class="stat-note">Support requests not fully funded</p></div>
                <div class="stat-card"><p class="stat-label">Pending Donations</p><h2 class="stat-value"><?php echo $pending_donations; ?></h2><p class="stat-note">Waiting for verification</p></div>
            </div>

            <div class="dashboard-columns">
                <section class="widget">
                    <div class="widget-header">
                        <div><h2>Upcoming Appointments</h2><p>The next appointments requiring staff attention.</p></div>
                        <a href="staff_appointment.php" class="btn btn-outline btn-sm">View All</a>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Date</th><th>Patient</th><th>Doctor</th><th>Reason</th></tr></thead>
                            <tbody>
                                <?php if (!$appointments): ?>
                                    <tr><td colspan="4" class="empty-state">No upcoming appointments.</td></tr>
                                <?php else: foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($appointment['appointment_date']))); ?><br><small><?php echo htmlspecialchars(date('H:i', strtotime($appointment['appointment_time']))); ?></small></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_name'] ?? 'Unknown Patient'); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Unknown Doctor'); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['reason'] ?? 'General consultation'); ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="widget">
                    <div class="widget-header"><div><h2>Quick Actions</h2><p>Open the workflows you use most.</p></div></div>
                    <div class="quick-links">
                        <a href="Register_patient.php" class="quick-link"><span><strong>Register Patient</strong><span>Add a new patient record</span></span><b>→</b></a>
                        <a href="staff_patient.php" class="quick-link"><span><strong>Manage Patients</strong><span>Search and update patient details</span></span><b>→</b></a>
                        <a href="medical_reports.php" class="quick-link"><span><strong>Medical Reports</strong><span>Review uploaded reports</span></span><b>→</b></a>
                        <a href="benefactor.php" class="quick-link"><span><strong>Benefactor Requests</strong><span>Review support submissions</span></span><b>→</b></a>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>
<script>
    const toggleButton = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggleButton && sidebar) toggleButton.addEventListener('click', () => sidebar.classList.toggle('open'));
</script>
</body>
</html>
