<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../util/redirectionpage.php';

// Initialize session variables for password reset if not set
if (!isset($_SESSION['reset_step'])) {
    $_SESSION['reset_step'] = 1;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($_SESSION['reset_step']) {
        case 1: // Step 1: Email lookup
            if ($action === 'lookup') {
                $email = trim($_POST['email'] ?? '');

                if (empty($email)) {
                    $_SESSION['reset_error'] = "Veuillez entrer votre adresse e-mail.";
                } else {
                    try {
                        $pdo = getPDO();
                        $stmt = $pdo->prepare('SELECT ID_AUTH, EMAIL_AUTH FROM AUTHENTIFICATION WHERE EMAIL_AUTH = :email');
                        $stmt->execute(['email' => $email]);
                        $auth = $stmt->fetch();

                        // Security: Always show generic message to prevent email enumeration
                        if ($auth) {
                            // Store auth info in session
                            $_SESSION['reset_auth_id'] = $auth['ID_AUTH'];
                            $_SESSION['reset_email'] = $auth['EMAIL_AUTH'];

                            // Check if user has security question
                            $stmtSec = $pdo->prepare('SELECT QUESTION, REPONSE FROM SEcutite WHERE ID_AUTH = ?');
                            $stmtSec->execute([$auth['ID_AUTH']]);
                            $securityQuestion = $stmtSec->fetch();

                            if ($securityQuestion) {
                                $_SESSION['reset_has_security_question'] = true;
                                $_SESSION['reset_security_question'] = $securityQuestion['QUESTION'];
                                // Note: We don't store the answer in session for security
                            } else {
                                $_SESSION['reset_has_security_question'] = false;
                            }

                            // Check if user has phone number
                            $stmtClient = $pdo->prepare('SELECT TEL_CLIENT FROM CLIENT WHERE ID_AUTH = ?');
                            $stmtClient->execute([$auth['ID_AUTH']]);
                            $client = $stmtClient->fetch();

                            if ($client && !empty($client['TEL_CLIENT']) && $client['TEL_CLIENT'] !== 'assets/images/avatar.png') {
                                $_SESSION['reset_has_phone'] = true;
                                // Store only digits of the phone number for comparison
                                $_SESSION['reset_phone_number'] = preg_replace('/\D/', '', $client['TEL_CLIENT']);
                            } else {
                                $_SESSION['reset_has_phone'] = false;
                            }

                            // Move to step 2
                            $_SESSION['reset_step'] = 2;
                        } else {
                            // Generic message for security (don't reveal if email exists or not)
                            $_SESSION['reset_error'] = "Si un compte existe avec cette adresse e-mail, vous pouvez continuer avec le processus de récupération.";
                        }
                    } catch (PDOException $e) {
                        $_SESSION['reset_error'] = "Une erreur technique est survenue.";
                    }
                }
            }
            break;

        case 2: // Step 2: Choose verification method
            if ($action === 'choose_method') {
                $method = $_POST['method'] ?? '';

                if ($method === 'security_question') {
                    if ($_SESSION['reset_has_security_question']) {
                        $_SESSION['reset_step'] = 3;
                    } else {
                        $_SESSION['reset_error'] = "Aucune question de sécurité configurée pour ce compte.";
                    }
                } elseif ($method === 'phone') {
                    if ($_SESSION['reset_has_phone']) {
                        $_SESSION['reset_step'] = 3;
                        $_SESSION['reset_use_phone'] = true;
                    } else {
                        $_SESSION['reset_error'] = "Aucun numéro de téléphone configuré pour ce compte.";
                    }
                } else {
                    $_SESSION['reset_error'] = "Veuillez choisir une méthode de vérification.";
                }
            }
            break;

        case 3: // Step 3: Verify identity
            if ($action === 'verify') {
                if (!empty($_SESSION['reset_use_phone'])) {
                    // Phone verification - check if entered phone matches stored one
                    $enteredPhone = "+261".preg_replace('/\D/', '', ($_POST['tel_client'] ?? ''));
                    $storedPhone = "+".($_SESSION['reset_phone_number'] ?? '');

                    if (!empty($enteredPhone) && $enteredPhone === $storedPhone) {
                        $_SESSION['reset_verified'] = true;
                        $_SESSION['reset_step'] = 4;
                    } else {
                        $_SESSION['reset_error'] = "Numéro de téléphone incorrect. Veuillez réessayer.";
                    }
                } else {
                    // Security question verification
                    $answer = $_POST['answer'] ?? '';
                    $hashedAnswer = $_SESSION['reset_security_answer_hash'] ?? null;

                    if (!empty($answer) && $hashedAnswer && password_verify(strtolower($answer), $hashedAnswer)) {
                        $_SESSION['reset_verified'] = true;
                        $_SESSION['reset_step'] = 4;
                    } else {
                        $_SESSION['reset_error'] = "Réponse incorrecte. Veuillez réessayer.";
                    }
                }
            }
            break;

        case 4: // Step 4: Reset password
            if ($action === 'reset_password') {
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                if (empty($password) || empty($confirm_password)) {
                    $_SESSION['reset_error'] = "Veuillez remplir tous les champs.";
                } elseif ($password !== $confirm_password) {
                    $_SESSION['reset_error'] = "Les mots de passe ne correspondent pas.";
                } elseif (strlen($password) < 8) {
                    $_SESSION['reset_error'] = "Le mot de passe doit contenir au moins 8 caractères.";
                } else {
                    try {
                        $pdo = getPDO();
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                        $stmt = $pdo->prepare('UPDATE AUTHENTIFICATION SET MDP_AUTH = ? WHERE ID_AUTH = ?');
                        $stmt->execute([$hashedPassword, $_SESSION['reset_auth_id']]);

                        // Clear reset session variables
                        $_SESSION['reset_success'] = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
                        $_SESSION['reset_step'] = 1; // Reset for next use
                        unset($_SESSION['reset_auth_id'], $_SESSION['reset_email'], $_SESSION['reset_has_security_question'],
                              $_SESSION['reset_security_question'], $_SESSION['reset_security_answer_hash'],
                              $_SESSION['reset_has_phone'], $_SESSION['reset_phone_number'],
                              $_SESSION['reset_use_phone'], $_SESSION['reset_verified']);
                    } catch (PDOException $e) {
                        $_SESSION['reset_error'] = "Une erreur technique est survenue lors de la réinitialisation.";
                    }
                }
            }
            break;
    }

    // Redirect back to same page to prevent form resubmission
    redirectionClient("./mdpOublier.php");
    exit;
}

