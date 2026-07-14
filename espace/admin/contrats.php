<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();

require_once __DIR__.'/composante/tolbarDto.php';
$titre = "Gestion des contrats";
$success = $error = '';

// ── Création d'un contrat ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $idResa      = (int) ($_POST['id_reservation'] ?? 0);
    $dateContrat = trim($_POST['date_contrat'] ?? '');

    if (!$idResa || !$dateContrat) {
        $error = 'Tous les champs sont requis.';
    } else {
        // Vérifier qu'un contrat n'existe pas déjà
        $chk = $pdo->prepare('SELECT COUNT(*) FROM CONTRAT WHERE ID_RESERVATION = ?');
        $chk->execute([$idResa]);
        if ((int)$chk->fetchColumn() > 0) {
            $error = 'Un contrat existe déjà pour cette réservation.';
        } else {
            $pdo->prepare('INSERT INTO CONTRAT (ID_RESERVATION, DATE_CONTRAT) VALUES (?, ?)')
                ->execute([$idResa, $dateContrat]);
            // Mettre la réservation en CONFIRMEE si pas déjà
            $pdo->prepare("UPDATE RESERVATION SET STATUS_RESERVATION='CONFIRMEE' WHERE ID_RESERVATION=? AND STATUS_RESERVATION='EN ATTENTE'")
                ->execute([$idResa]);
            $success = 'Contrat créé avec succès.';
        }
    }
}

// ── Suppression d'un contrat ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $idContrat = (int) ($_POST['id_contrat'] ?? 0);
    if ($idContrat) {
        // Vérifier qu'aucune facture n'est liée
        $chk = $pdo->prepare('SELECT COUNT(*) FROM FACTURE WHERE ID_CONTRAT = ?');
        $chk->execute([$idContrat]);
        if ((int)$chk->fetchColumn() > 0) {
            $error = 'Impossible de supprimer : une facture est liée à ce contrat.';
        } else {
            $pdo->prepare('DELETE FROM CONTRAT WHERE ID_CONTRAT = ?')->execute([$idContrat]);
            $success = 'Contrat supprimé.';
        }
    }
}

// ── Réservations CONFIRMEE sans contrat (pour le formulaire) ──
$resasSansContrat = $pdo->query(
    "SELECT r.ID_RESERVATION, r.DATE_RESERVATION, r.LIEU_RESERVATION, p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT
     FROM RESERVATION r
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
     LEFT JOIN CONTRAT ct ON ct.ID_RESERVATION = r.ID_RESERVATION
     WHERE r.STATUS_RESERVATION = 'CONFIRMEE' AND ct.ID_CONTRAT IS NULL
     ORDER BY r.DATE_RESERVATION DESC"
)->fetchAll();

// ── Liste des contrats existants ──────────────────────────────
$contrats = $pdo->query(
    'SELECT ct.*, r.DATE_RESERVATION, r.LIEU_RESERVATION, r.STATUS_RESERVATION,
            p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT,
            f.ID_FACTURE, f.NUM_FACTURE
     FROM CONTRAT ct
     JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
     LEFT JOIN FACTURE f ON f.ID_CONTRAT = ct.ID_CONTRAT
     ORDER BY ct.DATE_CONTRAT DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrats | Admin NY TIA SARY</title>
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
                <span>Contrats</span>
            </nav>

            <?php if ($success): ?><div class="dash-alert dash-alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="dash-alert dash-alert-error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 2fr;gap:28px;align-items:start;">

                <!-- FORMULAIRE NOUVEAU CONTRAT -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-file-signature" style="color:var(--primary-green);margin-right:8px;"></i> Nouveau contrat</h3>
                    </div>
                    <div class="dash-card-body padded">
                        <?php if (empty($resasSansContrat)): ?>
                            <div class="empty-state" style="padding:20px 0;">
                                <i class="fas fa-check-double" style="color:var(--primary-green);"></i>
                                <p style="font-size:0.9rem;">Toutes les réservations confirmées ont un contrat.</p>
                            </div>
                        <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="create">
                            <div class="dash-form-group">
                                <label for="id_reservation">Réservation confirmée <span class="required">*</span></label>
                                <select name="id_reservation" id="id_reservation" class="dash-select" required>
                                    <option value="">— Choisir —</option>
                                    <?php foreach ($resasSansContrat as $r): ?>
                                        <option value="<?= (int)$r['ID_RESERVATION'] ?>">
                                            #<?= (int)$r['ID_RESERVATION'] ?> — <?= htmlspecialchars($r['PRENOM_CLIENT'].' '.$r['NOM_CLIENT']) ?> — <?= htmlspecialchars($r['LIB_PRESTATION']) ?> (<?= date('d/m/Y', strtotime($r['DATE_RESERVATION'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="dash-form-group">
                                <label for="date_contrat">Date du contrat <span class="required">*</span></label>
                                <input type="date" name="date_contrat" id="date_contrat" class="dash-input"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <button type="submit" class="btn-dash btn-dash-primary" style="width:100%;justify-content:center;">
                                <i class="fas fa-file-signature"></i> Créer le contrat
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- LISTE DES CONTRATS -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-folder-open" style="color:var(--primary-green);margin-right:8px;"></i> Contrats signés</h3>
                        <span class="badge badge-confirm"><?= count($contrats) ?></span>
                    </div>
                    <div class="dash-card-body">
                        <?php if (empty($contrats)): ?>
                            <div class="empty-state"><i class="fas fa-file-signature"></i><p>Aucun contrat enregistré.</p></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Client</th>
                                        <th>Prestation</th>
                                        <th>Date contrat</th>
                                        <th>Facture</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($contrats as $ct): ?>
                                    <tr>
                                        <td>#<?= (int)$ct['ID_CONTRAT'] ?></td>
                                        <td><strong><?= htmlspecialchars($ct['PRENOM_CLIENT'].' '.$ct['NOM_CLIENT']) ?></strong></td>
                                        <td><?= htmlspecialchars($ct['LIB_PRESTATION']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($ct['DATE_CONTRAT'])) ?></td>
                                        <td>
                                            <?php if ($ct['ID_FACTURE']): ?>
                                                <a href="factures.php" class="badge badge-confirm" style="text-decoration:none;">
                                                    <i class="fas fa-file-invoice"></i> <?= htmlspecialchars($ct['NUM_FACTURE']) ?>
                                                </a>
                                            <?php else: ?>
                                                <a href="factures.php?id_contrat=<?= (int)$ct['ID_CONTRAT'] ?>" class="btn-dash btn-dash-outline btn-dash-sm">
                                                    <i class="fas fa-plus"></i> Créer facture
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$ct['ID_FACTURE']): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce contrat ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_contrat" value="<?= (int)$ct['ID_CONTRAT'] ?>">
                                                <button class="btn-dash btn-dash-danger btn-dash-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                            <?php else: ?>
                                                <span title="Contrat avec facture — non supprimable" style="color:#ccc;font-size:1rem;"><i class="fas fa-lock"></i></span>
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
