<?php
require_once __DIR__ . '/../patient_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transport — Apeksha OncoCare</title>
<link rel="stylesheet" href="../../../public/css/base.css?v=3">
<link rel="stylesheet" href="../../../public/css/transport.css">
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
        <li><a href="patient_transport.php" class="nav-item active" title="Transport">
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
          <h2>Transport</h2>
          <p class="date">Find a public bus to your appointment</p>
        </div>
      </div>
      <div class="topbar-actions">
        <a href="../../../logout.php" class="signout-btn">Sign Out</a>
      </div>
    </header>

    <section class="content">

      <div class="card list-card wide-card">
        <div class="list-card-header">
          <h3>Bus Schedule for Your Appointments</h3>
        </div>
        <p class="table-caption">Matched from Kurunegala (your home address) against your upcoming appointments below.</p>

        <div class="appt-bus-list">

          <div class="appt-bus-card">
            <div class="appt-bus-head">
              <div>
                <p class="appt-bus-date">Jul 5, 2026 · 9:00 AM</p>
                <p class="appt-bus-desc">Follow-up Consultation · Dr. Fernando</p>
              </div>
              <span class="badge badge-pending">Plan ahead</span>
            </div>
            <div class="appt-bus-route">
              <span class="icon icon-transport" aria-hidden="true"></span>
              <div>
                <p class="route-name">No same-day bus arrives in time</p>
                <p class="route-time">Earliest Kurunegala bus arrives 10:30 AM — after your appointment</p>
              </div>
            </div>
            <p class="route-note">Consider travelling the evening before, or ask the transport desk about earlier options at <strong>+94 37 222 4455</strong>.</p>
          </div>

          <div class="appt-bus-card">
            <div class="appt-bus-head">
              <div>
                <p class="appt-bus-date">Jul 10, 2026 · 11:00 AM</p>
                <p class="appt-bus-desc">Lab Review · Dr. Fernando</p>
              </div>
              <span class="badge badge-active">Good fit</span>
            </div>
            <div class="appt-bus-route">
              <span class="icon icon-transport" aria-hidden="true"></span>
              <div>
                <p class="route-name">Kurunegala → Maharagama · Semi-Luxury</p>
                <p class="route-time">Departs 7:00 AM · Arrives 10:30 AM</p>
              </div>
            </div>
            <p class="route-note">Arrives about 30 minutes before your appointment — a comfortable buffer.</p>
          </div>

          <div class="appt-bus-card">
            <div class="appt-bus-head">
              <div>
                <p class="appt-bus-date">Jul 17, 2026 · 2:30 PM</p>
                <p class="appt-bus-desc">Chemotherapy — Cycle 3</p>
              </div>
              <span class="badge badge-active">Good fit</span>
            </div>
            <div class="appt-bus-route">
              <span class="icon icon-transport" aria-hidden="true"></span>
              <div>
                <p class="route-name">Kurunegala → Maharagama · Normal (CTB)</p>
                <p class="route-time">Departs 9:00 AM · Arrives 12:15 PM</p>
              </div>
            </div>
            <p class="route-note">Arrives over 2 hours early — plenty of time before your session.</p>
          </div>

        </div>
      </div>

      <div class="card list-card wide-card">
        <div class="list-card-header">
          <h3>Public Bus Schedule to Maharagama</h3>
        </div>
        <p class="table-caption">Approximate inter-city bus timings for patients travelling to the hospital in Maharagama. Times may vary with traffic and road conditions.</p>

        <form class="search-bar" action="patient_transport.php#bus-results" method="get">
          <div class="search-field">
            <span class="icon icon-search" aria-hidden="true"></span>
            <input type="search" name="q" value="<?= htmlspecialchars($transport_search) ?>" placeholder="Search buses by city or route..." aria-label="Search buses by city or route">
          </div>
          <button type="submit" class="search-btn">Search</button>
        </form>

        <?php if ($transport_search !== ''): ?>
          <p class="search-result-note">
            Showing buses matching <strong><?= htmlspecialchars($transport_search) ?></strong>.
            <a href="patient_transport.php">Clear search</a>
          </p>
        <?php endif; ?>

        <div class="table-wrap" id="bus-results">
          <table class="data-table">
            <thead>
              <tr>
                <th>Route</th>
                <th>From</th>
                <th>To</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Bus Type</th>
                <th>Days</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($transport_schedules) === 0): ?>
                <tr>
                  <td colspan="7" class="empty-state">No buses found for this city or route.</td>
                </tr>
              <?php endif; ?>
              <?php foreach ($transport_schedules as $schedule): ?>
                <tr>
                  <td class="med-name"><?= htmlspecialchars($schedule['route_name']) ?></td>
                  <td><?= htmlspecialchars($schedule['departure_location']) ?></td>
                  <td><?= htmlspecialchars($schedule['arrival_location']) ?></td>
                  <td><?= htmlspecialchars(date('g:i A', strtotime($schedule['departure_time']))) ?></td>
                  <td><?= htmlspecialchars(date('g:i A', strtotime($schedule['arrival_time']))) ?></td>
                  <td><?= htmlspecialchars($schedule['vehicle_type']) ?> (<?= (int) $schedule['capacity'] ?> seats)</td>
                  <td><?= htmlspecialchars($schedule['operating_days']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card panel">
        <h4 class="panel-title">Need Help Planning Your Trip?</h4>
        <p class="panel-note">Can't find a bus that fits your appointment time? Call the patient transport desk at
        <strong>+94 37 222 4455</strong> and our team can help you plan the best route to Maharagama.</p>
      </div>

    </section>
  </main>
</div>

</body>
</html>
