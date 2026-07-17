<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();

require_once __DIR__ . '/composante/tolbarDto.php'; // fournit $pdo, $clientId, $clientNom, $clientPrenom
$titre = "Demande de Devis";

/* ============================================================
   DONNÉES DU CLIENT CONNECTÉ (pré-remplissage read-only)
   ============================================================ */
$stmtClient = $pdo->prepare(
    'SELECT c.NOM_CLIENT, c.PRENOM_CLIENT, c.TEL_CLIENT, c.TYPE_CLIENT,
            a.EMAIL_AUTH
     FROM client c
     JOIN authentification a ON c.ID_AUTH = a.ID_AUTH
     WHERE c.ID_CLIENT = ?'
);
$stmtClient->execute([$clientId]);
$clientData = $stmtClient->fetch(PDO::FETCH_ASSOC);

$cfNom    = htmlspecialchars($clientData['NOM_CLIENT']    ?? ($clientNom    ?? ''));
$cfPrenom = htmlspecialchars($clientData['PRENOM_CLIENT']  ?? ($clientPrenom ?? ''));
$cfEmail  = htmlspecialchars($clientData['EMAIL_AUTH']     ?? '');
$cfTel    = htmlspecialchars($clientData['TEL_CLIENT']     ?? '');
$cfType   = $clientData['TYPE_CLIENT'] ?? '';   // colonne TYPE_CLIENT dans la vraie BD

/* ============================================================
   LISTE DES PRESTATIONS
   ============================================================ */
$prestationsList = $pdo->query(
    "SELECT ID_PRESTATION, LIB_PRESTATION FROM prestations ORDER BY LIB_PRESTATION ASC"
)->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   STATUT TOAST (paramètres GET après redirection)
   ============================================================ */
$devisStatus  = $_GET['devis']   ?? null;
$devisMessage = $_GET['message'] ?? null;

/* ============================================================
   TYPES DE VISITEUR AUTORISÉS
   ============================================================ */
