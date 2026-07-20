<?php
// Adapter ce chemin si besoin (même dossier que celui utilisé dans prestations.php)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/util/mailService.php';

$pdo = getPDO();

//recuperer l'email de l'admin
    $stmt = $pdo->prepare('SELECT EMAIL_AUTH FROM AUTHENTIFICATION WHERE ROLE_AUTH = ?');
    $stmt->execute(["ADMIN"]);
    $email_admin = $stmt->fetchColumn();

// Redirige vers la bonne page selon l'origine de la soumission (public ou espace client)
function redirectVersIndex(bool $success, string $message, ?int $idDevis = null): void
{
    $params = [
        'devis'   => $success ? 'success' : 'error',
        'message' => $message,
    ];
    if ($idDevis !== null) {
        $params['id_devis'] = $idDevis;
    }

    // Si la demande vient de l'espace client, on redirige vers la page devis du dashboard
    $source = trim($_POST['source'] ?? '');
    if ($source === 'client') {
        header('Location: espace/client/devis.php?' . http_build_query($params));
    } else {
        header('Location: index.php?' . http_build_query($params) . '#devis');
    }
    exit;
}

// On n'accepte que POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectVersIndex(false, "Méthode non autorisée.");
}

/* ============================================================
   1. RÉCUPÉRATION & VALIDATION DES CHAMPS
   ============================================================ */

$nom            = trim($_POST['nom'] ?? '');
$prenom         = trim($_POST['prenom'] ?? '');
$email          = trim($_POST['email'] ?? '');
$telephone      = trim($_POST['telephone'] ?? '');
$typeVisiteur   = trim($_POST['type_visiteur'] ?? '');
$budget         = trim($_POST['budget'] ?? '');
$dateSouhaitee  = trim($_POST['date_souhaitee'] ?? '');
$description    = trim($_POST['description'] ?? '');
$idPrestation   = trim($_POST['id_prestation'] ?? '');
$categoriesPost = $_POST['id_categorie'] ?? []; // tableau (checkboxes)

$errors = [];

if ($nom === '') {
    $errors[] = "Le nom est requis.";
}
if ($prenom === '') {
    $errors[] = "Le prénom est requis.";
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "L'adresse email est invalide.";
}
if ($telephone === '') {
    $errors[] = "Le téléphone est requis.";
}

$typesAutorises = [
    'Entreprise', 'ONG', 'Institution', 'Collectivités', 'Couples',
    'Familles', "Organisateurs d'événement", 'Artistes',
    'Agences de communication', 'Particuliers',
];
$source = trim($_POST['source'] ?? '');
// Si ça vient de l'espace client, on accepte la valeur car elle vient de la DB
if ($source !== 'client' && !in_array($typeVisiteur, $typesAutorises, true)) {
    $errors[] = "Le type de visiteur sélectionné n'est pas valide.";
}

if ($description === '') {
    $errors[] = "La description est requise.";
}

if ($idPrestation === '' || !ctype_digit($idPrestation)) {
    $errors[] = "Veuillez sélectionner une prestation.";
}
$idPrestation = $idPrestation !== '' ? (int) $idPrestation : null;

// Catégories : optionnelles côté back, mais on filtre pour ne garder que des entiers valides
$idCategories = [];
if (is_array($categoriesPost)) {
    foreach ($categoriesPost as $cat) {
        $cat = trim((string) $cat);
        if ($cat !== '' && ctype_digit($cat)) {
            $idCategories[] = (int) $cat;
        }
    }
    $idCategories = array_unique($idCategories);
}

// Date souhaitée : optionnelle, mais si fournie elle doit être une date valide
if ($dateSouhaitee !== '') {
    $d = DateTime::createFromFormat('Y-m-d', $dateSouhaitee);
    if (!$d || $d->format('Y-m-d') !== $dateSouhaitee) {
        $errors[] = "La date souhaitée est invalide.";
    }
}

