<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
require_once __DIR__ . '/../../util/prg_helper.php';
requireAdmin();
require_once __DIR__ . '/../../util/file_upload.php';

require_once __DIR__.'/composante/tolbarDto.php';
//on changer le titre
$titre="Paramètre";

// ── TRAITEMENT POST (PRG Pattern) ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ACTION : Gestion des types de blog (CRUD)
    if ($_POST['action'] === 'crud_type_blog') {
        $subAction = $_POST['sub_action'] ?? '';
        $idType = (int) ($_POST['id_type_blog'] ?? 0);
        $libType = trim($_POST['lib_type_blog'] ?? '');

        if ($subAction === 'create') {
            if (!$libType) { prg_set_message('error', 'Le nom du type de blog est requis.'); }
            else {
                $pdo->prepare('INSERT INTO TYPE_BLOG (LIB_TYPE_BLOG) VALUES (?)')->execute([$libType]);
                prg_set_message('success', "Type de blog « $libType » ajouté.");
            }
            prg_redirect();
        } elseif ($subAction === 'edit' && $idType) {
            if (!$libType) { prg_set_message('error', 'Le nom du type de blog est requis.'); }
            else {
                $pdo->prepare('UPDATE TYPE_BLOG SET LIB_TYPE_BLOG = ? WHERE ID_TYPE_BLOG = ?')->execute([$libType, $idType]);
                prg_set_message('success', "Type de blog mis à jour.");
            }
            prg_redirect();
        } elseif ($subAction === 'delete' && $idType) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM BLOG WHERE ID_TYPE_BLOG = ?');
            $stmt->execute([$idType]);
            $count = (int) $stmt->fetchColumn();
            if ($count > 0) {
                prg_set_message('error', "Impossible de supprimer : $count article(s) utilisent ce type de blog.");
            } else {
                $pdo->prepare('DELETE FROM TYPE_BLOG WHERE ID_TYPE_BLOG = ?')->execute([$idType]);
                prg_set_message('success', "Type de blog supprimé.");
            }
            prg_redirect();
        }
    }

    // ACTION : Mettre à jour les informations personnelles
    if ($_POST['action'] === 'update_info') {
        $nom    = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $tel    = trim($_POST['telephone'] ?? '');
        $email  = trim($_POST['email'] ?? '');

        if ($nom && $prenom && $tel && $email) {
            try {
                $pdo->beginTransaction();

                $stmtCheck = $pdo->prepare("SELECT ID_AUTH FROM AUTHENTIFICATION WHERE EMAIL_AUTH = ? AND ID_AUTH != ?");
                $stmtCheck->execute([$email, $authId]);
                if ($stmtCheck->fetch()) {
                    throw new Exception("L'adresse email est déjà utilisée.");
                }

                $stmtUpdateAuth = $pdo->prepare("UPDATE AUTHENTIFICATION SET EMAIL_AUTH = ? WHERE ID_AUTH = ?");
                $stmtUpdateAuth->execute([$email, $authId]);

                // Check for TEL uniqueness in CLIENT
                $stmtCheckTel = $pdo->prepare("SELECT ID_CLIENT FROM CLIENT WHERE TEL_CLIENT = ? AND ID_CLIENT != ?");
                $stmtCheckTel->execute([$tel, $adminId]);
                if ($stmtCheckTel->fetch()) {
                    throw new Exception("Ce numéro de téléphone est déjà utilisé.");
                }

                $stmtUpdateClient = $pdo->prepare("UPDATE CLIENT SET NOM_CLIENT = ?, PRENOM_CLIENT = ?, TEL_CLIENT = ? WHERE ID_CLIENT = ?");
                $stmtUpdateClient->execute([$nom, $prenom, $tel, $adminId]);

                $pdo->commit();
                
                $_SESSION['admin_nom'] = $nom;
                $_SESSION['admin_prenom'] = $prenom;
                $_SESSION['admin_email'] = $email;

                prg_set_message('success', "Vos informations ont été mises à jour avec succès.");
            } catch (Exception $e) {
                $pdo->rollBack();
                prg_set_message('error', "Erreur : " . $e->getMessage());
            }
        } else {
            prg_set_message('error', "Veuillez remplir tous les champs obligatoires.");
        }
        prg_redirect();
    }

    // ACTION : Mettre à jour le mot de passe
    if ($_POST['action'] === 'update_password') {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if ($old_pass && $new_pass && $confirm_pass) {
            if ($new_pass === $confirm_pass) {
                $stmt = $pdo->prepare("SELECT MDP_AUTH FROM AUTHENTIFICATION WHERE ID_AUTH = ?");
                $stmt->execute([$authId]);
                $auth = $stmt->fetch();

                if ($auth && password_verify($old_pass, $auth['MDP_AUTH'])) {
                    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmtUpdate = $pdo->prepare("UPDATE AUTHENTIFICATION SET MDP_AUTH = ? WHERE ID_AUTH = ?");
                    if ($stmtUpdate->execute([$new_hash, $authId])) {
                        prg_set_message('success', "Votre mot de passe a été modifié avec succès.");
                    } else {
                        prg_set_message('error', "Une erreur est survenue lors de la modification du mot de passe.");
                    }
                } else {
                    prg_set_message('error', "L'ancien mot de passe est incorrect.");
                }
            } else {
                prg_set_message('error', "Les nouveaux mots de passe ne correspondent pas.");
            }
        } else {
            prg_set_message('error', "Veuillez remplir tous les champs obligatoires.");
        }
        prg_redirect();
    }

    // ACTION : Uploader / Changer la photo de profil
    if ($_POST['action'] === 'update_photo' && isset($_FILES['photo_profil'])) {
        $result = uploadFile($_FILES['photo_profil'], 'avatars', 'image');
        
        if ($result['success']) {
            $newPath = $result['path'];
            
            $stmt = $pdo->prepare("SELECT PHOTO_CLIENT FROM CLIENT WHERE ID_CLIENT = ?");
            $stmt->execute([$adminId]);
            $client = $stmt->fetch();
            
            if ($client && $client['PHOTO_CLIENT'] && $client['PHOTO_CLIENT'] !== 'assets/images/avatar.png') {
                deleteUploadedFile($client['PHOTO_CLIENT']);
            }
            
            $stmtUpdate = $pdo->prepare("UPDATE CLIENT SET PHOTO_CLIENT = ? WHERE ID_CLIENT = ?");
            if ($stmtUpdate->execute([$newPath, $adminId])) {
                prg_set_message('success', "Votre photo de profil a été mise à jour.");
            } else {
                prg_set_message('error', "Erreur lors de la mise à jour de la base de données.");
            }
        } else {
            prg_set_message('error', $result['error']);
        }
        prg_redirect();
    }

    // ACTION : Supprimer la photo de profil
    if ($_POST['action'] === 'delete_photo') {
        $stmt = $pdo->prepare("SELECT PHOTO_CLIENT FROM CLIENT WHERE ID_CLIENT = ?");
        $stmt->execute([$adminId]);
        $client = $stmt->fetch();
        
        if ($client && $client['PHOTO_CLIENT'] && $client['PHOTO_CLIENT'] !== 'assets/images/avatar.png') {
            deleteUploadedFile($client['PHOTO_CLIENT']);
            $stmtUpdate = $pdo->prepare("UPDATE CLIENT SET PHOTO_CLIENT = 'assets/images/avatar.png' WHERE ID_CLIENT = ?");
            if ($stmtUpdate->execute([$adminId])) {
                prg_set_message('success', "Votre photo de profil a été supprimée.");
            } else {
                prg_set_message('error', "Erreur lors de la suppression dans la base de données.");
            }
        } else {
             prg_set_message('error', "Vous utilisez déjà la photo par défaut.");
        }
        prg_redirect();
    }

    // ── ACTION : CRUD Partenaire ──────────────────────────────────────────────
    if ($_POST['action'] === 'crud_partenaire') {
        $subAction    = $_POST['sub_action'] ?? '';
        $idPartenaire = (int) ($_POST['id_partenaire'] ?? 0);
        $description  = trim($_POST['description_partenaire'] ?? '');
        $lien         = trim($_POST['lien_partenaire'] ?? '');

        if ($subAction === 'create') {
            if (!$description || !$lien) {
                prg_set_message('error', 'La description et le lien du partenaire sont requis.');
                prg_redirect();
            }
            if (empty($_FILES['logo_partenaire']['name'])) {
                prg_set_message('error', 'Le logo du partenaire est requis.');
                prg_redirect();
            }
            $upload = uploadFile($_FILES['logo_partenaire'], 'partenaires', 'image');
            if (!$upload['success']) {
                prg_set_message('error', 'Erreur logo : ' . $upload['error']);
                prg_redirect();
            }
            $pdo->prepare(
                'INSERT INTO partenaire (PATH_LOGO, DESCRIPTIONS, LIEN_PARTENAIRE) VALUES (?, ?, ?)'
            )->execute([$upload['path'], $description, $lien]);
            prg_set_message('success', "Partenaire ajouté avec succès.");
            prg_redirect();

        } elseif ($subAction === 'edit' && $idPartenaire) {
            if (!$description || !$lien) {
                prg_set_message('error', 'La description et le lien sont requis.');
                prg_redirect();
            }
            $stmtOld = $pdo->prepare('SELECT PATH_LOGO FROM partenaire WHERE ID_PARTENAIRE = ?');
            $stmtOld->execute([$idPartenaire]);
            $oldLogo = $stmtOld->fetchColumn();

            $newLogoPath = $oldLogo;
            if (!empty($_FILES['logo_partenaire']['name'])) {
                $upload = uploadFile($_FILES['logo_partenaire'], 'partenaires', 'image');
                if (!$upload['success']) {
                    prg_set_message('error', 'Erreur logo : ' . $upload['error']);
                    prg_redirect();
                }
                if ($oldLogo) { deleteUploadedFile($oldLogo); }
                $newLogoPath = $upload['path'];
            }
            $pdo->prepare(
                'UPDATE partenaire SET PATH_LOGO = ?, DESCRIPTIONS = ?, LIEN_PARTENAIRE = ? WHERE ID_PARTENAIRE = ?'
            )->execute([$newLogoPath, $description, $lien, $idPartenaire]);
            prg_set_message('success', 'Partenaire mis à jour.');
            prg_redirect();

        } elseif ($subAction === 'delete' && $idPartenaire) {
            $stmtOld = $pdo->prepare('SELECT PATH_LOGO FROM partenaire WHERE ID_PARTENAIRE = ?');
            $stmtOld->execute([$idPartenaire]);
            $oldLogo = $stmtOld->fetchColumn();
            if ($oldLogo) { deleteUploadedFile($oldLogo); }
            $pdo->prepare('DELETE FROM partenaire WHERE ID_PARTENAIRE = ?')->execute([$idPartenaire]);
            prg_set_message('success', 'Partenaire supprimé.');
            prg_redirect();
        }
    }

    // ── ACTION : CRUD Contact Entreprise (un seul actif) ──────────────────────
    if ($_POST['action'] === 'crud_contact') {
        $subAction = $_POST['sub_action'] ?? '';
        $adresse   = trim($_POST['adresse_contact'] ?? '');
        $tel       = trim($_POST['tel_contact'] ?? '');
        $whatsapp  = trim($_POST['whatsapp_lien'] ?? '');
        $messenger = trim($_POST['messenger_lien'] ?? '');
        $emailCt   = trim($_POST['email_contact'] ?? '');
        $horaire   = trim($_POST['horaire_contact'] ?? '');

        if (!$adresse || !$tel || !$emailCt || !$horaire) {
            prg_set_message('error', 'Veuillez remplir tous les champs obligatoires du contact.');
            prg_redirect();
        }

        if ($subAction === 'create') {
            $count = (int) $pdo->query('SELECT COUNT(*) FROM contact')->fetchColumn();
            if ($count > 0) {
                prg_set_message('error', 'Un contact entreprise existe déjà. Veuillez le modifier.');
            } else {
                $pdo->prepare(
                    'INSERT INTO contact (ADRESSE_CONTACT, TEL_CONTACT, WHATSAPP_LIEN, MESSENGER_LIEN, EMAIL_CONTACT, HORAIRE_CONTACT)
                     VALUES (?, ?, ?, ?, ?, ?)'
                )->execute([$adresse, $tel, $whatsapp, $messenger, $emailCt, $horaire]);
                prg_set_message('success', 'Contact entreprise créé avec succès.');
            }
            prg_redirect();

        } elseif ($subAction === 'edit') {
            $idContact = (int) ($_POST['id_contact'] ?? 0);
            if (!$idContact) {
                prg_set_message('error', 'Identifiant du contact invalide.');
                prg_redirect();
            }
            $pdo->prepare(
                'UPDATE contact SET ADRESSE_CONTACT = ?, TEL_CONTACT = ?, WHATSAPP_LIEN = ?, MESSENGER_LIEN = ?, EMAIL_CONTACT = ?, HORAIRE_CONTACT = ?
                 WHERE ID_CONTACT = ?'
            )->execute([$adresse, $tel, $whatsapp, $messenger, $emailCt, $horaire, $idContact]);
            prg_set_message('success', 'Contact entreprise mis à jour.');
            prg_redirect();
        }
    }
}

