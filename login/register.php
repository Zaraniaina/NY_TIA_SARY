<?php
session_start();
$error = $_SESSION['register_error'] ?? null;
unset($_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Créez votre compte client sécurisé pour accéder aux services premium du studio de photographie NY TIA SARY.">
    <title>Inscription | NY TIA SARY - Photography Studio</title>
    <!-- Chargement de la feuille de style locale partagée -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- Conteneur d'arrière-plan cinématographique -->
    <div class="bg-container"></div>
    <div class="bg-overlay"></div>

    <!-- Conteneur principal de la page d'inscription (avec classe register-container pour la largeur) -->
    <main class="login-container register-container">
        <div class="login-card">
            <!-- Logo du studio -->
            <header class="logo">
                <span class="logo-main">NY TIA SARY</span>
                <span class="logo-sub">Photography Studio</span>
            </header>

            <!-- Titre et sous-titre de la carte -->
            <div class="card-header">
                <h2>Création de Compte</h2>
                <p>Créez votre espace client personnalisé.</p>
            </div>

            <!-- Affichage des messages d'erreur -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Formulaire d'inscription -->
            <form class="login-form" action="../authentification/registre.php" method="POST" autocomplete="off">

                <!-- Grille à deux colonnes pour les champs -->
                <div class="form-grid">

                    <!-- Champ Nom -->
                    <div class="input-group">
                        <input type="text" id="nom_client" name="nom_client" required placeholder="Votre nom"
                            aria-label="Nom">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </span>
                    </div>

                    <!-- Champ Prénom -->
                    <div class="input-group">
                        <input type="text" id="prenom_client" name="prenom_client" required placeholder="Votre prénom"
                            aria-label="Prénom">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </span>
                    </div>

                    <!-- Champ Téléphone -->
                    <div class="input-group">
                        <input type="tel" id="tel_client" name="tel_client" required placeholder="Votre téléphone"
                            aria-label="Téléphone">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                            </svg>
                        </span>
                    </div>

                    <!-- Champ Email -->
                    <div class="input-group">
                        <input type="email" id="email_client" name="email_client" required placeholder="Votre adresse e-mail"
                            aria-label="Adresse e-mail">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="20" height="16" x="2" y="4" rx="2" />
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                            </svg>
                        </span>
                    </div>

                    <!-- Champ Mot de passe -->
                    <div class="input-group grid-full">
                        <input type="password" id="mdp_client" name="mdp_client" required placeholder="Votre mot de passe"
                            aria-label="Mot de passe">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                        </span>
                    </div>

                    <!-- Champ Question de sécurité (Select) -->
                    <div class="input-group select-group grid-full">
                        <select id="question" name="question" required aria-label="Question de sécurité">
                            <option value="" disabled selected hidden>Sélectionnez une question de sécurité</option>
                            <option value="Quel est le nom de votre artiste préféré ?">Quel est le nom de votre artiste préféré ?</option>
                            <option value="Quel est votre plat préféré ?">Quel est votre plat préféré ?</option>
                            <option value="Quelle est votre ville de naissance ?">Quelle est votre ville de naissance ?</option>
                            <option value="Quel était le nom de votre première école ?">Quel était le nom de votre première école ?</option>
                            <option value="Quel est votre film préféré ?">Quel est votre film préféré ?</option>
                        </select>
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                                <path d="M12 17h.01" />
                            </svg>
                        </span>
                    </div>

                    <!-- Champ Réponse de sécurité -->
                    <div class="input-group grid-full">
                        <input type="text" id="reponse" name="reponse" required placeholder="Votre réponse secrète"
                            aria-label="Réponse de sécurité">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path
                                    d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 1.5 1.5M15.5 7.5 14 6" />
                            </svg>
                        </span>
                    </div>

                </div>

                <!-- Bouton d'inscription -->
                <button type="submit" class="btn-submit">Créer mon compte</button>
            </form>

            <!-- Lien de retour vers connexion -->
            <div class="signup-prompt">
                Vous avez déjà un compte ? <a href="login.php">Se connecter</a>
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