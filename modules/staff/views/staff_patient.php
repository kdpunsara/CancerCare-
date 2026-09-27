<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'staff'
) {
    header("Location: ../../../login.php");
    exit();
}

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

$baseSql = "
    SELECT
        p.*,
        CONCAT(p.first_name, ' ', p.last_name) AS full_name,
        CONCAT('Dr. ', d.first_name, ' ', d.last_name) AS doctor_name
    FROM Patient p
    LEFT JOIN Doctor d
        ON d.user_id = p.assigned_doctor
";


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
        ORDER BY p.first_name ASC, p.last_name ASC
        "
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
        ORDER BY p.first_name ASC, p.last_name ASC
        "
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
        "
        SELECT user_id, first_name, last_name
        FROM Doctor
        ORDER BY first_name, last_name
        "
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

        /*
        |--------------------------------------------------------------------------
        | Patient Page Styles
        |--------------------------------------------------------------------------
        */

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
         SHARED SIDEBAR
         ===================================================== -->

    <?php

    /*
     * Tell sidebar.php that this is the Patients page.
     * sidebar.php will automatically show:
     *
     * Logged-in staff name
     * Staff initials
     * Patients = active
     */

    $activePage = 'patients';

    require __DIR__ . '/sidebar.php';

    ?>


    <!-- =====================================================
         MAIN
         ===================================================== -->

    <main class="main">


        <!-- =====================================================
             TOP BAR
             ===================================================== -->

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


                <!-- TOP SEARCH -->

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


                <!-- SIGN OUT -->

                <a
                    href="../../../logout.php"
                    class="btn btn-outline"
                >
                    Sign Out
                </a>


                <!-- SIDEBAR TOGGLE -->

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
             ===================================================== -->

        <div class="content">


            <!-- =================================================
                 SEARCH + REGISTER
                 ================================================= -->

            <div class="page-toolbar">


                <!-- SEARCH -->

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


                <!-- REGISTER -->

                <a
                    href="Register_patient.php"
                    class="btn btn-primary"
                >
                    + Register Patient
                </a>

            </div>


            <!-- =================================================
                 MESSAGES
                 ================================================= -->

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


            <!-- =================================================
                 SEARCH INFO
                 ================================================= -->

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
                 ================================================= -->

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

                                /*
                                |--------------------------------------------------------------------------
                                | Patient Name
                                |--------------------------------------------------------------------------
                                */

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


                                /*
                                |--------------------------------------------------------------------------
                                | Patient ID
                                |--------------------------------------------------------------------------
                                */

                                $patientId =
                                    $row['user_id'] ?? '';


                                /*
                                |--------------------------------------------------------------------------
                                | Doctor
                                |--------------------------------------------------------------------------
                                */

                                $doctorVal =
                                    $row['doctor_name'] ?? '';


                                /*
                                |--------------------------------------------------------------------------
                                | Status
                                |--------------------------------------------------------------------------
                                */

                                $status =
                                    $row['status'] ?? '';


                                $statusCls =
                                    strtolower(
                                        preg_replace(
                                            '/[^a-zA-Z0-9\_-]/',
                                            '',
                                            $status
                                        )
                                    );


                                /*
                                |--------------------------------------------------------------------------
                                | Patient JSON
                                |--------------------------------------------------------------------------
                                */

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


                                <!-- =================================================
                                     PATIENT ROW
                                     ================================================= -->

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
                 ================================================= -->

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


                <!-- =================================================
                     PATIENT DETAILS
                     ================================================= -->

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
                     ================================================= -->

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


<!-- =========================================================
     JAVASCRIPT
     ========================================================= -->

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
        (
            p.id
                ? p.id + ' · '
                : ''
        )
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
| Sidebar Toggle
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