// ── Récupérer les messages PRG pour affichage ──────────────────
$prgMessages = prg_get_messages();

// Get authId for password operations
$stmtAuthId = $pdo->prepare("SELECT ID_AUTH FROM CLIENT WHERE ID_CLIENT = ?");
$stmtAuthId->execute([$adminId]);
$authId = (int) $stmtAuthId->fetchColumn();

// ── RECUPERATION DES DONNEES ──────────────────────────────────────────

// Récupérer les données pour l'édition d'un type de blog
$editTypeBlog = null;
if (isset($_GET['edit_type'])) {
    $stmt = $pdo->prepare('SELECT * FROM TYPE_BLOG WHERE ID_TYPE_BLOG = ?');
    $stmt->execute([(int)$_GET['edit_type']]);
    $editTypeBlog = $stmt->fetch();
}

// Récupérer les types de blog
$typesBlog = $pdo->query('SELECT * FROM TYPE_BLOG ORDER BY LIB_TYPE_BLOG')->fetchAll();

// Récupérer les données admin pour l'affichage
$stmtAdmin = $pdo->prepare("
    SELECT c.*, a.EMAIL_AUTH 
    FROM CLIENT c 
    JOIN AUTHENTIFICATION a ON c.ID_AUTH = a.ID_AUTH 
    WHERE c.ID_CLIENT = ?
");
$stmtAdmin->execute([$adminId]);
$adminData = $stmtAdmin->fetch();

$adminNom    = $adminData['NOM_CLIENT'] ?? 'Admin';
$adminPrenom = $adminData['PRENOM_CLIENT'] ?? '';
$initiales    = getInitiales($adminNom, $adminPrenom);
$photoAdmin  = $adminData['PHOTO_CLIENT'] ?? 'assets/images/avatar.png';
$isDefaultPhoto = ($photoAdmin === 'assets/images/avatar.png');

$adminEmail = $_SESSION['admin_email'] ?? $adminData['EMAIL_AUTH'] ?? 'Admin';

// Récupérer les partenaires
$partenaires = $pdo->query('SELECT * FROM partenaire ORDER BY ID_PARTENAIRE DESC')->fetchAll();
$editPartenaire = null;
if (isset($_GET['edit_partenaire'])) {
    $stmtP = $pdo->prepare('SELECT * FROM partenaire WHERE ID_PARTENAIRE = ?');
    $stmtP->execute([(int)$_GET['edit_partenaire']]);
    $editPartenaire = $stmtP->fetch();
}

// Récupérer le contact entreprise (un seul actif)
$contact = $pdo->query('SELECT * FROM contact LIMIT 1')->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres | Admin NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">

    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

</head>
<body>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/composante/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="dashboard-main">
     <?php include __DIR__ . '/composante/tolbar.php'; ?>

        <div class="dashboard-content">
            <nav class="dash-breadcrumb">
                <a href="home.php">Dashboard</a>
                <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Paramètres</span>
            </nav>

            <div class="dash-page-header">
                <h2>Paramètres</h2>
                <p>Gérez votre compte administrateur et vos préférences</p>
            </div>

            <!-- ONGLETS / CARDS -->
            <div class="form-grid-2">
                <!-- COLONNE GAUCHE -->
                <div>
                    <!-- CARTE : PHOTO DE PROFIL -->
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3><i class="fas fa-user-circle"></i> Photo de Profil</h3>
                        </div>
                        <div class="dash-card-body padded">
                            <div style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 20px;">
                                  <div style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; border: 3px solid var(--light-green); flex-shrink: 0; display:flex; align-items:center; justify-content:center; background:var(--primary-green); color:var(--white); font-family:var(--font-headings); font-weight:700; font-size: 2rem;">
                                    <img src="../../<?= htmlspecialchars($photoAdmin) ?>" alt="Avatar" class="topbar-avatar" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div style="flex:1;">
                                    <p style="font-size:0.85rem; color:#666; margin-bottom:15px;">Une photo de profil aide à personnaliser votre compte administrateur.</p>
                                    
                                    <button type="button" class="btn-dash btn-dash-outline btn-dash-sm" id="openPhotoModal">
                                        <i class="fas fa-camera"></i> Modifier la photo
                                    </button>
                                    
                                    <?php if (!$isDefaultPhoto): ?>
                                        <button type="button" class="btn-dash btn-dash-danger btn-dash-sm" id="openDeletePhotoModal" style="margin-top: 10px;">
                                            <i class="fas fa-trash-alt"></i> Supprimer la photo
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLONNE DROITE -->
                <div>
                    <!-- CARTE : INFORMATIONS PERSONNELLES -->
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3><i class="fas fa-user"></i> Informations Personnelles</h3>
                        </div>
                        <div class="dash-card-body padded">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                                <div>
                                    <p style="font-size:0.9rem; color:#666; margin-bottom:5px;"><strong>Nom :</strong> <?= htmlspecialchars($adminData['NOM_CLIENT'] ?? '') ?></p>
                                    <p style="font-size:0.9rem; color:#666; margin-bottom:5px;"><strong>Prénom :</strong> <?= htmlspecialchars($adminData['PRENOM_CLIENT'] ?? '') ?></p>
                                    <p style="font-size:0.9rem; color:#666; margin-bottom:5px;"><strong>Téléphone :</strong> <?= htmlspecialchars($adminData['TEL_CLIENT'] ?? '') ?></p>
                                    <p style="font-size:0.9rem; color:#666;"><strong>E-mail :</strong> <?= htmlspecialchars($adminData['EMAIL_AUTH'] ?? '') ?></p>
                                </div>
                                <button type="button" class="btn-dash btn-dash-primary btn-dash-sm" id="openInfoModal">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- CARTE : SÉCURITÉ (MOT DE PASSE) -->
                    <div class="dash-card" style="margin-top: 30px;">
                        <div class="dash-card-header">
                            <h3><i class="fas fa-shield-alt"></i> Sécurité du compte</h3>
                        </div>
                        <div class="dash-card-body padded">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <div>
                                    <p style="font-size:0.9rem; color:#666; margin-bottom:5px;"><strong>Mot de passe :</strong> <span style="color:#666;">********</span></p>
                                </div>
                                <button type="button" class="btn-dash btn-dash-primary btn-dash-sm" id="openPasswordModal">
                                    <i class="fas fa-key"></i> Modifier le mot de passe
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARTE : GESTION DES TYPES DE BLOG -->
            <div class="dash-card" style="margin-top: 30px;">
                <div class="dash-card-header">
                    <h3><i class="fas fa-newspaper"></i> Types de Blog</h3>
                </div>
                <div class="dash-card-body padded">
                    <div style="display:grid;grid-template-columns:1fr 2fr;gap:28px;align-items:start;">
                        
                        <!-- FORMULAIRE -->
                        <div class="dash-card">
                            <div class="dash-card-header">
                                <h3><i class="fas fa-<?= $editTypeBlog ? 'edit' : 'plus' ?>" style="color:var(--primary-green);margin-right:8px;"></i>
                                    <?= $editTypeBlog ? 'Modifier' : 'Ajouter' ?> un type
                                </h3>
                                <?php if ($editTypeBlog): ?>
                                    <a href="parametres.php" class="btn-dash btn-dash-outline btn-dash-sm"><i class="fas fa-times"></i> Annuler</a>
                                <?php endif; ?>
                            </div>
                            <div class="dash-card-body padded">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="crud_type_blog">
                                    <input type="hidden" name="sub_action" value="<?= $editTypeBlog ? 'edit' : 'create' ?>">
                                    <?php if ($editTypeBlog): ?>
                                        <input type="hidden" name="id_type_blog" value="<?= (int)$editTypeBlog['ID_TYPE_BLOG'] ?>">
                                    <?php endif; ?>
                                    
                                    <div class="dash-form-group">
                                        <label for="lib_type_blog">Nom du type de blog <span class="required">*</span></label>
                                        <input type="text" name="lib_type_blog" id="lib_type_blog" class="dash-input"
                                               placeholder="Ex: Technologie, Mode, Événements..."
                                               value="<?= htmlspecialchars($editTypeBlog['LIB_TYPE_BLOG'] ?? '') ?>" required>
                                    </div>
                                    
                                    <button type="submit" class="btn-dash btn-dash-primary" style="width:100%;justify-content:center;">
                                        <i class="fas fa-save"></i> <?= $editTypeBlog ? 'Mettre à jour' : 'Ajouter le type' ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- LISTE DES TYPES -->
                        <div class="dash-card">
                            <div class="dash-card-header">
                                <h3><i class="fas fa-tags" style="color:var(--primary-green);margin-right:8px;"></i> Types de blog</h3>
                                <span class="badge badge-confirm"><?= count($typesBlog) ?></span>
                            </div>
                            <div class="dash-card-body">
                                <?php if (empty($typesBlog)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-tags"></i>
                                        <p>Aucun type de blog. Commencez par en ajouter un.</p>
                                    </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="dash-table">
                                        <thead><tr><th>#</th><th>Type de blog</th><th>Articles</th><th>Actions</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($typesBlog as $type): 
                                            $stmtArticles = $pdo->prepare('SELECT COUNT(*) FROM BLOG WHERE ID_TYPE_BLOG = ?');
                                            $stmtArticles->execute([(int)$type['ID_TYPE_BLOG']]);
                                            $nbArticles = (int) $stmtArticles->fetchColumn();
                                        ?>
                                            <tr>
                                                <td>#<?= (int)$type['ID_TYPE_BLOG'] ?></td>
                                                <td><strong><?= htmlspecialchars($type['LIB_TYPE_BLOG']) ?></strong></td>
                                                <td>
                                                    <span class="badge <?= $nbArticles > 0 ? 'badge-confirm' : 'badge-waiting' ?>">
                                                        <?= $nbArticles ?> article(s)
                                                    </span>
                                                </td>
                                                <td style="display:flex;gap:6px;">
                                                    <a href="?edit_type=<?= (int)$type['ID_TYPE_BLOG'] ?>" class="btn-dash btn-dash-outline btn-dash-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($nbArticles === 0): ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce type de blog ?');">
                                                        <input type="hidden" name="action" value="crud_type_blog">
                                                        <input type="hidden" name="sub_action" value="delete">
                                                        <input type="hidden" name="id_type_blog" value="<?= (int)$type['ID_TYPE_BLOG'] ?>">
                                                        <button class="btn-dash btn-dash-danger btn-dash-sm"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                    <?php else: ?>
                                                        <span title="Utilisé par des articles" style="color:#ccc;font-size:1rem;margin-left:6px;"><i class="fas fa-lock"></i></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

            <!-- ═══════════════════════════════════════════════════════════
                 CARTE : GESTION DES PARTENAIRES
            ════════════════════════════════════════════════════════════ -->
            <div class="dash-card" style="margin-top: 30px;">
                <div class="dash-card-header">
                    <h3><i class="fas fa-handshake"></i> Partenaires</h3>
                    <span class="badge badge-confirm"><?= count($partenaires) ?></span>
                </div>
                <div class="dash-card-body padded">
                    <div style="display:grid;grid-template-columns:1fr 2fr;gap:28px;align-items:start;">

                        <!-- FORMULAIRE PARTENAIRE -->
                        <div class="dash-card">
                            <div class="dash-card-header">
                                <h3><i class="fas fa-<?= $editPartenaire ? 'edit' : 'plus' ?>" style="color:var(--primary-green);margin-right:8px;"></i>
                                    <?= $editPartenaire ? 'Modifier' : 'Ajouter' ?> un partenaire
                                </h3>
                                <?php if ($editPartenaire): ?>
                                    <a href="parametres.php" class="btn-dash btn-dash-outline btn-dash-sm"><i class="fas fa-times"></i> Annuler</a>
                                <?php endif; ?>
                            </div>
                            <div class="dash-card-body padded">
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="crud_partenaire">
                                    <input type="hidden" name="sub_action" value="<?= $editPartenaire ? 'edit' : 'create' ?>">
                                    <?php if ($editPartenaire): ?>
                                        <input type="hidden" name="id_partenaire" value="<?= (int)$editPartenaire['ID_PARTENAIRE'] ?>">
                                    <?php endif; ?>

                                    <div class="dash-form-group">
                                        <label for="description_partenaire">Description <span class="required">*</span></label>
                                        <input type="text" name="description_partenaire" id="description_partenaire" class="dash-input"
                                               placeholder="Ex: Partenaire officiel de Ny Tia Sary"
                                               value="<?= htmlspecialchars($editPartenaire['DESCRIPTIONS'] ?? '') ?>" required>
                                    </div>

                                    <div class="dash-form-group">
                                        <label for="lien_partenaire">Lien (URL) <span class="required">*</span></label>
                                        <input type="url" name="lien_partenaire" id="lien_partenaire" class="dash-input"
                                               placeholder="https://www.exemple.com"
                                               value="<?= htmlspecialchars($editPartenaire['LIEN_PARTENAIRE'] ?? '') ?>" required>
                                    </div>

                                    <div class="dash-form-group">
                                        <label>Logo <?= $editPartenaire ? '(laisser vide pour conserver l\'actuel)' : '<span class="required">*</span>' ?></label>
                                        <?php if ($editPartenaire && $editPartenaire['PATH_LOGO']): ?>
                                            <div style="margin-bottom:8px;">
                                                <img src="../../../<?= htmlspecialchars($editPartenaire['PATH_LOGO']) ?>" alt="Logo actuel" style="max-height:50px;border-radius:4px;border:1px solid #eee;padding:4px;">
                                            </div>
                                        <?php endif; ?>
                                        <label class="upload-zone" style="padding:15px;cursor:pointer;">
                                            <i class="fas fa-cloud-upload-alt" style="font-size:1.5rem;margin-bottom:6px;"></i>
                                            <p style="font-size:0.85rem;font-weight:600;">Choisir un logo</p>
                                            <input type="file" name="logo_partenaire" id="logo_partenaire" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;" onchange="previewLogo(this)">
                                        </label>
                                        <img id="logoPreview" src="" alt="" style="max-height:50px;margin-top:8px;border-radius:4px;display:none;">
                                    </div>

                                    <button type="submit" class="btn-dash btn-dash-primary" style="width:100%;justify-content:center;">
                                        <i class="fas fa-save"></i> <?= $editPartenaire ? 'Mettre à jour' : 'Ajouter le partenaire' ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- LISTE DES PARTENAIRES -->
                        <div class="dash-card">
                            <div class="dash-card-header">
                                <h3><i class="fas fa-handshake" style="color:var(--primary-green);margin-right:8px;"></i> Liste des partenaires</h3>
                            </div>
                            <div class="dash-card-body">
                                <?php if (empty($partenaires)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-handshake"></i>
                                        <p>Aucun partenaire. Commencez par en ajouter un.</p>
                                    </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="dash-table">
                                        <thead><tr><th>Logo</th><th>Description</th><th>Lien</th><th>Actions</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($partenaires as $p): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($p['PATH_LOGO']): ?>
                                                        <img src="../../<?= htmlspecialchars($p['PATH_LOGO']) ?>" alt="Logo" style="max-height:38px;max-width:80px;object-fit:contain;border-radius:4px;">
                                                    <?php else: ?>
                                                        <span style="color:#ccc;"><i class="fas fa-image"></i></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong><?= htmlspecialchars($p['DESCRIPTIONS']) ?></strong></td>
                                                <td>
                                                    <a href="<?= htmlspecialchars($p['LIEN_PARTENAIRE']) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--primary-green);font-size:0.85rem;">
                                                        <i class="fas fa-external-link-alt"></i> Visiter
                                                    </a>
                                                </td>
                                                <td style="display:flex;gap:6px;">
                                                    <a href="?edit_partenaire=<?= (int)$p['ID_PARTENAIRE'] ?>" class="btn-dash btn-dash-outline btn-dash-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce partenaire et son logo ?');">
                                                        <input type="hidden" name="action" value="crud_partenaire">
                                                        <input type="hidden" name="sub_action" value="delete">
                                                        <input type="hidden" name="id_partenaire" value="<?= (int)$p['ID_PARTENAIRE'] ?>">
                                                        <button class="btn-dash btn-dash-danger btn-dash-sm"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════
                 CARTE : CONTACT ENTREPRISE (1 seul actif)
            ════════════════════════════════════════════════════════════ -->
            <div class="dash-card" style="margin-top: 30px;">
                <div class="dash-card-header">
                    <h3><i class="fas fa-address-card"></i> Contact Entreprise</h3>
                    <?php if ($contact): ?>
                        <button type="button" class="btn-dash btn-dash-primary btn-dash-sm" id="openContactModal">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                    <?php endif; ?>
                </div>
                <div class="dash-card-body padded">
                    <?php if (!$contact): ?>
                        <!-- Aucun contact : formulaire de création -->
                        <p style="font-size:0.9rem;color:#666;margin-bottom:20px;">Aucun contact entreprise défini. Remplissez le formulaire ci-dessous pour en créer un.</p>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="crud_contact">
                            <input type="hidden" name="sub_action" value="create">
                            <div class="form-grid-2">
                                <div class="dash-form-group">
                                    <label for="adresse_contact_create">Adresse <span class="required">*</span></label>
                                    <input type="text" name="adresse_contact" id="adresse_contact_create" class="dash-input" placeholder="Ex: Antananarivo, Madagascar" required>
                                </div>
                                <div class="dash-form-group">
                                    <label for="tel_contact_create">Téléphone <span class="required">*</span></label>
                                    <input type="text" name="tel_contact" id="tel_contact_create" class="dash-input" placeholder="+261 XX XXX XXXX" required>
                                </div>
                                <div class="dash-form-group">
                                    <label for="email_contact_create">E-mail <span class="required">*</span></label>
                                    <input type="email" name="email_contact" id="email_contact_create" class="dash-input" placeholder="contact@nytiasary.com" required>
                                </div>
                                <div class="dash-form-group">
                                    <label for="horaire_contact_create">Horaires <span class="required">*</span></label>
                                    <input type="text" name="horaire_contact" id="horaire_contact_create" class="dash-input" placeholder="Lun-Ven 08h-18h" required>
                                </div>
                                <div class="dash-form-group">
                                    <label for="whatsapp_create">Lien WhatsApp</label>
                                    <input type="url" name="whatsapp_lien" id="whatsapp_create" class="dash-input" placeholder="https://wa.me/261XXXXXXXXX">
                                </div>
                                <div class="dash-form-group">
                                    <label for="messenger_create">Lien Messenger</label>
                                    <input type="url" name="messenger_lien" id="messenger_create" class="dash-input" placeholder="https://m.me/nytiasary">
                                </div>
                            </div>
                            <button type="submit" class="btn-dash btn-dash-primary" style="margin-top:10px;">
                                <i class="fas fa-save"></i> Enregistrer le contact
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Contact existant : affichage en lecture -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <div>
                                <p style="font-size:0.9rem;color:#666;margin-bottom:8px;">
                                    <i class="fas fa-map-marker-alt" style="color:var(--primary-green);width:18px;"></i>
                                    <strong>Adresse :</strong> <?= htmlspecialchars($contact['ADRESSE_CONTACT']) ?>
                                </p>
                                <p style="font-size:0.9rem;color:#666;margin-bottom:8px;">
                                    <i class="fas fa-phone" style="color:var(--primary-green);width:18px;"></i>
                                    <strong>Téléphone :</strong> <?= htmlspecialchars($contact['TEL_CONTACT']) ?>
                                </p>
                                <p style="font-size:0.9rem;color:#666;margin-bottom:8px;">
                                    <i class="fas fa-envelope" style="color:var(--primary-green);width:18px;"></i>
                                    <strong>E-mail :</strong> <?= htmlspecialchars($contact['EMAIL_CONTACT']) ?>
                                </p>
                                <p style="font-size:0.9rem;color:#666;">
                                    <i class="fas fa-clock" style="color:var(--primary-green);width:18px;"></i>
                                    <strong>Horaires :</strong> <?= htmlspecialchars($contact['HORAIRE_CONTACT']) ?>
                                </p>
                            </div>
                            <div>
                                <?php if ($contact['WHATSAPP_LIEN']): ?>
                                <p style="font-size:0.9rem;color:#666;margin-bottom:8px;">
                                    <i class="fab fa-whatsapp" style="color:#25d366;width:18px;"></i>
                                    <strong>WhatsApp :</strong>
                                    <a href="<?= htmlspecialchars($contact['WHATSAPP_LIEN']) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--primary-green);"><?= htmlspecialchars($contact['WHATSAPP_LIEN']) ?></a>
                                </p>
                                <?php endif; ?>
                                <?php if ($contact['MESSENGER_LIEN']): ?>
                                <p style="font-size:0.9rem;color:#666;">
                                    <i class="fab fa-facebook-messenger" style="color:#0084ff;width:18px;"></i>
                                    <strong>Messenger :</strong>
                                    <a href="<?= htmlspecialchars($contact['MESSENGER_LIEN']) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--primary-green);"><?= htmlspecialchars($contact['MESSENGER_LIEN']) ?></a>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- MODAL : Modification des informations personnelles -->
<div class="dash-modal" id="infoModal">
    <div class="dash-modal-content">
        <button class="dash-modal-close" id="closeInfoModal">&times;</button>
        <h2 style="font-family: var(--font-headings); font-weight: 700; margin-bottom: 25px; color: var(--logo-black);">Modifier les informations</h2>
        <form action="" method="POST">
            <input type="hidden" name="action" value="update_info">
            
            <div class="form-grid-2">
                <div class="dash-form-group">
                    <label>Nom <span class="required">*</span></label>
                    <input type="text" class="dash-input" name="nom" value="<?= htmlspecialchars($adminData['NOM_CLIENT'] ?? '') ?>" required>
                </div>
                <div class="dash-form-group">
                    <label>Prénom <span class="required">*</span></label>
                    <input type="text" class="dash-input" name="prenom" value="<?= htmlspecialchars($adminData['PRENOM_CLIENT'] ?? '') ?>" required>
                </div>
            </div>

            <div class="dash-form-group">
                <label>Téléphone <span class="required">*</span></label>
                <input type="text" class="dash-input" name="telephone" value="<?= htmlspecialchars($adminData['TEL_CLIENT'] ?? '') ?>" required>
            </div>

            <div class="dash-form-group">
                <label>Adresse E-mail <span class="required">*</span></label>
                <input type="email" class="dash-input" name="email" value="<?= htmlspecialchars($adminData['EMAIL_AUTH'] ?? '') ?>" required>
            </div>

            <div style="margin-top: 25px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-dash btn-dash-outline btn-dash-sm" id="cancelInfoModal">Annuler</button>
                <button type="submit" class="btn-dash btn-dash-primary btn-dash-sm">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL : Modification du mot de passe -->
<div class="dash-modal" id="passwordModal">
    <div class="dash-modal-content">
        <button class="dash-modal-close" id="closePasswordModal">&times;</button>
        <h2 style="font-family: var(--font-headings); font-weight: 700; margin-bottom: 25px; color: var(--logo-black);">Sécurité du compte</h2>
        <form action="" method="POST">
            <input type="hidden" name="action" value="update_password">

            <div class="dash-form-group">
                <label>Mot de passe actuel <span class="required">*</span></label>
                <input type="password" class="dash-input" name="old_password" required placeholder="Saisissez votre mot de passe actuel">
            </div>

            <div class="form-grid-2">
                <div class="dash-form-group">
                    <label>Nouveau mot de passe <span class="required">*</span></label>
                    <input type="password" class="dash-input" name="new_password" required placeholder="Nouveau mot de passe">
                </div>
                <div class="dash-form-group">
                    <label>Confirmer le mot de passe <span class="required">*</span></label>
                    <input type="password" class="dash-input" name="confirm_password" required placeholder="Confirmez">
                </div>
            </div>

            <div style="margin-top: 25px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-dash btn-dash-outline btn-dash-sm" id="cancelPasswordModal">Annuler</button>
                <button type="submit" class="btn-dash btn-dash-primary btn-dash-sm">
                    <i class="fas fa-key"></i> Modifier le mot de passe
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL : Modification de la photo de profil -->
<div class="dash-modal" id="photoModal">
    <div class="dash-modal-content">
        <button class="dash-modal-close" id="closePhotoModal">&times;</button>
        <h2 style="font-family: var(--font-headings); font-weight: 700; margin-bottom: 25px; color: var(--logo-black);">Modifier la photo de profil</h2>
        <form action="" method="POST" enctype="multipart/form-data" id="photoForm">
            <input type="hidden" name="action" value="update_photo">
            
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 3px solid var(--light-green); margin: 0 auto 15px; display:flex; align-items:center; justify-content:center; background:var(--primary-red); color:var(--white); font-family:var(--font-headings); font-weight:700; font-size: 2.5rem;">
                     <img src="../../<?= htmlspecialchars($photoAdmin) ?>" alt="Photo de profil" id="previewPhoto" style="width: 100%; height: 100%; object-fit: cover;">
                 </div>
                <p style="font-size:0.85rem; color:#666;">Cliquez sur une image pour la sélectionner</p>
            </div>
            
            <label class="upload-zone" style="padding: 25px; margin-bottom:10px; cursor: pointer;">
                <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p style="font-size: 0.9rem; font-weight: 600;">Choisir une image</p>
                <input type="file" name="photo_profil" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewAndSubmit()" style="display: none;">
            </label>

            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-dash btn-dash-outline btn-dash-sm" id="cancelPhotoModal">Annuler</button>
                <button type="submit" class="btn-dash btn-dash-primary btn-dash-sm">
                    <i class="fas fa-save"></i> Sauvegarder
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL : Suppression de la photo -->
<div class="dash-modal" id="deletePhotoModal">
    <div class="dash-modal-content">
        <button class="dash-modal-close" id="closeDeletePhotoModal">&times;</button>
        <h2 style="font-family: var(--font-headings); font-weight: 700; margin-bottom: 20px; color: var(--logo-black);">Supprimer la photo</h2>
        <p style="font-size: 0.95rem; color: #555; margin-bottom: 25px;">Êtes-vous sûr de vouloir supprimer votre photo de profil ? Cette action est irréversible.</p>
        <form action="" method="POST">
            <input type="hidden" name="action" value="delete_photo">
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-dash btn-dash-outline btn-dash-sm" id="cancelDeletePhotoModal">Annuler</button>
                <button type="submit" class="btn-dash btn-dash-danger btn-dash-sm">
                    <i class="fas fa-trash-alt"></i> Supprimer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL : Confirmation de succès -->
<div class="dash-modal" id="successModal">
    <div class="dash-modal-content">
        <button class="dash-modal-close" id="closeSuccessModal">&times;</button>
        <div style="text-align: center; padding: 20px 0;">
            <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--primary-green); margin-bottom: 20px;"></i>
            <h2 id="successModalTitle" style="font-family: var(--font-headings); font-weight: 700; margin-bottom: 15px; color: var(--logo-black);"></h2>
            <p id="successModalMessage" style="font-size: 1rem; color: #555; margin-bottom: 25px;"></p>
            <button type="button" class="btn-dash btn-dash-primary btn-dash-sm" id="closeSuccessModalBtn">
                <i class="fas fa-check"></i> D'accord
            </button>
        </div>
    </div>
</div>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// Modal open/close functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }
}

// Event listeners for opening modals
document.getElementById('openInfoModal')?.addEventListener('click', () => openModal('infoModal'));
document.getElementById('openPasswordModal')?.addEventListener('click', () => openModal('passwordModal'));
document.getElementById('openPhotoModal')?.addEventListener('click', () => openModal('photoModal'));
document.getElementById('openDeletePhotoModal')?.addEventListener('click', () => openModal('deletePhotoModal'));

// Event listeners for closing modals
document.getElementById('closeInfoModal')?.addEventListener('click', () => closeModal('infoModal'));
document.getElementById('closePasswordModal')?.addEventListener('click', () => closeModal('passwordModal'));
document.getElementById('closePhotoModal')?.addEventListener('click', () => closeModal('photoModal'));
document.getElementById('closeDeletePhotoModal')?.addEventListener('click', () => closeModal('deletePhotoModal'));

// Cancel buttons
document.getElementById('cancelInfoModal')?.addEventListener('click', () => closeModal('infoModal'));
document.getElementById('cancelPasswordModal')?.addEventListener('click', () => closeModal('passwordModal'));
document.getElementById('cancelPhotoModal')?.addEventListener('click', () => closeModal('photoModal'));
document.getElementById('cancelDeletePhotoModal')?.addEventListener('click', () => closeModal('deletePhotoModal'));

// Photo preview and submit function
function previewAndSubmit() {
    const input = document.querySelector('#photoForm input[type="file"]');
    const preview = document.getElementById('previewPhoto');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
    
    // Auto-submit the form
    document.getElementById('photoForm').submit();
}

// Drag and drop for upload zone in modal
const uploadZones = document.querySelectorAll('.upload-zone');
uploadZones.forEach(zone => {
    zone.addEventListener('dragover', (e) => {
        e.preventDefault();
        zone.classList.add('drag-over');
    });
    zone.addEventListener('dragleave', () => {
        zone.classList.remove('drag-over');
    });
    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        const input = zone.querySelector('input[type="file"]');
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            previewAndSubmit();
        }
    });
});
</script>

<!-- Toastify pour messages PRG -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<?php if (!empty($prgMessages)): ?>
<script>
window.addEventListener('DOMContentLoaded', () => {
    <?= prg_render_toasts($prgMessages) ?>
    // Nettoyer l'URL
    const url = new URL(window.location);
    url.searchParams.delete('action');
    url.searchParams.delete('edit_type');
    window.history.replaceState({}, '', url);
});
</script>
<?php endif; ?>
</body>
</html>