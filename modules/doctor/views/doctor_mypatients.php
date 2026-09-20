<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../../../index.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];

// ---- Fetch ALL patients under this doctor (latest record per patient) ----
$sql = "SELECT p.user_id AS patient_user_id, p.first_name, p.last_name, p.nic, p.dob, p.gender,
               mr.cancer_stage AS stage,
               mr.record_date  AS last_visit,
               COALESCE(
                   (SELECT r.cancer_type FROM MedicalReport r
                     WHERE r.patient_user_id = p.user_id
                     ORDER BY r.report_date DESC, r.report_id DESC LIMIT 1),
                   mr.diagnosis
               ) AS cancer_type
        FROM Patient p
        JOIN MedicalRecord mr
          ON mr.record_id = (SELECT MAX(m2.record_id) FROM MedicalRecord m2
                              WHERE m2.patient_user_id = p.user_id
                                AND m2.doctor_user_id = ?)
        ORDER BY mr.record_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$all_patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ---- Stats ----
$total_patients = count($all_patients);
$advanced_count = 0;
foreach ($all_patients as $pt) {
    if ($pt['stage'] === 'Stage III' || $pt['stage'] === 'Stage IV') $advanced_count++;
}

// ---- Filters (GET) ----
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
$stage_filter = isset($_GET['stage']) ? $_GET['stage'] : '';

$patients = $all_patients;
if ($search !== '' || $stage_filter !== '') {
    $patients = array_values(array_filter($patients, function ($pt) use ($search, $stage_filter) {
        if ($search !== '') {
            $hay = strtolower($pt['first_name'] . ' ' . $pt['last_name'] . ' ' . $pt['nic'] . ' ' . $pt['patient_user_id']);
            if (strpos($hay, strtolower($search)) === false) return false;
        }
        if ($stage_filter !== '' && $pt['stage'] !== $stage_filter) return false;
        return true;
    }));
}

function calculateAge($dob) {
    return date_diff(date_create($dob), date_create('today'))->y;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Patients - Cancer Care</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
</head>
<body>
    <div class="app">
        <?php $current_page = 'mypatients'; require_once __DIR__ . '/../../../includes/doctor_sidebar.php'; ?>

        <main class="main">
            <header class="topbar">
                <div class="topbar-left">
                    <div>
                        <h2>My Patients</h2>
                        <p class="date">Manage and view patient information and treatment progress</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <a href="../../../index.php?logout=1" class="signout-btn">Sign Out</a>
                </div>
            </header>

            <div class="content">

                <!-- Statistics -->
                <div class="stat-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="card stat-card">
                        <div class="stat-icon stat-icon-amber"><span class="icon icon-records"></span></div>
                        <p class="stat-label">Total Patients</p>
                        <h3 class="stat-value"><?php echo $total_patients; ?></h3>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon stat-icon-yellow"><span class="icon icon-bell"></span></div>
                        <p class="stat-label">Stage III &amp; IV</p>
                        <h3 class="stat-value"><?php echo $advanced_count; ?></h3>
                    </div>
                </div>

                <!-- Search & Filters -->
                <div class="card wide-card">
                    <form method="GET" action="doctor_mypatients.php" class="doctor-filter-form">
                        <div class="form-field">
                            <label>Search Patients</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, NIC or ID...">
                        </div>
                        <div class="form-field">
                            <label>Cancer Stage</label>
                            <select name="stage">
                                <option value="">All Cancer Stages</option>
                                <option value="Stage I"     <?php echo $stage_filter === 'Stage I' ? 'selected' : ''; ?>>Stage I</option>
                                <option value="Stage II"    <?php echo $stage_filter === 'Stage II' ? 'selected' : ''; ?>>Stage II</option>
                                <option value="Stage III"   <?php echo $stage_filter === 'Stage III' ? 'selected' : ''; ?>>Stage III</option>
                                <option value="Stage IV"    <?php echo $stage_filter === 'Stage IV' ? 'selected' : ''; ?>>Stage IV</option>
                                <option value="In Remission" <?php echo $stage_filter === 'In Remission' ? 'selected' : ''; ?>>In Remission</option>
                            </select>
                        </div>
                        <div class="form-field form-field-action">
                            <button type="submit" class="btn-primary">Filter</button>
                        </div>
                    </form>
                </div>

                <!-- Patient List Table -->
                <div class="card wide-card">
                    <div class="list-card-header">
                        <h3>Patient List (<?php echo count($patients); ?> patients)</h3>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Patient ID</th>
                                    <th>Patient Name</th>
                                    <th>Age/Gender</th>
                                    <th>Cancer Type</th>
                                    <th>Stage</th>
                                    <th>Last Visit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($patients)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 20px; color: var(--text-muted);">No patients found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($patients as $pt): ?>
                                        <tr>
                                            <td class="med-name">P-<?php echo $pt['patient_user_id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($pt['first_name'] . ' ' . $pt['last_name']); ?></strong><br>
                                                <span style="font-size: 12.5px; color: var(--text-muted);">NIC: <?php echo htmlspecialchars($pt['nic']); ?></span>
                                            </td>
                                            <td><?php echo calculateAge($pt['dob']); ?> / <?php echo ucfirst($pt['gender']); ?></td>
                                            <td><?php echo htmlspecialchars($pt['cancer_type']); ?></td>
                                            <td><?php echo htmlspecialchars($pt['stage']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($pt['last_visit'])); ?></td>
                                            <td><a href="view_patient.php?id=<?php echo $pt['patient_user_id']; ?>" class="link-action">View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>