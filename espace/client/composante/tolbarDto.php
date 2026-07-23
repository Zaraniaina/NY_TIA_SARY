<?php
require_once __DIR__ . '/../../../config/database.php';
// Récupère les informations du client depuis la session
$clientEmail = $_SESSION['user_email'] ?? 'Client';
$clientNom = $_SESSION['client_nom'] ?? 'Client';
$clientPrenom = $_SESSION['client_prenom'] ?? '';
$clientId = (int) ($_SESSION['client_id'] ?? 0);
$pdo = getPDO();

$stmtPhoto = $pdo->prepare('SELECT PHOTO_CLIENT FROM CLIENT WHERE ID_CLIENT = ?');
$stmtPhoto->execute([$clientId]);
$photoClient = $stmtPhoto->fetchColumn() ?: 'assets/images/avatar.png';
$isDefaultPhoto = ($photoClient === 'assets/images/avatar.png');

$notifications = [];
$notifCount = 0;

try {
    $stmtNotif = $pdo->prepare(
        'SELECT ID_NOTIF, TYPE_NOTIF, ID_REF_NOTIF, TITRE_NOTIF, MESS_NOTIF, LU_NOTIF, DATE_NOTIF
         FROM notification
         WHERE SUP_NOTIF = 0 AND ID_CLIENT = ?
         ORDER BY DATE_NOTIF DESC
         LIMIT 20'
    );
    $stmtNotif->execute([$clientId]);
    $rows = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $isUnread = ((int) $row['LU_NOTIF']) === 0;
        if ($isUnread) {
            $notifCount++;
        }
        $notifications[] = [
            'id_notif' => (int) $row['ID_NOTIF'],
            'id_ref'   => (int) $row['ID_REF_NOTIF'],
            'type'     => $row['TYPE_NOTIF'],
            'title'    => $row['TITRE_NOTIF'],
            'message'  => $row['MESS_NOTIF'],
            'time'     => date('d/m/Y', strtotime($row['DATE_NOTIF'])),
            'unread'   => $isUnread,
        ];
    }
} catch (PDOException $e) {
    $notifications = [];
    $notifCount = 0;
}

if (!function_exists('fb_notif_icon')) {
    function fb_notif_icon(string $type): string {
        return match ($type) {
            'Reservation' => 'fa-calendar-check',
            'devis' => 'fa-file-invoice',
            'reaction' => 'fa-heart',
            'blog' => 'fa-newspaper',
            default => 'fa-bell',
        };
    }
}

if (!function_exists('fb_notif_badge_class')) {
    function fb_notif_badge_class(string $type): string {
        return match ($type) {
            'Reservation' => 'type-reservation',
            'devis' => 'type-devis',
            'reaction' => 'type-reaction',
            'blog' => 'type-reaction',
            default => 'type-default',
        };
    }
}

if (!function_exists('fb_notif_target_url')) {
    function fb_notif_target_url(string $type, int $idRef): string {
        return match ($type) {
            'Reservation' => 'home.php#notif-reservation-' . $idRef,
            'devis'       => 'home.php#notif-devis-' . $idRef,
            'blog',
            'reaction'    => '../../blog.php#article-' . $idRef,
            default       => 'home.php',
        };
    }
}
$titre = "";