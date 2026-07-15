<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();

require_once __DIR__.'/composante/tolbarDto.php';
//on changer le titre
$titre="Gestion des prestations";
$success = $error = '';

// ── CRUD Prestations ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    $idPrest     = (int) ($_POST['id_prestation'] ?? 0);
    $libPrest    = trim($_POST['lib_prestation'] ?? '');

    if ($action === 'create') {
        if (!$libPrest) { $error = 'Le nom de la prestation est requis.'; }
        else {
            $pdo->prepare('INSERT INTO PRESTATIONS (LIB_PRESTATION) VALUES (?)')->execute([$libPrest]);
            $success = "Prestation « $libPrest » ajoutée.";
        }
    } elseif ($action === 'edit' && $idPrest) {
        if (!$libPrest) { $error = 'Le nom de la prestation est requis.'; }
        else {
            $pdo->prepare('UPDATE PRESTATIONS SET LIB_PRESTATION = ? WHERE ID_PRESTATION = ?')->execute([$libPrest, $idPrest]);
            $success = "Prestation mise à jour.";
        }
    } elseif ($action === 'delete' && $idPrest) {
        // Vérifier qu'aucune réservation n'utilise cette prestation
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM RESERVATION WHERE ID_PRESTATION = ?');
        $stmt->execute([$idPrest]);
        $count = (int) $stmt->fetchColumn();
        if ($count > 0) {
            $error = "Impossible de supprimer : $count réservation(s) utilisent cette prestation.";
        } else {
            $pdo->prepare('DELETE FROM PRESTATIONS WHERE ID_PRESTATION = ?')->execute([$idPrest]);
            $success = "Prestation supprimée.";
        }
    }
}

$prestations = $pdo->query(
    'SELECT p.*, COUNT(r.ID_RESERVATION) AS nb_resas
     FROM PRESTATIONS p
     LEFT JOIN RESERVATION r ON r.ID_PRESTATION = p.ID_PRESTATION
     GROUP BY p.ID_PRESTATION
     ORDER BY p.LIB_PRESTATION'
)->fetchAll();

$editPrest = null;
if (isset($_GET['edit'])) {
    $se = $pdo->prepare('SELECT * FROM PRESTATIONS WHERE ID_PRESTATION = ?');
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
                <span>Prestations</span>
            </nav>

            
            

            <div style="display:grid;grid-template-columns:1fr 2fr;gap:28px;align-items:start;">

                <!-- FORMULAIRE -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-<?= $editPrest ? 'edit' : 'plus' ?>" style="color:var(--primary-green);margin-right:8px;"></i>
                            <?= $editPrest ? 'Modifier' : 'Ajouter' ?>
                        </h3>
                        <?php if ($editPrest): ?>
                            <a href="prestations.php" class="btn-dash btn-dash-outline btn-dash-sm"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                    <div class="dash-card-body padded">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="<?= $editPrest ? 'edit' : 'create' ?>">
                            <?php if ($editPrest): ?>
                                <input type="hidden" name="id_prestation" value="<?= (int)$editPrest['ID_PRESTATION'] ?>">
                            <?php endif; ?>
                            <div class="dash-form-group">
                                <label for="lib_prestation">Nom de la prestation <span class="required">*</span></label>
                                <input type="text" name="lib_prestation" id="lib_prestation" class="dash-input"
                                       placeholder="Ex: Mariage, Corporate, Mode..."
                                       value="<?= htmlspecialchars($editPrest['LIB_PRESTATION'] ?? '') ?>" required>
                            </div>
                            <button type="submit" class="btn-dash btn-dash-primary" style="width:100%;justify-content:center;">
                                <i class="fas fa-save"></i> <?= $editPrest ? 'Mettre à jour' : 'Ajouter la prestation' ?>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- LISTE -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-concierge-bell" style="color:var(--primary-green);margin-right:8px;"></i> Liste des prestations</h3>
                        <span class="badge badge-confirm"><?= count($prestations) ?></span>
                    </div>
                    <div class="dash-card-body">
                        <?php if (empty($prestations)): ?>
                            <div class="empty-state"><i class="fas fa-concierge-bell"></i><p>Aucune prestation.</p></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="dash-table">
                                <thead><tr><th>#</th><th>Prestation</th><th>Réservations</th><th>Actions</th></tr></thead>
                                <tbody>
                                <?php foreach ($prestations as $p): ?>
                                    <tr>
                                        <td>#<?= (int)$p['ID_PRESTATION'] ?></td>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:10px;">
                                                <div class="stat-icon green" style="width:36px;height:36px;border-radius:8px;font-size:0.9rem;flex-shrink:0;">
                                                    <i class="fas fa-camera"></i>
                                                </div>
                                                <strong><?= htmlspecialchars($p['LIB_PRESTATION']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?= (int)$p['nb_resas'] > 0 ? 'badge-confirm' : 'badge-waiting' ?>">
                                                <?= (int)$p['nb_resas'] ?> résa
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?edit=<?= (int)$p['ID_PRESTATION'] ?>" class="btn-dash btn-dash-outline btn-dash-sm"><i class="fas fa-edit"></i></a>
                                            <?php if ((int)$p['nb_resas'] === 0): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette prestation ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_prestation" value="<?= (int)$p['ID_PRESTATION'] ?>">
                                                <button class="btn-dash btn-dash-danger btn-dash-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                            <?php else: ?>
                                                <span title="Utilisée par des réservations" style="color:#ccc;font-size:1rem;margin-left:6px;"><i class="fas fa-lock"></i></span>
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