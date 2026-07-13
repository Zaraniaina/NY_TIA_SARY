<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();

require_once __DIR__.'/composante/tolbarDto.php';
//on changer le titre
$titre="Tableau de bord";

// ── KPIs ─────────────────────────────────────────────────────
$nbClients  = (int) $pdo->query("SELECT COUNT(*) FROM AUTHENTIFICATION WHERE ROLE_AUTH='CLIENT'")->fetchColumn();
$nbResas    = (int) $pdo->query('SELECT COUNT(*) FROM RESERVATION')->fetchColumn();
$nbDevis    = (int) $pdo->query('SELECT COUNT(*) FROM DEVIS')->fetchColumn();
$nbReasMois = (int) $pdo->query("SELECT COUNT(*) FROM RESERVATION WHERE MONTH(DATE_RESERVATION)=MONTH(CURDATE()) AND YEAR(DATE_RESERVATION)=YEAR(CURDATE())")->fetchColumn();
$nbAttente  = (int) $pdo->query("SELECT COUNT(*) FROM RESERVATION WHERE STATUS_RESERVATION='EN ATTENTE'")->fetchColumn();

// ── 5 dernières réservations ──────────────────────────────────
$lastResas = $pdo->query(
    'SELECT r.*, p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT
     FROM RESERVATION r
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
     ORDER BY r.DATE_RESERVATION DESC LIMIT 5'
)->fetchAll();

// ── Données graphique (12 derniers mois) ─────────────────────
$chartData = $pdo->query(
    "SELECT DATE_FORMAT(DATE_RESERVATION,'%b %Y') AS mois,
            YEAR(DATE_RESERVATION) AS an,
            MONTH(DATE_RESERVATION) AS mo,
            COUNT(*) AS total
     FROM RESERVATION
     WHERE DATE_RESERVATION >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY an, mo, mois
     ORDER BY an, mo"
)->fetchAll();
$chartLabels = json_encode(array_column($chartData, 'mois'));
$chartValues = json_encode(array_column($chartData, 'total'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | NY TIA SARY</title>
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
            <div class="dash-page-header">
                <h2>Vue d'ensemble</h2>
                <p>Aujourd'hui — <?= date('l d F Y') ?></p>
            </div>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <div class="stat-number"><?= $nbClients ?></div>
                        <div class="stat-label">Clients inscrits</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-info">
                        <div class="stat-number"><?= $nbResas ?></div>
                        <div class="stat-label">Total réservations</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <div class="stat-number"><?= $nbAttente ?></div>
                        <div class="stat-label">En attente</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info">
                        <div class="stat-number"><?= $nbDevis ?></div>
                        <div class="stat-label">Devis reçus</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-info">
                        <div class="stat-number"><?= $nbReasMois ?></div>
                        <div class="stat-label">Réservations ce mois</div>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:28px;align-items:start;">

                <!-- GRAPHIQUE -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-chart-bar" style="color:var(--primary-green);margin-right:8px;"></i> Réservations (12 derniers mois)</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="resaChart"></canvas>
                    </div>
                </div>

                <!-- ACTIONS RAPIDES -->
                <div class="dash-card">
                    <div class="dash-card-header"><h3><i class="fas fa-bolt" style="color:var(--primary-green);margin-right:8px;"></i> Actions</h3></div>
                    <div class="dash-card-body padded" style="display:flex;flex-direction:column;gap:12px;">
                        <a href="reservations.php" class="btn-dash btn-dash-primary" style="justify-content:flex-start;">
                            <i class="fas fa-calendar-check"></i> Gérer les réservations
                        </a>
                        <a href="clients.php" class="btn-dash btn-dash-outline" style="justify-content:flex-start;">
                            <i class="fas fa-users"></i> Voir les clients
                        </a>
                        <a href="devis.php" class="btn-dash btn-dash-outline" style="justify-content:flex-start;">
                            <i class="fas fa-file-alt"></i> Traiter les devis
                        </a>
                        <a href="blog.php" class="btn-dash btn-dash-outline" style="justify-content:flex-start;">
                            <i class="fas fa-newspaper"></i> Gérer le blog
                        </a>
                        <a href="prestations.php" class="btn-dash btn-dash-outline" style="justify-content:flex-start;">
                            <i class="fas fa-concierge-bell"></i> Gérer les prestations
                        </a>
                    </div>
                </div>
            </div>

            <!-- DERNIÈRES RÉSERVATIONS -->
            <div class="dash-card" style="margin-top:28px;">
                <div class="dash-card-header">
                    <h3><i class="fas fa-history" style="color:var(--primary-green);margin-right:8px;"></i> Dernières réservations</h3>
                    <a href="reservations.php" class="btn-dash btn-dash-outline btn-dash-sm">Voir tout</a>
                </div>
                <div class="dash-card-body">
                    <?php if (empty($lastResas)): ?>
                        <div class="empty-state"><i class="fas fa-calendar-times"></i><p>Aucune réservation.</p></div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr><th>#</th><th>Client</th><th>Prestation</th><th>Date</th><th>Lieu</th><th>Statut</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($lastResas as $r): ?>
                                <?php
                                $bc = match(strtoupper($r['STATUS_RESERVATION'])) {
                                    'CONFIRMÉ','CONFIRME' => 'badge-confirm',
                                    'ANNULÉ','ANNULE'     => 'badge-cancel',
                                    'TERMINÉ','TERMINE'   => 'badge-done',
                                    default               => 'badge-waiting',
                                };
                                ?>
                                <tr>
                                    <td>#<?= (int)$r['ID_RESERVATION'] ?></td>
                                    <td><?= htmlspecialchars($r['PRENOM_CLIENT'] . ' ' . $r['NOM_CLIENT']) ?></td>
                                    <td><?= htmlspecialchars($r['LIB_PRESTATION']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['DATE_RESERVATION'])) ?></td>
                                    <td><?= htmlspecialchars($r['LIEU_RESERVATION']) ?></td>
                                    <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($r['STATUS_RESERVATION']) ?></span></td>
                                    <td><a href="reservations.php?id=<?= (int)$r['ID_RESERVATION'] ?>" class="btn-dash btn-dash-outline btn-dash-sm btn-dash-icon"><i class="fas fa-edit"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// Graphique
const ctx = document.getElementById('resaChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [{
            label: 'Réservations',
            data: <?= $chartValues ?>,
            backgroundColor: 'rgba(55,125,73,0.18)',
            borderColor: '#377d49',
            borderWidth: 2,
            borderRadius: 6,
            hoverBackgroundColor: 'rgba(55,125,73,0.35)',
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Open Sans', size: 11 } }, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { ticks: { font: { family: 'Open Sans', size: 11 } }, grid: { display: false } }
        }
    }
});
</script>
</body>
</html>