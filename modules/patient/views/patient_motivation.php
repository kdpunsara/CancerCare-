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
<title>Motivation & Awareness — Apeksha OncoCare</title>
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
        <li><a href="patient_motivation.php" class="nav-item active" title="Motivation">
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
          <h2>Motivation & Awareness</h2>
          <p class="date">Encouragement, guidance and resources for your journey</p>
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

      <div class="card welcome-card">
        <h3>You are stronger than you think</h3>
        <p class="welcome-meta">Curated stories, guidance and reminders from the Apeksha OncoCare support team — for the days treatment feels heavy, and the days it doesn't.</p>
      </div>

      <div class="quote-card">
        <p class="quote-text">"Healing takes courage, and we all have courage, even if we have to dig a little to find it."</p>
        <p class="quote-author">— Tori Amos</p>
      </div>

      <div class="resource-grid">
          <div class="resource-card">
            <span class="resource-tag tag-article">Article</span>
            <h4 class="resource-title">Understanding Chemotherapy Fatigue</h4>
            <p class="resource-desc">Why tiredness during Cycle 2 is common, and simple ways to manage energy through the week.</p>
            <p class="resource-meta">5 min read · Apeksha Care Team</p>
          </div>
          <div class="resource-card">
            <span class="resource-tag tag-video">Video</span>
            <h4 class="resource-title">Breathing Exercises for Calm</h4>
            <p class="resource-desc">A short guided breathing session you can follow before or after treatment sessions.</p>
            <p class="resource-meta">8 min video · Wellness Center</p>
          </div>
          <div class="resource-card">
            <span class="resource-tag tag-story">Story</span>
            <h4 class="resource-title">Nadeesha's Journey — One Year On</h4>
            <p class="resource-desc">A fellow patient shares what helped her stay hopeful through chemotherapy and recovery.</p>
            <p class="resource-meta">6 min read · Patient Story</p>
          </div>
          <div class="resource-card">
            <span class="resource-tag tag-guide">Guide</span>
            <h4 class="resource-title">Talking to Family About Your Diagnosis</h4>
            <p class="resource-desc">Practical tips for opening up conversations with loved ones at your own pace.</p>
            <p class="resource-meta">4 min read · Counselling Team</p>
          </div>
          <div class="resource-card">
            <span class="resource-tag tag-article">Article</span>
            <h4 class="resource-title">Sleep and Recovery During Treatment</h4>
            <p class="resource-desc">How to build a restful night routine that supports your body's healing process.</p>
            <p class="resource-meta">5 min read · Apeksha Care Team</p>
          </div>
          <div class="resource-card">
            <span class="resource-tag tag-video">Video</span>
            <h4 class="resource-title">Gentle Movement for Low-Energy Days</h4>
            <p class="resource-desc">Light stretches you can do from a chair or bed on days when energy is limited.</p>
            <p class="resource-meta">10 min video · Physiotherapy Unit</p>
          </div>
      </div>

      <div class="card panel">
        <h4 class="panel-title">Need to talk to someone?</h4>
        <p class="panel-note">Our patient counselling team is available Monday to Saturday, 8:00 AM – 5:00 PM.
        Call <strong>+94 37 222 4400</strong> or ask any nurse on the ward to arrange a session — you don't have to go through this alone.</p>
      </div>

    </section>
  </main>
</div>

</body>
</html>
