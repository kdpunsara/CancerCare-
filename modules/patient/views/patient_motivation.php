<?php
require_once __DIR__ . '/../patient_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Motivation & Awareness — Apeksha OncoCare</title>
<link rel="stylesheet" href="../../../public/css/base.css?v=3">
<link rel="stylesheet" href="../../../public/css/dashboard.css?v=3">
<link rel="stylesheet" href="../../../public/css/motivation.css?v=3">

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
        <li><a href="patient_dashboard.php" class="nav-item" title="My Dashboard">
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
        <a href="../../../logout.php" class="signout-btn">Sign Out</a>
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
          <?php if (count($motivation_resources) === 0): ?>
            <div class="resource-empty">
              <span class="icon icon-motivation" aria-hidden="true"></span>
              <h4>No motivation resources available yet</h4>
              <p>Your care team will publish helpful articles, videos, and stories here.</p>
            </div>
          <?php else: ?>
          <?php foreach ($motivation_resources as $resource): ?>
            <div class="resource-card">
              <span class="resource-tag tag-<?= htmlspecialchars($resource['content_type']) ?>"><?= htmlspecialchars(ucfirst($resource['content_type'])) ?></span>
              <h4 class="resource-title"><?= htmlspecialchars($resource['title']) ?></h4>
              <p class="resource-desc"><?= htmlspecialchars($resource['description']) ?></p>
              <p class="resource-meta"><?= htmlspecialchars($resource['category'] ?: 'Apeksha Care Team') ?> · <?= htmlspecialchars($resource['published_date'] ?: '') ?></p>
              <?php if (!empty($resource['content_url'])): ?><a href="<?= htmlspecialchars($resource['content_url']) ?>" target="_blank" rel="noopener">Open resource</a><?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php endif; ?>
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
