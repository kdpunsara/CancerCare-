<?php
/*
 * SHARED SIDEBAR
 *
 * Hama pageekama <body><div class="app"> ekata passe meka include karanna:
 *
 *     $activePage = 'patients';
 *     require __DIR__ . '/sidebar.php';
 *
 * $activePage values:
 *     dashboard | patients | register | appointments | reports | doctors | benefactor
 *
 * Link ekak wenas karanna ona nam, methana witharak wenas karanna.
 */

$activePage = $activePage ?? '';

$navItems = [
    'dashboard'    => ['staff_dashboard.php',     'icon-dashboard',    'Dashboard'],
    'patients'     => ['staff_patient.php',       'icon-profile',      'Patients'],
    'register'     => ['Register_patient.php',    'icon-register',     'Register Patient'],
    'appointments' => ['staff_appointment.php',   'icon-appointments', 'Appointments'],
    'reports'      => ['medical_reports.php',     'icon-records',      'Medical Reports'],
    'doctors'      => ['doctor_availability.php', 'icon-doctor',       'Doctor Availability'],
    'benefactor'   => ['benefactor.php',          'icon-benefactor',   'Benefactor'],
];
?>
<style>
    /* SIDEBAR SVG ICONS */
    .icon {
        display: inline-block;
        width: 20px;
        height: 20px;
        background-repeat: no-repeat;
        background-position: center;
        background-size: contain;
        flex-shrink: 0;
    }

    .nav-item .icon {
        width: 18px;
        height: 18px;
    }

    .icon-dashboard {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='3' y='3' width='7' height='9' rx='1.5'/><rect x='14' y='3' width='7' height='5' rx='1.5'/><rect x='14' y='12' width='7' height='9' rx='1.5'/><rect x='3' y='16' width='7' height='5' rx='1.5'/></svg>");
    }

    .icon-profile {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='8' r='4'/><path d='M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7'/></svg>");
    }

    .icon-register {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M15 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'/><circle cx='8.5' cy='7' r='4'/><path d='M20 8v6M17 11h6'/></svg>");
    }

    .icon-appointments {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='3' y='5' width='18' height='16' rx='2'/><path d='M16 3v4M8 3v4M3 10h18'/></svg>");
    }

    .icon-records {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M7 3h7l4 4v14H7z'/><path d='M9 12h6M9 16h6M9 8h2'/></svg>");
    }

    .icon-doctor {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='7' r='4'/><path d='M5 21v-2a7 7 0 0 1 14 0v2'/><path d='M17 16l2 2 4-4'/></svg>");
    }

    .icon-benefactor {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='9' cy='7' r='4'/><path d='M2 21v-2a7 7 0 0 1 14 0v2'/><path d='M16 11h6M19 8v6'/></svg>");
    }
</style>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">❤</div>
            <div>
                Cancer care
                <span class="logo-sub">Cancer Patient Care</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">

        <div class="nav-section">Navigation</div>

        <?php foreach ($navItems as $key => $item): ?>
            <a href="<?= $item[0] ?>" class="nav-item<?= $key === $activePage ? ' active' : '' ?>">
                <span class="icon <?= $item[1] ?>"></span>
                <?= $item[2] ?>
            </a>
        <?php endforeach; ?>

    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">NP</div>
            <div class="user-info">
                <strong>Nimali Perera</strong>
                <span>Medical Staff</span>
            </div>
        </div>
    </div>

</aside>