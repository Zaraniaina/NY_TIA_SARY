<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
require_once __DIR__ . '/../../util/prg_helper.php';
requireAdmin();

require_once __DIR__.'/composante/tolbarDto.php';
$titre = "Gestion des contrats";

// ── TRAITEMENT POST (PRG Pattern) ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ── Création d'un contrat ─────────────────────────────────────
    if ($action === 'create') {
        $idResa      = (int) ($_POST['id_reservation'] ?? 0);
        $dateContrat = trim($_POST['date_contrat'] ?? '');

        if (!$idResa || !$dateContrat) {
            prg_set_message('error', 'Tous les champs sont requis.');
        } else {
            // Vérifier qu'un contrat n'existe pas déjà
            $chk = $pdo->prepare('SELECT COUNT(*) FROM CONTRAT WHERE ID_RESERVATION = ?');
            $chk->execute([$idResa]);
            if ((int)$chk->fetchColumn() > 0) {
                prg_set_message('error', 'Un contrat existe déjà pour cette réservation.');
            } else {
                $pdo->prepare("INSERT INTO CONTRAT (ID_RESERVATION, STATUS_CONTRAT, DATE_CONTRAT) VALUES (?, 'EN ATTENTE', ?)")
                    ->execute([$idResa, $dateContrat]);
                // Mettre la réservation en CONFIRMEE si pas déjà
                $pdo->prepare("UPDATE RESERVATION SET STATUS_RESERVATION='CONFIRMEE' WHERE ID_RESERVATION=? AND STATUS_RESERVATION='EN ATTENTE'")
                    ->execute([$idResa]);
                prg_set_message('success', 'Contrat créé avec succès.');
            }
        }
        prg_redirect();
    }

    // ── Suppression d'un contrat ──────────────────────────────────
    if ($action === 'delete') {
        $idContrat = (int) ($_POST['id_contrat'] ?? 0);
        if ($idContrat) {
            // Vérifier qu'aucune facture n'est liée
            $chk = $pdo->prepare('SELECT COUNT(*) FROM FACTURE WHERE ID_CONTRAT = ?');
            $chk->execute([$idContrat]);
            if ((int)$chk->fetchColumn() > 0) {
                prg_set_message('error', 'Impossible de supprimer : une facture est liée à ce contrat.');
            } else {
                $pdo->prepare('DELETE FROM CONTRAT WHERE ID_CONTRAT = ?')->execute([$idContrat]);
                prg_set_message('success', 'Contrat supprimé.');
            }
        } else {
            prg_set_message('error', 'Contrat invalide.');
        }
        prg_redirect();
    }
}

// ── Récupérer les messages PRG pour affichage ──────────────────
$prgMessages = prg_get_messages();

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
                <span>Contrats</span>
            </nav>

            
            

            <div style="width: 100%;">

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
                                        <th>Status</th>
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
                                                
                                                    <span class="badge badge-danger" style="color: red;">Aucune facture</span> 
                                                
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span><?= htmlspecialchars($ct['STATUS_CONTRAT']) ?></span>
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

<!-- Toastify pour messages PRG -->
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<?php if (!empty($prgMessages)): ?>
<script>
window.addEventListener('DOMContentLoaded', () => {
    <?= prg_render_toasts($prgMessages) ?>
});
</script>
<?php endif; ?>

</body>
</html>