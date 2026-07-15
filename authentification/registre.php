<?php
declare(strict_types=1);

// Utilisation de chemins absolus basés sur le répertoire du script pour garantir la compatibilité
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../util/redirectionpage.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage rapide des données du formulaire
    $nom_client = trim($_POST['nom_client'] ?? '');
    $prenom_client = trim($_POST['prenom_client'] ?? '');
    $tel_client = trim($_POST['tel_client'] ?? '');
    $type_client = trim($_POST['type_client'] ?? '');
    $email_client = trim($_POST['email_client'] ?? '');
    $mdp_client = $_POST['mdp_client'] ?? '';
    $question = $_POST['question'] ?? '';
    $reponse = trim($_POST['reponse'] ?? '');

    // Validation basique des champs requis
    if (empty($nom_client) || empty($prenom_client) || empty($tel_client) || empty($type_client) || empty($email_client) || empty($mdp_client) || empty($question) || empty($reponse)) {
        $_SESSION['register_error'] = "Veuillez remplir tous les champs du formulaire.";
        redirectionClient("../login/register.php");
    }

    // Formatage du numéro de téléphone (ajout de l'indicatif +261 si non présent)
    if (!str_starts_with($tel_client, '+261')) {
        $tel_client = '+261' . ltrim($tel_client, '0'); // On enlève le 0 initial s'il y en a un
    }

    // Validation du format de l'e-mail
    if (!filter_var($email_client, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = "Adresse e-mail invalide.";
        redirectionClient("../login/register.php");
    }

    try {
        $pdo = getPDO();
        
        // Démarrage de la transaction pour garantir l'intégrité (Client + Question de Sécurité)
        $pdo->beginTransaction();

        // Vérifier si l'adresse e-mail existe déjà dans la base de données
        $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM AUTHENTIFICATION WHERE EMAIL_AUTH = :email');
        $stmtCheck->execute(['email' => $email_client]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception("Cette adresse e-mail est déjà associée à un compte.");
        }

        // Hachage sécurisé du mot de passe
        $hashedMdp = password_hash($mdp_client, PASSWORD_BCRYPT);
        // Insertion de l'authentification
        $stmtAuth=$pdo->prepare("INSERT INTO AUTHENTIFICATION (EMAIL_AUTH,MDP_AUTH) VALUES (:email,:mdp) ");
        $stmtAuth->execute([
            "email"=> $email_client,
            'mdp'=> $hashedMdp,
        ]);

        
        // Récupération de l'ID du client nouvellement créer
        $idAuth = $pdo->lastInsertId();
        // Insertion du client
        $stmtClient = $pdo->prepare('INSERT INTO CLIENT (ID_AUTH,NOM_CLIENT, PRENOM_CLIENT, TEL_CLIENT, TYPE_CLIENT) VALUES (:id_auth,:nom, :prenom, :tel, :type)');
        $stmtClient->execute([
            'id_auth'=> $idAuth,
            'nom'    => $nom_client,
            'prenom' => $prenom_client,
            'tel'    => $tel_client,
            'type'   => $type_client, // Valeur sélectionnée par l'utilisateur
        ]);


        // Hachage sécurisé de la réponse de sécurité (en minuscules pour éviter les soucis de casse)
        $hashedReponse = password_hash(strtolower($reponse), PASSWORD_BCRYPT);

        // Insertion de la question de sécurité dans la table SECUTITE
        $stmtSecutite = $pdo->prepare('INSERT INTO SECUTITE (ID_AUTH, QUESTION, REPONSE) VALUES (:id_auth, :question, :reponse)');
        $stmtSecutite->execute([
            'id_auth' => $idAuth,
            'question'  => $question,
            'reponse'   => $hashedReponse
        ]);

        // Validation finale de la transaction
        $pdo->commit();

        // Redirection vers la page de connexion avec un message de succès
        $_SESSION['register_success'] = "Votre compte a été créé avec succès ! Connectez-vous maintenant.";
        redirectionClient("../login/login.php");

    } catch (Exception $e) {
        // Annulation de toutes les requêtes en cas d'erreur
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Enregistrement de l'erreur dans la session et retour au formulaire
        $_SESSION['register_error'] = $e->getMessage();
        redirectionClient("../login/register.php");
    }
} else {
    // Redirection si accès direct sans POST
    redirectionClient("../login/register.php");
}
?>