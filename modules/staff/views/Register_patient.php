<?php
session_start();

// DB connection eka
require_once __DIR__ . '/../../../config/database.php';

// medical_actions.php eken ena error message + old form data
$error = $_SESSION['error'] ?? '';
$old   = $_SESSION['old'] ?? [];

unset($_SESSION['error'], $_SESSION['old']);

// Input eke value eka ayeth pennanna
function old($name, $default = '')
{
    global $old;
    return htmlspecialchars($old[$name] ?? $default);
}

// Select eke option eka ayeth select karanna
function selected($name, $value)
{
    global $old;
    return (($old[$name] ?? '') === (string)$value) ? 'selected' : '';
}

// Doctor table eken doctors load karanna
$doctors = [];

try {

    $res = $conn->query(
        "SELECT user_id, first_name, last_name, specialization
         FROM `Doctor`
         ORDER BY first_name, last_name"
    );

    while ($row = $res->fetch_assoc()) {
        $doctors[] = $row;
    }

} catch (Throwable $e) {

    $doctors = [];
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

    <title>Register Patient — Cancer Care</title>

    <link
        rel="stylesheet"
        href="../../../public/css/styles.css"
    >

    <link
        rel="stylesheet"
        href="../../../public/css/modules.css"
    >

    <!-- =====================================================
         SIDEBAR SVG ICONS
    ====================================================== -->

    <style>

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

        /* Dashboard */
        .icon-dashboard {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='3' y='3' width='7' height='9' rx='1.5'/><rect x='14' y='3' width='7' height='5' rx='1.5'/><rect x='14' y='12' width='7' height='9' rx='1.5'/><rect x='3' y='16' width='7' height='5' rx='1.5'/></svg>");
        }

        /* Patients */
        .icon-profile {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='8' r='4'/><path d='M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7'/></svg>");
        }

        /* Register Patient */
        .icon-register {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M15 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'/><circle cx='8.5' cy='7' r='4'/><path d='M20 8v6M17 11h6'/></svg>");
        }

        /* Appointments */
        .icon-appointments {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='3' y='5' width='18' height='16' rx='2'/><path d='M16 3v4M8 3v4M3 10h18'/></svg>");
        }

        /* Medical Reports */
        .icon-records {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M7 3h7l4 4v14H7z'/><path d='M9 12h6M9 16h6M9 8h2'/></svg>");
        }

        /* Doctor Availability */
        .icon-doctor {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='7' r='4'/><path d='M5 21v-2a7 7 0 0 1 14 0v2'/><path d='M17 16l2 2 4-4'/></svg>");
        }

        /* Benefactor */
        .icon-benefactor {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='9' cy='7' r='4'/><path d='M2 21v-2a7 7 0 0 1 14 0v2'/><path d='M16 11h6M19 8v6'/></svg>");
        }

    </style>

</head>


<body>

<div class="app">

    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >

        <!-- SIDEBAR HEADER -->

        <div class="sidebar-header">

            <div class="logo">

                <div class="logo-icon">
                    ❤
                </div>

                <div>

                    Cancer care

                    <span class="logo-sub">
                        CANCER PATIENT CARE
                    </span>

                </div>

            </div>

        </div>


        <!-- =================================================
             NAVIGATION
        ================================================== -->

        <nav class="sidebar-nav">

            <div class="nav-section">
                NAVIGATION
            </div>


            <!-- DASHBOARD -->

            <a
                href="staff_dashboard.php"
                class="nav-item"
            >

                <span class="icon icon-dashboard"></span>

                Dashboard

            </a>


            <!-- PATIENTS -->

            <a
                href="staff_patient.php"
                class="nav-item"
            >

                <span class="icon icon-profile"></span>

                Patients

            </a>


            <!-- REGISTER PATIENT -->

            <a
                href="Register_patient.php"
                class="nav-item active"
            >

                <span class="icon icon-register"></span>

                Register Patient

            </a>


            <!-- APPOINTMENTS -->

            <a
                href="./staff_appointment.php"
                class="nav-item"
            >

                <span class="icon icon-appointments"></span>

                Appointments

            </a>


            <!-- MEDICAL REPORTS -->

            <a
                href="./medical_reports.php"
                class="nav-item"
            >

                <span class="icon icon-records"></span>

                Medical Reports

            </a>


            <!-- DOCTOR AVAILABILITY -->

            <a
                href="./doctor_availability.php"
                class="nav-item"
            >

                <span class="icon icon-doctor"></span>

                Doctor Availability

            </a>


            <!-- BENEFACTOR -->

            <a
                href="./benefactor.php"
                class="nav-item"
            >

                <span class="icon icon-benefactor"></span>

                Benefactor

            </a>

            <a
                href="./staff_profile.php"
                class="nav-item"
            >
                <span class="icon icon-profile"></span>
                My Profile
            </a>

        </nav>


        <!-- =================================================
             USER PROFILE FOOTER
        ================================================== -->

        <div
            class="sidebar-footer"
            style="
                padding:15px;
                border-top:1px solid rgba(255,255,255,0.1);
                display:flex;
                align-items:center;
                gap:10px;
                margin-top:auto;
            "
        >

            <div
                style="
                    background:#0066ff;
                    color:white;
                    width:35px;
                    height:35px;
                    border-radius:50%;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-weight:bold;
                    font-size:14px;
                "
            >
                NP
            </div>


            <div>

                <div
                    style="
                        color:white;
                        font-size:14px;
                        font-weight:500;
                    "
                >
                    Nimali Perera
                </div>


                <div
                    style="
                        color:#8a99ad;
                        font-size:12px;
                    "
                >
                    Medical Staff
                </div>

            </div>

        </div>

    </aside>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main">

        <!-- TOP BAR -->

        <header class="topbar">

            <div class="topbar-left">

                <h1>
                    Register Patient
                </h1>

                <p>
                    <?= date('l, F j, Y') ?>
                </p>

            </div>


            <div
                class="topbar-right"
                style="
                    display:flex;
                    align-items:center;
                    gap:15px;
                "
            >

                <!-- SIGN OUT -->

                <a
                    href="../../../logout.php"
                    class="btn btn-outline"
                    style="
                        padding:6px 14px;
                        font-size:13px;
                        text-decoration:none;
                        color:inherit;
                        border:1px solid #ccc;
                        border-radius:4px;
                    "
                >
                    Sign Out
                </a>

            </div>

        </header>


        <!-- =================================================
             CONTENT
        ================================================== -->

        <div class="content">

            <div class="form-card">


                <!-- =================================================
                     ERROR MESSAGE
                ================================================== -->

                <?php if (!empty($error)): ?>

                    <div
                        style="
                            background:#ffe5e5;
                            color:#b00020;
                            padding:10px 14px;
                            border-radius:6px;
                            margin-bottom:15px;
                        "
                    >

                        <?= htmlspecialchars($error) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     REGISTER FORM
                ================================================== -->

                <form
                    method="POST"
                    action="medical_actions.php"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="register_patient"
                    >


                    <!-- =================================================
                         PERSONAL INFORMATION
                    ================================================== -->

                    <fieldset class="form-section">

                        <legend>
                            Personal Information
                        </legend>


                        <div class="form-grid cols-3">


                            <!-- FIRST NAME -->

                            <div class="form-group">

                                <label>
                                    First Name
                                    <span class="req">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="first_name"
                                    class="form-input"
                                    value="<?= old('first_name') ?>"
                                    required
                                >

                            </div>


                            <!-- LAST NAME -->

                            <div class="form-group">

                                <label>
                                    Last Name
                                    <span class="req">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="last_name"
                                    class="form-input"
                                    value="<?= old('last_name') ?>"
                                    required
                                >

                            </div>


                            <!-- PASSWORD -->

                            <div class="form-group">

                                <label>
                                    Password
                                    <span class="req">*</span>
                                </label>

                                <input
                                    type="password"
                                    name="password"
                                    class="form-input"
                                    value="Patient@123"
                                    required
                                >

                            </div>


                            <!-- DATE OF BIRTH -->

                            <div class="form-group">

                                <label>
                                    Date of Birth
                                    <span class="req">*</span>
                                </label>

                                <input
                                    type="date"
                                    name="dob"
                                    class="form-input"
                                    max="<?= date('Y-m-d') ?>"
                                    value="<?= old('dob') ?>"
                                    required
                                >

                            </div>


                            <!-- GENDER -->

                            <div class="form-group">

                                <label>
                                    Gender
                                    <span class="req">*</span>
                                </label>

                                <select
                                    name="gender"
                                    class="form-input"
                                    required
                                >

                                    <option value="">
                                        Select
                                    </option>

                                    <option
                                        value="male"
                                        <?= selected('gender', 'male') ?>
                                    >
                                        Male
                                    </option>

                                    <option
                                        value="female"
                                        <?= selected('gender', 'female') ?>
                                    >
                                        Female
                                    </option>

                                    <option
                                        value="other"
                                        <?= selected('gender', 'other') ?>
                                    >
                                        Other
                                    </option>

                                </select>

                            </div>


                            <!-- NIC -->

                            <div class="form-group">

                                <label>
                                    NIC
                                    <span class="req">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="nic"
                                    class="form-input"
                                    value="<?= old('nic') ?>"
                                    required
                                >

                            </div>


                            <!-- PHONE -->

                            <div class="form-group">

                                <label>
                                    Phone
                                    <span class="req">*</span>
                                </label>

                                <input
                                    type="tel"
                                    name="phone"
                                    class="form-input"
                                    value="<?= old('phone') ?>"
                                    required
                                >

                            </div>


                            <!-- EMAIL -->

                            <div class="form-group">

                                <label>
                                    Email
                                    <span class="req">*</span>
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-input"
                                    value="<?= old('email') ?>"
                                    required
                                >

                            </div>


                            <!-- ADDRESS -->

                            <div class="form-group">

                                <label>
                                    Address
                                </label>

                                <input
                                    type="text"
                                    name="address"
                                    class="form-input"
                                    value="<?= old('address') ?>"
                                >

                            </div>


                            <!-- CITY -->

                            <div class="form-group">

                                <label>
                                    City
                                </label>

                                <input
                                    type="text"
                                    name="city"
                                    class="form-input"
                                    value="<?= old('city') ?>"
                                >

                            </div>


                            <!-- BLOOD GROUP -->

                            <div class="form-group">

                                <label>
                                    Blood Group
                                </label>

                                <select
                                    name="blood_group"
                                    class="form-input"
                                >

                                    <option
                                        value="O+"
                                        <?= selected('blood_group', 'O+') ?>
                                    >
                                        O+
                                    </option>

                                    <option
                                        value="O-"
                                        <?= selected('blood_group', 'O-') ?>
                                    >
                                        O-
                                    </option>

                                    <option
                                        value="A+"
                                        <?= selected('blood_group', 'A+') ?>
                                    >
                                        A+
                                    </option>

                                    <option
                                        value="A-"
                                        <?= selected('blood_group', 'A-') ?>
                                    >
                                        A-
                                    </option>

                                    <option
                                        value="B+"
                                        <?= selected('blood_group', 'B+') ?>
                                    >
                                        B+
                                    </option>

                                    <option
                                        value="B-"
                                        <?= selected('blood_group', 'B-') ?>
                                    >
                                        B-
                                    </option>

                                    <option
                                        value="AB+"
                                        <?= selected('blood_group', 'AB+') ?>
                                    >
                                        AB+
                                    </option>

                                    <option
                                        value="AB-"
                                        <?= selected('blood_group', 'AB-') ?>
                                    >
                                        AB-
                                    </option>

                                </select>

                            </div>


                            <!-- EMERGENCY CONTACT -->

                            <div class="form-group">

                                <label>
                                    Emergency Contact
                                </label>

                                <input
                                    type="tel"
                                    name="emergency_contact"
                                    class="form-input"
                                    value="<?= old('emergency_contact') ?>"
                                >

                            </div>

                        </div>

                    </fieldset>


                    <!-- =================================================
                         MEDICAL INFORMATION
                    ================================================== -->

                    <fieldset class="form-section">

                        <legend>
                            Medical Information
                        </legend>


                        <div class="form-grid cols-3">


                            <!-- CANCER TYPE -->

                            <div class="form-group">

                                <label>
                                    Cancer Type
                                    <span class="req">*</span>
                                </label>

                                <select
                                    name="cancer_type"
                                    class="form-input"
                                    required
                                >

                                    <option value="">
                                        Select
                                    </option>

                                    <option
                                        value="breast"
                                        <?= selected('cancer_type', 'breast') ?>
                                    >
                                        Breast
                                    </option>

                                    <option
                                        value="lung"
                                        <?= selected('cancer_type', 'lung') ?>
                                    >
                                        Lung
                                    </option>

                                    <option
                                        value="leukemia"
                                        <?= selected('cancer_type', 'leukemia') ?>
                                    >
                                        Leukemia
                                    </option>

                                    <option
                                        value="lymphoma"
                                        <?= selected('cancer_type', 'lymphoma') ?>
                                    >
                                        Lymphoma
                                    </option>

                                    <option
                                        value="colon"
                                        <?= selected('cancer_type', 'colon') ?>
                                    >
                                        Colon
                                    </option>

                                    <option
                                        value="prostate"
                                        <?= selected('cancer_type', 'prostate') ?>
                                    >
                                        Prostate
                                    </option>

                                    <option
                                        value="other"
                                        <?= selected('cancer_type', 'other') ?>
                                    >
                                        Other
                                    </option>

                                </select>

                            </div>


                            <!-- STAGE -->

                            <div class="form-group">

                                <label>
                                    Stage
                                    <span class="req">*</span>
                                </label>

                                <select
                                    name="stage"
                                    class="form-input"
                                    required
                                >

                                    <option value="">
                                        Select
                                    </option>

                                    <option
                                        value="I"
                                        <?= selected('stage', 'I') ?>
                                    >
                                        I
                                    </option>

                                    <option
                                        value="II"
                                        <?= selected('stage', 'II') ?>
                                    >
                                        II
                                    </option>

                                    <option
                                        value="III"
                                        <?= selected('stage', 'III') ?>
                                    >
                                        III
                                    </option>

                                    <option
                                        value="IV"
                                        <?= selected('stage', 'IV') ?>
                                    >
                                        IV
                                    </option>

                                </select>

                            </div>


                            <!-- ASSIGNED DOCTOR -->

                            <div class="form-group">

                                <label>
                                    Assigned Doctor
                                    <span class="req">*</span>
                                </label>

                                <select
                                    name="assigned_doctor"
                                    class="form-input"
                                    required
                                >

                                    <?php if (empty($doctors)): ?>

                                        <option value="">
                                            No doctors found in database
                                        </option>

                                    <?php else: ?>

                                        <option value="">
                                            Select Doctor
                                        </option>

                                        <?php foreach ($doctors as $d): ?>

                                            <option
                                                value="<?= (int)$d['user_id'] ?>"
                                                <?= selected(
                                                    'assigned_doctor',
                                                    $d['user_id']
                                                ) ?>
                                            >

                                                Dr.
                                                <?= htmlspecialchars(
                                                    $d['first_name']
                                                    . ' '
                                                    . $d['last_name']
                                                ) ?>

                                                <?php if (!empty($d['specialization'])): ?>

                                                    —
                                                    <?= htmlspecialchars(
                                                        $d['specialization']
                                                    ) ?>

                                                <?php endif; ?>

                                            </option>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                </select>

                            </div>


                            <!-- TREATMENT PLAN -->

                            <div class="form-group">

                                <label>
                                    Treatment Plan
                                </label>

                                <input
                                    type="text"
                                    name="treatment_plan"
                                    class="form-input"
                                    placeholder="e.g. Chemotherapy Cycle 1"
                                    value="<?= old('treatment_plan') ?>"
                                >

                            </div>


                            <!-- ALLERGIES -->

                            <div class="form-group">

                                <label>
                                    Allergies
                                </label>

                                <input
                                    type="text"
                                    name="allergies"
                                    class="form-input"
                                    value="<?= old('allergies', 'None') ?>"
                                >

                            </div>


                            <!-- STATUS -->

                            <div class="form-group">

                                <label>
                                    Status
                                </label>

                                <select
                                    name="status"
                                    class="form-input"
                                >

                                    <option
                                        value="active"
                                        <?= selected('status', 'active') ?>
                                    >
                                        Active
                                    </option>

                                    <option
                                        value="critical"
                                        <?= selected('status', 'critical') ?>
                                    >
                                        Critical
                                    </option>

                                    <option
                                        value="stable"
                                        <?= selected('status', 'stable') ?>
                                    >
                                        Stable
                                    </option>

                                    <option
                                        value="scheduled"
                                        <?= selected('status', 'scheduled') ?>
                                    >
                                        Scheduled
                                    </option>

                                </select>

                            </div>

                        </div>

                    </fieldset>


                    <!-- =================================================
                         BUTTONS
                    ================================================== -->

                    <div
                        class="form-actions"
                        style="margin-top:20px;"
                    >

                        <!-- CANCEL -->

                        <button
                            type="button"
                            class="btn btn-outline"
                            onclick="window.location.href='staff_patient.php'"
                        >
                            Cancel
                        </button>


                        <!-- REGISTER -->

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Register Patient
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </main>

</div>

</body>
</html>