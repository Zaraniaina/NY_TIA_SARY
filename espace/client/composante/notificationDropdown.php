<?php
// notificationDropdown.php — Composant Notification Dropdown pour le client
require_once __DIR__ . '/../../../config/database.php';
$pdo = getPDO();
$clientId = (int)($_SESSION['CLIENT_ID'] ?? 0);

// Récupérer les notifications du client
$notifications = [];
try {
    if ($clientId > 0) {
        $stmt = $pdo->prepare("
            SELECT ID_NOTIF, TYPE_NOTIF, ID_REF_NOTIF, TITRE_NOTIF, MESS_NOTIF, LU_NOTIF, DATE_NOTIF
            FROM notification
            WHERE ID_CLIENT = ? AND SUP_NOTIF = 0
            ORDER BY DATE_NOTIF DESC, ID_NOTIF DESC
        ");
        $stmt->execute([$clientId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $targetPage = '#';
            switch ($row['TYPE_NOTIF']) {
                case 'client_contrat':
                    $targetPage = 'contrats.php';
                    break;
                case 'client_devis':
                    $targetPage = 'devis.php';
                    break;
                case 'client_facture':
                    $targetPage = 'factures.php';
                    break;
                case 'client_media':
                    $targetPage = 'mes_photos.php'; 
                    break;
                case 'client_paiement':
                    $targetPage = 'paiements.php';
                    break;
            }

            $notifications[] = [
                'id_notif'   => (int) $row['ID_NOTIF'],
                'id_ref'     => (int) $row['ID_REF_NOTIF'],
                'type'       => $row['TYPE_NOTIF'],
                'title'      => $row['TITRE_NOTIF'],
                'time'       => date('d/m/Y', strtotime($row['DATE_NOTIF'])),
                'message'    => $row['MESS_NOTIF'],
                'unread'     => ((int) $row['LU_NOTIF']) === 0,
                'targetPage' => $targetPage
            ];
        }
    }
} catch (PDOException $e) {
    $notifications = [];
}

// Compter les notifications non lues
$unreadCount = 0;
foreach ($notifications as $n) {
    if ($n['unread']) $unreadCount++;
}
?>

<div class="notification-dropdown" id="notificationDropdown">
    <div class="notification-dropdown-header">
        <h3>Notifications</h3>
        <button class="notification-dropdown-close" id="notificationDropdownClose">&times;</button>
    </div>
    
    <div class="notification-dropdown-filters">
        <button class="notif-dropdown-filter active" data-filter="all" type="button">Tous</button>
        <button class="notif-dropdown-filter" data-filter="unread" type="button">Non lues (<?= $unreadCount ?>)</button>
    </div>

    <div class="notification-dropdown-list">
        <?php if (empty($notifications)): ?>
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <p>Aucune notification</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <?php 
                $cardClass = $notification['unread'] ? 'notification-dropdown-card unread' : 'notification-dropdown-card';
                ?>
                <article class="<?= htmlspecialchars($cardClass) ?>" data-filter="<?= $notification['unread'] ? 'unread' : 'read' ?>">
                    <a href="<?= $notification['targetPage'] ?>?id=<?= $notification['id_ref'] ?>&mark_notif=<?= $notification['id_notif'] ?>" 
                       class="notification-dropdown-link" style="text-decoration:none;">
                        <div class="notification-dropdown-card-top">
                            <div class="notification-title-wrap">
                                <h4><?= htmlspecialchars($notification['title']) ?></h4>
                            </div>
                            <?php if ($notification['unread']): ?>
                                <span class="notification-dot" aria-hidden="true"></span>
                            <?php endif; ?>
                        </div>
                        <p class="notification-message"><?= htmlspecialchars($notification['message']) ?></p>
                        <div class="notification-time-wrap">
                            <span class="notification-time"><?= htmlspecialchars($notification['time']) ?></span>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="notification-dropdown-footer">
        <!-- Lien vers la future page de notifications globales du client, on la créera si besoin -->
        <a href="home.php" class="notification-view-all">Fermer</a>
    </div>
</div>
