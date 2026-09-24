<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config/database.php';

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$msg = $_GET['msg'] ?? '';
$reason = $_GET['reason'] ?? '';

/*
|--------------------------------------------------------------------------
| Patient + Doctor
|--------------------------------------------------------------------------
*/

$baseSql = "SELECT p.*,
                   CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                   CONCAT('Dr. ', d.first_name, ' ', d.last_name) AS doctor_name
            FROM Patient p
            LEFT JOIN Doctor d
                ON d.user_id = p.assigned_doctor";

/*
|--------------------------------------------------------------------------
| Patient Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $stmt = $conn->prepare(
        $baseSql . "
        WHERE CONCAT(p.first_name, ' ', p.last_name)
        LIKE CONCAT('%', ?, '%')
        ORDER BY p.first_name ASC, p.last_name ASC"
    );

    if (!$stmt) {
        die("Query Preparation Failed: " . $conn->error);
    }

    $stmt->bind_param("s", $search);
    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $result = $conn->query(
        $baseSql . "
        ORDER BY p.first_name ASC, p.last_name ASC"
    );

    if (!$result) {
        die("Query Failed: " . $conn->error);
    }
}

/*
|--------------------------------------------------------------------------
| Doctors
|--------------------------------------------------------------------------
*/

$doctors = [];

