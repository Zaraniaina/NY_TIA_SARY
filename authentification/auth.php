<?php
declare(strict_types=1);

// Démarrer la session en premier pour stocker les messages d'erreur et de succès
session_start();

// Utilisation de __DIR__ pour s'assurer que les chemins d'inclusion soient résolus correctement
// indépendamment du dossier depuis lequel le script est appelé.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../util/redirectionpage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et assainir les informations du formulaire
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Vérifier si les champs sont vides
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Veuillez remplir tous les champs.";
        redirectionClient("../login/login.php");
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


                // Authentification réussie : Rediriger l'administrateurs vers la page d'accueil
                redirectionClient("../espace/admin/home.php");

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

                // Authentification réussie : Rediriger l'utilisateur vers la page d'accueil
                redirectionClient("../espace/client/home.php");

            }
            
            
        } else {
            // Identifiants erronés
            $_SESSION['login_error'] = "Adresse e-mail ou mot de passe incorrect.";
            redirectionClient("../login/login.php");
        }
    } catch (PDOException $e) {
        // Gestion des erreurs de base de données
        $_SESSION['login_error'] = "Une erreur technique est survenue.";
        redirectionClient("../login/login.php");
    }
} else {
    // Redirection si l'utilisateur essaie d'accéder au script directement
    redirectionClient("../login/login.php");
}
?>