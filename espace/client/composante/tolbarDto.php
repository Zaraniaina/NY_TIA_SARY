<?php
// Récupère les informations du client depuis la session
$clientEmail = $_SESSION['user_email'] ?? 'Client';
$clientNom = $_SESSION['client_nom'] ?? 'Client';
$clientPrenom = $_SESSION['client_prenom'] ?? '';
$clientId = (int) ($_SESSION['client_id'] ?? 0);
$pdo = getPDO();

// Récupère la photo du client depuis la base de données
$stmtPhoto = $pdo->prepare('SELECT PHOTO_CLIENT FROM CLIENT WHERE ID_CLIENT = ?');
$stmtPhoto->execute([$clientId]);
$photoClient = $stmtPhoto->fetchColumn() ?: 'assets/images/avatar.png';
$isDefaultPhoto = ($photoClient === 'assets/images/avatar.png');

$titre = "";
?>