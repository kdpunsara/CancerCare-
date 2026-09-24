<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config/database.php';


/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   GET PATIENTS
========================================================= */

$patients = [];

try {

    $res = $conn->query("
        SELECT
            user_id,
            first_name,
            last_name
        FROM Patient
        ORDER BY first_name ASC, last_name ASC
    ");

    while ($row = $res->fetch_assoc()) {
        $patients[] = $row;
    }

} catch (Throwable $ex) {

    $patients = [];

}


/* =========================================================
   GET DOCTORS
========================================================= */

$doctors = [];

try {

    $res = $conn->query("
        SELECT
            user_id,
            first_name,
            last_name
        FROM Doctor
        ORDER BY first_name ASC, last_name ASC
    ");

    while ($row = $res->fetch_assoc()) {
        $doctors[] = $row;
    }

} catch (Throwable $ex) {

    $doctors = [];

}


/* =========================================================
   GET APPOINTMENTS
========================================================= */

$appointments = [];

$dbError = '';

try {

    $sql = "
        SELECT
            a.appointment_id,
            a.patient_user_id,
            a.doctor_user_id,
            a.appointment_date,
            a.appointment_time,
            a.type,
            a.status,

            CONCAT(
                p.first_name,
                ' ',
                p.last_name
            ) AS patient_name,

            CONCAT(
                'Dr. ',
                d.first_name,
                ' ',
                d.last_name
            ) AS doctor_name

        FROM Appointment a

        LEFT JOIN Patient p
            ON p.user_id = a.patient_user_id

        LEFT JOIN Doctor d
            ON d.user_id = a.doctor_user_id

        ORDER BY
            a.appointment_date ASC,
            a.appointment_time ASC
    ";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {

        $appointments[] = $row;

    }

} catch (Throwable $ex) {

    $appointments = [];

    $dbError = $ex->getMessage();

}


/* =========================================================
   MESSAGES
========================================================= */

$msg    = $_GET['msg'] ?? '';
$reason = $_GET['reason'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Appointments — Cancer care</title>


    <link
        rel="stylesheet"
        href="../../../public/css/styles.css"
    >

    <link
        rel="stylesheet"
        href="../../../public/css/modules.css"
    >


    <style>

        /* =====================================================
           SIDEBAR ICONS
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


        .icon-doctor {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='7' r='4'/><path d='M5 21v-2a7 7 0 0 1 14 0v2'/><path d='M17 16l2 2 4-4'/></svg>");
        }


        .icon-benefactor {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='9' cy='7' r='4'/><path d='M2 21v-2a7 7 0 0 1 14 0v2'/><path d='M16 11h6M19 8v6'/></svg>");
        }


        /* =====================================================
           CREATE BUTTON
        ===================================================== */

        .btn-create {

            padding: 11px 18px;

            background: #2563eb;
            color: white;

            border: none;
            border-radius: 8px;

            cursor: pointer;

            font-weight: 600;
            font-size: 14px;

            transition: all 0.2s ease;
        }


        .btn-create:hover {
            background: #1d4ed8;
        }


        /* =====================================================
           ACTION CELL
        ===================================================== */

        .action-cell {

            display: flex;

            align-items: center;

            gap: 8px;

            flex-wrap: wrap;
        }


        /* =====================================================
           UPDATE BUTTON
        ===================================================== */

        .btn-update {

            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 7px;

            padding: 8px 13px;

            background: #ffffff;

            color: #2563eb;

            border: 1px solid #bfdbfe;

            border-radius: 7px;

            cursor: pointer;

            font-weight: 600;

            font-size: 13px;

            transition:
                background 0.2s ease,
                border-color 0.2s ease,
                color 0.2s ease,
                transform 0.1s ease;
        }


        .btn-update:hover {

            background: #eff6ff;

            border-color: #93c5fd;

            color: #1d4ed8;
        }


        .btn-update:active {

            transform: scale(0.97);
        }


        .update-icon {

            width: 16px;
            height: 16px;

            display: inline-block;

            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;

            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232563eb' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M12 20h9'/><path d='M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z'/></svg>");
        }


        .btn-update:hover .update-icon {

            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231d4ed8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M12 20h9'/><path d='M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z'/></svg>");
        }


        /* =====================================================
           DELETE BUTTON
           
           THIS IS LEFT AS THE EXISTING DELETE BUTTON
        ===================================================== */

        .btn-delete {

            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 7px;

            padding: 8px 13px;

            background: #ffffff;

            color: #dc2626;

            border: 1px solid #fecaca;

            border-radius: 7px;

            cursor: pointer;

            font-weight: 600;

            font-size: 13px;

            transition:
                background 0.2s ease,
                border-color 0.2s ease,
                color 0.2s ease,
                transform 0.1s ease;
        }


        .btn-delete:hover {

            background: #fee2e2;

            border-color: #fca5a5;

            color: #b91c1c;
        }


        .btn-delete:active {

            transform: scale(0.97);
        }


        .delete-icon {

            width: 16px;
            height: 16px;

            display: inline-block;

            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;

            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23dc2626' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='3 6 5 6 21 6'/><path d='M19 6l-1 14H6L5 6'/><path d='M10 11v6M14 11v6'/><path d='M9 6V4h6v2'/></svg>");
        }


        .btn-delete:hover .delete-icon {

            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23b91c1c' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='3 6 5 6 21 6'/><path d='M19 6l-1 14H6L5 6'/><path d='M10 11v6M14 11v6'/><path d='M9 6V4h6v2'/></svg>");
        }


        /* =====================================================
           MESSAGES
        ===================================================== */

        .message {

            padding: 12px 16px;

            margin-bottom: 18px;

            border-radius: 8px;

            background: #dcfce7;

            color: #166534;

            font-weight: 600;
        }


        .error-message {

            padding: 12px 16px;

            margin-bottom: 18px;

            border-radius: 8px;

            background: #fee2e2;

            color: #991b1b;

            font-weight: 600;
        }


        /* =====================================================
           TOOLBAR
        ===================================================== */

        .page-toolbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 20px;
        }


        .toolbar-left {

            display: flex;

            gap: 10px;
        }


        /* =====================================================
           MODAL
        ===================================================== */

        .modal {

            display: none;

            position: fixed;

            z-index: 9999;

            left: 0;
            top: 0;

            width: 100%;
            height: 100%;

            background: rgba(15, 23, 42, 0.55);

            align-items: center;
            justify-content: center;

            padding: 20px;
        }


        .modal.show {

            display: flex;
        }


        .modal-box {

            width: 100%;

            max-width: 600px;

            max-height: 92vh;

            overflow-y: auto;

            background: white;

            border-radius: 16px;

            padding: 28px;

            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        }


        .modal-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;
        }


        .modal-header h2 {

            margin: 0;

            font-size: 22px;
        }


        .close-btn {

            border: none;

            background: transparent;

            font-size: 28px;

            cursor: pointer;

            color: #64748b;
        }


        .form-group {

            margin-bottom: 18px;
        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-weight: 600;

            color: #334155;
        }


        .form-control {

            width: 100%;

            padding: 12px 13px;

            border: 1px solid #cbd5e1;

            border-radius: 8px;

            font-size: 14px;

            background: white;

            box-sizing: border-box;
        }


        .form-control:focus {

            outline: none;

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,0.12);
        }


        .form-row {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 15px;
        }


        .modal-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 25px;
        }


        .btn-cancel {

            padding: 11px 18px;

            background: #e2e8f0;

            color: #334155;

            border: none;

            border-radius: 8px;

            cursor: pointer;

            font-weight: 600;
        }


        .btn-save {

            padding: 11px 20px;

            background: #2563eb;

            color: white;

            border: none;

            border-radius: 8px;

            cursor: pointer;

            font-weight: 600;
        }


        .btn-save:hover {

            background: #1d4ed8;
        }


        @media (max-width: 700px) {

            .form-row {

                grid-template-columns: 1fr;
            }


            .page-toolbar {

                flex-direction: column;

                align-items: stretch;
            }


            .action-cell {

                flex-direction: column;

                align-items: stretch;
            }

        }

    </style>

