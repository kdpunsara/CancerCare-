<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'benefactor') {
    header("Location: ../../../home.html");
    exit();
}

$benefactor_id = $_SESSION['user_id'];

// ---- 1. Fetch Benefactor Profile for Sidebar ----
$stmt_profile = $conn->prepare("SELECT first_name, last_name, benefactor_type FROM Benefactor WHERE user_id = ?");
$stmt_profile->bind_param("i", $benefactor_id);
$stmt_profile->execute();
$profile = $stmt_profile->get_result()->fetch_assoc();

if (!$profile) {
    $profile = ['first_name' => 'Benefactor', 'last_name' => '', 'benefactor_type' => 'local'];
}
$full_name = $profile['first_name'] . ' ' . $profile['last_name'];
$initials = strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1));
$role_display = ucfirst($profile['benefactor_type']) . ' Benefactor';

// ---- 2. Fetch Dashboard Statistics ----
$sql_stats = "SELECT 
                COALESCE(SUM(CASE WHEN donation_type = 'Financial Aid' THEN amount ELSE 0 END), 0) AS total_given,
                COUNT(*) AS donations_made,
                COUNT(DISTINCT patient_user_id) AS patients_supported
              FROM Donation 
              WHERE benefactor_user_id = ?";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $benefactor_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// ---- 3. Fetch Recent Donations (Latest 5) ----
$sql_recent = "SELECT d.donation_id, d.donation_type, d.amount, d.item_name, d.quantity, 
                      d.status, d.created_at, d.bank_slip_path,
                      p.first_name AS p_first, p.last_name AS p_last
               FROM Donation d
               LEFT JOIN Patient p ON d.patient_user_id = p.user_id
               WHERE d.benefactor_user_id = ?
               ORDER BY d.created_at DESC
               LIMIT 5";
$stmt_recent = $conn->prepare($sql_recent);
$stmt_recent->bind_param("i", $benefactor_id);
$stmt_recent->execute();
$recent_donations = $stmt_recent->get_result()->fetch_all(MYSQLI_ASSOC);

// Helper to format status badges
function getDonationBadge($status) {
    switch ($status) {
        case 'Received': return 'badge-active'; // Green/Teal
        case 'Pending Verification': return 'badge-pending'; // Red/Orange
        case 'Awaiting Customs': return 'badge-done'; // Blue
        case 'Rejected': return 'badge-cancelled'; // Grey
        default: return 'badge-pending';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CancerCare Benefactor Portal</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
</head>
<body>
    <div class="app">
        <!-- Include the shared benefactor sidebar -->
        <?php 
        $current_page = 'dashboard'; 
        // Assuming you have a benefactor_sidebar.php similar to the doctor one
        require_once __DIR__ . '/../../../includes/benefactor_sidebar.php'; 
        ?>

        <main class="main">
            <header class="topbar">
                <div class="topbar-left">
                    <div>
                        <h2>Welcome back, <?php echo htmlspecialchars($profile['first_name']); ?></h2>
                        <p class="date">Here's the difference your giving has made so far.</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <a href="../../../logout.php" class="signout-btn">Log Out</a>
                </div>
            </header>

            <div class="content">

                <!-- Call to Action Banner -->
                <div class="card panel" style="background: var(--navy-soft); border: none; color: var(--navy);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                        <div>
                            <h3 style="margin: 0 0 8px 0; color: var(--navy);">Patients are waiting on support you could provide</h3>
                            <p style="margin: 0; font-size: 14px; color: var(--text-muted); max-width: 600px;">
                                Browse verified patient needs and choose who to help next — every request is reviewed by our medical team before it's listed.
                            </p>
                        </div>
                        <a href="patients_needing_support.php" class="btn-primary" style="background: var(--navy); color: #fff; text-decoration: none;">View Patients in Need</a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stat-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="card stat-card">
                        <p class="stat-label">Total Given (LKR)</p>
                        <h3 class="stat-value"><?php echo number_format($stats['total_given']); ?></h3>
                    </div>
                    <div class="card stat-card">
                        <p class="stat-label">Donations Made</p>
                        <h3 class="stat-value"><?php echo intval($stats['donations_made']); ?></h3>
                    </div>
                    <div class="card stat-card">
                        <p class="stat-label">Patients Supported</p>
                        <h3 class="stat-value"><?php echo intval($stats['patients_supported']); ?></h3>
                    </div>
                </div>

                <!-- Quick Actions Grid -->
                <div class="stat-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
                    <a href="make_donation.php" class="card list-card" style="text-decoration: none; color: inherit; transition: transform 0.2s;">
                        <div class="list-card-header"><h3>Make a Donation</h3></div>
                        <p style="padding: 0 20px 20px; font-size: 13.5px; color: var(--text-muted); margin: 0;">Give medication or financial aid, directly or to a specific patient.</p>
                    </a>
                    <a href="patients_needing_support.php" class="card list-card" style="text-decoration: none; color: inherit; transition: transform 0.2s;">
                        <div class="list-card-header"><h3>Patients Needing Support</h3></div>
                        <p style="padding: 0 20px 20px; font-size: 13.5px; color: var(--text-muted); margin: 0;">See verified, current requests for medication and financial aid.</p>
                    </a>
                    <a href="donation_history.php" class="card list-card" style="text-decoration: none; color: inherit; transition: transform 0.2s;">
                        <div class="list-card-header"><h3>My Donation History</h3></div>
                        <p style="padding: 0 20px 20px; font-size: 13.5px; color: var(--text-muted); margin: 0;">Track the status of every donation you've made.</p>
                    </a>
                </div>

                <!-- Recent Donations Table -->
                <div class="card wide-card">
                    <div class="list-card-header">
                        <h3>Your Recent Donations</h3>
                        <a href="donation_history.php" class="link-action">View full history &rarr;</a>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Donation ID</th>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Patient</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_donations)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">
                                            You haven't made any donations yet. 
                                            <a href="make_donation.php" style="color: var(--blue); font-weight: 600;">Make your first donation!</a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_donations as $don): ?>
                                        <tr>
                                            <td class="med-name">DON-<?php echo str_pad($don['donation_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($don['donation_type']); ?></td>
                                            <td>
                                                <?php if ($don['donation_type'] === 'Financial Aid'): ?>
                                                    <strong>LKR <?php echo number_format($don['amount']); ?></strong>
                                                <?php else: ?>
                                                    <strong><?php echo htmlspecialchars($don['item_name']); ?></strong> x<?php echo intval($don['quantity']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $patient_name = ($don['p_first'] && $don['p_last']) 
                                                    ? htmlspecialchars($don['p_first'] . ' ' . $don['p_last']) 
                                                    : 'General Fund'; 
                                                echo $patient_name;
                                                ?>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($don['created_at'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo getDonationBadge($don['status']); ?>">
                                                    <?php echo htmlspecialchars($don['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card panel" style="background: #f8f9fa; border-left: 4px solid var(--teal);">
                    <p class="panel-note" style="color: var(--text); font-size: 13.5px;">
                        <strong>Trust & Transparency:</strong> Every patient listed under "Patients Needing Support" has been verified by CancerCare's medical team, so you can give with confidence.
                    </p>
                </div>

            </div>
        </main>
    </div>
</body>
</html>