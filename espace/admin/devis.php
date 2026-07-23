<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
require_once __DIR__ . '/../../util/prg_helper.php';
requireAdmin();

require_once __DIR__.'/composante/tolbarDto.php';
//on changer le titre
$titre="Gestion des devis";

// ── TRAITEMENT POST (PRG Pattern) ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $idDevis = (int) ($_POST['id'] ?? 0);

    if ($action === 'valider' && $idDevis) {
        // Vérifier qu'une réservation existe (on crée un contrat symbolique)
        $stmt = $pdo->prepare('SELECT * FROM DEVIS WHERE ID = ?');
        $stmt->execute([$idDevis]);
        $devis = $stmt->fetch();
        prg_set_message('success', "Devis #$idDevis marqué comme validé. Un contrat devra être associé à une réservation.");
        prg_redirect();
    } elseif ($action === 'supprimer' && $idDevis) {
        try {
            $pdo->beginTransaction();

            $pdo->prepare('DELETE FROM DEVIS_CATEGORIES WHERE ID_DEVIS = ?')->execute([$idDevis]);
            $pdo->prepare('DELETE FROM PIECES_JOINTES WHERE ID = ?')->execute([$idDevis]);
            $pdo->prepare('DELETE FROM DEVIS WHERE ID = ?')->execute([$idDevis]);

            $pdo->commit();
            prg_set_message('success', "Devis #$idDevis supprimé.");
        } catch (PDOException $e) {
            $pdo->rollBack();
            prg_set_message('error', "Erreur lors de la suppression du devis.");
        }
        prg_redirect();
    } elseif ($action === 'envoyer_reponse' && $idDevis) {
        $uploadDir = __DIR__ . '/../../assets/uploads/devis_reponses/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if (!empty($_FILES['fichier_reponse']) && $_FILES['fichier_reponse']['error'] === UPLOAD_ERR_OK) {
            $fichier = $_FILES['fichier_reponse'];
            $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
            $nomUnique = 'devis_' . $idDevis . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $cheminFinal = $uploadDir . $nomUnique;
            $cheminPublic = 'assets/uploads/devis_reponses/' . $nomUnique;

            if (move_uploaded_file($fichier['tmp_name'], $cheminFinal)) {
                // Update DEVIS table
                $stmt = $pdo->prepare('UPDATE DEVIS SET FICHIER_REPONSE = ? WHERE ID = ?');
                $stmt->execute([$cheminPublic, $idDevis]);

                // Fetch devis details
                $stmt = $pdo->prepare('SELECT NOM, PRENOMS, EMAIL FROM DEVIS WHERE ID = ?');
                $stmt->execute([$idDevis]);
                $devisInfo = $stmt->fetch();

                if ($devisInfo) {
                    require_once __DIR__ . '/../../util/mailService.php';
                    $mailService = new MailService();
                    $clientName = $devisInfo['PRENOMS'] . ' ' . $devisInfo['NOM'];

                    // Check if client has account
                    $stmtAuth = $pdo->prepare('SELECT ID_AUTH FROM AUTHENTIFICATION WHERE EMAIL_AUTH = ?');
                    $stmtAuth->execute([$devisInfo['EMAIL']]);
                    if ($stmtAuth->fetch()) {
                        // Link to client space (adjust path as necessary)
                        $link = 'http://' . $_SERVER['HTTP_HOST'] . '/NY_TIA_SARY/espace/client/devis.php';
                        $mailService->sendDevisResponseToClient($devisInfo['EMAIL'], $clientName, $link);
                        
                        // --- Notification Client ---
                        $stmtCli = $pdo->prepare('SELECT ID_CLIENT FROM CLIENT WHERE EMAIL = ?');
                        $stmtCli->execute([$devisInfo['EMAIL']]);
                        $cliRow = $stmtCli->fetch();
                        if ($cliRow) {
                            $pdo->prepare("
                                INSERT INTO notification (TYPE_NOTIF, ID_REF_NOTIF, TITRE_NOTIF, MESS_NOTIF, LU_NOTIF, SUP_NOTIF, ID_CLIENT) 
                                VALUES ('client_devis', ?, 'Réponse à votre devis', 'Le studio a répondu à votre demande de devis.', 0, 0, ?)
                            ")->execute([$idDevis, $cliRow['ID_CLIENT']]);
                        }
                        
                    } else {
                        // Internaute: send with attachment
                        $mailService->sendDevisResponseWithAttachment($devisInfo['EMAIL'], $clientName, $cheminFinal);
                    }
                    prg_set_message('success', "Le devis a été envoyé avec succès au client.");
                }
            } else {
                prg_set_message('error', "Erreur lors de l'enregistrement du fichier.");
            }
        } else {
            prg_set_message('error', "Aucun fichier valide n'a été fourni.");
        }
        prg_redirect();
    }
}

