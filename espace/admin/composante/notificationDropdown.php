<?php
// notificationDropdown.php — Composant Notification Dropdown pour l'admin
require_once __DIR__ . '/../../../config/database.php';
$pdo = getPDO();
// Récupérer les notifications
$notifications = [];
try {
    $stmt = $pdo->prepare("
        SELECT n.ID_NOTIF, n.TYPE_NOTIF, n.ID_REF_NOTIF, n.TITRE_NOTIF, n.MESS_NOTIF, n.LU_NOTIF, n.DATE_NOTIF,
               d.NOM AS NOM_DEVIS, d.PRENOMS AS PRENOM_DEVIS,
               c.NOM_CLIENT AS NOM_RESA, c.PRENOM_CLIENT AS PRENOM_RESA
        FROM notification n
        LEFT JOIN devis d ON n.TYPE_NOTIF = 'devis' AND n.ID_REF_NOTIF = d.ID
        LEFT JOIN RESERVATION r ON n.TYPE_NOTIF = 'Reservation' AND n.ID_REF_NOTIF = r.ID_RESERVATION
        LEFT JOIN CLIENT c ON c.ID_CLIENT = r.ID_CLIENT
        WHERE n.SUP_NOTIF = 0 AND n.ID_CLIENT IS NULL
        ORDER BY n.DATE_NOTIF DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $isResa = $row['TYPE_NOTIF'] === 'Reservation';

        $notifications[] = [
            'id_notif' => (int) $row['ID_NOTIF'],
            'id_ref'   => (int) $row['ID_REF_NOTIF'],
            'type'     => $row['TYPE_NOTIF'],
            'title'    => $row['TITRE_NOTIF'],
            'nom'      => $isResa ? ($row['NOM_RESA'] ?? '')    : ($row['NOM_DEVIS'] ?? ''),
            'prenom'   => $isResa ? ($row['PRENOM_RESA'] ?? '') : ($row['PRENOM_DEVIS'] ?? ''),
            'time'     => date('d/m/Y', strtotime($row['DATE_NOTIF'])),
            'message'  => $row['MESS_NOTIF'],
            'unread'   => ((int) $row['LU_NOTIF']) === 0,
        ];
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
                $targetPage = $notification['type'] === 'Reservation' ? 'reservations.php' : 'devis.php';
                ?>
                <article class="<?= htmlspecialchars($cardClass) ?>" data-filter="<?= $notification['unread'] ? 'unread' : 'read' ?>">
                    <a href="<?= $targetPage ?>?id=<?= $notification['id_ref'] ?>&mark_notif=<?= $notification['id_notif'] ?>" 
                       class="notification-dropdown-link" style="text-decoration:none;">
                        <div class="notification-dropdown-card-top">
                            <div class="notification-title-wrap">
                                <h4><?= htmlspecialchars($notification['title']) ?></h4>
                            </div>
                            <?php if ($notification['unread']): ?>
                                <span class="notification-dot" aria-hidden="true"></span>
                            <?php endif; ?>
                        </div>
                        <div class="notification-client-details">
                            <span><?= htmlspecialchars($notification['prenom'] . ' ' . $notification['nom']) ?></span>
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
</div>