try {

    $dres = $conn->query(
        "SELECT user_id, first_name, last_name
         FROM Doctor
         ORDER BY first_name, last_name"
    );

    while ($d = $dres->fetch_assoc()) {
        $doctors[] = $d;
    }

} catch (Throwable $ex) {

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

    <title>Patients — Cancer Care</title>

    <link
        rel="stylesheet"
        href="../../../public/css/styles.css"
    >

    <link
        rel="stylesheet"
        href="../../../public/css/modules.css"
    >

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

        .search-info {
            margin: 15px 0;
            padding: 12px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #475569;
        }

        .clear-search {
            margin-left: 10px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        #patientDetailsCard {
            display: none;
            margin-top: 20px;
        }

        .alert {
            margin: 15px 0;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 600;
        }

        .alert-ok {
            background: #dcfce7;
            color: #166534;
        }

        .alert-err {
            background: #fee2e2;
            color: #991b1b;
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

        <div class="sidebar-header">

            <div class="logo">

                <div class="logo-icon">
                    ❤
                </div>

                <div>

                    Cancer Care

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


            <a
                href="staff_dashboard.php"
                class="nav-item"
            >

                <span class="icon icon-dashboard"></span>

                Dashboard

            </a>


            <a
                href="staff_patient.php"
                class="nav-item active"
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
                class="nav-item"
            >

                <span class="icon icon-appointments"></span>

                Appointments

            </a>


            <a
                href="medical_reports.html"
                class="nav-item"
            >

                <span class="icon icon-records"></span>

                Medical Reports

            </a>


            <a
                href="doctor_availability.html"
                class="nav-item"
            >

                <span class="icon icon-doctor"></span>

                Doctor Availability

            </a>


            <a
                href="benefactor.html"
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
    ====================================================== -->

    <main class="main">

        <!-- TOP BAR -->

        <header class="topbar">

            <div class="topbar-left">

                <h1>
                    Patients
                </h1>

                <p>
                    <?php echo date("l, F j, Y"); ?>
                </p>

            </div>


            <div class="topbar-right">

                <form
                    method="GET"
                    action="staff_patient.php"
                    class="search-box"
                >

                    <input
                        type="text"
                        name="search"
                        placeholder="Search patient by full name..."
                        value="<?php echo e($search); ?>"
                    >

                </form>


                <button
                    type="button"
                    class="icon-btn notif-btn"
                    title="Notifications"
                >
                    🔔
                </button>


                <button
                    type="button"
                    class="btn btn-outline"
                >
                    Sign Out
                </button>


                <button
                    type="button"
                    class="sidebar-toggle"
                    id="sidebarToggle"
                >
                    ☰
                </button>

            </div>

        </header>


        <!-- =====================================================
             CONTENT
        ====================================================== -->

        <div class="content">

            <!-- SEARCH -->

            <div class="page-toolbar">

                <form
                    method="GET"
                    action="staff_patient.php"
                    class="search-inline"
                    style="width:100%;max-width:400px;"
                >

                    <input
                        type="text"
                        name="search"
                        class="form-input"
                        placeholder="Search patient by full name..."
                        value="<?php echo e($search); ?>"
                    >

                </form>


                <a
                    href="Register_patient.php"
                    class="btn btn-primary"
                >
                    + Register Patient
                </a>

            </div>


            <!-- MESSAGES -->

            <?php if ($msg === 'registered'): ?>

                <div class="alert alert-ok">
                    Patient registered successfully.
                </div>

            <?php elseif ($msg === 'updated'): ?>

                <div class="alert alert-ok">
                    Patient updated successfully.
                </div>

            <?php elseif ($msg === 'error'): ?>

                <div class="alert alert-err">

                    <?php

                    echo $reason !== ''
                        ? e($reason)
                        : 'Something went wrong.';

                    ?>

                </div>

            <?php endif; ?>


            <!-- SEARCH INFO -->

            <?php if ($search !== ''): ?>

                <div class="search-info">

                    Showing patients matching:

                    <strong>
                        "<?php echo e($search); ?>"
                    </strong>


                    <a
                        href="staff_patient.php"
                        class="clear-search"
                    >
                        Clear Search
                    </a>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 PATIENT TABLE
            ================================================== -->

            <div class="widget">

                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    PATIENT
                                </th>

                                <th>
                                    CANCER TYPE
                                </th>

                                <th>
                                    STAGE
                                </th>

                                <th>
                                    STATUS
                                </th>

                                <th>
                                    DOCTOR
                                </th>

                                <th>
                                    ACTIONS
                                </th>

                            </tr>

                        </thead>


                        <tbody id="patientTableBody">

                        <?php if ($result && $result->num_rows > 0): ?>

                            <?php while ($row = $result->fetch_assoc()): ?>

                                <?php

                                $fullName =
                                    $row['full_name'] ?? '';

                                $initials = '';

                                foreach (
                                    preg_split(
                                        '/\s+/',
                                        trim($fullName)
                                    ) as $w
                                ) {

                                    if ($w !== '') {

                                        $initials .= strtoupper(
                                            mb_substr(
                                                $w,
                                                0,
                                                1
                                            )
                                        );

                                    }

                                }

                                $avatar =
                                    mb_substr(
                                        $initials,
                                        0,
                                        2
                                    );

                                $patientId =
                                    $row['user_id'] ?? '';

                                $doctorVal =
                                    $row['doctor_name'] ?? '';

                                $status =
                                    $row['status'] ?? '';

                                $statusCls =
                                    strtolower(
                                        preg_replace(
                                            '/[^a-zA-Z0-9_-]/',
                                            '',
                                            $status
                                        )
                                    );


                                $data = e(
                                    json_encode(
                                        [
                                            'id' =>
                                                $patientId,

                                            'first_name' =>
                                                $row['first_name'] ?? '',

                                            'last_name' =>
                                                $row['last_name'] ?? '',

                                            'full_name' =>
                                                $fullName,

                                            'age' =>
                                                $row['age'] ?? '',

                                            'cancer_type' =>
                                                $row['cancer_type'] ?? '',

                                            'stage' =>
                                                $row['stage'] ?? '',

                                            'status' =>
                                                $status,

                                            'doctor_id' =>
                                                $row['assigned_doctor'] ?? '',
                                        ]
                                    )
                                );

                                ?>


                                <tr
                                    id="patient-row-<?php echo e($patientId); ?>"
                                >

                                    <!-- PATIENT -->

                                    <td>

                                        <div class="patient-cell">

                                            <div class="patient-avatar">

                                                <?php
                                                echo e($avatar);
                                                ?>

                                            </div>


                                            <div>

                                                <strong>

                                                    <?php
                                                    echo e($fullName);
                                                    ?>

                                                </strong>


                                                <span>

                                                    <?php
                                                    echo e($patientId);
                                                    ?>

                                                    · Age

                                                    <?php
                                                    echo e(
                                                        $row['age'] ?? ''
                                                    );
                                                    ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- CANCER TYPE -->

                                    <td>

                                        <?php

                                        echo e(
                                            $row['cancer_type'] ?? ''
                                        );

                                        ?>

                                    </td>


                                    <!-- STAGE -->

                                    <td>

                                        <span class="stage-pill">

                                            <?php

                                            echo e(
                                                $row['stage'] ?? ''
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="status-pill <?php echo e($statusCls); ?>"
                                        >

                                            <?php

                                            echo e($status);

                                            ?>

                                        </span>

                                    </td>


                                    <!-- DOCTOR -->

                                    <td>

                                        <?php

                                        echo e($doctorVal);

                                        ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <div
                                            class="action-cell"
                                            style="
                                                display:flex;
                                                gap:6px;
                                                align-items:center;
                                            "
                                        >

                                            <button
                                                type="button"
                                                class="btn btn-outline btn-sm"
                                                data-p="<?php echo $data; ?>"
                                                onclick="openCard(this)"
                                            >
                                                View
                                            </button>

                                        </div>

                                    </td>

                                </tr>


                            <?php endwhile; ?>


                        <?php else: ?>

                            <tr id="noPatientRow">

                                <td
                                    colspan="6"
                                    style="
                                        text-align:center;
                                        padding:30px;
                                        color:#666;
                                    "
                                >

                                    <?php if ($search !== ''): ?>

                                        No patient found with full name
                                        containing

                                        <strong>
                                            "<?php echo e($search); ?>"
                                        </strong>


                                    <?php else: ?>

                                        No patients found.

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>


            <!-- =================================================
                 PATIENT DETAILS CARD
            ================================================== -->

            <form
                class="form-card"
                id="patientDetailsCard"
                method="POST"
                action="medical_actions.php"
            >

                <input
                    type="hidden"
                    name="id"
                    id="fId"
                >


                <div class="form-card-header">

                    <div>

                        <h2 id="cardTitle">
                            Patient Details
                        </h2>

                        <p id="cardSub"></p>

                    </div>

                </div>


                <!-- PATIENT DETAILS -->

                <div class="form-section">

                    <!-- FIRST NAME -->

                    <div class="form-group">

                        <label>
                            First Name
                        </label>

                        <input
                            type="text"
                            id="fFirst"
                            name="first_name"
                            class="form-input"
                            required
                        >

                    </div>


                    <!-- LAST NAME -->

                    <div class="form-group">

                        <label>
                            Last Name
                        </label>

                        <input
                            type="text"
                            id="fLast"
                            name="last_name"
                            class="form-input"
                            required
                        >

                    </div>


                    <!-- AGE -->

                    <div class="form-group">

                        <label>
                            Age
                        </label>

                        <input
                            type="number"
                            id="fAge"
                            name="age"
                            class="form-input"
                            min="0"
                            max="120"
                            required
                        >

                    </div>


                    <!-- CANCER -->

                    <div class="form-group">

                        <label>
                            Cancer Type
                        </label>


                        <select
                            id="fCancer"
                            name="cancer_type"
                            class="form-input"
                            required
                        >

                            <option value="">
                                Select
                            </option>

                            <option value="breast">
                                Breast
                            </option>

                            <option value="lung">
                                Lung
                            </option>

                            <option value="leukemia">
                                Leukemia
                            </option>

                            <option value="lymphoma">
                                Lymphoma
                            </option>

                            <option value="colon">
                                Colon
                            </option>

                            <option value="prostate">
                                Prostate
                            </option>

                            <option value="other">
                                Other
                            </option>

                        </select>

                    </div>


                    <!-- STAGE -->

                    <div class="form-group">

                        <label>
                            Cancer Stage
                        </label>


                        <select
                            id="fStage"
                            name="stage"
                            class="form-input"
                            required
                        >

                            <option value="">
                                Select
                            </option>

                            <option value="I">
                                I
                            </option>

                            <option value="II">
                                II
                            </option>

                            <option value="III">
                                III
                            </option>

                            <option value="IV">
                                IV
                            </option>

                        </select>

                    </div>


                    <!-- STATUS -->

                    <div class="form-group">

                        <label>
                            Status
                        </label>


                        <select
                            id="fStatus"
                            name="status"
                            class="form-input"
                            required
                        >

                            <option value="active">
                                Active
                            </option>

                            <option value="critical">
                                Critical
                            </option>

                            <option value="stable">
                                Stable
                            </option>

                            <option value="scheduled">
                                Scheduled
                            </option>

                        </select>

                    </div>


                    <!-- DOCTOR -->

                    <div class="form-group">

                        <label>
                            Assigned Doctor
                        </label>


                        <select
                            id="fDoctor"
                            name="assigned_doctor"
                            class="form-input"
                            required
                        >

                            <option value="">
                                Select Doctor
                            </option>


                            <?php foreach ($doctors as $d): ?>

                                <option
                                    value="<?php echo (int)$d['user_id']; ?>"
                                >

                                    Dr.

                                    <?php

                                    echo e(
                                        $d['first_name']
                                        . ' '
                                        . $d['last_name']
                                    );

                                    ?>

                                </option>

                            <?php endforeach; ?>


                        </select>

                    </div>

                </div>


                <!-- =================================================
                     BUTTONS
                ================================================== -->

                <div
                    style="
                        display:flex;
                        justify-content:flex-end;
                        gap:10px;
                        padding:24px 0 8px;
                    "
                >

                    <!-- CLOSE -->

                    <button
                        type="button"
                        onclick="closeCard()"
                        style="
                            order:1;
                            padding:10px 20px;
                            background:#fff;
                            color:#334155;
                            border:1px solid #cbd5e1;
                            border-radius:8px;
                            cursor:pointer;
                            font-weight:600;
                        "
                    >
                        Close
                    </button>


                    <!-- UPDATE -->

                    <button
                        type="submit"
                        name="action"
                        value="update_patient"
                        style="
                            order:2;
                            padding:10px 20px;
                            background:#2563eb;
                            color:#fff;
                            border:none;
                            border-radius:8px;
                            cursor:pointer;
                            font-weight:600;
                        "
                    >
                        Update
                    </button>

                </div>


            </form>

        </div>

    </main>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Patient Details Card
|--------------------------------------------------------------------------
*/

const card =
    document.getElementById(
        'patientDetailsCard'
    );

let currentPatientId = null;


/*
|--------------------------------------------------------------------------
| Input Mapping
|--------------------------------------------------------------------------
*/

const map = {

    fFirst:
        'first_name',

    fLast:
        'last_name',

    fAge:
        'age',

    fCancer:
        'cancer_type',

    fStage:
        'stage',

    fStatus:
        'status',

    fDoctor:
        'doctor_id'

};


/*
|--------------------------------------------------------------------------
| Open Patient Card
|--------------------------------------------------------------------------
*/

function openCard(btn)
{

    const p =
        JSON.parse(
            btn.dataset.p
        );


    currentPatientId =
        p.id;


    document.getElementById(
        'fId'
    ).value =
        p.id;


    for (
        const [inputId, key]
        of Object.entries(map)
    ) {

        document.getElementById(
            inputId
        ).value =
            p[key] ?? '';

    }


    document.getElementById(
        'cardTitle'
    ).innerText =
        p.full_name;


    document.getElementById(
        'cardSub'
    ).innerText =
        (p.id
            ? p.id + ' · '
            : '')
        + 'Age '
        + p.age;


    card.style.display =
        'block';


    card.scrollIntoView(
        {
            behavior: 'smooth',
            block: 'start'
        }
    );

}


/*
|--------------------------------------------------------------------------
| Close Card
|--------------------------------------------------------------------------
*/

function closeCard()
{

    card.style.display =
        'none';

}


/*
|--------------------------------------------------------------------------
| Sidebar
|--------------------------------------------------------------------------
*/

const sidebarToggle =
    document.getElementById(
        'sidebarToggle'
    );


const sidebar =
    document.getElementById(
        'sidebar'
    );


if (
    sidebarToggle &&
    sidebar
) {

    sidebarToggle.addEventListener(
        'click',
        function () {

            sidebar.classList.toggle(
                'open'
            );

        }
    );

}

</script>


</body>

</html>

<?php

$conn->close();

?>