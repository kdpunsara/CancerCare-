<?php
require_once __DIR__ . '/../patient_data.php';
$upcoming_appointments = array_filter($appointments, function ($appointment) {
  return $appointment['appointment_status'] === 'upcoming';
});
$active_prescriptions = array_filter($prescriptions, function ($prescription) {
  return $prescription['status'] === 'active';
});
$latest_record = count($medical_records) ? $medical_records[0] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Apeksha OncoCare</title>
<link rel="stylesheet" href="../../../public/css/base.css?v=3">
<link rel="stylesheet" href="../../../public/css/dashboard.css">
</head>
<body>

<div class="app">

  <aside class="sidebar">
    <div class="brand">
      <div class="brand-mark">CC</div>
      <div class="brand-text">
        <h1>Apeksha<br>CancerCare</h1>
        <p>Cancer Patient Care</p>
      </div>
    </div>

    <nav class="nav">
      <p class="nav-label">Navigation</p>
      <ul>
        <li><a href="patient_dashboard.php" class="nav-item active" title="My Dashboard">
          <span class="icon icon-dashboard" aria-hidden="true"></span>
          <span class="nav-text">My Dashboard</span>
        </a></li>
        <li><a href="patient_profile_edit.php" class="nav-item" title="My Profile">
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
          <h2>Dashboard</h2>
          <p class="date">Tuesday, July 14, 2026</p>
        </div>
      </div>
      <div class="topbar-actions">
        <a href="../../../logout.php" class="signout-btn">Sign Out</a>
      </div>
    </header>

    <section class="content">

      <!-- WELCOME CARD -->
      <div class="card welcome-card">
        <h3>Welcome, <?= htmlspecialchars($full_name) ?>!</h3>
        <p class="welcome-meta">Patient ID: <strong>P-<?= (int) $patient_id ?></strong></p>

        <div class="progress-block">
          <div class="progress-heading">
            <span class="progress-title">Treatment Progress</span>
            <span class="progress-value"><?= $latest_record ? 'Active' : 'No record' ?></span>
          </div>
          <div class="progress-track">
            <div class="progress-fill" style="width:<?= $latest_record ? '45' : '0' ?>%;"></div>
          </div>
          <p class="progress-caption">Chemotherapy — Cycle 2</p>
        </div>
      </div>

      <!-- STAT CARDS -->
      <div class="stat-grid">

        <div class="card stat-card">
          <div class="stat-icon stat-icon-blue">
            <span class="icon icon-appointments" aria-hidden="true"></span>
          </div>
          <p class="stat-label">My Appointments</p>
          <p class="stat-value"><?= count($appointments) ?></p>
          <p class="stat-sub"><?= count($upcoming_appointments) ?> upcoming</p>
        </div>

        <div class="card stat-card">
          <div class="stat-icon stat-icon-teal">
            <span class="icon icon-prescriptions" aria-hidden="true"></span>
          </div>
          <p class="stat-label">Prescriptions</p>
          <p class="stat-value"><?= count($active_prescriptions) ?></p>
          <p class="stat-sub">Active medications</p>
        </div>

        <div class="card stat-card">
          <div class="stat-icon stat-icon-amber">
            <span class="icon icon-records" aria-hidden="true"></span>
          </div>
          <p class="stat-label">Treatment Stage</p>
          <p class="stat-value stat-value-text">Stage II</p>
          <p class="stat-sub"><?= htmlspecialchars($latest_record ? $latest_record['cancer_stage'] : 'Not recorded') ?></p>
        </div>

        <div class="card stat-card">
          <div class="stat-icon stat-icon-yellow">
            <span class="icon icon-bell" aria-hidden="true"></span>
          </div>
          <p class="stat-label">Notifications</p>
          <p class="stat-value">2</p>
          <p class="stat-sub">Unread alerts</p>
        </div>

      </div>

      <!-- BOTTOM ROW -->
      <div class="bottom-grid">

        <div class="card list-card">
          <div class="list-card-header">
            <h3>Recent Appointments</h3>
            <a href="patient_appointments.php" class="view-all">View all →</a>
          </div>

          <ul class="appointment-list">
            <?php if (!$upcoming_appointments): ?>
              <li class="appointment-item">
                <div class="appointment-info">
                  <p class="appointment-name">No upcoming appointments</p>
                </div>
              </li>
            <?php else: ?>
              <?php foreach (array_slice($upcoming_appointments, 0, 3) as $appointment): ?>
                <li class="appointment-item confirmed">
                  <div class="appointment-time">
                    <span class="time"><?php echo htmlspecialchars(date('H:i', strtotime($appointment['appointment_time']))); ?></span>
                    <span class="date"><?php echo htmlspecialchars(date('M j, Y', strtotime($appointment['appointment_date']))); ?></span>
                  </div>
                  <div class="appointment-info">
                    <p class="appointment-name"><?php echo htmlspecialchars($appointment['reason'] ?: 'Appointment'); ?></p>
                    <p class="appointment-desc"><?php echo htmlspecialchars('Dr. ' . $appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></p>
                  </div>
                  <span class="status status-confirmed">upcoming</span>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>

      </div>

    </section>
  </main>
</div>

</body>
</html>
