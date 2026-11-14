<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../../../index.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];

// ---- My patients (latest record) + meal plan status ----
$sql_patients = "SELECT p.user_id AS patient_user_id, p.first_name, p.last_name,
        mr.cancer_stage,
        COALESCE(
            (SELECT r.cancer_type FROM MedicalReport r
              WHERE r.patient_user_id = p.user_id
              ORDER BY r.report_date DESC, r.report_id DESC LIMIT 1),
            mr.diagnosis
        ) AS cancer_type,
        (SELECT COUNT(*) FROM mealplan mp
          WHERE mp.patient_user_id = p.user_id AND mp.plan_date >= CURDATE()) AS active_meal_count
     FROM Patient p
     JOIN MedicalRecord mr ON mr.record_id = (
         SELECT MAX(m2.record_id) FROM MedicalRecord m2
         WHERE m2.patient_user_id = p.user_id AND m2.doctor_user_id = ?
     )
     ORDER BY p.first_name ASC";
$stmt = $conn->prepare($sql_patients);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ---- Active pre-made templates ----
$sql_tpl = "SELECT t.template_id, t.cancer_type, t.template_name, t.nutritional_guidelines, t.duration_days,
        (SELECT COUNT(DISTINCT mp.patient_user_id) FROM mealplan mp WHERE mp.template_id = t.template_id) AS patients_using,
        (SELECT COUNT(DISTINCT i.meal_type) FROM mealplantemplateitem i WHERE i.template_id = t.template_id) AS meals_per_day
     FROM mealplantemplate t
     WHERE t.is_active = 1
     ORDER BY t.template_id ASC";
$templates = $conn->query($sql_tpl)->fetch_all(MYSQLI_ASSOC);

