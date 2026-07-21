<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();
require_once __DIR__ . '/../../util/delete_file.php';
require_once __DIR__ . '/../../util/large_upload.php';

require_once __DIR__.'/composante/tolbarDto.php';
$titre = "Gestion des médias";
$success = $error = '';

// ── Suppression d'un média ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $idMedia = (int) ($_POST['id_media'] ?? 0);
    if ($idMedia) {
        $stmt = $pdo->prepare('SELECT PATH_MEDIA FROM MEDIA WHERE ID_MEDIA = ?');
        $stmt->execute([$idMedia]);
        $path = $stmt->fetchColumn();
        if ($path) {
            deleteFile($path);
        }
        $pdo->prepare('DELETE FROM MEDIA WHERE ID_MEDIA = ?')->execute([$idMedia]);
        $success = 'Média supprimé.';
    }
}

// ── Upload de médias ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    $idResa = (int) ($_POST['id_reservation'] ?? 0);
    $files  = $_FILES['medias'] ?? null;

    if (!$idResa) {
        $error = 'Veuillez sélectionner une réservation.';
    } elseif (empty($files['name'][0])) {
        $error = 'Veuillez sélectionner au moins un fichier.';
    } else {
        $uploadResults = uploadMultipleMedias($files, 'medias');

        $uploaded = 0;
        $errors   = [];

        foreach ($uploadResults as $res) {
            if ($res['success']) {
                $pdo->prepare('INSERT INTO MEDIA (ID_RESERVATION, PATH_MEDIA, TYPE_MEDIA) VALUES (?, ?, ?)')
                    ->execute([$idResa, $res['path'], $res['type']]);
                $uploaded++;
            } else {
                $errors[] = $res['error'];
            }
        }

        if ($uploaded > 0) {
            $success = "$uploaded fichier(s) uploadé(s) avec succès.";
        }
        if (!empty($errors)) {
            $error = implode(' | ', array_unique($errors));
        }
    }
}


// ── Réservations TERMINEE (pour le formulaire) ────────────────
$resasTerminees = $pdo->query(
    "SELECT r.ID_RESERVATION, r.DATE_RESERVATION, p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT,
            COUNT(m.ID_MEDIA) AS nb_medias
     FROM RESERVATION r
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
     LEFT JOIN MEDIA m ON m.ID_RESERVATION = r.ID_RESERVATION
     WHERE r.STATUS_RESERVATION = 'TERMINEE'
     GROUP BY r.ID_RESERVATION
     ORDER BY r.DATE_RESERVATION DESC"
)->fetchAll();

// ── Filtrage par réservation ──────────────────────────────────
$filterResa = (int) ($_GET['resa'] ?? 0);

$sqlM = 'SELECT m.*, r.DATE_RESERVATION, p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT
         FROM MEDIA m
         JOIN RESERVATION r ON m.ID_RESERVATION = r.ID_RESERVATION
         JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
         JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT';
