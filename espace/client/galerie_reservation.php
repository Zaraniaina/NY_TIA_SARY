<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();

require_once __DIR__ . '/composante/tolbarDto.php';

$idReservation = isset($_GET['id_reservation']) ? (int)$_GET['id_reservation'] : 0;
$typeMedia = isset($_GET['type']) ? strtoupper($_GET['type']) : 'IMAGE';

if ($idReservation <= 0) {
    header('Location: mes_photos.php');
    exit;
}

// Vérifier que la réservation appartient au client et récupérer les détails
$stmt = $pdo->prepare(
    'SELECT r.ID_RESERVATION, r.DATE_RESERVATION, r.LIEU_RESERVATION, r.COMME_RESERVATION, r.HEURE_RESERVATION,
            r.STATUS_RESERVATION, p.LIB_PRESTATION, p.ID_PRESTATION
     FROM RESERVATION r
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     WHERE r.ID_RESERVATION = ? AND r.ID_CLIENT = ?'
);
$stmt->execute([$idReservation, $clientId]);
$reservation = $stmt->fetch();

if (!$reservation) {
    header('Location: mes_photos.php');
    exit;
}

// Récupérer les médias de cette réservation du type demandé
$stmtMedia = $pdo->prepare(
    'SELECT m.PATH_MEDIA, m.ID_MEDIA
     FROM MEDIA m
     WHERE m.ID_RESERVATION = ? AND m.TYPE_MEDIA = ?
     ORDER BY m.ID_MEDIA ASC'
);
$stmtMedia->execute([$idReservation, $typeMedia]);
$medias = $stmtMedia->fetchAll();