$template_items = [];
$template_item_result = $conn->query("SELECT template_id, day_of_week, meal_type, menu_description
                                      FROM mealplantemplateitem
                                      ORDER BY template_id,
                                               FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
                                               FIELD(meal_type,'Breakfast','Lunch','Snack','Dinner')");
while ($item = $template_item_result->fetch_assoc()) {
    $template_items[$item['template_id']][] = $item;
}

// ---- Weekly Plan modal (view_plan) ----
$view_patient = null; $plan_days = []; $plan_meta = null; $plan_doctor_name = ''; $plan_template_name = 'Custom Plan';
if (isset($_GET['view_plan'])) {
    $vp = intval($_GET['view_plan']);
    foreach ($patients as $pt) {
        if ($pt['patient_user_id'] === $vp) $view_patient = $pt;
    }
    if ($view_patient) {
        $stmt_m = $conn->prepare("SELECT plan_date, meal_type, menu_description, dietary_restrictions, nutritional_notes, template_id, doctor_user_id
                                  FROM mealplan
                                  WHERE patient_user_id = ? AND plan_date >= CURDATE()
                                  ORDER BY plan_date ASC, FIELD(meal_type,'Breakfast','Lunch','Snack','Dinner')");
        $stmt_m->bind_param("i", $vp);
        $stmt_m->execute();
        $meals = $stmt_m->get_result()->fetch_all(MYSQLI_ASSOC);

        $by_date = [];
        foreach ($meals as $m) $by_date[$m['plan_date']][] = $m;
        foreach ($by_date as $date => $list) {
            if (count($plan_days) >= 7) break;
            $plan_days[] = ['label' => date('l', strtotime($date)), 'date' => $date, 'meals' => $list];
        }
        if (!empty($meals)) {
            $plan_meta = $meals[0];
            $stmt_d = $conn->prepare("SELECT first_name, last_name FROM Doctor WHERE user_id = ?");
            $stmt_d->bind_param("i", $plan_meta['doctor_user_id']);
            $stmt_d->execute();
            $doc = $stmt_d->get_result()->fetch_assoc();
            $plan_doctor_name = $doc ? 'Dr. ' . $doc['first_name'] . ' ' . $doc['last_name'] : 'Doctor';
            if ($plan_meta['template_id']) {
                $stmt_tn = $conn->prepare("SELECT template_name FROM mealplantemplate WHERE template_id = ?");
                $stmt_tn->bind_param("i", $plan_meta['template_id']);
                $stmt_tn->execute();
                $tn = $stmt_tn->get_result()->fetch_assoc();
                if ($tn) $plan_template_name = $tn['template_name'];
            }
        }
    }
}

// ---- Template Preview modal (preview_template) ----
$preview_template = null; $preview_days = [];
if (isset($_GET['preview_template'])) {
    $tp = intval($_GET['preview_template']);
    $stmt_t = $conn->prepare("SELECT * FROM mealplantemplate WHERE template_id = ?");
    $stmt_t->bind_param("i", $tp);
    $stmt_t->execute();
    $preview_template = $stmt_t->get_result()->fetch_assoc();
    if ($preview_template) {
        $stmt_i = $conn->prepare("SELECT day_of_week, meal_type, menu_description
                                  FROM mealplantemplateitem
                                  WHERE template_id = ?
                                  ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
                                           FIELD(meal_type,'Breakfast','Lunch','Snack','Dinner')");
        $stmt_i->bind_param("i", $tp);
        $stmt_i->execute();
        foreach ($stmt_i->get_result()->fetch_all(MYSQLI_ASSOC) as $it) {
            $preview_days[$it['day_of_week']][] = $it;
        }
    }
}

// ---- Assign modal state ----
$assign_open       = isset($_GET['assign']) || isset($_GET['new']);
$assign_patient_id = intval($_GET['assign'] ?? 0);
$assign_template_id = intval($_GET['template'] ?? 0);
$is_edit           = isset($_GET['edit']);
$default_type      = (isset($_GET['custom']) && $assign_template_id === 0) ? 'custom' : 'template';

$palette = ['#4f6ef7', '#7c3aed', '#d97706', '#0d9488', '#059669', '#101a33'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meal Plans - Cancer Care</title>
<link rel="stylesheet" href="../../../public/css/base.css">
<style>
/* Custom badges for meal types */
.meal-type-badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 8px; }
.meal-type-badge.breakfast { background: #fdf1de; color: #d97706; }
.meal-type-badge.lunch { background: #e8edfe; color: var(--blue-dark); }
.meal-type-badge.dinner { background: #f3e8ff; color: #7c3aed; }
.meal-type-badge.snack { background: #e2f7f3; color: #0d9488; }
/* Day Card */
.day-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 18px; }
.day-card h4 { font-size: 14.5px; font-weight: 700; margin: 0 0 14px; color: var(--text); border-bottom: 1px solid var(--border); padding-bottom: 10px; }
.meal-item { margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px dashed var(--border); }
.meal-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.meal-item p { font-size: 13px; color: var(--text-muted); margin: 0; line-height: 1.4; }
/* Cancer Template Cards */
.cancer-type-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 22px; transition: all 0.2s ease; cursor: pointer; height: 100%; }
.cancer-type-card:hover { border-color: var(--blue); box-shadow: 0 4px 12px rgba(79, 110, 247, 0.08); transform: translateY(-2px); }
.cancer-type-card h4 { font-size: 15px; font-weight: 700; margin: 0 0 8px; color: var(--text); }
.cancer-type-card p { font-size: 13px; color: var(--text-muted); margin: 0 0 16px; line-height: 1.5; }
.cancer-type-meta { display: flex; flex-wrap: wrap; gap: 12px; font-size: 12px; color: var(--text-faint); font-weight: 600; }
/* Modal Styles */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(16, 26, 51, 0.6); display: none; align-items: center; justify-content: center; z-index: 1000; padding: 20px; backdrop-filter: blur(3px); }
.modal-overlay.active { display: flex; }
.modal { background: var(--card); border-radius: var(--radius-lg); width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; padding: 30px 34px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
.modal-large { max-width: 960px; }
.weekly-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 20px; }
.radio-group { display: flex; gap: 16px; margin-top: 6px; }
.radio-group label { display: flex; align-items: center; gap: 6px; font-weight: 500 !important; font-size: 13px !important; cursor: pointer; }
@media (max-width: 1024px) { .weekly-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .weekly-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="app">
    <?php $current_page = 'mealplan'; require_once __DIR__ . '/../../../includes/doctor_sidebar.php'; ?>

    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <div>
                    <h2>Meal Plans</h2>
                    
                </div>
            </div>
            <div class="topbar-actions">
                <a href="../../../index.php?logout=1" class="signout-btn">Sign Out</a>
            </div>
        </header>

        <section class="content">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'plan_assigned'): ?>
                <div class="card panel" style="border-left: 4px solid var(--teal); margin-bottom: 20px;">
                    <p class="panel-note" style="color: var(--teal);">Meal plan saved successfully.</p>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="card panel" style="border-left: 4px solid var(--red); margin-bottom: 20px;">
                    <p class="panel-note" style="color: var(--red);">Could not save the meal plan. Please select a patient and at least one meal or template.</p>
                </div>
            <?php endif; ?>

            <!-- Search Patients Section -->
            <div class="card form-card" style="padding-bottom: 24px;">
                <div class="form-grid" style="align-items: end;">
                    <div class="form-field">
                        <label>Search by Patient Name or ID</label>
                        <input type="text" id="patientSearch" placeholder="e.g. Kamal Perera or P-1001">
                    </div>
                    <div class="form-field">
                        <label>Filter by Cancer Type</label>
                        <select id="cancerFilter">
                            <option value="">All Cancer Types</option>
                            <option value="colorectal">Colorectal Cancer</option>
                            <option value="breast">Breast Cancer</option>
                            <option value="lung">Lung Cancer</option>
                            <option value="prostate">Prostate Cancer</option>
                            <option value="lymphoma">Lymphoma</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Patient Meal Plans List -->
            <div class="card list-card wide-card">
                <div class="list-card-header">
                    <h3>Patient Meal Plans (<span id="patientCount"><?php echo count($patients); ?></span> patients)</h3>
                    <a href="doctor_mealplan.php?new=1" class="btn-primary" style="padding: 8px 16px; font-size: 13px; text-decoration: none;">+ Assign Plan</a>
                </div>
                <ul class="appointment-list" id="patientMealList">
                    <?php if (empty($patients)): ?>
                        <li style="text-align: center; padding: 24px; color: var(--text-muted);">No patients found under your care.</li>
                    <?php else: ?>
                        <?php foreach ($patients as $i => $pt):
                            $has_plan = intval($pt['active_meal_count']) > 0;
                            $initials = strtoupper(substr($pt['first_name'], 0, 1) . substr($pt['last_name'], 0, 1));
                            $color = $palette[$i % count($palette)];
                            $search_attr = strtolower($pt['first_name'] . ' ' . $pt['last_name'] . ' p-' . $pt['patient_user_id'] . ' ' . $pt['cancer_type']);
                        ?>
                            <li class="appointment-item <?php echo $has_plan ? '' : 'pending'; ?>"
                                data-search="<?php echo htmlspecialchars($search_attr); ?>"
                                <?php echo $has_plan ? 'style="border-left-color: var(--teal);"' : ''; ?>>
                                <div class="appointment-time">
                                    <div class="avatar" style="background: <?php echo $color; ?>;"><?php echo $initials; ?></div>
                                </div>
                                <div class="appointment-info">
                                    <p class="appointment-name"><?php echo htmlspecialchars($pt['first_name'] . ' ' . $pt['last_name']); ?></p>
                                    <p class="appointment-desc">P-<?php echo $pt['patient_user_id']; ?> &bull; <?php echo htmlspecialchars($pt['cancer_type']); ?> &bull; <?php echo htmlspecialchars($pt['cancer_stage']); ?></p>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px; margin-left: auto;">
                                    <?php if ($has_plan): ?>
                                        <span class="badge badge-active">Active Plan</span>
                                        <a href="doctor_mealplan.php?view_plan=<?php echo $pt['patient_user_id']; ?>" class="btn-secondary" style="padding: 6px 12px; font-size: 12px; text-decoration: none;">View Plan</a>
                                        <a href="doctor_mealplan.php?assign=<?php echo $pt['patient_user_id']; ?>&edit=1" class="btn-primary" style="padding: 6px 12px; font-size: 12px; text-decoration: none;">Edit</a>
                                    <?php else: ?>
                                        <span class="badge badge-pending">Pending Assignment</span>
                                        <a href="doctor_mealplan.php?assign=<?php echo $pt['patient_user_id']; ?>" class="btn-primary" style="padding: 6px 12px; font-size: 12px; text-decoration: none;">Assign Plan</a>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Pre-made Meal Plan Templates -->
            <div class="card list-card wide-card">
                <div class="list-card-header" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                    <h3>Pre-made Meal Plan Templates</h3>
                    <p class="table-caption" style="margin:0;">Select a template based on cancer type to quickly assign to patients</p>
                </div>
                <div class="form-grid">
                    <?php foreach ($templates as $t): ?>
                        <a class="cancer-type-card" href="doctor_mealplan.php?preview_template=<?php echo $t['template_id']; ?>" style="display: block; text-decoration: none; color: inherit;">
                            <h4><?php echo htmlspecialchars($t['template_name']); ?></h4>
                            <p><?php echo htmlspecialchars($t['nutritional_guidelines']); ?></p>
                            <div class="cancer-type-meta">
                                <span> <?php echo intval($t['duration_days']); ?>-day plan</span>
                                <span> <?php echo intval($t['meals_per_day']); ?> meals/day</span>
                                <span> <?php echo intval($t['patients_using']); ?> patients using</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- ========================================== -->
<!-- VIEW WEEKLY PLAN MODAL                     -->
<!-- ========================================== -->
<div class="modal-overlay <?php echo $view_patient ? 'active' : ''; ?>" id="weeklyModal">
    <div class="modal modal-large">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h2 class="form-title">Weekly Meal Plan &mdash; <?php echo htmlspecialchars($view_patient['first_name'] . ' ' . $view_patient['last_name']); ?></h2>
                <p class="form-subtitle" style="margin-bottom: 0;">Prepared by <?php echo htmlspecialchars($plan_doctor_name); ?> &bull; <?php echo htmlspecialchars($plan_template_name); ?></p>
            </div>
            <a href="doctor_mealplan.php" style="font-size: 24px; color: var(--text-faint); background: none; border: none; cursor: pointer; padding: 0; text-decoration: none;">&times;</a>
        </div>

        <?php if ($plan_meta && $plan_meta['dietary_restrictions']): ?>
            <div class="card panel" style="background: #fafbfd; margin-top: 16px;">
                <p class="panel-note" style="margin: 0;"><strong>Dietary Restrictions:</strong> <?php echo htmlspecialchars($plan_meta['dietary_restrictions']); ?></p>
            </div>
        <?php endif; ?>

        <div class="weekly-grid">
            <?php if (empty($plan_days)): ?>
                <p style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 20px;">No upcoming meals scheduled for this patient.</p>
            <?php else: ?>
                <?php foreach ($plan_days as $day): ?>
                    <div class="day-card">
                        <h4><?php echo $day['label']; ?> <span style="font-weight: 500; color: var(--text-faint); font-size: 12px;">(<?php echo date('M d', strtotime($day['date'])); ?>)</span></h4>
                        <?php foreach ($day['meals'] as $m): ?>
                            <div class="meal-item">
                                <span class="meal-type-badge <?php echo strtolower($m['meal_type']); ?>"><?php echo htmlspecialchars($m['meal_type']); ?></span>
                                <p><?php echo htmlspecialchars($m['menu_description']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <a href="doctor_mealplan.php" class="btn-secondary" style="text-decoration: none;">Close</a>
            <?php if ($view_patient): ?>
                <a href="doctor_mealplan.php?assign=<?php echo $view_patient['patient_user_id']; ?>&edit=1" class="btn-primary" style="text-decoration: none;">Edit This Plan</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- ASSIGN MEAL PLAN MODAL                     -->
<!-- ========================================== -->
<div class="modal-overlay <?php echo $assign_open ? 'active' : ''; ?>" id="assignModal">
    <div class="modal">
        <h2 class="form-title"><?php echo $is_edit ? 'Edit Meal Plan' : 'Assign Meal Plan'; ?></h2>
        <p class="form-subtitle">
            <?php echo $is_edit
                ? 'Saving will replace this patient\'s current and future scheduled meals.'
                : 'Select a patient and choose a pre-made template or create a custom plan'; ?>
        </p>

        <form method="POST" action="../doctor_actions.php">
            <input type="hidden" name="action" value="assign_meal_plan">

            <div class="form-field" style="margin-bottom: 18px;">
                <label for="patient_user_id">
                    Search Patient by ID <span style="color:var(--red);">*</span>
                </label>

                <input
                    type="text"
                    id="patient_user_id"
                    name="patient_user_id"
                    list="patient-list"
                    placeholder="Enter patient ID, e.g. 123 or P-123"
                    value="<?php echo htmlspecialchars($assign_patient_id ?? ''); ?>"
                    required
                >

                <datalist id="patient-list">
                    <?php foreach ($patients as $pt): ?>
                        <option value="<?php echo htmlspecialchars($pt['patient_user_id']); ?>">
                            <?php echo htmlspecialchars(
                                $pt['first_name'] . ' ' . $pt['last_name'] .
                                ' (P-' . $pt['patient_user_id'] . ') - ' .
                                $pt['cancer_type'] . ', ' . $pt['cancer_stage']
                            ); ?>
                        </option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-field" style="margin-bottom: 18px;">
                <label>Choose Plan Type <span style="color:var(--red);">*</span></label>
                <div class="radio-group">
                    <label><input type="radio" name="plan_type" value="template" onchange="togglePlanType()" <?php echo $default_type === 'template' ? 'checked' : ''; ?>> Use Pre-made Template</label>
                    <label><input type="radio" name="plan_type" value="custom" onchange="togglePlanType()" <?php echo $default_type === 'custom' ? 'checked' : ''; ?>> Create Custom Plan</label>
                </div>
            </div>

            <div class="form-field" style="margin-bottom: 18px;" id="templateField">
                <label>Select Template <span style="color:var(--red);">*</span></label>
                <select name="template_id" id="templateSelect">
                    <option value="">-- Choose Cancer Type Template --</option>
                    <?php foreach ($templates as $t): ?>
                        <option value="<?php echo $t['template_id']; ?>" <?php echo $assign_template_id === $t['template_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['template_name']); ?> (<?php echo intval($t['duration_days']); ?> days)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="templateMealFields" style="display: none; margin-bottom: 18px;">
                <label>Edit Template Meals for This Patient</label>
                <p style="font-size: 12px; color: var(--text-muted); margin: 6px 0 12px;">
                    These changes apply only to this patient's meal plan.
                </p>
                <div id="templateMealInputs"></div>
            </div>

            <div id="customFields" style="display: none;">
                <div class="form-field" style="margin-bottom: 14px;">
                    <label>Breakfast Menu</label>
                    <input type="text" name="custom_breakfast" placeholder="e.g. Kola kanda with a boiled egg">
                </div>
                <div class="form-field" style="margin-bottom: 14px;">
                    <label>Lunch Menu</label>
                    <input type="text" name="custom_lunch" placeholder="e.g. Red rice, dhal curry, steamed greens">
                </div>
                <div class="form-field" style="margin-bottom: 14px;">
                    <label>Snack Menu</label>
                    <input type="text" name="custom_snack" placeholder="e.g. Papaya slices">
                </div>
                <div class="form-field" style="margin-bottom: 14px;">
                    <label>Dinner Menu</label>
                    <input type="text" name="custom_dinner" placeholder="e.g. Vegetable soup with grilled fish">
                </div>
                <div class="form-field" style="margin-bottom: 18px;">
                    <label>Nutritional Notes</label>
                    <input type="text" name="nutritional_notes" placeholder="e.g. High-protein, low-sodium">
                </div>
            </div>

            <div class="form-field" style="margin-bottom: 18px;">
                <label>Dietary Restrictions / Notes</label>
                <textarea name="dietary_restrictions" rows="3" placeholder="e.g. Patient has diabetes - limit sugar intake. Avoid spicy foods due to mouth sores."></textarea>
            </div>

            <div class="form-field">
                <label>Plan Duration</label>
                <select name="duration_weeks">
                    <option value="1">1 Week (7 days)</option>
                    <option value="2">2 Weeks (14 days)</option>
                    <option value="4">4 Weeks (28 days)</option>
                </select>
            </div>

            <div class="form-actions">
                <a href="doctor_mealplan.php" class="btn-secondary" style="text-decoration: none;">Cancel</a>
                <button type="submit" class="btn-primary">Assign Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- TEMPLATE PREVIEW MODAL                     -->
<!-- ========================================== -->
<div class="modal-overlay <?php echo $preview_template ? 'active' : ''; ?>" id="templateModal">
    <div class="modal modal-large">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h2 class="form-title"><?php echo htmlspecialchars($preview_template['template_name'] ?? ''); ?></h2>
                <p class="form-subtitle" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($preview_template['cancer_type'] ?? ''); ?> &bull;
                    <?php echo intval($preview_template['duration_days'] ?? 0); ?> days &bull;
                    <?php echo intval($preview_template['meals_per_day'] ?? 4); ?> meals per day
                </p>
            </div>
            <a href="doctor_mealplan.php" style="font-size: 24px; color: var(--text-faint); background: none; border: none; cursor: pointer; padding: 0; text-decoration: none;">&times;</a>
        </div>

        <?php if ($preview_template): ?>
            <div class="card panel" style="background: #fafbfd; margin-bottom: 20px;">
                <h4 style="margin: 0 0 10px; font-size: 14.5px;">Nutritional Guidelines</h4>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.8; margin: 0;"><?php echo htmlspecialchars($preview_template['nutritional_guidelines']); ?></p>
            </div>
        <?php endif; ?>

        <div class="weekly-grid">
            <?php foreach ($preview_days as $day_name => $items): ?>
                <div class="day-card">
                    <h4><?php echo htmlspecialchars($day_name); ?></h4>
                    <?php foreach ($items as $it): ?>
                        <div class="meal-item">
                            <span class="meal-type-badge <?php echo strtolower($it['meal_type']); ?>"><?php echo htmlspecialchars($it['meal_type']); ?></span>
                            <p><?php echo htmlspecialchars($it['menu_description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <a href="doctor_mealplan.php" class="btn-secondary" style="text-decoration: none;">Close</a>
            <a href="doctor_mealplan.php?new=1&template=<?php echo intval($preview_template['template_id'] ?? 0); ?>" class="btn-primary" style="text-decoration: none;">Use This Template</a>
        </div>
    </div>
</div>

<script>
// Patient search
document.getElementById('patientSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#patientMealList .appointment-item');
    let count = 0;
    rows.forEach(row => {
        const data = row.getAttribute('data-search');
        if (data && data.includes(searchTerm)) { row.style.display = 'flex'; count++; }
        else { row.style.display = 'none'; }
    });
    document.getElementById('patientCount').textContent = count;
});
// Cancer type filter
document.getElementById('cancerFilter').addEventListener('change', function(e) {
    const filter = e.target.value;
    const rows = document.querySelectorAll('#patientMealList .appointment-item');
    let count = 0;
    rows.forEach(row => {
        const data = row.getAttribute('data-search');
        if (data && (!filter || data.includes(filter))) { row.style.display = 'flex'; count++; }
        else { row.style.display = 'none'; }
    });
    document.getElementById('patientCount').textContent = count;
});
// Template vs Custom fields toggle
const templateItems = <?php echo json_encode($template_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

function renderTemplateMeals() {
    const templateId = document.getElementById('templateSelect').value;
    const container = document.getElementById('templateMealInputs');
    const fields = document.getElementById('templateMealFields');
    const items = templateItems[templateId] || [];

    container.innerHTML = '';
    fields.style.display = items.length ? 'block' : 'none';

    items.forEach(item => {
        const field = document.createElement('div');
        field.className = 'form-field';
        field.style.marginBottom = '12px';
        const label = document.createElement('label');
        label.textContent = `${item.day_of_week} - ${item.meal_type}`;
        const textarea = document.createElement('textarea');
        textarea.name = `template_meals[${item.day_of_week}][${item.meal_type}]`;
        textarea.rows = 2;
        textarea.value = item.menu_description;
        field.append(label, textarea);
        container.appendChild(field);
    });
}

function togglePlanType() {
    const type = document.querySelector('input[name="plan_type"]:checked').value;
    document.getElementById('templateField').style.display = (type === 'template') ? 'block' : 'none';
    document.getElementById('customFields').style.display = (type === 'custom') ? 'block' : 'none';
    if (type === 'template') renderTemplateMeals();
    else document.getElementById('templateMealFields').style.display = 'none';
}
// Modal close on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.classList.remove('active');
    });
});
document.addEventListener('DOMContentLoaded', togglePlanType);
document.getElementById('templateSelect').addEventListener('change', renderTemplateMeals);
</script>
</body>
</html>