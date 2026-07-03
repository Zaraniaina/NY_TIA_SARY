<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Supprimer uniquement les données client
unset($_SESSION['client_id'], $_SESSION['client_nom'], $_SESSION['client_prenom'], $_SESSION['client_email']);
if (empty($_SESSION)) {
    session_unset();
    session_destroy();
}
header('Location: ../../login/login.php');
exit();