// ── Récupérer les messages PRG pour affichage ──────────────────
$prgMessages = prg_get_messages();

// ── Liste des devis ───────────────────────────────────────────
$devis = $pdo->query(
    'SELECT d.*,
            p.LIB_PRESTATION,
            GROUP_CONCAT(c.LIB_CATEGORIE ORDER BY c.LIB_CATEGORIE SEPARATOR "||") AS CATEGORIES_LIST,
            (SELECT COUNT(*) FROM PIECES_JOINTES pj WHERE pj.ID = d.ID AND pj.PATH_PIECE <> "aucun") AS nb_pj,
            (SELECT GROUP_CONCAT(pj.PATH_PIECE SEPARATOR ",") FROM PIECES_JOINTES pj WHERE pj.ID = d.ID AND pj.PATH_PIECE <> "aucun") AS PJ_PATHS
     FROM DEVIS d
     JOIN PRESTATIONS p ON d.ID_PRESTATION = p.ID_PRESTATION
     LEFT JOIN DEVIS_CATEGORIES dc ON dc.ID_DEVIS = d.ID
     LEFT JOIN CATEGORIE c ON c.ID_CATEGORIE = dc.ID_CATEGORIE
     GROUP BY d.ID
     ORDER BY d.DATE_SOUHAITE DESC'
)->fetchAll();

// ── Marquer la notification comme lue puis nettoyer l'URL ─────
if (isset($_GET['mark_notif']) && (int) $_GET['mark_notif'] > 0) {
    $idNotif = (int) $_GET['mark_notif'];
    $idDevisSelect = (int) ($_GET['id'] ?? 0);

    try {
        $stmtMark = $pdo->prepare('UPDATE notification SET LU_NOTIF = 1 WHERE ID_NOTIF = ?');
        $stmtMark->execute([$idNotif]);
    } catch (PDOException $e) {
        // on ignore silencieusement, la redirection se fait quand même
    }

    header('Location: devis.php' . ($idDevisSelect > 0 ? '?id=' . $idDevisSelect : ''));
    exit;
}

