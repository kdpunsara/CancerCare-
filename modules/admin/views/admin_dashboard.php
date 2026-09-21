<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../index.php");
    exit();
}

// ---- Live Stats ----
$registered_users = intval($conn->query("SELECT COUNT(*) AS c FROM User")->fetch_assoc()['c']);

$stmt_rep = $conn->prepare("SELECT COUNT(*) AS c FROM MedicalReport
                            WHERE MONTH(report_date) = MONTH(CURDATE())
                              AND YEAR(report_date) = YEAR(CURDATE())");
$stmt_rep->execute();
$monthly_reports = intval($stmt_rep->get_result()->fetch_assoc()['c']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CancerCare</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
</head>
<body>
    <div class="app">
        <?php $current_page = 'dashboard'; require_once __DIR__ . '/../../../includes/admin_sidebar.php'; ?>

        <main class="main">
            <header class="topbar">
                <div class="topbar-left">
                    <div>
                        <h2>Admin Control Center</h2>
                        <p class="date">Overview of system functions, operations, and analytical reports</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <a href="../../../logout.php" class="signout-btn">Sign Out</a>
                </div>
            </header>

            <div class="content">

                <!-- Statistics -->
                <div class="stat-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="card stat-card">
                        <p class="stat-label">Registered Users</p>
                        <h3 class="stat-value"><?php echo number_format($registered_users); ?></h3>
                    </div>
                    <div class="card stat-card">
                        <p class="stat-label">Monthly Reports</p>
                        <h3 class="stat-value"><?php echo number_format($monthly_reports); ?></h3>
                    </div>
                </div>

                <!-- Quick Administrative Actions -->
                <div class="card list-card">
                    <div class="list-card-header">
                        <h3>Quick Administrative Actions</h3>
                    </div>
                    <div class="appointment-list">
                        <a class="appointment-item" href="user_management.php">
                            <div class="appointment-info">
                                <p class="appointment-name">Manage System Users</p>
                                <p class="appointment-desc">Create, suspend or update accounts for all system actors</p>
                            </div>
                            <span class="link-action">Open</span>
                        </a>
                        <a class="appointment-item" href="operations.php">
                            <div class="appointment-info">
                                <p class="appointment-name">Monitor Operations</p>
                                <p class="appointment-desc">Track transport schedules, donations and pharmacy activity</p>
                            </div>
                            <span class="link-action">Open</span>
                        </a>
                        <a class="appointment-item" href="analytics.php">
                            <div class="appointment-info">
                                <p class="appointment-name">Generate Analytics</p>
                                <p class="appointment-desc">View usage, medical report and donation analytics</p>
                            </div>
                            <span class="link-action">Open</span>
                        </a>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>