<?php
declare(strict_types=1);
/**
 * auth_guard.php — Middleware de protection des espaces sécurisés
 * Studio NY TIA SARY
 */

/**
 * Calcule dynamiquement l'URL de la page de login
 * en fonction de la profondeur du script appelant.
 */
function getLoginUrl(): string
{
    $scriptDir  = dirname($_SERVER['SCRIPT_FILENAME']);
    $projectDir = realpath(__DIR__ . '/..');
    $relative   = str_replace('\\', '/', str_replace($projectDir, '', $scriptDir));
    $depth      = count(array_filter(explode('/', trim($relative, '/'))));
    return str_repeat('../', $depth) . 'login/login.php';
}

/**
 * Protège une page réservée aux administrateurs.
 * Si la session admin n'est pas active, redirige vers le login.
 */
function requireAdmin(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . getLoginUrl());
        exit();
    }
}

/**
 * Protège une page réservée aux clients connectés.
 * Si la session client n'est pas active, redirige vers le login.
 */
function requireClient(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['client_id'])) {
        header('Location: ' . getLoginUrl());
        exit();
    }
}

/**
 * Retourne les initiales d'un nom complet (ex: "Jean Dupont" → "JD")
 */
function getInitiales(string $nom, string $prenom = ''): string
{
    $first = mb_strtoupper(mb_substr(trim($prenom ?: $nom), 0, 1));
    $second = $prenom ? mb_strtoupper(mb_substr(trim($nom), 0, 1)) : '';
    return $first . $second;
}

// Nouvelle fonction pour nettoyer la session et désactiver le cache du navigateur
/**
 * clearSessionAndCache
 *
 * Cette fonction supprime toutes les variables de session, détruit la session,
 * supprime le cookie de session (si utilisé) et envoie des en‑têtes HTTP afin
 * d’empêcher le cache du navigateur. Elle doit être appelée avant toute
 * redirection après la déconnexion.
 *
 * @return void
 */
function clearSessionAndCache(): void
{
    // Démarrer la session si elle n’est pas déjà active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Supprimer toutes les variables de session
    $_SESSION = [];

    // Supprimer le cookie de session si le serveur utilise les cookies de session
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Détruire la session côté serveur
    session_unset();
    session_destroy();

    // Empêcher le cache du navigateur
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}
