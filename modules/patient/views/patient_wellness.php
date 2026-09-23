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
<title>Wellness & Meals — Apeksha OncoCare</title>
<link rel="stylesheet" href="../../../public/css/base.css">
<link rel="stylesheet" href="../../../public/css/wellness.css">
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
        <button class="icon-btn" aria-label="Notifications">
          <span class="icon icon-bell" aria-hidden="true"></span>
          <span class="dot"></span>
        </button>
      </div>
    </header>

    <section class="content">

      <div class="card welcome-card">
        <h3>Weekly Meal Plan</h3>
        <p class="welcome-meta">Prepared by Dietitian Perera &nbsp;·&nbsp; High-protein, low-sodium plan for Chemotherapy — Cycle 2</p>
      </div>

      <div class="meal-grid">
          <div class="meal-day-card">
            <p class="meal-day-name">Monday</p>
            <div class="meal-row"><span class="meal-tag meal-tag-breakfast">Breakfast</span><span class="meal-text">Kola kanda with a boiled egg</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-lunch">Lunch</span><span class="meal-text">Red rice, dhal curry, steamed greens</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-dinner">Dinner</span><span class="meal-text">Vegetable soup with grilled fish</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-snack">Snack</span><span class="meal-text">Papaya slices</span></div>
          </div>
          <div class="meal-day-card">
            <p class="meal-day-name">Tuesday</p>
            <div class="meal-row"><span class="meal-tag meal-tag-breakfast">Breakfast</span><span class="meal-text">Plain oats with banana</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-lunch">Lunch</span><span class="meal-text">Brown rice, chicken curry, beetroot salad</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-dinner">Dinner</span><span class="meal-text">String hoppers with dhal curry</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-snack">Snack</span><span class="meal-text">Coconut water</span></div>
          </div>
          <div class="meal-day-card">
            <p class="meal-day-name">Wednesday</p>
            <div class="meal-row"><span class="meal-tag meal-tag-breakfast">Breakfast</span><span class="meal-text">Milk rice with jaggery</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-lunch">Lunch</span><span class="meal-text">Red rice, fish curry, sautéed cabbage</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-dinner">Dinner</span><span class="meal-text">Vegetable soup with brown bread</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-snack">Snack</span><span class="meal-text">Sliced mango</span></div>
          </div>
          <div class="meal-day-card">
            <p class="meal-day-name">Thursday</p>
            <div class="meal-row"><span class="meal-tag meal-tag-breakfast">Breakfast</span><span class="meal-text">Roti with mild coconut sambol</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-lunch">Lunch</span><span class="meal-text">Brown rice, lentil curry, pumpkin curry</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-dinner">Dinner</span><span class="meal-text">Rice porridge with vegetables</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-snack">Snack</span><span class="meal-text">Boiled chickpeas</span></div>
          </div>
          <div class="meal-day-card">
            <p class="meal-day-name">Friday</p>
            <div class="meal-row"><span class="meal-tag meal-tag-breakfast">Breakfast</span><span class="meal-text">Steamed idli with sambar</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-lunch">Lunch</span><span class="meal-text">Red rice, chicken curry, carrot salad</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-dinner">Dinner</span><span class="meal-text">Noodle soup with tofu</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-snack">Snack</span><span class="meal-text">Watermelon cubes</span></div>
          </div>
          <div class="meal-day-card">
            <p class="meal-day-name">Saturday</p>
            <div class="meal-row"><span class="meal-tag meal-tag-breakfast">Breakfast</span><span class="meal-text">Vegetable kottu (light oil)</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-lunch">Lunch</span><span class="meal-text">Brown rice, fish curry, green beans</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-dinner">Dinner</span><span class="meal-text">Clear soup with steamed vegetables</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-snack">Snack</span><span class="meal-text">Plain yoghurt</span></div>
          </div>
          <div class="meal-day-card">
            <p class="meal-day-name">Sunday</p>
            <div class="meal-row"><span class="meal-tag meal-tag-breakfast">Breakfast</span><span class="meal-text">Wheat pittu with coconut milk</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-lunch">Lunch</span><span class="meal-text">Red rice, egg curry, mixed salad</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-dinner">Dinner</span><span class="meal-text">Vegetable stew with rice</span></div>
            <div class="meal-row"><span class="meal-tag meal-tag-snack">Snack</span><span class="meal-text">Herbal tea &amp; dry fruits</span></div>
          </div>
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
