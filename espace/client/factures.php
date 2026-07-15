<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();

require_once __DIR__ . '/composante/tolbarDto.php';
$titre = "Mes Factures";

// ── Liste des factures du client ──────────────────────────────
$stmtFactures = $pdo->prepare(
    'SELECT f.*, ct.DATE_CONTRAT, r.DATE_RESERVATION, r.LIEU_RESERVATION,
            p.LIB_PRESTATION,
            GROUP_CONCAT(cat.LIB_CATEGORIE SEPARATOR \'<br>\') AS LIBS_CATEGORIES
     FROM FACTURE f
     JOIN CONTRAT ct ON f.ID_CONTRAT = ct.ID_CONTRAT
     JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     LEFT JOIN RESERVATION_CATEGORIE rc ON rc.ID_RESERVATION = r.ID_RESERVATION
     LEFT JOIN CATEGORIE cat ON cat.ID_CATEGORIE = rc.ID_CATEGORIE
     WHERE r.ID_CLIENT = ?
     GROUP BY f.ID_FACTURE
     ORDER BY f.DATE_FACTURE DESC'
);
$stmtFactures->execute([$clientId]);
$factures = $stmtFactures->fetchAll();
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

            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-file-invoice-dollar" style="color:var(--primary-green);margin-right:8px;"></i> Historique de mes factures</h3>
                    <span class="badge badge-confirm"><?= count($factures) ?> facture(s)</span>
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
                                    <th>Prestation & Formules</th>
                                    <th>Date Prestation</th>
                                    <th>Date Facture</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Télécharger</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($factures as $f): ?>
                                <tr>
                                    <td>
                                        <strong style="color:var(--primary-green);">
                                            <i class="fas fa-file-invoice"></i> <?= htmlspecialchars($f['NUM_FACTURE']) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($f['LIB_PRESTATION']) ?></strong><br>
                                        <span style="font-size:0.8rem;color:#aaa;"><?= $f['LIBS_CATEGORIES'] ? $f['LIBS_CATEGORIES'] : 'Formule non spécifiée' ?></span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($f['DATE_RESERVATION'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($f['DATE_FACTURE'])) ?></td>
                                    <td>
                                        <strong style="color:var(--primary-green);"><?= number_format((int)$f['MONTANT_FACTURE'], 0, ',', ' ') ?> Ar</strong>
                                    </td>
                                    <td>
                                        <?php 
                                        $sf = strtoupper(trim($f['STATUS_FACTURE']));
                                        if ($sf === 'PAYEE' || $sf === 'PAYÉE' || $sf === 'REGLÉE') {
                                            echo '<span class="badge badge-confirm">PAYÉE</span>';
                                        } else {
                                            echo '<span class="badge badge-cancel">NON PAYÉE</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="generer_facture_pdf.php?id_facture=<?= (int)$f['ID_FACTURE'] ?>" target="_blank" class="btn-dash btn-dash-outline btn-dash-sm" style="display:inline-flex;align-items:center;gap:6px;">
                                            <i class="fas fa-file-pdf"></i> PDF
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
</body>
</html>
