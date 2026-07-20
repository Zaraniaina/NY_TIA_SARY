<?php
declare(strict_types=1);

function redirectionClient( string $url): void{
    header("Location: " . $url);
    exit();
}

/**
 * Vérifie si une URL de redirection est sécurisée (locale, pas d'Open Redirect).
 * Elle doit commencer par un seul '/' et ne pas contenir de host ou de schéma.
 */
function isSafeRedirect(string $url): bool
{
    if (empty($url)) {
        return false;
    }
    // L'URL doit commencer par un seul '/' (ex: /projet_ny/...) et ne pas être '//' ou '\'
    if (preg_match('#^/[^/\\\\]#', $url) || $url === '/') {
        $parts = parse_url($url);
        return empty($parts['host']) && empty($parts['scheme']);
    }
    return false;
}