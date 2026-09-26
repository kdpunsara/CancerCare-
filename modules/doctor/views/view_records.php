<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../../../index.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];

// ---- Determine which view to show ----
$view = 'list'; // default
$selected_patient = null;
$records = [];
$current_record = null;
$record_to_update = null;

if (isset($_GET['patient'])) {
    $patient_id = intval($_GET['patient']);
    $view = 'details';

    // Fetch patient info
    $stmt_p = $conn->prepare("SELECT p.user_id, p.first_name, p.last_name, p.dob, p.gender, p.blood_group, p.nic
                              FROM Patient p WHERE p.user_id = ?");
    $stmt_p->bind_param("i", $patient_id);
    $stmt_p->execute();
    $selected_patient = $stmt_p->get_result()->fetch_assoc();

    // Fetch all medical records for this patient (latest first)
    $stmt_r = $conn->prepare("SELECT mr.*, d.first_name AS doc_first, d.last_name AS doc_last
                              FROM MedicalRecord mr
                              JOIN Doctor d ON d.user_id = mr.doctor_user_id
                              WHERE mr.patient_user_id = ?
                              ORDER BY mr.record_date DESC, mr.record_id DESC");
    $stmt_r->bind_param("i", $patient_id);
    $stmt_r->execute();
    $records = $stmt_r->get_result()->fetch_all(MYSQLI_ASSOC);

    if (!empty($records)) {
        $current_record = $records[0]; // Latest record
    }

    // If updating a specific record
    if (isset($_GET['record'])) {
        $view = 'update';
        $record_id = intval($_GET['record']);
        foreach ($records as $r) {
            if ($r['record_id'] === $record_id) {
                $record_to_update = $r;
                break;
            }
        }
        // Fallback to latest if record not found, or create empty if no records exist
        if (!$record_to_update) {
            if (!empty($records)) {
                $record_to_update = $current_record;
            } else {
                // Initialize an empty record for a new entry
                $record_to_update = [
                    'record_id' => 0,
                    'record_date' => date('Y-m-d'),
                    'diagnosis' => '',
                    'cancer_stage' => '',
                    'clinical_notes' => '',
                    'treatment_plan' => '',
                    'future_treatment_plan' => ''
                ];
            }
        }
    }
}

// ---- Patient Search (GET) ----
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$all_patients = [];
if ($view === 'list') {
    $sql_p = "SELECT p.user_id, p.first_name, p.last_name,
                     mr.cancer_stage, mr.record_date AS last_updated
              FROM Patient p
              LEFT JOIN MedicalRecord mr ON mr.record_id = (
                  SELECT MAX(m2.record_id) FROM MedicalRecord m2
                  WHERE m2.patient_user_id = p.user_id
                    AND m2.doctor_user_id = ?
              )
              WHERE p.user_id IN (SELECT DISTINCT patient_user_id FROM MedicalRecord WHERE doctor_user_id = ?)";
    $stmt_p = $conn->prepare($sql_p);
    $stmt_p->bind_param("ii", $doctor_id, $doctor_id);
    $stmt_p->execute();
    $all_patients = $stmt_p->get_result()->fetch_all(MYSQLI_ASSOC);

    if ($search !== '') {
        $all_patients = array_values(array_filter($all_patients, function ($p) use ($search) {
            $hay = strtolower($p['first_name'] . ' ' . $p['last_name'] . ' ' . $p['user_id']);
            return strpos($hay, strtolower($search)) !== false;
        }));
    }
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
    <title>View Records - Cancer Care</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
</head>
<body>
    <div class="app">
        <?php $current_page = 'records'; require_once __DIR__ . '/../../../includes/doctor_sidebar.php'; ?>

        <main class="main">

            <?php if ($view === 'list'): ?>
                <!-- ========================================== -->
                <!-- VIEW 1: PATIENT SEARCH & LIST              -->
                <!-- ========================================== -->
                <header class="topbar">
                    <div class="topbar-left">
                        <div>
                            <h2>Patient Records Directory</h2>
                            <p class="date">Search and select a patient to view or update their medical records</p>
                        </div>
                    </div>
                    <div class="topbar-actions">
                        <a href="../../../index.php?logout=1" class="signout-btn">Sign Out</a>
                    </div>
                </header>

                <section class="content">
                    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'record_saved'): ?>
                        <div class="card panel" style="border-left: 4px solid var(--teal);">
                            <p class="panel-note" style="color: var(--teal);">Diagnosis updated successfully.</p>
                        </div>
                    <?php endif; ?>

                    <div class="card wide-card">
                        <form method="GET" action="view_records.php" class="form-grid" style="grid-template-columns: minmax(0, 1fr) auto; align-items: end;">
                            <div class="form-field">
                                <label>Search Patients</label>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or Patient ID...">
                            </div>
                            <div class="form-field" style="align-items: flex-end;">
                                <button type="submit" class="btn-primary">Search</button>
                            </div>
                        </form>
                    </div>

                    <div class="card wide-card">
                        <div class="list-card-header">
                            <h3>My Patients (<?php echo count($all_patients); ?>)</h3>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Patient Name</th>
                                        <th>Current Stage</th>
                                        <th>Last Updated</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($all_patients)): ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: 20px; color: var(--text-muted);">No patients found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($all_patients as $p): ?>
                                            <tr>
                                                <td class="med-name">P-<?php echo $p['user_id']; ?></td>
                                                <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                                                <td><span class="badge badge-active"><?php echo htmlspecialchars($p['cancer_stage'] ?: 'N/A'); ?></span></td>
                                                <td><?php echo $p['last_updated'] ? date('M d, Y', strtotime($p['last_updated'])) : '—'; ?></td>
                                                <td><a href="view_records.php?patient=<?php echo $p['user_id']; ?>" class="link-action">View Details</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

            <?php elseif ($view === 'details' && $selected_patient): ?>
                <!-- ========================================== -->
                <!-- VIEW 2: PATIENT DETAILS                    -->
                <!-- ========================================== -->
                <header class="topbar">
                    <div class="topbar-left">
                        <div>
                            <h2>Patient Medical Records</h2>
                            <p class="date">Comprehensive view of current status and historical diagnosis records</p>
                        </div>
                    </div>
                    <div class="topbar-actions">
                        <a href="view_records.php" class="btn-secondary" style="margin-right: 12px;">Back to List</a>
                        <a href="view_records.php?patient=<?php echo $selected_patient['user_id']; ?>&record=<?php echo $current_record ? $current_record['record_id'] : 0; ?>" class="btn-primary" style="margin-right: 12px;"><?php echo $current_record ? 'Update Diagnosis' : 'Add Diagnosis'; ?></a>
                        <a href="../../../index.php?logout=1" class="signout-btn">Sign Out</a>
                    </div>
                </header>

                <section class="content">
                    <!-- Patient Header -->
                    <div class="card panel">
                        <div style="display: flex; gap: 20px; align-items: center;">
                            <div class="avatar" style="width: 56px; height: 56px; font-size: 18px;">
                                <?php echo strtoupper(substr($selected_patient['first_name'], 0, 1) . substr($selected_patient['last_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h2 style="margin: 0 0 6px; font-size: 20px;">
                                    <?php echo htmlspecialchars($selected_patient['first_name'] . ' ' . $selected_patient['last_name']); ?>
                                    <?php if ($current_record): ?>
                                        <span class="badge badge-active" style="margin-left: 8px;"><?php echo htmlspecialchars($current_record['cancer_stage']); ?></span>
                                    <?php endif; ?>
                                </h2>
                                <p class="panel-note">
                                    ID: P-<?php echo $selected_patient['user_id']; ?> |
                                    Age: <?php echo calculateAge($selected_patient['dob']); ?> |
                                    <?php echo ucfirst($selected_patient['gender']); ?> |
                                    Blood Group: <?php echo htmlspecialchars($selected_patient['blood_group'] ?: 'N/A'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Current Diagnosis -->
                    <?php if ($current_record): ?>
                        <div class="card form-card">
                            <h3 class="form-title">Current Diagnosis &amp; Treatment Plan</h3>
                            <p class="form-subtitle">Latest active plan outlined for this patient.</p>
                            <div class="form-grid">
                                <div class="form-field form-field-wide">
                                    <label>Primary Diagnosis</label>
                                    <textarea readonly rows="3" style="background: var(--bg); pointer-events: none;"><?php echo htmlspecialchars($current_record['diagnosis']); ?></textarea>
                                </div>
                                <div class="form-field form-field-wide">
                                    <label>Current Treatment Plan</label>
                                    <textarea readonly rows="3" style="background: var(--bg); pointer-events: none;"><?php echo htmlspecialchars($current_record['treatment_plan']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Previous Records History -->
                    <div class="card wide-card">
                        <div class="list-card-header">
                            <h3>Previous Diagnosis &amp; Treatment History</h3>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date Updated</th>
                                        <th>Updated By</th>
                                        <th>Diagnosis / Findings</th>
                                        <th>Stage</th>
                                        <th>Treatment Plan</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($records)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">No records found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($records as $r): ?>
                                            <tr>
                                                <td><strong><?php echo date('M d, Y', strtotime($r['record_date'])); ?></strong></td>
                                                <td>Dr. <?php echo htmlspecialchars($r['doc_first'] . ' ' . $r['doc_last']); ?></td>
                                                <td style="max-width: 250px; white-space: normal; line-height: 1.4;"><?php echo htmlspecialchars($r['clinical_notes'] ?: $r['diagnosis']); ?></td>
                                                <td><span class="badge badge-active"><?php echo htmlspecialchars($r['cancer_stage']); ?></span></td>
                                                <td style="max-width: 200px; white-space: normal; line-height: 1.4;"><?php echo htmlspecialchars($r['treatment_plan']); ?></td>
                                                <td><a href="view_records.php?patient=<?php echo $selected_patient['user_id']; ?>&record=<?php echo $r['record_id']; ?>" class="link-action">View / Edit</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

            <?php elseif ($view === 'update' && $record_to_update && $selected_patient): ?>
                <!-- ========================================== -->
                <!-- VIEW 3: UPDATE DIAGNOSIS                   -->
                <!-- ========================================== -->
                <header class="topbar">
                    <div class="topbar-left">
                        <div>
                            <h2>Update Diagnosis &amp; Treatment</h2>
                            <p class="date">Update patient medical status, diagnosis reports, and treatment history</p>
                        </div>
                    </div>
                    <div class="topbar-actions">
                        <a href="view_records.php?patient=<?php echo $selected_patient['user_id']; ?>" class="btn-secondary" style="margin-right: 12px;">Back to Details</a>
                        <a href="../../../index.php?logout=1" class="signout-btn">Sign Out</a>
                    </div>
                </header>

                <section class="content">
                    <?php if (isset($_GET['error'])): ?>
                        <div class="card panel" style="border-left: 4px solid var(--red);">
                            <p class="panel-note" style="color: var(--red);">
                                <?php echo $_GET['error'] === 'invalid_record' ? 'Could not save: please fill all required fields.' : 'Database error while saving the record.'; ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Patient Context -->
                    <div class="card form-card">
                        <div class="form-grid" style="align-items: center;">
                            <div class="form-field">
                                <label>Selected Patient</label>
                                <input type="text" value="P-<?php echo $selected_patient['user_id']; ?> - <?php echo htmlspecialchars($selected_patient['first_name'] . ' ' . $selected_patient['last_name']); ?>" readonly style="background: var(--bg); pointer-events: none;">
                            </div>
                            <div style="display: flex; gap: 24px; margin-top: 10px;">
                                <div>
                                    <span class="form-section-label" style="display: block; margin: 0 0 6px 0;">Current Stage</span>
                                    <span class="badge badge-active"><?php echo htmlspecialchars($record_to_update['cancer_stage']); ?></span>
                                </div>
                                <div>
                                    <span class="form-section-label" style="display: block; margin: 0 0 6px 0;">Last Updated</span>
                                    <div style="font-weight: 600; font-size: 13.5px;"><?php echo date('M d, Y', strtotime($record_to_update['record_date'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="../doctor_actions.php">
                        <input type="hidden" name="action" value="update_diagnosis">
                        <input type="hidden" name="patient_user_id" value="<?php echo $selected_patient['user_id']; ?>">
                        <input type="hidden" name="record_id" value="<?php echo $record_to_update['record_id']; ?>">

                        <!-- Section 1: Update Diagnosis -->
                        <div class="card form-card">
                            <h3 class="form-title">Update Diagnosis Report</h3>
                            <p class="form-subtitle">Record clinical observations and confirm staging details.</p>
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Cancer Type / Primary Diagnosis *</label>
                                    <input type="text" name="diagnosis" value="<?php echo htmlspecialchars($record_to_update['diagnosis']); ?>" required>
                                </div>
                                <div class="form-field">
                                    <label>Cancer Stage *</label>
                                    <select name="cancer_stage" required>
                                        <option value="Stage I"        <?php echo $record_to_update['cancer_stage'] === 'Stage I' ? 'selected' : ''; ?>>Stage I</option>
                                        <option value="Stage II"       <?php echo $record_to_update['cancer_stage'] === 'Stage II' ? 'selected' : ''; ?>>Stage II</option>
                                        <option value="Stage III"      <?php echo $record_to_update['cancer_stage'] === 'Stage III' ? 'selected' : ''; ?>>Stage III</option>
                                        <option value="Stage IV"       <?php echo $record_to_update['cancer_stage'] === 'Stage IV' ? 'selected' : ''; ?>>Stage IV</option>
                                        <option value="In Remission"   <?php echo $record_to_update['cancer_stage'] === 'In Remission' ? 'selected' : ''; ?>>In Remission</option>
                                    </select>
                                </div>
                                <div class="form-field form-field-wide">
                                    <label>Clinical Findings &amp; Diagnosis Notes *</label>
                                    <textarea name="clinical_notes" rows="4" required placeholder="Enter detailed clinical observations, biopsy results, or scan interpretations..."><?php echo htmlspecialchars($record_to_update['clinical_notes']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Treatment History -->
                        <div class="card form-card">
                            <h3 class="form-title">Update Treatment History &amp; Plan</h3>
                            <p class="form-subtitle">Record current treatment response and establish the path forward.</p>
                            <div class="form-grid">
                                <div class="form-field form-field-wide">
                                    <label>Current Treatment Protocol</label>
                                    <input type="text" name="treatment_plan" value="<?php echo htmlspecialchars($record_to_update['treatment_plan']); ?>" placeholder="e.g. FOLFOX Chemotherapy (Cycle 4 of 8)">
                                </div>
                                <div class="form-field form-field-wide">
                                    <label>Future Treatment Plan &amp; Next Steps</label>
                                    <textarea name="future_treatment_plan" rows="3" placeholder="Outline the next phase of treatment..."><?php echo htmlspecialchars($record_to_update['future_treatment_plan']); ?></textarea>
                                </div>
                            </div>

                            <div class="form-actions">
                                <a href="view_records.php?patient=<?php echo $selected_patient['user_id']; ?>" class="btn-secondary">Cancel</a>
                                <button type="submit" class="btn-primary">Save Diagnosis Updates</button>
                            </div>
                        </div>
                    </form>
                </section>

            <?php else: ?>
                <!-- Fallback if patient not found -->
                <header class="topbar">
                    <div class="topbar-left">
                        <div>
                            <h2>Patient Not Found</h2>
                            <p class="date">The requested patient record could not be located.</p>
                        </div>
                    </div>
                    <div class="topbar-actions">
                        <a href="view_records.php" class="btn-secondary">Back to List</a>
                    </div>
                </header>
            <?php endif; ?>

        </main>
    </div>
</body>
</html>