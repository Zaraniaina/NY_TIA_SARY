<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();

require_once __DIR__ . '/composante/tolbarDto.php';
$titre = "Mes Réservations";

$success      = '';
$error        = '';

// ── Traitement nouvelle réservation ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_resa') {
    $idPrestation    = (int) ($_POST['id_prestation'] ?? 0);
    $idCategories    = $_POST['id_categories'] ?? [];
    $dateResa        = trim($_POST['date_reservation'] ?? '');
    $heureResa       = trim($_POST['heure_reservation'] ?? '');
    $lieuResa        = trim($_POST['lieu_reservation'] ?? '');
    $commentaire     = trim($_POST['commentaire'] ?? '');

    if (!$idPrestation || empty($idCategories) || !$dateResa || !$heureResa || !$lieuResa) {
        $error = 'Veuillez remplir tous les champs obligatoires et choisir au moins une formule.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO RESERVATION (ID_PRESTATION, ID_CLIENT, DATE_RESERVATION, HEURE_RESERVATION, LIEU_RESERVATION, COMME_RESERVATION, STATUS_RESERVATION)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$idPrestation, $clientId, $dateResa, $heureResa, $lieuResa, $commentaire, 'EN ATTENTE']);
        
        $idResa = $pdo->lastInsertId();

        $stmtCat = $pdo->prepare('SELECT TARIF_CATEGORIE FROM CATEGORIE WHERE ID_CATEGORIE = ?');
        $stmtRc = $pdo->prepare('INSERT INTO RESERVATION_CATEGORIE (ID_RESERVATION, ID_CATEGORIE, PRIX) VALUES (?, ?, ?)');
        
        foreach ($idCategories as $idCat) {
            $idCat = (int) $idCat;
            if ($idCat > 0) {
                $stmtCat->execute([$idCat]);
                $prix = (int) $stmtCat->fetchColumn();
                $stmtRc->execute([$idResa, $idCat, $prix]);
            }
        }

        $success = 'Votre réservation a été soumise avec succès ! Nous vous contacterons bientôt.';
    }
}

// ── Annulation d'une réservation ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $idResa = (int) ($_POST['id_reservation'] ?? 0);
    if ($idResa) {
        $pdo->prepare("UPDATE RESERVATION SET STATUS_RESERVATION='ANNULEE' WHERE ID_RESERVATION=? AND ID_CLIENT=? AND STATUS_RESERVATION='EN ATTENTE'")
            ->execute([$idResa, $clientId]);
        $success = 'Réservation annulée.';
    }
}

// ── Soumission d'un témoignage ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'temoignage') {
    $idResa = (int) ($_POST['id_reservation'] ?? 0);
    $note   = (int) ($_POST['note'] ?? 0);
    $mess   = trim($_POST['message'] ?? '');
    if (!$idResa || $note < 1 || $note > 5 || !$mess) {
        $error = 'Veuillez remplir tous les champs du témoignage.';
    } else {
        // Vérifier que la réservation appartient au client et est TERMINEE
        $chk = $pdo->prepare("SELECT COUNT(*) FROM RESERVATION WHERE ID_RESERVATION=? AND ID_CLIENT=? AND STATUS_RESERVATION='TERMINEE'");
        $chk->execute([$idResa, $clientId]);
        if ((int)$chk->fetchColumn() === 0) {
            $error = 'Opération non autorisée.';
        } else {
            // Vérifier qu'un avis n'existe pas déjà
            $exists = $pdo->prepare('SELECT COUNT(*) FROM TEMOIGNAGE WHERE ID_RESERVATION=?');
            $exists->execute([$idResa]);
            if ((int)$exists->fetchColumn() > 0) {
                $error = 'Vous avez déjà laissé un avis pour cette réservation.';
            } else {
                $pdo->prepare('INSERT INTO TEMOIGNAGE (ID_RESERVATION, MESS_RESERVATION, NOTE) VALUES (?, ?, ?)')
                    ->execute([$idResa, $mess, $note]);
                $success = 'Merci pour votre témoignage !';
            }
        }
    }
}

// ── Liste des prestations disponibles ────────────────────────
$prestations = $pdo->query('SELECT ID_PRESTATION, LIB_PRESTATION FROM PRESTATIONS ORDER BY LIB_PRESTATION')->fetchAll();

