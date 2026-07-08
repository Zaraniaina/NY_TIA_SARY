<?php
// sidebar.php — Sidebar de l'espace admin
$current = basename($_SERVER['PHP_SELF']);
$root    = '../../';
?>
<aside class="dashboard-sidebar" id="sidebar">
    <div class="sidebar-logo">
        <span class="logo-main">NY TIA SARY</span>
        <span class="logo-sub">Photography Studio</span>
        <span class="sidebar-role-badge admin">Administrateur</span>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Tableau de bord</div>
        <a href="<?= $root ?>espace/admin/home.php"
           class="<?= $current === 'home.php' ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <div class="sidebar-section-label">Voir notification</div>
        <a href="<?= $root ?>espace/admin/notifications.php"
           class="<?= $current === 'notifications.php' ? 'active' : '' ?>">
            <i class="fas fa-bell"></i> Notification
        </a>

        <div class="sidebar-section-label">Gestion</div>
        <a href="<?= $root ?>espace/admin/reservations.php"
           class="<?= $current === 'reservations.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i> Réservations
        </a>
        <a href="<?= $root ?>espace/admin/clients.php"
           class="<?= $current === 'clients.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Clients
        </a>
        <a href="<?= $root ?>espace/admin/devis.php"
           class="<?= $current === 'devis.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i> Devis
        </a>

        <div class="sidebar-section-label">Contenu</div>
        <a href="<?= $root ?>espace/admin/blog.php"
           class="<?= $current === 'blog.php' ? 'active' : '' ?>">
            <i class="fas fa-newspaper"></i> Blog
        </a>
        <a href="<?= $root ?>espace/admin/prestations.php"
           class="<?= $current === 'prestations.php' ? 'active' : '' ?>">
            <i class="fas fa-concierge-bell"></i> Prestations
        </a>

        <div class="sidebar-divider"></div>
        <a href="<?= $root ?>espace/admin/parametres.php"
           class="<?= $current === 'parametres.php' ? 'active' : '' ?>">
            <i class="fas fa-cogs"></i> Paramètres
        </a>
        <!-- Déconnexion avec confirmation modal -->
        <a href="#" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </nav>
</aside>
<?php
// le modal de déconnexion est inclus ici
include __DIR__ . '/../../composante/modalDeconexionAdmin.php';
?>