// Handle GET requests (initial load or after redirect)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Clear any error/success messages from session for display
    $error = $_SESSION['reset_error'] ?? null;
    $success = $_SESSION['reset_success'] ?? null;

    // Clear them from session after retrieving
    unset($_SESSION['reset_error'], $_SESSION['reset_success']);

    // For security question step, we need to hash the answer for comparison later
    if ($_SESSION['reset_step'] === 3 && !empty($_SESSION['reset_security_question']) && !isset($_SESSION['reset_security_answer_hash'])) {
        // Retrieve the hashed answer from the database
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT REPONSE FROM SEcutite WHERE ID_AUTH = ?');
        $stmt->execute([$_SESSION['reset_auth_id']]);
        $hashedAnswer = $stmt->fetchColumn();
        if ($hashedAnswer) {
            $_SESSION['reset_security_answer_hash'] = $hashedAnswer;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié | NY TIA SARY - Photography Studio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="bg-container"></div>
    <div class="bg-overlay"></div>

    <main class="login-container">
        <div class="login-card">

            <!-- Logo du studio -->
            <header class="logo">
                <span class="logo-main">NY TIA SARY</span>
                <span class="logo-sub">Photography Studio</span>
            </header>

            <!-- Titre et sous-titre de la carte -->
            <div class="card-header">
                <h2>Mot de passe oublié</h2>
                <p>Récupérez l'accès à votre compte en suivant les étapes ci-dessous.</p>
            </div>

            <!-- Messages de statut (Erreur ou Succès) -->
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

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon" aria-hidden="true">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Formulaire de réinitialisation de mot de passe -->
            <form class="login-form" method="POST" autocomplete="off">
                <?php if ($_SESSION['reset_step'] === 1): ?>
                    <!-- ÉTAPE 1: Recherche du compte -->
                    <div class="input-group">
                        <input type="email" id="email" name="email" required placeholder="Votre adresse e-mail"
                               aria-label="Adresse e-mail" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="20" height="16" x="2" y="4" rx="2" />
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                            </svg>
                        </span>
                    </div>

                    <!-- Bouton de soumission -->
                    <button type="submit" class="btn-submit" name="action" value="lookup">Rechercher mon compte</button>

                <?php elseif ($_SESSION['reset_step'] === 2): ?>
                    <!-- ÉTAPE 2: Choix de la méthode de vérification -->
                    <p>Nous avons trouvé un compte associé à <strong><?php echo htmlspecialchars($_SESSION['reset_email'] ?? ''); ?></strong>. Comment souhaitez-vous vérifier votre identité ?</p>


                    <div style="margin-bottom: 20px;margin-top: 20px">
                        <div style="display: grid; gap: 12px;">
                            <?php if ($_SESSION['reset_has_security_question']): ?>
                                <label style="display: flex; align-items: start; gap: 10px; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px; cursor: pointer;">
                                    <input type="radio" name="method" value="security_question" style="margin-top: 4px;">
                                    <div>
                                        <strong>Question de sécurité</strong>
                                        <p style="margin: 4px 0 0; font-size: 14px; color: var(--color-text-muted);">Répondre à votre question de sécurité secrète</p>
                                    </div>
                                </label>
                            <?php endif; ?>
                            <?php if ($_SESSION['reset_has_phone']): ?>
                                <label style="display: flex; align-items: start; gap: 10px; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px; cursor: pointer;">
                                    <input type="radio" name="method" value="phone" style="margin-top: 4px;">
                                    <div>
                                        <strong>Vérification par téléphone</strong>
                                        <p style="margin: 4px 0 0; font-size: 14px; color: var(--color-text-muted);">Vérifier votre identité en saisissant votre numéro de téléphone enregistré</p>
                                    </div>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Boutons de navigation -->
                    <div style="display: flex; gap: 12px;">
                        <button type="submit" class="btn-submit" name="action" value="choose_method" style="flex: 1;">Continuer</button>
                        <a href="login.php" style="flex: 1; text-align: center; display: block; padding: 16px; background: var(--color-card-bg); border: 1px solid var(--border-glass); border-radius: 12px; color: var(--color-text-white); text-decoration: none; font-weight: 500; transition: var(--transition-smooth);"
                           onmouseover="this.style.background='rgba(255,255,255,0.06)'; this.style.borderColor='var(--color-primary-gold)'; this.style.color='var(--color-primary-gold)';"
                           onmouseout="this.style.background='var(--color-card-bg)'; this.style.borderColor='var(--border-glass)'; this.style.color='var(--color-text-white);'">Annuler</a>
                    </div>

                <?php elseif ($_SESSION['reset_step'] === 3): ?>
                    <!-- ÉTAPE 3: Vérification de l'identité -->
                    <?php if (!empty($_SESSION['reset_use_phone'])): ?>
                        <!-- Phone verification -->
                        <p>Pour vérifier votre identité, veuillez saisir votre numéro de téléphone associé à votre compte.</p>

                        <!-- Indice du numéro de téléphone -->
                        <?php if (!empty($_SESSION['reset_phone_number'])): ?>
                            <div class="phone-hint" style="margin-bottom: 15px;margin-top: 10px; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 8px; font-size: 14px; color: var(--color-text-muted);">
                                <span style="margin-right: 8px;">Indice :</span>
                                <span style="font-family: monospace; font-weight: 500;">+261 3x xxx x<?php echo htmlspecialchars(substr($_SESSION['reset_phone_number'], -1)); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="input-group phone-group">
                            <div class="phone-prefix">
                                <img src="../assets/images/drapaux.png"
                                     alt="Madagascar"
                                     class="phone-flag-img"
                                     aria-hidden="true">
                                <span class="phone-dial-code">+261</span>
                            </div>
                            <div class="phone-sep"></div>
                            <input type="tel" id="tel_client" name="tel_client" required
                                   placeholder="34 00 000 00"
                                   aria-label="Numéro de téléphone"
                                   maxlength="13"
                                   inputmode="numeric">
                        </div>
                    <?php else: ?>
                        <!-- Security question verification -->
                        <p>Pour vérifier votre identité, veuillez répondre à votre question de sécurité :</p>

                        <div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                            <p style="font-style: italic; font-size: 16px; margin: 0;">
                                "<?php echo htmlspecialchars($_SESSION['reset_security_question'] ?? ''); ?>"
                            </p>
                        </div>

                        <div class="input-group">
                            <input type="text" id="answer" name="answer" required placeholder="Votre réponse"
                                   aria-label="Réponse à la question de sécurité" autocomplete="off">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9 12l2 2 4-4"/>
                                    <circle cx="12" cy="12" r="10"/>
                                </svg>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- Boutons de navigation -->
                    <div style="display: flex; gap: 12px; margin-top: 20px;">
                        <button type="submit" class="btn-submit" name="action" value="verify" style="flex: 1;">Vérifier</button>
                        <a href="login.php" style="flex: 1; text-align: center; display: block; padding: 16px; background: var(--color-card-bg); border: 1px solid var(--border-glass); border-radius: 12px; color: var(--color-text-white); text-decoration: none; font-weight: 500; transition: var(--transition-smooth);"
                           onmouseover="this.style.background='rgba(255,255,255,0.06)'; this.style.borderColor='var(--color-primary-gold)'; this.style.color='var(--color-primary-gold)';"
                           onmouseout="this.style.background='var(--color-card-bg)'; this.style.borderColor='var(--border-glass)'; this.style.color='var(--color-text-white);'">Annuler</a>
                    </div>

                <?php elseif ($_SESSION['reset_step'] === 4): ?>
                    <!-- ÉTAPE 4: Réinitialisation du mot de passe -->
                    <p>Entrez un nouveau mot de passe pour votre compte :</p>

                    <div class="input-group">
                        <input type="password" id="password" name="password" required placeholder="Nouveau mot de passe"
                               aria-label="Nouveau mot de passe" autocomplete="new-password">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                        </span>
                    </div>

                    <div class="input-group" style="margin-top: 12px;">
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirmer le nouveau mot de passe"
                               aria-label="Confirmer le nouveau mot de passe" autocomplete="new-password">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                        </span>
                    </div>

                    <!-- Boutons de navigation -->
                    <div style="display: flex; gap: 12px; margin-top: 20px;">
                        <button type="submit" class="btn-submit" name="action" value="reset_password" style="flex: 1;">Réinitialiser le mot de passe</button>
                        <a href="login.php" style="flex: 1; text-align: center; display: block; padding: 16px; background: var(--color-card-bg); border: 1px solid var(--border-glass); border-radius: 12px; color: var(--color-text-white); text-decoration: none; font-weight: 500; transition: var(--transition-smooth);"
                           onmouseover="this.style.background='rgba(255,255,255,0.06)'; this.style.borderColor='var(--color-primary-gold)'; this.style.color='var(--color-primary-gold)';"
                           onmouseout="this.style.background='var(--color-card-bg)'; this.style.borderColor='var(--border-glass)'; this.style.color='var(--color-text-white);'">Annuler</a>
                    </div>
                <?php endif; ?>

                <!-- Lien de retour vers la connexion -->
                <div class="signup-prompt" style="margin-top: 20px;">
                    Vous vous souvenez de votre mot de passe ? <a href="login.php">Se connecter</a>
                </div>

                <!-- Pied de page de la carte -->
                <footer class="card-footer">
                    <p>&copy; 2026 NY TIA SARY</p>
                    <p>Tous droits réservés.</p>
                </footer>
            </form>
        </div>
    </main>


    <!-- The Javascript is essential for all interactivity, loaded via footer.php -->
</body>
</html>