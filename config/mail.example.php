<?php

return [
    // Configuration SMTP pour la production
    "host" => "smtp.votre-serveur.com",
    "port" => 465, // 465 pour SSL, 587 pour TLS

    "username" => "votre_email@domaine.com",
    "password" => "votre_mot_de_passe",

    "encryption" => "tls",

    "from_email" => "contact@votre-domaine.com",
    "from_name" => "Nom de votre entreprise"
];
?>
