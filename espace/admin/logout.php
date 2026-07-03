<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
// Supprimer uniquement les données admin
unset($_SESSION['admin_id'], $_SESSION['admin_email']);
if (empty($_SESSION)) {
    session_unset();
    session_destroy();
}
header('Location: ../../login/login.php');
exit();
