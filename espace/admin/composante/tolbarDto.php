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

try {
    $stmtNotif = $pdo->prepare("SELECT COUNT(*) FROM notification WHERE LU_NOTIF = 0 AND SUP_NOTIF = 0 AND ID_CLIENT IS NULL");
    $stmtNotif->execute();
    $notifCount = (int) $stmtNotif->fetchColumn();
} catch (PDOException $e) {
    $notifCount = 0;
}
?>