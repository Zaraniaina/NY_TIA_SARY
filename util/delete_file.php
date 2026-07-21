<?php
declare(strict_types=1);

/**
 * delete_file.php — Gestionnaire de suppression de fichiers sécurisé
 * Studio NY TIA SARY
 *
 * Ce fichier centralise TOUTE suppression de fichiers uploadés dans le projet.
 * Il protège contre les attaques Path Traversal (ex: ../../etc/passwd).
 */

// Chemin absolu de la racine des uploads — doit correspondre à UPLOAD_BASE_DIR dans file_upload.php
define('DELETE_BASE_DIR', realpath(__DIR__ . '/../assets/uploads'));

/**
 * Supprime un fichier uploadé de manière sécurisée à partir de son chemin relatif.
 *
 * @param  string|null $relativePath  Chemin relatif depuis la racine du projet
 *                                    (ex: "assets/uploads/avatars/abc.jpg")
 * @param  string[]    $protectedPaths Chemins à ne jamais supprimer (ex: photo par défaut)
 * @return bool  true si supprimé, false si ignoré ou introuvable
 */
function deleteFile(?string $relativePath, array $protectedPaths = []): bool
{
    // 1. Chemin vide ou nul → rien à faire
    if (empty($relativePath)) {
        return false;
    }

    // 2. Protection contre les chemins réservés (ex: avatar par défaut)
    foreach ($protectedPaths as $protected) {
        if (rtrim($relativePath, '/\\') === rtrim($protected, '/\\')) {
            return false;
        }
    }

    // 3. Construire le chemin absolu et le résoudre
    $projectRoot = realpath(__DIR__ . '/..');
    $normalized  = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    $fullPath    = $projectRoot . DIRECTORY_SEPARATOR . ltrim($normalized, DIRECTORY_SEPARATOR);
    $realFull    = realpath($fullPath);

    // 4. Vérification Path Traversal : le fichier doit être dans assets/uploads/
    if ($realFull === false) {
        // Le fichier n'existe pas physiquement — on considère que c'est OK (déjà supprimé)
        return false;
    }

    if (!str_starts_with($realFull, DELETE_BASE_DIR . DIRECTORY_SEPARATOR)) {
        // Tentative de sortie du dossier uploads → refus silencieux
        error_log("[delete_file.php] Tentative de suppression hors uploads : $realFull");
        return false;
    }

    // 5. Suppression effective
    if (file_exists($realFull) && is_file($realFull)) {
        return unlink($realFull);
    }

    return false;
}

/**
 * Supprime plusieurs fichiers d'un coup.
 *
 * @param  string[] $relativePaths  Tableau de chemins relatifs
 * @param  string[] $protectedPaths Chemins à ne jamais supprimer
 * @return int  Nombre de fichiers supprimés avec succès
 */
function deleteFiles(array $relativePaths, array $protectedPaths = []): int
{
    $count = 0;
    foreach ($relativePaths as $path) {
        if (deleteFile($path, $protectedPaths)) {
            $count++;
        }
    }
    return $count;
}
