<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();

require_once __DIR__ . '/composante/tolbarDto.php';
$titre = "Mes Photos";
$typeMedia = "IMAGE";

if (isset($_GET['mark_notif']) && (int)$_GET['mark_notif'] > 0) {
    $idNotif = (int)$_GET['mark_notif'];
    $pdo->prepare('UPDATE notification SET LU_NOTIF = 1 WHERE ID_NOTIF = ? AND ID_CLIENT = ?')->execute([$idNotif, $clientId]);
    echo "<script>if (window.history.replaceState) { const url = new URL(window.location); url.searchParams.delete('mark_notif'); window.history.replaceState(null, null, url); }</script>";
}

// Récupérer les réservations avec leurs photos, groupées par réservation
$stmt = $pdo->prepare(
    'SELECT r.ID_RESERVATION, r.DATE_RESERVATION, r.LIEU_RESERVATION, r.HEURE_RESERVATION, r.STATUS_RESERVATION,
            p.LIB_PRESTATION, p.ID_PRESTATION,
            COUNT(m.ID_MEDIA) as NB_PHOTOS
     FROM RESERVATION r
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     JOIN MEDIA m ON r.ID_RESERVATION = m.ID_RESERVATION
     WHERE r.ID_CLIENT = ? AND m.TYPE_MEDIA = ?
     GROUP BY r.ID_RESERVATION, r.DATE_RESERVATION, r.LIEU_RESERVATION, r.HEURE_RESERVATION, r.STATUS_RESERVATION,
              p.LIB_PRESTATION, p.ID_PRESTATION
     ORDER BY r.DATE_RESERVATION DESC'
);
$stmt->execute([$clientId, $typeMedia]);
$reservations = $stmt->fetchAll();

// Récupérer le nombre total de photos
$stmtTotal = $pdo->prepare(
    'SELECT COUNT(*) FROM MEDIA WHERE TYPE_MEDIA = ? AND ID_RESERVATION IN (
        SELECT ID_RESERVATION FROM RESERVATION WHERE ID_CLIENT = ?
    )'
);
$stmtTotal->execute([$typeMedia, $clientId]);
$totalPhotos = $stmtTotal->fetchColumn();

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
    <style>
        .reservation-group {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
            transition: var(--transition-smooth);
        }
        .reservation-group:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        .reservation-title {
            font-family: var(--font-headings);
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--logo-black);
            margin: 0;
        }
        .reservation-meta {
            display: flex;
            gap: 20px;
            font-size: 0.85rem;
            color: #777;
        }
        .view-photos-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--primary-green);
            color: var(--white);
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition-smooth);
        }
        .view-photos-btn:hover {
            background: var(--logo-black);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(55, 125, 73, 0.25);
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
                <span>Mes Photos</span>
            </nav>

            <div class="dash-page-header">
                <h2>Ma Galerie de Photos</h2>
                <p><?= $totalPhotos ?> photo(s) livrée(s) par le studio, réparties sur <?= count($reservations) ?> réservation(s)</p>
            </div>

            <?php if (empty($reservations)): ?>
                <div class="dash-card">
                    <div class="empty-state">
                        <i class="fas fa-images"></i>
                        <p>Aucune photo livrée pour le moment.<br>
                           Vos photos apparaîtront ici après la réalisation de vos séances.</p>
                    </div>
                </div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:25px;">
                <?php foreach ($reservations as $r): ?>
                    <div class="reservation-group">
                        <div class="reservation-header">
                            <div>
                                <h3 class="reservation-title"><?= htmlspecialchars($r['LIB_PRESTATION']) ?></h3>
                                <div class="reservation-meta">
                                    <span><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($r['DATE_RESERVATION'])) ?></span>
                                    <?php if (!empty($r['HEURE_RESERVATION'])): ?>
                                        <span><i class="fas fa-clock"></i> <?= date('H:i', strtotime($r['HEURE_RESERVATION'])) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($r['LIEU_RESERVATION'])): ?>
                                        <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($r['LIEU_RESERVATION']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="galerie_reservation.php?id_reservation=<?= $r['ID_RESERVATION'] ?>&type=IMAGE" class="view-photos-btn">
                                <i class="fas fa-images"></i> Voir les photos (<?= $r['NB_PHOTOS'] ?>)
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
</script>
</body>
</html>