</head>


<body>


<div class="app">


    <!-- =====================================================
         SIDEBAR
    ===================================================== -->

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
                NAVIGATION
            </div>


            <a
                href="staff_dashboard.php"
                class="nav-item"
            >

                <span class="icon icon-dashboard"></span>

                Dashboard

            </a>


            <a
                href="staff_patient.php"
                class="nav-item"
            >

                <span class="icon icon-profile"></span>

                Patients

            </a>


            <a
                href="Register_patient.php"
                class="nav-item"
            >

                <span class="icon icon-register"></span>

                Register Patient

            </a>


            <a
                href="staff_appointment.php"
                class="nav-item active"
            >

                <span class="icon icon-appointments"></span>

                Appointments

            </a>


            <a
                href="medical_reports.php"
                class="nav-item"
            >

                <span class="icon icon-records"></span>

                Medical Reports

            </a>


            <a
                href="doctor_availability.php"
                class="nav-item"
            >

                <span class="icon icon-doctor"></span>

                Doctor Availability

            </a>


            <a
                href="benefactor.php"
                class="nav-item"
            >

                <span class="icon icon-benefactor"></span>

                Benefactor

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
    ===================================================== -->

    <main class="main">


        <header class="topbar">


            <div class="topbar-left">

                <h1>
                    Appointments
                </h1>

                <p>
                    <?php echo date('l, F j, Y'); ?>
                </p>

            </div>


            <div class="topbar-right">

                <button
                    class="icon-btn notif-btn"
                    title="Notifications"
                    type="button"
                >
                    🔔

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
                    ☰
                </button>

            </div>


        </header>


        <div class="content">


            <!-- =================================================
                 MESSAGES
            ================================================= -->

            <?php if ($msg === 'created'): ?>

                <div class="message">
                    Appointment created successfully.
                </div>

            <?php elseif ($msg === 'updated'): ?>

                <div class="message">
                    Appointment updated successfully.
                </div>

            <?php elseif ($msg === 'deleted'): ?>

                <div class="message">
                    Appointment deleted successfully.
                </div>

            <?php elseif ($msg === 'error'): ?>

                <div class="error-message">

                    <?php

                    echo $reason !== ''
                        ? e($reason)
                        : 'Something went wrong. Please try again.';

                    ?>

                </div>

            <?php endif; ?>


            <?php if ($dbError !== ''): ?>

                <div class="error-message">

                    <?php echo e($dbError); ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 TOOLBAR
            ================================================= -->

            <div class="page-toolbar">


                <div class="toolbar-left">


                    <select
                        class="form-input filter-select"
                        id="statusFilter"
                    >

                        <option value="all">
                            ✓ All Status
                        </option>

                        <option value="pending">
                            pending
                        </option>

                        <option value="confirmed">
                            confirmed
                        </option>

                        <option value="cancelled">
                            cancelled
                        </option>

                        <option value="completed">
                            completed
                        </option>

                    </select>


                </div>


                <button
                    type="button"
                    class="btn-create"
                    id="openCreateModal"
                >

                    + Create Appointment

                </button>


            </div>


            <!-- =================================================
                 APPOINTMENT TABLE
            ================================================= -->

            <div class="widget">


                <div class="table-wrap">


                    <table>


                        <thead>

                            <tr>

                                <th>DATE</th>

                                <th>TIME</th>

                                <th>PATIENT</th>

                                <th>DOCTOR</th>

                                <th>TYPE</th>

                                <th>STATUS</th>

                                <th>ACTIONS</th>

                            </tr>

                        </thead>


                        <tbody
                            id="appointmentTableBody"
                        >


                        <?php if (empty($appointments)): ?>


                            <tr>

                                <td
                                    colspan="7"
                                    style="text-align:center;"
                                >

                                    No appointments found.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($appointments as $appointment): ?>


                                <tr
                                    data-status="<?php
                                        echo e(
                                            strtolower(
                                                $appointment['status']
                                            )
                                        );
                                    ?>"
                                >


                                    <!-- DATE -->

                                    <td>

                                        <?php

                                        echo e(
                                            date(
                                                'M j, Y',
                                                strtotime(
                                                    $appointment[
                                                        'appointment_date'
                                                    ]
                                                )
                                            )
                                        );

                                        ?>

                                    </td>


                                    <!-- TIME -->

                                    <td>

                                        <?php

                                        echo e(
                                            date(
                                                'H:i',
                                                strtotime(
                                                    $appointment[
                                                        'appointment_time'
                                                    ]
                                                )
                                            )
                                        );

                                        ?>

                                    </td>


                                    <!-- PATIENT -->

                                    <td>

                                        <?php

                                        echo e(
                                            $appointment[
                                                'patient_name'
                                            ] ?? 'Unknown Patient'
                                        );

                                        ?>

                                    </td>


                                    <!-- DOCTOR -->

                                    <td>

                                        <?php

                                        echo e(
                                            $appointment[
                                                'doctor_name'
                                            ] ?? 'Unknown Doctor'
                                        );

                                        ?>

                                    </td>


                                    <!-- TYPE -->

                                    <td>

                                        <?php

                                        echo e(
                                            $appointment['type']
                                        );

                                        ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <strong>

                                            <?php

                                            echo e(
                                                $appointment['status']
                                            );

                                            ?>

                                        </strong>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>


                                        <div class="action-cell">


                                            <!-- UPDATE BUTTON -->

                                            <button
                                                type="button"
                                                class="btn-update"
                                                onclick="openUpdateModal(
                                                    <?php echo (int)$appointment['appointment_id']; ?>,
                                                    <?php echo (int)$appointment['patient_user_id']; ?>,
                                                    <?php echo (int)$appointment['doctor_user_id']; ?>,
                                                    '<?php echo e($appointment['appointment_date']); ?>',
                                                    '<?php echo e($appointment['appointment_time']); ?>',
                                                    '<?php echo e($appointment['type']); ?>',
                                                    '<?php echo e($appointment['status']); ?>'
                                                )"
                                                title="Update Appointment"
                                            >

                                                <span
                                                    class="update-icon"
                                                ></span>

                                                Update

                                            </button>


                                            <!-- DELETE BUTTON
                                                 UNCHANGED
                                            -->

                                            <form
                                                method="POST"
                                                action="medical_actions.php"
                                                onsubmit="return confirmDelete();"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete_appointment"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="appointment_id"
                                                    value="<?php
                                                        echo e(
                                                            $appointment[
                                                                'appointment_id'
                                                            ]
                                                        );
                                                    ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="btn-delete"
                                                    title="Delete Appointment"
                                                >

                                                    <span
                                                        class="delete-icon"
                                                    ></span>

                                                    Delete

                                                </button>


                                            </form>


                                        </div>


                                    </td>


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


