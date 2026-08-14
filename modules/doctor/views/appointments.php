<?php
// ==========================================
// 1. PHP LOGIC (Database & Security)
// ==========================================
session_start();
require_once __DIR__ . '/../../../config/database.php';
//if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
 //   header("Location: ../../../index.php");
 //   exit();
//}

$doctor_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? $conn->real_escape_string(trim($_GET['search'])) : '';
$type_filter = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : '';

$sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
                a.reason,
               p.user_id as patient_user_id, p.first_name, p.last_name, p.dob, p.gender
        FROM Appointment a
        JOIN Patient p ON a.patient_user_id = p.user_id
        WHERE a.doctor_user_id = ?";

$params = [$doctor_id];
$types = "i";

if (!empty($search)) {
    $sql .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.user_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Helper functions
function calculateAge($dob) {
    return date_diff(date_create($dob), date_create('today'))->y;
}
function formatDate($date) {
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    if ($date === $today) return 'Today';
    if ($date === $tomorrow) return 'Tomorrow';
    return date('M d, Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Cancer Care</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
</head>
<body>
    <div class="app">
        <!-- Sidebar -->
        <?php
// 1. Tell the sidebar which page is active so it can highlight the correct link
$current_page = 'appointments'; // Change this to 'dashboard', 'prescriptions', etc. on other pages
?>

<!-- ... your <div class="app"> opening tag ... -->

<!-- 2. Inject the sidebar -->
<?php require_once __DIR__ . '/../../../includes/doctor_sidebar.php'; ?>

<!-- ... the rest of your <main> content ... -->

        <!-- Main Content -->
        <main class="main">
            <!-- Top Bar -->
            <header class="topbar">
                <div class="topbar-left">
                    <div>
                        <h2>Appointments</h2>
                        <p class="date">Manage and schedule patient appointments</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <button class="signout-btn">Sign Out</button>
                </div>
            </header>

            <section class="content">
                
                <!-- Search and Filter Section -->
                <div class="card form-card" style="padding-bottom: 24px; margin-bottom: 24px;">
                    <form method="GET" action="doctor_appointments.php" class="form-grid" style="align-items: end;">
                        <div class="form-field">
                            <label>Search Patient</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or ID...">
                        </div>
                        <div class="form-field">
                            <label>Appointment Type</label>
                            <select name="type">
                                <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                                <option value="Follow-up" <?php echo $type_filter === 'Follow-up' ? 'selected' : ''; ?>>Follow-up</option>
                                <option value="New Consultation" <?php echo $type_filter === 'New Consultation' ? 'selected' : ''; ?>>New Consultation</option>
                                <option value="Chemotherapy" <?php echo $type_filter === 'Chemotherapy' ? 'selected' : ''; ?>>Chemotherapy</option>
                                <option value="Lab Review" <?php echo $type_filter === 'Lab Review' ? 'selected' : ''; ?>>Lab Review</option>
                            </select>
                        </div>
                        <div class="form-field" style="justify-content: flex-start;">
                            <button type="submit" class="btn-primary" style="padding: 12px 24px;">Filter</button>
                        </div>
                    </form>
                </div>

                <!-- Appointments Table -->
                <div class="card list-card wide-card">
                    <div class="list-card-header">
                        <h3>Appointment Schedule</h3>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Patient</th>
                                    <th>Patient ID</th>
                                    <th>Type</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($appointments)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">No appointments found matching your criteria.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($appointments as $appt): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo formatDate($appt['appointment_date']); ?></strong><br>
                                                <small class="table-caption" style="margin:0;"><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']); ?></strong><br>
                                                <small class="table-caption" style="margin:0;">Age: <?php echo calculateAge($appt['dob']); ?> • <?php echo ucfirst(htmlspecialchars($appt['gender'])); ?></small>
                                            </td>
                                            <td>P-<?php echo htmlspecialchars($appt['patient_user_id']); ?></td>
                                            <!-- Fallback applied here just in case appointment_type is missing from the query -->
                                            <td><?php echo isset($appt['appointment_type']) ? htmlspecialchars($appt['appointment_type']) : 'Consultation'; ?></td>
                                            <td><?php echo htmlspecialchars($appt['reason']); ?></td>
                                            <td>
                                                <a href="view_appointment.php?id=<?php echo htmlspecialchars($appt['appointment_id']); ?>" class="btn-primary" style="padding: 6px 12px; font-size: 12px;">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>
        </main>
    </div>
</body>
</html>