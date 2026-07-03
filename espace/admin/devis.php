<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();
require_once __DIR__ . '/../../config/database.php';

$adminEmail = $_SESSION['admin_email'] ?? 'Admin';
$pdo        = getPDO();
$success = $error = '';

// ── Traitement actions ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $idDevis = (int) ($_POST['id_devis'] ?? 0);

    if ($action === 'valider' && $idDevis) {
        // Vérifier qu'une réservation existe (on crée un contrat symbolique)
        $stmt = $pdo->prepare('SELECT * FROM DEVIS WHERE ID_DEVIS = ?');
        $stmt->execute([$idDevis]);
        $devis = $stmt->fetch();
        $success = "Devis #$idDevis marqué comme validé. Un contrat devra être associé à une réservation.";
    } elseif ($action === 'supprimer' && $idDevis) {
        $stmt = $pdo->prepare('DELETE FROM PIECES_JOINTES WHERE ID_DEVIS = ?');
        $stmt->execute([$idDevis]);
        $stmt2 = $pdo->prepare('DELETE FROM DEVIS WHERE ID_DEVIS = ?');
        $stmt2->execute([$idDevis]);
        $success = "Devis #$idDevis supprimé.";
    }
}

// ── Liste des devis ───────────────────────────────────────────
$devis = $pdo->query(
    'SELECT d.*, p.LIB_PRESTATION,
            (SELECT COUNT(*) FROM PIECES_JOINTES pj WHERE pj.ID_DEVIS = d.ID_DEVIS) AS nb_pj
     FROM DEVIS d
     JOIN PRESTATIONS p ON d.ID_PRESTATION = p.ID_PRESTATION
     ORDER BY d.DATE_SOUHAITE DESC'
)->fetchAll();
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
</head>
<body>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/composante/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div style="display:flex;align-items:center;gap:14px;">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <span class="topbar-title">Gestion des Devis</span>
            </div>
            <div class="topbar-user">
                <div class="topbar-user-info">
                    <span class="topbar-user-name"><?= htmlspecialchars($adminEmail) ?></span>
                    <span class="topbar-user-role" style="color:var(--primary-red);">Administrateur</span>
                </div>
                <div class="topbar-avatar admin-avatar"><i class="fas fa-shield-alt" style="font-size:.85rem;"></i></div>
            </div>
        </div>

        <div class="dashboard-content">
            <nav class="dash-breadcrumb">
                <a href="home.php">Dashboard</a>
                <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Devis</span>
            </nav>

            <?php if ($success): ?>
                <div class="dash-alert dash-alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="dash-alert dash-alert-error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-file-alt" style="color:var(--primary-green);margin-right:8px;"></i> Demandes de devis reçues</h3>
                    <span class="badge badge-waiting"><?= count($devis) ?> total</span>
                </div>
                <div class="dash-card-body">
                    <?php if (empty($devis)): ?>
                        <div class="empty-state"><i class="fas fa-file-times"></i><p>Aucune demande de devis pour le moment.</p></div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr><th>#</th><th>Client</th><th>Téléphone</th><th>Prestation</th><th>Budget</th><th>Date souhaitée</th><th>PJ</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($devis as $d): ?>
                                <tr>
                                    <td>#<?= (int)$d['ID_DEVIS'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($d['PRENOMS'] . ' ' . $d['NOM']) ?></strong>
                                        <?php if ($d['ENTREPRISE'] && $d['ENTREPRISE'] !== '—'): ?>
                                            <br><small style="color:#888;"><?= htmlspecialchars($d['ENTREPRISE']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($d['TELEPHONE']) ?></td>
                                    <td><?= htmlspecialchars($d['LIB_PRESTATION']) ?></td>
                                    <td><strong><?= htmlspecialchars($d['BUGET_ESTIMATIF']) ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($d['DATE_SOUHAITE'])) ?></td>
                                    <td>
                                        <?php if ((int)$d['nb_pj'] > 0): ?>
                                            <span class="badge badge-confirm"><i class="fas fa-paperclip"></i> <?= (int)$d['nb_pj'] ?></span>
                                        <?php else: ?>
                                            <span style="color:#ccc;font-size:0.8rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-dash btn-dash-sm btn-dash-outline" onclick="showDetail(<?= $d['ID_DEVIS'] ?>, '<?= addslashes(htmlspecialchars($d['DESCRIPTION'])) ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce devis ?');">
                                            <input type="hidden" name="action" value="supprimer">
                                            <input type="hidden" name="id_devis" value="<?= (int)$d['ID_DEVIS'] ?>">
                                            <button type="submit" class="btn-dash btn-dash-sm btn-dash-danger"><i class="fas fa-trash"></i></button>
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

<!-- Modal description devis -->
<div id="devisModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:14px;max-width:560px;width:100%;padding:32px;position:relative;">
        <button onclick="document.getElementById('devisModal').style.display='none'" style="position:absolute;top:16px;right:20px;background:none;border:none;font-size:1.5rem;cursor:pointer;color:#aaa;">&times;</button>
        <h3 style="font-family:var(--font-headings);margin-bottom:16px;font-size:1.1rem;">Description du projet</h3>
        <p id="devisDescription" style="font-size:0.9rem;line-height:1.8;color:var(--text-body);"></p>
    </div>
</div>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

function showDetail(id, desc) {
    document.getElementById('devisDescription').textContent = desc;
    document.getElementById('devisModal').style.display = 'flex';
}
document.getElementById('devisModal')?.addEventListener('click', e => {
    if (e.target === document.getElementById('devisModal')) e.target.style.display = 'none';
});
</script>
</body>
</html>