<!-- =========================================================
     CREATE APPOINTMENT MODAL
========================================================= -->

<div
    class="modal"
    id="createAppointmentModal"
>


    <div class="modal-box">


        <div class="modal-header">

            <h2>
                Create Appointment
            </h2>


            <button
                type="button"
                class="close-btn"
                id="closeCreateModal"
            >
                &times;
            </button>

        </div>


        <form
            method="POST"
            action="medical_actions.php"
        >


            <input
                type="hidden"
                name="action"
                value="create_appointment"
            >


            <!-- PATIENT -->

            <div class="form-group">

                <label>
                    Patient
                </label>


                <select
                    name="patient_id"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select Patient
                    </option>


                    <?php foreach ($patients as $patient): ?>

                        <option
                            value="<?php
                                echo e(
                                    $patient['user_id']
                                );
                            ?>"
                        >

                            <?php

                            echo e(
                                $patient['first_name']
                                . ' '
                                . $patient['last_name']
                            );

                            ?>

                        </option>

                    <?php endforeach; ?>


                </select>

            </div>


            <!-- DOCTOR -->

            <div class="form-group">

                <label>
                    Doctor
                </label>


                <select
                    name="doctor_id"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select Doctor
                    </option>


                    <?php foreach ($doctors as $doctor): ?>

                        <option
                            value="<?php
                                echo e(
                                    $doctor['user_id']
                                );
                            ?>"
                        >

                            <?php

                            echo e(
                                'Dr. '
                                . $doctor['first_name']
                                . ' '
                                . $doctor['last_name']
                            );

                            ?>

                        </option>

                    <?php endforeach; ?>


                </select>

            </div>


            <!-- DATE / TIME -->

            <div class="form-row">


                <div class="form-group">

                    <label>
                        Date
                    </label>


                    <input
                        type="date"
                        name="appointment_date"
                        class="form-control"
                        min="<?php echo date('Y-m-d'); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Time
                    </label>


                    <input
                        type="time"
                        name="appointment_time"
                        class="form-control"
                        required
                    >

                </div>


            </div>


            <!-- TYPE -->

            <div class="form-group">

                <label>
                    Appointment Type
                </label>


                <select
                    name="type"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select Type
                    </option>

                    <option value="Consultation">
                        Consultation
                    </option>

                    <option value="Follow-up">
                        Follow-up
                    </option>

                    <option value="Treatment">
                        Treatment
                    </option>

                    <option value="Check-up">
                        Check-up
                    </option>

                    <option value="Screening">
                        Screening
                    </option>

                </select>

            </div>


            <!-- STATUS -->

            <div class="form-group">

                <label>
                    Status
                </label>


                <select
                    name="status"
                    class="form-control"
                    required
                >

                    <option value="pending">
                        Pending
                    </option>

                    <option value="confirmed">
                        Confirmed
                    </option>

                    <option value="cancelled">
                        Cancelled
                    </option>

                    <option value="completed">
                        Completed
                    </option>

                </select>

            </div>


            <div class="modal-actions">


                <button
                    type="button"
                    class="btn-cancel"
                    id="cancelCreateModal"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="btn-save"
                >
                    Create Appointment
                </button>


            </div>


        </form>


    </div>


