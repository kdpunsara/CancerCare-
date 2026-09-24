<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!$conn) {
    die("Database connection failed.");
}

$donation_requests = [];
$pending_donations = 0;
$received_donations = 0;
$page_error = '';

try {
    $stats_result = $conn->query(
        "SELECT
            COUNT(CASE WHEN status = 'Pending Verification' THEN 1 END) AS pending_count,
            COUNT(CASE WHEN status = 'Received' THEN 1 END) AS received_count
         FROM Donation"
    );
    $donation_stats = $stats_result->fetch_assoc();
    $pending_donations = (int)($donation_stats['pending_count'] ?? 0);
    $received_donations = (int)($donation_stats['received_count'] ?? 0);

    $requests_sql = "SELECT
                        d.donation_id, d.donation_type, d.amount, d.currency,
                        d.item_name, d.quantity, d.status, d.bank_slip_path,
                        d.notes, d.created_at, d.received_date,
                        b.first_name AS benefactor_first_name,
                        b.last_name AS benefactor_last_name,
                        b.organization_name,
                        p.first_name AS patient_first_name,
                        p.last_name AS patient_last_name,
                        pn.title AS need_title
                     FROM Donation d
                     INNER JOIN Benefactor b ON b.user_id = d.benefactor_user_id
                     LEFT JOIN Patient p ON p.user_id = d.patient_user_id
                     LEFT JOIN PatientNeed pn ON pn.need_id = d.need_id
                     ORDER BY
                        CASE WHEN d.status = 'Pending Verification' THEN 0 ELSE 1 END,
                        d.created_at DESC";
    $requests_result = $conn->query($requests_sql);
    $donation_requests = $requests_result->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $ex) {
    $page_error = $ex->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Benefactor Support — Cancer Care</title>

    <link rel="stylesheet" href="../../../public/css/styles.css">
    <link rel="stylesheet" href="../../../public/css/modules.css">
    <style>
        /* =====================================================
           SIDEBAR SVG ICONS
           Same style as Medical Reports / Doctor Availability
           No library / no framework
        ===================================================== */

        .icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            flex-shrink: 0;
        }

        .nav-item .icon {
            width: 18px;
            height: 18px;
        }


        /* =====================================================
           DASHBOARD
        ===================================================== */

        .icon-dashboard {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='3' y='3' width='7' height='9' rx='1.5'/><rect x='14' y='3' width='7' height='5' rx='1.5'/><rect x='14' y='12' width='7' height='9' rx='1.5'/><rect x='3' y='16' width='7' height='5' rx='1.5'/></svg>");
        }


        /* =====================================================
           PATIENTS
        ===================================================== */

        .icon-profile {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='8' r='4'/><path d='M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7'/></svg>");
        }


        /* =====================================================
           REGISTER PATIENT
        ===================================================== */

        .icon-register {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M15 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'/><circle cx='8.5' cy='7' r='4'/><path d='M20 8v6M17 11h6'/></svg>");
        }


        /* =====================================================
           APPOINTMENTS
        ===================================================== */

        .icon-appointments {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='3' y='5' width='18' height='16' rx='2'/><path d='M16 3v4M8 3v4M3 10h18'/></svg>");
        }


        /* =====================================================
           MEDICAL REPORTS
        ===================================================== */

        .icon-records {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M7 3h7l4 4v14H7z'/><path d='M9 12h6M9 16h6M9 8h2'/></svg>");
        }


        


        /* =====================================================
           DOCTOR AVAILABILITY
        ===================================================== */

        .icon-doctor {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='7' r='4'/><path d='M5 21v-2a7 7 0 0 1 14 0v2'/><path d='M17 16l2 2 4-4'/></svg>");
        }


        /* =====================================================
           BENEFACTOR
        ===================================================== */

        .icon-benefactor {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='9' cy='7' r='4'/><path d='M2 21v-2a7 7 0 0 1 14 0v2'/><path d='M16 11h6M19 8v6'/></svg>");
        }
    </style>
