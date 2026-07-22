<?php
require_once __DIR__.'/tolbarDto.php';
$pdo = getPDO();
$clientId = (int)($_SESSION['CLIENT_ID'] ?? 0);
$notifCount = 0;
if ($clientId > 0) {
    $stmtNotif = $pdo->prepare('SELECT COUNT(*) FROM notification WHERE ID_CLIENT = ? AND SUP_NOTIF = 0 AND LU_NOTIF = 0');
    $stmtNotif->execute([$clientId]);
    $notifCount = (int)$stmtNotif->fetchColumn();
}
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
            <span class="topbar-user-name"><?= htmlspecialchars($clientPrenom . ' ' . $clientNom) ?></span>
            <span class="topbar-user-role">Client</span>
        </div>
        <img src="../../<?= htmlspecialchars($photoClient) ?>" alt="Avatar" class="topbar-avatar" style="object-fit: cover;">
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationDropdownClose = document.getElementById('notificationDropdownClose');
    const dropdownFilters = document.querySelectorAll('.notif-dropdown-filter');
    const dropdownCards = document.querySelectorAll('.notification-dropdown-card');

    notificationToggle?.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown?.classList.toggle('open');
    });

    notificationDropdownClose?.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown?.classList.remove('open');
    });

    document.addEventListener('click', function(e) {
        if (notificationDropdown?.classList.contains('open') && 
            !notificationDropdown.contains(e.target) && 
            e.target !== notificationToggle) {
            notificationDropdown.classList.remove('open');
        }
    });

    notificationDropdown?.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    dropdownFilters.forEach(filterBtn => {
        filterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            dropdownFilters.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const filterValue = this.getAttribute('data-filter');
            
            dropdownCards.forEach(card => {
                if (filterValue === 'all') {
                    card.style.display = 'block';
                } else if (filterValue === 'unread') {
                    if (card.getAttribute('data-filter') === 'unread') {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        });
    });
});
</script>