</div>


<!-- =========================================================
     UPDATE APPOINTMENT MODAL
========================================================= -->

<div
    class="modal"
    id="updateAppointmentModal"
>


    <div class="modal-box">


        <div class="modal-header">

            <h2>
                Update Appointment
            </h2>


            <button
                type="button"
                class="close-btn"
                id="closeUpdateModal"
            >
                &times;
            </button>

        </div>


        <form
            method="POST"
            action="medical_actions.php"
        >


            <input
                type="hidden"
                name="action"
                value="update_appointment"
            >


            <input
                type="hidden"
                name="appointment_id"
                id="update_appointment_id"
            >


            <!-- PATIENT -->

            <div class="form-group">

                <label>
                    Patient
                </label>


                <select
                    name="patient_id"
                    id="update_patient_id"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select Patient
                    </option>


                    <?php foreach ($patients as $patient): ?>

                        <option
                            value="<?php
                                echo e(
                                    $patient['user_id']
                                );
                            ?>"
                        >

                            <?php

                            echo e(
                                $patient['first_name']
                                . ' '
                                . $patient['last_name']
                            );

                            ?>

                        </option>

                    <?php endforeach; ?>


                </select>

            </div>


            <!-- DOCTOR -->

            <div class="form-group">

                <label>
                    Doctor
                </label>


                <select
                    name="doctor_id"
                    id="update_doctor_id"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select Doctor
                    </option>


                    <?php foreach ($doctors as $doctor): ?>

                        <option
                            value="<?php
                                echo e(
                                    $doctor['user_id']
                                );
                            ?>"
                        >

                            <?php

                            echo e(
                                'Dr. '
                                . $doctor['first_name']
                                . ' '
                                . $doctor['last_name']
                            );

                            ?>

                        </option>

                    <?php endforeach; ?>


                </select>

            </div>


            <!-- DATE / TIME -->

            <div class="form-row">


                <div class="form-group">

                    <label>
                        Date
                    </label>


                    <input
                        type="date"
                        name="appointment_date"
                        id="update_appointment_date"
                        class="form-control"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Time
                    </label>


                    <input
                        type="time"
                        name="appointment_time"
                        id="update_appointment_time"
                        class="form-control"
                        required
                    >

                </div>


            </div>


            <!-- TYPE -->

            <div class="form-group">

                <label>
                    Appointment Type
                </label>


                <select
                    name="type"
                    id="update_type"
                    class="form-control"
                    required
                >

                    <option value="">
                        Select Type
                    </option>

                    <option value="Consultation">
                        Consultation
                    </option>

                    <option value="Follow-up">
                        Follow-up
                    </option>

                    <option value="Treatment">
                        Treatment
                    </option>

                    <option value="Check-up">
                        Check-up
                    </option>

                    <option value="Screening">
                        Screening
                    </option>

                </select>

            </div>


            <!-- STATUS -->

            <div class="form-group">

                <label>
                    Status
                </label>


                <select
                    name="status"
                    id="update_status"
                    class="form-control"
                    required
                >

                    <option value="pending">
                        Pending
                    </option>

                    <option value="confirmed">
                        Confirmed
                    </option>

                    <option value="cancelled">
                        Cancelled
                    </option>

                    <option value="completed">
                        Completed
                    </option>

                </select>

            </div>


            <div class="modal-actions">


                <button
                    type="button"
                    class="btn-cancel"
                    id="cancelUpdateModal"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="btn-save"
                >
                    Update Appointment
                </button>


            </div>


        </form>


    </div>


