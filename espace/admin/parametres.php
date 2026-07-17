<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();
require_once __DIR__ . '/../../util/file_upload.php';

require_once __DIR__.'/composante/tolbarDto.php';
//on changer le titre
$titre="Paramètre";
$success  = '';
$error    = '';
$showSuccessModal = false;
$successModalTitle = '';
$successModalMessage = '';

// Get authId for password operations
$stmtAuthId = $pdo->prepare("SELECT ID_AUTH FROM CLIENT WHERE ID_CLIENT = ?");
$stmtAuthId->execute([$adminId]);
$authId = (int) $stmtAuthId->fetchColumn();

// --- GESTION DES REQUÊTES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ACTION : Gestion des types de blog (CRUD)
    if ($_POST['action'] === 'crud_type_blog') {
        $subAction = $_POST['sub_action'] ?? '';
        $idType = (int) ($_POST['id_type_blog'] ?? 0);
        $libType = trim($_POST['lib_type_blog'] ?? '');

        if ($subAction === 'create') {
            if (!$libType) {
                $error = 'Le nom du type de blog est requis.';
            } else {
                $pdo->prepare('INSERT INTO TYPE_BLOG (LIB_TYPE_BLOG) VALUES (?)')->execute([$libType]);
                $success = "Type de blog « $libType » ajouté.";
            }
        } elseif ($subAction === 'edit' && $idType) {
            if (!$libType) {
                $error = 'Le nom du type de blog est requis.';
            } else {
                $pdo->prepare('UPDATE TYPE_BLOG SET LIB_TYPE_BLOG = ? WHERE ID_TYPE_BLOG = ?')->execute([$libType, $idType]);
                $success = "Type de blog mis à jour.";
            }
        } elseif ($subAction === 'delete' && $idType) {
            // Vérifier si des articles utilisent ce type
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM BLOG WHERE ID_TYPE_BLOG = ?');
            $stmt->execute([$idType]);
            $count = (int) $stmt->fetchColumn();
            if ($count > 0) {
                $error = "Impossible de supprimer : $count article(s) utilisent ce type de blog.";
            } else {
                $pdo->prepare('DELETE FROM TYPE_BLOG WHERE ID_TYPE_BLOG = ?')->execute([$idType]);
                $success = "Type de blog supprimé.";
            }
        }
    }

    // ACTION : Mettre à jour les informations personnelles
    elseif ($_POST['action'] === 'update_info') {
        $nom    = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $tel    = trim($_POST['telephone'] ?? '');
        $email  = trim($_POST['email'] ?? '');

        if ($nom && $prenom && $tel && $email) {
            try {
                $pdo->beginTransaction();

                // 1. Update EMAIL in AUTHENTIFICATION (Check for uniqueness)
                $stmtCheck = $pdo->prepare("SELECT ID_AUTH FROM AUTHENTIFICATION WHERE EMAIL_AUTH = ? AND ID_AUTH != ?");
                $stmtCheck->execute([$email, $authId]);
                if ($stmtCheck->fetch()) {
                    throw new Exception("L'adresse email est déjà utilisée.");
                }

                $stmtUpdateAuth = $pdo->prepare("UPDATE AUTHENTIFICATION SET EMAIL_AUTH = ? WHERE ID_AUTH = ?");
                $stmtUpdateAuth->execute([$email, $authId]);

                // 2. Update CLIENT info (admin is stored in CLIENT table)
                $stmtUpdateClient = $pdo->prepare("UPDATE CLIENT SET NOM_CLIENT = ?, PRENOM_CLIENT = ?, TEL_CLIENT = ? WHERE ID_CLIENT = ?");
                $stmtUpdateClient->execute([$nom, $prenom, $tel, $adminId]);

                $pdo->commit();
                
                // Mettre à jour la session
                $_SESSION['admin_nom'] = $nom;
                $_SESSION['admin_prenom'] = $prenom;
                $_SESSION['admin_email'] = $email;

                $success = "Vos informations ont été mises à jour avec succès.";
                $showSuccessModal = true;
                $successModalTitle = "Information mise à jour";
                $successModalMessage = "Vos informations personnelles ont été modifiées avec succès.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erreur : " . $e->getMessage();
            }
        } else {
            $error = "Veuillez remplir tous les champs obligatoires.";
        }
    }

    // ACTION : Mettre à jour le mot de passe
    elseif ($_POST['action'] === 'update_password') {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if ($old_pass && $new_pass && $confirm_pass) {
            if ($new_pass === $confirm_pass) {
                // Fetch current password hash
                $stmt = $pdo->prepare("SELECT MDP_AUTH FROM AUTHENTIFICATION WHERE ID_AUTH = ?");
                $stmt->execute([$authId]);
                $auth = $stmt->fetch();

                if ($auth && password_verify($old_pass, $auth['MDP_AUTH'])) {
                    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmtUpdate = $pdo->prepare("UPDATE AUTHENTIFICATION SET MDP_AUTH = ? WHERE ID_AUTH = ?");
                    if ($stmtUpdate->execute([$new_hash, $authId])) {
                        $success = "Votre mot de passe a été modifié avec succès.";
                        $showSuccessModal = true;
                        $successModalTitle = "Mot de passe modifié";
                        $successModalMessage = "Votre mot de passe a été modifié avec succès.";
                    } else {
                        $error = "Une erreur est survenue lors de la modification du mot de passe.";
                    }
                } else {
                    $error = "L'ancien mot de passe est incorrect.";
                }
            } else {
                $error = "Les nouveaux mots de passe ne correspondent pas.";
            }
        } else {
            $error = "Veuillez remplir tous les champs obligatoires.";
        }
    }

    // ACTION : Uploader / Changer la photo de profil
    elseif ($_POST['action'] === 'update_photo' && isset($_FILES['photo_profil'])) {
        $result = uploadFile($_FILES['photo_profil'], 'avatars', 'image');
        
        if ($result['success']) {
            $newPath = $result['path'];
            
            // Fetch old photo to delete it if it's not the default
            $stmt = $pdo->prepare("SELECT PHOTO_CLIENT FROM CLIENT WHERE ID_CLIENT = ?");
            $stmt->execute([$adminId]);
            $client = $stmt->fetch();
            
            if ($client && $client['PHOTO_CLIENT'] && $client['PHOTO_CLIENT'] !== 'assets/images/avatar.png') {
                deleteUploadedFile($client['PHOTO_CLIENT']);
            }
            
            $stmtUpdate = $pdo->prepare("UPDATE CLIENT SET PHOTO_CLIENT = ? WHERE ID_CLIENT = ?");
            if ($stmtUpdate->execute([$newPath, $adminId])) {
                $success = "Votre photo de profil a été mise à jour.";
                $showSuccessModal = true;
                $successModalTitle = "Photo mise à jour";
                $successModalMessage = "Votre photo de profil a été modifiée avec succès.";
            } else {
                $error = "Erreur lors de la mise à jour de la base de données.";
            }
        } else {
            $error = $result['error'];
        }
    }

    // ACTION : Supprimer la photo de profil
    elseif ($_POST['action'] === 'delete_photo') {
        $stmt = $pdo->prepare("SELECT PHOTO_CLIENT FROM CLIENT WHERE ID_CLIENT = ?");
        $stmt->execute([$adminId]);
        $client = $stmt->fetch();
        
        if ($client && $client['PHOTO_CLIENT'] && $client['PHOTO_CLIENT'] !== 'assets/images/avatar.png') {
            deleteUploadedFile($client['PHOTO_CLIENT']);
            $stmtUpdate = $pdo->prepare("UPDATE CLIENT SET PHOTO_CLIENT = 'assets/images/avatar.png' WHERE ID_CLIENT = ?");
            if ($stmtUpdate->execute([$adminId])) {
                $success = "Votre photo de profil a été supprimée.";
                $showSuccessModal = true;
                $successModalTitle = "Photo supprimée";
                $successModalMessage = "Votre photo de profil a été supprimée avec succès.";
            } else {
                $error = "Erreur lors de la suppression dans la base de données.";
            }
        } else {
             $error = "Vous utilisez déjà la photo par défaut.";
        }
    }
}

