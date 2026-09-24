<?php
// Shared sidebar for the pharmacist module.
// Expects: $current_page, $pharmacist_name, $pharmacist_initials (set by pharmacist_init.php / the page)
$current_page = $current_page ?? 'dashboard';
$nav_active = function (string $page) use ($current_page): string {
    return $current_page === $page ? ' active' : '';
};
?>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    <!-- ================= SIDEBAR ================= -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <button class="sidebar-close" onclick="closeSidebar()" aria-label="Close menu">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            <div class="logo">
                <svg width="20" height="20" viewBox="0 0 24 24" style="border-radius: 6px; overflow: hidden;">
                    <image href="../../../public/images/pharmacy-logo.jpeg" x="0" y="0" width="24" height="24" />
                </svg>
                <div>CancerCare<span class="logo-sub">Pharmacy Module</span></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">Inventory Management</div>
            <a href="dashboard.php" class="nav-item<?php echo $nav_active('dashboard'); ?>">
                <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg></span>
                Dashboard
            </a>
            <a href="add-drug.php" class="nav-item<?php echo $nav_active('add-drug'); ?>">
                <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></span>
                Add New Drug
            </a>
            <a href="expiry-tracking.php" class="nav-item<?php echo $nav_active('expiry-tracking'); ?>">
                <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></span>
                Expiry Tracking
            </a>
            <div class="nav-section">Account</div>
            <a href="profile.php" class="nav-item<?php echo $nav_active('profile'); ?>">
                <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></span>
                My Profile
            </a>
            <a href="help.php" class="nav-item<?php echo $nav_active('help'); ?>">
                <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></span>
                Help &amp; Support
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar" style="background: linear-gradient(135deg, #0f766e, #0284c7);"><?php echo e($pharmacist_initials); ?></div>
                <div class="user-info"><strong><?php echo e($pharmacist_name); ?></strong><span>Pharmacist</span></div>
            </div>
        </div>
    </aside>
