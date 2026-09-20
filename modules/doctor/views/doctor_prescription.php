<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../../../index.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];

// ---- Stats ----
$stmt_stats = $conn->prepare("SELECT 
        COUNT(*) AS total,
        SUM(status = 'active') AS active_count,
        SUM(MONTH(prescription_date) = MONTH(CURDATE()) AND YEAR(prescription_date) = YEAR(CURDATE())) AS month_count
      FROM Prescription WHERE doctor_user_id = ?");
$stmt_stats->bind_param("i", $doctor_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// ---- Filters (GET) ----
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_range    = isset($_GET['range']) ? $_GET['range'] : '';

$sql = "SELECT p.prescription_id, p.prescription_date, p.status,
               pt.user_id AS patient_user_id, pt.first_name, pt.last_name,
               (SELECT COUNT(*) FROM PrescriptionItem pi WHERE pi.prescription_id = p.prescription_id) AS item_count,
               (SELECT GROUP_CONCAT(m.medicine_name SEPARATOR ', ')
                  FROM PrescriptionItem pi
                  JOIN Medicine m ON m.medicine_id = pi.medicine_id
                 WHERE pi.prescription_id = p.prescription_id) AS medicine_names
        FROM Prescription p
        JOIN Patient pt ON pt.user_id = p.patient_user_id
        WHERE p.doctor_user_id = ?";

$params = [$doctor_id];
$types  = "i";

if ($search !== '') {
    $sql .= " AND (pt.first_name LIKE ? OR pt.last_name LIKE ? OR p.prescription_id LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}

if (in_array($status_filter, ['active', 'completed', 'cancelled'], true)) {
    $sql .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_range === 'today') {
    $sql .= " AND p.prescription_date = CURDATE()";
} elseif ($date_range === 'week') {
    $sql .= " AND YEARWEEK(p.prescription_date) = YEARWEEK(CURDATE())";
} elseif ($date_range === 'month') {
    $sql .= " AND MONTH(p.prescription_date) = MONTH(CURDATE()) AND YEAR(p.prescription_date) = YEAR(CURDATE())";
}

$sql .= " ORDER BY p.prescription_date DESC, p.prescription_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ---- Add Prescription Dialog State ----
$modal_open      = isset($_GET['new']);
$patient_input   = isset($_GET['patient']) ? intval($_GET['patient']) : 0;
$patient         = null;
$patient_not_found = false;
$medicines       = [];
$row_count       = isset($_GET['rows']) ? max(1, min(10, intval($_GET['rows']))) : 1;
$view_prescription_id = isset($_GET['view']) ? intval($_GET['view']) : 0;
$view_prescription = null;
$view_items = [];

if ($view_prescription_id > 0) {
    $stmt_view = $conn->prepare("SELECT p.prescription_id, p.patient_user_id, p.prescription_date, p.status, p.diagnosis_notes,
                              pt.user_id, pt.first_name, pt.last_name, pt.dob, pt.gender, pt.blood_group, pt.allergies
                              FROM Prescription p
                              JOIN Patient pt ON pt.user_id = p.patient_user_id
                              WHERE p.prescription_id = ? AND p.doctor_user_id = ?");
    $stmt_view->bind_param("ii", $view_prescription_id, $doctor_id);
    $stmt_view->execute();
    $view_prescription = $stmt_view->get_result()->fetch_assoc();

    if ($view_prescription) {
        $stmt_items = $conn->prepare("SELECT pi.item_id, pi.medicine_id, m.medicine_name, pi.dosage, pi.frequency, pi.duration, pi.instructions
                                     FROM PrescriptionItem pi
                                     JOIN Medicine m ON m.medicine_id = pi.medicine_id
                                     WHERE pi.prescription_id = ?
                                     ORDER BY pi.item_id ASC");
        $stmt_items->bind_param("i", $view_prescription_id);
        $stmt_items->execute();
        $view_items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

if ($patient_input > 0) {
    $modal_open = true;
    $stmt_p = $conn->prepare("SELECT p.user_id, p.first_name, p.last_name, p.dob, p.gender, p.blood_group, p.allergies
                              FROM Patient p WHERE p.user_id = ?");
    $stmt_p->bind_param("i", $patient_input);
    $stmt_p->execute();
    $patient = $stmt_p->get_result()->fetch_assoc();
    if (!$patient) $patient_not_found = true;
}

if (isset($_GET['error'])) $modal_open = true;

$medicines = $conn->query("SELECT medicine_id, medicine_name, generic_name FROM Medicine ORDER BY medicine_name ASC")->fetch_all(MYSQLI_ASSOC);

function statusBadge($status) {
    switch ($status) {
        case 'active':    return 'badge-active';
        case 'completed': return 'badge-done';
        default:          return 'badge-pending';
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
    <title>Prescriptions - Cancer Care</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
    <link rel="stylesheet" href="../../../public/css/prescription.css">
    
    <!-- Select2 (jQuery) for searchable dropdowns with tags -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        /* ---- Select2: match our form-field input styles ---- */
        .select2-container { width: 100% !important; }
        .select2-container .select2-selection--single {
            height: 42px !important;
            border: 1px solid var(--border, #e7e9f2) !important;
            border-radius: var(--radius-sm, 8px) !important;
            background: #fafbfd !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px !important;
            font-size: 13.5px;
            color: var(--text, #14162b);
            padding: 0 40px 0 14px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: var(--text-muted, #6b7280);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px !important;
            width: 30px !important;
            top: 0 !important;
            right: 4px !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--blue, #4f6ef7) !important;
            background: #fff !important;
            outline: none !important;
            box-shadow: none !important;
        }
        /* ---- Dropdown panel ---- */
        .select2-container--open .select2-dropdown {
            z-index: 99999 !important;
        }
        .select2-dropdown {
            border: 1px solid var(--blue, #4f6ef7) !important;
            border-radius: var(--radius-sm, 8px) !important;
            box-shadow: 0 8px 24px rgba(16,24,40,0.12) !important;
        }
        .select2-search--dropdown .select2-search__field {
            border-radius: 4px !important;
            border: 1px solid var(--border, #e7e9f2) !important;
            padding: 6px 10px !important;
            font-size: 13.5px !important;
            outline: none;
        }
        .select2-results__option {
            font-size: 13.5px;
            padding: 9px 14px;
        }
        .select2-results__option--highlighted {
            background: var(--blue, #4f6ef7) !important;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(11, 16, 32, 0.42);
            display: none;
            justify-content: flex-start;
            align-items: center;
            padding: 18px 18px 18px 240px;
            z-index: 9999;
        }

        .modal-overlay.open {
            display: flex;
        }

        .modal-card {
            width: min(980px, calc(100vw - 260px));
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 16px;
            background: var(--card, #fff);
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
            margin-left: 30px;
        }

        .modal-head {
            flex-shrink: 0;
            position: relative;
        }

        .modal-body-scroll {
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0 18px 18px;
            scrollbar-width: thin;
            scrollbar-color: rgba(90, 101, 126, 0.65) transparent;
        }

        .modal-body-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body-scroll::-webkit-scrollbar-thumb {
            background: rgba(90, 101, 126, 0.65);
            border-radius: 999px;
        }
    </style>
</head>
<body>
    <div class="app">
        <?php $current_page = 'prescriptions'; require_once __DIR__ . '/../../../includes/doctor_sidebar.php'; ?>

        <main class="main">
            <header class="topbar">
                <div class="topbar-left">
                    <div>
                        <h2>Prescriptions</h2>
                        <p class="date">Manage and issue digital prescriptions for your patients</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <a href="../../../index.php?logout=1" class="signout-btn">Sign Out</a>
                </div>
            </header>

            <div class="content">

                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'rx_saved'): ?>
                    <div class="card panel">
                        <p class="panel-note">Prescription created successfully.</p>
                    </div>
                <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'rx_updated'): ?>
                    <div class="card panel">
                        <p class="panel-note">Prescription updated successfully.</p>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="stat-grid">
                    <div class="card stat-card">
                        <div class="stat-icon stat-icon-teal"><span class="icon icon-prescriptions"></span></div>
                        <p class="stat-label">Total Prescriptions</p>
                        <h3 class="stat-value"><?php echo intval($stats['total']); ?></h3>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon stat-icon-amber"><span class="icon icon-records"></span></div>
                        <p class="stat-label">Currently Active</p>
                        <h3 class="stat-value"><?php echo intval($stats['active_count']); ?></h3>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-icon stat-icon-blue"><span class="icon icon-appointments"></span></div>
                        <p class="stat-label">Issued This Month</p>
                        <h3 class="stat-value"><?php echo intval($stats['month_count']); ?></h3>
                    </div>
                </div>

                <!-- Search & Filters -->
                <div class="card wide-card">
                    <form method="GET" action="doctor_prescription.php" class="rx-filter-form">
                        <div class="form-field">
                            <label>Search Prescriptions</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by patient name or Rx ID...">
                        </div>
                        <div class="form-field">
                            <label>Status</label>
                            <select name="status">
                                <option value="">All Statuses</option>
                                <option value="active"    <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Date Range</label>
                            <select name="range">
                                <option value="">All Time</option>
                                <option value="today" <?php echo $date_range === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week"  <?php echo $date_range === 'week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="month" <?php echo $date_range === 'month' ? 'selected' : ''; ?>>This Month</option>
                            </select>
                        </div>
                        <div class="form-field form-field-action">
                            <button type="submit" class="btn-primary">Filter</button>
                        </div>
                    </form>
                </div>

                <!-- Prescriptions Table -->
                <div class="card wide-card">
                    <div class="list-card-header">
                        <h3>Issued Prescriptions</h3>
                        <a href="doctor_prescription.php?new=1" class="btn-primary">Add Prescription</a>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rx ID</th>
                                    <th>Date Issued</th>
                                    <th>Patient</th>
                                    <th>Medications</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($prescriptions)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">No prescriptions found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($prescriptions as $rx): ?>
                                        <tr>
                                            <td class="med-name">RX-<?php echo str_pad($rx['prescription_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($rx['prescription_date'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($rx['first_name'] . ' ' . $rx['last_name']); ?></strong><br>
                                                <span style="font-size: 12.5px; color: var(--text-muted);">P-<?php echo $rx['patient_user_id']; ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo intval($rx['item_count']); ?> items</strong><br>
                                                <span style="font-size: 12.5px; color: var(--text-muted);">
                                                    <?php echo $rx['medicine_names'] ? htmlspecialchars($rx['medicine_names']) : 'Voided by doctor'; ?>
                                                </span>
                                            </td>
                                            <td><span class="badge <?php echo statusBadge($rx['status']); ?>"><?php echo ucfirst($rx['status']); ?></span></td>
                                            <td><a href="doctor_prescription.php?view=<?php echo $rx['prescription_id']; ?>" class="link-action">View</a></td>
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

    <!-- ========================================== -->
    <!-- ADD PRESCRIPTION DIALOG (same page)        -->
    <!-- ========================================== -->
    <div class="modal-overlay <?php echo $modal_open ? 'open' : ''; ?>">
        <div class="modal-card">
            <div class="modal-head">
                <h3>New Prescription</h3>
                <a href="doctor_prescription.php" class="modal-close" title="Close">&times;</a>
            </div>
            <div class="modal-body-scroll">
                <p class="form-subtitle" style="margin: 0 0 18px;">Search the patient first, then add medicines from the system catalogue.</p>

                <?php if (isset($_GET['error'])): ?>
                    <p class="panel-note" style="color: var(--red); margin-bottom: 14px;">
                        <?php echo $_GET['error'] === 'invalid_prescription'
                            ? 'Could not save: please select at least one medicine with a dosage and frequency.'
                            : 'Database error while saving the prescription. Please try again.'; ?>
                    </p>
                <?php endif; ?>

                <!-- Step 1: Search Patient -->
                <form method="GET" action="doctor_prescription.php" class="form-grid">
                    <input type="hidden" name="new" value="1">
                    <div class="form-field">
                        <label>Patient ID</label>
                        <input type="number" name="patient" value="<?php echo $patient_input > 0 ? $patient_input : ''; ?>" placeholder="e.g. 100001" required>
                    </div>
                    <div class="form-field" style="align-items: flex-start; margin-top:22px;">
                        <button type="submit" class="btn-primary">Search Patient</button>
                    </div>
                </form>

                <?php if ($patient_not_found): ?>
                    <p class="panel-note" style="color: var(--red); margin-top: 14px;">No patient found with ID <?php echo $patient_input; ?>. Please check the ID and try again.</p>
                <?php endif; ?>

                <?php if ($patient): ?>
                    <!-- Step 2: Confirmed Patient -->
                    <div class="panel" style="margin-top: 18px; background: #fafbfd; border: 1px solid var(--border); border-radius: var(--radius-md);">
                        <h3 class="panel-title"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?> (P-<?php echo $patient['user_id']; ?>)</h3>
                        <p class="panel-note">
                            Age: <?php echo calculateAge($patient['dob']); ?> &bull;
                            <?php echo ucfirst($patient['gender']); ?> &bull;
                            Blood Group: <?php echo htmlspecialchars($patient['blood_group']); ?> &bull;
                            Allergies: <?php echo $patient['allergies'] ? htmlspecialchars($patient['allergies']) : 'None recorded'; ?>
                        </p>
                    </div>

                    <!-- Step 3: Prescription Form -->
                    <form method="POST" action="../doctor_actions.php">
                        <input type="hidden" name="action" value="save_prescription">
                        <input type="hidden" name="patient_user_id" value="<?php echo $patient['user_id']; ?>">

                        <p class="form-section-label">Diagnosis</p>
                        <div class="form-grid">
                            <div class="form-field form-field-wide">
                                <label>Diagnosis / Clinical Notes</label>
                                <textarea name="diagnosis_notes" rows="3" placeholder="e.g. Post-chemotherapy nausea management"></textarea>
                            </div>
                        </div>

                        <!-- Medicine rows container -->
                        <div id="medicine-rows-container">
                            <div class="medicine-row">
                                <p class="form-section-label med-row-label">Medicine 1</p>
                                <div class="form-grid">
                                    <div class="form-field">
                                        <label>Medicine *</label>
                                        <select name="medicine_name[]" class="searchable-select" data-placeholder="Type or select a medicine..." required style="width: 100%;">
                                            <option value=""></option>
                                            <?php foreach ($medicines as $m): ?>
                                                <option value="<?php echo htmlspecialchars($m['medicine_name']); ?>">
                                                    <?php echo htmlspecialchars($m['medicine_name']); ?><?php echo $m['generic_name'] ? ' (' . htmlspecialchars($m['generic_name']) . ')' : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label>Dosage *</label>
                                        <input type="text" name="dosage[]" placeholder="e.g. 8mg" required>
                                    </div>
                                    <div class="form-field">
                                        <label>Frequency *</label>
                                        <select name="frequency[]" class="searchable-select" data-placeholder="e.g. Twice daily" required style="width: 100%;">
                                            <option value=""></option>
                                            <option value="Once daily">Once daily</option>
                                            <option value="Twice daily">Twice daily</option>
                                            <option value="Three times daily">Three times daily</option>
                                            <option value="Every 8 hours">Every 8 hours</option>
                                            <option value="Every 12 hours">Every 12 hours</option>
                                            <option value="As needed">As needed</option>
                                            <option value="At bedtime">At bedtime</option>
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label>Duration</label>
                                        <input type="text" name="duration[]" placeholder="e.g. 7 days">
                                    </div>
                                    <div class="form-field form-field-wide">
                                        <label>Instructions</label>
                                        <input type="text" name="instructions[]" placeholder="e.g. Take 30 minutes before meals">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p style="margin: 16px 0 0;">
                            <button type="button" class="link-action" id="add-med-row-btn" style="background:none;border:none;padding:0;cursor:pointer;font-family:inherit;font-size:13.5px;color:var(--blue);font-weight:600;">+ Add Another Medicine Row</button>
                        </p>

                        <div class="form-actions">
                            <a href="doctor_prescription.php" class="btn-secondary">Cancel</a>
                            <button type="submit" class="btn-primary">Save Prescription</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Hidden template cloned by JS for adding new medicine rows without refreshing -->
    <template id="medicine-row-template">
        <div class="medicine-row" style="margin-top: 22px; padding-top: 18px; border-top: 1px dashed var(--border);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 8px;">
                <p class="form-section-label med-row-label" style="margin:0;"></p>
                <button type="button" class="remove-med-row" style="background:none;border:none;padding:0;cursor:pointer;font-size:13px;color:var(--red,#e0435c);font-family:inherit;font-weight:600;">✕ Remove</button>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label>Medicine *</label>
                    <select name="medicine_name[]" class="searchable-select" data-placeholder="Type or select a medicine..." required style="width: 100%;">
                        <option value=""></option>
                        <?php foreach ($medicines as $m): ?>
                            <option value="<?php echo htmlspecialchars($m['medicine_name']); ?>">
                                <?php echo htmlspecialchars($m['medicine_name']); ?><?php echo $m['generic_name'] ? ' (' . htmlspecialchars($m['generic_name']) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>Dosage *</label>
                    <input type="text" name="dosage[]" placeholder="e.g. 8mg" required>
                </div>
                <div class="form-field">
                    <label>Frequency *</label>
                    <select name="frequency[]" class="searchable-select" data-placeholder="e.g. Twice daily" required style="width: 100%;">
                        <option value=""></option>
                        <option value="Once daily">Once daily</option>
                        <option value="Twice daily">Twice daily</option>
                        <option value="Three times daily">Three times daily</option>
                        <option value="Every 8 hours">Every 8 hours</option>
                        <option value="Every 12 hours">Every 12 hours</option>
                        <option value="As needed">As needed</option>
                        <option value="At bedtime">At bedtime</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Duration</label>
                    <input type="text" name="duration[]" placeholder="e.g. 7 days">
                </div>
                <div class="form-field form-field-wide">
                    <label>Instructions</label>
                    <input type="text" name="instructions[]" placeholder="e.g. Take 30 minutes before meals">
                </div>
            </div>
        </div>
    </template>

    <?php if ($view_prescription): ?>
    <div class="modal-overlay open">
        <div class="modal-card" style="max-width: 980px;">
            <div class="modal-head">
                <h3>Prescription Details</h3>
                <a href="doctor_prescription.php" class="modal-close" title="Close">&times;</a>
            </div>
            <div class="modal-body-scroll">
                <div class="panel" style="margin: 10px 0 18px; background: #fafbfd; border: 1px solid var(--border); border-radius: var(--radius-md);">
                    <h3 class="panel-title"><?php echo htmlspecialchars($view_prescription['first_name'] . ' ' . $view_prescription['last_name']); ?> (P-<?php echo $view_prescription['user_id']; ?>)</h3>
                    <p class="panel-note">
                        Rx ID: RX-<?php echo str_pad($view_prescription['prescription_id'], 4, '0', STR_PAD_LEFT); ?> &bull;
                        Date: <?php echo date('M d, Y', strtotime($view_prescription['prescription_date'])); ?> &bull;
                        Status: <span class="badge <?php echo statusBadge($view_prescription['status']); ?>"><?php echo ucfirst($view_prescription['status']); ?></span>
                    </p>
                </div>

                <form method="POST" action="../doctor_actions.php">
                    <input type="hidden" name="action" value="update_prescription">
                    <input type="hidden" name="prescription_id" value="<?php echo $view_prescription['prescription_id']; ?>">

                    <div class="form-grid">
                        <div class="form-field form-field-wide">
                            <label>Diagnosis / Clinical Notes</label>
                            <textarea name="diagnosis_notes" rows="3" placeholder="Enter diagnosis notes"><?php echo htmlspecialchars($view_prescription['diagnosis_notes'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div id="edit-medicine-rows-container" style="margin-top: 18px;">
                        <?php if (empty($view_items)): ?>
                            <p class="panel-note" style="margin-bottom: 12px;">No medicines attached to this prescription yet.</p>
                        <?php else: ?>
                            <?php foreach ($view_items as $index => $item): ?>
                                <div class="medicine-row" style="margin-top: 22px; padding-top: 18px; border-top: 1px dashed var(--border);">
                                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 8px;">
                                        <p class="form-section-label med-row-label" style="margin:0;">Medicine <?php echo $index + 1; ?></p>
                                        <button type="button" class="remove-item-btn" data-item-id="<?php echo $item['item_id']; ?>" data-prescription-id="<?php echo $view_prescription['prescription_id']; ?>" style="background:none;border:none;padding:0;cursor:pointer;font-size:13px;color:var(--red,#e0435c);font-family:inherit;font-weight:600;">✕ Remove</button>
                                    </div>

                                    <input type="hidden" name="existing_item_id[]" value="<?php echo $item['item_id']; ?>">
                                    <div class="form-grid">
                                        <div class="form-field">
                                            <label>Medicine *</label>
                                            <select name="edit_medicine_name[]" class="searchable-select" data-placeholder="Type or select a medicine..." required style="width: 100%;">
                                                <option value=""></option>
                                                <?php foreach ($medicines as $m): ?>
                                                    <option value="<?php echo htmlspecialchars($m['medicine_name']); ?>" <?php echo ($m['medicine_name'] === $item['medicine_name']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($m['medicine_name']); ?><?php echo $m['generic_name'] ? ' (' . htmlspecialchars($m['generic_name']) . ')' : ''; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label>Dosage *</label>
                                            <input type="text" name="edit_dosage[]" value="<?php echo htmlspecialchars($item['dosage']); ?>" placeholder="e.g. 8mg" required>
                                        </div>
                                        <div class="form-field">
                                            <label>Frequency *</label>
                                            <select name="edit_frequency[]" class="searchable-select" data-placeholder="e.g. Twice daily" required style="width: 100%;">
                                                <option value=""></option>
                                                <option value="Once daily" <?php echo ($item['frequency'] === 'Once daily') ? 'selected' : ''; ?>>Once daily</option>
                                                <option value="Twice daily" <?php echo ($item['frequency'] === 'Twice daily') ? 'selected' : ''; ?>>Twice daily</option>
                                                <option value="Three times daily" <?php echo ($item['frequency'] === 'Three times daily') ? 'selected' : ''; ?>>Three times daily</option>
                                                <option value="Every 8 hours" <?php echo ($item['frequency'] === 'Every 8 hours') ? 'selected' : ''; ?>>Every 8 hours</option>
                                                <option value="Every 12 hours" <?php echo ($item['frequency'] === 'Every 12 hours') ? 'selected' : ''; ?>>Every 12 hours</option>
                                                <option value="As needed" <?php echo ($item['frequency'] === 'As needed') ? 'selected' : ''; ?>>As needed</option>
                                                <option value="At bedtime" <?php echo ($item['frequency'] === 'At bedtime') ? 'selected' : ''; ?>>At bedtime</option>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label>Duration</label>
                                            <input type="text" name="edit_duration[]" value="<?php echo htmlspecialchars($item['duration']); ?>" placeholder="e.g. 7 days">
                                        </div>
                                        <div class="form-field form-field-wide">
                                            <label>Instructions</label>
                                            <input type="text" name="edit_instructions[]" value="<?php echo htmlspecialchars($item['instructions']); ?>" placeholder="e.g. Take 30 minutes before meals">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <p style="margin: 16px 0 0;">
                        <button type="button" class="link-action" id="add-edit-med-row-btn" style="background:none;border:none;padding:0;cursor:pointer;font-family:inherit;font-size:13.5px;color:var(--blue);font-weight:600;">+ Add Another Medicine</button>
                    </p>

                    <div class="form-actions" style="margin-top: 24px;">
                        <a href="doctor_prescription.php" class="btn-secondary">Close</a>
                        <button type="submit" class="btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <template id="edit-medicine-row-template">
        <div class="medicine-row" style="margin-top: 22px; padding-top: 18px; border-top: 1px dashed var(--border);">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 8px;">
                <p class="form-section-label med-row-label" style="margin:0;">Medicine</p>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label>Medicine *</label>
                    <select name="new_medicine_name[]" class="searchable-select" data-placeholder="Type or select a medicine..." required style="width: 100%;">
                        <option value=""></option>
                        <?php foreach ($medicines as $m): ?>
                            <option value="<?php echo htmlspecialchars($m['medicine_name']); ?>"><?php echo htmlspecialchars($m['medicine_name']); ?><?php echo $m['generic_name'] ? ' (' . htmlspecialchars($m['generic_name']) . ')' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>Dosage *</label>
                    <input type="text" name="new_dosage[]" placeholder="e.g. 8mg" required>
                </div>
                <div class="form-field">
                    <label>Frequency *</label>
                    <select name="new_frequency[]" class="searchable-select" data-placeholder="e.g. Twice daily" required style="width: 100%;">
                        <option value=""></option>
                        <option value="Once daily">Once daily</option>
                        <option value="Twice daily">Twice daily</option>
                        <option value="Three times daily">Three times daily</option>
                        <option value="Every 8 hours">Every 8 hours</option>
                        <option value="Every 12 hours">Every 12 hours</option>
                        <option value="As needed">As needed</option>
                        <option value="At bedtime">At bedtime</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Duration</label>
                    <input type="text" name="new_duration[]" placeholder="e.g. 7 days">
                </div>
                <div class="form-field form-field-wide">
                    <label>Instructions</label>
                    <input type="text" name="new_instructions[]" placeholder="e.g. Take 30 minutes before meals">
                </div>
            </div>
        </div>
    </template>

    <script>
        const SELECT2_CONFIG = {
            tags: true,
            dropdownParent: $('body'),
            dropdownAutoWidth: false,
            placeholder: function() {
                return $(this).data('placeholder') || '';
            }
        };

        function initSelect2OnRow(rowEl) {
            $(rowEl).find('.searchable-select').each(function () {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
                $(this).select2(SELECT2_CONFIG);
            });
        }

        function renumberRows(selector) {
            $(selector + ' .medicine-row').each(function (i) {
                $(this).find('.med-row-label').text('Medicine ' + (i + 1));
            });
        }

        $(document).ready(function () {
            initSelect2OnRow('#medicine-rows-container');
            initSelect2OnRow('#edit-medicine-rows-container');

            $('#add-med-row-btn').on('click', function (e) {
                e.preventDefault();
                const tmpl = document.getElementById('medicine-row-template');
                const clone = tmpl.content.cloneNode(true);
                document.getElementById('medicine-rows-container').appendChild(clone);

                const newRow = $('#medicine-rows-container .medicine-row').last()[0];
                initSelect2OnRow(newRow);
                renumberRows('#medicine-rows-container');
            });

            $('#add-edit-med-row-btn').on('click', function (e) {
                e.preventDefault();
                const tmpl = document.getElementById('edit-medicine-row-template');
                const clone = tmpl.content.cloneNode(true);
                document.getElementById('edit-medicine-rows-container').appendChild(clone);

                const newRow = $('#edit-medicine-rows-container .medicine-row').last()[0];
                initSelect2OnRow(newRow);
                renumberRows('#edit-medicine-rows-container');
            });

            $('#medicine-rows-container').on('click', '.remove-med-row', function (e) {
                e.preventDefault();
                if ($('#medicine-rows-container .medicine-row').length <= 1) {
                    alert('At least one medicine row is required.');
                    return;
                }
                const row = $(this).closest('.medicine-row');
                row.find('.searchable-select').each(function () {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });
                row.remove();
                renumberRows('#medicine-rows-container');
            });

            $('.remove-item-btn').on('click', function () {
                const itemId = $(this).data('item-id');
                const prescriptionId = $(this).data('prescription-id');
                const confirmDelete = confirm('Remove this medicine from the prescription?');

                if (!confirmDelete) {
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../doctor_actions.php';

                const fields = {
                    action: 'remove_prescription_item',
                    prescription_id: prescriptionId,
                    item_id: itemId
                };

                Object.entries(fields).forEach(([name, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            });
        });
    </script>
</body>
</html>