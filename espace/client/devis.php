<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../util/file_upload.php';

$clientId     = (int) $_SESSION['client_id'];
$clientNom    = $_SESSION['client_nom']    ?? 'Client';
$clientPrenom = $_SESSION['client_prenom'] ?? '';
$initiales    = getInitiales($clientNom, $clientPrenom);
$pdo          = getPDO();
$success = $error = '';

// ── Soumission du devis ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPrestation  = (int) ($_POST['id_prestation'] ?? 0);
    $nom           = trim($_POST['nom'] ?? '');
    $prenoms       = trim($_POST['prenoms'] ?? '');
    $telephone     = trim($_POST['telephone'] ?? '');
    $entreprise    = trim($_POST['entreprise'] ?? '—');
    $budget        = trim($_POST['budget'] ?? '');
    $dateSouhait   = trim($_POST['date_souhait'] ?? '');
    $description   = trim($_POST['description'] ?? '');

    if (!$idPrestation || !$nom || !$prenoms || !$telephone || !$budget || !$dateSouhait || !$description) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        try {
            // Insérer le devis
            $stmt = $pdo->prepare(
                'INSERT INTO DEVIS (ID_PRESTATION, NOM, PRENOMS, TELEPHONE, ENTREPRISE, BUGET_ESTIMATIF, DATE_SOUHAITE, DESCRIPTION)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$idPrestation, $nom, $prenoms, $telephone, $entreprise, $budget, $dateSouhait, $description]);
            $idDevis = (int) $pdo->lastInsertId();

            // Gestion des pièces jointes
            if (!empty($_FILES['pieces_jointes']['name'][0])) {
                $results = uploadMultipleFiles($_FILES['pieces_jointes'], 'devis', 'any');
                $stmtPj  = $pdo->prepare('INSERT INTO PIECES_JOINTES (ID_DEVIS, PATH_PIECE) VALUES (?, ?)');
                foreach ($results as $res) {
                    if ($res['success'] && !empty($res['path'])) {
                        $stmtPj->execute([$idDevis, $res['path']]);
                    }
                }
            }

            $success = 'Votre demande de devis a bien été envoyée ! Notre équipe vous contactera dans les 48h.';
        } catch (\PDOException $e) {
            $error = 'Une erreur technique est survenue. Veuillez réessayer.';
        }
    }
}

