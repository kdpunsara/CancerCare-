<?php
// Fetch the logged-in admin's profile (only if not already fetched)
if (!isset($admin_profile)) {
    $stmt_ap = $conn->prepare("SELECT first_name, last_name, admin_level FROM Admin WHERE user_id = ?");
    $stmt_ap->bind_param("i", $_SESSION['user_id']);
    $stmt_ap->execute();
    $admin_profile = $stmt_ap->get_result()->fetch_assoc();

    if (!$admin_profile) {
        $admin_profile = ['first_name' => 'System', 'last_name' => 'Admin', 'admin_level' => 'regular'];
    }

    $admin_name  = $admin_profile['first_name'] . ' ' . $admin_profile['last_name'];
    $admin_role  = $admin_profile['admin_level'] === 'super' ? 'Super Administrator' : 'Administrator';
    $admin_init  = strtoupper(substr($admin_profile['first_name'], 0, 1) . substr($admin_profile['last_name'], 0, 1));
}

$current_page = $current_page ?? 'dashboard';
?>
<aside class="sidebar">
    <div class="brand">
        <div class="brand-mark">CC</div>
        <div class="brand-text">
            <h1>CancerCare</h1>
            <p>Admin Module</p>
        </div>
    </div>

    <nav class="nav">
        <p class="nav-label">System Administration</p>
        <ul>
            <li><a href="admin_dashboard.php" class="nav-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="icon icon-dashboard" aria-hidden="true"></span>
                <span class="nav-text">Dashboard</span>
            </a></li>
            <li><a href="user_management.php" class="nav-item <?php echo $current_page === 'users' ? 'active' : ''; ?>">
                <span class="icon icon-profile" aria-hidden="true"></span>
                <span class="nav-text">User Management</span>
            </a></li>
            <li><a href="operations.html" class="nav-item <?php echo $current_page === 'operations' ? 'active' : ''; ?>">
                <span class="icon icon-transport" aria-hidden="true"></span>
                <span class="nav-text">Operations</span>
            </a></li>
            <li><a href="analytics.html" class="nav-item <?php echo $current_page === 'analytics' ? 'active' : ''; ?>">
                <span class="icon icon-records" aria-hidden="true"></span>
                <span class="nav-text">Reports &amp; Analytics</span>
            </a></li>
            <li><a href="admin_profile.php" class="nav-item <?php echo $current_page === 'profile' ? 'active' : ''; ?>">
                <span class="icon icon-profile" aria-hidden="true"></span>
                <span class="nav-text">My Profile</span>
            </a></li>
        </ul>
    </nav>

    <div class="sidebar-user">
        <div class="avatar"><?php echo $admin_init; ?></div>
        <div>
            <p class="user-name"><?php echo htmlspecialchars($admin_name); ?></p>
            <p class="user-role"><?php echo htmlspecialchars($admin_role); ?></p>
        </div>
    </div>
</aside>