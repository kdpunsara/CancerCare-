<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'staff') {
    header("Location: ../../../login.php");
    exit();
}

require_once __DIR__ . '/../../../config/database.php';

if (!$conn) {
    die("Database connection failed.");
}

/*
|--------------------------------------------------------------------------
| Load Patients From Database
|--------------------------------------------------------------------------
*/

$patients = [];

$sql = "SELECT
            user_id,
            first_name,
            last_name,
            cancer_type,
            stage
        FROM `Patient`
        ORDER BY first_name ASC, last_name ASC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Reports — Cancer care</title>
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
           SEARCH / NOTIFICATION / MENU SVG
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
                    <div class="logo-icon">❤</div>
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
            <!-- =================================================
                 TOP BAR
            ================================================== -->
            <header class="topbar">
                <div class="topbar-left">
                    <h1>
                        Medical Reports
                    </h1>
                   
                </div>
                <div class="topbar-right">
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
            <!-- =================================================
                 CONTENT
            ================================================== -->
            <div class="content">
                <!-- ============================= -->
                <!-- Upload Medical Report Card -->
                <!-- ============================= -->
                <div class="form-card">
                    <div class="form-card-header">
                        <div>
                            <h2>
                                Upload Medical Report
                            </h2>
                            <p>
                                Upload cancer-related test reports, medical documents or report images.
                            </p>
                        </div>
                    </div>
                    <form id="medicalReportForm">
                        <div class="form-section">
                            <!-- Patient -->
                            <div class="form-group">
                                <label for="patient">
                                    Patient
                                </label>
                                <select
                                    id="patient"
                                    name="patient"
                                    class="form-input"
                                    required
                                >
                                    <option value="">
                                        Select Patient
                                    </option>
                                    <option value="P-1001">
                                        Nadun Kaveen (P-1001)
                                    </option>
                                    <option value="P-1002">
                                        Kamani Wickramasinghe (P-1002)
                                    </option>
                                    <option value="P-1003">
                                        Ravi Perera (P-1003)
                                    </option>
                                    <option value="P-1004">
                                        Sandun Jayasekara (P-1004)
                                    </option>
                                </select>
                            </div>
                            <!-- Report Title -->
                            <div class="form-group">
                                <label for="reportTitle">
                                    Report Title
                                </label>
                                <input
                                    type="text"
                                    id="reportTitle"
                                    name="reportTitle"
                                    class="form-input"
                                    placeholder="Example: PET Scan Report"
                                    required
                                >
                            </div>
                            <!-- Cancer Type -->
                            <div class="form-group">
                                <label for="cancerType">
                                    Cancer Type
                                </label>
                                <select
                                    id="cancerType"
                                    name="cancerType"
                                    class="form-input"
                                    required
                                >
                                    <option value="">
                                        Select Cancer Type
                                    </option>
                                    <option value="Breast Cancer">
                                        Breast Cancer
                                    </option>
                                    <option value="Lung Cancer">
                                        Lung Cancer
                                    </option>
                                    <option value="Colorectal Cancer">
                                        Colorectal Cancer
                                    </option>
                                    <option value="Prostate Cancer">
                                        Prostate Cancer
                                    </option>
                                    <option value="Leukemia">
                                        Leukemia
                                    </option>
                                    <option value="Lymphoma">
                                        Lymphoma
                                    </option>
                                    <option value="Liver Cancer">
                                        Liver Cancer
                                    </option>
                                    <option value="Cervical Cancer">
                                        Cervical Cancer
                                    </option>
                                    <option value="Other">
                                        Other
                                    </option>
                                </select>
                            </div>
                            <!-- Cancer Stage -->
                            <div class="form-group">
                                <label for="cancerStage">
                                    Cancer Stage
                                </label>
                                <select
                                    id="cancerStage"
                                    name="cancerStage"
                                    class="form-input"
                                    required
                                >
                                    <option value="">
                                        Select Cancer Stage
                                    </option>
                                    <option value="Stage 0">
                                        Stage 0 — In situ / Non-invasive
                                    </option>
                                    <option value="Stage I">
                                        Stage I — Early / Localized
                                    </option>
                                    <option value="Stage II">
                                        Stage II — Localized / Larger Tumor
                                    </option>
                                    <option value="Stage III">
                                        Stage III — Regional Spread
                                    </option>
                                    <option value="Stage IV">
                                        Stage IV — Advanced / Distant Spread
                                    </option>
                                    <option value="Unknown">
                                        Stage Not Confirmed
                                    </option>
                                </select>
                            </div>
                            <!-- Stage Related Details -->
                            <div class="form-group">
                                <label for="stageDetails">
                                    Cancer Stage Details
                                </label>
                                <textarea
                                    id="stageDetails"
                                    name="stageDetails"
                                    class="form-input"
                                    rows="4"
                                    placeholder="Enter stage-related clinical details, tumor size, lymph node involvement, metastasis information, TNM findings, etc."
                                ></textarea>
                            </div>
                            <!-- Test Type -->
                            <div class="form-group">
                                <label for="testType">
                                    Test / Report Type
                                </label>
                                <select
                                    id="testType"
                                    name="testType"
                                    class="form-input"
                                    required
                                >
                                    <option value="">
                                        Select Test / Report Type
                                    </option>
                                    <option value="Blood Test">
                                        Blood Test
                                    </option>
                                    <option value="Biopsy">
                                        Biopsy Report
                                    </option>
                                    <option value="PET Scan">
                                        PET Scan
                                    </option>
                                    <option value="CT Scan">
                                        CT Scan
                                    </option>
                                    <option value="MRI">
                                        MRI Report
                                    </option>
                                    <option value="X-Ray">
                                        X-Ray Report
                                    </option>
                                    <option value="Ultrasound">
                                        Ultrasound Report
                                    </option>
                                    <option value="Histopathology">
                                        Histopathology Report
                                    </option>
                                    <option value="Mammogram">
                                        Mammogram
                                    </option>
                                    <option value="Tumor Marker">
                                        Tumor Marker Test
                                    </option>
                                    <option value="Other">
                                        Other
                                    </option>
                                </select>
                            </div>
                            <!-- Test Date -->
                            <div class="form-group">
                                <label for="testDate">
                                    Test / Report Date
                                </label>
                                <input
                                    type="date"
                                    id="testDate"
                                    name="testDate"
                                    class="form-input"
                                >
                            </div>
                            <!-- Doctor -->
                            <div class="form-group">
                                <label for="doctor">
                                    Referring / Reporting Doctor
                                </label>
                                <input
                                    type="text"
                                    id="doctor"
                                    name="doctor"
                                    class="form-input"
                                    placeholder="Enter doctor's name"
                                >
                            </div>
                            <!-- Report Details -->
                            <div class="form-group">
                                <label for="reportDetails">
                                    Report Details / Findings
                                </label>
                                <textarea
                                    id="reportDetails"
                                    name="reportDetails"
                                    class="form-input"
                                    rows="6"
                                    placeholder="Enter important findings, diagnosis, observations, treatment response, recommendations, etc."
                                    required
                                ></textarea>
                            </div>
                            <!-- File Upload -->
                            <div class="form-group">
                                <label for="reportFile">
                                    Medical Report File
                                </label>
                                <input
                                    type="file"
                                    id="reportFile"
                                    name="reportFile"
                                    class="form-input"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    required
                                >
                                <small>
                                    Accepted files: PDF, JPG, JPEG and PNG.
                                </small>
                            </div>
                            <!-- File Information -->
                            <div
                                id="fileInformation"
                                style="
                                    display:none;
                                    margin-top:10px;
                                    padding:12px;
                                    border:1px solid #ddd;
                                    border-radius:8px;
                                "
                            >
                                <strong>
                                    Selected File
                                </strong>
                                <p
                                    id="fileName"
                                    style="margin:5px 0;"
                                ></p>
                                <p
                                    id="fileSize"
                                    style="margin:5px 0;"
                                ></p>
                            </div>
                            <!-- Image Preview -->
                            <div
                                id="imagePreviewContainer"
                                style="
                                    display:none;
                                    margin-top:15px;
                                "
                            >
                                <label>
                                    Report Image Preview
                                </label>
                                <div style="margin-top:10px;">
                                    <img
                                        id="imagePreview"
                                        src=""
                                        alt="Medical report preview"
                                        style="
                                            max-width:100%;
                                            max-height:350px;
                                            border-radius:8px;
                                            border:1px solid #ddd;
                                            object-fit:contain;
                                        "
                                    >
                                </div>
                            </div>
                        </div>
                        <!-- Form Actions -->
                        <div
                            class="form-actions"
                            style="
                                background: transparent;
                                border: none;
                                padding-top: 0;
                                justify-content: flex-end;
                            "
                        >
                            <button
                                type="reset"
                                class="btn btn-outline"
                                id="clearForm"
                            >
                                Clear
                            </button>
                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Upload Report
                            </button>
                        </div>
                    </form>
                </div>
                <!-- ============================= -->
                <!-- Uploaded Reports -->
                <!-- ============================= -->
                <div
                    class="form-card"
                    style="margin-top:20px;"
                >
                    <div class="form-card-header">
                        <div>
                            <h2>
                                Uploaded Reports
                            </h2>
                            <p>
                                Recently uploaded cancer patient medical reports.
                            </p>
                        </div>
                    </div>
                    <!-- Report 01 -->
                    <div class="record-item">
                        <h4>
                            Sandun Jayasekara — Chronic Myeloid Leukemia
                        </h4>
                        <p>
                            <strong>
                                Cancer Stage:
                            </strong>
                            Stage II
                        </p>
                        <p>
                            <strong>
                                Test:
                            </strong>
                            BCR-ABL Molecular Test
                        </p>
                        <p>
                            BCR-ABL positive. Patient is responding well to targeted
                            therapy. Continue monitoring molecular response.
                        </p>
                    </div>
                    <!-- Report 02 -->
                    <div class="record-item">
                        <h4>
                            Ravi Perera — Non-Small Cell Lung Cancer
                        </h4>
                        <p>
                            <strong>
                                Cancer Stage:
                            </strong>
                            Stage III
                        </p>
                        <p>
                            <strong>
                                Test:
                            </strong>
                            PET Scan
                        </p>
                        <p>
                            PET scan shows partial response to current treatment.
                            Continue monitoring respiratory status and follow-up imaging.
                        </p>
                    </div>
                    <!-- Report 03 -->
                    <div class="record-item">
                        <h4>
                            Kamani Wickramasinghe — Breast Cancer
                        </h4>
                        <p>
                            <strong>
                                Cancer Stage:
                            </strong>
                            Stage I
                        </p>
                        <p>
                            <strong>
                                Test:
                            </strong>
                            Histopathology Report
                        </p>
                        <p>
                            Histopathology findings are consistent with an early-stage
                            breast malignancy. Treatment plan and further staging
                            investigations are recommended.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <!-- =====================================================
         JAVASCRIPT
         No external library
         No framework
         No API
    ====================================================== -->
    <script>
        /* =====================================================
           SIDEBAR TOGGLE
        ====================================================== */
        const toggleBtn =
            document.getElementById("sidebarToggle");
        const sidebar =
            document.getElementById("sidebar");
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener("click", () => {
                sidebar.classList.toggle("open");
            });
        }
        /* =====================================================
           FILE UPLOAD HANDLING
        ====================================================== */
        const reportFile =
            document.getElementById("reportFile");
        const fileInformation =
            document.getElementById("fileInformation");
        const fileName =
            document.getElementById("fileName");
        const fileSize =
            document.getElementById("fileSize");
        const imagePreviewContainer =
            document.getElementById("imagePreviewContainer");
        const imagePreview =
            document.getElementById("imagePreview");
        reportFile.addEventListener("change", function () {
            const file = this.files[0];
            if (!file) {
                fileInformation.style.display = "none";
                imagePreviewContainer.style.display = "none";
                imagePreview.src = "";
                return;
            }
            /* Show file information */
            fileInformation.style.display = "block";
            fileName.textContent =
                "File Name: " + file.name;
            fileSize.textContent =
                "File Size: " + formatFileSize(file.size);
            /* Show image preview */
            if (file.type.startsWith("image/")) {
                const reader =
                    new FileReader();
                reader.onload =
                    function (event) {
                        imagePreview.src =
                            event.target.result;
                        imagePreviewContainer.style.display =
                            "block";
                    };
                reader.readAsDataURL(file);
            } else {
                imagePreview.src = "";
                imagePreviewContainer.style.display =
                    "none";
            }
        });
        /* =====================================================
           FORMAT FILE SIZE
        ====================================================== */
        function formatFileSize(bytes) {
            if (bytes === 0) {
                return "0 Bytes";
            }
            const units = [
                "Bytes",
                "KB",
                "MB",
                "GB"
            ];
            const i =
                Math.floor(
                    Math.log(bytes) /
                    Math.log(1024)
                );
            return (
                parseFloat(
                    (
                        bytes /
                        Math.pow(1024, i)
                    ).toFixed(2)
                ) +
                " " +
                units[i]
            );
        }
        /* =====================================================
           FORM SUBMIT
        ====================================================== */
        const medicalReportForm =
            document.getElementById(
                "medicalReportForm"
            );
        medicalReportForm.addEventListener(
            "submit",
            function (event) {
                event.preventDefault();
                const patient =
                    document.getElementById(
                        "patient"
                    ).value;
                const reportTitle =
                    document.getElementById(
                        "reportTitle"
                    ).value;
                const cancerType =
                    document.getElementById(
                        "cancerType"
                    ).value;
                const cancerStage =
                    document.getElementById(
                        "cancerStage"
                    ).value;
                const testType =
                    document.getElementById(
                        "testType"
                    ).value;
                const reportFileValue =
                    document.getElementById(
                        "reportFile"
                    ).files[0];
                if (
                    !patient ||
                    !reportTitle ||
                    !cancerType ||
                    !cancerStage ||
                    !testType ||
                    !reportFileValue
                ) {
                    alert(
                        "Please complete all required fields and select a medical report file."
                    );
                    return;
                }
                alert(
                    "Medical report uploaded successfully!"
                );
                medicalReportForm.reset();
                fileInformation.style.display =
                    "none";
                imagePreviewContainer.style.display =
                    "none";
                imagePreview.src = "";
            }
        );
        /* =====================================================
           CLEAR FORM
        ====================================================== */
        document
            .getElementById("clearForm")
            .addEventListener(
                "click",
                function () {
                    fileInformation.style.display =
                        "none";
                    imagePreviewContainer.style.display =
                        "none";
                    imagePreview.src = "";
                }
            );
    </script>
</body>
</html>