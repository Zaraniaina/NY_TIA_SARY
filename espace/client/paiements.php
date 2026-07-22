<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();

require_once __DIR__ . '/composante/tolbarDto.php';
$titre = "Mes Paiements";

if (isset($_GET['mark_notif']) && (int)$_GET['mark_notif'] > 0) {
    $idNotif = (int)$_GET['mark_notif'];
    $pdo->prepare('UPDATE notification SET LU_NOTIF = 1 WHERE ID_NOTIF = ? AND ID_CLIENT = ?')->execute([$idNotif, $clientId]);
    echo "<script>if (window.history.replaceState) { const url = new URL(window.location); url.searchParams.delete('mark_notif'); window.history.replaceState(null, null, url); }</script>";
}

// ── Filtre par facture (depuis le lien dans factures.php) ─────
$filterFac = (int)($_GET['id_facture'] ?? 0);

// ── Paiements du client connecté ──────────────────────────────
$sql = 'SELECT pay.ID_PAIEMENT, pay.DATE_PAIEMENT, pay.MONTANT_PAIEMENT,
               f.ID_FACTURE, f.NUM_FACTURE, f.MONTANT_FACTURE, f.STATUS_FACTURE,
               p.LIB_PRESTATION,
               COALESCE(all_pay.total_paye, 0) AS TOTAL_PAYE,
               f.MONTANT_FACTURE - COALESCE(all_pay.total_paye, 0) AS RESTE
        FROM PAIEMENT pay
        JOIN FACTURE f ON pay.ID_FACTURE = f.ID_FACTURE
        JOIN CONTRAT ct ON f.ID_CONTRAT = ct.ID_CONTRAT
        JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
        JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
        LEFT JOIN (
            SELECT ID_FACTURE, SUM(MONTANT_PAIEMENT) AS total_paye
            FROM PAIEMENT
            GROUP BY ID_FACTURE
        ) AS all_pay ON all_pay.ID_FACTURE = f.ID_FACTURE
        WHERE r.ID_CLIENT = ?';

$params = [$clientId];
if ($filterFac > 0) {
    $sql .= ' AND f.ID_FACTURE = ?';
    $params[] = $filterFac;
}
$sql .= ' ORDER BY pay.DATE_PAIEMENT DESC, pay.ID_PAIEMENT DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$paiements = $stmt->fetchAll();

// ── Récapitulatif global du client ────────────────────────────
$stmtRecap = $pdo->prepare(
    'SELECT
        COALESCE(SUM(f.MONTANT_FACTURE), 0) AS total_facture,
        COALESCE(SUM(pay.MONTANT_PAIEMENT), 0) AS total_paye
     FROM FACTURE f
     JOIN CONTRAT ct ON f.ID_CONTRAT = ct.ID_CONTRAT
     JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
     LEFT JOIN PAIEMENT pay ON pay.ID_FACTURE = f.ID_FACTURE
     WHERE r.ID_CLIENT = ?'
);
$stmtRecap->execute([$clientId]);
$recap = $stmtRecap->fetch();
$totalFacture = (int)($recap['total_facture'] ?? 0);
$totalPaye    = (int)($recap['total_paye'] ?? 0);
$totalReste   = $totalFacture - $totalPaye;

