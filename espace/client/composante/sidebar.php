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
        <div class="sidebar-section-label">Media</div>
        <a href="<?= $root ?>espace/client/mes_photos.php"
           class="<?= $current === 'mes_photos.php' ? 'active' : '' ?>">
            <i class="fas fa-images"></i> Mes Photos
        </a>
        <a href="<?= $root ?>espace/client/mes_videos.php"
           class="<?= $current === 'mes_videos.php' ? 'active' : '' ?>">
            <i class="fas fa-video"></i> Videos
        </a>

        <div class="sidebar-divider"></div>
        <div class="sidebar-section-label">Compte</div>
        <a href="<?= $root ?>espace/client/parametres.php"
           class="<?= $current === 'parametres.php' ? 'active' : '' ?>">
            <i class="fas fa-cogs"></i> Paramètres
        </a>
        <a href="<?= $root ?>index.php">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </nav>

</aside>