$selectedDevisId = (int) ($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devis | Admin NY TIA SARY</title>
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
                <span>Devis</span>
            </nav>

            
            

            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-file-alt" style="color:var(--primary-green);margin-right:8px;"></i> Demandes de devis reçues</h3>
                    <span class="badge badge-waiting" id="devisCount"><?= count($devis) ?></span>
                </div>
                        <div class="dash-card-body">
                            <div style="padding:16px 20px;">
                    <div class="dash-form-group" style="position:relative;margin-bottom:0;">
                        <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#999;"></i>
                        <input type="text" id="searchDevis" class="dash-input"
                            style="padding-left:38px;padding-right:14px;width:100%;box-sizing:border-box;border-radius:8px;"
                            placeholder="Rechercher par client, téléphone, prestation, catégorie...">
                    </div>
                </div>
                <?php if (empty($devis)): ?>
                    <div class="empty-state"><i class="fas fa-file-times"></i><p>Aucune demande de devis pour le moment.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="dash-table" id="devisTable">
                        <thead>
                            <tr><th>#</th><th>Client</th><th>Téléphone</th><th>Prestation</th><th>Catégorie</th><th>Budget</th><th>Date souhaitée</th><th>PJ</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($devis as $d): ?>
                        <?php
                        $isSelected     = $selectedDevisId > 0 && (int)$d['ID'] === $selectedDevisId;
                        $categoriesArr  = !empty($d['CATEGORIES_LIST']) ? explode('||', $d['CATEGORIES_LIST']) : [];
                        $categoriesTxt  = !empty($categoriesArr) ? implode(', ', $categoriesArr) : '';
                        $searchBlob     = mb_strtolower($d['PRENOMS'] . ' ' . $d['NOM'] . ' ' . $d['TELEPHONE'] . ' ' . $d['LIB_PRESTATION'] . ' ' . $categoriesTxt);
                        $pjPaths        = !empty($d['PJ_PATHS']) ? explode(',', $d['PJ_PATHS']) : [];
                        ?>
                            <tr<?= $isSelected ? ' class="row-highlighted" id="devis-selected"' : '' ?> data-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES) ?>">
                            <td data-label="#">#<?= (int)$d['ID'] ?></td>
                            <td data-label="Client">
                                <strong><?= htmlspecialchars($d['PRENOMS'] . ' ' . $d['NOM']) ?></strong>
                                <?php if ($d['TYPE_VISITEUR'] && $d['TYPE_VISITEUR'] !== '—'): ?>
                                    <br><small style="color:#888;"><?= htmlspecialchars($d['TYPE_VISITEUR']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td data-label="Téléphone"><?= htmlspecialchars($d['TELEPHONE']) ?></td>
                            <td data-label="Prestation"><?= htmlspecialchars($d['LIB_PRESTATION']) ?></td>
                            <td data-label="Catégorie">
                                <?php if (empty($categoriesArr)): ?>
                                    <span style="color:#aaa;">—</span>
                                <?php else: ?>
                                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                        <?php foreach ($categoriesArr as $cat): ?>
                                            <span class="badge badge-waiting" style="font-weight:500;"><?= htmlspecialchars($cat) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Budget"><strong><?= htmlspecialchars($d['BUGET_ESTIMATIF']) . ' AR' ?></strong></td>
                            <td data-label="Date"><?= date('d/m/Y', strtotime($d['DATE_SOUHAITE'])) ?></td>
                            <td data-label="Pièces jointes">
                                <?php if ((int)$d['nb_pj'] > 0): ?>
                                    <span class="badge badge-confirm"><i class="fas fa-paperclip"></i> <?= (int)$d['nb_pj'] ?></span>
                                <?php else: ?>
                                    <span class="badge badge-confirm"><i class="fas fa-paperclip"></i>0</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Actions">
                                <button class="btn-dash btn-dash-sm btn-dash-outline" onclick="toggleDevisDetail(<?= $d['ID'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce devis ?');">
                                    <input type="hidden" name="action" value="supprimer">
                                    <input type="hidden" name="id" value="<?= (int)$d['ID'] ?>">
                                    <button type="submit" class="btn-dash btn-dash-sm btn-dash-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <!-- Ligne de détail du devis (initialement cachée) -->
                        <tr class="devis-detail-row" id="devis-detail-<?= (int)$d['ID'] ?>" style="display:none;">
                            <td colspan="9" class="devis-detail-cell">
                                <div class="devis-detail-content" style="background:#f8f8f6;border:1px solid var(--border-color);border-radius:12px;padding:20px;margin-top:10px;">
                                    <h4 style="font-family:var(--font-headings);font-weight:700;margin-bottom:16px;color:var(--logo-black);display:flex;align-items:center;gap:10px;">
                                        <i class="fas fa-info-circle" style="color:var(--primary-green);"></i>
                                        Détails du devis #<?= (int)$d['ID'] ?>
                                    </h4>
                                    
                                    <!-- Infos Client -->
                                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-bottom:20px;">
                                        <div>
                                            <strong style="color:var(--logo-black);font-size:0.9rem;">Nom :</strong><br>
                                            <span><?= htmlspecialchars($d['PRENOMS'] . ' ' . $d['NOM']) ?></span>
                                        </div>
                                        <div>
                                            <strong style="color:var(--logo-black);font-size:0.9rem;">Email :</strong><br>
                                            <span><?= htmlspecialchars($d['EMAIL']) ?></span>
                                        </div>
                                        <div>
                                            <strong style="color:var(--logo-black);font-size:0.9rem;">Téléphone :</strong><br>
                                            <span><?= htmlspecialchars($d['TELEPHONE']) ?></span>
                                        </div>
                                        <div>
                                            <strong style="color:var(--logo-black);font-size:0.9rem;">Type de visiteur :</strong><br>
                                            <span><?= htmlspecialchars($d['TYPE_VISITEUR']) ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Détails du devis -->
                                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-bottom:20px;">
                                        <div>
                                            <strong style="color:var(--logo-black);font-size:0.9rem;">Prestation :</strong><br>
                                            <span><?= htmlspecialchars($d['LIB_PRESTATION']) ?></span>
                                        </div>
                                        <div>
                                            <strong style="color:var(--logo-black);font-size:0.9rem;">Catégorie(s) :</strong><br>
                                            <span><?= !empty($categoriesTxt) ? htmlspecialchars($categoriesTxt) : '—' ?></span>
                                        </div>
                                        <div>
                                            <strong style="color:var(--logo-black);font-size:0.9rem;">Budget estimatif :</strong><br>
                                            <span><?= htmlspecialchars($d['BUGET_ESTIMATIF']) ?> AR</span>
                                        </div>
                                        <div>
                                            <strong style="color:var(--logo-black);font-size:0.9rem;">Date souhaitée :</strong><br>
                                            <span><?= date('d/m/Y', strtotime($d['DATE_SOUHAITE'])) ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Description -->
                                    <div style="margin-bottom:20px;">
                                        <strong style="color:var(--logo-black);font-size:0.9rem;display:block;margin-bottom:8px;">Description :</strong>
                                        <p style="margin:0;color:var(--text-body);line-height:1.6;"><?= nl2br(htmlspecialchars($d['DESCRIPTION'])) ?></p>
                                    </div>
                                    
                                    <!-- Pièces jointes -->
                                    <?php if (!empty($pjPaths) && $pjPaths[0] !== 'aucun'): ?>
                                        <div>
                                            <strong style="color:var(--logo-black);font-size:0.9rem;display:block;margin-bottom:8px;"><i class="fas fa-paperclip" style="color:var(--primary-green);"></i> Pièces jointes :</strong>
                                            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                                <?php foreach ($pjPaths as $path): ?>
                                                    <?php if ($path !== 'aucun'): ?>
                                                        <a href="<?= htmlspecialchars('../../' . $path) ?>" target="_blank" class="btn-dash btn-dash-sm btn-dash-outline" style="padding:6px 12px;font-size:0.8rem;">
                                                            <i class="fas fa-file-pdf"></i> Télécharger
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Formulaire pour envoyer le devis au client -->
                                    <div style="margin-top:20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                                        <strong style="color:var(--logo-black);font-size:0.9rem;display:block;margin-bottom:8px;"><i class="fas fa-paper-plane" style="color:var(--primary-green);"></i> Envoyer un devis au client :</strong>
                                        <?php if (!empty($d['FICHIER_REPONSE'])): ?>
                                            <div style="margin-bottom:10px; color:#555; font-size:0.85rem;">
                                                <i class="fas fa-check-circle" style="color:var(--primary-green);"></i> Un devis a déjà été envoyé. <a href="<?= htmlspecialchars('../../' . $d['FICHIER_REPONSE']) ?>" target="_blank" style="color:var(--primary-green);">Voir le fichier</a>.
                                            </div>
                                        <?php endif; ?>
                                        <form method="POST" enctype="multipart/form-data" style="display:flex; align-items:center; gap:10px;">
                                            <input type="hidden" name="action" value="envoyer_reponse">
                                            <input type="hidden" name="id" value="<?= (int)$d['ID'] ?>">
                                            <input type="file" name="fichier_reponse" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" required style="font-size:0.85rem; padding: 5px; border: 1px solid var(--border-color); border-radius: 4px;">
                                            <button type="submit" class="btn-dash btn-dash-sm btn-dash-primary"><i class="fas fa-paper-plane"></i> Envoyer</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                                <tr id="noSearchResultsDevis" style="display:none;">
                                    <td colspan="9" style="text-align:center;color:#999;padding:20px;">
                                        <i class="fas fa-search"></i> Aucune demande ne correspond à votre recherche.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                </div>
                <?php endif; ?>
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

// Fonction pour afficher/masquer les détails du devis
function toggleDevisDetail(id) {
    const detailRow = document.getElementById('devis-detail-' + id);
    const currentlyVisible = document.querySelector('.devis-detail-row:not([style*="display: none"])');
    
    // Fermer si on clique sur la même ligne
    if (detailRow && detailRow.style.display !== 'none') {
        detailRow.style.display = 'none';
        return;
    }
    
    // Fermer la ligne visible actuelle
    if (currentlyVisible) {
        currentlyVisible.style.display = 'none';
    }
    
    // Afficher la nouvelle ligne
    if (detailRow) {
        detailRow.style.display = '';
        detailRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

const selectedRow = document.getElementById('devis-selected');
if (selectedRow) {
    selectedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
// ── Recherche / filtre en direct de la liste des devis ──
const searchDevis      = document.getElementById('searchDevis');
const devisTable       = document.getElementById('devisTable');
const devisCount       = document.getElementById('devisCount');
const noSearchResultsD = document.getElementById('noSearchResultsDevis');

searchDevis?.addEventListener('input', () => {
    const term = searchDevis.value.trim().toLowerCase();
    const rows = devisTable.querySelectorAll('tbody tr[data-search]');
    let visibleCount = 0;

    rows.forEach(row => {
        const match = row.dataset.search.includes(term);
        row.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });

    if (noSearchResultsD) {
        noSearchResultsD.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }
    if (devisCount) {
        devisCount.textContent = visibleCount;
    }
});
</script>

<!-- Toastify pour messages PRG -->
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<?php if (!empty($prgMessages)): ?>
<script>
window.addEventListener('DOMContentLoaded', () => {
    <?= prg_render_toasts($prgMessages) ?>
    // Nettoyer l'URL
    const url = new URL(window.location);
    url.searchParams.delete('id');
    url.searchParams.delete('mark_notif');
    window.history.replaceState({}, '', url);
});
</script>
<?php endif; ?>

</body>
</html>