$typesVisiteur = [
    'Entreprise', 'ONG', 'Institution', 'Collectivités', 'Couples',
    'Familles', "Organisateurs d'événement", 'Artistes',
    'Agences de communication', 'Particuliers',
];
if ($cfType !== '' && !in_array($cfType, $typesVisiteur, true)) {
    $typesVisiteur[] = $cfType;
}
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
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        /* ── Champs read-only : style distinct ── */
        .dash-input[readonly],
        .dash-select[disabled] {
            background: rgba(255, 255, 255, 0.04) !important;
            color: var(--text-secondary, #a0a0a0) !important;
            cursor: not-allowed !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            opacity: 0.8;
        }
        .field-readonly-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.72rem;
            color: var(--primary-green, #3aaa6e);
            margin-left: 8px;
            font-weight: 500;
        }
        .field-readonly-badge i { font-size: 0.65rem; }

        /* ── Fieldset catégories ── */
        .devis-categories-fieldset {
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            padding: 16px 20px;
            margin-top: 4px;
        }
        .devis-categories-fieldset legend {
            padding: 0 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary-green, #3aaa6e);
        }
        .devis-categories-hint {
            display: block;
            font-size: 0.8rem;
            color: #888;
            margin-bottom: 12px;
        }
        .devis-categories-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .devis-category-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 6px 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            font-size: 0.83rem;
            transition: all 0.2s;
        }
        .devis-category-checkbox:hover {
            border-color: var(--primary-green, #3aaa6e);
            background: rgba(58,170,110,0.08);
        }
        .devis-category-checkbox input[type="checkbox"] {
            accent-color: var(--primary-green, #3aaa6e);
        }
        .devis-categories-loading, .devis-categories-empty, .devis-categories-error {
            font-size: 0.83rem;
            color: #888;
            padding: 4px 0;
        }
        .devis-categories-error { color: #e55; }

        /* ── Upload zone ── */
        .upload-zone {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 2px dashed rgba(255,255,255,0.18);
            border-radius: 10px;
            padding: 28px 20px;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            text-align: center;
            color: #888;
        }
        .upload-zone:hover, .upload-zone.drag-over {
            border-color: var(--primary-green, #3aaa6e);
            background: rgba(58,170,110,0.05);
        }
        .upload-zone i { font-size: 2rem; color: var(--primary-green, #3aaa6e); }
        .upload-zone input[type="file"] { display: none; }

        /* ── Info card ── */
        .info-step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .info-step:last-child { border-bottom: none; }
        .info-step-icon {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: rgba(58,170,110,0.15);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .info-step-icon i { font-size: 0.85rem; color: var(--primary-green, #3aaa6e); }
        .info-step-text { font-size: 0.86rem; color: #aaa; line-height: 1.5; }
    </style>
</head>
<body>
<div class="dashboard-wrapper">

    <?php include __DIR__ . '/composante/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="dashboard-main">

        <!-- TOPBAR -->
        <?php include __DIR__ . '/composante/tolbar.php'; ?>

        <div class="dashboard-content">

            <!-- Breadcrumb -->
            <nav class="dash-breadcrumb">
                <a href="home.php">Dashboard</a>
                <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Demande de Devis</span>
            </nav>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:28px;align-items:start;">

                <!-- ═══════════════════ FORMULAIRE ═══════════════════ -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3>
                            <i class="fas fa-file-invoice" style="color:var(--primary-green);margin-right:8px;"></i>
                            Formulaire de demande de devis
                        </h3>
                    </div>
                    <div class="dash-card-body padded">
                        <form method="POST"
                              action="../../traitement_devis.php"
                              enctype="multipart/form-data"
                              id="devisForm">

                            <!-- Champ caché : indique qu'on vient de l'espace client -->
                            <input type="hidden" name="source" value="client">

                            <!-- ── Ligne Nom / Prénom ── -->
                            <div class="form-grid-2">
                                <div class="dash-form-group">
                                    <label for="devis-nom">
                                        Nom <span class="required">*</span>
                                        <span class="field-readonly-badge">
                                            <i class="fas fa-lock"></i> Verrouillé
                                        </span>
                                    </label>
                                    <input type="text"
                                           id="devis-nom"
                                           name="nom"
                                           class="dash-input"
                                           value="<?= $cfNom ?>"
                                           readonly
                                           aria-label="Nom du client">
                                </div>
                                <div class="dash-form-group">
                                    <label for="devis-prenom">
                                        Prénom <span class="required">*</span>
                                        <span class="field-readonly-badge">
                                            <i class="fas fa-lock"></i> Verrouillé
                                        </span>
                                    </label>
                                    <input type="text"
                                           id="devis-prenom"
                                           name="prenom"
                                           class="dash-input"
                                           value="<?= $cfPrenom ?>"
                                           readonly
                                           aria-label="Prénom du client">
                                </div>
                            </div>

                            <!-- ── Ligne Email / Téléphone ── -->
                            <div class="form-grid-2">
                                <div class="dash-form-group">
                                    <label for="devis-email">
                                        Email <span class="required">*</span>
                                        <span class="field-readonly-badge">
                                            <i class="fas fa-lock"></i> Verrouillé
                                        </span>
                                    </label>
                                    <input type="email"
                                           id="devis-email"
                                           name="email"
                                           class="dash-input"
                                           value="<?= $cfEmail ?>"
                                           readonly
                                           aria-label="Email du client">
                                </div>
                                <div class="dash-form-group">
                                    <label for="devis-telephone">
                                        Téléphone <span class="required">*</span>
                                        <span class="field-readonly-badge">
                                            <i class="fas fa-lock"></i> Verrouillé
                                        </span>
                                    </label>
                                    <input type="tel"
                                           id="devis-telephone"
                                           name="telephone"
                                           class="dash-input"
                                           value="<?= $cfTel ?>"
                                           readonly
                                           aria-label="Téléphone du client">
                                </div>
                            </div>

                            <!-- ── Prestation souhaitée ── -->
                            <div class="dash-form-group">
                                <label for="devis-prestation">
                                    Prestation souhaitée <span class="required">*</span>
                                </label>
                                <select id="devis-prestation"
                                        name="id_prestation"
                                        class="dash-select"
                                        required
                                        aria-label="Prestation souhaitée">
                                    <option value="" disabled selected>— Sélectionnez une prestation —</option>
                                    <?php foreach ($prestationsList as $prestation): ?>
                                        <option value="<?= htmlspecialchars((string)$prestation['ID_PRESTATION']) ?>">
                                            <?= htmlspecialchars($prestation['LIB_PRESTATION']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- ── Catégories dynamiques ── -->
                            <fieldset class="devis-categories-fieldset" id="devis-categories-fieldset">
                                <legend>Catégories</legend>
                                <small class="devis-categories-hint">
                                    Cochez une ou plusieurs catégories (choisissez d'abord une prestation)
                                </small>
                                <div class="devis-categories-list" id="devis-categories-list">
                                    <!-- Rempli dynamiquement via JS -->
                                </div>
                            </fieldset>

                            <!-- ── Type de visiteur ── -->
                            <div class="dash-form-group" style="margin-top:16px;">
                                <label for="devis-type">
                                    Type de visiteur <span class="required">*</span>
                                    <span class="field-readonly-badge">
                                        <i class="fas fa-lock"></i> Verrouillé
                                    </span>
                                </label>
                                <!-- Input hidden pour que la valeur soit soumise même si le select est disabled -->
                                <input type="hidden" name="type_visiteur" value="<?= htmlspecialchars($cfType) ?>">
                                <select id="devis-type"
                                        class="dash-select"
                                        disabled
                                        aria-label="Type de visiteur">
                                    <option value="" <?= empty($cfType) ? 'selected' : '' ?>>— Type de visiteur —</option>
                                    <?php foreach ($typesVisiteur as $type): ?>
                                        <option value="<?= htmlspecialchars($type) ?>"
                                            <?= ($cfType === $type) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- ── Budget / Date ── -->
                            <div class="form-grid-2">
                                <div class="dash-form-group">
                                    <label for="devis-budget">Budget estimatif (Ar)</label>
                                    <input type="number"
                                           id="devis-budget"
                                           name="budget"
                                           class="dash-input"
                                           min="0"
                                           step="1000"
                                           placeholder="Ex: 500000"
                                           aria-label="Budget estimatif">
                                </div>
                                <div class="dash-form-group">
                                    <label for="devis-date">Date souhaitée</label>
                                    <input type="date"
                                           id="devis-date"
                                           name="date_souhaitee"
                                           class="dash-input"
                                           min="<?= date('Y-m-d') ?>"
                                           aria-label="Date souhaitée">
                                </div>
                            </div>

                            <!-- ── Description ── -->
                            <div class="dash-form-group">
                                <label for="devis-description">
                                    Description du projet <span class="required">*</span>
                                </label>
                                <textarea id="devis-description"
                                          name="description"
                                          class="dash-textarea"
                                          rows="5"
                                          required
                                          placeholder="Décrivez votre projet : thème, ambiance souhaitée, nombre de personnes, lieu envisagé..."
                                          aria-label="Description du projet"></textarea>
                            </div>

                            <!-- ── Pièce jointe ── -->
                            <div class="dash-form-group">
                                <label>
                                    Pièce jointe
                                    <small style="font-weight:400;color:#888;">
                                        (PDF, Word, images — max 10 Mo)
                                    </small>
                                </label>
                                <label class="upload-zone" for="devis-fichier" id="uploadZone">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p id="uploadText">Cliquez ou glissez votre fichier ici</p>
                                    <input type="file"
                                           name="piece_jointe"
                                           id="devis-fichier"
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp">
                                </label>
                                <div id="fileList" style="margin-top:10px;font-size:0.82rem;color:#888;"></div>
                            </div>

                            <!-- ── Bouton submit ── -->
                            <button type="submit"
                                    class="btn-dash btn-dash-primary"
                                    style="width:100%;justify-content:center;padding:14px;margin-top:8px;">
                                <i class="fas fa-paper-plane"></i>&nbsp; Envoyer ma demande de devis
                            </button>
                        </form>
                    </div>
                </div>

                <!-- ═══════════════════ COLONNE INFO ═══════════════════ -->
                <div>
                    <!-- Card : Vos informations -->
                    <div class="dash-card" style="margin-bottom:20px;">
                        <div class="dash-card-header">
                            <h3>
                                <i class="fas fa-user-circle" style="color:var(--primary-green);margin-right:8px;"></i>
                                Vos informations
                            </h3>
                        </div>
                        <div class="dash-card-body padded" style="font-size:0.87rem;">
                            <div style="display:flex;flex-direction:column;gap:10px;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <i class="fas fa-user" style="color:var(--primary-green);width:16px;"></i>
                                    <span><?= $cfPrenom . ' ' . $cfNom ?></span>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <i class="fas fa-envelope" style="color:var(--primary-green);width:16px;"></i>
                                    <span><?= $cfEmail ?></span>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <i class="fas fa-phone" style="color:var(--primary-green);width:16px;"></i>
                                    <span><?= $cfTel ?: '—' ?></span>
                                </div>
                                <?php if (!empty($cfType)): ?>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <i class="fas fa-tag" style="color:var(--primary-green);width:16px;"></i>
                                    <span><?= htmlspecialchars($cfType) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <p style="font-size:0.78rem;color:#666;margin-top:14px;">
                                <i class="fas fa-info-circle" style="color:var(--primary-green);margin-right:4px;"></i>
                                Pour modifier vos informations, rendez-vous dans
                                <a href="parametres.php" style="color:var(--primary-green);">Paramètres</a>.
                            </p>
                        </div>
                    </div>

                    <!-- Card : Comment ça marche -->
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3>
                                <i class="fas fa-info-circle" style="color:var(--primary-green);margin-right:8px;"></i>
                                Comment ça marche ?
                            </h3>
                        </div>
                        <div class="dash-card-body padded">
                            <div class="info-step">
                                <div class="info-step-icon"><i class="fas fa-edit"></i></div>
                                <div class="info-step-text">Remplissez le formulaire avec les détails de votre projet</div>
                            </div>
                            <div class="info-step">
                                <div class="info-step-icon"><i class="fas fa-search"></i></div>
                                <div class="info-step-text">Notre équipe analyse votre demande sous <strong>48h</strong></div>
                            </div>
                            <div class="info-step">
                                <div class="info-step-icon"><i class="fas fa-comments"></i></div>
                                <div class="info-step-text">Nous vous contactons pour affiner le projet ensemble</div>
                            </div>
                            <div class="info-step">
                                <div class="info-step-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                <div class="info-step-text">Un devis détaillé et personnalisé vous est envoyé</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /grid -->
        </div><!-- /dashboard-content -->
    </div><!-- /dashboard-main -->
</div><!-- /dashboard-wrapper -->

<!-- ═══════════════════ TOAST NOTIFICATION ═══════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<?php if ($devisStatus && $devisMessage): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        Toastify({
            text: <?= json_encode($devisMessage, JSON_UNESCAPED_UNICODE) ?>,
            duration: 6000,
            gravity: "top",
            position: "right",
            close: true,
            stopOnFocus: true,
            style: {
                background: <?= $devisStatus === 'success'
                    ? "'linear-gradient(135deg, #377d49, #2a5c3a)'"
                    : "'linear-gradient(135deg, #d93d3d, #a82c2c)'" ?>,
                borderRadius: "6px",
                fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
                fontWeight: "600",
                boxShadow: "0 10px 30px rgba(0, 0, 0, 0.25)",
            },
        }).showToast();

        // Nettoie l'URL pour éviter de réafficher le toast au rechargement
        const url = new URL(window.location);
        url.searchParams.delete('devis');
        url.searchParams.delete('message');
        url.searchParams.delete('id_devis');
        window.history.replaceState({}, '', url);
    });
</script>
<?php endif; ?>

<script>
// ── Sidebar toggle ──────────────────────────────────────────────
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
});
overlay?.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
});

// ── Catégories dynamiques (même logique que script.js public) ───
const prestationSelect = document.getElementById('devis-prestation');
const categoriesList   = document.getElementById('devis-categories-list');
const categoriesFieldset = document.getElementById('devis-categories-fieldset');

// On masque le fieldset par défaut s'il n'y a pas de prestation
if (categoriesFieldset && (!prestationSelect || !prestationSelect.value)) {
    categoriesFieldset.style.display = 'none';
}

if (prestationSelect && categoriesList) {
    prestationSelect.addEventListener('change', () => {
        const idPrestation = prestationSelect.value;
        categoriesList.innerHTML = '';

        if (!idPrestation) {
            if (categoriesFieldset) categoriesFieldset.style.display = 'none';
            return;
        }
        
        if (categoriesFieldset) categoriesFieldset.style.display = 'block';

        categoriesList.innerHTML = '<p class="devis-categories-loading"><i class="fas fa-spinner fa-spin"></i> Chargement...</p>';

        // L'endpoint est à la racine du site (2 niveaux au-dessus)
        fetch(`../../categorie.php?id_prestation=${encodeURIComponent(idPrestation)}`)
            .then(res => res.json())
            .then(categories => {
                categoriesList.innerHTML = '';
                if (!Array.isArray(categories) || categories.length === 0) {
                    categoriesList.innerHTML = '<p class="devis-categories-empty">Aucune catégorie disponible</p>';
                    return;
                }
                categories.forEach(cat => {
                    const item = document.createElement('label');
                    item.className = 'devis-category-checkbox';
                    item.innerHTML = `
                        <input type="checkbox" name="id_categorie[]" value="${cat.ID_CATEGORIE}">
                        <span>${cat.LIB_CATEGORIE}</span>
                    `;
                    categoriesList.appendChild(item);
                });
            })
            .catch(() => {
                categoriesList.innerHTML = '<p class="devis-categories-error"><i class="fas fa-exclamation-triangle"></i> Erreur de chargement</p>';
            });
    });
}

// ── Upload drag & drop + aperçu fichier ─────────────────────────
const zone     = document.getElementById('uploadZone');
const fileInput = document.getElementById('devis-fichier');
const fileList = document.getElementById('fileList');
const txt      = document.getElementById('uploadText');

function showFile(files) {
    if (!files || !files.length) {
        txt.textContent = 'Cliquez ou glissez votre fichier ici';
        fileList.innerHTML = '';
        return;
    }
    const f = files[0];
    txt.textContent = '1 fichier sélectionné';
    fileList.innerHTML = `
        <div style="padding:6px 10px;background:rgba(58,170,110,0.08);border-radius:8px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-file" style="color:var(--primary-green);"></i>
            <span>${f.name}</span>
            <span style="color:#888;font-size:0.78rem;">(${(f.size / 1024).toFixed(1)} Ko)</span>
        </div>`;
}

fileInput?.addEventListener('change', () => showFile(fileInput.files));

zone?.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone?.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
zone?.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drag-over');
    const dt = e.dataTransfer;
    if (dt.files.length) {
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(dt.files[0]); // 1 seul fichier
        fileInput.files = dataTransfer.files;
        showFile(fileInput.files);
    }
});
</script>
</body>
</html>