</div>


<script>


/* =========================================================
   SIDEBAR
========================================================= */

const toggleBtn =
    document.getElementById('sidebarToggle');

const sidebar =
    document.getElementById('sidebar');


if (toggleBtn && sidebar) {

    toggleBtn.addEventListener(
        'click',
        function () {

            sidebar.classList.toggle('open');

        }
    );

}


/* =========================================================
   DELETE CONFIRMATION
========================================================= */

function confirmDelete()
{
    return confirm(
        'Are you sure you want to delete this appointment?'
    );
}


/* =========================================================
   STATUS FILTER
========================================================= */

const statusFilter =
    document.getElementById('statusFilter');


const appointmentRows =
    document.querySelectorAll(
        '#appointmentTableBody tr[data-status]'
    );


if (statusFilter) {

    statusFilter.addEventListener(
        'change',
        function () {

            const selected =
                this.value.toLowerCase();


            appointmentRows.forEach(
                function (row) {

                    if (
                        selected === 'all' ||
                        row.dataset.status === selected
                    ) {

                        row.style.display = '';

                    } else {

                        row.style.display = 'none';

                    }

                }
            );

        }
    );

}


/* =========================================================
   CREATE MODAL
========================================================= */

const createModal =
    document.getElementById(
        'createAppointmentModal'
    );


