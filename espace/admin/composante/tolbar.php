<?php
require_once __DIR__.'/tolbarDto.php';
?>
<div class="dashboard-topbar">
            <div style="display:flex;align-items:center;gap:14px;">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <span class="topbar-title"><?php echo $titre; ?></span>
            </div>
            <div class="topbar-user">
                <div style="position: relative; display: flex; align-items: center;">
                    <button class="topbar-notification" id="notificationToggle" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($notifCount > 0): ?>
                            <span class="topbar-notification-count"><?= $notifCount ?></span>
                        <?php endif; ?>
                    </button>
                    <?php require_once __DIR__.'/notificationDropdown.php'; ?>
                </div>
                <div class="topbar-user-info">
                    <span class="topbar-user-name"><?= htmlspecialchars($adminNom." ".$adminPrenom) ?></span>
                    <span class="topbar-user-role" style="color:var(--primary-red);">Administrateur</span>
                </div>
                <img src="../../<?= htmlspecialchars($photoAdmin) ?>" alt="Avatar" class="topbar-avatar" style="object-fit: cover;">
                
            </div>
</div>

<script>
// Notification Dropdown - chargé dynamiquement
document.addEventListener('DOMContentLoaded', function() {
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationDropdownClose = document.getElementById('notificationDropdownClose');
    const dropdownFilters = document.querySelectorAll('.notif-dropdown-filter');
    const dropdownCards = document.querySelectorAll('.notification-dropdown-card');

    // Ouvrir/fermer le dropdown
    notificationToggle?.addEventListener('click', function(e) {
        e.stopPropagation();
        // Check if mobile or tablet (width <= 1024px)
        if (window.innerWidth <= 1024) {
            // Redirect to notifications page
            window.location.href = 'notifications.php';
        } else {
            notificationDropdown?.classList.toggle('open');
        }
    });

    // Fermer le dropdown en cliquant à l'extérieur
    document.addEventListener('click', function(e) {
        if (notificationDropdown && !notificationToggle?.contains(e.target) && !notificationDropdown.contains(e.target)) {
            notificationDropdown.classList.remove('open');
        }
    });

    // Fermer le dropdown avec le bouton X
    notificationDropdownClose?.addEventListener('click', function() {
        notificationDropdown.classList.remove('open');
    });

    // Filtrer les notifications dans le dropdown
    dropdownFilters.forEach(function(button) {
        button.addEventListener('click', function() {
            dropdownFilters.forEach(function(item) { item.classList.remove('active'); });
            button.classList.add('active');

            var selected = button.dataset.filter || 'all';
            dropdownCards.forEach(function(card) {
                var shouldShow = selected === 'all' || card.dataset.filter === 'unread';
                card.style.display = shouldShow ? 'flex' : 'none';
            });
        });
    });
});
</script>
