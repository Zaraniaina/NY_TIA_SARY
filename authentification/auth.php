<?php
declare(strict_types=1);

// Démarrer la session en premier pour stocker les messages d'erreur et de succès
session_start();

// Utilisation de __DIR__ pour s'assurer que les chemins d'inclusion soient résolus correctement
// indépendamment du dossier depuis lequel le script est appelé.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../util/redirectionpage.php';

// Charger la config du site pour les URLs absolues (require, pas require_once)
$siteConfig = require __DIR__ . '/../config/site.php';
$baseUrl    = rtrim($siteConfig['url'], '/');

// On récupère juste le domaine pour reconstruire les URL absolues à partir de $_SERVER['REQUEST_URI']
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$hostUrl = $scheme . '://' . $host;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et assainir les informations du formulaire
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Vérifier si les champs sont vides
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Veuillez remplir tous les champs.";
        redirectionClient($baseUrl . '/login/login.php');
    }

    try {
        // Obtenir la connexion PDO
        $pdo = getPDO();

        // Requête préparée pour vérifier si l'utilisateur existe dans la base de données
        $stmt = $pdo->prepare('SELECT * FROM AUTHENTIFICATION WHERE EMAIL_AUTH = :email');
        $stmt->execute(['email' => $email]);
        $auth = $stmt->fetch();

        // Si le client existe et que son mot de passe correspond à la colonne MDP_CLIENT
        if ( $auth && password_verify($password,  $auth['MDP_AUTH'])) {

            //on dois verifier son role
            if($auth['ROLE_AUTH']=="ADMIN"){
                // Stocker les données utiles dans la session de l'utilisateur
                $_SESSION['admin_id'] = $auth['ID_AUTH'];
                $_SESSION['admin_email'] = $auth['EMAIL_AUTH'];

                // simple client on dois recuperer les information du client
                $stmtClient= $pdo->prepare("SELECT * FROM CLIENT WHERE ID_AUTH=:id_auth");
                $stmtClient->execute(["id_auth"=>  $auth['ID_AUTH']]);
                $client=$stmtClient->fetch();
                
                // Stocker les données utiles dans la session de l'utilisateur
                $_SESSION['admin_nom'] = $client['NOM_CLIENT'];
                $_SESSION['admin_prenom'] = $client['PRENOM_CLIENT'];


                // Authentification réussie : Rediriger l'administrateur vers la page d'accueil ou ressource d'origine
                if (isset($_SESSION['redirect_url']) && isSafeRedirect($_SESSION['redirect_url'])) {
                    $redirectUrl = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                    // Sécurité : Un admin ne doit pas être redirigé vers l'espace client
                    if (strpos($redirectUrl, '/espace/client/') !== false) {
                        redirectionClient($baseUrl . '/espace/admin/home.php');
                    } else {
                        redirectionClient($hostUrl . $redirectUrl);
                    }
                } else {
                    redirectionClient($baseUrl . '/espace/admin/home.php');
                }

            }else{
                // simple client on dois recuperer les information du client
                $stmtClient= $pdo->prepare("SELECT * FROM CLIENT WHERE ID_AUTH=:id_auth");
                $stmtClient->execute(["id_auth"=>  $auth['ID_AUTH']]);
                $client=$stmtClient->fetch();
                
                // Stocker les données utiles dans la session de l'utilisateur
                $_SESSION['client_id'] = $client['ID_CLIENT'];
                $_SESSION['client_nom'] = $client['NOM_CLIENT'];
                $_SESSION['client_prenom'] = $client['PRENOM_CLIENT'];
                $_SESSION['client_email'] = $auth['EMAIL_AUTH'];

                // Authentification réussie : Rediriger le client vers la page d'accueil ou ressource d'origine
                if (isset($_SESSION['redirect_url']) && isSafeRedirect($_SESSION['redirect_url'])) {
                    $redirectUrl = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                    // Sécurité : Un client ne doit pas être redirigé vers l'espace admin
                    if (strpos($redirectUrl, '/espace/admin/') !== false) {
                        redirectionClient($baseUrl . '/espace/client/home.php');
                    } else {
                        redirectionClient($hostUrl . $redirectUrl);
                    }
                } else {
                    redirectionClient($baseUrl . '/espace/client/home.php');
                }

            }
            
            
        } else {
            // Identifiants erronés
            $_SESSION['login_error'] = "Adresse e-mail ou mot de passe incorrect.";
            redirectionClient($baseUrl . '/login/login.php');
        }
    } catch (PDOException $e) {
        // Gestion des erreurs de base de données
        $_SESSION['login_error'] = "Une erreur technique est survenue.";
        redirectionClient($baseUrl . '/login/login.php');
    }
} else {
    // Redirection si l'utilisateur essaie d'accéder au script directement
    redirectionClient($baseUrl . '/login/login.php');
}
?>