<?php

declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
require_once __DIR__ . '/../../util/prg_helper.php';
requireAdmin();
require_once __DIR__ . '/../../util/file_upload.php';
require_once __DIR__ . '/../../util/delete_file.php';
require_once __DIR__ . '/composante/tolbarDto.php';
//on changer le titre
$titre = "Gestion des Blog";

// ── TRAITEMENT POST (PRG Pattern) ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $idBlog  = (int) ($_POST['id_blog'] ?? 0);

    if ($action === 'create' || $action === 'edit') {
        $idCat   = (int) ($_POST['id_type_blog'] ?? 0);
        $titre   = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $imgPath = trim($_POST['img_actuelle'] ?? '');
        $status  = $_POST['status'] ?? 'PUBLIER';

        if (!$idCat || !$titre || !$contenu) {
            prg_set_message('error', 'Veuillez remplir tous les champs obligatoires.');
        } else {
            // Upload image couverture si fournie
            if (!empty($_FILES['image_couverture']['name'])) {
                $res = uploadFile($_FILES['image_couverture'], 'blog', 'image');
                if ($res['success']) {
                    $imgPath = $res['path'];
                } else {
                    prg_set_message('error', $res['error']);
                }
            }

            if (!$imgPath || $_POST['img_actuelle'] || empty($_FILES['image_couverture']['name'])) {
                if ($action === 'create') {
                    $stmt = $pdo->prepare('INSERT INTO BLOG (ID_TYPE_BLOG, TITRE_BLOG, CONTENU, IMAGE_COURVERTURE, STATUS_BLOG) VALUES (?,?,?,?,?)');
                    $stmt->execute([$idCat, $titre, $contenu, $imgPath, $status]);
                    prg_set_message('success', "Article « $titre » créé avec succès.");
                } else {
                    $stmt = $pdo->prepare('UPDATE BLOG SET ID_TYPE_BLOG=?, TITRE_BLOG=?, CONTENU=?, IMAGE_COURVERTURE=?, DATE_MODIFICATION=CURDATE(), STATUS_BLOG=? WHERE ID_BLOG=?');
                    $stmt->execute([$idCat, $titre, $contenu, $imgPath, $status, $idBlog]);
                    prg_set_message('success', "Article modifié avec succès.");
                }
            }
        }
        prg_redirect();
    } elseif ($action === 'publish' && $idBlog) {
        $pdo->prepare('UPDATE BLOG SET STATUS_BLOG = "PUBLIER" WHERE ID_BLOG = ?')->execute([$idBlog]);
        prg_set_message('success', "Article publié avec succès.");
        prg_redirect();
    } elseif ($action === 'draft' && $idBlog) {
        $pdo->prepare('UPDATE BLOG SET STATUS_BLOG = "BROUILLON" WHERE ID_BLOG = ?')->execute([$idBlog]);
        prg_set_message('success', "Article mis en brouillon avec succès.");
        prg_redirect();
    } elseif ($action === 'delete' && $idBlog) {
        // Supprimer l'image de couverture du serveur avant de supprimer en base
        $stmtImg = $pdo->prepare('SELECT IMAGE_COURVERTURE FROM BLOG WHERE ID_BLOG = ?');
        $stmtImg->execute([$idBlog]);
        $imgToDelete = $stmtImg->fetchColumn();
        if ($imgToDelete) {
            deleteFile($imgToDelete);
        }
        $pdo->prepare('DELETE FROM BLOG WHERE ID_BLOG = ?')->execute([$idBlog]);
        prg_set_message('success', "Article supprimé.");
        prg_redirect();
    }
}

// ── Récupérer les messages PRG pour affichage ──────────────────
$prgMessages = prg_get_messages();

// ── Données ───────────────────────────────────────────────────
$articles   = $pdo->query('SELECT b.*, t.LIB_TYPE_BLOG FROM BLOG b LEFT JOIN TYPE_BLOG t ON b.ID_TYPE_BLOG  = t.ID_TYPE_BLOG ORDER BY b.DATE_PUBLICATION DESC')->fetchAll();
$categories = $pdo->query('SELECT ID_TYPE_BLOG, LIB_TYPE_BLOG FROM TYPE_BLOG ORDER BY LIB_TYPE_BLOG')->fetchAll();

