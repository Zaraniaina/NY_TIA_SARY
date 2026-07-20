<?php
session_start();
require_once __DIR__ . '/../util/redirectionpage.php';

if (isset($_GET['redirect']) && isSafeRedirect($_GET['redirect'])) {
    $_SESSION['redirect_url'] = $_GET['redirect'];
}

$error = $_SESSION['login_error'] ?? null;
$success = $_SESSION['register_success'] ?? null;
unset($_SESSION['login_error'], $_SESSION['register_success']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Espace de connexion sécurisé pour les clients du studio de photographie premium NY TIA SARY.">
    <title>Connexion | NY TIA SARY - Photography Studio</title>
    <!-- Chargement de la feuille de style locale -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- Conteneur d'arrière-plan cinématographique -->
    <div class="bg-container"></div>
    <div class="bg-overlay"></div>


    <!-- Conteneur principal de la page de connexion -->
    <main class="login-container">
        <div class="login-card">

            <!-- Bouton Retour vers la page vitrine -->
            <div class="back-to-home">
                <a href="../index.php" class="btn-back">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H6" />
                        <path d="M12 5l-7 7 7 7" />
                    </svg>
                </a>
            </div>

            <!-- Logo du studio -->
            <header class="logo">
                <span class="logo-main">NY TIA SARY</span>
                <span class="logo-sub">Photography Studio</span>
            </header>

            <!-- Titre et sous-titre de la carte -->
            <div class="card-header">
                <h2>Connexion</h2>
                <p>Connectez-vous à votre espace.</p>
            </div>

            <!-- Messages de statut (Erreur ou Succès) -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon" aria-hidden="true">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Formulaire de connexion -->
            <form class="login-form" action="../authentification/auth.php" method="POST" autocomplete="off">

                <!-- Champ Email -->
                <div class="input-group">
                    <input type="email" id="email" name="email" required placeholder="Votre adresse e-mail"
                        aria-label="Adresse e-mail">
                    <span class="input-icon">
                        <!-- Icône Enveloppe Minimaliste en SVG Interne -->
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="20" height="16" x="2" y="4" rx="2" />
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                        </svg>
                    </span>
                </div>

                <!-- Champ Mot de passe -->
                <div class="input-group">
                    <input type="password" id="password" name="password" required placeholder="Votre mot de passe"
                        aria-label="Mot de passe">
                    <span class="input-icon">
                        <!-- Icône Cadenas Minimaliste en SVG Interne -->
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                    </span>
                </div>

                <!-- Lien Mot de passe oublié -->
                <div class="forgot-password">
                    <a href="mdpOublier.php">Mot de passe oublié ?</a>
                </div>

                <!-- Bouton de soumission -->
                <button type="submit" class="btn-submit">Se connecter</button>
            </form>

            <!-- Lien d'inscription -->
            <div class="signup-prompt">
                Vous n'avez pas de compte ? <a href="register.php">Créer un compte</a>
            </div>

            <!-- Pied de page de la carte -->
            <footer class="card-footer">
                <p>&copy; 2026 NY TIA SARY</p>
                <p>Tous droits réservés.</p>
            </footer>

        </div>
    </main>

</body>

</html>