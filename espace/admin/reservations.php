<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();
require_once __DIR__ . '/../../config/database.php';

$adminEmail = $_SESSION['admin_email'] ?? 'Admin';
$adminId    = (int) ($_SESSION['admin_id'] ?? 0);
$pdo        = getPDO();
// Fetch admin photo from CLIENT table (admin is stored as a client with role ADMIN)
$stmtPhoto = $pdo->prepare('SELECT PHOTO_CLIENT FROM CLIENT WHERE ID_AUTH = ?');
$stmtPhoto->execute([$adminId]);
$photoAdmin = $stmtPhoto->fetchColumn() ?: 'assets/images/avatar.png';
$isDefaultPhoto = ($photoAdmin === 'assets/images/avatar.png');
$success = $error = '';

// ── Changement de statut via AJAX ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_statut'])) {
    header('Content-Type: application/json');
    $id     = (int) ($_POST['id'] ?? 0);
    $statut = trim($_POST['statut'] ?? '');
    $allowed = ['EN ATTENTE', 'CONFIRMÉ', 'ANNULÉ', 'TERMINÉ'];
    if ($id && in_array($statut, $allowed, true)) {
        $stmt = $pdo->prepare('UPDATE RESERVATION SET STATUS_RESERVATION = ? WHERE ID_RESERVATION = ?');
        $stmt->execute([$statut, $id]);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit();
}

// ── Filtres ───────────────────────────────────────────────────
$filterStatut = $_GET['statut'] ?? 'TOUS';
$search       = trim($_GET['q'] ?? '');

$sql = 'SELECT r.*, p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT
        FROM RESERVATION r
        JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
        JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
        WHERE 1=1';
$params = [];
if ($filterStatut !== 'TOUS') { 
    $sql .= ' AND r.STATUS_RESERVATION = ?'; 
    $params[] = $filterStatut; 
}
if ($search) { 
    $sql .= ' AND (c.NOM_CLIENT LIKE ? OR c.PRENOM_CLIENT LIKE ? OR p.LIB_PRESTATION LIKE ?)'; 
    $like = "%$search%"; 
    $params = array_merge($params, [$like, $like, $like]); 
}
$sql .= ' ORDER BY r.DATE_RESERVATION DESC, r.HEURE_RESERVATION DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservations | Admin NY TIA SARY</title>
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
                <span class="topbar-title">Gestion des Réservations</span>
            </div>
<div class="topbar-user">
                <div class="topbar-user-info">
                    <span class="topbar-user-name"><?= htmlspecialchars($adminEmail) ?></span>
                    <span class="topbar-user-role" style="color:var(--primary-red);">Administrateur</span>
                </div>
                <?php if ($isDefaultPhoto): ?>
                    <div class="topbar-avatar admin-avatar"><i class="fas fa-shield-alt" style="font-size:.85rem;"></i></div>
                <?php else: ?>
                    <img src="../../<?= htmlspecialchars($photoAdmin) ?>" alt="Avatar" class="topbar-avatar" style="object-fit: cover;">
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-content">
            <nav class="dash-breadcrumb">
                <a href="home.php">Dashboard</a>
                <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Réservations</span>
            </nav>

            <div id="statusMsg"></div>

            <!-- FILTRES & RECHERCHE -->
            <div class="dash-action-row">
                <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    <div class="dash-search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Client, prestation...">
                    </div>
                    <select name="statut" class="dash-select" style="width:auto;min-width:160px;" onchange="this.form.submit()">
                        <?php foreach (['TOUS', 'EN ATTENTE', 'CONFIRMÉ', 'ANNULÉ', 'TERMINÉ'] as $s): ?>
                            <option value="<?= $s ?>" <?= $filterStatut === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-dash btn-dash-primary"><i class="fas fa-search"></i> Filtrer</button>
                </form>
                <span style="font-size:0.85rem;color:#888;"><?= count($reservations) ?> résultat(s)</span>
            </div>

            <div class="dash-card">
                <div class="dash-card-body">
                    <?php if (empty($reservations)): ?>
                        <div class="empty-state"><i class="fas fa-calendar-times"></i><p>Aucune réservation trouvée.</p></div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr><th>#</th><th>Client</th><th>Prestation</th><th>Date</th><th>Heure</th><th>Lieu</th><th>Statut</th><th>Modifier statut</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($reservations as $r): ?>
                                <?php
                                $bc = match(strtoupper($r['STATUS_RESERVATION'])) {
                                    'CONFIRMÉ','CONFIRME' => 'badge-confirm',
                                    'ANNULÉ','ANNULE'     => 'badge-cancel',
                                    'TERMINÉ','TERMINE'   => 'badge-done',
                                    default               => 'badge-waiting',
                                };
                                ?>
                                <tr id="row-<?= (int)$r['ID_RESERVATION'] ?>">
                                    <td>#<?= (int)$r['ID_RESERVATION'] ?></td>
                                    <td><strong><?= htmlspecialchars($r['PRENOM_CLIENT'] . ' ' . $r['NOM_CLIENT']) ?></strong></td>
                                    <td><?= htmlspecialchars($r['LIB_PRESTATION']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['DATE_RESERVATION'])) ?></td>
                                    <td><?= substr($r['HEURE_RESERVATION'], 0, 5) ?></td>
                                    <td><?= htmlspecialchars($r['LIEU_RESERVATION']) ?></td>
                                    <td><span class="badge <?= $bc ?>" id="badge-<?= (int)$r['ID_RESERVATION'] ?>"><?= htmlspecialchars($r['STATUS_RESERVATION']) ?></span></td>
                                    <td>
                                        <select class="dash-select statut-select" style="padding:6px 10px;font-size:0.8rem;width:auto;"
                                                data-id="<?= (int)$r['ID_RESERVATION'] ?>">
                                            <?php foreach (['EN ATTENTE', 'CONFIRMÉ', 'ANNULÉ', 'TERMINÉ'] as $s): ?>
                                                <option value="<?= $s ?>" <?= $r['STATUS_RESERVATION'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                            <?php endforeach; ?>
                                        </select>
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

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// Changement de statut via AJAX
const badgeMap = {
    'EN ATTENTE': 'badge-waiting',
    'CONFIRMÉ':   'badge-confirm',
    'ANNULÉ':     'badge-cancel',
    'TERMINÉ':    'badge-done',
};
document.querySelectorAll('.statut-select').forEach(sel => {
    sel.addEventListener('change', async () => {
        const id     = sel.dataset.id;
        const statut = sel.value;
        const fd = new FormData();
        fd.append('ajax_statut', '1');
        fd.append('id', id);
        fd.append('statut', statut);
        const res  = await fetch('', { method: 'POST', body: fd });
        const data = await res.json();
        const msg  = document.getElementById('statusMsg');
        if (data.ok) {
            const badge = document.getElementById('badge-' + id);
            badge.className = 'badge ' + (badgeMap[statut] || 'badge-waiting');
            badge.textContent = statut;
            msg.innerHTML = '<div class="dash-alert dash-alert-success"><i class="fas fa-check-circle"></i> Statut mis à jour avec succès.</div>';
        } else {
            msg.innerHTML = '<div class="dash-alert dash-alert-error"><i class="fas fa-exclamation-circle"></i> Erreur lors de la mise à jour.</div>';
        }
        setTimeout(() => msg.innerHTML = '', 3000);
    });
});
</script>
</body>
</html>
