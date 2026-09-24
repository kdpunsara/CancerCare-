<?php
require_once __DIR__ . '/../patient_data.php';
$full_name = trim($patient['first_name'].' '.$patient['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — Apeksha OncoCare</title>
<link rel="stylesheet" href="../../../public/css/base.css?v=3">
<link rel="stylesheet" href="../../../public/css/profile.css">
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
        <li><a href="patient_profile.php" class="nav-item active" title="My Profile">
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
        <p class="user-name"><?= htmlspecialchars($full_name) ?></p>
        <p class="user-role">Patient</p>
      </div>
    </div>
  </aside>

  <main class="main">

    <header class="topbar">
      <div class="topbar-left">
        <div>
          <h2>My Profile</h2>
          <p class="date">Manage your personal & medical information</p>
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

      <div class="card profile-card">
        <div class="profile-head">
          <div class="profile-avatar">SJ</div>
          <div>
            <h3><?= htmlspecialchars($full_name) ?></h3>
            <p class="profile-sub">Patient ID: <strong>P-1001</strong> &nbsp;·&nbsp; Registered 12 Jan 2026</p>
          </div>
          <a href="patient_profile_edit.php" class="edit-btn">Edit Profile</a>
        </div>
      </div>

      <div class="profile-grid">

        <div class="card panel">
          <h4 class="panel-title">Personal Information</h4>
          <div class="info-grid">
            <div class="info-item"><span class="info-label">Full Name</span><span class="info-value"><?= htmlspecialchars($full_name) ?></span></div>
            <div class="info-item"><span class="info-label">Date of Birth</span><span class="info-value"><?= htmlspecialchars(date('d M Y', strtotime($patient['dob']))) ?></span></div>
            <div class="info-item"><span class="info-label">Gender</span><span class="info-value"><?= htmlspecialchars(ucfirst($patient['gender'])) ?></span></div>
            <div class="info-item"><span class="info-label">NIC Number</span><span class="info-value"><?= htmlspecialchars($patient['nic']) ?></span></div>
            <div class="info-item"><span class="info-label">Address</span><span class="info-value"><?= htmlspecialchars(trim($patient['address'].', '.$patient['city'], ', ')) ?></span></div>
            <div class="info-item"><span class="info-label">Language</span><span class="info-value">Sinhala, English</span></div>
          </div>
        </div>

        <div class="card panel">
          <h4 class="panel-title">Contact Details</h4>
          <div class="info-grid">
            <div class="info-item"><span class="info-label">Mobile Number</span><span class="info-value"><?= htmlspecialchars($patient['phone']) ?></span></div>
            <div class="info-item"><span class="info-label">Email</span><span class="info-value"><?= htmlspecialchars($patient['email']) ?></span></div>
            <div class="info-item"><span class="info-label">Emergency Contact</span><span class="info-value">Nadeesha Jayasekara</span></div>
            <div class="info-item"><span class="info-label">Emergency Number</span><span class="info-value">+94 77 987 6543</span></div>
            <div class="info-item"><span class="info-label">Relationship</span><span class="info-value">Spouse</span></div>
            <div class="info-item"><span class="info-label">Preferred Contact</span><span class="info-value">Phone Call</span></div>
          </div>
        </div>

        <div class="card panel">
          <h4 class="panel-title">Medical Information</h4>
          <div class="info-grid">
            <div class="info-item"><span class="info-label">Blood Group</span><span class="info-value">O+</span></div>
            <div class="info-item"><span class="info-label">Diagnosis</span><span class="info-value">Stage II Lymphoma</span></div>
            <div class="info-item"><span class="info-label">Attending Doctor</span><span class="info-value">Dr. Fernando</span></div>
            <div class="info-item"><span class="info-label">Allergies</span><span class="info-value">Penicillin</span></div>
            <div class="info-item"><span class="info-label">Height / Weight</span><span class="info-value">172 cm / 68 kg</span></div>
            <div class="info-item"><span class="info-label">Insurance Provider</span><span class="info-value">Ceylinco Life</span></div>
          </div>
        </div>

        <div class="card panel">
          <h4 class="panel-title">Account Settings</h4>
          <div class="info-grid">
            <div class="info-item"><span class="info-label">Username</span><span class="info-value">sandun.jayasekara</span></div>
            <div class="info-item"><span class="info-label">Password</span><span class="info-value">••••••••••</span></div>
            <div class="info-item"><span class="info-label">Notifications</span><span class="info-value">Email &amp; SMS</span></div>
            <div class="info-item"><span class="info-label">Two-Factor Auth</span><span class="info-value">Enabled</span></div>
          </div>
        </div>

      </div>

    </section>
  </main>
</div>

</body>
</html>