// ── Prestations ───────────────────────────────────────────────
$prestations = $pdo->query('SELECT ID_PRESTATION, LIB_PRESTATION FROM PRESTATIONS ORDER BY LIB_PRESTATION')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Devis | NY TIA SARY</title>
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
        <div class="dashboard-topbar">
            <div style="display:flex;align-items:center;gap:14px;">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <span class="topbar-title">Demande de Devis</span>
            </div>
            <div class="topbar-user">
                <div class="topbar-user-info">
                    <span class="topbar-user-name"><?= htmlspecialchars($clientPrenom . ' ' . $clientNom) ?></span>
                    <span class="topbar-user-role">Client</span>
                </div>
                <div class="topbar-avatar"><?= htmlspecialchars($initiales) ?></div>
            </div>
        </div>

        <div class="dashboard-content">
            <nav class="dash-breadcrumb">
                <a href="home.php">Dashboard</a>
                <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Demande de Devis</span>
            </nav>

            <?php if ($success): ?>
                <div class="dash-alert dash-alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="dash-alert dash-alert-error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:28px;align-items:start;">

                <!-- FORMULAIRE -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-file-invoice" style="color:var(--primary-green);margin-right:8px;"></i> Formulaire de demande</h3>
                    </div>
                    <div class="dash-card-body padded">
                        <form method="POST" action="" enctype="multipart/form-data" id="devisForm">

                            <div class="dash-form-group">
                                <label for="id_prestation">Prestation souhaitée <span class="required">*</span></label>
                                <select name="id_prestation" id="id_prestation" class="dash-select" required>
                                    <option value="">— Sélectionner une prestation —</option>
                                    <?php foreach ($prestations as $p): ?>
                                        <option value="<?= (int) $p['ID_PRESTATION'] ?>"
                                            <?= (isset($_POST['id_prestation']) && (int)$_POST['id_prestation'] === (int)$p['ID_PRESTATION']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['LIB_PRESTATION']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-grid-2">
                                <div class="dash-form-group">
                                    <label for="nom">Nom <span class="required">*</span></label>
                                    <input type="text" name="nom" id="nom" class="dash-input"
                                           value="<?= htmlspecialchars($_POST['nom'] ?? $clientNom) ?>" required>
                                </div>
                                <div class="dash-form-group">
                                    <label for="prenoms">Prénom(s) <span class="required">*</span></label>
                                    <input type="text" name="prenoms" id="prenoms" class="dash-input"
                                           value="<?= htmlspecialchars($_POST['prenoms'] ?? $clientPrenom) ?>" required>
                                </div>
                                <div class="dash-form-group">
                                    <label for="telephone">Téléphone <span class="required">*</span></label>
                                    <input type="tel" name="telephone" id="telephone" class="dash-input"
                                           placeholder="+261 34 00 000 00" required>
                                </div>
                                <div class="dash-form-group">
                                    <label for="entreprise">Entreprise / Événement</label>
                                    <input type="text" name="entreprise" id="entreprise" class="dash-input"
                                           placeholder="Optionnel">
                                </div>
                                <div class="dash-form-group">
                                    <label for="budget">Budget estimatif <span class="required">*</span></label>
                                    <input type="text" name="budget" id="budget" class="dash-input"
                                           placeholder="Ex: 500 000 Ar" required>
                                </div>
                                <div class="dash-form-group">
                                    <label for="date_souhait">Date souhaitée <span class="required">*</span></label>
                                    <input type="date" name="date_souhait" id="date_souhait" class="dash-input"
                                           min="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <div class="dash-form-group">
                                <label for="description">Description du projet <span class="required">*</span></label>
                                <textarea name="description" id="description" class="dash-textarea"
                                          placeholder="Décrivez votre projet en détail : thème, ambiance souhaitée, nombre de personnes, lieu envisagé..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>

                            <!-- UPLOAD PIÈCES JOINTES -->
                            <div class="dash-form-group">
                                <label>Pièces jointes <small style="font-weight:400;color:#888;">(PDF, Word, images — max 10 Mo chacun)</small></label>
                                <label class="upload-zone" for="pieces_jointes" id="uploadZone">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p id="uploadText">Cliquez ou glissez vos fichiers ici</p>
                                    <input type="file" name="pieces_jointes[]" id="pieces_jointes" multiple
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp">
                                </label>
                                <div id="fileList" style="margin-top:10px;font-size:0.82rem;color:#555;"></div>
                            </div>

                            <button type="submit" class="btn-dash btn-dash-primary" style="width:100%;justify-content:center;padding:14px;">
                                <i class="fas fa-paper-plane"></i>&nbsp; Envoyer ma demande de devis
                            </button>
                        </form>
                    </div>
                </div>

                <!-- INFOS -->
                <div>
                    <div class="dash-card" style="margin-bottom:20px;">
                        <div class="dash-card-header"><h3><i class="fas fa-info-circle" style="color:var(--primary-green);margin-right:8px;"></i> Comment ça marche ?</h3></div>
                        <div class="dash-card-body padded" style="font-size:0.88rem;color:#555;line-height:1.8;">
                            <p style="margin-bottom:12px;"><i class="fas fa-check" style="color:var(--primary-green);margin-right:8px;"></i> Remplissez le formulaire avec vos besoins</p>
                            <p style="margin-bottom:12px;"><i class="fas fa-check" style="color:var(--primary-green);margin-right:8px;"></i> Notre équipe analyse votre demande sous 48h</p>
                            <p style="margin-bottom:12px;"><i class="fas fa-check" style="color:var(--primary-green);margin-right:8px;"></i> Nous vous contactons pour affiner le projet</p>
                            <p><i class="fas fa-check" style="color:var(--primary-green);margin-right:8px;"></i> Un devis détaillé vous est envoyé</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Sidebar toggle
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// Upload drag & drop + aperçu fichiers
const zone     = document.getElementById('uploadZone');
const input    = document.getElementById('pieces_jointes');
const fileList = document.getElementById('fileList');
const txt      = document.getElementById('uploadText');

function showFiles(files) {
    if (!files.length) { txt.textContent = 'Cliquez ou glissez vos fichiers ici'; fileList.innerHTML = ''; return; }
    txt.textContent = files.length + ' fichier(s) sélectionné(s)';
    fileList.innerHTML = [...files].map(f =>
        `<div style="padding:4px 0;border-bottom:1px solid #eee;">
            <i class="fas fa-file" style="color:var(--primary-green);margin-right:6px;"></i>
            ${f.name} <span style="color:#aaa;">(${(f.size/1024).toFixed(1)} Ko)</span>
         </div>`
    ).join('');
}

input.addEventListener('change', () => showFiles(input.files));
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('drag-over');
    const dt = e.dataTransfer;
    if (dt.files.length) {
        const dataTransfer = new DataTransfer();
        [...dt.files].forEach(f => dataTransfer.items.add(f));
        input.files = dataTransfer.files;
        showFiles(input.files);
    }
});
</script>
</body>
</html>
