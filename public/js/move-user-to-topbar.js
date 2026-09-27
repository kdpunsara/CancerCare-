document.addEventListener('DOMContentLoaded', function () {
    var userIdentity = document.querySelector('.sidebar-user, .sidebar-footer .user-card');
    var topbarActions = document.querySelector('.topbar-actions, .topbar-right');

    if (!userIdentity || !topbarActions) {
        return;
    }

    var sidebarFooter = userIdentity.closest('.sidebar-footer');
    userIdentity.classList.add('topbar-user');
    topbarActions.prepend(userIdentity);

    if (sidebarFooter) {
        sidebarFooter.hidden = true;
    }
});
