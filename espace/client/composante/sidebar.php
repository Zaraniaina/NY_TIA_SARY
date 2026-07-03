<?php
// sidebar.php — Sidebar de l'espace client
$current = basename($_SERVER['PHP_SELF']);
$root    = '../../';
?>
<aside class="dashboard-sidebar" id="sidebar">
    <div class="sidebar-logo">
        <span class="logo-main">NY TIA SARY</span>
        <span class="logo-sub">Photography Studio</span>
        <span class="sidebar-role-badge">Client</span>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Navigation</div>
        <a href="<?= $root ?>espace/client/home.php"
           class="<?= $current === 'home.php' ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="<?= $root ?>espace/client/reservations.php"
           class="<?= $current === 'reservations.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i> Mes Réservations
        </a>
        <a href="<?= $root ?>espace/client/devis.php"
           class="<?= $current === 'devis.php' ? 'active' : '' ?>">
            <i class="fas fa-file-invoice"></i> Demande de Devis
        </a>
        <a href="<?= $root ?>espace/client/mes_photos.php"
           class="<?= $current === 'mes_photos.php' ? 'active' : '' ?>">
            <i class="fas fa-images"></i> Mes Photos
        </a>

        <div class="sidebar-divider"></div>
        <div class="sidebar-section-label">Compte</div>
        <a href="<?= $root ?>index.php" target="_blank">
            <i class="fas fa-globe"></i> Site public
        </a>
    </nav>

    <div class="sidebar-logout">
        <div class="sidebar-divider"></div>
        <a href="<?= $root ?>espace/client/logout.php">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</aside>
