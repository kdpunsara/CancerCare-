<?php
session_start();
require_once __DIR__ .  '/../../../config/database.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'benefactor') {
    header("Location: ../../../index.php");
    exit();
}

$benefactor_id = $_SESSION['user_id'];

// ---- Fetch Benefactor Profile for Sidebar ----
$stmt_profile = $conn->prepare("SELECT first_name, last_name, benefactor_type FROM Benefactor WHERE user_id = ?");
$stmt_profile->bind_param("i", $benefactor_id);
$stmt_profile->execute();
$profile = $stmt_profile->get_result()->fetch_assoc();

if (!$profile) {
    $profile = ['first_name' => 'Benefactor', 'last_name' => '', 'benefactor_type' => 'local'];
}
$full_name = $profile['first_name'] . ' ' . $profile['last_name'];
$initials = strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1));

// ---- Fetch Patient Needs ----
// We only show needs that are not yet fully funded
$type_filter = isset($_GET['type']) && in_array($_GET['type'], ['Financial Aid', 'Equipment']) ? $_GET['type'] : '';

$sql = "SELECT pn.need_id, pn.need_type, pn.title, pn.description, pn.target_amount, pn.current_amount,
               p.first_name, p.last_name, p.dob,
               mr.diagnosis AS cancer_type, mr.cancer_stage
        FROM PatientNeed pn
        JOIN Patient p ON pn.patient_user_id = p.user_id
        LEFT JOIN MedicalRecord mr ON mr.patient_user_id = p.user_id
            AND mr.record_id = (SELECT MAX(record_id) FROM MedicalRecord WHERE patient_user_id = p.user_id)
        WHERE pn.current_amount < pn.target_amount";

if ($type_filter !== '') {
    $sql .= " AND pn.need_type = ?";
}

$sql .= " ORDER BY pn.created_at DESC";

$stmt = $conn->prepare($sql);
if ($type_filter !== '') {
    $stmt->bind_param("s", $type_filter);
}
$stmt->execute();
$needs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Helper to calculate age
function calculateAge($dob) {
    if (!$dob) return 'N/A';
    return date_diff(date_create($dob), date_create('today'))->y;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients Needing Support - CancerCare</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
    <style>
        .needs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        .need-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow);
        }
        .need-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .need-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--navy);
            margin: 0 0 4px 0;
        }
        .need-meta {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }
        .need-desc {
            font-size: 14px;
            color: var(--text);
            line-height: 1.5;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        .progress-container {
            margin-bottom: 20px;
        }
        .progress-labels {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .progress-bar-bg {
            height: 8px;
            background: var(--bg);
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: var(--teal);
            border-radius: 4px;
        }
        .need-actions {
            display: flex;
            gap: 12px;
        }
        .filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: #fff;
            color: var(--text);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .filter-btn:hover, .filter-btn.active {
            background: var(--navy);
            color: #fff;
            border-color: var(--navy);
        }
    </style>
</head>
<body>
    <div class="app">
        <?php 
        $current_page = 'patients'; 
        require_once __DIR__ . '/../../../includes/benefactor_sidebar.php'; 
        ?>

        <main class="main">
            <header class="topbar">
                <div class="topbar-left">
                    <div>
                        <h2>Patients Needing Support</h2>
                        <p class="date">Verified requests from patients currently in treatment. Patient names are shared with consent.</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <a href="../../../logout.php" class="signout-btn">Log Out</a>
                </div>
            </header>

            <div class="content">
                
                <!-- Filters -->
                <div class="filter-bar">
                    <a href="patients.php" class="filter-btn <?php echo $type_filter === '' ? 'active' : ''; ?>">All Needs</a>
                    <a href="patients.php?type=Financial+Aid" class="filter-btn <?php echo $type_filter === 'Financial Aid' ? 'active' : ''; ?>">Financial Aid</a>
                    <a href="patients.php?type=Equipment" class="filter-btn <?php echo $type_filter === 'Equipment' ? 'active' : ''; ?>">Equipment / Medication</a>
                </div>

                <!-- Needs Grid -->
                <div class="needs-grid">
                    <?php if (empty($needs)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-muted);">
                            <h3>No active needs found.</h3>
                            <p>All current patient requests have been fully funded. Thank you for your generosity!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($needs as $need): 
                            $percent = min(100, ($need['current_amount'] / $need['target_amount']) * 100);
                            $age = calculateAge($need['dob']);
                            $cancer_info = $need['cancer_type'] ? htmlspecialchars($need['cancer_type']) . ($need['cancer_stage'] ? ', ' . htmlspecialchars($need['cancer_stage']) : '') : 'Oncology Patient';
                        ?>
                            <div class="need-card">
                                <div class="need-header">
                                    <div>
                                        <h3 class="need-title"><?php echo htmlspecialchars($need['first_name'] . ' ' . $need['last_name']); ?>, <?php echo $age; ?></h3>
                                        <p class="need-meta"><?php echo $cancer_info; ?></p>
                                    </div>
                                    <span class="badge <?php echo $need['need_type'] === 'Financial Aid' ? 'badge-active' : 'badge-done'; ?>" style="font-size: 11px; padding: 4px 8px;">
                                        <?php echo htmlspecialchars($need['need_type']); ?>
                                    </span>
                                </div>
                                
                                <p class="need-desc"><?php echo htmlspecialchars($need['description']); ?></p>
                                
                                <div class="progress-container">
                                    <div class="progress-labels">
                                        <span>LKR <?php echo number_format($need['current_amount']); ?> raised</span>
                                        <span style="color: var(--text-muted);">of LKR <?php echo number_format($need['target_amount']); ?></span>
                                    </div>
                                    <div class="progress-bar-bg">
                                        <div class="progress-bar-fill" style="width: <?php echo $percent; ?>%;"></div>
                                    </div>
                                </div>

                                <div class="need-actions">
                                    <a href="make_donation.php?need_id=<?php echo $need['need_id']; ?>" class="btn-primary" style="flex: 1; text-align: center; text-decoration: none; padding: 10px;">Support This Patient</a>
                                    <a href="#" class="btn-secondary" style="text-decoration: none; padding: 10px;">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>
</body>
</html>