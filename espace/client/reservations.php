<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();
require_once __DIR__ . '/../../config/database.php';

$clientId     = (int) $_SESSION['client_id'];
$clientNom    = $_SESSION['client_nom']    ?? 'Client';
$clientPrenom = $_SESSION['client_prenom'] ?? '';
$initiales    = getInitiales($clientNom, $clientPrenom);
$pdo          = getPDO();
$success      = '';
$error        = '';

// ── Traitement nouvelle réservation ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_resa') {
    $idPrestation    = (int) ($_POST['id_prestation'] ?? 0);
    $dateResa        = trim($_POST['date_reservation'] ?? '');
    $heureResa       = trim($_POST['heure_reservation'] ?? '');
    $lieuResa        = trim($_POST['lieu_reservation'] ?? '');
    $commentaire     = trim($_POST['commentaire'] ?? '');

    if (!$idPrestation || !$dateResa || !$heureResa || !$lieuResa) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO RESERVATION (ID_PRESTATION, ID_CLIENT, DATE_RESERVATION, HEURE_RESERVATION, LIEU_RESERVATION, COMME_RESERVATION, STATUS_RESERVATION)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$idPrestation, $clientId, $dateResa, $heureResa, $lieuResa, $commentaire, 'EN ATTENTE']);
        $success = 'Votre réservation a été soumise avec succès ! Nous vous contacterons bientôt.';
    }
}

// ── Liste des prestations disponibles ────────────────────────
$prestations = $pdo->query('SELECT ID_PRESTATION, LIB_PRESTATION FROM PRESTATIONS ORDER BY LIB_PRESTATION')->fetchAll();

// ── Toutes mes réservations ───────────────────────────────────
$filter = $_GET['statut'] ?? 'TOUS';
$sql = 'SELECT r.*, p.LIB_PRESTATION FROM RESERVATION r
        JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
        WHERE r.ID_CLIENT = ?';
$params = [$clientId];
if ($filter !== 'TOUS') {
    $sql    .= ' AND r.STATUS_RESERVATION = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY r.DATE_RESERVATION DESC';
$stmtResas = $pdo->prepare($sql);
$stmtResas->execute($params);
$reservations = $stmtResas->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations | NY TIA SARY</title>
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
                <span class="topbar-title">Mes Réservations</span>
            </div>
            <div class="topbar-user">
                <div class="topbar-user-info">
                    <span class="topbar-user-name"><?= htmlspecialchars($clientPrenom . ' ' . $clientNom) ?></span>
                    <span class="topbar-user-role">Client</span>
                </div>
                <div class="topbar-avatar"><?= htmlspecialchars($initiales) ?></div>
            </div>
        </div>

        <div class="dashboard-content">
            <nav class="dash-breadcrumb">
                <a href="home.php">Dashboard</a> <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Mes Réservations</span>
            </nav>

            <?php if ($success): ?>
                <div class="dash-alert dash-alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="dash-alert dash-alert-error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- FORMULAIRE NOUVELLE RÉSERVATION -->
            <div class="dash-card" style="margin-bottom:28px;">
                <div class="dash-card-header">
                    <h3><i class="fas fa-plus-circle" style="color:var(--primary-green);margin-right:8px;"></i> Nouvelle réservation</h3>
                    <button class="btn-dash btn-dash-outline btn-dash-sm" id="toggleForm">
                        <i class="fas fa-chevron-down" id="toggleIcon"></i> Ouvrir le formulaire
                    </button>
                </div>
                <div class="dash-card-body padded" id="formResa" style="display:none;">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="new_resa">
                        <div class="form-grid-2">
                            <div class="dash-form-group">
                                <label for="id_prestation">Type de prestation <span class="required">*</span></label>
                                <select name="id_prestation" id="id_prestation" class="dash-select" required>
                                    <option value="">— Choisir une prestation —</option>
                                    <?php foreach ($prestations as $p): ?>
                                        <option value="<?= (int) $p['ID_PRESTATION'] ?>">
                                            <?= htmlspecialchars($p['LIB_PRESTATION']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="dash-form-group">
                                <label for="lieu_reservation">Lieu <span class="required">*</span></label>
                                <input type="text" name="lieu_reservation" id="lieu_reservation" class="dash-input" placeholder="Ex: Antananarivo, Salle Ivato..." required>
                            </div>
                            <div class="dash-form-group">
                                <label for="date_reservation">Date souhaitée <span class="required">*</span></label>
                                <input type="date" name="date_reservation" id="date_reservation" class="dash-input"
                                       min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="dash-form-group">
                                <label for="heure_reservation">Heure <span class="required">*</span></label>
                                <input type="time" name="heure_reservation" id="heure_reservation" class="dash-input" required>
                            </div>
                        </div>
                        <div class="dash-form-group">
                            <label for="commentaire">Commentaires / Précisions</label>
                            <textarea name="commentaire" id="commentaire" class="dash-textarea" placeholder="Décrivez votre projet, le nombre de personnes, vos attentes..."></textarea>
                        </div>
                        <button type="submit" class="btn-dash btn-dash-primary">
                            <i class="fas fa-paper-plane"></i> Envoyer la demande
                        </button>
                    </form>
                </div>
            </div>

            <!-- LISTE DES RÉSERVATIONS -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-list" style="color:var(--primary-green);margin-right:8px;"></i> Historique</h3>
                    <!-- Filtres statut -->
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php foreach (['TOUS', 'EN ATTENTE', 'CONFIRMÉ', 'ANNULÉ', 'TERMINÉ'] as $s): ?>
                            <a href="?statut=<?= urlencode($s) ?>"
                               class="btn-dash btn-dash-sm <?= $filter === $s ? 'btn-dash-primary' : 'btn-dash-outline' ?>">
                               <?= htmlspecialchars($s) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="dash-card-body">
                    <?php if (empty($reservations)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>Aucune réservation trouvée pour ce filtre.</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Prestation</th>
                                    <th>Date</th>
                                    <th>Heure</th>
                                    <th>Lieu</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($reservations as $r): ?>
                                <?php
                                $badgeClass = match(strtoupper($r['STATUS_RESERVATION'])) {
                                    'CONFIRMÉ', 'CONFIRME' => 'badge-confirm',
                                    'ANNULÉ', 'ANNULE'     => 'badge-cancel',
                                    'TERMINÉ', 'TERMINE'   => 'badge-done',
                                    default                 => 'badge-waiting',
                                };
                                ?>
                                <tr>
                                    <td>#<?= (int) $r['ID_RESERVATION'] ?></td>
                                    <td><strong><?= htmlspecialchars($r['LIB_PRESTATION']) ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($r['DATE_RESERVATION'])) ?></td>
                                    <td><?= substr($r['HEURE_RESERVATION'], 0, 5) ?></td>
                                    <td><?= htmlspecialchars($r['LIEU_RESERVATION']) ?></td>
                                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($r['STATUS_RESERVATION']) ?></span></td>
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
// Sidebar toggle
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// Toggle formulaire
const btn    = document.getElementById('toggleForm');
const form   = document.getElementById('formResa');
const icon   = document.getElementById('toggleIcon');
btn?.addEventListener('click', () => {
    const open = form.style.display === 'block';
    form.style.display = open ? 'none' : 'block';
    icon.className = open ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
    btn.querySelector('span') && (btn.textContent = '');
});
</script>
</body>
</html>