$isImage = $typeMedia === 'IMAGE';
$imgExts = ['jpg','jpeg','png','webp','gif'];
$videoExts = ['mp4','webm','ogg','mov'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie <?= $isImage ? 'Photos' : 'Vidéos' ?> | NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .reservation-details {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        .reservation-details h3 {
            font-family: var(--font-headings);
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--logo-black);
            margin-bottom: 15px;
        }
        .reservation-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .info-item {
            background: #f8f8f6;
            padding: 12px 15px;
            border-radius: 8px;
        }
        .info-label {
            font-size: 0.75rem;
            color: #888;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .info-value {
            font-family: var(--font-headings);
            font-size: 0.95rem;
            color: var(--logo-black);
            font-weight: 600;
        }
        .media-gallery {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid var(--border-color);
        }
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .media-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow-subtle);
            border: 1px solid var(--border-color);
        }
        .media-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            transition: var(--transition-smooth);
        }
        .media-item:hover img {
            transform: scale(1.05);
        }
        .media-overlay {
            position: absolute;
            inset: 0;
            background: rgba(22, 32, 26, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition-smooth);
            gap: 15px;
        }
        .media-item:hover .media-overlay {
            opacity: 1;
        }
        .media-overlay a {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--white);
            color: var(--logo-black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: var(--transition-smooth);
            text-decoration: none;
        }
        .media-overlay a:hover {
            background: var(--primary-green);
            color: var(--white);
        }
        .video-item {
            background: #000;
            height: 150px;
            position: relative;
        }
        .video-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .video-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition-smooth);
        }
        .video-item:hover .video-overlay {
            opacity: 1;
        }
        .video-overlay i {
            font-size: 2.5rem;
            color: var(--white);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        /* Lightbox vidéo */
        #lbVideo video {
            max-width: 90vw;
            max-height: 85vh;
            width: auto;
            height: auto;
        }
    </style>
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
                <a href="<?= $isImage ? 'mes_photos.php' : 'mes_videos.php' ?>"><?= $isImage ? 'Mes Photos' : 'Mes Vidéos' ?></a>
                <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Galerie <?= $isImage ? 'Photos' : 'Vidéos' ?></span>
            </nav>

            <div class="dash-page-header">
                <a href="<?= $isImage ? 'mes_photos.php' : 'mes_videos.php' ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <h2>Galerie <?= $isImage ? 'Photos' : 'Vidéos' ?></h2>
                <p>Réservation du <?= date('d/m/Y', strtotime($reservation['DATE_RESERVATION'])) ?></p>
            </div>

            <!-- Reservation Details -->
            <div class="reservation-details">
                <h3>Détails de la réservation</h3>
                <div class="reservation-info">
                    <div class="info-item">
                        <div class="info-label">Type de prestation</div>
                        <div class="info-value"><?= htmlspecialchars($reservation['LIB_PRESTATION']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date</div>
                        <div class="info-value"><?= date('d/m/Y', strtotime($reservation['DATE_RESERVATION'])) ?></div>
                    </div>
                    <?php if (!empty($reservation['HEURE_RESERVATION'])): ?>
                    <div class="info-item">
                        <div class="info-label">Heure</div>
                        <div class="info-value"><?= date('H:i', strtotime($reservation['HEURE_RESERVATION'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($reservation['LIEU_RESERVATION'])): ?>
                    <div class="info-item">
                        <div class="info-label">Lieu</div>
                        <div class="info-value"><?= htmlspecialchars($reservation['LIEU_RESERVATION']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Media Gallery -->
            <div class="media-gallery">
                <h3 style="font-family: var(--font-headings); font-weight: 700; font-size: 1.2rem; margin-bottom: 20px; color: var(--logo-black);">
                    <?= count($medias) ?> <?= $isImage ? 'Photo' : 'Vidéo' ?> <?= count($medias) > 1 ? 's' : '' ?>
                </h3>
                
                <?php if (empty($medias)): ?>
                    <div class="empty-state" style="padding: 40px;">
                        <i class="fas fa-<?= $isImage ? 'images' : 'video' ?>"></i>
                        <p style="margin-top: 15px; font-size: 0.95rem;">Aucune <?= $isImage ? 'photo' : 'vidéo' ?> disponible pour cette réservation.</p>
                    </div>
                <?php else: ?>
                    <div class="media-grid">
                        <?php foreach ($medias as $m): ?>
                            <?php
                            $path = htmlspecialchars($m['PATH_MEDIA']);
                            $absPath = '../../' . $path;
                            $ext = strtolower(pathinfo($m['PATH_MEDIA'], PATHINFO_EXTENSION));
                            ?>
                            <div class="media-item" data-src="<?= $absPath ?>" data-type="<?= $isImage ? 'image' : 'video' ?>">
                                <?php if ($isImage): ?>
                                    <img src="<?= $absPath ?>" alt="Photo <?= (int) $m['ID_MEDIA'] ?>" loading="lazy">
<div class="media-overlay">
    <a href="<?= $absPath ?>" class="lb-open" title="Voir en grand"><i class="fas fa-expand-alt"></i></a>
    <a href="<?= $absPath ?>" download title="Télécharger"><i class="fas fa-download"></i></a>
</div>
                                <?php else: ?>
                                    <div class="video-item">
                                        <video controls preload="metadata">
                                            <source src="<?= $absPath ?>" type="video/<?= $ext === 'mov' ? 'mp4' : $ext ?>">
                                        </video>
                                        <div class="video-overlay">
                                            <a href="<?= $absPath ?>" download title="Télécharger"><i class="fas fa-download"></i></a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox pour les images -->
<div id="lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.95);z-index:3000;align-items:center;justify-content:center;padding:40px;">
    <button id="lbClose" style="position:absolute;top:20px;right:28px;background:none;border:none;color:#fff;font-size:2rem;cursor:pointer;">&times;</button>
<a id="lbDownload" href="#" download title="Télécharger l'image" style="position:absolute;top:20px;right:60px;background:none;border:none;color:#fff;font-size:1.5rem;cursor:pointer;display:flex;align-items:center;justify-content:center;opacity:0.8;text-decoration:none;"><i class="fas fa-download"></i></a>
    <img id="lbImg" src="" alt="" style="max-width:90%;max-height:85vh;border-radius:6px;border:4px solid rgba(255,255,255,0.15);">
</div>

<!-- Lightbox pour les vidéos -->
<div id="lightbox-video" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.95);z-index:3000;align-items:center;justify-content:center;padding:40px;">
    <button id="lbVideoClose" style="position:absolute;top:20px;right:28px;background:none;border:none;color:#fff;font-size:2rem;cursor:pointer;">&times;</button>
    <a id="lbVideoDownload" href="#" download title="Télécharger la vidéo" style="position:absolute;top:20px;right:60px;background:none;border:none;color:#fff;font-size:1.5rem;cursor:pointer;display:flex;align-items:center;justify-content:center;opacity:0.8;text-decoration:none;"><i class="fas fa-download"></i></a>
    <video id="lbVideo" controls preload="metadata" style="max-width:90vw;max-height:85vh;"></video>
</div>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// Lightbox pour les images
const lb      = document.getElementById('lightbox');
const lbImg   = document.getElementById('lbImg');
const lbDownload = document.getElementById('lbDownload');

// Lightbox pour les vidéos
const lbVideo      = document.getElementById('lightbox-video');
const lbVideoEl    = document.getElementById('lbVideo');
const lbVideoDownload = document.getElementById('lbVideoDownload');

// Ouvrir le lightbox en cliquant sur le média
document.querySelectorAll('.media-item').forEach(item => {
    item.addEventListener('click', e => {
        if (e.target.closest('a')) return;
        const src = item.dataset.src;
        const type = item.dataset.type;
        
        if (type === 'image') {
            lbImg.src = src;
            lbDownload.href = src;
            lb.style.display = 'flex';
        } else if (type === 'video') {
            lbVideoEl.src = src;
            lbVideoDownload.href = src;
            lbVideo.style.display = 'flex';
        }
    });
});

// Ouvrir le lightbox en cliquant sur le bouton "Voir en grand" (images)
document.querySelectorAll('.lb-open').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        const src = link.getAttribute('href');
        lbImg.src = src;
        lbDownload.href = src;
        lb.style.display = 'flex';
    });
});

// Fermer les lightboxes
document.getElementById('lbClose')?.addEventListener('click', () => { lb.style.display = 'none'; lbImg.src = ''; });
lb?.addEventListener('click', e => { if (e.target === lb) { lb.style.display = 'none'; lbImg.src = ''; } });

document.getElementById('lbVideoClose')?.addEventListener('click', () => { lbVideo.style.display = 'none'; lbVideoEl.src = ''; });
lbVideo?.addEventListener('click', e => { if (e.target === lbVideo) { lbVideo.style.display = 'none'; lbVideoEl.src = ''; } });

document.addEventListener('keydown', e => { 
    if (e.key === 'Escape') { 
        lb.style.display = 'none'; 
        lbImg.src = '';
        lbVideo.style.display = 'none';
        lbVideoEl.src = '';
    } 
});
</script>
</body>
</html>