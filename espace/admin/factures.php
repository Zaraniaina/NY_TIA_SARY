<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
require_once __DIR__ . '/../../util/prg_helper.php';
requireAdmin();

require_once __DIR__.'/composante/tolbarDto.php';
$titre = "Gestion des factures";

// ── TRAITEMENT POST (PRG Pattern) ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ── Création d'une facture ────────────────────────────────────
    if ($action === 'create') {
        $idContrat = (int) ($_POST['id_contrat'] ?? 0);

        if (!$idContrat) {
            prg_set_message('error', 'Veuillez sélectionner un contrat.');
        } else {
            // Vérifier qu'une facture n'existe pas déjà
            $chk = $pdo->prepare('SELECT COUNT(*) FROM FACTURE WHERE ID_CONTRAT = ?');
            $chk->execute([$idContrat]);
            if ((int)$chk->fetchColumn() > 0) {
                prg_set_message('error', 'Une facture existe déjà pour ce contrat.');
            } else {
                // Générer un numéro unique FAC-YYYY-XXXX
                $annee  = date('Y');
                $cntStmt = $pdo->query("SELECT COUNT(*) FROM FACTURE WHERE YEAR(DATE_FACTURE) = $annee");
                $cnt    = (int)$cntStmt->fetchColumn() + 1;
                $numFac = 'FAC-' . $annee . '-' . str_pad((string)$cnt, 4, '0', STR_PAD_LEFT);

                // Récupérer le montant total depuis le contrat -> réservation -> catégories
                $stmtPrice = $pdo->prepare(
                    'SELECT SUM(rc.PRIX) 
                     FROM CONTRAT ct
                     JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
                     LEFT JOIN RESERVATION_CATEGORIE rc ON rc.ID_RESERVATION = r.ID_RESERVATION
                     WHERE ct.ID_CONTRAT = ?'
                );
                $stmtPrice->execute([$idContrat]);
                $montant = (int)($stmtPrice->fetchColumn() ?: 0);

                $pdo->prepare("INSERT INTO FACTURE (ID_CONTRAT, NUM_FACTURE, STATUS_FACTURE, MONTANT_FACTURE, DATE_FACTURE) VALUES (?, ?, 'NON PAYEE', ?, CURDATE())")
                    ->execute([$idContrat, $numFac, $montant]);
                prg_set_message('success', "Facture $numFac créée avec succès.");
            }
        }
        prg_redirect();
    }

    // ── Modification du statut d'une facture ─────────────────────────
    if ($action === 'update_status') {
        $idFac  = (int) ($_POST['id_facture'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        if ($idFac && in_array($status, ['PAYEE', 'NON PAYEE'], true)) {
            $pdo->prepare('UPDATE FACTURE SET STATUS_FACTURE = ? WHERE ID_FACTURE = ?')
                ->execute([$status, $idFac]);
            prg_set_message('success', 'Le statut de la facture a été mis à jour avec succès.');
        } else {
            prg_set_message('error', 'Données invalides.');
        }
        prg_redirect();
    }

    // ── Suppression d'une facture ─────────────────────────────────
    if ($action === 'delete') {
        $idFac = (int) ($_POST['id_facture'] ?? 0);
        if ($idFac) {
            $pdo->prepare('DELETE FROM FACTURE WHERE ID_FACTURE = ?')->execute([$idFac]);
            prg_set_message('success', 'Facture supprimée.');
        } else {
            prg_set_message('error', 'Facture invalide.');
        }
        prg_redirect();
    }
}

// ── Récupérer les messages PRG pour affichage ──────────────────
$prgMessages = prg_get_messages();

// ── Pré-sélection depuis l'URL (venant de contrats.php) ───────
$preselContrat = (int) ($_GET['id_contrat'] ?? 0);

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

// ── Recherche et Liste complète des factures ───────────────────
$search = trim($_GET['q'] ?? '');
$sql = 'SELECT f.*, ct.DATE_CONTRAT, r.DATE_RESERVATION, r.LIEU_RESERVATION,
                p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT
         FROM FACTURE f
         JOIN CONTRAT ct ON f.ID_CONTRAT = ct.ID_CONTRAT
         JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
         JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
         JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT';

$params = [];
if ($search !== '') {
    $sql .= ' WHERE f.NUM_FACTURE LIKE ? OR c.NOM_CLIENT LIKE ? OR c.PRENOM_CLIENT LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY f.DATE_FACTURE DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$factures = $stmt->fetchAll();
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

    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

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

            <div style="width:100%;">

                <!-- LISTE DES FACTURES -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-list" style="color:var(--primary-green);margin-right:8px;"></i> Toutes les factures</h3>
                        <span class="badge badge-confirm"><?= count($factures) ?></span>
                    </div>
                    <div class="dash-card-body">
                        <!-- BARRE DE RECHERCHE -->
                        <form method="GET" action="" style="display:flex;gap:8px;margin-bottom:16px;">
                            <div class="dash-search-bar" style="margin:0;flex:1;">
                                <i class="fas fa-search"></i>
                                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher par n° de facture ou client...">
                            </div>
                            <button type="submit" class="btn-dash btn-dash-primary" style="padding:10px 14px;"><i class="fas fa-search"></i> Filtrer</button>
                            <?php if ($search !== ''): ?>
                                <a href="factures.php" class="btn-dash btn-dash-outline" style="padding:10px 14px;text-decoration:none;"><i class="fas fa-times"></i> Ràz</a>
                            <?php endif; ?>
                        </form>

                        <?php if (empty($factures)): ?>
                            <div class="empty-state"><i class="fas fa-file-invoice"></i><p>Aucune facture trouvée.</p></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>N° Facture</th>
                                        <th>Client</th>
                                        <th>Prestation</th>
                                        <th>Date prestation</th>
                                        <th>Statut</th>
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
                                        <td>
                                            <form method="POST" action="" style="display:inline-flex;margin:0;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="id_facture" value="<?= (int)$f['ID_FACTURE'] ?>">
                                                <select name="status" class="dash-select" style="padding:4px 8px;font-size:0.75rem;width:auto;margin:0;" onchange="this.form.submit()">
                                                    <option value="NON PAYEE" <?= $f['STATUS_FACTURE'] === 'NON PAYEE' ? 'selected' : '' ?>>NON PAYÉE</option>
                                                    <option value="PAYEE" <?= $f['STATUS_FACTURE'] === 'PAYEE' ? 'selected' : '' ?>>PAYÉE</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <div style="display:inline-flex;gap:6px;">
                                                <a href="../client/generer_facture_pdf.php?id_facture=<?= (int)$f['ID_FACTURE'] ?>" class="btn-dash btn-dash-outline btn-dash-sm" title="Télécharger le PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                                <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Supprimer cette facture ?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_facture" value="<?= (int)$f['ID_FACTURE'] ?>">
                                                    <button class="btn-dash btn-dash-danger btn-dash-sm"><i class="fas fa-trash"></i></button>
                                                </form>
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
</div>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });
</script>

<!-- Toastify pour messages PRG -->
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<?php if (!empty($prgMessages)): ?>
<script>
window.addEventListener('DOMContentLoaded', () => {
    <?= prg_render_toasts($prgMessages) ?>
    // Nettoyer l'URL
    const url = new URL(window.location);
    url.searchParams.delete('q');
    url.searchParams.delete('id_contrat');
    window.history.replaceState({}, '', url);
});
</script>
<?php endif; ?>

</body>
</html>