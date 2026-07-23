<?php
declare(strict_types=1);
/**
 * auth_guard.php — Middleware de protection des espaces sécurisés
 * Studio NY TIA SARY
 */

/**
 * Retourne l'URL absolue de la page de login.
 * RFC 7231 exige des URI absolus dans les headers Location.
 */
function getLoginUrl(): string
{
    // Construire le baseUrl à partir de la requête courante
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Chemin absolu vers login.php depuis la racine du projet
    $projectDir = realpath(__DIR__ . '/..');
    $loginPath  = str_replace('\\', '/', str_replace($projectDir, '', realpath(__DIR__ . '/../login'))) . '/login.php';
    // Retrouver le chemin web (REQUEST_URI moins le script)
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $webBase = $docRoot ? str_replace('\\', '/', str_replace($docRoot, '', $projectDir)) : '';
    return $scheme . '://' . $host . $webBase . $loginPath;
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
        // Stocker l'URL cible pour redirection post-login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        // Passer aussi en paramètre GET pour robustesse (si session non démarrée côté login)
        $loginUrl = getLoginUrl() . '?redirect=' . urlencode($_SERVER['REQUEST_URI']);
        header('Location: ' . $loginUrl);
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
        // Stocker l'URL cible pour redirection post-login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        // Passer aussi en paramètre GET pour robustesse
        $loginUrl = getLoginUrl() . '?redirect=' . urlencode($_SERVER['REQUEST_URI']);
        header('Location: ' . $loginUrl);
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
