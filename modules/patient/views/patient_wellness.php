<?php
require_once __DIR__ . '/../patient_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Wellness & Meals — Apeksha OncoCare</title>
<link rel="stylesheet" href="../../../public/css/base.css?v=3">
<link rel="stylesheet" href="../../../public/css/dashboard.css?v=3">
<link rel="stylesheet" href="../../../public/css/wellness.css?v=3">
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
        <li><a href="patient_wellness.php" class="nav-item active" title="Wellness & Meals">
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
          <h2>Wellness & Meals</h2>
          <p class="date">Your personalised meal plan and wellness tips</p>
        </div>
      </div>
      <div class="topbar-actions">
        <a href="../../../logout.php" class="signout-btn">Sign Out</a>
      </div>
    </header>

    <section class="content">

      <div class="card welcome-card">
        <h3>Weekly Meal Plan</h3>
        <p class="welcome-meta">Prepared by Dietitian Perera &nbsp;·&nbsp; High-protein, low-sodium plan for Chemotherapy — Cycle 2</p>
      </div>

      <div class="meal-grid">
          <?php $meals_by_day = array(); foreach ($meal_plans as $meal) { $meals_by_day[$meal['plan_date']][] = $meal; } ?>
          <?php if (count($meals_by_day) === 0): ?>
            <div class="meal-empty">
              <span class="icon icon-wellness" aria-hidden="true"></span>
              <h4>No meal plan assigned yet</h4>
              <p>Your care team will publish your personalised meals here.</p>
            </div>
          <?php else: ?>
            <?php foreach ($meals_by_day as $plan_date => $meals): ?>
            <div class="meal-day-card">
              <p class="meal-day-name"><?= htmlspecialchars(date('l, d M Y', strtotime($plan_date))) ?></p>
              <?php foreach ($meals as $meal): ?>
                <div class="meal-row"><span class="meal-tag meal-tag-<?= htmlspecialchars($meal['meal_type']) ?>"><?= htmlspecialchars(ucfirst($meal['meal_type'])) ?></span><span class="meal-text"><?= htmlspecialchars($meal['menu_description']) ?></span></div>
              <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
      </div>

      <div class="card panel">
        <h4 class="panel-title">Nutrition Guidance</h4>
        <ul class="tips-list">
          <li>Keep meals small and frequent — aim for 5–6 light meals through the day.</li>
          <li>Favour boiled, steamed, or lightly sautéed foods over fried or spicy dishes.</li>
          <li>Stay hydrated with water, coconut water, or herbal tea between meals.</li>
          <li>Avoid raw or undercooked food while white blood cell counts are low.</li>
          <li>Report any loss of appetite or nausea lasting more than two days to Dr. Fernando.</li>
        </ul>
      </div>

    </section>
  </main>
</div>

</body>
</html>
