<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();
require_once __DIR__.'/composante/tolbarDto.php';
//on changer le titre
$titre="Gestion des Clients";
$search     = trim($_GET['q'] ?? '');

$sql = 'SELECT c.*, a.EMAIL_AUTH, a.ROLE_AUTH,
        (SELECT COUNT(*) FROM RESERVATION r WHERE r.ID_CLIENT = c.ID_CLIENT) AS nb_resas
        FROM CLIENT c
        JOIN AUTHENTIFICATION a ON c.ID_AUTH = a.ID_AUTH and a.ROLE_AUTH = "CLIENT"';
$params = [];
if ($search) {
    $sql   .= ' WHERE c.NOM_CLIENT LIKE ? OR c.PRENOM_CLIENT LIKE ? OR a.EMAIL_AUTH LIKE ?';
    $like   = "%$search%";
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY c.NOM_CLIENT';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();

// Détail d'un client
$detailClient = null;
$detailResas  = [];
if (isset($_GET['id'])) {
    $idC = (int) $_GET['id'];
    $sc  = $pdo->prepare('SELECT c.*, a.EMAIL_AUTH, a.ROLE_AUTH FROM CLIENT c JOIN AUTHENTIFICATION a ON c.ID_AUTH = a.ID_AUTH WHERE c.ID_CLIENT = ?');
    $sc->execute([$idC]);
    $detailClient = $sc->fetch();
    if ($detailClient) {
        $sr = $pdo->prepare('SELECT r.*, p.LIB_PRESTATION FROM RESERVATION r JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION WHERE r.ID_CLIENT = ? ORDER BY r.DATE_RESERVATION DESC');
        $sr->execute([$idC]);
        $detailResas = $sr->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients | Admin NY TIA SARY</title>
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
                <span>Clients</span>
            </nav>

            <?php if ($detailClient): ?>
            <!-- DÉTAIL CLIENT -->
            <div style="margin-bottom:20px;">
                <a href="clients.php" class="btn-dash btn-dash-outline btn-dash-sm"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
            </div>
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;align-items:start;">
                <div class="dash-card">
                    <div class="dash-card-header"><h3><i class="fas fa-user" style="color:var(--primary-green);margin-right:8px;"></i> Profil</h3></div>
                    <div class="dash-card-body padded">
                        <div style="text-align:center;margin-bottom:20px;">
                            <img src="../../<?= htmlspecialchars($detailClient['PHOTO_CLIENT']) ?>" alt="Avatar" class="topbar-avatar" style="width:64px;height:64px;object-fit:cover;margin:0 auto 12px;">
                            <strong style="font-family:var(--font-headings);font-size:1.1rem;">
                                <?= htmlspecialchars($detailClient['PRENOM_CLIENT'] . ' ' . $detailClient['NOM_CLIENT']) ?>
                            </strong>
                        </div>
                        <table style="width:100%;font-size:0.85rem;line-height:2.2;">
                            <tr><td data-label="Email" style="color:#888;width:40%;">Email</td><td><?= htmlspecialchars($detailClient['EMAIL_AUTH']) ?></td></tr>
                            <tr><td data-label="Tél." style="color:#888;">Tél.</td><td><?= htmlspecialchars($detailClient['TEL_CLIENT']) ?></td></tr>
                            <tr><td data-label="Type" style="color:#888;">Type</td><td><?= htmlspecialchars($detailClient['TYPE_CLIENT']) ?></td></tr>
                            <tr><td data-label="Rôle" style="color:#888;">Rôle</td><td><?= htmlspecialchars($detailClient['ROLE_AUTH']) ?></td></tr>
                            <tr><td data-label="Réservations" style="color:#888;">Réservations</td><td><?= count($detailResas) ?></td></tr>
                        </table>
                    </div>
                </div>
                <div class="dash-card">
                    <div class="dash-card-header"><h3><i class="fas fa-calendar-alt" style="color:var(--primary-green);margin-right:8px;"></i> Réservations du client</h3></div>
                    <div class="dash-card-body">
                        <?php if (empty($detailResas)): ?>
                            <div class="empty-state"><i class="fas fa-calendar-times"></i><p>Aucune réservation.</p></div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="dash-table">
                                <thead><tr><th>#</th><th>Prestation</th><th>Date</th><th>Lieu</th><th>Statut</th></tr></thead>
                                <tbody>
                                <?php foreach ($detailResas as $r): ?>
                                    <?php $bc = match(strtoupper($r['STATUS_RESERVATION'])) { 'CONFIRMÉ','CONFIRME'=>'badge-confirm','ANNULÉ','ANNULE'=>'badge-cancel','TERMINÉ','TERMINE'=>'badge-done', default=>'badge-waiting' }; ?>
                                    <tr>
                                        <td>#<?= (int)$r['ID_RESERVATION'] ?></td>
                                        <td><?= htmlspecialchars($r['LIB_PRESTATION']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($r['DATE_RESERVATION'])) ?></td>
                                        <td><?= htmlspecialchars($r['LIEU_RESERVATION']) ?></td>
                                        <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($r['STATUS_RESERVATION']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- LISTE CLIENTS -->
            <div class="dash-action-row">
                <form method="GET" style="display:flex;gap:10px;">
                    <div class="dash-search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Nom, prénom, email...">
                    </div>
                    <button type="submit" class="btn-dash btn-dash-primary"><i class="fas fa-search"></i> Rechercher</button>
                    <?php if ($search): ?><a href="clients.php" class="btn-dash btn-dash-outline"><i class="fas fa-times"></i></a><?php endif; ?>
                </form>
                <span style="font-size:0.85rem;color:#888;"><?= count($clients) ?> client(s)</span>
            </div>

            <div class="dash-card">
                <div class="dash-card-body">
                    <?php if (empty($clients)): ?>
                        <div class="empty-state"><i class="fas fa-users-slash"></i><p>Aucun client trouvé.</p></div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead><tr><th>Photo de profil</th><th>Nom complet</th><th>Email</th><th>Téléphone</th><th>Type</th><th>Rôle</th><th>Réservations</th><th>Détails</th></tr></thead>
                            <tbody>
                            <?php foreach ($clients as $c): ?>
                                <?php $isDefaultClientPhoto = ($c['PHOTO_CLIENT'] === 'assets/images/avatar.png'); ?>
                                    <tr>
                                        <td data-label="Photo de profil">
                                            <img src="../../<?= htmlspecialchars($c['PHOTO_CLIENT']) ?>" alt="Avatar" class="topbar-avatar" style="object-fit: cover;">
                                        </td>
                                        <td data-label="Nom complet">
                                            <div style="display:flex;align-items:center;gap:10px;">
                                                <strong><?= htmlspecialchars($c['PRENOM_CLIENT'] . ' ' . $c['NOM_CLIENT']) ?></strong>
                                            </div>
                                        </td>
                                        <td data-label="Email"><?= htmlspecialchars($c['EMAIL_AUTH']) ?></td>
                                        <td data-label="Téléphone"><?= htmlspecialchars($c['TEL_CLIENT']) ?></td>
                                        <td data-label="Type"><?= htmlspecialchars($c['TYPE_CLIENT']) ?></td>
                                        <td data-label="Rôle"><span class="badge badge-confirm"><?= htmlspecialchars($c['ROLE_AUTH']) ?></span></td>
                                        <td data-label="Réservations"><span class="badge badge-confirm"><?= (int)$c['nb_resas'] ?></span></td>
                                        <td data-label="Détails"><a href="?id=<?= (int)$c['ID_CLIENT'] ?>" class="btn-dash btn-dash-outline btn-dash-sm"><i class="fas fa-eye"></i> Voir</a></td>
                                    </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
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
</body>
</html>