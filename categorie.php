<?php
/**
 * get_categories.php
 * Endpoint AJAX : renvoie en JSON les catégories liées à une prestation donnée.
 * Appelé par script.js quand l'utilisateur change le menu déroulant "Prestation"
 * dans le modal de demande de devis.
 */

require_once __DIR__ . '/config/database.php'; // adapte le chemin si besoin, doit fournir $pdo

header('Content-Type: application/json; charset=utf-8');
$pdo        = getPDO();
$idPrestation = $_GET['id_prestation'] ?? '';

if ($idPrestation === '' || !ctype_digit((string) $idPrestation)) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT ID_CATEGORIE, LIB_CATEGORIE
         FROM categorie
         WHERE ID_PRESTATION = :id_prestation
         ORDER BY LIB_CATEGORIE ASC"
    );
    $stmt->execute([':id_prestation' => (int) $idPrestation]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($categories);
} catch (Throwable $e) {
    error_log('[get_categories.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => "Erreur lors du chargement des catégories."]);
}