// Article à éditer ?
$editArticle = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM BLOG WHERE ID_BLOG = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editArticle = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog | Admin NY TIA SARY</title>
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
                    <span>Blog</span>
                </nav>


                <div style="display:grid;grid-template-columns:1fr 1.8fr;gap:28px;align-items:start;">

                    <!-- FORMULAIRE -->
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3><i class="fas fa-<?= $editArticle ? 'edit' : 'plus-circle' ?>" style="color:var(--primary-green);margin-right:8px;"></i>
                                <?= $editArticle ? 'Modifier l\'article' : 'Nouvel article' ?>
                            </h3>
                            <?php if ($editArticle): ?>
                                <a href="blog.php" class="btn-dash btn-dash-outline btn-dash-sm"><i class="fas fa-times"></i> Annuler</a>
                            <?php endif; ?>
                        </div>
                        <div class="dash-card-body padded">
                            <form method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="<?= $editArticle ? 'edit' : 'create' ?>">
                                <?php if ($editArticle): ?>
                                    <input type="hidden" name="id_blog" value="<?= (int)$editArticle['ID_BLOG'] ?>">
                                    <input type="hidden" name="img_actuelle" value="<?= htmlspecialchars($editArticle['IMAGE_COURVERTURE']) ?>">
                                <?php endif; ?>

                                <div class="dash-form-group">
                                    <label for="titre">Titre <span class="required">*</span></label>
                                    <input type="text" name="titre" id="titre" class="dash-input" required
                                        value="<?= htmlspecialchars($editArticle['TITRE_BLOG'] ?? '') ?>">
                                </div>
                                <div class="dash-form-group">
                                    <label for="id_type_blog">Type de blog <span class="required">*</span></label>
                                    <select name="id_type_blog" id="id_type_blog" class="dash-select" required>
                                        <option value="">— Choisir —</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= (int)$cat['ID_TYPE_BLOG'] ?>"
                                                <?= ($editArticle && (int)$editArticle['ID_TYPE_BLOG'] === (int)$cat['ID_TYPE_BLOG'] ? 'selected' : '') ?>>
                                                <?= htmlspecialchars($cat['LIB_TYPE_BLOG']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="dash-form-group">
                                    <label for="status">Statut</label>
                                    <select name="status" id="status" class="dash-select" <?= !$editArticle ? 'required' : '' ?>>
                                        <option value="BROUILLON" <?= (!$editArticle || $editArticle['STATUS_BLOG'] === 'BROUILLON') ? 'selected' : '' ?>>Brouillon</option>
                                        <option value="PUBLIER" <?= ($editArticle && $editArticle['STATUS_BLOG'] === 'PUBLIER') ? 'selected' : '' ?>>Publié</option>
                                    </select>
                                </div>
                                <div class="dash-form-group">
                                    <label for="image_couverture">Image de couverture</label>
                                    <?php if ($editArticle && $editArticle['IMAGE_COURVERTURE']): ?>
                                        <img src="../../<?= htmlspecialchars($editArticle['IMAGE_COURVERTURE']) ?>" style="width:100%;border-radius:8px;margin-bottom:10px;object-fit:cover;max-height:120px;">
                                    <?php endif; ?>
                                    <label class="upload-zone" for="image_couverture">
                                        <i class="fas fa-image"></i>
                                        <p>Cliquez pour choisir une image (JPG, PNG, WebP)</p>
                                        <input type="file" name="image_couverture" id="image_couverture" accept="image/*">
                                    </label>
                                    <div id="imgPreview" style="margin-top:8px;"></div>
                                </div>
                                <div class="dash-form-group">
                                    <label for="contenu">Contenu <span class="required">*</span></label>
                                    <textarea name="contenu" id="contenu" class="dash-textarea" style="min-height:160px;" required><?= htmlspecialchars($editArticle['CONTENU'] ?? '') ?></textarea>
                                </div>
                                <button type="submit" class="btn-dash btn-dash-primary" style="width:100%;justify-content:center;">
                                    <i class="fas fa-save"></i> <?= $editArticle ? 'Mettre à jour' : 'Créer l\'article' ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- LISTE ARTICLES -->
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3><i class="fas fa-newspaper" style="color:var(--primary-green);margin-right:8px;"></i> Articles</h3>
                            <span class="badge badge-confirm"><?= count($articles) ?></span>
                        </div>
                        <div class="dash-card-body">
                            <?php if (empty($articles)): ?>
                                <div class="empty-state"><i class="fas fa-newspaper"></i>
                                    <p>Aucun article.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="dash-table">
                                        <thead>
                                            <tr>
                                                <th>Titre</th>
                                                <th>Type</th>
                                                <th>Statut</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($articles as $art): ?>
                                                <tr>
                                                    <td>
                                                        <div style="display:flex;align-items:center;gap:10px;">
                                                            <?php if ($art['IMAGE_COURVERTURE']): ?>
                                                                <img src="../../<?= htmlspecialchars($art['IMAGE_COURVERTURE']) ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover;flex-shrink:0;">
                                                            <?php else: ?>
                                                                <div style="width:40px;height:40px;background:#f0f0ec;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-image" style="color:#ccc;"></i></div>
                                                            <?php endif; ?>
                                                            <strong style="font-size:0.88rem;"><?= htmlspecialchars($art['TITRE_BLOG']) ?></strong>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($art['LIB_TYPE_BLOG'] ?? '—') ?></td>
                                                    <td>
                                                        <?php if ($art['STATUS_BLOG'] === 'PUBLIER'): ?>
                                                            <span class="badge badge-confirm">Publié</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-waiting">Brouillon</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= date('d/m/Y', strtotime($art['DATE_PUBLICATION'])) ?></td>
                                                    <td style="white-space:nowrap;">
                                                        <a href="?edit=<?= (int)$art['ID_BLOG'] ?>" class="btn-dash btn-dash-outline btn-dash-sm"><i class="fas fa-edit"></i></a>
                                                        <?php if ($art['STATUS_BLOG'] === 'PUBLIER'): ?>
                                                            <form method="POST" style="display:inline;" id="draftBlogForm-<?= (int)$art['ID_BLOG'] ?>">
                                                                <input type="hidden" name="action" value="draft">
                                                                <input type="hidden" name="id_blog" value="<?= (int)$art['ID_BLOG'] ?>">
                                                                <button type="button" class="btn-dash btn-dash-warning btn-dash-sm confirm-delete-btn" title="Mettre en brouillon"
                                                                    data-form-id="draftBlogForm-<?= (int)$art['ID_BLOG'] ?>"
                                                                    data-message="Mettre cet article en brouillon ?">
                                                                    <i class="fas fa-file-alt"></i>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="POST" style="display:inline;" id="publishBlogForm-<?= (int)$art['ID_BLOG'] ?>">
                                                                <input type="hidden" name="action" value="publish">
                                                                <input type="hidden" name="id_blog" value="<?= (int)$art['ID_BLOG'] ?>">
                                                                <button type="button" class="btn-dash btn-dash-primary btn-dash-sm confirm-delete-btn" title="Publier"
                                                                    data-form-id="publishBlogForm-<?= (int)$art['ID_BLOG'] ?>"
                                                                    data-message="Publier cet article ?">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <form method="POST" style="display:inline;" id="deleteBlogForm-<?= (int)$art['ID_BLOG'] ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id_blog" value="<?= (int)$art['ID_BLOG'] ?>">
                                                            <button type="button" class="btn-dash btn-dash-danger btn-dash-sm confirm-delete-btn"
                                                                data-form-id="deleteBlogForm-<?= (int)$art['ID_BLOG'] ?>"
                                                                data-message="Supprimer cet article ?">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
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
    <!-- ── MODAL CONFIRMATION ─────────────────────────────────────────── -->
    <div class="dash-modal" id="confirmDeleteModal">
        <div class="dash-modal-content">
            <button class="dash-modal-close" id="confirmDeleteModalClose">&times;</button>
            <h3>Confirmation</h3>
            <p id="confirmDeleteMessage">Êtes-vous sûr ?</p>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" class="btn-dash btn-dash-danger" id="confirmDeleteYes">Oui, confirmer</button>
                <button type="button" class="btn-dash btn-dash-outline" id="confirmDeleteCancel">Annuler</button>
            </div>
        </div>
    </div>
    <script>
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        toggle?.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        });
        overlay?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });

        // ── Modal générique de confirmation ──
        let formToConfirmDelete = null;
        const confirmDeleteModal = document.getElementById('confirmDeleteModal');
        const confirmDeleteMessage = document.getElementById('confirmDeleteMessage');
        const confirmDeleteClose = document.getElementById('confirmDeleteModalClose');
        const confirmDeleteCancel = document.getElementById('confirmDeleteCancel');
        const confirmDeleteYes = document.getElementById('confirmDeleteYes');

        document.querySelectorAll('.confirm-delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                formToConfirmDelete = document.getElementById(btn.dataset.formId);
                confirmDeleteMessage.textContent = btn.dataset.message || 'Êtes-vous sûr ?';
                confirmDeleteModal.classList.add('open');
            });
        });

        function closeConfirmDeleteModal() {
            confirmDeleteModal.classList.remove('open');
            formToConfirmDelete = null;
        }

        confirmDeleteClose.addEventListener('click', closeConfirmDeleteModal);
        confirmDeleteCancel.addEventListener('click', closeConfirmDeleteModal);
        confirmDeleteModal.addEventListener('click', (e) => {
            if (e.target === confirmDeleteModal) closeConfirmDeleteModal();
        });

        confirmDeleteYes.addEventListener('click', () => {
            if (formToConfirmDelete) formToConfirmDelete.submit();
        });


        // Aperçu image
        document.getElementById('image_couverture')?.addEventListener('change', function() {
            const prev = document.getElementById('imgPreview');
            if (this.files[0]) {
                const url = URL.createObjectURL(this.files[0]);
                prev.innerHTML = `<img src="${url}" style="width:100%;max-height:120px;object-fit:cover;border-radius:8px;">`;
            }
        });
    </script>

    <!-- Toastify pour messages PRG -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <?php if (!empty($prgMessages)): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                <?= prg_render_toasts($prgMessages) ?>
                // Nettoyer l'URL
                const url = new URL(window.location);
                url.searchParams.delete('edit');
                window.history.replaceState({}, '', url);
            });
        </script>
    <?php endif; ?>

</body>

</html>