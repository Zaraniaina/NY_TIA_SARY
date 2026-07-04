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
