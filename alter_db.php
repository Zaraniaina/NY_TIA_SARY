<?php
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();
try {
    $pdo->exec("ALTER TABLE RESERVATION ADD ID_CATEGORIE bigint(4) NULL AFTER ID_PRESTATION");
    $pdo->exec("ALTER TABLE RESERVATION ADD CONSTRAINT FK_RESA_CAT FOREIGN KEY (ID_CATEGORIE) REFERENCES categorie(ID_CATEGORIE)");
    echo "Success";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
