<?php
// 1. Fetch Doctor Profile (Only if it hasn't been fetched already)
if (!isset($doctor_profile)) {
    $stmt_profile = $conn->prepare("SELECT first_name, last_name, specialization FROM Doctor WHERE user_id = ?");
    $stmt_profile->bind_param("i", $_SESSION['user_id']);
    $stmt_profile->execute();
    $doctor_profile = $stmt_profile->get_result()->fetch_assoc();
    
    $full_name = $doctor_profile['first_name'] . ' ' . $doctor_profile['last_name'];
    $specialization = $doctor_profile['specialization'];
    $initials = strtoupper(substr($doctor_profile['first_name'], 0, 1) . substr($doctor_profile['last_name'], 0, 1));
}

// 2. Determine which page is currently active
$current_page = $current_page ?? 'dashboard'; 
?>

<aside class="sidebar">
    <div class="brand">
        <div class="brand-mark">CC</div>
        <div class="brand-text">
            <h1>Cancer Care</h1>
            <p>Doctor Module</p>
        </div>
    </div>

    <nav class="nav">
        <p class="nav-label">Main Menu</p>
        <ul>
            <li><a href="doctor_dashboard.php" class="nav-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="icon icon-dashboard" aria-hidden="true"></span>
                <span class="nav-text">Dashboard</span>
            </a></li>
            <li><a href="appointments.php" class="nav-item <?php echo $current_page === 'appointments' ? 'active' : ''; ?>">
                <span class="icon icon-appointments" aria-hidden="true"></span>
                <span class="nav-text">Appointments</span>
            </a></li>
            <li><a href="doctor_mypatients.php" class="nav-item <?php echo $current_page === 'mypatients' ? 'active' : ''; ?>">
                <span class="icon icon-profile" aria-hidden="true"></span>
                <span class="nav-text">My Patients</span>
            </a></li>
            <li><a href="doctor_prescription.php" class="nav-item <?php echo $current_page === 'prescriptions' ? 'active' : ''; ?>">
                <span class="icon icon-prescriptions" aria-hidden="true"></span>
                <span class="nav-text">Prescriptions</span>
            </a></li>
        </ul>

        <p class="nav-label" style="margin-top: 16px;">Medical Records</p>
        <ul>
            <li><a href="view_records.php" class="nav-item <?php echo $current_page === 'records' ? 'active' : ''; ?>">
                <span class="icon icon-records" aria-hidden="true"></span>
                <span class="nav-text">View Records</span>
            </a></li>
        </ul>

        <p class="nav-label" style="margin-top: 16px;">Patient Support</p>
        <ul>
            <li><a href="doctor_mealplan.php" class="nav-item <?php echo $current_page === 'mealplan' ? 'active' : ''; ?>">
                <span class="icon icon-wellness" aria-hidden="true"></span>
                <span class="nav-text">Meal Plans</span>
            </a></li>
        </ul>

        <p class="nav-label" style="margin-top: 16px;">Account</p>
        <ul>
            <li><a href="doctor_profile.php" class="nav-item <?php echo $current_page === 'profile' ? 'active' : ''; ?>">
                <span class="icon icon-profile" aria-hidden="true"></span>
                <span class="nav-text">My Profile</span>
            </a></li>
        </ul>
    </nav>

    <div class="sidebar-user">
        <div class="avatar"><?php echo $initials; ?></div>
        <div>
            <p class="user-name"><?php echo htmlspecialchars($full_name); ?></p>
            <p class="user-role"><?php echo htmlspecialchars($specialization); ?></p>
        </div>
    </div>
</aside>