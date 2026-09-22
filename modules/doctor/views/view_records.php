<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../../../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Records - Cancer Care</title>
    <link rel="stylesheet" href="../../../public/css/base.css">
    <style>
        /* Basic utility to handle view switching */
        .view-section {
            display: none;
        }
        .view-section.active {
            display: block;
        }
        .search-bar-container {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }
        .search-bar-container input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="app">
        <?php
        $current_page = 'records';
        require_once __DIR__ . '/../../../includes/doctor_sidebar.php';
        ?>

        <!-- Main Content -->
        <main class="main">

            <!-- ========================================== -->
            <!-- VIEW 1: PATIENT SEARCH & LIST              -->
            <!-- ========================================== -->
            <div id="view-patient-list" class="view-section active">
                <header class="topbar">
                    <div class="topbar-left">
                        <div>
                            <h2>Patient Records Directory</h2>
                            <p class="date">Search and select a patient to view or update their medical records</p>
                        </div>
                    </div>
                    <div class="topbar-actions">
                        <button class="signout-btn">Sign Out</button>
                    </div>
                </header>

                <section class="content">
                    <div class="card form-card">
                        <div class="search-bar-container">
                            <input type="text" placeholder="Search patients by Name, ID (e.g., P-1001), or NIC...">
                            <button class="btn-primary">Search</button>
                        </div>
                    </div>

                    <div class="card list-card wide-card">
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Patient Name</th>
                                        <th>Current Stage</th>
                                        <th>Last Updated</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>P-1001</strong></td>
                                        <td>Kamal Perera</td>
                                        <td><span class="badge" style="background: var(--navy-soft); color: #fff;">Stage III</span></td>
                                        <td>Jun 15, 2026</td>
                                        <td><button class="btn-primary" onclick="showView('view-patient-details')" style="padding: 6px 12px; font-size: 13px;">View Details</button></td>
                                    </tr>
                                    <tr>
                                        <td><strong>P-1002</strong></td>
                                        <td>Nimali Silva</td>
                                        <td><span class="badge" style="background: var(--navy-soft); color: #fff;">Stage II</span></td>
                                        <td>Jun 10, 2026</td>
                                        <td><button class="btn-primary" onclick="showView('view-patient-details')" style="padding: 6px 12px; font-size: 13px;">View Details</button></td>
                                    </tr>
                                    <tr>
                                        <td><strong>P-1003</strong></td>
                                        <td>Sunil Bandara</td>
                                        <td><span class="badge" style="background: var(--navy-soft); color: #fff;">Stage IV</span></td>
                                        <td>May 28, 2026</td>
                                        <td><button class="btn-primary" onclick="showView('view-patient-details')" style="padding: 6px 12px; font-size: 13px;">View Details</button></td>
                                    </tr>
                                    <tr>
                                        <td><strong>P-1004</strong></td>
                                        <td>Chamari Jayawardena</td>
                                        <td><span class="badge" style="background: var(--navy-soft); color: #fff;">Stage I</span></td>
                                        <td>May 15, 2026</td>
                                        <td><button class="btn-primary" onclick="showView('view-patient-details')" style="padding: 6px 12px; font-size: 13px;">View Details</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ========================================== -->
            <!-- VIEW 2: PATIENT DETAILS (View Records)     -->
            <!-- ========================================== -->
            <div id="view-patient-details" class="view-section">
                <header class="topbar">
                    <div class="topbar-left">
                        <div>
                            <h2>Patient Medical Records</h2>
                            <p class="date">Comprehensive view of current status and historical diagnosis records</p>
                        </div>
                    </div>
                    <div class="topbar-actions">
                        <button class="btn-secondary" onclick="showView('view-patient-list')" style="margin-right: 12px;">Back to List</button>
                        <button class="btn-primary" onclick="showView('view-update-diagnosis')" style="margin-right: 12px;">Update Diagnosis</button>
                        <button class="signout-btn">Sign Out</button>
                    </div>
                </header>

                <section class="content">
                    <!-- Patient Header -->
                    <div class="card panel">
                        <div style="display: flex; gap: 20px; align-items: center;">
                            <div class="avatar" style="width: 56px; height: 56px; font-size: 18px;">KP</div>
                            <div>
                                <h2 style="margin: 0 0 6px; font-size: 20px;">Kamal Perera <span class="badge" style="background: var(--navy-soft); color: #fff; margin-left: 8px;">Stage III</span></h2>
                                <p class="table-caption" style="margin: 0;">ID: P-1001 | NIC: 198510201234 | Age: 52 | Male | Blood Group: O+</p>
                            </div>
                        </div>
                    </div>

                    <!-- Current Status Summary -->
                    <div class="card form-card">
                        <h3 class="form-title">Current Diagnosis & Treatment Plan</h3>
                        <p class="form-subtitle">Latest active plan outlined for this patient.</p>
                        
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Primary Diagnosis</label>
                                <textarea readonly rows="3" style="background: var(--bg); pointer-events:none;">Colorectal Adenocarcinoma (Stage III). Confirmed via biopsy on Feb 05, 2026.</textarea>
                            </div>
                            <div class="form-field">
                                <label>Current Treatment Plan</label>
                                <textarea readonly rows="3" style="background: var(--bg); pointer-events:none;">Neoadjuvant Chemotherapy (FOLFOX regimen) for 8 cycles. Currently on Cycle 4. Next cycle scheduled for July 13, 2026.</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- PREVIOUS DIAGNOSIS RECORDS -->
                    <div class="card list-card wide-card">
                        <div class="list-card-header">
                            <h3>Previous Diagnosis & Treatment History</h3>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date Updated</th>
                                        <th>Updated By</th>
                                        <th>Diagnosis / Findings</th>
                                        <th>Stage</th>
                                        <th>Treatment Plan</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Jun 15, 2026</strong></td>
                                        <td>Dr. N. Fernando</td>
                                        <td style="max-width: 250px; white-space: normal; line-height:1.4;">Post-cycle 4 evaluation. 15% reduction in tumor mass. Mild neuropathy noted.</td>
                                        <td><span class="badge" style="background: var(--navy-soft); color: #fff;">Stage III</span></td>
                                        <td style="max-width: 200px; white-space: normal; line-height:1.4;">Continue FOLFOX. Monitor neuropathy.</td>
                                        <td><button class="btn-secondary" style="padding: 6px 12px; font-size: 12px;">View Details</button></td>
                                    </tr>
                                    <tr>
                                        <td><strong>May 18, 2026</strong></td>
                                        <td>Dr. N. Fernando</td>
                                        <td style="max-width: 250px; white-space: normal; line-height:1.4;">CT Scan Review. Stable disease progression. Blood counts normal.</td>
                                        <td><span class="badge" style="background: var(--navy-soft); color: #fff;">Stage III</span></td>
                                        <td style="max-width: 200px; white-space: normal; line-height:1.4;">Proceed with Cycle 4 as planned.</td>
                                        <td><button class="btn-secondary" style="padding: 6px 12px; font-size: 12px;">View Details</button></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Mar 10, 2026</strong></td>
                                        <td>Dr. N. Fernando</td>
                                        <td style="max-width: 250px; white-space: normal; line-height:1.4;">Adverse reaction: Neutropenia (low WBC). Treatment delayed.</td>
                                        <td><span class="badge" style="background: var(--navy-soft); color: #fff;">Stage III</span></td>
                                        <td style="max-width: 200px; white-space: normal; line-height:1.4;">Administered G-CSF. Delayed Cycle 3 by 1 week.</td>
                                        <td><button class="btn-secondary" style="padding: 6px 12px; font-size: 12px;">View Details</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Uploaded Medical Reports -->
                    <div class="card list-card wide-card">
                        <div class="list-card-header">
                            <h3>Uploaded Medical Reports</h3>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date Uploaded</th>
                                        <th>Report Type</th>
                                        <th>Uploaded By</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Jun 15, 2026</strong></td>
                                        <td class="med-name">Blood Test Results (CBC)</td>
                                        <td>Nurse Amali (Staff)</td>
                                        <td><button class="btn-secondary" style="padding: 6px 12px; font-size: 12px;">View File</button></td>
                                    </tr>
                                    <tr>
                                        <td><strong>May 18, 2026</strong></td>
                                        <td class="med-name">CT Scan Report (Abdomen)</td>
                                        <td>Nurse Amali (Staff)</td>
                                        <td><button class="btn-secondary" style="padding: 6px 12px; font-size: 12px;">View File</button></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Feb 05, 2026</strong></td>
                                        <td class="med-name">Biopsy & Histopathology Report</td>
                                        <td>Nurse Amali (Staff)</td>
                                        <td><button class="btn-secondary" style="padding: 6px 12px; font-size: 12px;">View File</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ========================================== -->
            <!-- VIEW 3: UPDATE DIAGNOSIS                   -->
            <!-- ========================================== -->
            <div id="view-update-diagnosis" class="view-section">
                <header class="topbar">
                    <div class="topbar-left">
                        <div>
                            <h2>Update Diagnosis & Treatment</h2>
                            <p class="date">Update patient medical status, diagnosis reports, and treatment history</p>
                        </div>
                    </div>
                    <div class="topbar-actions">
                        <button class="signout-btn">Sign Out</button>
                    </div>
                </header>

                <section class="content">
                    
                    <!-- Patient Context (Readonly since patient is already selected) -->
                    <div class="card form-card">
                        <div class="form-grid" style="align-items: center;">
                            <div class="form-field">
                                <label>Selected Patient</label>
                                <input type="text" value="P-1001 - Kamal Perera" readonly style="background: var(--bg); pointer-events: none;">
                            </div>
                            <div style="display: flex; gap: 24px; margin-top: 10px;">
                                <div>
                                    <span class="form-section-label" style="display:block; margin:0 0 6px 0;">Current Stage</span>
                                    <span class="badge" style="background: var(--navy-soft); color: #fff;">Stage III</span>
                                </div>
                                <div>
                                    <span class="form-section-label" style="display:block; margin:0 0 6px 0;">Last Updated</span>
                                    <div style="font-weight: 600; font-size: 13.5px;">Jun 15, 2026</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form onsubmit="event.preventDefault(); showView('view-patient-details'); alert('Updates saved successfully!');">
                        <!-- Section 1: Update Diagnosis -->
                        <div class="card form-card">
                            <h3 class="form-title">Update Diagnosis Report</h3>
                            <p class="form-subtitle">Record clinical observations and confirm staging details.</p>
                            
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Cancer Type / Primary Diagnosis <span style="color:var(--red);">*</span></label>
                                    <input type="text" value="Colorectal Adenocarcinoma">
                                </div>
                                <div class="form-field">
                                    <label>Cancer Stage <span style="color:var(--red);">*</span></label>
                                    <select>
                                        <option>Stage I</option>
                                        <option>Stage II</option>
                                        <option selected>Stage III</option>
                                        <option>Stage IV</option>
                                        <option>In Remission</option>
                                    </select>
                                </div>
                                <div class="form-field form-field-wide">
                                    <label>Clinical Findings & Diagnosis Notes <span style="color:var(--red);">*</span></label>
                                    <textarea rows="4" placeholder="Enter detailed clinical observations, biopsy results, or scan interpretations...">Post-cycle 4 evaluation. Patient is responding well to FOLFOX regimen. Mild peripheral neuropathy noted. CT scan shows 15% reduction in primary tumor mass. No new metastases detected.</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Maintain Treatment History -->
                        <div class="card form-card">
                            <h3 class="form-title">Update Treatment History & Plan</h3>
                            <p class="form-subtitle">Record current treatment response and establish the path forward.</p>
                            
                            <div class="form-grid">
                                <div class="form-field">
                                    <label>Current Treatment Protocol</label>
                                    <input type="text" value="FOLFOX Chemotherapy (Cycle 4 of 8)">
                                </div>
                                <div class="form-field">
                                    <label>Treatment Response</label>
                                    <select>
                                        <option>Excellent Response</option>
                                        <option selected>Partial Response</option>
                                        <option>Stable Disease</option>
                                        <option>Progressive Disease</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label>Next Scheduled Treatment</label>
                                    <input type="date" value="2026-07-13">
                                </div>
                                <div class="form-field">
                                    <label>Referrals / Additional Tests Required</label>
                                    <input type="text" placeholder="e.g., Cardiology clearance, MRI Brain">
                                </div>
                                <div class="form-field form-field-wide">
                                    <label>Future Treatment Plan & Next Steps</label>
                                    <textarea rows="3" placeholder="Outline the next phase of treatment...">Continue FOLFOX for 4 more cycles. Monitor neuropathy. If stable, proceed to surgical resection evaluation in September. Prescribe Gabapentin for nerve pain management.</textarea>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="form-actions">
                                <button type="button" class="btn-secondary" onclick="showView('view-patient-details')">Cancel</button>
                                <button type="submit" class="btn-primary">Save Diagnosis Updates</button>
                            </div>
                        </div>
                    </form>
                </section>
            </div>

        </main>
    </div>

    <!-- JavaScript to handle View Switching -->
    <script>
        function showView(viewId) {
            // Hide all views
            const views = document.querySelectorAll('.view-section');
            views.forEach(view => {
                view.classList.remove('active');
            });
            
            // Show the selected view
            const activeView = document.getElementById(viewId);
            if(activeView) {
                activeView.classList.add('active');
            }
            
            // Scroll to top when switching views for better UX
            window.scrollTo(0, 0);
        }
    </script>
</body>
</html>