<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
require_once __DIR__ . '/../../util/prg_helper.php';
requireAdmin();

require_once __DIR__.'/composante/tolbarDto.php';
$titre = "Témoignages clients";

// ── TRAITEMENT POST (PRG Pattern) ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $idTemo = (int) ($_POST['id_temoignage'] ?? 0);
    if ($idTemo) {
        $pdo->prepare('DELETE FROM TEMOIGNAGE WHERE ID_TEMOIGNAGE = ?')->execute([$idTemo]);
        prg_set_message('success', 'Témoignage supprimé.');
    } else {
        prg_set_message('error', 'Témoignage invalide.');
    }
    prg_redirect();
}

// ── Récupérer les messages PRG pour affichage ──────────────────
$prgMessages = prg_get_messages();

// ── Liste des témoignages ─────────────────────────────────────
$temoignages = $pdo->query(
    'SELECT t.*, r.DATE_RESERVATION, p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT, c.PHOTO_CLIENT
     FROM TEMOIGNAGE t
     JOIN RESERVATION r ON t.ID_RESERVATION = r.ID_RESERVATION
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
     ORDER BY t.ID_TEMOIGNAGE DESC'
)->fetchAll();

// Stats
$notesMoy = count($temoignages) > 0
    ? round(array_sum(array_column($temoignages, 'NOTE')) / count($temoignages), 1)
    : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Témoignages | Admin NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .temo-card {
            background: rgba(255, 255, 255, 1);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 20px 22px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: border-color .2s;
        }
        .temo-card:hover { border-color: rgba(255,255,255,0.18); }
        .temo-header { display:flex; align-items:center; gap:14px; }
        .temo-avatar { width:46px; height:46px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,.15); flex-shrink:0; }
        .temo-stars { color:#f5c518; letter-spacing:2px; font-size:1rem; }
        .temo-msg { font-size:0.92rem; color:black; line-height:1.6; font-style:italic; }
        .temo-meta { font-size:0.78rem; color:#888; }
        .star-bar { display:flex; gap:4px; align-items:center; }
        .star-full { color:#f5c518; }
        .star-empty { color:#444; }
    </style>

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
                <span>Témoignages</span>
            </nav>

            

            <!-- Stats rapides -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px;">
                <div class="dash-stat-card">
                    <div class="stat-icon green"><i class="fas fa-star"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= count($temoignages) ?></div>
                        <div class="stat-label">Avis reçus</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="stat-icon green"><i class="fas fa-trophy"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $notesMoy ?><span style="font-size:0.8rem;color:#aaa;"> / 5</span></div>
                        <div class="stat-label">Note moyenne</div>
                    </div>
                </div>
            </div>

            <!-- LISTE DES TEMOIGNAGES -->
            <?php if (empty($temoignages)): ?>
                <div class="dash-card"><div class="dash-card-body"><div class="empty-state">
                    <i class="fas fa-comment-slash"></i>
                    <p>Aucun témoignage pour l'instant.</p>
                </div></div></div>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:18px;">
                <?php foreach ($temoignages as $t): ?>
                    <div class="temo-card">
                        <div class="temo-header">
                            <img src="../../<?= htmlspecialchars($t['PHOTO_CLIENT']) ?>"
                                 alt="<?= htmlspecialchars($t['PRENOM_CLIENT']) ?>"
                                 class="temo-avatar"
                                 onerror="this.src='../../assets/images/avatar.png'">
                            <div style="flex:1;">
                                <div style="font-weight:700;font-size:0.95rem;"><?= htmlspecialchars($t['PRENOM_CLIENT'].' '.$t['NOM_CLIENT']) ?></div>
                                <div class="temo-meta"><?= htmlspecialchars($t['LIB_PRESTATION']) ?> — <?= date('d/m/Y', strtotime($t['DATE_RESERVATION'])) ?></div>
                            </div>
                            <div class="star-bar">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?= $i <= (int)$t['NOTE'] ? 'star-full' : 'star-empty' ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <blockquote class="temo-msg">"<?= htmlspecialchars($t['MESS_RESERVATION']) ?>"</blockquote>
                        <div style="display:flex;justify-content:flex-end;">
                            <form method="POST" onsubmit="return confirm('Supprimer ce témoignage ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_temoignage" value="<?= (int)$t['ID_TEMOIGNAGE'] ?>">
                                <button class="btn-dash btn-dash-danger btn-dash-sm"><i class="fas fa-trash"></i> Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
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