<?php
declare(strict_types=1);

/**
 * large_upload.php — Gestionnaire d'upload de fichiers volumineux (vidéos)
 * Studio NY TIA SARY
 *
 * Ce fichier est conçu spécifiquement pour les vidéos de grande taille (jusqu'à 2 Go).
 * Il utilise la même convention de nommage sécurisé que file_upload.php.
 *
 * ⚠️  IMPORTANT — Configuration PHP requise sur le serveur (php.ini ou .htaccess) :
 *
 *      upload_max_filesize = 2048M
 *      post_max_size       = 2100M
 *      max_execution_time  = 600
 *      max_input_time      = 600
 *      memory_limit        = 512M
 *
 *  En local (XAMPP) : modifiez C:\xampp\php\php.ini et redémarrez Apache.
 */

// ─── Constantes ────────────────────────────────────────────────────────────

/** Taille maximale autorisée pour les vidéos : 2 Go */
define('LARGE_UPLOAD_MAX_SIZE', 2 * 1024 * 1024 * 1024);

/** Taille maximale pour les images dans le contexte des médias : 50 Mo */
define('LARGE_UPLOAD_IMAGE_MAX_SIZE', 50 * 1024 * 1024);

/** Répertoire racine des uploads (identique à file_upload.php) */
if (!defined('UPLOAD_BASE_DIR')) {
    define('UPLOAD_BASE_DIR', realpath(__DIR__ . '/../assets') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
}

/** Types MIME autorisés pour les vidéos */
const ALLOWED_VIDEO_MIMES = [
    'video/mp4',
    'video/webm',
    'video/ogg',
    'video/quicktime',   // .mov
    'video/x-msvideo',  // .avi
    'video/x-matroska', // .mkv
];

/** Types MIME autorisés pour les images dans ce contexte */
const ALLOWED_MEDIA_IMAGE_MIMES = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
];

// ─── Fonctions publiques ────────────────────────────────────────────────────

/**
 * Upload un fichier vidéo volumineux de manière sécurisée.
 *
 * @param  array  $file    Entrée $_FILES['monchamp']
 * @param  string $subDir  Sous-répertoire cible (ex: 'medias')
 * @return array{success: bool, path: string|null, type: string|null, error: string|null}
 */
function uploadLargeVideo(array $file, string $subDir = 'medias'): array
{
    // 1. Erreur native PHP
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return _largeUploadError(_largeUploadErrorMessage($file['error']));
    }

    // 2. Vérifier la taille (2 Go max pour les vidéos)
    if ($file['size'] > LARGE_UPLOAD_MAX_SIZE) {
        $maxGo = round(LARGE_UPLOAD_MAX_SIZE / (1024 ** 3), 1);
        return _largeUploadError("La vidéo dépasse la taille maximale autorisée ({$maxGo} Go).");
    }

    // 3. Vérifier le type MIME réel
    $mime = _getRealMime($file['tmp_name']);
    if (!in_array($mime, ALLOWED_VIDEO_MIMES, true)) {
        return _largeUploadError("Format vidéo non autorisé ($mime). Formats acceptés : MP4, WebM, MOV, AVI, MKV.");
    }

    // 4. Créer le répertoire cible
    $targetDir = UPLOAD_BASE_DIR . trim($subDir, '/\\') . DIRECTORY_SEPARATOR;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        return _largeUploadError('Impossible de créer le répertoire de destination.');
    }

    // 5. Nom de fichier sécurisé + unique
    $ext        = _getVideoExtension($mime) ?? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeName   = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetPath = $targetDir . $safeName;

    // 6. Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return _largeUploadError("Échec du déplacement du fichier. Vérifiez les permissions du dossier.");
    }

    // 7. Chemin relatif depuis la racine du projet
    $relativePath = 'assets/uploads/' . trim($subDir, '/\\') . '/' . $safeName;

    return ['success' => true, 'path' => $relativePath, 'type' => 'VIDEO', 'error' => null];
}

/**
 * Upload une image dans le contexte des médias (avec limite plus haute que file_upload.php : 50 Mo).
 *
 * @param  array  $file    Entrée $_FILES['monchamp']
 * @param  string $subDir  Sous-répertoire cible
 * @return array{success: bool, path: string|null, type: string|null, error: string|null}
 */
