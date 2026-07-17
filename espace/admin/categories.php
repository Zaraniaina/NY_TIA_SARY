<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();

require_once __DIR__.'/composante/tolbarDto.php';
$titre = "Gestion des catégories";
$success = $error = '';

// ── CRUD Catégories ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $idCat     = (int) ($_POST['id_categorie'] ?? 0);
    $idPrest   = (int) ($_POST['id_prestation'] ?? 0);
    $libCat    = trim($_POST['lib_categorie'] ?? '');
    $tarif     = (int) ($_POST['tarif_categorie'] ?? 0);

    if ($action === 'create') {
        if (!$libCat || !$idPrest) {
            $error = 'Le nom et la prestation sont requis.';
        } elseif ($tarif < 0) {
            $error = 'Le tarif doit être positif ou nul.';
        } else {
            $pdo->prepare('INSERT INTO CATEGORIE (ID_PRESTATION, LIB_CATEGORIE, TARIF_CATEGORIE) VALUES (?, ?, ?)')
                ->execute([$idPrest, $libCat, $tarif]);
            $success = "Catégorie « $libCat » ajoutée.";
        }
    } elseif ($action === 'edit' && $idCat) {
        if (!$libCat || !$idPrest) {
            $error = 'Le nom et la prestation sont requis.';
        } elseif ($tarif < 0) {
            $error = 'Le tarif doit être positif ou nul.';
        } else {
            $pdo->prepare('UPDATE CATEGORIE SET ID_PRESTATION=?, LIB_CATEGORIE=?, TARIF_CATEGORIE=? WHERE ID_CATEGORIE=?')
                ->execute([$idPrest, $libCat, $tarif, $idCat]);
            $success = "Catégorie mise à jour.";
        }
    } elseif ($action === 'delete' && $idCat) {
        $pdo->prepare('DELETE FROM CATEGORIE WHERE ID_CATEGORIE = ?')->execute([$idCat]);
        $success = "Catégorie supprimée.";
    }
}

// ── Données ───────────────────────────────────────────────────
$prestations = $pdo->query('SELECT ID_PRESTATION, LIB_PRESTATION FROM PRESTATIONS ORDER BY LIB_PRESTATION')->fetchAll();

// Catégories avec nom de prestation
$categories = $pdo->query(
    'SELECT c.*, p.LIB_PRESTATION
     FROM CATEGORIE c
     JOIN PRESTATIONS p ON c.ID_PRESTATION = p.ID_PRESTATION
     ORDER BY p.LIB_PRESTATION, c.LIB_CATEGORIE'
)->fetchAll();

// Grouper par prestation pour l'affichage
$grouped = [];
foreach ($categories as $cat) {
    $grouped[$cat['LIB_PRESTATION']][] = $cat;
}

$editCat = null;
if (isset($_GET['edit'])) {
    $se = $pdo->prepare('SELECT * FROM CATEGORIE WHERE ID_CATEGORIE = ?');
    $se->execute([(int)$_GET['edit']]);
    $editCat = $se->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories | Admin NY TIA SARY</title>
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
                <a href="prestations.php">Prestations</a>
                <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Catégories</span>
            </nav>

            
            

            <div style="display:grid;grid-template-columns:1fr 2fr;gap:28px;align-items:start;">

                <!-- FORMULAIRE -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3>
                            <i class="fas fa-<?= $editCat ? 'edit' : 'plus' ?>" style="color:var(--primary-green);margin-right:8px;"></i>
                            <?= $editCat ? 'Modifier' : 'Ajouter' ?> une catégorie
                        </h3>
                        <?php if ($editCat): ?>
                            <a href="categories.php" class="btn-dash btn-dash-outline btn-dash-sm"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                    <div class="dash-card-body padded">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="<?= $editCat ? 'edit' : 'create' ?>">
                            <?php if ($editCat): ?>
                                <input type="hidden" name="id_categorie" value="<?= (int)$editCat['ID_CATEGORIE'] ?>">
                            <?php endif; ?>

                            <div class="dash-form-group">
                                <label for="id_prestation">Prestation parente <span class="required">*</span></label>
                                <select name="id_prestation" id="id_prestation" class="dash-select" required>
                                    <option value="">— Choisir —</option>
                                    <?php foreach ($prestations as $p): ?>
                                        <option value="<?= (int)$p['ID_PRESTATION'] ?>"
                                            <?= (isset($editCat) && $editCat['ID_PRESTATION'] == $p['ID_PRESTATION']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['LIB_PRESTATION']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="dash-form-group">
                                <label for="lib_categorie">Nom de la catégorie <span class="required">*</span></label>
                                <input type="text" name="lib_categorie" id="lib_categorie" class="dash-input"
                                       placeholder="Ex: Demi-journée, Journée complète..."
                                       value="<?= htmlspecialchars($editCat['LIB_CATEGORIE'] ?? '') ?>" required>
                            </div>

                            <div class="dash-form-group">
                                <label for="tarif_categorie">Tarif (Ar) <span class="required">*</span></label>
                                <input type="number" name="tarif_categorie" id="tarif_categorie" class="dash-input"
                                       placeholder="Ex: 150000" min="0" step="1000"
                                       value="<?= (int)($editCat['TARIF_CATEGORIE'] ?? 0) ?>" required>
                            </div>

                            <button type="submit" class="btn-dash btn-dash-primary" style="width:100%;justify-content:center;">
                                <i class="fas fa-save"></i> <?= $editCat ? 'Mettre à jour' : 'Ajouter la catégorie' ?>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- LISTE GROUPÉE PAR PRESTATION -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-tags" style="color:var(--primary-green);margin-right:8px;"></i> Catégories par prestation</h3>
                        <span class="badge badge-confirm"><?= count($categories) ?></span>
                    </div>
                    <div class="dash-card-body">
                        <?php if (empty($categories)): ?>
                            <div class="empty-state">
                                <i class="fas fa-tags"></i>
                                <p>Aucune catégorie. Commencez par en ajouter une.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($grouped as $prestName => $cats): ?>
                                <div style="margin-bottom:20px;">
                                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid rgba(255,255,255,0.08);">
                                        <div class="stat-icon green" style="width:32px;height:32px;border-radius:8px;font-size:0.8rem;flex-shrink:0;">
                                            <i class="fas fa-camera"></i>
                                        </div>
                                        <strong style="font-size:0.95rem;"><?= htmlspecialchars($prestName) ?></strong>
                                        <span class="badge badge-confirm" style="margin-left:auto;"><?= count($cats) ?> cat.</span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="dash-table" style="margin-bottom:0;">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Catégorie</th>
                                                    <th>Tarif (Ar)</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($cats as $cat): ?>
                                                <tr>
                                                    <td>#<?= (int)$cat['ID_CATEGORIE'] ?></td>
                                                    <td><strong><?= htmlspecialchars($cat['LIB_CATEGORIE']) ?></strong></td>
                                                    <td>
                                                        <span style="font-weight:600;color:var(--primary-green);">
                                                            <?= number_format((int)$cat['TARIF_CATEGORIE'], 0, ',', ' ') ?> Ar
                                                        </span>
                                                    </td>
                                                    <td style="display:flex;gap:6px;">
                                                        <a href="?edit=<?= (int)$cat['ID_CATEGORIE'] ?>" class="btn-dash btn-dash-outline btn-dash-sm">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id_categorie" value="<?= (int)$cat['ID_CATEGORIE'] ?>">
                                                            <button class="btn-dash btn-dash-danger btn-dash-sm"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
