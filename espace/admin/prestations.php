<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();

require_once __DIR__.'/composante/tolbarDto.php';
//on changer le titre
$titre="Gestion des prestations";
$success = $error = '';

// ── CRUD Prestations + Categories ─────────────────────────────
// Schéma réel : CATEGORIES.ID_PRESTATION -> PRESTATIONS.ID_PRESTATION
// (une prestation peut avoir plusieurs catégories, chacune référence la prestation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action           = $_POST['action'] ?? '';
    $idPrest          = (int) ($_POST['id_prestation'] ?? 0);
    $idPrestExistante = (int) ($_POST['id_prestation_existante'] ?? 0);
    $libPrest         = trim($_POST['lib_prestation'] ?? '');
    $libCategorie     = trim($_POST['lib_categorie'] ?? '');
    $tarifCategRaw    = trim($_POST['tarif_categorie'] ?? '');

    if ($action === 'create') {
        // La catégorie et le tarif sont toujours requis
        if ($libCategorie === '' || $tarifCategRaw === '') {
            $error = 'La catégorie et le tarif sont requis.';
        } elseif (!is_numeric($tarifCategRaw)) {
            $error = 'Le tarif de la catégorie doit être un nombre.';
        } elseif ($idPrestExistante <= 0 && $libPrest === '') {
            // Ni prestation existante sélectionnée, ni nouveau nom fourni
            $error = 'Sélectionnez une prestation existante ou saisissez le nom d\'une nouvelle prestation.';
        } else {
            $tarifCateg = (int) $tarifCategRaw;
            try {
                $pdo->beginTransaction();

                if ($idPrestExistante > 0) {
                    // ── Cas 1 : on rattache la nouvelle catégorie à une prestation existante ──
                    $chk = $pdo->prepare('SELECT LIB_PRESTATION FROM PRESTATIONS WHERE ID_PRESTATION = ?');
                    $chk->execute([$idPrestExistante]);
                    $libExistante = $chk->fetchColumn();

                    if (!$libExistante) {
                        $pdo->rollBack();
                        $error = 'La prestation sélectionnée est introuvable.';
                    } else {
                        $pdo->prepare('INSERT INTO CATEGORIES (LIB_CATEGORIE, TARIF_CATEGORIE, ID_PRESTATION) VALUES (?, ?, ?)')
                            ->execute([$libCategorie, $tarifCateg, $idPrestExistante]);
                        $pdo->commit();
                        $success = "Catégorie « $libCategorie » ajoutée à la prestation « $libExistante ».";
                    }
                } else {
                    // ── Cas 2 : nouvelle prestation + nouvelle catégorie ──
                    $pdo->prepare('INSERT INTO PRESTATIONS (LIB_PRESTATION) VALUES (?)')
                        ->execute([$libPrest]);
                    $newIdPrest = (int) $pdo->lastInsertId();

                    $pdo->prepare('INSERT INTO CATEGORIES (LIB_CATEGORIE, TARIF_CATEGORIE, ID_PRESTATION) VALUES (?, ?, ?)')
                        ->execute([$libCategorie, $tarifCateg, $newIdPrest]);

                    $pdo->commit();
                    $success = "Prestation « $libPrest » et catégorie « $libCategorie » ajoutées.";
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $error = "Erreur lors de l'ajout : " . $e->getMessage();
            }
        }
    } elseif ($action === 'edit' && $idPrest) {
        // id_categorie identifie précisément QUELLE catégorie on modifie
        // (une prestation peut en avoir plusieurs, on ne veut pas toujours retomber sur la première)
        $idCateg = (int) ($_POST['id_categorie'] ?? 0);

        if ($libPrest === '' || $libCategorie === '' || $tarifCategRaw === '') {
            $error = 'Tous les champs sont requis (prestation, catégorie, tarif).';
        } elseif (!is_numeric($tarifCategRaw)) {
            $error = 'Le tarif de la catégorie doit être un nombre.';
        } else {
            $tarifCateg = (int) $tarifCategRaw;
            try {
                $pdo->beginTransaction();

                // Mise à jour du libellé de la prestation (partagé par toutes ses catégories)
                $pdo->prepare('UPDATE PRESTATIONS SET LIB_PRESTATION = ? WHERE ID_PRESTATION = ?')
                    ->execute([$libPrest, $idPrest]);

                if ($idCateg > 0) {
                    // On vérifie que cette catégorie appartient bien à cette prestation avant de la modifier
                    $chkCateg = $pdo->prepare('SELECT ID_PRESTATION FROM CATEGORIES WHERE ID_CATEGORIE = ?');
                    $chkCateg->execute([$idCateg]);
                    $ownerPrest = (int) $chkCateg->fetchColumn();

                    if ($ownerPrest !== $idPrest) {
                        $pdo->rollBack();
                        $error = 'Cette catégorie ne correspond pas à la prestation sélectionnée.';
                    } else {
                        $pdo->prepare('UPDATE CATEGORIES SET LIB_CATEGORIE = ?, TARIF_CATEGORIE = ? WHERE ID_CATEGORIE = ?')
                            ->execute([$libCategorie, $tarifCateg, $idCateg]);
                        $pdo->commit();
                        $success = "Prestation mise à jour.";
                    }
                } else {
                    // Pas d'id de catégorie fourni : on en crée une nouvelle pour cette prestation
                    $pdo->prepare('INSERT INTO CATEGORIES (LIB_CATEGORIE, TARIF_CATEGORIE, ID_PRESTATION) VALUES (?, ?, ?)')
                        ->execute([$libCategorie, $tarifCateg, $idPrest]);
                    $pdo->commit();
                    $success = "Prestation mise à jour.";
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $error = "Erreur lors de la mise à jour : " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete' && $idPrest) {
        // Deux portées possibles, choisies dans le modal côté client :
        // - 'categorie' : supprime uniquement la catégorie ciblée
        // - 'prestation' : supprime la prestation ET toutes ses catégories
        $idCateg = (int) ($_POST['id_categorie'] ?? 0);
        $scope   = $_POST['delete_scope'] ?? 'categorie';

        if ($scope === 'prestation') {
            // Suppression de la prestation entière : on vérifie d'abord qu'aucune réservation ne l'utilise
            $stmtResa = $pdo->prepare('SELECT COUNT(*) FROM RESERVATION WHERE ID_PRESTATION = ?');
            $stmtResa->execute([$idPrest]);
            $nbResa = (int) $stmtResa->fetchColumn();

            if ($nbResa > 0) {
                $error = "Impossible de supprimer : $nbResa réservation(s) utilisent cette prestation.";
            } else {
                try {
                    $pdo->beginTransaction();
                    $pdo->prepare('DELETE FROM CATEGORIES WHERE ID_PRESTATION = ?')->execute([$idPrest]);
                    $pdo->prepare('DELETE FROM PRESTATIONS WHERE ID_PRESTATION = ?')->execute([$idPrest]);
                    $pdo->commit();
                    $success = "Prestation et toutes ses catégories supprimées.";
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) { $pdo->rollBack(); }
                    $error = "Erreur lors de la suppression : " . $e->getMessage();
                }
            }
        } else {
            // Suppression d'UNE catégorie précise. Les réservations référencent ID_PRESTATION,
            // jamais ID_CATEGORIE : supprimer une catégorie ne casse donc jamais une réservation.
            try {
                $pdo->beginTransaction();

                if ($idCateg > 0) {
                    $pdo->prepare('DELETE FROM CATEGORIES WHERE ID_CATEGORIE = ? AND ID_PRESTATION = ?')
                        ->execute([$idCateg, $idPrest]);
                }

                // S'il ne reste plus aucune catégorie pour cette prestation ET qu'aucune
                // réservation ne l'utilise, on nettoie aussi la prestation devenue inutile.
                $stmtRest = $pdo->prepare('SELECT COUNT(*) FROM CATEGORIES WHERE ID_PRESTATION = ?');
                $stmtRest->execute([$idPrest]);
                $categRestantes = (int) $stmtRest->fetchColumn();

                if ($categRestantes === 0) {
                    $stmtResa = $pdo->prepare('SELECT COUNT(*) FROM RESERVATION WHERE ID_PRESTATION = ?');
                    $stmtResa->execute([$idPrest]);
                    $nbResa = (int) $stmtResa->fetchColumn();

                    if ($nbResa === 0) {
                        $pdo->prepare('DELETE FROM PRESTATIONS WHERE ID_PRESTATION = ?')->execute([$idPrest]);
                        $success = "Catégorie et prestation supprimées (plus aucune catégorie associée).";
                    } else {
                        $success = "Catégorie supprimée. La prestation est conservée car des réservations l'utilisent.";
                    }
                } else {
                    $success = "Catégorie supprimée.";
                }

                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $error = "Erreur lors de la suppression : " . $e->getMessage();
            }
        }
    }
}

// Une ligne par catégorie (une prestation avec 2 catégories = 2 lignes)
$prestations = $pdo->query(
    'SELECT p.ID_PRESTATION, p.LIB_PRESTATION, c.ID_CATEGORIE, c.LIB_CATEGORIE, c.TARIF_CATEGORIE
     FROM PRESTATIONS p
     LEFT JOIN CATEGORIES c ON c.ID_PRESTATION = p.ID_PRESTATION
     ORDER BY p.LIB_PRESTATION, c.LIB_CATEGORIE'
)->fetchAll();

// Nombre de réservations par prestation, calculé à part (une réservation ne cible pas une catégorie précise)
$resaParPrestation = $pdo->query(
    'SELECT ID_PRESTATION, COUNT(*) AS nb FROM RESERVATION GROUP BY ID_PRESTATION'
)->fetchAll(PDO::FETCH_KEY_PAIR);

// Liste distincte des prestations (pour le select "prestation existante")
$toutesPrestations = $pdo->query(
    'SELECT ID_PRESTATION, LIB_PRESTATION FROM PRESTATIONS ORDER BY LIB_PRESTATION'
)->fetchAll();

$editPrest = null;
if (isset($_GET['edit'])) {
    $se = $pdo->prepare(
        'SELECT p.ID_PRESTATION, p.LIB_PRESTATION, c.ID_CATEGORIE, c.LIB_CATEGORIE, c.TARIF_CATEGORIE
         FROM PRESTATIONS p
         LEFT JOIN CATEGORIES c ON c.ID_PRESTATION = p.ID_PRESTATION
         WHERE p.ID_PRESTATION = ?
         LIMIT 1'
    );
    $se->execute([(int)$_GET['edit']]);
    $editPrest = $se->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prestations | Admin NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .dash-alert { position: relative; padding-right: 40px; }
        .alert-close-btn {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            color: inherit;
            opacity: 0.6;
            padding: 4px;
            line-height: 1;
        }
        .alert-close-btn:hover { opacity: 1; }
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
                <span>Prestations</span>
            </nav>

            <?php if ($success): ?>
            <div class="dash-alert dash-alert-success" id="alertBox">
                <i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="alert-close-btn" aria-label="Fermer"><i class="fas fa-times"></i></button>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="dash-alert dash-alert-error" id="alertBox">
                <i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="alert-close-btn" aria-label="Fermer"><i class="fas fa-times"></i></button>
            </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 2fr;gap:28px;align-items:start;">

                <!-- FORMULAIRE -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3 id="formTitle"><i class="fas fa-<?= $editPrest ? 'edit' : 'plus' ?>" id="formIcon" style="color:var(--primary-green);margin-right:8px;"></i>
                            <span id="formTitleText"><?= $editPrest ? 'Modifier' : 'Ajouter' ?></span>
                        </h3>
                        <?php if ($editPrest): ?>
                            <a href="prestations.php" class="btn-dash btn-dash-outline btn-dash-sm"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                    <div class="dash-card-body padded">
                        <form method="POST" action="" id="prestationForm">
                            <input type="hidden" name="action" id="formAction" value="<?= $editPrest ? 'edit' : 'create' ?>">
                            <input type="hidden" name="id_prestation" id="id_prestation" value="<?= $editPrest ? (int)$editPrest['ID_PRESTATION'] : '' ?>">
                            <input type="hidden" name="id_categorie" id="id_categorie" value="<?= $editPrest ? (int)($editPrest['ID_CATEGORIE'] ?? 0) : '' ?>">

                            <!-- Sélection d'une prestation existante (uniquement en mode "Ajouter") -->
                            <div class="dash-form-group" id="groupExistante" style="<?= $editPrest ? 'display:none;' : '' ?>">
                                <label for="id_prestation_existante">Prestation existante</label>
                                <select name="id_prestation_existante" id="id_prestation_existante" class="dash-input">
                                    <option value="">-- Créer une nouvelle prestation --</option>
                                    <?php foreach ($toutesPrestations as $pr): ?>
                                        <option value="<?= (int)$pr['ID_PRESTATION'] ?>"><?= htmlspecialchars($pr['LIB_PRESTATION']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small style="color:#888;display:block;margin-top:4px;">
                                    Choisissez une prestation existante pour lui ajouter une nouvelle catégorie, sans retaper son nom.
                                </small>
                            </div>

                            <div class="dash-form-group" id="groupNouvelle">
                                <label for="lib_prestation">Nom de la prestation <span class="required" id="reqLibPrest">*</span></label>
                                <input type="text" name="lib_prestation" id="lib_prestation" class="dash-input"
                                       placeholder="Ex: Mariage, Corporate, Mode..."
                                       value="<?= htmlspecialchars($editPrest['LIB_PRESTATION'] ?? '') ?>"
                                       <?= $editPrest ? 'required' : 'required' ?>>
                            </div>

                            <div class="dash-form-group">
                                <label for="lib_categorie">Catégorie de la prestation <span class="required">*</span></label>
                                <input type="text" name="lib_categorie" id="lib_categorie" class="dash-input"
                                       placeholder="Ex: Standard, Premium, Luxe..."
                                       value="<?= htmlspecialchars($editPrest['LIB_CATEGORIE'] ?? '') ?>" required>
                            </div>
                            <div class="dash-form-group">
                                <label for="tarif_categorie">Tarif de la catégorie (Ar) <span class="required">*</span></label>
                                <input type="number" name="tarif_categorie" id="tarif_categorie" class="dash-input"
                                       placeholder="Ex: 150000" min="0"
                                       value="<?= htmlspecialchars($editPrest['TARIF_CATEGORIE'] ?? '') ?>" required>
                            </div>
                            <button type="submit" class="btn-dash btn-dash-primary" id="submitBtn" style="width:100%;justify-content:center;">
                                <i class="fas fa-save"></i> <span id="submitBtnText"><?= $editPrest ? 'Mettre à jour' : 'Ajouter la prestation' ?></span>
                            </button>
                            <button type="button" class="btn-dash btn-dash-outline" id="cancelBtn" style="width:100%;justify-content:center;margin-top:8px;<?= $editPrest ? '' : 'display:none;' ?>">
                                <i class="fas fa-times"></i> Annuler
                            </button>
                        </form>
                    </div>
                </div>

                <!-- LISTE -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-concierge-bell" style="color:var(--primary-green);margin-right:8px;"></i> Liste des prestations</h3>
                        <span class="badge badge-confirm" id="prestationsCount"><?= count($prestations) ?></span>
                    </div>
                    <div class="dash-card-body">
                        <div style="padding:16px 20px;">
                            <div class="dash-form-group" style="position:relative;margin-bottom:0;">
                                <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#999;"></i>
                                <input type="text" id="searchPrestation" class="dash-input"
                                       style="padding-left:38px;padding-right:14px;width:100%;box-sizing:border-box;border-radius:8px;"
                                       placeholder="Rechercher une prestation ou une catégorie...">
                            </div>
                        </div>
                        <?php if (empty($prestations)): ?>
                            <div class="empty-state"><i class="fas fa-concierge-bell"></i><p>Aucune prestation.</p></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="dash-table" id="prestationsTable">
                                <thead><tr><th>#</th><th>Prestation</th><th>Catégorie</th><th>Réservations</th><th>Actions</th></tr></thead>
                                <tbody>
                                <?php $i = 0; foreach ($prestations as $p):
                                    $i++;
                                    $nbResas = $resaParPrestation[$p['ID_PRESTATION']] ?? 0;
                                ?>
                                    <tr data-search="<?= htmlspecialchars(mb_strtolower($p['LIB_PRESTATION'] . ' ' . ($p['LIB_CATEGORIE'] ?? '')), ENT_QUOTES) ?>">
                                        <td>#<?= $i ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($p['LIB_PRESTATION']) ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($p['LIB_CATEGORIE']): ?>
                                                <span><?= htmlspecialchars($p['LIB_CATEGORIE']) ?></span>
                                                <small style="display:block;color:#888;"><?= number_format((float)$p['TARIF_CATEGORIE'], 0, ',', ' ') ?> Ar</small>
                                            <?php else: ?>
                                                <span style="color:#ccc;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $nbResas > 0 ? 'badge-confirm' : 'badge-waiting' ?>">
                                                <?= (int)$nbResas ?> résa
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn-dash btn-dash-outline btn-dash-sm btn-edit-prest"
                                                    data-id="<?= (int)$p['ID_PRESTATION'] ?>"
                                                    data-id-categorie="<?= (int)($p['ID_CATEGORIE'] ?? 0) ?>"
                                                    data-prestation="<?= htmlspecialchars($p['LIB_PRESTATION'], ENT_QUOTES) ?>"
                                                    data-categorie="<?= htmlspecialchars($p['LIB_CATEGORIE'] ?? '', ENT_QUOTES) ?>"
                                                    data-tarif="<?= htmlspecialchars((string)($p['TARIF_CATEGORIE'] ?? ''), ENT_QUOTES) ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($p['ID_CATEGORIE']): ?>
                                            <button type="button" class="btn-dash btn-dash-danger btn-dash-sm btn-delete-prest"
                                                    data-id-prestation="<?= (int)$p['ID_PRESTATION'] ?>"
                                                    data-id-categorie="<?= (int)$p['ID_CATEGORIE'] ?>"
                                                    data-prestation-name="<?= htmlspecialchars($p['LIB_PRESTATION'], ENT_QUOTES) ?>"
                                                    data-categorie-name="<?= htmlspecialchars($p['LIB_CATEGORIE'], ENT_QUOTES) ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr id="noSearchResults" style="display:none;">
                                    <td colspan="5" style="text-align:center;color:#999;padding:20px;">
                                        <i class="fas fa-search"></i> Aucune prestation ne correspond à votre recherche.
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
    </div>
</div>

<!-- MODAL DE SUPPRESSION -->
<div class="delete-modal-overlay" id="deleteModalOverlay">
    <div class="delete-modal">
        <div class="delete-modal-header">
            <i class="fas fa-exclamation-triangle" style="color:var(--primary-red, #e63946);"></i>
            <h3>Que souhaitez-vous supprimer ?</h3>
        </div>
        <p class="delete-modal-text">
            Catégorie « <strong id="deleteModalCategorie"></strong> » de la prestation « <strong id="deleteModalPrestation"></strong> ».
        </p>
        <div class="delete-modal-actions">
            <button type="button" class="btn-dash btn-dash-outline" id="btnDeleteCategorieSeule">
                <i class="fas fa-tag"></i> Supprimer uniquement cette catégorie
            </button>
            <button type="button" class="btn-dash btn-dash-danger" id="btnDeletePrestationEntiere">
                <i class="fas fa-trash"></i> Supprimer toute la prestation (et ses catégories)
            </button>
            <button type="button" class="btn-dash btn-dash-outline" id="btnCancelDelete">
                Annuler
            </button>
        </div>
    </div>
</div>

<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id_prestation" id="deleteIdPrestation">
    <input type="hidden" name="id_categorie" id="deleteIdCategorie">
    <input type="hidden" name="delete_scope" id="deleteScope">
</form>

<style>
    .delete-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .delete-modal-overlay.open { display: flex; }
    .delete-modal {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        max-width: 420px;
        width: 90%;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    .delete-modal-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
    }
    .delete-modal-header h3 { margin: 0; font-size: 1.1rem; }
    .delete-modal-text { color: #555; margin-bottom: 20px; font-size: 0.92rem; }
    .delete-modal-actions { display: flex; flex-direction: column; gap: 10px; }
    .delete-modal-actions .btn-dash { width: 100%; justify-content: center; }
</style>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// ── Fermeture des messages de succès/erreur au clic sur la croix ──
document.querySelectorAll('.alert-close-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        btn.closest('.dash-alert')?.remove();
    });
});

// ── Éléments du formulaire ──
const formTitleText     = document.getElementById('formTitleText');
const formIcon          = document.getElementById('formIcon');
const formAction        = document.getElementById('formAction');
const idPrestation      = document.getElementById('id_prestation');
const idCategorieField  = document.getElementById('id_categorie');
const idPrestExistante  = document.getElementById('id_prestation_existante');
const groupExistante    = document.getElementById('groupExistante');
const groupNouvelle     = document.getElementById('groupNouvelle');
const libPrestation     = document.getElementById('lib_prestation');
const libCategorie      = document.getElementById('lib_categorie');
const tarifCategorie    = document.getElementById('tarif_categorie');
const submitBtnText     = document.getElementById('submitBtnText');
const cancelBtn         = document.getElementById('cancelBtn');

// Quand on choisit une prestation existante : on masque/désactive le champ "nouveau nom"
idPrestExistante?.addEventListener('change', () => {
    if (idPrestExistante.value !== '') {
        groupNouvelle.style.display = 'none';
        libPrestation.required = false;
        libPrestation.value = '';
    } else {
        groupNouvelle.style.display = '';
        libPrestation.required = true;
    }
});

// ── Clic sur "Modifier" une prestation existante dans la liste ──
document.querySelectorAll('.btn-edit-prest').forEach(btn => {
    btn.addEventListener('click', () => {
        formAction.value     = 'edit';
        idPrestation.value   = btn.dataset.id;
        idCategorieField.value = btn.dataset.idCategorie || '';
        libPrestation.value  = btn.dataset.prestation;
        libCategorie.value   = btn.dataset.categorie;
        tarifCategorie.value = btn.dataset.tarif;

        // En modification, on cache le sélecteur "prestation existante" (pas pertinent ici)
        groupExistante.style.display = 'none';
        if (idPrestExistante) idPrestExistante.value = '';
        groupNouvelle.style.display = '';
        libPrestation.required = true;

        formTitleText.textContent = 'Modifier';
        formIcon.classList.remove('fa-plus');
        formIcon.classList.add('fa-edit');
        submitBtnText.textContent = 'Mettre à jour';
        cancelBtn.style.display = 'block';

        libPrestation.scrollIntoView({ behavior: 'smooth', block: 'center' });
        libPrestation.focus();
    });
});

cancelBtn?.addEventListener('click', () => {
    formAction.value = 'create';
    idPrestation.value = '';
    idCategorieField.value = '';
    libPrestation.value = '';
    libCategorie.value = '';
    tarifCategorie.value = '';
    if (idPrestExistante) idPrestExistante.value = '';

    groupExistante.style.display = '';
    groupNouvelle.style.display = '';
    libPrestation.required = true;

    formTitleText.textContent = 'Ajouter';
    formIcon.classList.remove('fa-edit');
    formIcon.classList.add('fa-plus');
    submitBtnText.textContent = 'Ajouter la prestation';
    cancelBtn.style.display = 'none';
});

// ── Recherche / filtre en direct de la liste des prestations ──
const searchInput      = document.getElementById('searchPrestation');
const prestationsTable = document.getElementById('prestationsTable');
const prestationsCount = document.getElementById('prestationsCount');
const noSearchResults  = document.getElementById('noSearchResults');

searchInput?.addEventListener('input', () => {
    const term = searchInput.value.trim().toLowerCase();
    const rows = prestationsTable.querySelectorAll('tbody tr[data-search]');
    let visibleCount = 0;

    rows.forEach(row => {
        const match = row.dataset.search.includes(term);
        row.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });

    if (noSearchResults) {
        noSearchResults.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }
    if (prestationsCount) {
        prestationsCount.textContent = visibleCount;
    }
});

// ── Modal de suppression : choix entre "catégorie seule" et "prestation entière" ──
const deleteModalOverlay    = document.getElementById('deleteModalOverlay');
const deleteModalCategorie  = document.getElementById('deleteModalCategorie');
const deleteModalPrestation = document.getElementById('deleteModalPrestation');
const deleteForm            = document.getElementById('deleteForm');
const deleteIdPrestation    = document.getElementById('deleteIdPrestation');
const deleteIdCategorie     = document.getElementById('deleteIdCategorie');
const deleteScope           = document.getElementById('deleteScope');

document.querySelectorAll('.btn-delete-prest').forEach(btn => {
    btn.addEventListener('click', () => {
        deleteIdPrestation.value = btn.dataset.idPrestation;
        deleteIdCategorie.value  = btn.dataset.idCategorie;
        deleteModalCategorie.textContent  = btn.dataset.categorieName;
        deleteModalPrestation.textContent = btn.dataset.prestationName;
        deleteModalOverlay.classList.add('open');
    });
});

document.getElementById('btnDeleteCategorieSeule')?.addEventListener('click', () => {
    deleteScope.value = 'categorie';
    deleteForm.submit();
});

document.getElementById('btnDeletePrestationEntiere')?.addEventListener('click', () => {
    deleteScope.value = 'prestation';
    deleteForm.submit();
});

document.getElementById('btnCancelDelete')?.addEventListener('click', () => {
    deleteModalOverlay.classList.remove('open');
});

deleteModalOverlay?.addEventListener('click', (e) => {
    if (e.target === deleteModalOverlay) {
        deleteModalOverlay.classList.remove('open');
    }
});
</script>
</body>
</html>