// ── Toutes mes réservations ───────────────────────────────────
$filter = $_GET['statut'] ?? 'TOUS';
$sql = 'SELECT r.*, p.LIB_PRESTATION,
               SUM(rc.PRIX) AS TOTAL_PRIX, 
               GROUP_CONCAT(cat.LIB_CATEGORIE SEPARATOR \'<br>\') AS LIBS_CATEGORIES,
               (SELECT COUNT(*) FROM TEMOIGNAGE t WHERE t.ID_RESERVATION=r.ID_RESERVATION) AS a_temoigne
        FROM RESERVATION r
        JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
        LEFT JOIN RESERVATION_CATEGORIE rc ON rc.ID_RESERVATION = r.ID_RESERVATION
        LEFT JOIN CATEGORIE cat ON cat.ID_CATEGORIE = rc.ID_CATEGORIE
        WHERE r.ID_CLIENT = ?';
$params = [$clientId];
if ($filter !== 'TOUS') {
    $sql    .= ' AND r.STATUS_RESERVATION = ?';
    $params[] = $filter;
}
$sql .= ' GROUP BY r.ID_RESERVATION ORDER BY r.DATE_RESERVATION DESC';
$stmtResas = $pdo->prepare($sql);
$stmtResas->execute($params);
$reservations = $stmtResas->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations | NY TIA SARY</title>
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
        <!-- TOPBAR -->
       <?php include __DIR__.'/composante/tolbar.php';?>

        <div class="dashboard-content">
            <nav class="dash-breadcrumb">
                <a href="home.php">Dashboard</a> <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Mes Réservations</span>
            </nav>

            <?php if ($success): ?>
                <div class="dash-alert dash-alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="dash-alert dash-alert-error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- FORMULAIRE NOUVELLE RÉSERVATION -->
            <div class="dash-card" style="margin-bottom:28px;">
                <div class="dash-card-header">
                    <h3><i class="fas fa-plus-circle" style="color:var(--primary-green);margin-right:8px;"></i> Nouvelle réservation</h3>
                    <button class="btn-dash btn-dash-outline btn-dash-sm" id="toggleForm">
                        <i class="fas fa-chevron-down" id="toggleIcon"></i> Ouvrir le formulaire
                    </button>
                </div>
                <div class="dash-card-body padded" id="formResa" style="display:none;">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="new_resa">
                        <div class="form-grid-2">
                            <div class="dash-form-group">
                                <label for="id_prestation">Type de prestation <span class="required">*</span></label>
                                <select name="id_prestation" id="id_prestation" class="dash-select" required>
                                    <option value="">— Choisir une prestation —</option>
                                    <?php foreach ($prestations as $p): ?>
                                        <option value="<?= (int) $p['ID_PRESTATION'] ?>">
                                            <?= htmlspecialchars($p['LIB_PRESTATION']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="dash-form-group" style="grid-column: 1 / -1;">
                                <label>Catégories / Formules <span class="required">*</span></label>
                                <div id="categories-container" class="checkbox-grid">
                                    <div style="color:#888;font-size:0.85rem;padding:10px 0;">— Choisissez d'abord une prestation —</div>
                                </div>
                                <div id="tarif-info" style="margin-top:12px;font-size:0.95rem;color:var(--primary-green);display:none;background:rgba(55, 125, 73, 0.05);padding:10px 15px;border-radius:6px;border:1px solid rgba(55, 125, 73, 0.1);">
                                    <i class="fas fa-calculator"></i> Total estimatif : <strong id="tarif-val" style="font-size:1.1rem;">0 Ar</strong>
                                </div>
                            </div>
                            <div class="dash-form-group">
                                <label for="lieu_reservation">Lieu <span class="required">*</span></label>
                                <input type="text" name="lieu_reservation" id="lieu_reservation" class="dash-input" placeholder="Ex: Antananarivo, Salle Ivato..." required>
                            </div>
                            <div class="dash-form-group">
                                <label for="date_reservation">Date souhaitée <span class="required">*</span></label>
                                <input type="date" name="date_reservation" id="date_reservation" class="dash-input"
                                       min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="dash-form-group">
                                <label for="heure_reservation">Heure <span class="required">*</span></label>
                                <input type="time" name="heure_reservation" id="heure_reservation" class="dash-input" required>
                            </div>
                        </div>
                        <div class="dash-form-group">
                            <label for="commentaire">Commentaires / Précisions</label>
                            <textarea name="commentaire" id="commentaire" class="dash-textarea" placeholder="Décrivez votre projet, le nombre de personnes, vos attentes..."></textarea>
                        </div>
                        <button type="submit" class="btn-dash btn-dash-primary">
                            <i class="fas fa-paper-plane"></i> Envoyer la demande
                        </button>
                    </form>
                </div>
            </div>

            <!-- LISTE DES RÉSERVATIONS -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-list" style="color:var(--primary-green);margin-right:8px;"></i> Historique</h3>
                    <!-- Filtres statut -->
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php foreach (['TOUS', 'EN ATTENTE', 'CONFIRMEE', 'ANNULEE', 'TERMINEE'] as $s): ?>
                            <a href="?statut=<?= urlencode($s) ?>"
                               class="btn-dash btn-dash-sm <?= $filter === $s ? 'btn-dash-primary' : 'btn-dash-outline' ?>">
                               <?= htmlspecialchars($s) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="dash-card-body">
                    <?php if (empty($reservations)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>Aucune réservation trouvée pour ce filtre.</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                            <tr>
                                        <th>#</th>
                                        <th>Prestation & Formule</th>
                                        <th>Tarif</th>
                                        <th>Date</th>
                                        <th>Heure</th>
                                        <th>Lieu</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($reservations as $r): ?>
                                <?php
                                $badgeClass = match(strtoupper($r['STATUS_RESERVATION'])) {
                                    'CONFIRMEE' => 'badge-confirm',
                                    'ANNULEE'   => 'badge-cancel',
                                    'TERMINEE'  => 'badge-done',
                                    default     => 'badge-waiting',
                                };
                                ?>
                                <tr>
                                    <td>#<?= (int) $r['ID_RESERVATION'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($r['LIB_PRESTATION']) ?></strong><br>
                                        <span style="font-size:0.8rem;color:#aaa;"><?= $r['LIBS_CATEGORIES'] ? $r['LIBS_CATEGORIES'] : 'Formule non spécifiée' ?></span>
                                    </td>
                                    <td>
                                        <?php if ($r['TOTAL_PRIX']): ?>
                                            <span style="color:var(--primary-green);font-weight:600;"><?= number_format((int)$r['TOTAL_PRIX'], 0, ',', ' ') ?> Ar</span>
                                        <?php else: ?>
                                            <span style="color:#777;">À définir</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($r['DATE_RESERVATION'])) ?></td>
                                    <td><?= substr($r['HEURE_RESERVATION'], 0, 5) ?></td>
                                    <td><?= htmlspecialchars($r['LIEU_RESERVATION']) ?></td>
                                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($r['STATUS_RESERVATION']) ?></span></td>
                                    <td style="white-space:nowrap;">
                                        <?php if ($r['STATUS_RESERVATION'] === 'EN ATTENTE'): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Annuler cette réservation ?');">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="id_reservation" value="<?= (int)$r['ID_RESERVATION'] ?>">
                                                <button class="btn-dash btn-dash-danger btn-dash-sm"><i class="fas fa-times"></i> Annuler</button>
                                            </form>
                                        <?php elseif ($r['STATUS_RESERVATION'] === 'TERMINEE' && !(int)$r['a_temoigne']): ?>
                                            <button class="btn-dash btn-dash-outline btn-dash-sm" onclick="openTemoModal(<?= (int)$r['ID_RESERVATION'] ?>)">
                                                <i class="fas fa-star"></i> Laisser un avis
                                            </button>
                                        <?php elseif ($r['STATUS_RESERVATION'] === 'TERMINEE' && (int)$r['a_temoigne']): ?>
                                            <span style="color:var(--primary-green);font-size:0.85rem;"><i class="fas fa-check"></i> Avis déposé</span>
                                        <?php else: ?>
                                            <span style="color:#555;">—</span>
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

<!-- MODAL TEMOIGNAGE -->
<div id="temoModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.7);backdrop-filter:blur(6px);align-items:center;justify-content:center;">
    <div style="background:#1a1a2e;border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:36px;width:100%;max-width:480px;box-shadow:0 30px 80px rgba(0,0,0,.5);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
            <h3 style="margin:0;font-size:1.15rem;"><i class="fas fa-star" style="color:#f5c518;margin-right:8px;"></i> Laisser un témoignage</h3>
            <button onclick="closeTemoModal()" style="background:none;border:none;color:#aaa;font-size:1.3rem;cursor:pointer;">×</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="temoignage">
            <input type="hidden" name="id_reservation" id="temo_id_resa" value="">
            <div style="margin-bottom:18px;">
                <label style="display:block;margin-bottom:10px;font-size:0.9rem;color:#ccc;">Votre note <span style="color:#e74c3c;">*</span></label>
                <div id="starSelector" style="display:flex;gap:8px;font-size:2rem;cursor:pointer;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star" data-val="<?= $i ?>" style="color:#444;transition:color .15s;"></i>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="note" id="noteInput" value="0" required>
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block;margin-bottom:8px;font-size:0.9rem;color:#ccc;">Votre message <span style="color:#e74c3c;">*</span></label>
                <textarea name="message" class="dash-textarea" placeholder="Partagez votre expérience avec le studio..." required style="min-height:100px;"></textarea>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="button" onclick="closeTemoModal()" class="btn-dash btn-dash-outline" style="flex:1;justify-content:center;">Annuler</button>
                <button type="submit" class="btn-dash btn-dash-primary" style="flex:1;justify-content:center;"><i class="fas fa-paper-plane"></i> Envoyer</button>
            </div>
        </form>
    </div>
</div>

<script>
// Sidebar toggle
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// Toggle formulaire
const btn    = document.getElementById('toggleForm');
const form   = document.getElementById('formResa');
const icon   = document.getElementById('toggleIcon');
btn?.addEventListener('click', () => {
    const open = form.style.display === 'block';
    form.style.display = open ? 'none' : 'block';
    icon.className = open ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
    btn.querySelector('span') && (btn.textContent = '');
});

// Chargement dynamique des catégories
const selPrest = document.getElementById('id_prestation');
const catContainer = document.getElementById('categories-container');
const tarifDiv = document.getElementById('tarif-info');
const tarifVal = document.getElementById('tarif-val');

function calculateTotal() {
    let total = 0;
    const checkboxes = document.querySelectorAll('input[name="id_categories[]"]:checked');
    checkboxes.forEach(cb => {
        total += parseInt(cb.dataset.tarif || 0);
    });
    
    if (checkboxes.length > 0) {
        tarifVal.textContent = total.toLocaleString('fr-FR') + ' Ar';
        tarifDiv.style.display = 'block';
    } else {
        tarifDiv.style.display = 'none';
    }
}

selPrest?.addEventListener('change', async () => {
    const idPrest = selPrest.value;
    catContainer.innerHTML = '<div style="color:#888;font-size:0.85rem;padding:10px 0;">— Chargement... —</div>';
    tarifDiv.style.display = 'none';

    if (!idPrest) {
        catContainer.innerHTML = '<div style="color:#888;font-size:0.85rem;padding:10px 0;">— Choisissez d\'abord une prestation —</div>';
        return;
    }

    try {
        const res  = await fetch('../../api/categories.php?id_prestation=' + idPrest);
        const data = await res.json();
        
        if (data.length === 0) {
            catContainer.innerHTML = '<div style="color:#888;font-size:0.85rem;padding:10px 0;">Aucune formule disponible</div>';
        } else {
            catContainer.innerHTML = '';
            data.forEach(cat => {
                const wrapper = document.createElement('label');
                wrapper.style.cssText = 'display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:8px;';
                wrapper.innerHTML = `
                    <input type="checkbox" name="id_categories[]" value="${cat.id}" data-tarif="${cat.tarif}" onchange="calculateTotal()" style="width:16px; height:16px; accent-color:var(--primary-green);">
                    <span style="font-size:0.9rem; color:var(--logo-black);">${cat.lib} — <strong style="color:var(--primary-green);">${cat.tarif_fmt} Ar</strong></span>
                `;
                catContainer.appendChild(wrapper);
            });
        }
    } catch(e) {
        catContainer.innerHTML = '<div style="color:#e74c3c;font-size:0.85rem;padding:10px 0;">— Erreur de chargement —</div>';
    }
});
// Modal témoignage
const temoModal = document.getElementById('temoModal');
function openTemoModal(id) {
    document.getElementById('temo_id_resa').value = id;
    temoModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    resetStars();
}
function closeTemoModal() {
    temoModal.style.display = 'none';
    document.body.style.overflow = '';
}
temoModal?.addEventListener('click', e => { if (e.target === temoModal) closeTemoModal(); });

// Sélecteur étoiles
const stars = document.querySelectorAll('#starSelector i');
const noteInput = document.getElementById('noteInput');
function resetStars() { stars.forEach(s => s.style.color = '#444'); noteInput.value = '0'; }
stars.forEach(star => {
    star.addEventListener('mouseenter', () => {
        const val = parseInt(star.dataset.val);
        stars.forEach(s => s.style.color = parseInt(s.dataset.val) <= val ? '#f5c518' : '#444');
    });
    star.addEventListener('mouseleave', () => {
        const cur = parseInt(noteInput.value);
        stars.forEach(s => s.style.color = parseInt(s.dataset.val) <= cur ? '#f5c518' : '#444');
    });
    star.addEventListener('click', () => {
        noteInput.value = star.dataset.val;
        stars.forEach(s => s.style.color = parseInt(s.dataset.val) <= parseInt(star.dataset.val) ? '#f5c518' : '#444');
    });
});
</script>
</body>
</html>
