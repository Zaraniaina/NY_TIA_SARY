<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();

require_once __DIR__ . '/composante/tolbarDto.php';
$titre = "Mes Photos";
$typeMedia = "IMAGE";
// Récupérer tous les médias liés aux réservations du client
$stmt = $pdo->prepare(
    'SELECT m.PATH_MEDIA, m.ID_MEDIA, p.LIB_PRESTATION, r.DATE_RESERVATION
     FROM MEDIA m
     JOIN RESERVATION r ON m.ID_RESERVATION = r.ID_RESERVATION
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     WHERE r.ID_CLIENT = ? and m.TYPE_MEDIA = ?
     ORDER BY r.DATE_RESERVATION DESC'
);
$stmt->execute([$clientId,$typeMedia]);
$medias = $stmt->fetchAll();

// Extensions image
$imgExts = ['jpg','jpeg','png','webp','gif'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Photos | NY TIA SARY</title>
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
                <span>Mes Photos</span>
            </nav>

            <div class="dash-page-header">
                <h2>Ma Galerie</h2>
                <p><?= count($medias) ?> fichier(s) livré(s) par le studio</p>
            </div>

            <?php if (empty($medias)): ?>
                <div class="dash-card">
                    <div class="empty-state">
                        <i class="fas fa-images"></i>
                        <p>Aucune photo livrée pour le moment.<br>
                           Vos photos apparaîtront ici après la réalisation de vos séances.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="photo-grid">
                <?php foreach ($medias as $m): ?>
                    <?php
                    $path = htmlspecialchars($m['PATH_MEDIA']);
                    $absPath = '../../' . $path;
                    $ext = strtolower(pathinfo($m['PATH_MEDIA'], PATHINFO_EXTENSION));
                    $isImage = in_array($ext, $imgExts, true);
                    ?>
                    <div class="photo-item" data-src="<?= $absPath ?>" data-type="<?= $isImage ? 'image' : 'file' ?>">
                        <?php if ($isImage): ?>
                            <img src="<?= $absPath ?>" alt="Photo <?= (int) $m['ID_MEDIA'] ?>" loading="lazy">
                        <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#f0f0ec;">
                                <i class="fas fa-file-pdf" style="font-size:3rem;color:var(--primary-red);"></i>
                                <p style="font-size:0.8rem;margin-top:8px;color:#666;"><?= strtoupper($ext) ?></p>
                            </div>
                        <?php endif; ?>
                        <div class="photo-overlay">
                            <a href="<?= $absPath ?>" target="_blank" title="Voir en grand"><i class="fas fa-expand-alt"></i></a>
                            <a href="<?= $absPath ?>" download title="Télécharger"><i class="fas fa-download"></i></a>
                        </div>
                        <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,0.7));padding:10px;pointer-events:none;">
                            <p style="color:#fff;font-size:0.72rem;font-family:var(--font-headings);margin:0;"><?= htmlspecialchars($m['LIB_PRESTATION']) ?></p>
                            <p style="color:rgba(255,255,255,0.7);font-size:0.68rem;margin:2px 0 0;"><?= date('d/m/Y', strtotime($m['DATE_RESERVATION'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>

                <!-- LIGHTBOX -->
                <div id="lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.95);z-index:3000;align-items:center;justify-content:center;padding:40px;">
                    <button id="lbClose" style="position:absolute;top:20px;right:28px;background:none;border:none;color:#fff;font-size:2rem;cursor:pointer;">&times;</button>
                    <img id="lbImg" src="" alt="" style="max-width:90%;max-height:85vh;border-radius:6px;border:4px solid rgba(255,255,255,0.15);">
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
