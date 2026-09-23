<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account — Apeksha OncoCare</title>
<link rel="stylesheet" href="../../../public/css/base.css">
<link rel="stylesheet" href="../../../public/css/auth.css">
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
          <h2>Create Account</h2>
          <p class="date">Register as a new patient</p>
        </div>
      </div>
      <div class="topbar-actions">
      </div>
    </header>

    <section class="content">

      <div class="auth-page-wrap">
        <div class="auth-card auth-card-wide">
          <h2 class="auth-title">Create your account</h2>
          <p class="auth-subtitle">Register as a patient to track your care online.</p>

          <form class="app-form" action="patient_register.php" method="get">

            <div class="form-grid">
              <div class="form-field">
                <label for="reg-name">Full Name</label>
                <input type="text" id="reg-name" name="reg-name" placeholder="Your full name" required>
              </div>
              <div class="form-field">
                <label for="reg-nic">NIC Number</label>
                <input type="text" id="reg-nic" name="reg-nic" placeholder="200012345678" required>
              </div>
              <div class="form-field">
                <label for="reg-dob">Date of Birth</label>
                <input type="date" id="reg-dob" name="reg-dob" required>
              </div>
              <div class="form-field">
                <label for="reg-mobile">Mobile Number</label>
                <input type="tel" id="reg-mobile" name="reg-mobile" placeholder="+94 7X XXX XXXX" required>
              </div>
              <div class="form-field form-field-wide">
                <label for="reg-email">Email Address</label>
                <input type="email" id="reg-email" name="reg-email" placeholder="you@email.com" required>
              </div>
              <div class="form-field">
                <label for="reg-password">Password</label>
                <input type="password" id="reg-password" name="reg-password" placeholder="Create a password" required>
              </div>
              <div class="form-field">
                <label for="reg-confirm">Confirm Password</label>
                <input type="password" id="reg-confirm" name="reg-confirm" placeholder="Re-enter your password" required>
              </div>
            </div>

            <label class="checkbox-label checkbox-terms">
              <input type="checkbox" required> I agree to the Terms of Service and Privacy Policy
            </label>

            <button type="submit" class="btn-primary btn-full">Create Account</button>
          </form>
        </div>
      </div>

    </section>
  </main>
</div>

</body>
</html>
