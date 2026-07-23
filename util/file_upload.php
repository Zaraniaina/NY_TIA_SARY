<?php
declare(strict_types=1);

/**
 * file_upload.php — Gestionnaire d'upload de fichiers sécurisé
 * Studio NY TIA SARY
 */

// Répertoire racine des uploads (créé automatiquement si absent)
define('UPLOAD_BASE_DIR', realpath(__DIR__ . '/../assets') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

// Taille maximale autorisée : 10 Mo
define('MAX_FILE_SIZE', 10 * 1024 * 1024);

// Types MIME autorisés par catégorie
const ALLOWED_TYPES = [
    'image'    => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    'document' => ['application/pdf', 'application/msword',
                   'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'any'      => [], // vide = tous les types autorisés ci-dessus réunis
];

/**
 * Upload un fichier de manière sécurisée.
 *
 * @param  array  $file       Entrée $_FILES['monchamp']
 * @param  string $subDir     Sous-répertoire cible (ex: 'devis', 'blog', 'photos')
 * @param  string $category   Catégorie de types autorisés : 'image', 'document', 'any'
 * @return array{success: bool, path: string|null, error: string|null}
 */
function uploadFile(array $file, string $subDir = 'general', string $category = 'any'): array
{
    // 1. Vérifier s'il y a une erreur native PHP
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => null, 'error' => uploadErrorMessage($file['error'])];
    }

    // 2. Vérifier la taille
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'path' => null, 'error' => 'Le fichier dépasse la taille maximale de 10 Mo.'];
    }

    // 3. Vérifier le type MIME réel (pas celui du client)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $allowed = getAllowedMimes($category);
    if (!empty($allowed) && !in_array($mimeType, $allowed, true)) {
        return ['success' => false, 'path' => null, 'error' => "Type de fichier non autorisé ($mimeType)."];
    }

    // 4. Construire le répertoire cible
    $targetDir = UPLOAD_BASE_DIR . trim($subDir, '/\\') . DIRECTORY_SEPARATOR;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        return ['success' => false, 'path' => null, 'error' => 'Impossible de créer le répertoire de destination.'];
    }

    // 5. Générer un nom de fichier sécurisé et unique
    $ext        = getExtensionFromMime($mimeType) ?? pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeName   = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetPath = $targetDir . $safeName;

    // 6. Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'path' => null, 'error' => "Échec du déplacement du fichier."];
    }

    // 7. Retourner le chemin relatif depuis la racine du projet
    $relativePath = 'assets/uploads/' . trim($subDir, '/\\') . '/' . $safeName;

    return ['success' => true, 'path' => $relativePath, 'error' => null];
}

/**
 * Upload multiple de fichiers (ex: galerie, pièces jointes).
 * Retourne un tableau de résultats individuels.
 *
 * @param  array  $files     $_FILES['monchamp'] restructuré ou tableau déjà normalisé
 * @param  string $subDir    Sous-répertoire cible
 * @param  string $category  Catégorie de types autorisés
 * @return array  Liste de résultats uploadFile()
 */
function uploadMultipleFiles(array $files, string $subDir = 'general', string $category = 'any'): array
{
    $results = [];

    // Normalise le tableau si c'est le format $_FILES multiple (tableau de tableaux)
    if (is_array($files['name'])) {
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            $single = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            $results[] = uploadFile($single, $subDir, $category);
        }
    } else {
        $results[] = uploadFile($files, $subDir, $category);
    }

    return $results;
}

/**
 * Supprime un fichier uploadé à partir de son chemin relatif.
 * Délègue à delete_file.php pour centraliser la logique de suppression sécurisée.
 */
function deleteUploadedFile(string $relativePath): bool
{
    // Chargement paresseux de delete_file.php si pas encore inclus
    if (!function_exists('deleteFile')) {
        require_once __DIR__ . '/delete_file.php';
    }
    return deleteFile($relativePath);
}

// ─── Fonctions internes ────────────────────────────────────────────────────

function getAllowedMimes(string $category): array
{
    if ($category === 'any') {
        return array_merge(ALLOWED_TYPES['image'], ALLOWED_TYPES['document']);
    }
    return ALLOWED_TYPES[$category] ?? [];
}

function getExtensionFromMime(string $mime): ?string
{
    $map = [
        'image/jpeg'    => 'jpg',
        'image/png'     => 'png',
        'image/webp'    => 'webp',
        'image/gif'     => 'gif',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];
    return $map[$mime] ?? null;
}

function uploadErrorMessage(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille autorisée.',
        UPLOAD_ERR_PARTIAL                         => 'Le fichier n\'a été que partiellement téléversé.',
        UPLOAD_ERR_NO_FILE                         => 'Aucun fichier sélectionné.',
        UPLOAD_ERR_NO_TMP_DIR                      => 'Répertoire temporaire manquant.',
        UPLOAD_ERR_CANT_WRITE                      => 'Échec de l\'écriture sur le disque.',
        UPLOAD_ERR_EXTENSION                       => 'Upload bloqué par une extension PHP.',
        default                                    => 'Erreur d\'upload inconnue.',
    };
}
