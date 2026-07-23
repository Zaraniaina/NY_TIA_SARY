<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
require_once __DIR__ . '/../../util/prg_helper.php';
requireAdmin();

require_once __DIR__.'/composante/tolbarDto.php';
$titre = "Historique des paiements";

// ── Filtres ────────────────────────────────────────────────────
$search     = trim($_GET['q'] ?? '');
$filterDate = trim($_GET['date'] ?? '');
$filterMois = trim($_GET['mois'] ?? '');

// ── Requête principale ─────────────────────────────────────────
$sql = 'SELECT pay.ID_PAIEMENT, pay.DATE_PAIEMENT, pay.MONTANT_PAIEMENT,
               f.ID_FACTURE, f.NUM_FACTURE, f.MONTANT_FACTURE, f.STATUS_FACTURE,
               c.NOM_CLIENT, c.PRENOM_CLIENT,
               p.LIB_PRESTATION,
               COALESCE(all_pay.total_paye, 0) AS TOTAL_PAYE,
               f.MONTANT_FACTURE - COALESCE(all_pay.total_paye, 0) AS RESTE
        FROM PAIEMENT pay
        JOIN FACTURE f ON pay.ID_FACTURE = f.ID_FACTURE
        JOIN CONTRAT ct ON f.ID_CONTRAT = ct.ID_CONTRAT
        JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
        JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
        JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
        LEFT JOIN (
            SELECT ID_FACTURE, SUM(MONTANT_PAIEMENT) AS total_paye
            FROM PAIEMENT
            GROUP BY ID_FACTURE
        ) AS all_pay ON all_pay.ID_FACTURE = f.ID_FACTURE';

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(f.NUM_FACTURE LIKE ? OR c.NOM_CLIENT LIKE ? OR c.PRENOM_CLIENT LIKE ?)';
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like]);
}
if ($filterMois !== '') {
    $conditions[] = 'DATE_FORMAT(pay.DATE_PAIEMENT, "%Y-%m") = ?';
    $params[] = $filterMois;
}

if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY pay.DATE_PAIEMENT DESC, pay.ID_PAIEMENT DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$paiements = $stmt->fetchAll();

// ── Stats globales ─────────────────────────────────────────────
$stmtStats = $pdo->query(
    'SELECT
        COUNT(DISTINCT f.ID_FACTURE) AS nb_factures,
        COUNT(pay.ID_PAIEMENT) AS nb_paiements,
        COALESCE(SUM(f.MONTANT_FACTURE), 0) AS total_facture,
        COALESCE(SUM(pay.MONTANT_PAIEMENT), 0) AS total_encaisse
     FROM FACTURE f
     LEFT JOIN PAIEMENT pay ON pay.ID_FACTURE = f.ID_FACTURE'
);
$stats = $stmtStats->fetch();

$totalEncaisse = (int)($stats['total_encaisse'] ?? 0);
$totalFacture  = (int)($stats['total_facture'] ?? 0);
$totalRestant  = $totalFacture - $totalEncaisse;

