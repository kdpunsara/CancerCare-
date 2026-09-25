<?php
require_once __DIR__ . '/../patient_data.php';

$stmt = $conn->prepare(
    "SELECT reminder_id, reminder_title, reminder_date, reminder_time
     FROM Reminder
     WHERE patient_user_id = ?
     ORDER BY reminder_date ASC, reminder_time ASC"
);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$reminders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointments — Apeksha OncoCare</title>
<link rel="stylesheet" href="../../../public/css/base.css?v=3">
<link rel="stylesheet" href="../../../public/css/appointments.css">
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
        <li><a href="patient_appointments.php" class="nav-item active" title="Appointments">
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
          <h2>Appointments</h2>
          <p class="date">View and track your upcoming and past visits</p>
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
          <h3>My Reminders</h3>
        </div>

        <form method="POST" action="../patient_actions.php" class="reminder-form">
          <input type="hidden" name="action" value="add_reminder">
          <input name="reminder_title" class="reminder-input" type="text" placeholder="Enter a reminder" required>
          <input name="reminder_date" class="reminder-input" type="date" required>
          <input name="reminder_time" class="reminder-input" type="time">
          <button type="submit" class="request-btn reminder-add-btn">+ Add Reminder</button>
        </form>

        <ul class="reminder-list">
          <?php if ($reminders->num_rows === 0): ?>
            <li class="reminder-empty">No reminders added yet.</li>
          <?php else: ?>
            <?php while ($reminder = $reminders->fetch_assoc()): ?>
              <li class="reminder-item">
                <div class="reminder-details">
                  <p class="reminder-title"><?= htmlspecialchars($reminder['reminder_title']) ?></p>
                  <p class="reminder-date">
                    <?= htmlspecialchars(date("M j, Y g:i A", strtotime($reminder['reminder_date'] . " " . (isset($reminder['reminder_time']) ? $reminder['reminder_time'] : '00:00:00')))) ?>
                  </p>
                </div>
                <form method="POST" action="../patient_actions.php">
                  <input type="hidden" name="action" value="delete_reminder">
                  <input type="hidden" name="reminder_id" value="<?= (int)$reminder['reminder_id'] ?>">
                  <button type="submit" class="reminder-delete-btn"
                          onclick="return confirm('Delete this reminder?');">Delete</button>
                </form>
              </li>
            <?php endwhile; ?>
          <?php endif; ?>
        </ul>
      </div>


      <div class="card list-card wide-card">
        <div class="list-card-header">
          <h3>Upcoming Appointments</h3>
          <div class="filter-pills">
            <span class="pill pill-active">All</span>
            <span class="pill">Upcoming</span>
            <span class="pill">Past</span>
          </div>
        </div>

        <ul class="appointment-list">
          <?php foreach ($appointments as $appointment): ?>
            <?php if ($appointment['appointment_status'] !== 'upcoming') { continue; } ?>
            <li class="appointment-item confirmed">
              <div class="appointment-time"><span class="time"><?= htmlspecialchars(date('g:i A', strtotime($appointment['appointment_time']))) ?></span><span class="date"><?= htmlspecialchars(date('M j, Y', strtotime($appointment['appointment_date']))) ?></span></div>
              <div class="appointment-info">
                <p class="appointment-name"><?= htmlspecialchars($appointment['reason'] ?: 'Appointment') ?></p>
                <p class="appointment-desc"><?= htmlspecialchars('Dr. ' . $appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']) ?></p>
              </div>
              <span class="status status-confirmed">upcoming</span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>



      <div class="card list-card wide-card">
        <div class="list-card-header">
          <h3>Past Appointments</h3>
        </div>

        <ul class="appointment-list">
          <?php foreach ($appointments as $appointment): ?>
            <?php if ($appointment['appointment_status'] !== 'completed') { continue; } ?>
            <li class="appointment-item completed">
              <div class="appointment-time"><span class="time"><?= htmlspecialchars(date('g:i A', strtotime($appointment['appointment_time']))) ?></span><span class="date"><?= htmlspecialchars(date('M j, Y', strtotime($appointment['appointment_date']))) ?></span></div>
              <div class="appointment-info">
                <p class="appointment-name"><?= htmlspecialchars($appointment['reason'] ?: 'Appointment') ?></p>
                <p class="appointment-desc"><?= htmlspecialchars('Dr. ' . $appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']) ?></p>
              </div>
              <span class="status status-completed">completed</span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

    </section>
  </main>
</div>


</body>
</html>
