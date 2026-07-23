<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();
require_once __DIR__ . '/composante/tolbarDto.php';
$titre = 'Notifications';

$success = '';
$error = '';

if (isset($_GET['mark_notif']) && (int)$_GET['mark_notif'] > 0) {
    $idNotif = (int) $_GET['mark_notif'];
    $goto = trim((string)($_GET['goto'] ?? ''));

    try {
        $stmtCheck = $pdo->prepare('SELECT ID_NOTIF FROM notification WHERE ID_NOTIF = ? AND ID_CLIENT = ?');
        $stmtCheck->execute([$idNotif, $clientId]);
        $notifExists = (bool) $stmtCheck->fetchColumn();

        if ($notifExists) {
            $stmtUpdate = $pdo->prepare('UPDATE notification SET LU_NOTIF = 1 WHERE ID_NOTIF = ? AND ID_CLIENT = ?');
            $stmtUpdate->execute([$idNotif, $clientId]);

            if ($goto !== '' && preg_match('/^(?!.*:\\/\/)(?!.*\\\\)[A-Za-z0-9_\/\-\.\#%\?\=&]*$/', $goto)) {
                $decodedGoto = rawurldecode($goto);
                header('Location: ' . $decodedGoto);
                exit;
            }

            $success = 'Notification marquée comme lue.';
        } else {
            $error = 'Notification introuvable.';
        }
    } catch (PDOException $e) {
        $error = 'Impossible de marquer la notification.';
    }
}

$notifications = [];
try {
    $stmt = $pdo->prepare(
        'SELECT ID_NOTIF, TYPE_NOTIF, ID_REF_NOTIF, TITRE_NOTIF, MESS_NOTIF, LU_NOTIF, DATE_NOTIF
         FROM notification
         WHERE SUP_NOTIF = 0 AND ID_CLIENT = ?
         ORDER BY DATE_NOTIF DESC'
    );
    $stmt->execute([$clientId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $notifications = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Espace client NY TIA SARY</title>
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
                <span>Notifications</span>
            </nav>

            <?php if ($success): ?>
                <div class="dash-alert dash-alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="dash-alert dash-alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-bell" style="color:var(--primary-green);margin-right:8px;"></i> Mes notifications</h3>
                    <span class="badge badge-confirm"><?= count($notifications) ?></span>
                </div>
                <div class="dash-card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state"><i class="fas fa-bell-slash"></i><p>Aucune notification pour le moment.</p></div>
                    <?php else: ?>
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notification):
                                $isUnread = ((int)$notification['LU_NOTIF']) === 0;
                                $itemClass = $isUnread ? 'fb-notif-item unread' : 'fb-notif-item';
                                $icon = fb_notif_icon($notification['TYPE_NOTIF']);
                                $badgeClass = fb_notif_badge_class($notification['TYPE_NOTIF']);
                            ?>
                                <div class="fb-notif-item <?= $itemClass ?>">
                                    <div class="fb-notif-avatar">
                                        <div class="fb-notif-avatar-circle"><i class="fas fa-user"></i></div>
                                        <span class="fb-notif-badge <?= $badgeClass ?>"><i class="fas <?= $icon ?>"></i></span>
                                    </div>
                                    <div class="fb-notif-text">
                                        <span><strong><?= htmlspecialchars($notification['TITRE_NOTIF']) ?></strong></span>
                                        <span class="fb-notif-message"><?= htmlspecialchars($notification['MESS_NOTIF']) ?></span>
                                        <span class="fb-notif-time"><?= date('d/m/Y', strtotime($notification['DATE_NOTIF'])) ?></span>
                                    </div>
                                    <?php if ($isUnread): ?><span class="fb-notif-dot" aria-hidden="true"></span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const toggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', closeSidebar);
</script>
</body>
</html>