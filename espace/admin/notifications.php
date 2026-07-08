<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();
require_once __DIR__ . '/../../config/database.php';

$adminEmail = $_SESSION['admin_email'] ?? 'Admin';
$adminId    = (int) ($_SESSION['admin_id'] ?? 0);
$pdo        = getPDO();

$stmtPhoto = $pdo->prepare('SELECT PHOTO_CLIENT FROM CLIENT WHERE ID_AUTH = ?');
$stmtPhoto->execute([$adminId]);
$photoAdmin = $stmtPhoto->fetchColumn() ?: 'assets/images/avatar.png';
$isDefaultPhoto = ($photoAdmin === 'assets/images/avatar.png');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/composante/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div style="display:flex;align-items:center;gap:14px;">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <span class="topbar-title">Notifications</span>
            </div>
            <div class="topbar-user">
                <div class="topbar-user-info">
                    <span class="topbar-user-name"><?= htmlspecialchars($adminEmail) ?></span>
                    <span class="topbar-user-role" style="color:var(--primary-red);">Administrateur</span>
                </div>
                <?php if ($isDefaultPhoto): ?>
                    <div class="topbar-avatar admin-avatar"><i class="fas fa-shield-alt" style="font-size:.85rem;"></i></div>
                <?php else: ?>
                    <img src="../../<?= htmlspecialchars($photoAdmin) ?>" alt="Avatar" class="topbar-avatar" style="object-fit: cover;">
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="dash-page-header">
                <h2>Notifications</h2>
                <p>Page en construction</p>
            </div>
        </div>
    </div>
</div>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });
</script>
</body>
</html>
