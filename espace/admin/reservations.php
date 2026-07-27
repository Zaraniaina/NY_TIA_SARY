<?php

declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();
require_once __DIR__ . '/../../util/mailService.php';
require_once __DIR__ . '/../../util/prg_helper.php';
require_once __DIR__ . '/composante/tolbarDto.php';
//on changer le titre
$titre = "Gestion des réservations";

// ── Changement de statut via AJAX 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_statut'])) {
    header('Content-Type: application/json');
    $id     = (int) ($_POST['id'] ?? 0);
    $statut = trim($_POST['statut'] ?? '');
    $allowed = ['EN ATTENTE', 'CONFIRMEE', 'ANNULEE', 'TERMINEE'];
    if ($id && in_array($statut, $allowed, true)) {
        $stmt = $pdo->prepare('UPDATE RESERVATION SET STATUS_RESERVATION = ? WHERE ID_RESERVATION = ?');
        $stmt->execute([$statut, $id]);

        // Récupérer les informations de la réservation pour l'email
        $stmtResa = $pdo->prepare('SELECT r.*, c.PRENOM_CLIENT, c.NOM_CLIENT, a.EMAIL_AUTH, p.LIB_PRESTATION, r.LIEU_RESERVATION
                                    FROM RESERVATION r
                                    JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
                                    JOIN AUTHENTIFICATION a ON c.ID_AUTH = a.ID_AUTH
                                    JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
                                    WHERE r.ID_RESERVATION = ?');
        $stmtResa->execute([$id]);
        $resaInfo = $stmtResa->fetch(PDO::FETCH_ASSOC);

        // Si la réservation est CONFIRMEE, envoyer un email au client
        if ($statut === 'CONFIRMEE' && $resaInfo) {
            // S'assurer qu'un contrat est généré
            $chk = $pdo->prepare('SELECT COUNT(*) FROM CONTRAT WHERE ID_RESERVATION = ?');
            $chk->execute([$id]);
            if ((int)$chk->fetchColumn() === 0) {
                $pdo->prepare("INSERT INTO CONTRAT (ID_RESERVATION, STATUS_CONTRAT, DATE_CONTRAT) VALUES (?, 'EN ATTENTE', CURDATE())")
                    ->execute([$id]);
            }

            // Récupérer les catégories de la réservation
            $stmtCats = $pdo->prepare('SELECT c.LIB_CATEGORIE FROM CATEGORIE c JOIN RESERVATION_CATEGORIE rc ON c.ID_CATEGORIE = rc.ID_CATEGORIE WHERE rc.ID_RESERVATION = ?');
            $stmtCats->execute([$id]);
            $categoriesLib = $stmtCats->fetchAll(PDO::FETCH_COLUMN);

            // Calculer le total
            $stmtTotal = $pdo->prepare('SELECT SUM(PRIX) FROM RESERVATION_CATEGORIE WHERE ID_RESERVATION = ?');
            $stmtTotal->execute([$id]);
            $totalPrix = (int) $stmtTotal->fetchColumn();

            $clientNom = $resaInfo['NOM_CLIENT'] ?? 'Inconnu';
            $clientPrenom = $resaInfo['PRENOM_CLIENT'] ?? '';
            $clientEmail = $resaInfo['EMAIL_AUTH'] ?? '';
            $prestationLib = $resaInfo['LIB_PRESTATION'] ?? 'Non spécifiée';
            $lieuResa = $resaInfo['LIEU_RESERVATION'] ?? 'Non défini';
            $dateResa = $resaInfo['DATE_RESERVATION'];
            $heureResa = $resaInfo['HEURE_RESERVATION'];

            // Formater la date et l'heure
            $dateFormatee = date('d/m/Y', strtotime($dateResa));
            $heureFormatee = substr($heureResa, 0, 5);

            // Récupérer le ID du contrat
            $stmtContrat = $pdo->prepare('SELECT ID_CONTRAT FROM CONTRAT WHERE ID_RESERVATION = ?');
            $stmtContrat->execute([$id]);
            $idContrat = $stmtContrat->fetchColumn();

            // Créer une notification pour le client concernant le contrat
            if ($resaInfo && $idContrat) {
                $stmtNotif = $pdo->prepare("INSERT INTO notification (ID_CLIENT, TYPE_NOTIF, ID_REF_NOTIF, TITRE_NOTIF, MESS_NOTIF, LU_NOTIF, SUP_NOTIF, DATE_NOTIF) 
                                             VALUES (?, ?, ?, ?, ?, 0, 0, NOW())");
                $stmtNotif->execute([
                    $resaInfo['ID_CLIENT'],
                    'client_contrat',
                    $idContrat,
                    'Votre contrat est prêt',
                    'Votre réservation du ' . date('d/m/Y', strtotime($resaInfo['DATE_RESERVATION'])) . ' a été confirmée et votre contrat est disponible.'
                ]);
            }

            if ($clientEmail) {
                $mailService = new MailService();
                $reservationData = [
                    'client_nom' => $clientNom,
                    'client_prenom' => $clientPrenom,
                    'prestation' => $prestationLib,
                    'date_reservation' => $dateFormatee,
                    'heure_reservation' => $heureFormatee,
                    'lieu_reservation' => $lieuResa,
                    'categories' => $categoriesLib,
                    'total_prix' => $totalPrix,
                    'id_reservation' => $id,
                    'id_contrat' => $idContrat
                ];
                $mailService->sendReservationConfirmed($clientEmail, $reservationData);
            }
        }

        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit();
}

// ── Filtres ───────────────────────────────────────────────────
$filterStatut = $_GET['statut'] ?? 'TOUS';
$search       = trim($_GET['q'] ?? '');

$sql = 'SELECT r.*, p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT,
               ct.ID_CONTRAT, 
               SUM(rc.PRIX) AS TOTAL_PRIX, 
               GROUP_CONCAT(cat.LIB_CATEGORIE SEPARATOR \'<br>\') AS LIBS_CATEGORIES
        FROM RESERVATION r
        JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
        JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
        LEFT JOIN CONTRAT ct ON ct.ID_RESERVATION = r.ID_RESERVATION
        LEFT JOIN RESERVATION_CATEGORIE rc ON rc.ID_RESERVATION = r.ID_RESERVATION
        LEFT JOIN CATEGORIE cat ON cat.ID_CATEGORIE = rc.ID_CATEGORIE
        WHERE 1=1';
$params = [];
if ($filterStatut !== 'TOUS') {
    $sql .= ' AND r.STATUS_RESERVATION = ?';
    $params[] = $filterStatut;
}
if ($search) {
    $sql .= ' AND (c.NOM_CLIENT LIKE ? OR c.PRENOM_CLIENT LIKE ? OR p.LIB_PRESTATION LIKE ?)';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}
$sql .= ' GROUP BY r.ID_RESERVATION ORDER BY r.DATE_RESERVATION DESC, r.HEURE_RESERVATION DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();
// ── Récupérer les messages PRG pour affichage ──────────────────
$prgMessages = prg_get_messages();
// ── Marquer la notification comme lue puis nettoyer l'URL ─────
if (isset($_GET['mark_notif']) && (int) $_GET['mark_notif'] > 0) {
    $idNotif      = (int) $_GET['mark_notif'];
    $idResaSelect = (int) ($_GET['id'] ?? 0);

    try {
        $stmtMark = $pdo->prepare('UPDATE notification SET LU_NOTIF = 1 WHERE ID_NOTIF = ?');
        $stmtMark->execute([$idNotif]);
    } catch (PDOException $e) {
        // on ignore silencieusement, la redirection se fait quand même
    }

    header('Location: reservations.php' . ($idResaSelect > 0 ? '?id=' . $idResaSelect : ''));
    exit;
}

$selectedResaId = (int) ($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservations | Admin NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">

    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <style>
    /* ═══════════════════════════════════════════════════════════════
       MODAL DE CONFIRMATION — Changement de statut de réservation
       Styles scoped à cette page uniquement (hors dashboard.css)
       ═══════════════════════════════════════════════════════════════ */

    /* Conteneur principal : overlay + carte, caché par défaut */
    #modalConfirmStatut {
        display: none;
        position: fixed;
        inset: 0;                        /* Couvre tout l'écran */
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }

    /* Le modal s'affiche quand JS ajoute la classe .open */
    #modalConfirmStatut.open {
        display: flex;
    }

    /* Fond semi-transparent cliquable (permet de fermer en cliquant dessus) */
    #modalStatutOverlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(3px);
        cursor: pointer;
    }

    /* Carte blanche centrale */
    .modal-statut-card {
        position: relative;              /* Au-dessus de l'overlay */
        z-index: 1;
        background: var(--white);
        border-radius: 14px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.22);
        width: 100%;
        max-width: 440px;
        padding: 32px 28px 24px;
        animation: modalSlideIn 0.28s cubic-bezier(0.165, 0.84, 0.44, 1) both;
    }

    /* Animation d'entrée : scale + glissement depuis le bas */
    @keyframes modalSlideIn {
        from { opacity: 0; transform: scale(0.92) translateY(16px); }
        to   { opacity: 1; transform: scale(1)    translateY(0);    }
    }

    /* Icône ronde en haut de la carte */
    .modal-statut-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: #fff8e1;
        color: #e6a817;
        font-size: 1.6rem;
        margin: 0 auto 18px;
    }

    /* Titre du modal */
    .modal-statut-card h3 {
        font-family: var(--font-headings);
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--logo-black);
        text-align: center;
        margin-bottom: 10px;
    }

    /* Texte descriptif avec nom du client et statut */
    .modal-statut-card p {
        text-align: center;
        font-size: 0.9rem;
        color: var(--text-body);
        line-height: 1.55;
        margin-bottom: 0;
    }

    /* Mise en valeur du nom et du statut */
    .modal-statut-card p strong {
        color: var(--logo-black);
    }

    /* Bloc d'avertissement — visible uniquement pour le statut CONFIRMEE */
    #modalConfirmeeWarning {
        display: none;                   /* Caché par défaut, JS l'affiche si CONFIRMEE */
        align-items: flex-start;
        gap: 10px;
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 10px 14px;
        margin-top: 16px;
        font-size: 0.82rem;
        color: #856404;
        line-height: 1.5;
    }

    #modalConfirmeeWarning i {
        flex-shrink: 0;
        margin-top: 2px;
        color: #e6a817;
    }

    /* Ligne de boutons */
    .modal-statut-actions {
        display: flex;
        gap: 10px;
        margin-top: 24px;
    }

    /* Bouton Annuler (style neutre) */
    #btnModalCancel {
        flex: 1;
        padding: 10px 16px;
        border: 2px solid var(--input-border);
        border-radius: 8px;
        background: transparent;
        color: var(--text-body);
        font-family: var(--font-body);
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition-smooth);
    }

    #btnModalCancel:hover {
        border-color: #aaa;
        background: var(--bg-accent);
    }

    /* Bouton Confirmer (vert, couleur principale du studio) */
    #btnModalConfirm {
        flex: 1;
        padding: 10px 16px;
        border: none;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--primary-green), #2a5c3a);
        color: #fff;
        font-family: var(--font-body);
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition-smooth);
        box-shadow: 0 4px 14px rgba(55, 125, 73, 0.35);
    }

    #btnModalConfirm:hover {
        background: linear-gradient(135deg, #2a5c3a, #1e4028);
        box-shadow: 0 6px 20px rgba(55, 125, 73, 0.45);
        transform: translateY(-1px);
    }

    /* ── Responsive mobile ── */
    @media (max-width: 480px) {
        .modal-statut-card {
            padding: 24px 18px 20px;
            border-radius: 12px;
        }

        /* Boutons empilés verticalement sur petit écran */
        .modal-statut-actions {
            flex-direction: column;
        }
    }
    </style>

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
                    <span>Réservations</span>
                </nav>

                <!-- FILTRES & RECHERCHE -->
                <div class="dash-action-row">
                    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                        <div class="dash-search-bar">
                            <i class="fas fa-search"></i>
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Client, prestation...">
                        </div>
                        <select name="statut" class="dash-select" style="width:auto;min-width:160px;" onchange="this.form.submit()">
                            <?php foreach (['TOUS', 'EN ATTENTE', 'CONFIRMEE', 'ANNULEE', 'TERMINEE'] as $s): ?>
                                <option value="<?= $s ?>" <?= $filterStatut === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-dash btn-dash-primary"><i class="fas fa-search"></i> Filtrer</button>
                    </form>
                    <span style="font-size:0.85rem;color:#888;"><?= count($reservations) ?> résultat(s)</span>
                </div>

                <div class="dash-card">
                    <div class="dash-card-body">
                        <?php if (empty($reservations)): ?>
                            <div class="empty-state"><i class="fas fa-calendar-times"></i>
                                <p>Aucune réservation trouvée.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="dash-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Client</th>
                                            <th>Prestation & Formule</th>
                                            <th>Tarif</th>
                                            <th>Date</th>
                                            <th>Heure</th>
                                            <th>Lieu</th>
                                            <th>Statut</th>
                                            <th>Contrat</th>
                                            <th>Modifier statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reservations as $r): ?>
                                            <?php
                                            $bc = match (strtoupper($r['STATUS_RESERVATION'])) {
                                                'CONFIRMEE' => 'badge-confirm',
                                                'ANNULEE'   => 'badge-cancel',
                                                'TERMINEE'  => 'badge-done',
                                                default     => 'badge-waiting',
                                            };
                                            ?>
                                            <?php $isSelected = $selectedResaId > 0 && (int)$r['ID_RESERVATION'] === $selectedResaId; ?>
                                            <tr id="row-<?= (int)$r['ID_RESERVATION'] ?>" <?= $isSelected ? ' class="row-highlighted"' : '' ?>>
                                                <td>#<?= (int) $r['ID_RESERVATION'] ?></td>
                                                <td><strong><?= htmlspecialchars($r['PRENOM_CLIENT'] . ' ' . $r['NOM_CLIENT']) ?></strong></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($r['LIB_PRESTATION']) ?></strong><br>
                                                    <span style="font-size:0.8rem;color:#aaa;"><?= $r['LIBS_CATEGORIES'] ? $r['LIBS_CATEGORIES'] : 'Formule non spécifiée' ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($r['TOTAL_PRIX']): ?>
                                                        <span style="color:var(--primary-green);font-weight:600;"><?= number_format((int)$r['TOTAL_PRIX'], 0, ',', ' ') ?> Ar</span>
                                                    <?php else: ?>
                                                        <span style="color:#777;">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($r['DATE_RESERVATION'])) ?></td>
                                                <td><?= substr($r['HEURE_RESERVATION'], 0, 5) ?></td>
                                                <td><?= htmlspecialchars($r['LIEU_RESERVATION']) ?></td>
                                                <td><span class="badge <?= $bc ?>" id="badge-<?= (int)$r['ID_RESERVATION'] ?>"><?= htmlspecialchars($r['STATUS_RESERVATION']) ?></span></td>
                                                <td>
                                                    <?php if ($r['ID_CONTRAT']): ?>
                                                        <a href="contrats.php" title="Voir le contrat" style="color:var(--primary-green);">
                                                            <i class="fas fa-file-signature"></i>
                                                        </a>
                                                    <?php elseif ($r['STATUS_RESERVATION'] === 'CONFIRMEE'): ?>
                                                        <a href="contrats.php?id_resa=<?= (int)$r['ID_RESERVATION'] ?>" title="Créer un contrat" class="btn-dash btn-dash-outline btn-dash-sm" style="font-size:0.75rem;padding:3px 8px;">
                                                            <i class="fas fa-plus"></i> Contrat
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="color:#555;font-size:0.8rem;">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <select class="dash-select statut-select" style="padding:6px 10px;font-size:0.8rem;width:auto;"
                                                        data-id="<?= (int)$r['ID_RESERVATION'] ?>">
                                                        <?php foreach (['EN ATTENTE', 'CONFIRMEE', 'ANNULEE', 'TERMINEE'] as $s): ?>
                                                            <option value="<?= $s ?>" <?= $r['STATUS_RESERVATION'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
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

    <!-- ═══════════════════════════════════════════════════════════════
         MODAL DE CONFIRMATION — Changement de statut de réservation
         Affiché avant tout envoi AJAX pour éviter les changements accidentels.
         Le nom du client et le nouveau statut sont injectés dynamiquement par JS.
         ═══════════════════════════════════════════════════════════════ -->
    <div id="modalConfirmStatut" role="dialog" aria-modal="true" aria-labelledby="modalStatutTitle">

        <!-- Overlay sombre — clic dessus = annulation (même effet que le bouton Annuler) -->
        <div id="modalStatutOverlay"></div>

        <!-- Carte principale du modal -->
        <div class="modal-statut-card">

            <!-- Icône d'échange de statut -->
            <div class="modal-statut-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>

            <!-- Titre -->
            <h3 id="modalStatutTitle">Confirmer le changement de statut</h3>

            <!-- Message dynamique : nom du client et nouveau statut remplis par JS -->
            <p>
                Voulez-vous changer le statut de la réservation de
                <strong id="modalClientName">—</strong>
                vers <strong id="modalNewStatut">—</strong>&nbsp;?
            </p>

            <!-- Avertissement spécial affiché uniquement quand le statut est CONFIRMEE -->
            <div id="modalConfirmeeWarning">
                <i class="fas fa-exclamation-triangle"></i>
                <span>
                    <strong>Attention&nbsp;:</strong> Cette action enverra automatiquement
                    un email de confirmation au client et générera un contrat.
                </span>
            </div>

            <!-- Boutons : Annuler (restaure l'ancien statut) ou Confirmer (envoie l'AJAX) -->
            <div class="modal-statut-actions">
                <button id="btnModalCancel" type="button">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button id="btnModalConfirm" type="button">
                    <i class="fas fa-check"></i> Confirmer
                </button>
            </div>
        </div>
    </div>

    <script>
        const toggle = document.getElementById('sidebarToggle');
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

        // Changement de statut via AJAX
        const badgeMap = {
            'EN ATTENTE': 'badge-waiting',
            'CONFIRMEE': 'badge-confirm',
            'ANNULEE': 'badge-cancel',
            'TERMINEE': 'badge-done',
        };
        // ── Références aux éléments du modal ──────────────────────────
        const modalStatut        = document.getElementById('modalConfirmStatut');
        const modalOverlay       = document.getElementById('modalStatutOverlay');
        const modalClientName    = document.getElementById('modalClientName');
        const modalNewStatut     = document.getElementById('modalNewStatut');
        const modalWarning       = document.getElementById('modalConfirmeeWarning');
        const btnModalCancel     = document.getElementById('btnModalCancel');
        const btnModalConfirm    = document.getElementById('btnModalConfirm');

        /**
         * Stocke les informations du changement en attente de confirmation.
         * Réinitialisé à null après confirmation ou annulation.
         * @type {{ id: string, newStatut: string, oldStatut: string, selectEl: HTMLSelectElement }|null}
         */
        let pendingStatutChange = null;

        /**
         * Envoie la requête AJAX pour mettre à jour le statut de la réservation.
         * Met à jour le badge dans le tableau et affiche un toast de résultat.
         *
         * @param {string}          id       - ID de la réservation
         * @param {string}          statut   - Nouveau statut à appliquer
         * @param {HTMLSelectElement} sel    - Le <select> concerné (pour restaurer si erreur)
         */
        async function sendStatutAjax(id, statut, sel) {
            const fd = new FormData();
            fd.append('ajax_statut', '1');
            fd.append('id', id);
            fd.append('statut', statut);

            const res  = await fetch('', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.ok) {
                // Mettre à jour le badge de statut visible dans le tableau
                const badge = document.getElementById('badge-' + id);
                badge.className  = 'badge ' + (badgeMap[statut] || 'badge-waiting');
                badge.textContent = statut;
                Toastify({
                    text: "Statut mis à jour avec succès.",
                    duration: 6000, gravity: "top", position: "right",
                    close: true, stopOnFocus: true,
                    style: {
                        background: "linear-gradient(135deg, #377d49, #2a5c3a)",
                        borderRadius: "6px",
                        fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
                        fontWeight: "600",
                        boxShadow: "0 10px 30px rgba(0, 0, 0, 0.25)",
                    },
                }).showToast();
            } else {
                // En cas d'erreur serveur, restaurer l'ancienne valeur dans le select
                if (pendingStatutChange) sel.value = pendingStatutChange.oldStatut;
                Toastify({
                    text: "Erreur lors de la mise à jour du statut.",
                    duration: 6000, gravity: "top", position: "right",
                    close: true, stopOnFocus: true,
                    style: {
                        background: "linear-gradient(135deg, #d93d3d, #a82c2c)",
                        borderRadius: "6px",
                        fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
                        fontWeight: "600",
                        boxShadow: "0 10px 30px rgba(0, 0, 0, 0.25)",
                    },
                }).showToast();
            }
        }

        /**
         * Ouvre le modal de confirmation en injectant le nom du client
         * et le nouveau statut, et en affichant l'avertissement si CONFIRMEE.
         *
         * @param {string} clientName - Nom complet du client
         * @param {string} newStatut  - Nouveau statut demandé
         */
        function openStatutModal(clientName, newStatut) {
            modalClientName.textContent = clientName;
            modalNewStatut.textContent  = newStatut;
            // Afficher le bloc d'avertissement uniquement pour CONFIRMEE
            // car cette action génère un contrat et envoie un email au client
            modalWarning.style.display  = (newStatut === 'CONFIRMEE') ? 'flex' : 'none';
            // Afficher le modal + bloquer le scroll de la page
            modalStatut.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        /**
         * Ferme le modal et optionnellement restaure l'ancienne valeur dans le select.
         *
         * @param {boolean} restoreOldValue - Si true, remet l'ancien statut dans le <select>
         */
        function closeStatutModal(restoreOldValue = true) {
            modalStatut.classList.remove('open');
            document.body.style.overflow = '';
            // Restaurer l'ancien statut si l'admin annule
            if (restoreOldValue && pendingStatutChange) {
                pendingStatutChange.selectEl.value = pendingStatutChange.oldStatut;
            }
            pendingStatutChange = null;
        }

        // ── Intercepter le changement de statut sur chaque select ──────
        document.querySelectorAll('.statut-select').forEach(sel => {

            // Mémoriser la valeur au focus pour pouvoir la restaurer si annulation
            sel.addEventListener('focus', () => {
                sel.dataset.oldValue = sel.value;
            });

            // Sur changement : ouvrir le modal au lieu d'envoyer l'AJAX directement
            sel.addEventListener('change', () => {
                const id        = sel.dataset.id;
                const newStatut = sel.value;
                const oldStatut = sel.dataset.oldValue || sel.value;

                // Récupérer le nom du client depuis la colonne 2 de la même ligne
                const row        = sel.closest('tr');
                const clientName = row
                    ? (row.querySelector('td:nth-child(2) strong')?.textContent?.trim() || 'ce client')
                    : 'ce client';

                // Stocker les infos pour que les boutons du modal puissent y accéder
                pendingStatutChange = { id, newStatut, oldStatut, selectEl: sel };

                // Ouvrir le modal de confirmation
                openStatutModal(clientName, newStatut);
            });
        });

        // Bouton Annuler → fermer le modal et restaurer l'ancien statut dans le select
        btnModalCancel.addEventListener('click', () => closeStatutModal(true));

        // Clic sur l'overlay (fond sombre) → même effet qu'Annuler
        modalOverlay.addEventListener('click', () => closeStatutModal(true));

        // Touche Échap → même effet qu'Annuler
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalStatut.classList.contains('open')) {
                closeStatutModal(true);
            }
        });

        // Bouton Confirmer → envoyer l'AJAX puis fermer le modal
        btnModalConfirm.addEventListener('click', async () => {
            if (!pendingStatutChange) return;
            const { id, newStatut, selectEl } = pendingStatutChange;
            // Fermer sans restaurer : la confirmation est validée
            closeStatutModal(false);
            await sendStatutAjax(id, newStatut, selectEl);
        });
        const selectedRow = document.querySelector('.row-highlighted');
        if (selectedRow) {
            selectedRow.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    </script>

    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <?php if (!empty($prgMessages)): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                <?= prg_render_toasts($prgMessages) ?>
            });
        </script>
    <?php endif; ?>

</body>

</html>