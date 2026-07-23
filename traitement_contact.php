<?php
session_start();
require_once __DIR__ . '/util/mailService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Collecter et nettoyer les données
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $objet = trim($_POST['objet'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // 2. Validation simple
    if (empty($nom) || empty($prenom) || empty($email) || empty($message)) {
        $_SESSION['contact_status'] = 'error';
        $_SESSION['contact_message'] = 'Veuillez remplir tous les champs obligatoires.';
        header('Location: contact.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['contact_status'] = 'error';
        $_SESSION['contact_message'] = 'L\'adresse email fournie n\'est pas valide.';
        header('Location: contact.php');
        exit;
    }

    // 3. Récupérer l'email admin depuis la session (stocké dans header.php)
    $adminEmail = $_SESSION['email_admin_contact'] ?? 'admin@gmail.com'; // fallback

    // 4. Préparer les données pour le service mail
    $contactData = [
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $email,
        'telephone' => $telephone,
        'objet' => $objet,
        'message' => $message
    ];

    // 5. Envoyer l'email
    $mailService = new MailService();
    $success = $mailService->sendContactMessage($adminEmail, $contactData);

    // 6. Gérer le résultat
    if ($success) {
        $_SESSION['contact_status'] = 'success';
        $_SESSION['contact_message'] = 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.';
    } else {
        $_SESSION['contact_status'] = 'error';
        $_SESSION['contact_message'] = 'Une erreur est survenue lors de l\'envoi de votre message. Veuillez réessayer plus tard.';
    }

    header('Location: contact.php');
    exit;
} else {
    // Redirection si accès direct sans POST
    header('Location: contact.php');
    exit;
}
