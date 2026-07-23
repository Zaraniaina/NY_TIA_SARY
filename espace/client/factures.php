<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();

require_once __DIR__ . '/composante/tolbarDto.php';
$titre = "Mes Factures";

if (isset($_GET['mark_notif']) && (int)$_GET['mark_notif'] > 0) {
    $idNotif = (int)$_GET['mark_notif'];
    $pdo->prepare('UPDATE notification SET LU_NOTIF = 1 WHERE ID_NOTIF = ? AND ID_CLIENT = ?')->execute([$idNotif, $clientId]);
    echo "<script>if (window.history.replaceState) { const url = new URL(window.location); url.searchParams.delete('mark_notif'); window.history.replaceState(null, null, url); }</script>";
}

// ── Liste des factures du client avec soldes ──────────────────
$stmtFactures = $pdo->prepare(
    'SELECT f.*, ct.DATE_CONTRAT, r.DATE_RESERVATION, r.LIEU_RESERVATION,
            p.LIB_PRESTATION,
            GROUP_CONCAT(DISTINCT cat.LIB_CATEGORIE ORDER BY cat.LIB_CATEGORIE SEPARATOR \'<br>\') AS LIBS_CATEGORIES,
            COALESCE(SUM(pay.MONTANT_PAIEMENT), 0) AS MONTANT_PAYE,
            f.MONTANT_FACTURE - COALESCE(SUM(pay.MONTANT_PAIEMENT), 0) AS RESTE_A_PAYER
     FROM FACTURE f
     JOIN CONTRAT ct ON f.ID_CONTRAT = ct.ID_CONTRAT
     JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     LEFT JOIN RESERVATION_CATEGORIE rc ON rc.ID_RESERVATION = r.ID_RESERVATION
     LEFT JOIN CATEGORIE cat ON cat.ID_CATEGORIE = rc.ID_CATEGORIE
     LEFT JOIN PAIEMENT pay ON pay.ID_FACTURE = f.ID_FACTURE
     WHERE r.ID_CLIENT = ?
     GROUP BY f.ID_FACTURE
     ORDER BY f.DATE_FACTURE DESC'
);
$stmtFactures->execute([$clientId]);
$factures = $stmtFactures->fetchAll();

// ── Calcul récapitulatif ───────────────────────────────────────
$totalFacture = array_sum(array_column($factures, 'MONTANT_FACTURE'));
$totalPaye    = array_sum(array_column($factures, 'MONTANT_PAYE'));
$totalReste   = $totalFacture - $totalPaye;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Factures | NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        /* ── Récap solde client ── */
        .recap-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px; margin-bottom: 22px;
        }
        .recap-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px; padding: 16px 18px;
            display: flex; align-items: center; gap: 12px;
        }
        .recap-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
        .recap-icon.blue   { background:rgba(41,128,185,.15); color:#2980b9; }
        .recap-icon.green  { background:rgba(55,125,73,.15);  color:var(--primary-green); }
        .recap-icon.orange { background:rgba(243,156,18,.15); color:#f39c12; }
        .recap-label { font-size:.76rem; color:var(--text-muted); margin-bottom:2px; }
        .recap-value { font-size:.98rem; font-weight:700; color:var(--text-primary); }

        /* ── Badge partiel ── */
        .badge-partial {
            display:inline-flex; align-items:center; gap:5px;
            padding:3px 10px; border-radius:50px; font-size:.72rem; font-weight:700;
            background:rgba(243,156,18,.15); color:#f39c12;
            border:1px solid rgba(243,156,18,.3);
        }

        /* ── Progress bar ── */
        .progress-bar-wrap {
            background:rgba(255,255,255,.08); border-radius:4px; height:5px; overflow:hidden; margin-top:4px;
        }
        .progress-bar-fill {
            height:100%; border-radius:4px;
            background:linear-gradient(90deg, #377d49, #56b870); transition:width .4s ease;
        }
        .progress-label { font-size:.7rem; color:var(--text-muted); margin-top:2px; }

        .amount-col { white-space:nowrap; }
        .action-view-link {
            display:inline-flex; align-items:center; gap:5px;
            padding:5px 10px; border-radius:6px; font-size:.75rem; font-weight:600;
            border:1px solid var(--border-color); background:rgba(255,255,255,.05);
            color:var(--text-primary); text-decoration:none; transition:background .2s;
        }
        .action-view-link:hover { background:rgba(255,255,255,.1); }
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
                <span>Mes Factures</span>
            </nav>

            <?php if (!empty($factures)): ?>
            <!-- RÉCAPITULATIF SOLDE -->
            <div class="recap-row">
                <div class="recap-card">
                    <div class="recap-icon blue"><i class="fas fa-file-invoice"></i></div>
                    <div>
                        <div class="recap-label">Total facturé</div>
                        <div class="recap-value"><?= number_format($totalFacture, 0, ',', ' ') ?> Ar</div>
                    </div>
                </div>
                <div class="recap-card">
                    <div class="recap-icon green"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <div class="recap-label">Déjà payé</div>
                        <div class="recap-value"><?= number_format($totalPaye, 0, ',', ' ') ?> Ar</div>
                    </div>
                </div>
                <div class="recap-card">
                    <div class="recap-icon orange"><i class="fas fa-hourglass-half"></i></div>
                    <div>
                        <div class="recap-label">Reste à payer</div>
                        <div class="recap-value"><?= number_format($totalReste, 0, ',', ' ') ?> Ar</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-file-invoice-dollar" style="color:var(--primary-green);margin-right:8px;"></i> Historique de mes factures</h3>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="badge badge-confirm"><?= count($factures) ?> facture(s)</span>
                        <a href="paiements.php" class="action-view-link">
                            <i class="fas fa-history"></i> Mes paiements
                        </a>
                    </div>
                </div>
                <div class="dash-card-body">
                    <?php if (empty($factures)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-invoice"></i>
                            <p>Vous n'avez aucune facture générée pour le moment.<br>
                               <span style="font-size:0.85rem;color:#888;">Les factures sont disponibles dès que vous acceptez un contrat de réservation.</span>
                            </p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>N° Facture</th>
                                    <th>Prestation &amp; Formules</th>
                                    <th>Date Prestation</th>
                                    <th>Date Facture</th>
                                    <th class="amount-col">Montant total</th>
                                    <th class="amount-col">Payé</th>
                                    <th class="amount-col">Reste</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($factures as $f):
                                $montantTotal = (int)$f['MONTANT_FACTURE'];
                                $montantPaye  = (int)$f['MONTANT_PAYE'];
                                $resteAPayer  = (int)$f['RESTE_A_PAYER'];
                                $pct          = $montantTotal > 0 ? min(100, round($montantPaye / $montantTotal * 100)) : 0;
                                $sf           = strtoupper(trim($f['STATUS_FACTURE']));
                            ?>
                                <tr>
                                    <td>
                                        <strong style="color:var(--primary-green);">
                                            <i class="fas fa-file-invoice"></i> <?= htmlspecialchars($f['NUM_FACTURE']) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($f['LIB_PRESTATION']) ?></strong><br>
                                        <span style="font-size:0.8rem;color:#aaa;"><?= $f['LIBS_CATEGORIES'] ?: 'Formule non spécifiée' ?></span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($f['DATE_RESERVATION'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($f['DATE_FACTURE'])) ?></td>
                                    <td class="amount-col">
                                        <strong><?= number_format($montantTotal, 0, ',', ' ') ?> Ar</strong>
                                    </td>
                                    <td class="amount-col" style="color:var(--primary-green);">
                                        <?= number_format($montantPaye, 0, ',', ' ') ?> Ar
                                        <div class="progress-bar-wrap">
                                            <div class="progress-bar-fill" style="width:<?= $pct ?>%;"></div>
                                        </div>
                                        <div class="progress-label"><?= $pct ?>%</div>
                                    </td>
                                    <td class="amount-col" style="font-weight:600; color:<?= $resteAPayer > 0 ? '#f39c12' : 'var(--primary-green)' ?>;">
                                        <?= number_format($resteAPayer, 0, ',', ' ') ?> Ar
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
                                        <div style="display:inline-flex;gap:6px;">
                                            <a href="paiements.php?id_facture=<?= (int)$f['ID_FACTURE'] ?>"
                                               class="action-view-link" title="Voir mes paiements pour cette facture">
                                                <i class="fas fa-history"></i>
                                            </a>
                                            <a href="generer_facture_pdf.php?id_facture=<?= (int)$f['ID_FACTURE'] ?>"
                                               class="btn-dash btn-dash-outline btn-dash-sm" style="display:inline-flex;align-items:center;gap:6px;">
                                                <i class="fas fa-file-pdf"></i> PDF
                                            </a>
                                        </div>
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
</body>
</html>