// ── Infos de la facture filtrée (si applicable) ───────────────
$facFiltered = null;
if ($filterFac > 0 && !empty($paiements)) {
    $facFiltered = [
        'NUM_FACTURE'    => $paiements[0]['NUM_FACTURE'],
        'MONTANT_FACTURE'=> $paiements[0]['MONTANT_FACTURE'],
        'TOTAL_PAYE'     => $paiements[0]['TOTAL_PAYE'],
        'RESTE'          => $paiements[0]['RESTE'],
        'STATUS_FACTURE' => $paiements[0]['STATUS_FACTURE'],
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Paiements | NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .recap-row {
            display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
            gap:14px; margin-bottom:22px;
        }
        .recap-card {
            background:var(--bg-card); border:1px solid var(--border-color);
            border-radius:12px; padding:16px 18px;
            display:flex; align-items:center; gap:12px;
        }
        .recap-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
        .recap-icon.blue   { background:rgba(41,128,185,.15); color:#2980b9; }
        .recap-icon.green  { background:rgba(55,125,73,.15);  color:var(--primary-green); }
        .recap-icon.orange { background:rgba(243,156,18,.15); color:#f39c12; }
        .recap-label { font-size:.76rem; color:var(--text-muted); margin-bottom:2px; }
        .recap-value { font-size:.98rem; font-weight:700; color:var(--text-primary); }

        .badge-partial {
            display:inline-flex; align-items:center; gap:5px;
            padding:3px 10px; border-radius:50px; font-size:.72rem; font-weight:700;
            background:rgba(243,156,18,.15); color:#f39c12;
            border:1px solid rgba(243,156,18,.3);
        }
        .progress-bar-wrap {
            background:rgba(255,255,255,.08); border-radius:4px; height:6px; overflow:hidden; margin-top:3px;
        }
        .progress-bar-fill {
            height:100%; border-radius:4px;
            background:linear-gradient(90deg, #377d49, #56b870);
        }
        .progress-label { font-size:.7rem; color:var(--text-muted); margin-top:2px; }

        /* Bandeau facture filtrée */
        .fac-filter-banner {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--primary-green);
            border-radius: 10px;
            padding: 16px 20px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        }
        .fac-filter-info { flex: 1; min-width: 200px; }
        .fac-filter-title { font-weight: 700; font-size: .95rem; color: var(--primary-green); margin-bottom: 6px; }
        .fac-filter-stats { display: flex; gap: 14px; flex-wrap: wrap; }
        .fac-filter-stat { font-size: .83rem; }
        .fac-filter-stat .label { color: var(--text-muted); }
        .fac-filter-stat .val { font-weight: 700; margin-left: 4px; }

        .amount-col { white-space: nowrap; }
        .timeline-amount { font-weight:700; font-size:.95rem; color:var(--primary-green); }
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
                <a href="factures.php">Mes Factures</a>
                <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Mes Paiements</span>
            </nav>

            <!-- RÉCAPITULATIF GLOBAL -->
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
                        <div class="recap-label">Total payé</div>
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

            <!-- BANDEAU FACTURE FILTRÉE -->
            <?php if ($facFiltered): 
                $sfF   = strtoupper(trim($facFiltered['STATUS_FACTURE']));
                $pctF  = $facFiltered['MONTANT_FACTURE'] > 0 ? min(100, round($facFiltered['TOTAL_PAYE'] / $facFiltered['MONTANT_FACTURE'] * 100)) : 0;
            ?>
            <div class="fac-filter-banner">
                <div class="fac-filter-info">
                    <div class="fac-filter-title">
                        <i class="fas fa-filter"></i>
                        Filtre actif — Facture <?= htmlspecialchars($facFiltered['NUM_FACTURE']) ?>
                    </div>
                    <div class="fac-filter-stats">
                        <div class="fac-filter-stat">
                            <span class="label">Total :</span>
                            <span class="val"><?= number_format((int)$facFiltered['MONTANT_FACTURE'], 0, ',', ' ') ?> Ar</span>
                        </div>
                        <div class="fac-filter-stat">
                            <span class="label">Payé :</span>
                            <span class="val" style="color:var(--primary-green);"><?= number_format((int)$facFiltered['TOTAL_PAYE'], 0, ',', ' ') ?> Ar</span>
                        </div>
                        <div class="fac-filter-stat">
                            <span class="label">Reste :</span>
                            <span class="val" style="color:#f39c12;"><?= number_format((int)$facFiltered['RESTE'], 0, ',', ' ') ?> Ar</span>
                        </div>
                    </div>
                    <div class="progress-bar-wrap" style="margin-top:8px;">
                        <div class="progress-bar-fill" style="width:<?= $pctF ?>%;"></div>
                    </div>
                    <div class="progress-label"><?= $pctF ?>% payé</div>
                </div>
                <a href="paiements.php" class="btn-dash btn-dash-outline btn-dash-sm" style="flex-shrink:0;">
                    <i class="fas fa-times"></i> Voir tous
                </a>
            </div>
            <?php endif; ?>

            <!-- LISTE DES PAIEMENTS -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3>
                        <i class="fas fa-history" style="color:var(--primary-green);margin-right:8px;"></i>
                        <?= $facFiltered ? 'Paiements de la facture '.htmlspecialchars($facFiltered['NUM_FACTURE']) : 'Historique de mes paiements' ?>
                    </h3>
                    <span class="badge badge-confirm"><?= count($paiements) ?> paiement(s)</span>
                </div>
                <div class="dash-card-body">
                    <?php if (empty($paiements)): ?>
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <p>
                                <?php if ($filterFac > 0): ?>
                                    Aucun paiement enregistré pour cette facture.
                                <?php else: ?>
                                    Aucun paiement enregistré pour le moment.<br>
                                    <span style="font-size:0.85rem;color:#888;">L'administrateur enregistre vos paiements dans votre espace.</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Date paiement</th>
                                    <th>N° Facture</th>
                                    <th>Prestation</th>
                                    <th class="amount-col">Montant payé</th>
                                    <th class="amount-col">Total facture</th>
                                    <th class="amount-col">Reste dû</th>
                                    <th>Statut facture</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($paiements as $pay):
                                $sf    = strtoupper(trim($pay['STATUS_FACTURE']));
                                $reste = (int)$pay['RESTE'];
                                $pct   = $pay['MONTANT_FACTURE'] > 0
                                    ? min(100, round($pay['TOTAL_PAYE'] / $pay['MONTANT_FACTURE'] * 100))
                                    : 0;
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= date('d/m/Y', strtotime($pay['DATE_PAIEMENT'])) ?></strong>
                                    </td>
                                    <td>
                                        <a href="paiements.php?id_facture=<?= (int)$pay['ID_FACTURE'] ?>"
                                           style="color:var(--primary-green);font-weight:700;text-decoration:none;">
                                            <i class="fas fa-file-invoice"></i> <?= htmlspecialchars($pay['NUM_FACTURE']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($pay['LIB_PRESTATION']) ?></td>
                                    <td class="amount-col">
                                        <span class="timeline-amount">
                                            <i class="fas fa-coins" style="font-size:.8rem;"></i>
                                            <?= number_format((int)$pay['MONTANT_PAIEMENT'], 0, ',', ' ') ?> Ar
                                        </span>
                                    </td>
                                    <td class="amount-col"><?= number_format((int)$pay['MONTANT_FACTURE'], 0, ',', ' ') ?> Ar</td>
                                    <td class="amount-col" style="font-weight:600;color:<?= $reste > 0 ? '#f39c12' : 'var(--primary-green)' ?>;">
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
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Note informative -->
            <div style="background:rgba(41,128,185,.08);border:1px solid rgba(41,128,185,.2);border-radius:10px;padding:14px 18px;margin-top:16px;display:flex;align-items:flex-start;gap:10px;">
                <i class="fas fa-info-circle" style="color:#2980b9;margin-top:2px;"></i>
                <div style="font-size:.83rem;color:var(--text-muted);">
                    <strong style="color:var(--text-primary);">Comment fonctionnent les paiements ?</strong><br>
                    Les paiements sont enregistrés manuellement par l'administrateur (espèces, etc.).
                    Si vous avez effectué un versement qui n'apparaît pas encore ici, veuillez contacter le studio.
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
