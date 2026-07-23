<?php
require_once __DIR__ . '/../../../config/database.php';
//on recupere les information de l'admin
$adminEmail = $_SESSION['admin_email'] ?? 'Admin';
$adminNom = $_SESSION['admin_nom'] ?? 'Admin';
$adminPrenom = $_SESSION['admin_prenom'] ?? 'Admin';
$adminId    = (int) ($_SESSION['admin_id'] ?? 0);
$pdo        = getPDO();
// Fetch admin photo
$stmtPhoto = $pdo->prepare('SELECT PHOTO_CLIENT FROM CLIENT WHERE ID_AUTH = ?');
$stmtPhoto->execute([$adminId]);
$photoAdmin = $stmtPhoto->fetchColumn() ?: 'assets/images/avatar.png';
$isDefaultPhoto = ($photoAdmin === 'assets/images/avatar.png');

$titre="";

// Icône + badge selon le type de notification
function fb_notif_icon(string $type): string {
    return match ($type) {
        'devis' => 'fa-file-invoice',
        'Reservation' => 'fa-calendar-check',
        'reaction' => 'fa-heart',
        default => 'fa-bell',
    };
}
function fb_notif_badge_class(string $type): string {
    return match ($type) {
        'devis' => 'type-devis',
        'Reservation' => 'type-reservation',
        'reaction' => 'type-reaction',
        default => 'type-default',
    };
}

$notifications = [];
$notifCount = 0;

try {
    $stmt = $pdo->prepare("
        SELECT n.ID_NOTIF, n.TYPE_NOTIF, n.ID_REF_NOTIF, n.TITRE_NOTIF, n.MESS_NOTIF, n.LU_NOTIF, n.DATE_NOTIF,
               d.NOM AS NOM_DEVIS, d.PRENOMS AS PRENOM_DEVIS,
               c.NOM_CLIENT AS NOM_RESA, c.PRENOM_CLIENT AS PRENOM_RESA
        FROM notification n
        LEFT JOIN devis d ON n.TYPE_NOTIF = 'devis' AND n.ID_REF_NOTIF = d.ID
        LEFT JOIN RESERVATION r ON n.TYPE_NOTIF = 'Reservation' AND n.ID_REF_NOTIF = r.ID_RESERVATION
        LEFT JOIN CLIENT c ON c.ID_CLIENT = r.ID_CLIENT
        LEFT JOIN BLOG b ON n.TYPE_NOTIF = 'reaction' AND n.ID_REF_NOTIF = b.ID_BLOG
        WHERE n.SUP_NOTIF = 0 AND n.TYPE_NOTIF != 'blog'
        ORDER BY n.DATE_NOTIF DESC
        LIMIT 20
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $isResa = $row['TYPE_NOTIF'] === 'Reservation';
        $isUnread = ((int) $row['LU_NOTIF']) === 0;

        if ($isUnread) {
            $notifCount++;
        }

        $notifications[] = [
            'id_notif' => (int) $row['ID_NOTIF'],
            'id_ref'   => (int) $row['ID_REF_NOTIF'],
            'type'     => $row['TYPE_NOTIF'],
            'title'    => $row['TYPE_NOTIF'] === 'reaction' ? 'Nouvelle réaction' : $row['TITRE_NOTIF'],
            'blog_title' => $row['TYPE_NOTIF'] === 'reaction' ? ($row['TITRE_BLOG'] ?? 'Article') : null,
            'nom'      => $isResa ? ($row['NOM_RESA'] ?? '')    : ($row['NOM_DEVIS'] ?? ''),
            'prenom'   => $isResa ? ($row['PRENOM_RESA'] ?? '') : ($row['PRENOM_DEVIS'] ?? ''),
            'time'     => date('d/m/Y', strtotime($row['DATE_NOTIF'])),
            'message'  => $row['MESS_NOTIF'],
            'unread'   => $isUnread,
        ];
    }
} catch (PDOException $e) {
    $notifications = [];
    $notifCount = 0;
}