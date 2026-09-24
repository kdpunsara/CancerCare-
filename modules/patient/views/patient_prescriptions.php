<?php
require_once __DIR__ . '/../patient_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prescriptions — Apeksha OncoCare</title>
<link rel="stylesheet" href="../../../public/css/base.css?v=3">
<link rel="stylesheet" href="../../../public/css/prescription.css">
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
        <li><a href="patient_records.php" class="nav-item" title="Medical Records">
          <span class="icon icon-records" aria-hidden="true"></span>
          <span class="nav-text">Medical Records</span>
        </a></li>
        <li><a href="patient_prescriptions.php" class="nav-item active" title="Prescriptions">
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
          <h2>Prescriptions</h2>
          <p class="date">Your current and past medications</p>
        </div>
      </div>
      <div class="topbar-actions">
        <button class="icon-btn" aria-label="Notifications">
          <span class="icon icon-bell" aria-hidden="true"></span>
          <span class="dot"></span>
        </button>
        <a href="../../../logout.php" class="signout-btn">Sign Out</a>
      </div>
    </header>

    <section class="content">

      <div class="card list-card wide-card">
        <div class="list-card-header">
          <h3>Active Medications</h3>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Medication</th>
                <th>Dosage</th>
                <th>Frequency</th>
                <th>Prescribed By</th>
                <th>Start Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($prescriptions as $prescription): ?>
                <?php if ($prescription['status'] !== 'active') { continue; } ?>
                <tr>
                  <td class="med-name"><?= htmlspecialchars($prescription['medicine_name']) ?></td>
                  <td><?= htmlspecialchars($prescription['dosage']) ?></td>
                  <td><?= htmlspecialchars($prescription['frequency']) ?></td>
                  <td><?= htmlspecialchars('Dr. ' . $prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']) ?></td>
                  <td><?= htmlspecialchars(date('d M Y', strtotime($prescription['prescription_date']))) ?></td>
                  <td><span class="badge badge-active">Active</span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card list-card wide-card">
        <div class="list-card-header">
          <h3>Past Medications</h3>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Medication</th>
                <th>Dosage</th>
                <th>Frequency</th>
                <th>Prescribed By</th>
                <th>Duration</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($prescriptions as $prescription): ?>
                <?php if ($prescription['status'] === 'active') { continue; } ?>
                <tr>
                  <td class="med-name"><?= htmlspecialchars($prescription['medicine_name']) ?></td>
                  <td><?= htmlspecialchars($prescription['dosage']) ?></td>
                  <td><?= htmlspecialchars($prescription['frequency']) ?></td>
                  <td><?= htmlspecialchars('Dr. ' . $prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']) ?></td>
                  <td><?= htmlspecialchars($prescription['duration'] ?: date('d M Y', strtotime($prescription['prescription_date']))) ?></td>
                  <td><span class="badge badge-done"><?= htmlspecialchars(ucfirst($prescription['status'])) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </section>
  </main>
</div>

</body>
</html>