// --- RECUPERATION DES DONNEES ---

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

// Success modal - open on page load if needed
<?php if ($showSuccessModal): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('successModalTitle').textContent = <?= json_encode($successModalTitle) ?>;
    document.getElementById('successModalMessage').textContent = <?= json_encode($successModalMessage) ?>;
    openModal('successModal');
});
<?php endif; ?>

// Close success modal
document.getElementById('closeSuccessModal')?.addEventListener('click', () => closeModal('successModal'));
document.getElementById('closeSuccessModalBtn')?.addEventListener('click', () => closeModal('successModal'));
</script>

<!-- Toastify JS -->
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
window.addEventListener('DOMContentLoaded', () => {
    const errorMsg = <?php echo json_encode($error ?? '', JSON_UNESCAPED_UNICODE); ?>;
    const successMsg = <?php echo json_encode($success ?? '', JSON_UNESCAPED_UNICODE); ?>;
    
    if (errorMsg) {
        Toastify({
            text: errorMsg,
            duration: 6000,
            gravity: "top",
            position: "right",
            close: true,
            style: {
                background: "linear-gradient(135deg, #d93d3d, #a82c2c)",
                borderRadius: "6px",
                fontFamily: "system-ui, -apple-system, sans-serif",
                fontWeight: "600",
                boxShadow: "0 10px 30px rgba(0, 0, 0, 0.25)"
            }
        }).showToast();
    }
    
    if (successMsg) {
        Toastify({
            text: successMsg,
            duration: 6000,
            gravity: "top",
            position: "right",
            close: true,
            style: {
                background: "linear-gradient(135deg, #377d49, #2a5c3a)",
                borderRadius: "6px",
                fontFamily: "system-ui, -apple-system, sans-serif",
                fontWeight: "600",
                boxShadow: "0 10px 30px rgba(0, 0, 0, 0.25)"
            }
        }).showToast();
    }
});
</script>

</body>
</html>