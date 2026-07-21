<?php
declare(strict_types=1);

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // Remplacez ces valeurs par celles de votre serveur de production
        $host = 'localhost';
        $db   = 'nom_de_la_base';
        $user = 'utilisateur_db';
        $pass = 'mot_de_passe_db';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES    => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
           
        } catch (PDOException $e) {
            error_log($e->getMessage());
            die('Erreur de connexion à la base de données.');
        }
    }

    return $pdo;
}
?>
