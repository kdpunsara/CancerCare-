<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'patient') {
    header("Location: ../../../login.php");
    exit();
}
$patient_id = (int) $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medical Records — Apeksha OncoCare</title>
<link rel="stylesheet" href="../../../public/css/base.css">
</head>
<body>

<div class="app">

  <aside class="sidebar">
    <div class="brand">
      <div class="brand-mark">AO</div>
      <div class="brand-text">
        <h1>Apeksha<br>OncoCare</h1>
        <p>Cancer Patient Care</p>
      </div>
    </div>

    <nav class="nav">
      <p class="nav-label">Navigation</p>
      <ul>
        <li><a href="patient_dashboard.php" class="nav-item" title="My Dashboard">
          <span class="icon icon-dashboard" aria-hidden="true"></span>
          <span class="nav-text">My Dashboard</span>
        </a></li>
        <li><a href="patient_profile.php" class="nav-item" title="My Profile">
          <span class="icon icon-profile" aria-hidden="true"></span>
          <span class="nav-text">My Profile</span>
        </a></li>
        <li><a href="patient_appointments.php" class="nav-item" title="Appointments">
          <span class="icon icon-appointments" aria-hidden="true"></span>
          <span class="nav-text">Appointments</span>
        </a></li>
        <li><a href="patient_records.php" class="nav-item active" title="Medical Records">
          <span class="icon icon-records" aria-hidden="true"></span>
          <span class="nav-text">Medical Records</span>
        </a></li>
        <li><a href="patient_prescriptions.php" class="nav-item" title="Prescriptions">
          <span class="icon icon-prescriptions" aria-hidden="true"></span>
          <span class="nav-text">Prescriptions</span>
        </a></li>
        <li><a href="patient_wellness.php" class="nav-item" title="Wellness & Meals">
          <span class="icon icon-wellness" aria-hidden="true"></span>
          <span class="nav-text">Wellness & Meals</span>
        </a></li>
        <li><a href="patient_transport.php" class="nav-item" title="Transport">
          <span class="icon icon-transport" aria-hidden="true"></span>
          <span class="nav-text">Transport</span>
        </a></li>
        <li><a href="patient_drug_availability.php" class="nav-item" title="Drug Availability">
          <span class="icon icon-drug" aria-hidden="true"></span>
          <span class="nav-text">Drug Availability</span>
        </a></li>
        <li><a href="patient_motivation.php" class="nav-item" title="Motivation">
          <span class="icon icon-motivation" aria-hidden="true"></span>
          <span class="nav-text">Motivation</span>
        </a></li>
      </ul>
    </nav>

    <div class="sidebar-user">
      <div class="avatar">SJ</div>
      <div>
        <p class="user-name">Sandun Jayasekara</p>
        <p class="user-role">Patient</p>
      </div>
    </div>
  </aside>

  <main class="main">

    <header class="topbar">
      <div class="topbar-left">
        <div>
          <h2>Medical Records</h2>
          <p class="date">Lab results, imaging and treatment notes</p>
        </div>
      </div>
      <div class="topbar-actions">
        <button class="icon-btn" aria-label="Notifications">
          <span class="icon icon-bell" aria-hidden="true"></span>
          <span class="dot"></span>
        </button>
      </div>
    </header>

    <section class="content">

      <div class="stat-grid">
        <div class="card stat-card">
          <div class="stat-icon stat-icon-blue">
            <span class="icon icon-records" aria-hidden="true"></span>
          </div>
          <p class="stat-label">Total Records</p>
          <p class="stat-value">11</p>
          <p class="stat-sub">Since Feb 2026</p>
        </div>
        <div class="card stat-card">
          <div class="stat-icon stat-icon-teal">
            <span class="icon icon-records" aria-hidden="true"></span>
          </div>
          <p class="stat-label">Lab Results</p>
          <p class="stat-value">4</p>
          <p class="stat-sub">Latest: 2 Jul 2026</p>
        </div>
        <div class="card stat-card">
          <div class="stat-icon stat-icon-amber">
            <span class="icon icon-records" aria-hidden="true"></span>
          </div>
          <p class="stat-label">Imaging</p>
          <p class="stat-value">3</p>
          <p class="stat-sub">Latest: 18 Jun 2026</p>
        </div>
        <div class="card stat-card">
          <div class="stat-icon stat-icon-yellow">
            <span class="icon icon-records" aria-hidden="true"></span>
          </div>
          <p class="stat-label">Treatment Notes</p>
          <p class="stat-value">4</p>
          <p class="stat-sub">Latest: 21 Jun 2026</p>
        </div>
      </div>

      <div class="card list-card wide-card">
        <div class="list-card-header">
          <h3>Search Medical Records</h3>
        </div>

        <form class="search-bar" action="records.html" method="get">
          <div class="search-field">
            <span class="icon icon-search" aria-hidden="true"></span>
            <input type="text" name="q" placeholder="Search by record name, type or doctor…">
          </div>
          <button type="submit" class="search-btn">Search</button>
        </form>
        <p class="search-note">Search looks through all record categories below — lab results, imaging, treatment notes and more.</p>
      </div>

      <div class="card list-card wide-card">
        <div class="list-card-header">
          <h3>Lab Results</h3>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Record</th>
                <th>Type</th>
                <th>Date</th>
                <th>Doctor</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="med-name">Full Blood Count (FBC) Report</td>
                <td>Lab Result</td>
                <td>2 Jul 2026</td>
                <td>Dr. Fernando</td>
                <td><span class="badge badge-done">Reviewed</span></td>
                <td><span class="link-action">View</span></td>
              </tr>
              <tr>
                <td class="med-name">Liver Function Test</td>
                <td>Lab Result</td>
                <td>21 Jun 2026</td>
                <td>Dr. Fernando</td>
                <td><span class="badge badge-done">Reviewed</span></td>
                <td><span class="link-action">View</span></td>
              </tr>
              <tr>
                <td class="med-name">Renal Function Test</td>
                <td>Lab Result</td>
                <td>21 Jun 2026</td>
                <td>Dr. Fernando</td>
                <td><span class="badge badge-done">Reviewed</span></td>
                <td><span class="link-action">View</span></td>
              </tr>
              <tr>
                <td class="med-name">Tumour Marker Panel (CA-125)</td>
                <td>Lab Result</td>
                <td>7 Jun 2026</td>
                <td>Dr. Fernando</td>
                <td><span class="badge badge-active">New</span></td>
                <td><span class="link-action">View</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card list-card wide-card">
        <div class="list-card-header">
          <h3>Imaging</h3>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Record</th>
                <th>Type</th>
                <th>Date</th>
                <th>Doctor</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="med-name">CT Scan — Chest &amp; Abdomen</td>
                <td>Imaging</td>
                <td>18 Jun 2026</td>
                <td>Dr. Fernando</td>
                <td><span class="badge badge-done">Reviewed</span></td>
                <td><span class="link-action">View</span></td>
              </tr>
              <tr>
                <td class="med-name">Chest X-Ray</td>
                <td>Imaging</td>
                <td>21 May 2026</td>
                <td>Dr. Fernando</td>
                <td><span class="badge badge-done">Reviewed</span></td>
                <td><span class="link-action">View</span></td>
              </tr>
              <tr>
                <td class="med-name">PET-CT Scan — Whole Body</td>
                <td>Imaging</td>
                <td>10 Feb 2026</td>
                <td>Dr. Fernando</td>
                <td><span class="badge badge-done">Reviewed</span></td>
                <td><span class="link-action">View</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card list-card wide-card">
        <div class="list-card-header">
          <h3>Treatment &amp; Consultation Notes</h3>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Record</th>
                <th>Type</th>
                <th>Date</th>
                <th>Doctor</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="med-name">Chemotherapy — Cycle 2 Summary</td>
                <td>Treatment Note</td>
                <td>21 Jun 2026</td>
                <td>Dr. Fernando</td>
                <td><span class="badge badge-done">Reviewed</span></td>
                <td><span class="link-action">View</span></td>
              </tr>
              <tr>
                <td class="med-name">Chemotherapy — Cycle 1 Summary</td>
                <td>Treatment Note</td>
                <td>21 May 2026</td>
                <td>Dr. Fernando</td>
                <td><span class="badge badge-done">Reviewed</span></td>
                <td><span class="link-action">View</span></td>
              </tr>
              <tr>
                <td class="med-name">Initial Oncology Consultation</td>
                <td>Consultation Note</td>
                <td>7 Feb 2026</td>
                <td>Dr. Fernando</td>
                <td><span class="badge badge-done">Reviewed</span></td>
                <td><span class="link-action">View</span></td>
              </tr>
              <tr>
                <td class="med-name">Nutrition Assessment</td>
                <td>Consultation Note</td>
                <td>15 Feb 2026</td>
                <td>Dietitian Perera</td>
                <td><span class="badge badge-done">Reviewed</span></td>
                <td><span class="link-action">View</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card list-card wide-card">
        <div class="list-card-header">
          <h3>Pathology &amp; Other Records</h3>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Record</th>
                <th>Type</th>
                <th>Date</th>
                <th>Doctor</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="med-name">Biopsy Report — Lymph Node</td>
                <td>Pathology</td>
                <td>4 Feb 2026</td>
                <td>Dr. Fernando</td>
                <td><span class="badge badge-done">Reviewed</span></td>
                <td><span class="link-action">View</span></td>
              </tr>
              <tr>
                <td class="med-name">Vaccination Record</td>
                <td>Immunisation</td>
                <td>28 Jan 2026</td>
                <td>Dr. Silva</td>
                <td><span class="badge badge-done">Reviewed</span></td>
                <td><span class="link-action">View</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </section>
  </main>
</div>

</body>
</html>