$paramsM = [];
if ($filterResa) {
    $sqlM .= ' WHERE m.ID_RESERVATION = ?';
    $paramsM[] = $filterResa;
}
$sqlM .= ' ORDER BY m.ID_MEDIA DESC';
$stmtM = $pdo->prepare($sqlM);
$stmtM->execute($paramsM);
$medias = $stmtM->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médias | Admin NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .media-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:14px; }
        .media-thumb {
            position:relative; border-radius:12px; overflow:hidden;
            background:#111; aspect-ratio:4/3;
            border:1px solid rgba(255,255,255,.08);
            transition:transform .2s, box-shadow .2s;
        }
        .media-thumb:hover { transform:scale(1.02); box-shadow:0 8px 24px rgba(0,0,0,.5); }
        .media-thumb img, .media-thumb video { width:100%; height:100%; object-fit:cover; display:block; }
        .media-thumb-overlay {
            position:absolute; bottom:0; left:0; right:0;
            background:linear-gradient(transparent,rgba(0,0,0,.8));
            padding:10px 12px 8px;
            display:flex; justify-content:space-between; align-items:flex-end;
        }
        .media-type-badge { font-size:0.7rem; font-weight:700; letter-spacing:.5px; color:#fff; }
        .media-del-btn {
            background:rgba(231,76,60,.85); border:none; border-radius:6px;
            color:#fff; font-size:0.75rem; padding:4px 8px; cursor:pointer;
        }
    </style>

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
                <span>Médias</span>
            </nav>

            
            

            <div style="display:grid;grid-template-columns:1fr 2fr;gap:28px;align-items:start;">

                <!-- FORMULAIRE UPLOAD -->
                <div style="display:flex;flex-direction:column;gap:18px;">
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3><i class="fas fa-upload" style="color:var(--primary-green);margin-right:8px;"></i> Uploader des médias</h3>
                        </div>
                        <div class="dash-card-body padded">
                            <form method="POST" enctype="multipart/form-data" action="">
                                <input type="hidden" name="action" value="upload">
                                <div class="dash-form-group">
                                    <label for="id_reservation">Réservation terminée <span class="required">*</span></label>
                                    <select name="id_reservation" id="id_reservation" class="dash-select" required>
                                        <option value="">— Choisir —</option>
                                        <?php foreach ($resasTerminees as $r): ?>
                                            <option value="<?= (int)$r['ID_RESERVATION'] ?>"
                                                <?= $filterResa === (int)$r['ID_RESERVATION'] ? 'selected' : '' ?>>
                                                #<?= (int)$r['ID_RESERVATION'] ?> — <?= htmlspecialchars($r['PRENOM_CLIENT'].' '.$r['NOM_CLIENT']) ?> — <?= htmlspecialchars($r['LIB_PRESTATION']) ?>
                                                (<?= date('d/m/Y', strtotime($r['DATE_RESERVATION'])) ?>)
                                                <?= (int)$r['nb_medias'] > 0 ? ' — '.(int)$r['nb_medias'].' média(s)' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="dash-form-group">
                                    <label for="medias">Fichiers (photos/vidéos) <span class="required">*</span></label>
                                    <input type="file" name="medias[]" id="medias" multiple
                                           accept="image/*,video/*"
                                           style="color:#ccc;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:10px;width:100%;">
                                    <small style="color:#888;font-size:0.78rem;margin-top:4px;display:block;">JPEG, PNG, WEBP, MP4, WEBM acceptés.</small>
                                </div>
                                <button type="submit" class="btn-dash btn-dash-primary" style="width:100%;justify-content:center;">
                                    <i class="fas fa-upload"></i> Uploader
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Filtre par réservation -->
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3><i class="fas fa-filter" style="color:var(--primary-green);margin-right:8px;"></i> Filtrer</h3>
                        </div>
                        <div class="dash-card-body padded">
                            <form method="GET" action="">
                                <div class="dash-form-group">
                                    <label for="resa_filter">Par réservation</label>
                                    <select name="resa" id="resa_filter" class="dash-select" onchange="this.form.submit()">
                                        <option value="">— Toutes —</option>
                                        <?php foreach ($resasTerminees as $r): ?>
                                            <option value="<?= (int)$r['ID_RESERVATION'] ?>" <?= $filterResa === (int)$r['ID_RESERVATION'] ? 'selected' : '' ?>>
                                                #<?= (int)$r['ID_RESERVATION'] ?> — <?= htmlspecialchars($r['PRENOM_CLIENT'].' '.$r['NOM_CLIENT']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- GALERIE -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3><i class="fas fa-photo-video" style="color:var(--primary-green);margin-right:8px;"></i> Galerie</h3>
                        <span class="badge badge-confirm"><?= count($medias) ?> média(s)</span>
                    </div>
                    <div class="dash-card-body padded">
                        <?php if (empty($medias)): ?>
                            <div class="empty-state"><i class="fas fa-images"></i><p>Aucun média trouvé.</p></div>
                        <?php else: ?>
                        <div class="media-grid">
                            <?php foreach ($medias as $m): ?>
                                <div class="media-thumb">
                                    <?php if ($m['TYPE_MEDIA'] === 'IMAGE'): ?>
                                        <img src="../../<?= htmlspecialchars($m['PATH_MEDIA']) ?>" alt="Média" loading="lazy">
                                    <?php else: ?>
                                        <video src="../../<?= htmlspecialchars($m['PATH_MEDIA']) ?>" muted preload="metadata">
                                            <i class="fas fa-film" style="font-size:3rem;color:#555;margin:auto;display:block;padding-top:40%;"></i>
                                        </video>
                                    <?php endif; ?>
                                    <div class="media-thumb-overlay">
                                        <span class="media-type-badge">
                                            <i class="fas fa-<?= $m['TYPE_MEDIA'] === 'IMAGE' ? 'image' : 'film' ?>"></i>
                                            <?= $m['TYPE_MEDIA'] ?>
                                        </span>
                                        <form method="POST" onsubmit="return confirm('Supprimer ce média ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_media" value="<?= (int)$m['ID_MEDIA'] ?>">
                                            <button class="media-del-btn"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
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
