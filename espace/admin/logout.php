<?php
declare(strict_types=1);

require_once __DIR__.'../../../util/redirectionpage.php';

if($_GET['action']==='deconnexion'){

    if (session_status() === PHP_SESSION_NONE) session_start();
    // Supprimer uniquement les données admin
    unset($_SESSION['admin_id'], $_SESSION['admin_email']);
    if (empty($_SESSION)) {
        session_unset();
        session_destroy();
    }
    redirectionClient('../../login/login.php');
}