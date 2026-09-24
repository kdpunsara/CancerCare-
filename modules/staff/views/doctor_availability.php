<?php
session_start();

require_once __DIR__ . '/../../../config/database.php';

if (!$conn) {
    die("Database connection failed.");
}

/* =========================================================
   CREATE AVAILABILITY TABLE IF NOT EXISTS
   ========================================================= */

$createTableSql = "
CREATE TABLE IF NOT EXISTS `DoctorAvailability` (
    `availability_id` INT NOT NULL AUTO_INCREMENT,
    `doctor_id` INT NOT NULL,
    `available_days` VARCHAR(255) NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Available',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`availability_id`),
    UNIQUE KEY `unique_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

$conn->query($createTableSql);


/* =========================================================
   UPDATE AVAILABILITY
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'update_availability') {

        $doctorId     = intval($_POST['doctor_id'] ?? 0);
        $availableDays = trim($_POST['available_days'] ?? '');
        $startTime     = trim($_POST['start_time'] ?? '');
        $endTime       = trim($_POST['end_time'] ?? '');
        $status        = trim($_POST['status'] ?? 'Available');

        if ($doctorId <= 0) {
            $_SESSION['availability_error'] = "Please select a doctor.";
        } elseif ($availableDays === '') {
            $_SESSION['availability_error'] = "Please enter available days.";
        } elseif ($startTime === '' || $endTime === '') {
            $_SESSION['availability_error'] = "Please enter start and end time.";
        } elseif ($startTime >= $endTime) {
            $_SESSION['availability_error'] =
                "End time must be later than start time.";
        } elseif (!in_array($status, ['Available', 'Limited', 'Not Today'], true)) {
            $_SESSION['availability_error'] = "Invalid status.";
        } else {

            /* Check doctor exists */
            $doctorCheck = $conn->prepare(
                "SELECT user_id
                 FROM `Doctor`
                 WHERE user_id = ?
                 LIMIT 1"
            );

            $doctorCheck->bind_param("i", $doctorId);
            $doctorCheck->execute();
            $doctorResult = $doctorCheck->get_result();

            if ($doctorResult->num_rows === 0) {

                $_SESSION['availability_error'] =
                    "Selected doctor does not exist.";

            } else {

                /*
                 * INSERT OR UPDATE
                 * One availability record per doctor.
                 */

                $sql = "
                    INSERT INTO `DoctorAvailability`
                    (
                        doctor_id,
                        available_days,
                        start_time,
                        end_time,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?)

                    ON DUPLICATE KEY UPDATE
                        available_days = VALUES(available_days),
                        start_time = VALUES(start_time),
                        end_time = VALUES(end_time),
                        status = VALUES(status)
                ";

                $stmt = $conn->prepare($sql);

                if ($stmt) {

                    $stmt->bind_param(
                        "issss",
                        $doctorId,
                        $availableDays,
                        $startTime,
                        $endTime,
                        $status
                    );

                    if ($stmt->execute()) {

                        $_SESSION['availability_success'] =
                            "Doctor availability updated successfully.";

                    } else {

                        $_SESSION['availability_error'] =
                            "Failed to update availability.";
                    }

                    $stmt->close();

                } else {

                    $_SESSION['availability_error'] =
                        "Database query error: " . $conn->error;
                }
            }

            $doctorCheck->close();
        }

        header("Location: doctor_availability.php");
        exit;
    }
}


/* =========================================================
   SUCCESS / ERROR MESSAGE
   ========================================================= */

$success = $_SESSION['availability_success'] ?? '';
$error   = $_SESSION['availability_error'] ?? '';

unset(
    $_SESSION['availability_success'],
    $_SESSION['availability_error']
);


/* =========================================================
   LOAD DOCTORS + AVAILABILITY
   ========================================================= */

$doctors = [];

$sql = "
    SELECT
        d.user_id,
        d.first_name,
        d.last_name,
        d.specialization,

        da.available_days,
        da.start_time,
        da.end_time,
        da.status

    FROM `Doctor` d

    LEFT JOIN `DoctorAvailability` da
        ON da.doctor_id = d.user_id

    ORDER BY
        d.first_name ASC,
        d.last_name ASC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $doctors[] = $row;
    }
}


/* =========================================================
   TODAY
   ========================================================= */

$todayName = date('l');
$todayFormatted = date('l, F d, Y');


/* =========================================================
   HELPER FUNCTIONS
   ========================================================= */

function formatDoctorTime($time)
{
    if (!$time) {
        return 'Not Set';
    }

    return date('h:i A', strtotime($time));
}


function getDoctorInitials($firstName, $lastName)
{
    $first = strtoupper(substr($firstName, 0, 1));
    $last  = strtoupper(substr($lastName, 0, 1));

    return $first . $last;
}


function statusClass($status)
{
    if ($status === 'Available') {
        return 'active';
    }

    if ($status === 'Limited') {
        return 'scheduled';
    }

    if ($status === 'Not Today') {
        return 'stable';
    }

    return 'stable';
}


/* =========================================================
   TODAY SCHEDULE CHECK
   ========================================================= */

function doctorAvailableToday($days, $today)
{
    if (!$days) {
        return false;
    }

    $dayList = array_map(
        'trim',
        explode(',', $days)
    );

    foreach ($dayList as $day) {

        if (strcasecmp($day, $today) === 0) {
            return true;
        }
    }

    return false;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Doctor Availability — Cancer Care</title>

<link rel="stylesheet" href="../../../public/css/styles.css">
<link rel="stylesheet" href="../../../public/css/modules.css">

<style>

/* =====================================================
   SIDEBAR SVG ICONS
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

.icon-dashboard {
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='3' y='3' width='7' height='9' rx='1.5'/><rect x='14' y='3' width='7' height='5' rx='1.5'/><rect x='14' y='12' width='7' height='9' rx='1.5'/><rect x='3' y='16' width='7' height='5' rx='1.5'/></svg>");
}

.icon-profile {
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='8' r='4'/><path d='M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7'/></svg>");
}

.icon-register {
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M15 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'/><circle cx='8.5' cy='7' r='4'/><path d='M20 8v6M17 11h6'/></svg>");
}

.icon-appointments {
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='3' y='5' width='18' height='16' rx='2'/><path d='M16 3v4M8 3v4M3 10h18'/></svg>");
}

.icon-records {
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M7 3h7l4 4v14H7z'/><path d='M9 12h6M9 16h6M9 8h2'/></svg>");
}

.icon-prescriptions {
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='3' y='9' width='18' height='9' rx='4.5' transform='rotate(-45 12 12)'/><path d='M8 16l8-8'/></svg>");
}

.icon-doctor {
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='7' r='4'/><path d='M5 21v-2a7 7 0 0 1 14 0v2'/><path d='M17 16l2 2 4-4'/></svg>");
}

.icon-benefactor {
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='9' cy='7' r='4'/><path d='M2 21v-2a7 7 0 0 1 14 0v2'/><path d='M16 11h6M19 8v6'/></svg>");
}


/* =====================================================
   SEARCH / NOTIFICATION / MENU
===================================================== */

.search-icon {
    display: inline-block;
    width: 18px;
    height: 18px;
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='11' cy='11' r='7'/><path d='m20 20-4-4'/></svg>");
    flex-shrink: 0;
}

.notification-icon {
    display: inline-block;
    width: 18px;
    height: 18px;
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9'/><path d='M10 21h4'/></svg>");
}

.menu-icon {
    display: inline-block;
    width: 20px;
    height: 20px;
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round'><path d='M4 6h16M4 12h16M4 18h16'/></svg>");
}


/* =====================================================
   DATABASE MESSAGE
===================================================== */

.db-message {
    margin-bottom: 20px;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
}

.db-success {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.db-error {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

</style>

</head>


<body>

<div class="app">


<!-- =====================================================
     SIDEBAR
====================================================== -->

<aside class="sidebar" id="sidebar">

    <div class="sidebar-header">

        <div class="logo">

            <div class="logo-icon">
                ❤
            </div>

            <div>
                Cancer care

                <span class="logo-sub">
                    Cancer Patient Care
                </span>

            </div>

        </div>

    </div>


    <nav class="sidebar-nav">

        <div class="nav-section">
            Navigation
        </div>

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

    </nav>


    <div class="sidebar-footer">

        <div class="user-card">

            <div class="user-avatar">
                NP
            </div>

            <div class="user-info">

                <strong>
                    Nimali Perera
                </strong>

                <span>
                    Medical Staff
                </span>

            </div>

        </div>

    </div>

</aside>



<!-- =====================================================
     MAIN
====================================================== -->

<main class="main">


<!-- =====================================================
     TOPBAR
====================================================== -->

<header class="topbar">

    <div class="topbar-left">

        <h1>
            Doctor Availability
        </h1>

        <p>
            Check and update doctor consultation times
        </p>

    </div>


    <div class="topbar-right">

        <div class="search-box">

            <span class="search-icon"></span>

            <input
                type="text"
                placeholder="Search doctors..."
                id="topSearch"
            >

        </div>


        <button
            class="icon-btn"
            type="button"
            title="Notifications"
        >

            <span class="notification-icon"></span>

            <span class="dot"></span>

        </button>


        <button
            class="btn btn-outline"
            type="button"
        >
            Sign Out
        </button>


        <button
            class="sidebar-toggle"
            id="sidebarToggle"
            type="button"
        >

            <span class="menu-icon"></span>

        </button>

    </div>

</header>



<!-- =====================================================
     CONTENT
====================================================== -->

<div class="content">


<?php if ($success): ?>

    <div class="db-message db-success">
        <?= htmlspecialchars($success) ?>
    </div>

<?php endif; ?>


<?php if ($error): ?>

    <div class="db-message db-error">
        <?= htmlspecialchars($error) ?>
    </div>

<?php endif; ?>


<!-- =====================================================
     FILTERS
====================================================== -->

<div class="page-toolbar">


    <div class="search-box search-inline">

        <span class="search-icon"></span>

        <input
            type="text"
            id="doctorSearch"
            placeholder="Search doctor by name or specialty..."
        >

    </div>


    <select
        class="form-input filter-select"
        id="departmentFilter"
    >

        <option value="">
            All Departments
        </option>

        <option value="Medical Oncology">
            Medical Oncology
        </option>

        <option value="Radiation Oncology">
            Radiation Oncology
        </option>

        <option value="Surgical Oncology">
            Surgical Oncology
        </option>

        <option value="Hematology">
            Hematology
        </option>

    </select>


    <select
        class="form-input filter-select"
        id="availabilityFilter"
    >

        <option value="">
            All Availability
        </option>

        <option value="Available">
            Available
        </option>

        <option value="Limited">
            Limited
        </option>

        <option value="Not Today">
            Not Today
        </option>

    </select>

</div>



<!-- =====================================================
     UPDATE DOCTOR AVAILABILITY
====================================================== -->

<div class="widget">

    <div class="widget-header">

        <div>

            <h3>
                Update Doctor Availability
            </h3>

            <p
                style="
                    color:var(--gray-500);
                    font-size:0.82rem;
                "
            >
                Update available days and consultation time
            </p>

        </div>

    </div>


    <form
        id="availabilityForm"
        method="POST"
        action="doctor_availability.php"
    >

        <input
            type="hidden"
            name="action"
            value="update_availability"
        >

        <input
            type="hidden"
            name="doctor_id"
            id="doctorIdInput"
        >


        <div
            style="
                display:grid;
                grid-template-columns:
                    repeat(auto-fit,minmax(180px,1fr));
                gap:16px;
            "
        >


            <!-- Doctor -->

            <div>

                <label
                    for="doctorSelect"
                    style="
                        display:block;
                        margin-bottom:6px;
                        font-weight:600;
                    "
                >
                    Doctor
                </label>


                <select
                    id="doctorSelect"
                    class="form-input"
                    required
                >

                    <option value="">
                        Select Doctor
                    </option>


                    <?php foreach ($doctors as $doctor): ?>

                        <option
                            value="<?= htmlspecialchars($doctor['user_id']) ?>"
                        >

                            Dr.
                            <?= htmlspecialchars(
                                $doctor['first_name'] . ' ' .
                                $doctor['last_name']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <!-- Available Days -->

            <div>

                <label
                    for="daysInput"
                    style="
                        display:block;
                        margin-bottom:6px;
                        font-weight:600;
                    "
                >
                    Available Days
                </label>


                <input
                    type="text"
                    id="daysInput"
                    name="available_days"
                    class="form-input"
                    placeholder="Monday, Wednesday, Friday"
                    required
                >

            </div>



            <!-- Start Time -->

            <div>

                <label
                    for="startTime"
                    style="
                        display:block;
                        margin-bottom:6px;
                        font-weight:600;
                    "
                >
                    Start Time
                </label>


                <input
                    type="time"
                    id="startTime"
                    name="start_time"
                    class="form-input"
                    required
                >

            </div>



            <!-- End Time -->

            <div>

                <label
                    for="endTime"
                    style="
                        display:block;
                        margin-bottom:6px;
                        font-weight:600;
                    "
                >
                    End Time
                </label>


                <input
                    type="time"
                    id="endTime"
                    name="end_time"
                    class="form-input"
                    required
                >

            </div>



            <!-- Status -->

            <div>

                <label
                    for="statusInput"
                    style="
                        display:block;
                        margin-bottom:6px;
                        font-weight:600;
                    "
                >
                    Status
                </label>


                <select
                    id="statusInput"
                    name="status"
                    class="form-input"
                    required
                >

                    <option value="Available">
                        Available
                    </option>

                    <option value="Limited">
                        Limited
                    </option>

                    <option value="Not Today">
                        Not Today
                    </option>

                </select>

            </div>

        </div>



        <div
            style="
                margin-top:18px;
                display:flex;
                gap:10px;
                flex-wrap:wrap;
            "
        >

            <button
                type="submit"
                class="btn btn-primary"
            >
                Update Schedule
            </button>


            <button
                type="button"
                class="btn btn-outline"
                id="clearForm"
            >
                Clear
            </button>

        </div>

    </form>

</div>



<!-- =====================================================
     AVAILABLE DOCTORS
====================================================== -->

<div
    class="widget"
    style="margin-top:24px;"
>

    <div class="widget-header">

        <div>

            <h3>
                Available Doctors
            </h3>

            <p
                style="
                    color:var(--gray-500);
                    font-size:0.82rem;
                "
            >
                Doctors currently available for patient appointments
            </p>

        </div>


        <span
            class="form-badge"
            id="doctorCount"
        >
            <?= count($doctors) ?> Doctors
        </span>

    </div>



    <div class="table-wrap">

        <table>

            <thead>

                <tr>

                    <th>
                        Doctor
                    </th>

                    <th>
                        Specialty
                    </th>

                    <th>
                        Available Days
                    </th>

                    <th>
                        Time
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody id="doctorTableBody">


            <?php if (!empty($doctors)): ?>


                <?php foreach ($doctors as $doctor): ?>

                    <?php

                    $doctorId =
                        $doctor['user_id'];

                    $doctorName =
                        'Dr. ' .
                        $doctor['first_name'] .
                        ' ' .
                        $doctor['last_name'];

                    $initials =
                        getDoctorInitials(
                            $doctor['first_name'],
                            $doctor['last_name']
                        );

                    $days =
                        $doctor['available_days']
                        ?: 'Not Set';

                    $start =
                        $doctor['start_time'];

                    $end =
                        $doctor['end_time'];

                    $status =
                        $doctor['status']
                        ?: 'Not Set';

                    $statusCss =
                        statusClass($status);

                    ?>

                    <tr
                        data-doctor-id="<?= htmlspecialchars($doctorId) ?>"
                        data-doctor="<?= htmlspecialchars($doctorName) ?>"
                        data-department="<?= htmlspecialchars($doctor['specialization']) ?>"
                    >

                        <td>

                            <div class="patient-cell">

                                <div class="patient-avatar">
                                    <?= htmlspecialchars($initials) ?>
                                </div>


                                <div>

                                    <strong>
                                        <?= htmlspecialchars($doctorName) ?>
                                    </strong>

                                    <span>
                                        <?= htmlspecialchars($doctor['specialization']) ?>
                                    </span>

                                </div>

                            </div>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $doctor['specialization']
                            ) ?>

                        </td>


                        <td class="doctor-days">

                            <?= htmlspecialchars($days) ?>

                        </td>


                        <td class="doctor-time">

                            <?php if ($start && $end): ?>

                                <?= htmlspecialchars(
                                    formatDoctorTime($start)
                                ) ?>

                                –

                                <?= htmlspecialchars(
                                    formatDoctorTime($end)
                                ) ?>

                            <?php else: ?>

                                Not Set

                            <?php endif; ?>

                        </td>


                        <td>

                            <span
                                class="status-pill <?= $statusCss ?> doctor-status"
                            >
                                <?= htmlspecialchars($status) ?>
                            </span>

                        </td>


                        <td>

                            <button
                                class="btn btn-outline btn-sm edit-doctor"
                                type="button"
                                data-doctor-id="<?= htmlspecialchars($doctorId) ?>"
                            >
                                Edit
                            </button>

                        </td>

                    </tr>


                <?php endforeach; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="6"
                        style="text-align:center;"
                    >
                        No doctors found in database.
                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </div>

</div>



<!-- =====================================================
     TODAY'S SCHEDULE
====================================================== -->

<div
    class="widget"
    style="margin-top:24px;"
>

    <div class="widget-header">

        <h3>
            Today's Doctor Schedule
        </h3>


        <span class="status-pill active">

            <?= htmlspecialchars($todayFormatted) ?>

        </span>

    </div>


    <div
        class="appt-list"
        id="todaySchedule"
    >


        <?php

        $todayDoctors = [];

        foreach ($doctors as $doctor) {

            if (
                $doctor['status'] === 'Not Today'
            ) {
                continue;
            }

            if (
                doctorAvailableToday(
                    $doctor['available_days'],
                    $todayName
                )
            ) {

                $todayDoctors[] = $doctor;
            }
        }

        ?>


        <?php if (!empty($todayDoctors)): ?>


            <?php foreach ($todayDoctors as $doctor): ?>

                <?php

                $doctorName =
                    'Dr. ' .
                    $doctor['first_name'] .
                    ' ' .
                    $doctor['last_name'];

                $doctorStatus =
                    $doctor['status']
                    ?: 'Available';

                $itemClass = 'appt-item';

                if (
                    $doctorStatus === 'Limited'
                ) {
                    $itemClass .= ' critical';
                }

                ?>

                <div class="<?= $itemClass ?>">

                    <div class="appt-time">

                        <strong>

                            <?php
                            if ($doctor['start_time']) {
                                echo htmlspecialchars(
                                    formatDoctorTime(
                                        $doctor['start_time']
                                    )
                                );
                            } else {
                                echo 'Not Set';
                            }
                            ?>

                        </strong>

                        <span>
                            -
                        </span>

                    </div>


                    <div class="appt-info">

                        <h4>
                            <?= htmlspecialchars($doctorName) ?>
                        </h4>

                        <p>

                            <?= htmlspecialchars(
                                $doctor['specialization']
                            ) ?>

                            · Consultation available

                        </p>

                    </div>


                    <span class="appt-tag">

                        <?= htmlspecialchars(
                            $doctorStatus
                        ) ?>

                    </span>

                </div>

            <?php endforeach; ?>


        <?php else: ?>


            <div class="appt-item">

                <div class="appt-info">

                    <h4>
                        No doctors available today
                    </h4>

                    <p>
                        There are currently no doctors scheduled.
                    </p>

                </div>

            </div>


        <?php endif; ?>


    </div>

</div>


</div>

</main>

</div>



<script>

/* =========================================================
   DOCTOR DATA FROM PHP / DATABASE
========================================================= */

const doctors = {

<?php foreach ($doctors as $doctor): ?>

    <?= json_encode((string)$doctor['user_id']) ?>: {

        name:
            <?= json_encode(
                'Dr. ' .
                $doctor['first_name'] .
                ' ' .
                $doctor['last_name']
            ) ?>,

        days:
            <?= json_encode(
                $doctor['available_days'] ?? ''
            ) ?>,

        start:
            <?= json_encode(
                $doctor['start_time'] ?? ''
            ) ?>,

        end:
            <?= json_encode(
                $doctor['end_time'] ?? ''
            ) ?>,

        status:
            <?= json_encode(
                $doctor['status'] ?? 'Available'
            ) ?>

    },

<?php endforeach; ?>

};


/* =========================================================
   ELEMENTS
========================================================= */

const form =
    document.getElementById("availabilityForm");

const doctorSelect =
    document.getElementById("doctorSelect");

const doctorIdInput =
    document.getElementById("doctorIdInput");

const daysInput =
    document.getElementById("daysInput");

const startTime =
    document.getElementById("startTime");

const endTime =
    document.getElementById("endTime");

const statusInput =
    document.getElementById("statusInput");

const doctorSearch =
    document.getElementById("doctorSearch");

const departmentFilter =
    document.getElementById("departmentFilter");

const availabilityFilter =
    document.getElementById("availabilityFilter");

const doctorTableBody =
    document.getElementById("doctorTableBody");


/* =========================================================
   LOAD DOCTOR
========================================================= */

function loadDoctor(id) {

    if (!doctors[id]) {
        return;
    }

    const doctor =
        doctors[id];

    doctorIdInput.value =
        id;

    doctorSelect.value =
        id;

    daysInput.value =
        doctor.days || "";

    startTime.value =
        doctor.start || "";

    endTime.value =
        doctor.end || "";

    statusInput.value =
        doctor.status || "Available";
}


/* =========================================================
   DOCTOR SELECT CHANGE
========================================================= */

doctorSelect.addEventListener(
    "change",
    function () {

        const id =
            this.value;

        if (!id) {

            doctorIdInput.value = "";
            daysInput.value = "";
            startTime.value = "";
            endTime.value = "";
            statusInput.value = "Available";

            return;
        }

        loadDoctor(id);
    }
);


/* =========================================================
   EDIT BUTTONS
========================================================= */

document
    .querySelectorAll(".edit-doctor")
    .forEach(button => {

        button.addEventListener(
            "click",
            function () {

                const id =
                    this.dataset.doctorId;

                loadDoctor(id);

                document
                    .getElementById("availabilityForm")
                    .scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });

            }
        );

    });


/* =========================================================
   FORM VALIDATION
========================================================= */

form.addEventListener(
    "submit",
    function(event) {

        const doctorId =
            doctorSelect.value;

        if (!doctorId) {

            event.preventDefault();

            alert(
                "Please select a doctor."
            );

            return;
        }


        if (
            startTime.value >=
            endTime.value
        ) {

            event.preventDefault();

            alert(
                "End time must be later than start time."
            );

            return;
        }

    }
);


/* =========================================================
   CLEAR FORM
========================================================= */

document
    .getElementById("clearForm")
    .addEventListener(
        "click",
        function() {

            form.reset();

            doctorIdInput.value = "";

        }
    );


/* =========================================================
   SEARCH + FILTER
========================================================= */

function filterDoctors() {

    const search =
        doctorSearch.value
            .toLowerCase()
            .trim();

    const department =
        departmentFilter.value;

    const availability =
        availabilityFilter.value;

    const rows =
        document.querySelectorAll(
            "#doctorTableBody tr"
        );

    let visibleCount = 0;


    rows.forEach(row => {

        const name =
            (
                row.dataset.doctor || ""
            ).toLowerCase();

        const rowDepartment =
            row.dataset.department || "";

        const statusElement =
            row.querySelector(
                ".doctor-status"
            );

        const status =
            statusElement
                ? statusElement.textContent.trim()
                : "";


        const matchesSearch =
            name.includes(search) ||
            rowDepartment
                .toLowerCase()
                .includes(search);


        const matchesDepartment =
            !department ||
            rowDepartment === department;


        const matchesAvailability =
            !availability ||
            status === availability;


        const visible =
            matchesSearch &&
            matchesDepartment &&
            matchesAvailability;


        row.style.display =
            visible ? "" : "none";


        if (visible) {
            visibleCount++;
        }

    });


    document.getElementById(
        "doctorCount"
    ).textContent =
        `${visibleCount} Doctors`;

}


/* =========================================================
   SEARCH EVENTS
========================================================= */

doctorSearch.addEventListener(
    "input",
    filterDoctors
);


departmentFilter.addEventListener(
    "change",
    filterDoctors
);


availabilityFilter.addEventListener(
    "change",
    filterDoctors
);


/* =========================================================
   TOPBAR SEARCH
========================================================= */

document
    .getElementById("topSearch")
    .addEventListener(
        "input",
        function() {

            doctorSearch.value =
                this.value;

            filterDoctors();

        }
    );


/* =========================================================
   MOBILE SIDEBAR
========================================================= */

const toggleBtn =
    document.getElementById(
        "sidebarToggle"
    );

const sidebar =
    document.getElementById(
        "sidebar"
    );


if (toggleBtn && sidebar) {

    toggleBtn.addEventListener(
        "click",
        function() {

            sidebar.classList.toggle(
                "open"
            );

        }
    );

}


/* =========================================================
   AUTO HIDE SUCCESS / ERROR
========================================================= */

setTimeout(
    function() {

        const messages =
            document.querySelectorAll(
                ".db-message"
            );

        messages.forEach(message => {

            message.style.transition =
                "opacity 0.5s";

            message.style.opacity = "0";

        });

    },
    4000
);

</script>

</body>
</html>