<?php
/**
 * api/categories.php
 * Retourne en JSON les catégories d'une prestation (pour le formulaire de réservation client).
 * GET ?id_prestation=N
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
session_start();

header('Content-Type: application/json');

// Sécurité basique : il faut être connecté (client ou admin)
if (empty($_SESSION['client_id']) && empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode([]);
    exit();
}

$idPrest = (int) ($_GET['id_prestation'] ?? 0);
if (!$idPrest) {
    echo json_encode([]);
    exit();
}

$pdo  = getPDO();
$stmt = $pdo->prepare('SELECT ID_CATEGORIE, LIB_CATEGORIE, TARIF_CATEGORIE FROM CATEGORIE WHERE ID_PRESTATION = ? ORDER BY TARIF_CATEGORIE ASC');
$stmt->execute([$idPrest]);
$rows = $stmt->fetchAll();

$result = [];
foreach ($rows as $row) {
    $result[] = [
        'id'        => (int) $row['ID_CATEGORIE'],
        'lib'       => $row['LIB_CATEGORIE'],
        'tarif'     => (int) $row['TARIF_CATEGORIE'],
        'tarif_fmt' => number_format((int) $row['TARIF_CATEGORIE'], 0, ',', ' '),
    ];
}

echo json_encode($result);
