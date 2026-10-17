<?php
// Shared top bar. Expects $page_title.
?>
        <header class="topbar">
            <div class="topbar-left" style="display:flex; align-items:center;">
                <button class="menu-toggle" onclick="openSidebar()" aria-label="Open menu">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <h1><?php echo e($page_title ?? 'Pharmacy'); ?></h1>
            </div>
            <div class="topbar-right" style="display: flex; flex-direction: row; align-items: center; gap: 16px;">
                <div class="topbar-user user-card">
                    <div class="user-avatar" style="background: linear-gradient(135deg, #0f766e, #0284c7);">
                        <?php echo e($pharmacist_initials); ?>
                    </div>
                    <div class="user-info">
                        <strong><?php echo e($pharmacist_name); ?></strong>
                        <span>Pharmacist</span>
                    </div>
                </div>
                <a href="../../../logout.php" class="btn btn-primary">Sign Out</a>
            </div>
        </header>