const openCreateModal =
    document.getElementById(
        'openCreateModal'
    );


const closeCreateModal =
    document.getElementById(
        'closeCreateModal'
    );


const cancelCreateModal =
    document.getElementById(
        'cancelCreateModal'
    );


if (openCreateModal) {

    openCreateModal.addEventListener(
        'click',
        function () {

            createModal.classList.add('show');

        }
    );

}


if (closeCreateModal) {

    closeCreateModal.addEventListener(
        'click',
        function () {

            createModal.classList.remove('show');

        }
    );

}


if (cancelCreateModal) {

    cancelCreateModal.addEventListener(
        'click',
        function () {

            createModal.classList.remove('show');

        }
    );

}


/* =========================================================
   UPDATE MODAL
========================================================= */

const updateModal =
    document.getElementById(
        'updateAppointmentModal'
    );


const closeUpdateModal =
    document.getElementById(
        'closeUpdateModal'
    );


const cancelUpdateModal =
    document.getElementById(
        'cancelUpdateModal'
    );


function openUpdateModal(
    appointmentId,
    patientId,
    doctorId,
    appointmentDate,
    appointmentTime,
    type,
    status
) {

    document.getElementById(
        'update_appointment_id'
    ).value = appointmentId;


    document.getElementById(
        'update_patient_id'
    ).value = patientId;


    document.getElementById(
        'update_doctor_id'
    ).value = doctorId;


    document.getElementById(
        'update_appointment_date'
    ).value = appointmentDate;


    document.getElementById(
        'update_appointment_time'
    ).value = appointmentTime;


    document.getElementById(
        'update_type'
    ).value = type;


    document.getElementById(
        'update_status'
    ).value = status;


    updateModal.classList.add('show');

}


if (closeUpdateModal) {

    closeUpdateModal.addEventListener(
        'click',
        function () {

            updateModal.classList.remove('show');

        }
    );

}


if (cancelUpdateModal) {

    cancelUpdateModal.addEventListener(
        'click',
        function () {

            updateModal.classList.remove('show');

        }
    );

}


/* =========================================================
   CLOSE MODALS WHEN CLICK OUTSIDE
========================================================= */

window.addEventListener(
    'click',
    function (event) {

        if (event.target === createModal) {

            createModal.classList.remove('show');

        }


        if (event.target === updateModal) {

            updateModal.classList.remove('show');

        }

    }
);

</script>


</body>

</html>