</head>

<body>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-header">

            <div class="logo">

                <div class="logo-icon">❤</div>

                <div>
                    Cancer care
                    <span class="logo-sub">Cancer Patient Care</span>
                </div>

            </div>

        </div>


        <nav class="sidebar-nav">

            <div class="nav-section">Navigation</div>


            <!-- DASHBOARD -->
<a href="staff_dashboard.php" class="nav-item">
    <span class="icon icon-dashboard"></span> Dashboard
</a>

<!-- PATIENTS -->
<a href="staff_patient.php" class="nav-item">
    <span class="icon icon-profile"></span> Patients
</a>

<!-- REGISTER PATIENT -->
<a href="Register_patient.php" class="nav-item">
    <span class="icon icon-register"></span> Register Patient
</a>

<!-- APPOINTMENTS -->
<a href="staff_appointment.php" class="nav-item">
    <span class="icon icon-appointments"></span> Appointments
</a>

<!-- MEDICAL REPORTS -->
<a href="medical_reports.php" class="nav-item">
    <span class="icon icon-records"></span> Medical Reports
</a>

<!-- DOCTOR AVAILABILITY -->
<a href="doctor_availability.php" class="nav-item">
    <span class="icon icon-doctor"></span> Doctor Availability
</a>



<!-- BENEFACTOR -->
<a href="benefactor.php" class="nav-item">
    <span class="icon icon-benefactor"></span> Benefactor
</a>
<a href="staff_profile.php" class="nav-item">
    <span class="icon icon-profile"></span> My Profile
