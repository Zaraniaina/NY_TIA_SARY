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
        <div class="sidebar-section-label">Documents</div>
        <a href="<?= $root ?>espace/client/contrats.php"
            class="<?= $current === 'contrats.php' ? 'active' : '' ?>">
            <i class="fas fa-file-signature"></i> Mes Contrats
        </a>
        <a href="<?= $root ?>espace/client/factures.php"
            class="<?= $current === 'factures.php' ? 'active' : '' ?>">
            <i class="fas fa-file-invoice-dollar"></i> Mes Factures
        </a>
        <a href="<?= $root ?>espace/client/paiements.php"
            class="<?= $current === 'paiements.php' ? 'active' : '' ?>" style="padding-left:2.2rem;font-size:0.88rem;">
            <i class="fas fa-coins"></i> Mes Paiements
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
        <!-- Déconnexion avec confirmation modal -->
        <a href="#" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </nav>

</aside>
<?php
// le modal de déconnexion est inclus ici
include __DIR__ . '/../../composante/modalDeconexionClient.php';
?>
<script>
    (function() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        const STORAGE_KEY = 'sidebarScrollPos';

        // Restaurer la position de scroll au chargement de la page
        const savedPos = sessionStorage.getItem(STORAGE_KEY);
        if (savedPos !== null) {
            sidebar.scrollTop = parseInt(savedPos, 10);
        }

        // Sauvegarder la position de scroll avant de quitter la page
        // (à chaque clic sur un lien de navigation du sidebar)
        sidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                sessionStorage.setItem(STORAGE_KEY, sidebar.scrollTop);
            });
        });

        // Filet de sécurité : sauvegarder aussi en continu pendant le scroll
        // (utile si l'utilisateur ferme l'onglet ou navigue autrement)
        sidebar.addEventListener('scroll', () => {
            sessionStorage.setItem(STORAGE_KEY, sidebar.scrollTop);
        });
    })();
</script>