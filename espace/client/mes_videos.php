<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();
require_once __DIR__ . '/composante/tolbarDto.php';
$titre = "Mes Vidéos";
$typeMedia = "VIDEO";

// Récupérer tous les médias liés aux réservations du client
$stmt = $pdo->prepare(
    'SELECT m.PATH_MEDIA, m.ID_MEDIA, p.LIB_PRESTATION, r.DATE_RESERVATION
     FROM MEDIA m
     JOIN RESERVATION r ON m.ID_RESERVATION = r.ID_RESERVATION
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     WHERE r.ID_CLIENT = ? AND m.TYPE_MEDIA = ?
     ORDER BY r.DATE_RESERVATION DESC'
);
$stmt->execute([$clientId, $typeMedia]);
$medias = $stmt->fetchAll();

$videoExts = ['mp4', 'webm', 'ogg', 'mov'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Videos | NY TIA SARY</title>
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
        <?php include __DIR__ . '/composante/tolbar.php'; ?>

        <div class="dashboard-content">
            <nav class="dash-breadcrumb">
                <a href="home.php">Dashboard</a>
                <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Mes Videos</span>
            </nav>

            <div class="dash-page-header">
                <h2>Ma Galerie</h2>
                <p><?= count($medias) ?> fichier(s) livré(s) par le studio</p>
            </div>

            <?php if (empty($medias)): ?>
                <div class="dash-card">
                    <div class="empty-state">
                        <i class="fas fa-video"></i>
                        <p>Aucune vidéo livrée pour le moment.<br>
                           Vos vidéos apparaîtront ici après la réalisation de vos séances.</p>
                    </div>
                </div>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;">
                <?php foreach ($medias as $m): ?>
                    <?php
                    $path    = htmlspecialchars($m['PATH_MEDIA']);
                    $absPath = '../../' . $path;
                    $ext     = strtolower(pathinfo($m['PATH_MEDIA'], PATHINFO_EXTENSION));
                    ?>
                    <div class="dash-card" style="overflow:hidden;">
                        <video controls style="width:100%;border-radius:10px 10px 0 0;background:#000;max-height:220px;" preload="metadata">
                            <source src="<?= $absPath ?>" type="video/<?= $ext === 'mov' ? 'mp4' : $ext ?>">
                            Votre navigateur ne supporte pas la lecture de vidéo.
                        </video>
                        <div style="padding:14px 16px;">
                            <div style="font-weight:600;font-size:0.9rem;margin-bottom:4px;"><?= htmlspecialchars($m['LIB_PRESTATION']) ?></div>
                            <div style="font-size:0.78rem;color:#aaa;margin-bottom:12px;"><?= date('d/m/Y', strtotime($m['DATE_RESERVATION'])) ?></div>
                            <a href="<?= $absPath ?>" download class="btn-dash btn-dash-outline btn-dash-sm" style="width:100%;justify-content:center;">
                                <i class="fas fa-download"></i> Télécharger
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// Lightbox
const lb    = document.getElementById('lightbox');
const lbImg = document.getElementById('lbImg');
document.querySelectorAll('.photo-item').forEach(item => {
    item.addEventListener('click', e => {
        if (e.target.closest('a')) return;
        if (item.dataset.type === 'image') {
            lbImg.src = item.dataset.src;
            lb.style.display = 'flex';
        }
    });
});
document.getElementById('lbClose')?.addEventListener('click', () => { lb.style.display = 'none'; lbImg.src = ''; });
lb?.addEventListener('click', e => { if (e.target === lb) { lb.style.display = 'none'; lbImg.src = ''; } });
document.addEventListener('keydown', e => { if (e.key === 'Escape') { lb.style.display = 'none'; } });
</script>
</body>
</html>