</a>

        </nav>


        <div class="sidebar-footer">

            <div class="user-card">

                <div class="user-avatar">
                    NP
                </div>

                <div class="user-info">

                    <strong>Nimali Perera</strong>

                    <span>
                        Medical Staff
                    </span>

                </div>

            </div>

        </div>

    </aside>


    <!-- MAIN -->
    <main class="main">

        <!-- TOPBAR -->
        <header class="topbar">

            <div class="topbar-left">

                <h1>Benefactor Support</h1>

                <p>
                    Financial and essential support for cancer patients
                </p>

            </div>


            <div class="topbar-right">

                <div class="search-box">

                    <span>🔍</span>

                    <input
                        type="text"
                        placeholder="Search patients..."
                    >

                </div>

                <a href="../../../logout.php" class="btn btn-outline">
                    Sign Out
                </a>

                <button
                    class="sidebar-toggle"
                    id="sidebarToggle"
                >
                    ☰
                </button>

            </div>

        </header>


        <!-- CONTENT -->
        <div class="content">

            <?php if (($_GET['msg'] ?? '') === 'donation_updated'): ?>
                <div class="message">Donation request status updated successfully.</div>
            <?php elseif (($_GET['msg'] ?? '') === 'error'): ?>
                <div class="error-message"><?php echo htmlspecialchars($_GET['reason'] ?? 'Unable to update donation request.'); ?></div>
            <?php elseif ($page_error !== ''): ?>
                <div class="error-message">Unable to load benefactor requests.</div>
            <?php endif; ?>


            <!-- SUMMARY -->
            <div class="kpi-grid">

                <div class="kpi-card">

                    <div class="kpi-icon red">
                        💰
                    </div>

                    <div class="kpi-body">

                        <div class="kpi-label">
                            Financial Support Needed
                        </div>

                        <div class="kpi-value">
                            3
                        </div>

                        <div class="kpi-change down">
                            Patients requiring support
                        </div>

                    </div>

                </div>


                <div class="kpi-card">

                    <div class="kpi-icon orange">
                        💊
                    </div>

                    <div class="kpi-body">

                        <div class="kpi-label">
                            Medicine Requests
                        </div>

                        <div class="kpi-value">
                            2
                        </div>

                        <div class="kpi-change down">
                            Waiting for support
                        </div>

                    </div>

                </div>


                <div class="kpi-card">

                    <div class="kpi-icon teal">
                        🤝
                    </div>

                    <div class="kpi-body">

                        <div class="kpi-label">
                            Benefactor Requests
                        </div>

                        <div class="kpi-value">
                            <?php echo $pending_donations; ?>
                        </div>

                        <div class="kpi-change up">
                            Active requests
                        </div>

                    </div>

                </div>


                <div class="kpi-card">

                    <div class="kpi-icon blue">
                        ✅
                    </div>

                    <div class="kpi-body">

                        <div class="kpi-label">
                            Confirmed Support
                        </div>

                        <div class="kpi-value">
                            <?php echo $received_donations; ?>
                        </div>

                        <div class="kpi-change up">
                            Successfully supported
                        </div>

                    </div>

                </div>

            </div>


            <!-- PATIENT NEEDS -->
            <div class="widget">

                <div class="widget-header">

                    <div>

                        <h3>
                            Patient Support Requirements
                        </h3>

                        <p style="font-size:0.82rem; color:var(--gray-500);">
                            Patients who require financial or essential item assistance
                        </p>

                    </div>


                    <div
                        class="page-toolbar"
                        style="margin-bottom:0;"
                    >

                        <select class="form-input filter-select">

                            <option>
                                All Support Types
                            </option>

                            <option>
                                Financial
                            </option>

                            <option>
                                Medicine
                            </option>

                            <option>
                                Food
                            </option>

                            <option>
                                Transport
                            </option>

                        </select>

                    </div>

                </div>


                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>Patient</th>
                                <th>Requirement</th>
                                <th>Reason</th>
                                <th>Estimated Need</th>
                                <th>Priority</th>
                                <th>Request</th>

                            </tr>

                        </thead>


                        <tbody>


                            <!-- PATIENT 1 -->

                            <tr class="row-warning">

                                <td>

                                    <div class="patient-cell">

                                        <div class="patient-avatar">
                                            SP
                                        </div>

                                        <div>

                                            <strong>
                                                Sandun Jayasekara
                                            </strong>

                                            <span>
                                                Patient ID: P001
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <td>
                                    Chemotherapy Cost
                                </td>


                                <td>
                                    Financial difficulty
                                </td>


                                <td>
                                    LKR 85,000
                                </td>


                                <td>

                                    <span class="status-pill critical">
                                        High
                                    </span>

                                </td>


                                <td>

                                    <button class="btn btn-primary btn-sm">
                                        Send Request
                                    </button>

                                </td>

                            </tr>


                            <!-- PATIENT 2 -->

                            <tr>

                                <td>

                                    <div class="patient-cell">

                                        <div class="patient-avatar">
                                            KW
                                        </div>

                                        <div>

                                            <strong>
                                                Kamani Wickramasinghe
                                            </strong>

                                            <span>
                                                Patient ID: P002
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <td>
                                    Cancer Medication
                                </td>


                                <td>
                                    Unable to purchase prescribed medicine
                                </td>


                                <td>
                                    LKR 42,500
                                </td>


                                <td>

                                    <span class="status-pill scheduled">
                                        Medium
                                    </span>

                                </td>


                                <td>

                                    <button class="btn btn-primary btn-sm">
                                        Send Request
                                    </button>

                                </td>

                            </tr>


                            <!-- PATIENT 3 -->

                            <tr>

                                <td>

                                    <div class="patient-cell">

                                        <div class="patient-avatar">
                                            RP
                                        </div>

                                        <div>

                                            <strong>
                                                Ravi Perera
                                            </strong>

                                            <span>
                                                Patient ID: P003
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <td>
                                    Transport Assistance
                                </td>


                                <td>
                                    Unable to afford regular hospital transport
                                </td>


                                <td>
                                    LKR 12,000
                                </td>


                                <td>

                                    <span class="status-pill stable">
                                        Normal
                                    </span>

                                </td>


                                <td>

                                    <button class="btn btn-primary btn-sm">
                                        Send Request
                                    </button>

                                </td>

                            </tr>


                            <!-- PATIENT 4 -->

                            <tr>

                                <td>

                                    <div class="patient-cell">

                                        <div class="patient-avatar">
                                            MN
                                        </div>

                                        <div>

                                            <strong>
                                                Malini Niroshan
                                            </strong>

                                            <span>
                                                Patient ID: P004
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <td>
                                    Nutrition Support
                                </td>


                                <td>
                                    Requires nutritional food during treatment
                                </td>


                                <td>
                                    LKR 18,000
                                </td>


                                <td>

                                    <span class="status-pill scheduled">
                                        Medium
                                    </span>

                                </td>


                                <td>

                                    <button class="btn btn-primary btn-sm">
                                        Send Request
                                    </button>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- BENEFATOR REQUESTS -->
            <div
                class="widget"
                style="margin-top:24px;"
            >

                <div class="widget-header">

                    <div>

                        <h3>
                            Benefactor Requests
                        </h3>

                        <p style="font-size:0.82rem; color:var(--gray-500);">
                            Track requests sent to benefactors
                        </p>

                    </div>

                </div>


                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>Patient</th>
                                <th>Support Required</th>
                                <th>Benefactor</th>
                                <th>Amount / Item</th>
                                <th>Status</th>
                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (empty($donation_requests)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;">No benefactor donation requests found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($donation_requests as $request): ?>
                                    <?php
                                    $patient_name = trim(($request['patient_first_name'] ?? '') . ' ' . ($request['patient_last_name'] ?? ''));
                                    $benefactor_name = trim(($request['benefactor_first_name'] ?? '') . ' ' . ($request['benefactor_last_name'] ?? ''));
                                    $organization = trim((string)($request['organization_name'] ?? ''));
                                    $status_class = $request['status'] === 'Received'
                                        ? 'active'
                                        : ($request['status'] === 'Rejected' ? 'critical' : 'scheduled');
                                    $slip_path = trim((string)($request['bank_slip_path'] ?? ''));
                                    $slip_url = '';
                                    if (preg_match('#^uploads/(bank_slips|bank-slips)/[A-Za-z0-9._-]+$#', $slip_path)) {
                                        $slip_url = '../../../public/' . str_replace(' ', '%20', $slip_path);
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($patient_name !== '' ? $patient_name : 'General Fund'); ?></td>
                                        <td><?php echo htmlspecialchars($request['need_title'] ?? $request['donation_type']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($benefactor_name); ?>
                                            <?php if ($organization !== ''): ?>
                                                <br><small><?php echo htmlspecialchars($organization); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($request['donation_type'] === 'Financial Aid'): ?>
                                                <?php echo htmlspecialchars($request['currency'] ?? 'LKR'); ?>
                                                <?php echo number_format((float)($request['amount'] ?? 0), 2); ?>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($request['item_name'] ?? $request['donation_type']); ?>
                                                x<?php echo (int)($request['quantity'] ?? 0); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="status-pill <?php echo $status_class; ?>"><?php echo htmlspecialchars($request['status']); ?></span></td>
                                        <td>
                                            <?php if ($slip_url !== ''): ?>
                                                <a class="btn btn-outline btn-sm" href="<?php echo htmlspecialchars($slip_url); ?>" target="_blank" rel="noopener">View Slip</a>
                                            <?php else: ?>
                                                <span style="font-size:0.8rem; color:var(--gray-500);">No slip</span>
                                            <?php endif; ?>

                                            <?php if ($request['status'] === 'Pending Verification'): ?>
                                                <form method="POST" action="medical_actions.php" style="display:inline-flex; gap:6px; margin-top:6px;">
                                                    <input type="hidden" name="action" value="update_donation_status">
                                                    <input type="hidden" name="donation_id" value="<?php echo (int)$request['donation_id']; ?>">
                                                    <button class="btn btn-primary btn-sm" type="submit" name="status" value="Received">Accept</button>
                                                    <button class="btn btn-outline btn-sm" type="submit" name="status" value="Rejected">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (false): ?>


                            <tr>

                                <td>
                                    Sandun Jayasekara
                                </td>

                                <td>
                                    Chemotherapy Cost
                                </td>

                                <td>
                                    Hope Foundation
                                </td>

                                <td>
                                    LKR 85,000
                                </td>

                                <td>

                                    <span class="status-pill scheduled">
                                        Pending
                                    </span>

                                </td>

                                <td>

                                    <button class="btn btn-primary btn-sm">
                                        Confirm
                                    </button>

                                    <button class="btn btn-outline btn-sm">
                                        View
                                    </button>

                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Kamani Wickramasinghe
                                </td>

                                <td>
                                    Cancer Medication
                                </td>

                                <td>
                                    Care Support Lanka
                                </td>

                                <td>
                                    Medicine
                                </td>

                                <td>

                                    <span class="status-pill active">
                                        Confirmed
                                    </span>

                                </td>

                                <td>

                                    <button class="btn btn-outline btn-sm">
                                        View
                                    </button>

                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Ravi Perera
                                </td>

                                <td>
                                    Transport Assistance
                                </td>

                                <td>
                                    Helping Hands
                                </td>

                                <td>
                                    LKR 12,000
                                </td>

                                <td>

                                    <span class="status-pill scheduled">
                                        Pending
                                    </span>

                                </td>

                                <td>

                                    <button class="btn btn-primary btn-sm">
                                        Confirm
                                    </button>

                                    <button class="btn btn-outline btn-sm">
                                        View
                                    </button>

                                </td>

                            </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- BENEFACOR INFO -->
            <div
                class="dash-grid"
                style="margin-top:24px;"
            >

                <div class="widget">

                    <div class="widget-header">

                        <h3>
                            Available Benefactors
                        </h3>

                    </div>

                    <div class="wellness-cards">

                        <div class="wellness-card">

                            <h4>
                                🤝 Hope Foundation
                            </h4>

                            <p>
                                Supports chemotherapy costs, medication and
                                hospital treatment expenses.
                            </p>

                        </div>


                        <div class="wellness-card">

                            <h4>
                                💙 Care Support Lanka
                            </h4>

                            <p>
                                Provides prescribed medicines and essential
                                medical equipment.
                            </p>

                        </div>


                        <div class="wellness-card">

                            <h4>
                                🚑 Helping Hands
                            </h4>

                            <p>
                                Provides patient transportation and
                                treatment-related travel assistance.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="widget">

                    <div class="widget-header">

                        <h3>
                            Support Process
                        </h3>

                    </div>

                    <div class="progress-list">

                        <div class="progress-item">

                            <h4>
                                Identify Patient Need
                                <span>100%</span>
                            </h4>

                            <div class="progress-bar-bg">

                                <div
                                    class="progress-bar-fill"
                                    style="width:100%;"
                                >
                                </div>

                            </div>

                            <p>
                                Financial or essential support requirement identified.
                            </p>

                        </div>


                        <div class="progress-item">

                            <h4>
                                Send Benefactor Request
                                <span>75%</span>
                            </h4>

                            <div class="progress-bar-bg">

                                <div
                                    class="progress-bar-fill"
                                    style="width:75%;"
                                >
                                </div>

                            </div>

                            <p>
                                Request sent to a suitable benefactor.
                            </p>

                        </div>


                        <div class="progress-item">

                            <h4>
                                Staff Confirmation
                                <span>50%</span>
                            </h4>

                            <div class="progress-bar-bg">

                                <div
                                    class="progress-bar-fill"
                                    style="width:50%;"
                                >
                                </div>

                            </div>

                            <p>
                                Medical staff confirms the support request.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>


<script>

const toggleBtn =
    document.getElementById("sidebarToggle");

const sidebar =
    document.getElementById("sidebar");


if (toggleBtn) {

    toggleBtn.addEventListener(
        "click",
        function () {

            sidebar.classList.toggle("open");

        }
    );

}

</script>

</body>
</html>