<?php
require_once __DIR__ . '/../patient_data.php';

$search = trim(isset($_GET['q']) ? $_GET['q'] : '');
$total_medications = 0;
$in_stock = 0;
$low_stock = 0;
$out_of_stock = 0;
$medicines = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Drug Availability — Apeksha OncoCare</title>
<link rel="stylesheet" href="../../../public/css/base.css?v=3">
<link rel="stylesheet" href="../../../public/css/dashboard.css?v=3">
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
        <li><a href="patient_drug_availability.php" class="nav-item active" title="Drug Availability">
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
          <h2>Drug Availability</h2>
          <p class="date">Check current medication stock at the hospital pharmacy</p>
        </div>
      </div>
      <div class="topbar-actions">
        <a href="../../../logout.php" class="signout-btn">Sign Out</a>
      </div>
    </header>

    <section class="content">

      <div class="card list-card wide-card">
        <div class="header-actions-row">
          <h3>Drug Availability</h3>
        </div>

        <form class="search-bar" action="patient_drug_availability.php" method="get">
          <div class="search-field">
            <span class="icon icon-search" aria-hidden="true"></span>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search medication by name…">
          </div>
          <button type="submit" class="search-btn">Search</button>
        </form>
        <p class="search-note">Search looks up medications by name from the pharmacy list below.</p>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Medication</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Pharmacy Location</th>
                <th>Last Updated</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($medicines) === 0): ?>
                <tr>
                  <td colspan="6">No medications found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($medicines as $medicine): ?>
                  <?php
                    $quantity = (int)$medicine['quantity'];
                    if ($quantity <= 0) {
                        $status = 'Out of Stock';
                        $badge = 'badge-cancelled';
                    } elseif ($quantity <= 20) {
                        $status = 'Low Stock';
                        $badge = 'badge-pending';
                    } else {
                        $status = 'In Stock';
                        $badge = 'badge-active';
                    }
                  ?>
                  <tr>
                    <td class="med-name"><?= htmlspecialchars($medicine['medicine_name']) ?></td>
                    <td><?= htmlspecialchars(isset($medicine['category']) ? $medicine['category'] : '') ?></td>
                    <td><?= $quantity ?></td>
                    <td><?= htmlspecialchars(isset($medicine['pharmacy_location']) ? $medicine['pharmacy_location'] : '') ?></td>
                    <td><?= htmlspecialchars(isset($medicine['last_updated']) ? $medicine['last_updated'] : '') ?></td>
                    <td><span class="badge <?= $badge ?>"><?= $status ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card panel">
        <h4 class="panel-title">Prescription Pickup</h4>
        <p class="panel-note">Medications marked <strong>Low Stock</strong> or <strong>Out of Stock</strong> may take an extra day to dispense.
        Call the Main Pharmacy at <strong>+94 37 222 4420</strong> before your visit to confirm availability.</p>
      </div>
    </section>
  </main>
</div>

</body>
</html>
