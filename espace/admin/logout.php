<?php
declare(strict_types=1);

// Inclusion des fonctions de redirection et de nettoyage de session
require_once __DIR__.'/../../util/redirectionpage.php';
require_once __DIR__.'/../../util/auth_guard.php'; // fonction clearSessionAndCache()

if($_GET['action']==='deconnexion'){
    // Lors de la déconnexion, on nettoie complètement la session et on désactive le cache du navigateur
    // La fonction clearSessionAndCache() se charge de tout cela (voir util/auth_guard.php)
    clearSessionAndCache();

    // Rediriger l'utilisateur vers la page de connexion
    redirectionClient('../../login/login.php');
}