<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();

require_once __DIR__.'/composante/tolbarDto.php';
$titre = "Gestion des factures";
$success = $error = '';

// ── Pré-sélection depuis l'URL (venant de contrats.php) ───────
$preselContrat = (int) ($_GET['id_contrat'] ?? 0);

// ── Création d'une facture ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $idContrat = (int) ($_POST['id_contrat'] ?? 0);

    if (!$idContrat) {
        $error = 'Veuillez sélectionner un contrat.';
    } else {
        // Vérifier qu'une facture n'existe pas déjà
        $chk = $pdo->prepare('SELECT COUNT(*) FROM FACTURE WHERE ID_CONTRAT = ?');
        $chk->execute([$idContrat]);
        if ((int)$chk->fetchColumn() > 0) {
            $error = 'Une facture existe déjà pour ce contrat.';
        } else {
            // Générer un numéro unique FAC-YYYY-XXXX
            $annee  = date('Y');
            $cntStmt = $pdo->query("SELECT COUNT(*) FROM FACTURE WHERE YEAR(DATE_FACTURE) = $annee");
            $cnt    = (int)$cntStmt->fetchColumn() + 1;
            $numFac = 'FAC-' . $annee . '-' . str_pad((string)$cnt, 4, '0', STR_PAD_LEFT);

            $pdo->prepare('INSERT INTO FACTURE (ID_CONTRAT, NUM_FACTURE, DATE_FACTURE) VALUES (?, ?, CURDATE())')
                ->execute([$idContrat, $numFac]);
            $success = "Facture $numFac créée avec succès.";
        }
    }
}

// ── Suppression d'une facture ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $idFac = (int) ($_POST['id_facture'] ?? 0);
    if ($idFac) {
        $pdo->prepare('DELETE FROM FACTURE WHERE ID_FACTURE = ?')->execute([$idFac]);
        $success = 'Facture supprimée.';
    }
}

// ── Contrats sans facture (pour le formulaire) ────────────────
$contratsSansFac = $pdo->query(
    'SELECT ct.ID_CONTRAT, ct.DATE_CONTRAT, r.DATE_RESERVATION, r.ID_RESERVATION,
            p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT
     FROM CONTRAT ct
     JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
     LEFT JOIN FACTURE f ON f.ID_CONTRAT = ct.ID_CONTRAT
     WHERE f.ID_FACTURE IS NULL
     ORDER BY ct.DATE_CONTRAT DESC'
)->fetchAll();

// ── Liste complète des factures ───────────────────────────────
$factures = $pdo->query(
    'SELECT f.*, ct.DATE_CONTRAT, r.DATE_RESERVATION, r.LIEU_RESERVATION,
            p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT
     FROM FACTURE f
     JOIN CONTRAT ct ON f.ID_CONTRAT = ct.ID_CONTRAT
     JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
     ORDER BY f.DATE_FACTURE DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factures | Admin NY TIA SARY</title>
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
                <a href="contrats.php">Contrats</a>
                <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Factures</span>
            </nav>

            <?php if ($success): ?><div class="dash-alert dash-alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="dash-alert dash-alert-error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 2fr;gap:28px;align-items:start;">

                <!-- FORMULAIRE NOUVELLE FACTURE -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-file-invoice" style="color:var(--primary-green);margin-right:8px;"></i> Nouvelle facture</h3>
                    </div>
                    <div class="dash-card-body padded">
                        <?php if (empty($contratsSansFac)): ?>
                            <div class="empty-state" style="padding:20px 0;">
                                <i class="fas fa-check-double" style="color:var(--primary-green);"></i>
                                <p style="font-size:0.9rem;">Tous les contrats ont une facture.</p>
                            </div>
                        <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="create">
                            <div class="dash-form-group">
                                <label for="id_contrat">Contrat <span class="required">*</span></label>
                                <select name="id_contrat" id="id_contrat" class="dash-select" required>
                                    <option value="">— Choisir un contrat —</option>
                                    <?php foreach ($contratsSansFac as $ct): ?>
                                        <option value="<?= (int)$ct['ID_CONTRAT'] ?>"
                                            <?= $preselContrat === (int)$ct['ID_CONTRAT'] ? 'selected' : '' ?>>
                                            Contrat #<?= (int)$ct['ID_CONTRAT'] ?> — <?= htmlspecialchars($ct['PRENOM_CLIENT'].' '.$ct['NOM_CLIENT']) ?> — <?= htmlspecialchars($ct['LIB_PRESTATION']) ?> (<?= date('d/m/Y', strtotime($ct['DATE_CONTRAT'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="background:rgba(255,255,255,0.04);border-radius:10px;padding:14px;margin-bottom:16px;font-size:0.85rem;color:#aaa;">
                                <i class="fas fa-info-circle" style="color:var(--primary-green);margin-right:6px;"></i>
                                Le numéro de facture sera généré automatiquement au format <strong style="color:#fff;">FAC-<?= date('Y') ?>-XXXX</strong>.
                            </div>
                            <button type="submit" class="btn-dash btn-dash-primary" style="width:100%;justify-content:center;">
                                <i class="fas fa-file-invoice"></i> Générer la facture
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- LISTE DES FACTURES -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-list" style="color:var(--primary-green);margin-right:8px;"></i> Toutes les factures</h3>
                        <span class="badge badge-confirm"><?= count($factures) ?></span>
                    </div>
                    <div class="dash-card-body">
                        <?php if (empty($factures)): ?>
                            <div class="empty-state"><i class="fas fa-file-invoice"></i><p>Aucune facture émise.</p></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>N° Facture</th>
                                        <th>Client</th>
                                        <th>Prestation</th>
                                        <th>Date prestation</th>
                                        <th>Date facture</th>
                                        <th>Actions</th>
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
                                        <td><strong><?= htmlspecialchars($f['PRENOM_CLIENT'].' '.$f['NOM_CLIENT']) ?></strong></td>
                                        <td><?= htmlspecialchars($f['LIB_PRESTATION']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($f['DATE_RESERVATION'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($f['DATE_FACTURE'])) ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette facture ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_facture" value="<?= (int)$f['ID_FACTURE'] ?>">
                                                <button class="btn-dash btn-dash-danger btn-dash-sm"><i class="fas fa-trash"></i></button>
                                            </form>
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
