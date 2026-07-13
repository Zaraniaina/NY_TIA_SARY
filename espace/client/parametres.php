<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();
require_once __DIR__ . '/../../util/file_upload.php';

require_once __DIR__ . '/composante/tolbarDto.php';
$titre = "Paramètres";

// Fetch authId from DB directly instead of relying on session key
$stmtAuth = $pdo->prepare("SELECT ID_AUTH FROM CLIENT WHERE ID_CLIENT = ?");
$stmtAuth->execute([$clientId]);
$authId   = (int) $stmtAuth->fetchColumn();

$success  = '';
$error    = '';
$showSuccessModal = false;
$successModalTitle = '';
$successModalMessage = '';

// --- GESTION DES REQUÊTES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ACTION : Mettre à jour les informations personnelles
    if ($_POST['action'] === 'update_info') {
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

                // 2. Update CLIENT info
                $stmtUpdateClient = $pdo->prepare("UPDATE CLIENT SET NOM_CLIENT = ?, PRENOM_CLIENT = ?, TEL_CLIENT = ? WHERE ID_CLIENT = ?");
                $stmtUpdateClient->execute([$nom, $prenom, $tel, $clientId]);

                $pdo->commit();
                
                // Mettre à jour la session
                $_SESSION['client_nom'] = $nom;
                $_SESSION['client_prenom'] = $prenom;
                $_SESSION['user_email'] = $email;

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
            $stmt->execute([$clientId]);
            $client = $stmt->fetch();
            
            if ($client && $client['PHOTO_CLIENT'] && $client['PHOTO_CLIENT'] !== 'assets/images/avatar.png') {
                deleteUploadedFile($client['PHOTO_CLIENT']);
            }
            
            $stmtUpdate = $pdo->prepare("UPDATE CLIENT SET PHOTO_CLIENT = ? WHERE ID_CLIENT = ?");
            if ($stmtUpdate->execute([$newPath, $clientId])) {
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
        $stmt->execute([$clientId]);
        $client = $stmt->fetch();
        
        if ($client && $client['PHOTO_CLIENT'] && $client['PHOTO_CLIENT'] !== 'assets/images/avatar.png') {
            deleteUploadedFile($client['PHOTO_CLIENT']);
            $stmtUpdate = $pdo->prepare("UPDATE CLIENT SET PHOTO_CLIENT = 'assets/images/avatar.png' WHERE ID_CLIENT = ?");
            if ($stmtUpdate->execute([$clientId])) {
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

// --- RECUPERATION DES DONNEES CLIENT POUR L'AFFICHAGE ---
$stmtClient = $pdo->prepare("
    SELECT c.*, a.EMAIL_AUTH 
    FROM CLIENT c 
    JOIN AUTHENTIFICATION a ON c.ID_AUTH = a.ID_AUTH 
    WHERE c.ID_CLIENT = ?
");
$stmtClient->execute([$clientId]);
$clientData = $stmtClient->fetch();

$clientNom    = $clientData['NOM_CLIENT'] ?? 'Client';
$clientPrenom = $clientData['PRENOM_CLIENT'] ?? '';
$initiales    = getInitiales($clientNom, $clientPrenom);
$photoClient  = $clientData['PHOTO_CLIENT'] ?? 'assets/images/avatar.png';
$isDefaultPhoto = ($photoClient === 'assets/images/avatar.png');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres | NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
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

            <?php if (!empty($success)): ?>
                <div class="dash-alert dash-alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="dash-alert dash-alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <div class="form-grid-2">
                <!-- COLONNE GAUCHE -->
                <div>
                    <!-- CARTE : PHOTO DE PROFIL -->
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>Photo de Profil</h3>
                        </div>
                        <div class="dash-card-body padded">
                            <div style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 20px;">
                                <div style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; border: 3px solid var(--light-green); flex-shrink: 0; display:flex; align-items:center; justify-content:center; background:var(--primary-green); color:var(--white); font-family:var(--font-headings); font-weight:700; font-size: 2rem;">
                                    <img src="../../<?= htmlspecialchars($photoClient) ?>" alt="Photo de profil" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div style="flex:1;">
                                    <p style="font-size:0.85rem; color:#666; margin-bottom:15px;">Une photo de profil aide à personnaliser votre compte.</p>
                                    
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
                            <h3>Informations Personnelles</h3>
                        </div>
                        <div class="dash-card-body padded">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <div>
                                    <p style="font-size:0.9rem; color:#666; margin-bottom:5px;"><strong>Nom :</strong> <?= htmlspecialchars($clientData['NOM_CLIENT'] ?? '') ?></p>
                                    <p style="font-size:0.9rem; color:#666; margin-bottom:5px;"><strong>Prénom :</strong> <?= htmlspecialchars($clientData['PRENOM_CLIENT'] ?? '') ?></p>
                                    <p style="font-size:0.9rem; color:#666; margin-bottom:5px;"><strong>Téléphone :</strong> <?= htmlspecialchars($clientData['TEL_CLIENT'] ?? '') ?></p>
                                    <p style="font-size:0.9rem; color:#666;"><strong>E-mail :</strong> <?= htmlspecialchars($clientData['EMAIL_AUTH'] ?? '') ?></p>
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
                            <h3>Sécurité du compte</h3>
                        </div>
                        <div class="dash-card-body padded">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <div>
                                    <p style="font-size:0.9rem; color:#666; margin-bottom:5px;"><strong>Mot de passe actuel :</strong> <span style="color:#666;">********</span></p>
                                </div>
                                <button type="button" class="btn-dash btn-dash-primary btn-dash-sm" id="openPasswordModal">
                                    <i class="fas fa-key"></i> Modifier le mot de passe
                                </button>
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
                                <input type="text" class="dash-input" name="nom" value="<?= htmlspecialchars($clientData['NOM_CLIENT'] ?? '') ?>" required>
                            </div>
                            <div class="dash-form-group">
                                <label>Prénom <span class="required">*</span></label>
                                <input type="text" class="dash-input" name="prenom" value="<?= htmlspecialchars($clientData['PRENOM_CLIENT'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="dash-form-group">
                            <label>Téléphone <span class="required">*</span></label>
                            <input type="text" class="dash-input" name="telephone" value="<?= htmlspecialchars($clientData['TEL_CLIENT'] ?? '') ?>" required>
                        </div>

                        <div class="dash-form-group">
                            <label>Adresse E-mail <span class="required">*</span></label>
                            <input type="email" class="dash-input" name="email" value="<?= htmlspecialchars($clientData['EMAIL_AUTH'] ?? '') ?>" required>
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
                            <div style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 3px solid var(--light-green); margin: 0 auto 15px; display:flex; align-items:center; justify-content:center; background:var(--primary-green); color:var(--white); font-family:var(--font-headings); font-weight:700; font-size: 2.5rem;">
                                <img src="../../<?= htmlspecialchars($photoClient) ?>" alt="Photo de profil" id="previewPhoto" style="width: 100%; height: 100%; object-fit: cover;">
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
</body>
</html>