// Budget : optionnel, on le garde en texte (ex: "500 000 - 1 000 000") comme dans le schéma varchar
if (mb_strlen($budget) > 128) {
    $errors[] = "Le budget estimatif est trop long.";
}

/* ============================================================
   2. VALIDATION DU FICHIER JOINT (optionnel — "aucun" en base si absent)
   ============================================================ */

$uploadDir      = __DIR__ . '/assets/uploads/pieces_joint/';
$publicPathBase = 'assets/uploads/pieces_joint/'; // chemin stocké en base, relatif au site
$extensionsOk   = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'docx'];
$tailleMaxOctets = 10 * 1024 * 1024; // 10 Mo

$fichierValide  = false;
$cheminFinal    = null;
$cheminTmp      = null;

if (!empty($_FILES['piece_jointe']) && $_FILES['piece_jointe']['error'] !== UPLOAD_ERR_NO_FILE) {
    $fichier = $_FILES['piece_jointe'];

    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Erreur lors de l'envoi du fichier (code " . $fichier['error'] . ").";
    } elseif ($fichier['size'] > $tailleMaxOctets) {
        $errors[] = "Le fichier joint dépasse la taille maximale autorisée (10 Mo).";
    } else {
        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $extensionsOk, true)) {
            $errors[] = "Type de fichier non autorisé (formats acceptés : " . implode(', ', $extensionsOk) . ").";
        } else {
            $nomUnique   = bin2hex(random_bytes(16)) . '.' . $extension;
            $cheminTmp   = $fichier['tmp_name'];
            $cheminFinal = $uploadDir . $nomUnique;
            $cheminPublic = $publicPathBase . $nomUnique;
            $fichierValide = true;
        }
    }
}

/* ============================================================
   3. ARRÊT SI ERREURS DE VALIDATION
   ============================================================ */

if (!empty($errors)) {
    redirectVersIndex(false, implode(' ', $errors));
}

/* ============================================================
   4. INSERTION EN BASE (PDO + TRANSACTION)
   ============================================================ */