function uploadMediaImage(array $file, string $subDir = 'medias'): array
{
    // 1. Erreur native PHP
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return _largeUploadError(_largeUploadErrorMessage($file['error']));
    }

    // 2. Vérifier la taille (50 Mo max pour les images)
    if ($file['size'] > LARGE_UPLOAD_IMAGE_MAX_SIZE) {
        $maxMo = round(LARGE_UPLOAD_IMAGE_MAX_SIZE / (1024 ** 2));
        return _largeUploadError("L'image dépasse la taille maximale autorisée ({$maxMo} Mo).");
    }

    // 3. Vérifier le type MIME réel
    $mime = _getRealMime($file['tmp_name']);
    if (!in_array($mime, ALLOWED_MEDIA_IMAGE_MIMES, true)) {
        return _largeUploadError("Format image non autorisé ($mime). Formats acceptés : JPEG, PNG, WebP, GIF.");
    }

    // 4. Créer le répertoire cible
    $targetDir = UPLOAD_BASE_DIR . trim($subDir, '/\\') . DIRECTORY_SEPARATOR;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        return _largeUploadError('Impossible de créer le répertoire de destination.');
    }

    // 5. Nom de fichier sécurisé + unique
    $ext        = _getImageExtension($mime) ?? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeName   = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetPath = $targetDir . $safeName;

    // 6. Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return _largeUploadError("Échec du déplacement du fichier. Vérifiez les permissions du dossier.");
    }

    // 7. Chemin relatif depuis la racine du projet
    $relativePath = 'assets/uploads/' . trim($subDir, '/\\') . '/' . $safeName;

    return ['success' => true, 'path' => $relativePath, 'type' => 'IMAGE', 'error' => null];
}

/**
 * Upload automatique : détecte si c'est une image ou une vidéo et applique la bonne fonction.
 *
 * @param  array  $file    Entrée $_FILES['monchamp']
 * @param  string $subDir  Sous-répertoire cible
 * @return array{success: bool, path: string|null, type: string|null, error: string|null}
 */
function uploadMedia(array $file, string $subDir = 'medias'): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return _largeUploadError(_largeUploadErrorMessage($file['error']));
    }

    $mime = _getRealMime($file['tmp_name']);

    if (in_array($mime, ALLOWED_VIDEO_MIMES, true)) {
        return uploadLargeVideo($file, $subDir);
    }

    if (in_array($mime, ALLOWED_MEDIA_IMAGE_MIMES, true)) {
        return uploadMediaImage($file, $subDir);
    }

    return _largeUploadError("Format non autorisé ($mime). Formats acceptés : JPEG, PNG, WebP, MP4, WebM, MOV, AVI, MKV.");
}

/**
 * Upload multiple de médias (images + vidéos).
 *
 * @param  array  $files   $_FILES['monchamp'] (format multiple PHP)
 * @param  string $subDir  Sous-répertoire cible
 * @return array  Liste de résultats uploadMedia()
 */
function uploadMultipleMedias(array $files, string $subDir = 'medias'): array
{
    $results = [];

    if (is_array($files['name'])) {
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ((int)$files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue; // Ignorer les champs vides
            }
            $single = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            $results[] = uploadMedia($single, $subDir);
        }
    } else {
        $results[] = uploadMedia($files, $subDir);
    }

    return $results;
}

// ─── Fonctions internes (privées) ──────────────────────────────────────────

function _getRealMime(string $tmpPath): string
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->file($tmpPath) ?: 'application/octet-stream';
}

function _getVideoExtension(string $mime): ?string
{
    return [
        'video/mp4'        => 'mp4',
        'video/webm'       => 'webm',
        'video/ogg'        => 'ogv',
        'video/quicktime'  => 'mov',
        'video/x-msvideo'  => 'avi',
        'video/x-matroska' => 'mkv',
    ][$mime] ?? null;
}

function _getImageExtension(string $mime): ?string
{
    return [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ][$mime] ?? null;
}

function _largeUploadError(string $message): array
{
    return ['success' => false, 'path' => null, 'type' => null, 'error' => $message];
}

function _largeUploadErrorMessage(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille autorisée par le serveur. Vérifiez php.ini (upload_max_filesize / post_max_size).',
        UPLOAD_ERR_PARTIAL                         => 'Le fichier n\'a été que partiellement téléversé.',
        UPLOAD_ERR_NO_FILE                         => 'Aucun fichier sélectionné.',
        UPLOAD_ERR_NO_TMP_DIR                      => 'Répertoire temporaire manquant.',
        UPLOAD_ERR_CANT_WRITE                      => 'Échec de l\'écriture sur le disque.',
        UPLOAD_ERR_EXTENSION                       => 'Upload bloqué par une extension PHP.',
        default                                    => 'Erreur d\'upload inconnue (code ' . $code . ').',
    };
}
