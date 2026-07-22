<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();
require_once __DIR__.'/composante/tolbarDto.php';
//on changer le titre
$titre="Gestion des réservations";
$success = $error = '';

// ── Changement de statut via AJAX 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_statut'])) {
    header('Content-Type: application/json');
    $id     = (int) ($_POST['id'] ?? 0);
    $statut = trim($_POST['statut'] ?? '');
    $allowed = ['EN ATTENTE', 'CONFIRMEE', 'ANNULEE', 'TERMINEE'];
    if ($id && in_array($statut, $allowed, true)) {
        $stmt = $pdo->prepare('UPDATE RESERVATION SET STATUS_RESERVATION = ? WHERE ID_RESERVATION = ?');
        $stmt->execute([$statut, $id]);
        
        // Si la réservation est CONFIRMEE, on s'assure qu'un contrat est généré
        if ($statut === 'CONFIRMEE') {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM CONTRAT WHERE ID_RESERVATION = ?');
            $chk->execute([$id]);
            if ((int)$chk->fetchColumn() === 0) {
                $pdo->prepare("INSERT INTO CONTRAT (ID_RESERVATION, STATUS_CONTRAT, DATE_CONTRAT) VALUES (?, 'EN ATTENTE', CURDATE())")
                    ->execute([$id]);
            }
        }
        
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit();
}

// ── Filtres ───────────────────────────────────────────────────
$filterStatut = $_GET['statut'] ?? 'TOUS';
$search       = trim($_GET['q'] ?? '');

$sql = 'SELECT r.*, p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT,
               ct.ID_CONTRAT, 
               SUM(rc.PRIX) AS TOTAL_PRIX, 
               GROUP_CONCAT(cat.LIB_CATEGORIE SEPARATOR \'<br>\') AS LIBS_CATEGORIES
        FROM RESERVATION r
        JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
        JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
        LEFT JOIN CONTRAT ct ON ct.ID_RESERVATION = r.ID_RESERVATION
        LEFT JOIN RESERVATION_CATEGORIE rc ON rc.ID_RESERVATION = r.ID_RESERVATION
        LEFT JOIN CATEGORIE cat ON cat.ID_CATEGORIE = rc.ID_CATEGORIE
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
$sql .= ' GROUP BY r.ID_RESERVATION ORDER BY r.DATE_RESERVATION DESC, r.HEURE_RESERVATION DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();
// ── Marquer la notification comme lue puis nettoyer l'URL ─────
if (isset($_GET['mark_notif']) && (int) $_GET['mark_notif'] > 0) {
    $idNotif      = (int) $_GET['mark_notif'];
    $idResaSelect = (int) ($_GET['id'] ?? 0);

    try {
        $stmtMark = $pdo->prepare('UPDATE notification SET LU_NOTIF = 1 WHERE ID_NOTIF = ?');
        $stmtMark->execute([$idNotif]);
    } catch (PDOException $e) {
        // on ignore silencieusement, la redirection se fait quand même
    }

    header('Location: reservations.php' . ($idResaSelect > 0 ? '?id=' . $idResaSelect : ''));
    exit;
}

$selectedResaId = (int) ($_GET['id'] ?? 0);
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
                <span>Réservations</span>
            </nav>

            <div id="statusMsg"></div>

            <!-- FILTRES & RECHERCHE -->
            <div class="dash-action-row">
                <form method="GET" style="display:flex;gap:15px;flex-wrap:wrap;align-items:center;">
                    <div class="dash-search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Client, prestation...">
                    </div>
                    <select name="statut" class="dash-select" style="width:auto;min-width:160px;" onchange="this.form.submit()">
                        <?php foreach (['TOUS', 'EN ATTENTE', 'CONFIRMEE', 'ANNULEE', 'TERMINEE'] as $s): ?>
                            <option value="<?= $s ?>" <?= $filterStatut === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-dash btn-dash-primary" style="margin-left:10px;"><i class="fas fa-search"></i> Filtrer</button>
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
                                <tr><th>#</th><th>Client</th><th>Prestation & Formule</th><th>Tarif</th><th>Date</th><th>Heure</th><th>Lieu</th><th>Statut</th><th>Contrat</th><th>Modifier statut</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($reservations as $r): ?>
                                <?php
                                $bc = match(strtoupper($r['STATUS_RESERVATION'])) {
                                    'CONFIRMEE' => 'badge-confirm',
                                    'ANNULEE'   => 'badge-cancel',
                                    'TERMINEE'  => 'badge-done',
                                    default     => 'badge-waiting',
                                };
                                ?>
                                <?php $isSelected = $selectedResaId > 0 && (int)$r['ID_RESERVATION'] === $selectedResaId; ?>
<tr id="row-<?= (int)$r['ID_RESERVATION'] ?>"<?= $isSelected ? ' class="row-highlighted"' : '' ?>>
                                    <td>#<?= (int)$r['ID_RESERVATION'] ?></td>
                                    <td><strong><?= htmlspecialchars($r['PRENOM_CLIENT'] . ' ' . $r['NOM_CLIENT']) ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($r['LIB_PRESTATION']) ?></strong><br>
                                        <span style="font-size:0.8rem;color:#aaa;"><?= $r['LIBS_CATEGORIES'] ? $r['LIBS_CATEGORIES'] : 'Formule non spécifiée' ?></span>
                                    </td>
                                    <td>
                                        <?php if ($r['TOTAL_PRIX']): ?>
                                            <span style="color:var(--primary-green);font-weight:600;"><?= number_format((int)$r['TOTAL_PRIX'], 0, ',', ' ') ?> Ar</span>
                                        <?php else: ?>
                                            <span style="color:#777;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($r['DATE_RESERVATION'])) ?></td>
                                    <td><?= substr($r['HEURE_RESERVATION'], 0, 5) ?></td>
                                    <td><?= htmlspecialchars($r['LIEU_RESERVATION']) ?></td>
                                    <td><span class="badge <?= $bc ?>" id="badge-<?= (int)$r['ID_RESERVATION'] ?>"><?= htmlspecialchars($r['STATUS_RESERVATION']) ?></span></td>
                                    <td>
                                        <?php if ($r['ID_CONTRAT']): ?>
                                            <a href="contrats.php" title="Voir le contrat" style="color:var(--primary-green);">
                                                <i class="fas fa-file-signature"></i>
                                            </a>
                                        <?php elseif ($r['STATUS_RESERVATION'] === 'CONFIRMEE'): ?>
                                            <a href="contrats.php?id_resa=<?= (int)$r['ID_RESERVATION'] ?>" title="Créer un contrat" class="btn-dash btn-dash-outline btn-dash-sm" style="font-size:0.75rem;padding:3px 8px;">
                                                <i class="fas fa-plus"></i> Contrat
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#555;font-size:0.8rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select class="dash-select statut-select" style="padding:6px 10px;font-size:0.8rem;width:auto;"
                                                data-id="<?= (int)$r['ID_RESERVATION'] ?>">
                                            <?php foreach (['EN ATTENTE', 'CONFIRMEE', 'ANNULEE', 'TERMINEE'] as $s): ?>
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
    'CONFIRMEE':  'badge-confirm',
    'ANNULEE':    'badge-cancel',
    'TERMINEE':   'badge-done',
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
const selectedRow = document.querySelector('.row-highlighted');
if (selectedRow) {
    selectedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
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
