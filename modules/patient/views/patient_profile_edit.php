<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'patient') {
    header("Location: ../../../index.php");
    exit();
}
$patient_id = (int) $_SESSION['user_id'];
?>
<?php
$stmt = $conn->prepare("SELECT u.email, u.phone, p.nic, p.first_name, p.last_name, p.dob, p.gender, p.address, p.city, p.blood_group, p.allergies
                        FROM User u JOIN Patient p ON u.user_id = p.user_id
                        WHERE u.user_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
if (!$patient) { die("Patient profile not found."); }
$full_name = trim($patient['first_name'].' '.$patient['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile — Apeksha OncoCare</title>
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
        <p class="user-name">Sandun Jayasekara</p>
        <p class="user-role">Patient</p>
      </div>
    </div>
  </aside>

  <main class="main">

    <header class="topbar">
      <div class="topbar-left">
        <div>
          <h2>Edit Profile</h2>
          <p class="date">Update your personal and contact information</p>
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

      <div class="card wide-card form-card">
        <h3 class="form-title">Edit Profile</h3>
        <p class="form-subtitle">Update your personal and contact information. Medical information can only be changed by your care team.</p>

        <form class="app-form" action="../patient_actions.php" method="post">
<input type="hidden" name="action" value="update_profile">

          <p class="form-section-label">Personal Information</p>
          <div class="form-grid">
            <div class="form-field">
              <label for="fullname">Full Name</label>
              <input type="text" id="fullname" name="fullname" value="Sandun Jayasekara">
            </div>
            <div class="form-field">
              <label for="dob">Date of Birth</label>
              <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($patient['dob']) ?>">
            </div>
            <div class="form-field">
              <label for="gender">Gender</label>
              <select id="gender" name="gender">
                <option value="male" <?= $patient['gender']==='male'?'selected':'' ?>>Male</option>
                <option value="female" <?= $patient['gender']==='female'?'selected':'' ?>>Female</option>
                <option value="other" <?= $patient['gender']==='other'?'selected':'' ?>>Other</option>
              </select>
            </div>
            <div class="form-field">
              <label for="nic">NIC Number</label>
              <input type="text" id="nic" name="nic" value="<?= htmlspecialchars($patient['nic']) ?>">
            </div>
            <div class="form-field form-field-wide">
              <label for="address">Address</label>
              <input type="text" id="address" name="address" value="<?= htmlspecialchars($patient['address']) ?>">
            </div>
          <div class="form-field">
                <label for="city">City</label>
                <input type="text" id="city" name="city" value="<?= htmlspecialchars($patient['city']) ?>">
              </div>
            </div>

          <p class="form-section-label">Contact Details</p>
          <div class="form-grid">
            <div class="form-field">
              <label for="mobile">Mobile Number</label>
              <input type="tel" id="mobile" name="phone" value="<?= htmlspecialchars($patient['phone']) ?>">
            </div>
            <div class="form-field">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?= htmlspecialchars($patient['email']) ?>">
            </div>
            <div class="form-field">
              <label for="emergency-name">Emergency Contact Name</label>
              <input type="text" id="emergency-name" name="emergency-name" value="Nadeesha Jayasekara">
            </div>
            <div class="form-field">
              <label for="emergency-number">Emergency Contact Number</label>
              <input type="tel" id="emergency-number" name="emergency-number" value="+94 77 987 6543">
            </div>
          </div>

          <div class="form-actions">
            <a href="patient_profile.php" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save Changes</button>
          </div>

        </form>
      </div>

    </section>
  </main>
</div>

</body>
</html>