try {
    $pdo->beginTransaction();

    // 4.1 Récupérer le nom de la prestation
    $stmtPrestation = $pdo->prepare('SELECT LIB_PRESTATION FROM prestations WHERE ID_PRESTATION = ?');
    $stmtPrestation->execute([$idPrestation]);
    $prestationLib = $stmtPrestation->fetchColumn() ?: 'Non spécifiée';

    // 4.2 Récupérer les noms des catégories
    $categoriesLib = [];
    if (!empty($idCategories)) {
        $stmtCats = $pdo->prepare('SELECT LIB_CATEGORIE FROM categorie WHERE ID_CATEGORIE = ?');
        foreach ($idCategories as $idCat) {
            $stmtCats->execute([$idCat]);
            $libCat = $stmtCats->fetchColumn();
            if ($libCat) {
                $categoriesLib[] = $libCat;
            }
        }
    }

    // 4.3 Insertion du devis
    $sqlDevis = "INSERT INTO devis
                    (NOM, PRENOMS, EMAIL, TELEPHONE, TYPE_VISITEUR, BUGET_ESTIMATIF, DATE_SOUHAITE, DESCRIPTION, ID_PRESTATION)
                 VALUES
                    (:nom, :prenom, :email, :telephone, :type_visiteur, :budget, :date_souhaitee, :description, :id_prestation)";

    $stmtDevis = $pdo->prepare($sqlDevis);
    $stmtDevis->execute([
        ':nom'            => $nom,
        ':prenom'         => $prenom,
        ':email'          => $email,
        ':telephone'      => $telephone,
        ':type_visiteur'  => $typeVisiteur,
        ':budget'         => $budget !== '' ? $budget : null,
        ':date_souhaitee' => $dateSouhaitee !== '' ? $dateSouhaitee : null,
        ':description'    => $description,
        ':id_prestation'  => $idPrestation,
    ]);

    $idDevis = (int) $pdo->lastInsertId();

    // 4.4 Insertion des catégories choisies (table de liaison devis_categories)
    //     Un visiteur peut cocher une ou plusieurs catégories -> une ligne par catégorie.
    if (!empty($idCategories)) {
        $sqlCategorie  = "INSERT INTO devis_categories (ID_DEVIS, ID_CATEGORIE) VALUES (:id_devis, :id_categorie)";
        $stmtCategorie = $pdo->prepare($sqlCategorie);

        foreach ($idCategories as $idCategorie) {
            $stmtCategorie->execute([
                ':id_devis'     => $idDevis,
                ':id_categorie' => $idCategorie,
            ]);
        }
    }

    // 4.5 Upload physique (si un fichier a été fourni) + insertion de la pièce jointe
    //     -> une ligne est TOUJOURS créée dans pieces_jointes, avec PATH_PIECE = "aucun"
    //        si le visiteur n'a rien joint.
    $cheminPublicFinal = 'aucun';

    if ($fichierValide) {
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!move_uploaded_file($cheminTmp, $cheminFinal)) {
            throw new RuntimeException("Impossible d'enregistrer le fichier joint sur le serveur.");
        }

        $cheminPublicFinal = $cheminPublic;
    }

    $sqlPiece = "INSERT INTO pieces_jointes (ID, PATH_PIECE)
                 VALUES (:id_devis, :path_piece)";
    $stmtPiece = $pdo->prepare($sqlPiece);
    $stmtPiece->execute([
        ':id_devis'   => $idDevis,
        ':path_piece' => $cheminPublicFinal,
    ]);

    // 4.6 Insertion de la notification (pour prévenir l'admin dans le dashboard)
    $sqlNotif = "INSERT INTO notification
                    (TYPE_NOTIF, ID_REF_NOTIF, TITRE_NOTIF, MESS_NOTIF, LU_NOTIF, SUP_NOTIF)
                 VALUES
                    (:type_notif, :id_ref_notif, :titre_notif, :mess_notif, :lu_notif, :sup_notif)";
    // DATE_NOTIF n'est pas fourni : la colonne a un défaut CURRENT_TIMESTAMP côté base.

    $stmtNotif = $pdo->prepare($sqlNotif);
    $stmtNotif->execute([
        ':type_notif'   => 'devis',
        ':id_ref_notif' => $idDevis,
        ':titre_notif'  => 'Demande de devis',
        ':mess_notif'   => "Notification de {$nom} {$prenom}",
        ':lu_notif'     => 0,
        ':sup_notif'    => 0,
    ]);

    $pdo->commit();

    // 4.7 Envoi d'un email de notification à l'administrateur
    $adminEmail = $email_admin;

    $devisData = [
        'client_nom' => $nom,
        'client_prenom' => $prenom,
        'email' => $email,
        'telephone' => $telephone,
        'type_visiteur' => $typeVisiteur,
        'prestation' => $prestationLib,
        'date_souhaitee' => $dateSouhaitee ?: 'Non définie',
        'budget' => $budget ?: 'Non défini',
        'description' => $description,
        'categories' => $categoriesLib,
        'id_devis' => $idDevis,
        'piece_jointe' => $cheminPublicFinal !== 'aucun' ? $cheminPublicFinal : null,
    ];

    $mailService = new MailService();
    $mailService->sendDevisNew($adminEmail, $devisData);

    redirectVersIndex(true, "Votre demande de devis a bien été envoyée. Nous vous recontacterons rapidement.", $idDevis);

} catch (Throwable $e) {
    $pdo->rollBack();

    // Si le fichier a été déplacé avant l'échec de l'insertion, on le supprime
    if ($fichierValide && $cheminFinal && file_exists($cheminFinal)) {
        unlink($cheminFinal);
    }

    // En dev tu peux temporairement afficher $e->getMessage() pour déboguer.
    // En production, ne jamais exposer le détail de l'erreur SQL au client.
    error_log('[traitement_devis.php] ' . $e->getMessage());

    // TEMPORAIRE — À RETIRER après debug
    redirectVersIndex(false, "DEBUG: " . $e->getMessage());
}