// ── Mois disponibles pour le filtre ───────────────────────────
$stmtMois = $pdo->query(
    "SELECT DISTINCT DATE_FORMAT(DATE_PAIEMENT, '%Y-%m') AS mois_val,
            DATE_FORMAT(DATE_PAIEMENT, '%M %Y') AS mois_label
     FROM PAIEMENT
     ORDER BY mois_val DESC"
);
$moisDisponibles = $stmtMois->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique Paiements | Admin NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px; padding: 18px 20px;
            display: flex; align-items: center; gap: 14px;
        }
        .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
        .stat-icon.green  { background:rgba(55,125,73,.15); color:var(--primary-green); }
        .stat-icon.blue   { background:rgba(41,128,185,.15); color:#2980b9; }
        .stat-icon.orange { background:rgba(243,156,18,.15); color:#f39c12; }
        .stat-icon.purple { background:rgba(142,68,173,.15); color:#8e44ad; }
        .stat-label { font-size:.78rem; color:var(--text-muted); margin-bottom:2px; }
        .stat-value { font-size:1.05rem; font-weight:700; color:var(--text-primary); }

        .badge-partial {
            display:inline-flex; align-items:center; gap:5px;
            padding:3px 10px; border-radius:50px; font-size:.72rem; font-weight:700;
            background:rgba(243,156,18,.15); color:#f39c12;
            border:1px solid rgba(243,156,18,.3);
        }

        .filter-row {
            display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; align-items: center;
        }
        .filter-row .dash-select, .filter-row input[type="text"] {
            height: 40px;
        }

        .timeline-amount {
            font-weight: 700; font-size: .95rem; color: var(--primary-green);
            white-space: nowrap;
        }
        .reste-col {
            font-size: .85rem; font-weight: 600;
            white-space: nowrap;
        }

        /* Ligne de montant cumulatif */
        .cumul-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: .72rem; color: var(--text-muted);
            background: rgba(255,255,255,.05); border-radius: 4px;
            padding: 2px 6px; margin-top: 3px;
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
                <a href="factures.php">Factures</a>
                <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Historique des paiements</span>
            </nav>

            <!-- STATS GLOBALES -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-receipt"></i></div>
                    <div>
                        <div class="stat-label">Paiements enregistrés</div>
                        <div class="stat-value"><?= (int)($stats['nb_paiements'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-file-invoice"></i></div>
                    <div>
                        <div class="stat-label">Total facturé</div>
                        <div class="stat-value"><?= number_format($totalFacture, 0, ',', ' ') ?> Ar</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <div class="stat-label">Total encaissé</div>
                        <div class="stat-value"><?= number_format($totalEncaisse, 0, ',', ' ') ?> Ar</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
                    <div>
                        <div class="stat-label">Total restant dû</div>
                        <div class="stat-value"><?= number_format($totalRestant, 0, ',', ' ') ?> Ar</div>
                    </div>
                </div>
            </div>

            <!-- LISTE DES PAIEMENTS -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-history" style="color:var(--primary-green);margin-right:8px;"></i> Tous les paiements clients</h3>
                    <span class="badge badge-confirm"><?= count($paiements) ?> paiement(s)</span>
                </div>
                <div class="dash-card-body">
                    <!-- FILTRES -->
                    <form method="GET" action="" class="filter-row">
                        <div class="dash-search-bar" style="margin:0;flex:1;min-width:200px;">
                            <i class="fas fa-search"></i>
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher client ou n° facture...">
                        </div>
                        <?php if (!empty($moisDisponibles)): ?>
                        <select name="mois" class="dash-select" style="width:auto;min-width:160px;">
                            <option value="">Tous les mois</option>
                            <?php foreach ($moisDisponibles as $m): ?>
                            <option value="<?= htmlspecialchars($m['mois_val']) ?>" <?= $filterMois === $m['mois_val'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['mois_label']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                        <button type="submit" class="btn-dash btn-dash-primary" style="padding:10px 14px;">
                            <i class="fas fa-filter"></i> Filtrer
                        </button>
                        <?php if ($search !== '' || $filterMois !== ''): ?>
                        <a href="paiements.php" class="btn-dash btn-dash-outline" style="padding:10px 14px;text-decoration:none;">
                            <i class="fas fa-times"></i> Ràz
                        </a>
                        <?php endif; ?>
                    </form>

                    <?php if (empty($paiements)): ?>
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <p>Aucun paiement enregistré pour le moment.</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Date paiement</th>
                                    <th>N° Facture</th>
                                    <th>Client</th>
                                    <th>Prestation</th>
                                    <th>Montant payé</th>
                                    <th>Total facture</th>
                                    <th>Reste dû</th>
                                    <th>Statut facture</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($paiements as $pay):
                                $sf    = strtoupper(trim($pay['STATUS_FACTURE']));
                                $reste = (int)$pay['RESTE'];
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= date('d/m/Y', strtotime($pay['DATE_PAIEMENT'])) ?></strong>
                                    </td>
                                    <td>
                                        <a href="factures.php?view_payments=<?= (int)$pay['ID_FACTURE'] ?>"
                                           style="color:var(--primary-green);font-weight:700;text-decoration:none;">
                                            <i class="fas fa-file-invoice"></i> <?= htmlspecialchars($pay['NUM_FACTURE']) ?>
                                        </a>
                                    </td>
                                    <td><strong><?= htmlspecialchars($pay['PRENOM_CLIENT'].' '.$pay['NOM_CLIENT']) ?></strong></td>
                                    <td><?= htmlspecialchars($pay['LIB_PRESTATION']) ?></td>
                                    <td>
                                        <span class="timeline-amount">
                                            <i class="fas fa-coins" style="font-size:.8rem;"></i>
                                            <?= number_format((int)$pay['MONTANT_PAIEMENT'], 0, ',', ' ') ?> Ar
                                        </span>
                                    </td>
                                    <td><?= number_format((int)$pay['MONTANT_FACTURE'], 0, ',', ' ') ?> Ar</td>
                                    <td class="reste-col" style="color:<?= $reste > 0 ? '#f39c12' : 'var(--primary-green)' ?>;">
                                        <?= number_format($reste, 0, ',', ' ') ?> Ar
                                    </td>
                                    <td>
                                        <?php if ($sf === 'PAYEE' || $sf === 'PAYÉE'): ?>
                                            <span class="badge badge-confirm"><i class="fas fa-check-circle"></i> PAYÉE</span>
                                        <?php elseif ($sf === 'PARTIELLEMENT PAYEE' || $sf === 'PARTIELLEMENT PAYÉE'): ?>
                                            <span class="badge-partial"><i class="fas fa-adjust"></i> PARTIELLE</span>
                                        <?php else: ?>
                                            <span class="badge badge-cancel"><i class="fas fa-times-circle"></i> NON PAYÉE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="factures.php?view_payments=<?= (int)$pay['ID_FACTURE'] ?>"
                                           class="btn-dash btn-dash-outline btn-dash-sm" title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
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

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });
</script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</body>
</html>
