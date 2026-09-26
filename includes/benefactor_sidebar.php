<?php
$current_page = $current_page ?? 'dashboard';
$role_display = $role_display
    ?? (isset($profile['benefactor_type']) ? ucfirst($profile['benefactor_type']) . ' Benefactor' : 'Benefactor');
?>
<aside class="sidebar">
    <div class="brand">
        <div class="brand-mark">CC</div>
        <div class="brand-text">
            <h1>CancerCare</h1>
            <p>Benefactor Portal</p>
        </div>
    </div>

    <nav class="nav">
        <p class="nav-label">Main Menu</p>
        <ul>
            <li><a href="dashboard.php" class="nav-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="icon icon-dashboard" aria-hidden="true"></span>
                <span class="nav-text">Dashboard</span>
            </a></li>
            <li><a href="patients.php" class="nav-item <?php echo $current_page === 'patients' ? 'active' : ''; ?>">
                <span class="icon icon-records" aria-hidden="true"></span>
                <span class="nav-text">Patients in Need</span>
            </a></li>
            <li><a href="make_donation.php" class="nav-item <?php echo $current_page === 'donate' ? 'active' : ''; ?>">
                <span class="icon icon-transport" aria-hidden="true"></span>
                <span class="nav-text">Make a Donation</span>
            </a></li>
            <li><a href="donation_history.php" class="nav-item <?php echo $current_page === 'history' ? 'active' : ''; ?>">
                <span class="icon icon-prescriptions" aria-hidden="true"></span>
                <span class="nav-text">Donation History</span>
            </a></li>
            <li><a href="benefactor_profile.php" class="nav-item <?php echo $current_page === 'profile' ? 'active' : ''; ?>">
                <span class="icon icon-profile" aria-hidden="true"></span>
                <span class="nav-text">My Profile</span>
            </a></li>
        </ul>
    </nav>

    <div class="sidebar-user">
        <div class="avatar"><?php echo htmlspecialchars($initials); ?></div>
        <div>
            <p class="user-name"><?php echo htmlspecialchars($full_name); ?></p>
            <p class="user-role"><?php echo htmlspecialchars($role_display); ?></p>
        </div>
    </div>
</aside>
