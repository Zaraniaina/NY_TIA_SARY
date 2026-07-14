<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();
require_once __DIR__ . '/composante/tolbarDto.php';
$titre = "Mon Espace Client";

// KPIs
$stmtNb  = $pdo->prepare('SELECT COUNT(*) FROM RESERVATION WHERE ID_CLIENT = ?');
$stmtNb->execute([$clientId]);
$nbResa  = (int) $stmtNb->fetchColumn();

$stmtDevis = $pdo->prepare('SELECT COUNT(*) FROM DEVIS WHERE NOM = ? OR PRENOMS = ?');
$stmtDevis->execute([$clientNom, $clientPrenom]);
$nbDevis = (int) $stmtDevis->fetchColumn();

$stmtPhotos = $pdo->prepare('SELECT COUNT(*) FROM MEDIA m JOIN RESERVATION r ON m.ID_RESERVATION = r.ID_RESERVATION WHERE r.ID_CLIENT = ?');
$stmtPhotos->execute([$clientId]);
$nbPhotos = (int) $stmtPhotos->fetchColumn();

// 5 dernières réservations
$stmtLast = $pdo->prepare(
    'SELECT r.*, p.LIB_PRESTATION
     FROM RESERVATION r
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     WHERE r.ID_CLIENT = ?
     ORDER BY r.DATE_RESERVATION DESC, r.HEURE_RESERVATION DESC
     LIMIT 5'
);
$stmtLast->execute([$clientId]);
$lastResas = $stmtLast->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace | NY TIA SARY</title>
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
        <!-- TOPBAR -->
       <?php include __DIR__.'/composante/tolbar.php';?>

        <!-- CONTENU -->
        <div class="dashboard-content">
            <div class="dash-page-header">
                <h2>Bonjour, <?= htmlspecialchars($clientPrenom) ?> </h2>
                <p>Bienvenue dans votre espace personnel NY TIA SARY.</p>
            </div>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-info">
                        <div class="stat-number"><?= $nbResa ?></div>
                        <div class="stat-label">Réservations</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="fas fa-file-invoice"></i></div>
                    <div class="stat-info">
                        <div class="stat-number"><?= $nbDevis ?></div>
                        <div class="stat-label">Devis soumis</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-images"></i></div>
                    <div class="stat-info">
                        <div class="stat-number"><?= $nbPhotos ?></div>
                        <div class="stat-label">Photos livrées</div>
                    </div>
                </div>
            </div>

            <!-- ACTIONS RAPIDES -->
            <div class="dash-card" style="margin-bottom:28px;">
                <div class="dash-card-header">
                    <h3><i class="fas fa-bolt" style="color:var(--primary-green);margin-right:8px;"></i> Actions rapides</h3>
                </div>
                <div class="dash-card-body padded" style="display:flex;gap:14px;flex-wrap:wrap;">
                    <a href="reservations.php" class="btn-dash btn-dash-primary">
                        <i class="fas fa-plus"></i> Nouvelle réservation
                    </a>
                    <a href="devis.php" class="btn-dash btn-dash-outline">
                        <i class="fas fa-file-invoice"></i> Demander un devis
                    </a>
                    <a href="mes_photos.php" class="btn-dash btn-dash-outline">
                        <i class="fas fa-images"></i> Voir mes photos
                    </a>
                </div>
            </div>

            <!-- DERNIÈRES RÉSERVATIONS -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-history" style="color:var(--primary-green);margin-right:8px;"></i> Mes dernières réservations</h3>
                    <a href="reservations.php" class="btn-dash btn-dash-outline btn-dash-sm">Voir tout</a>
                </div>
                <div class="dash-card-body">
                    <?php if (empty($lastResas)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>Aucune réservation pour le moment.<br>
                               <a href="reservations.php" style="color:var(--primary-green);">Effectuer ma première réservation</a>
                            </p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Prestation</th>
                                    <th>Date</th>
                                    <th>Lieu</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($lastResas as $r): ?>
                                <?php
                                $badgeClass = match(strtoupper($r['STATUS_RESERVATION'])) {
                                    'CONFIRMÉ', 'CONFIRME' => 'badge-confirm',
                                    'ANNULÉ', 'ANNULE'     => 'badge-cancel',
                                    'TERMINÉ', 'TERMINE'   => 'badge-done',
                                    default                 => 'badge-waiting',
                                };
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r['LIB_PRESTATION']) ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($r['DATE_RESERVATION'])) ?></td>
                                    <td><?= htmlspecialchars($r['LIEU_RESERVATION']) ?></td>
                                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($r['STATUS_RESERVATION']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- /.dashboard-content -->
    </div><!-- /.dashboard-main -->
</div><!-- /.dashboard-wrapper -->

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', closeSidebar);
</script>
</body>
</html>
