<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();
require_once __DIR__ . '/../../util/file_upload.php';
require_once __DIR__.'/composante/tolbarDto.php';
//on changer le titre
$titre="Gestion des Blog";
$success = $error = '';

// ── CRUD Blog ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $idBlog  = (int) ($_POST['id_blog'] ?? 0);

    if ($action === 'create' || $action === 'edit') {
        $idCat   = (int) ($_POST['id_categorie'] ?? 0);
        $titre   = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $imgPath = trim($_POST['img_actuelle'] ?? '');

        if (!$idCat || !$titre || !$contenu) {
            $error = 'Veuillez remplir tous les champs obligatoires.';
        } else {
            // Upload image couverture si fournie
            if (!empty($_FILES['image_couverture']['name'])) {
                $res = uploadFile($_FILES['image_couverture'], 'blog', 'image');
                if ($res['success']) { $imgPath = $res['path']; }
                else { $error = $res['error']; }
            }

            if (!$error) {
                if ($action === 'create') {
                    $stmt = $pdo->prepare('INSERT INTO BLOG (ID_TYPE_BLOG, TITRE_BLOG, CONTENU, IMAGE_COURVERTURE,STATUS_BLOG) VALUES (?,?,?,?,?)');
                    $stmt->execute([$idCat, $titre, $contenu, $imgPath,"PUBLIER"]);
                    $success = "Article « $titre » publié avec succès.";
                } else {
                    $stmt = $pdo->prepare('UPDATE BLOG SET ID_TYPE_BLOG=?, TITRE_BLOG=?, CONTENU=?, IMAGE_COURVERTURE=?, DATE_MODIFICATION=CURDATE(),STATUS_BLOG=? WHERE ID_BLOG=?');
                    $stmt->execute([$idCat, $titre, $contenu, $imgPath,"PUBLIER", $idBlog]);
                    $success = "Article modifié avec succès.";
                }
            }
        }
    } elseif ($action === 'delete' && $idBlog) {
        $pdo->prepare('DELETE FROM BLOG WHERE ID_BLOG = ?')->execute([$idBlog]);
        $success = "Article supprimé.";
    }
}

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
                                <label for="id_categorie">Catégorie <span class="required">*</span></label>
                                <select name="id_categorie" id="id_categorie" class="dash-select" required>
                                    <option value="">— Choisir —</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= (int)$cat['ID_TYPE_BLOG'] ?>"
                                            <?= isset($editArticle) && (int)$editArticle['ID_BLOG'] === (int)$cat['ID_TYPE_BLOG'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['LIB_TYPE_BLOG']) ?>
                                        </option>
                                    <?php endforeach; ?>
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
                                <i class="fas fa-save"></i> <?= $editArticle ? 'Mettre à jour' : 'Publier l\'article' ?>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- LISTE ARTICLES -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-newspaper" style="color:var(--primary-green);margin-right:8px;"></i> Articles publiés</h3>
                        <span class="badge badge-confirm"><?= count($articles) ?></span>
                    </div>
                    <div class="dash-card-body">
                        <?php if (empty($articles)): ?>
                            <div class="empty-state"><i class="fas fa-newspaper"></i><p>Aucun article publié.</p></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="dash-table">
                                <thead><tr><th>Titre</th><th>Catégorie</th><th>Publié le</th><th>Actions</th></tr></thead>
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
                                        <td><?= date('d/m/Y', strtotime($art['DATE_PUBLICATION'])) ?></td>
                                        <td style="white-space:nowrap;">
                                            <a href="?edit=<?= (int)$art['ID_BLOG'] ?>" class="btn-dash btn-dash-outline btn-dash-sm"><i class="fas fa-edit"></i></a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cet article ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_blog" value="<?= (int)$art['ID_BLOG'] ?>">
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

// Aperçu image
document.getElementById('image_couverture')?.addEventListener('change', function() {
    const prev = document.getElementById('imgPreview');
    if (this.files[0]) {
        const url = URL.createObjectURL(this.files[0]);
        prev.innerHTML = `<img src="${url}" style="width:100%;max-height:120px;object-fit:cover;border-radius:8px;">`;
    }
});
</script>

<!-- Toastify JS -->
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
window.addEventListener('DOMContentLoaded', () => {
    const errorMsg = <?php echo json_encode($error ?? '', JSON_UNESCAPED_UNICODE); ?>;
    const successMsg = <?php echo json_encode($success ?? '', JSON_UNESCAPED_UNICODE); ?>;
    
    if (errorMsg) {
        Toastify({
            text: errorMsg,
            duration: 6000,
            gravity: "top",
            position: "right",
            close: true,
            style: {
                background: "linear-gradient(135deg, #d93d3d, #a82c2c)",
                borderRadius: "6px",
                fontFamily: "system-ui, -apple-system, sans-serif",
                fontWeight: "600",
                boxShadow: "0 10px 30px rgba(0, 0, 0, 0.25)"
            }
        }).showToast();
    }
    
    if (successMsg) {
        Toastify({
            text: successMsg,
            duration: 6000,
            gravity: "top",
            position: "right",
            close: true,
            style: {
                background: "linear-gradient(135deg, #377d49, #2a5c3a)",
                borderRadius: "6px",
                fontFamily: "system-ui, -apple-system, sans-serif",
                fontWeight: "600",
                boxShadow: "0 10px 30px rgba(0, 0, 0, 0.25)"
            }
        }).showToast();
    }
});
</script>

</body>
</html>
