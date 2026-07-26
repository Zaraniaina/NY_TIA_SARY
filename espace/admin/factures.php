<?php

declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
require_once __DIR__ . '/../../util/prg_helper.php';
requireAdmin();

require_once __DIR__ . '/composante/tolbarDto.php';
$titre = "Gestion des factures";

// ── TRAITEMENT POST (PRG Pattern) ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Création d'une facture ────────────────────────────────────
    if ($action === 'create') {
        $idContrat = (int) ($_POST['id_contrat'] ?? 0);

        if (!$idContrat) {
            prg_set_message('error', 'Veuillez sélectionner un contrat.');
        } else {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM FACTURE WHERE ID_CONTRAT = ?');
            $chk->execute([$idContrat]);
            if ((int)$chk->fetchColumn() > 0) {
                prg_set_message('error', 'Une facture existe déjà pour ce contrat.');
            } else {
                $annee  = date('Y');
                $cntStmt = $pdo->query("SELECT COUNT(*) FROM FACTURE WHERE YEAR(DATE_FACTURE) = $annee");
                $cnt    = (int)$cntStmt->fetchColumn() + 1;
                $numFac = 'FAC-' . $annee . '-' . str_pad((string)$cnt, 4, '0', STR_PAD_LEFT);

                $stmtPrice = $pdo->prepare(
                    'SELECT SUM(rc.PRIX)
                     FROM CONTRAT ct
                     JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
                     LEFT JOIN RESERVATION_CATEGORIE rc ON rc.ID_RESERVATION = r.ID_RESERVATION
                     WHERE ct.ID_CONTRAT = ?'
                );
                $stmtPrice->execute([$idContrat]);
                $montant = (int)($stmtPrice->fetchColumn() ?: 0);

                $pdo->prepare("INSERT INTO FACTURE (ID_CONTRAT, NUM_FACTURE, STATUS_FACTURE, MONTANT_FACTURE, DATE_FACTURE) VALUES (?, ?, 'NON PAYEE', ?, CURDATE())")
                    ->execute([$idContrat, $numFac, $montant]);
                prg_set_message('success', "Facture $numFac créée avec succès.");
            }
        }
        prg_redirect();
    }

    // ── Enregistrement d'un paiement ──────────────────────────────
    if ($action === 'add_payment') {
        $idFac    = (int) ($_POST['id_facture'] ?? 0);
        $montantP = (int) ($_POST['montant_paiement'] ?? 0);
        $dateP    = trim($_POST['date_paiement'] ?? date('Y-m-d'));

        if (!$idFac || $montantP < 1) {
            prg_set_message('error', 'Le montant du paiement doit être supérieur ou égal à 1.');
        } else {
            // Récupérer le montant total de la facture
            $stmtFac = $pdo->prepare('SELECT MONTANT_FACTURE FROM FACTURE WHERE ID_FACTURE = ?');
            $stmtFac->execute([$idFac]);
            $montantTotal = (int)($stmtFac->fetchColumn() ?: 0);

            // Calculer le total déjà payé
            $stmtPaid = $pdo->prepare('SELECT COALESCE(SUM(MONTANT_PAIEMENT), 0) FROM PAIEMENT WHERE ID_FACTURE = ?');
            $stmtPaid->execute([$idFac]);
            $dejaPaye = (int)$stmtPaid->fetchColumn();

            if ($montantP > ($montantTotal - $dejaPaye)) {
                prg_set_message('error', 'Le montant saisi dépasse le reste à payer (' . number_format($montantTotal - $dejaPaye, 0, ',', ' ') . ' Ar).');
            } else {
                // Insérer le paiement
                $pdo->prepare('INSERT INTO PAIEMENT (ID_FACTURE, DATE_PAIEMENT, MONTANT_PAIEMENT) VALUES (?, ?, ?)')
                    ->execute([$idFac, $dateP, $montantP]);
                $idPaiementInsert = $pdo->lastInsertId();

                // --- Notification Client ---
                $stmtCli = $pdo->prepare('
                    SELECT r.ID_CLIENT 
                    FROM FACTURE f 
                    JOIN CONTRAT c ON f.ID_CONTRAT = c.ID_CONTRAT 
                    JOIN RESERVATION r ON c.ID_RESERVATION = r.ID_RESERVATION 
                    WHERE f.ID_FACTURE = ?
                ');
                $stmtCli->execute([$idFac]);
                $idClient = $stmtCli->fetchColumn();
                if ($idClient) {
                    $pdo->prepare("
                        INSERT INTO notification (TYPE_NOTIF, ID_REF_NOTIF, TITRE_NOTIF, MESS_NOTIF, LU_NOTIF, SUP_NOTIF, ID_CLIENT) 
                        VALUES ('client_paiement', ?, 'Paiement enregistré', ?, 0, 0, ?)
                    ")->execute([$idPaiementInsert, "Votre paiement de " . number_format((float)$montantP, 0, ',', ' ') . " Ar a bien été pris en compte.", $idClient]);
                }

                // Recalculer le total payé et mettre à jour le statut
                $stmtPaid2 = $pdo->prepare('SELECT COALESCE(SUM(MONTANT_PAIEMENT), 0) FROM PAIEMENT WHERE ID_FACTURE = ?');
                $stmtPaid2->execute([$idFac]);
                $nouveauPaye = (int)$stmtPaid2->fetchColumn();

                if ($nouveauPaye >= $montantTotal) {
                    $newStatus = 'PAYEE';
                } elseif ($nouveauPaye > 0) {
                    $newStatus = 'PARTIELLEMENT PAYEE';
                } else {
                    $newStatus = 'NON PAYEE';
                }
                $pdo->prepare('UPDATE FACTURE SET STATUS_FACTURE = ? WHERE ID_FACTURE = ?')
                    ->execute([$newStatus, $idFac]);

                prg_set_message('success', 'Paiement de ' . number_format($montantP, 0, ',', ' ') . ' Ar enregistré avec succès.');
            }
        }
        prg_redirect();
    }

    // ── Suppression d'un paiement ─────────────────────────────────
    if ($action === 'delete_payment') {
        $idPay = (int) ($_POST['id_paiement'] ?? 0);
        $idFac = (int) ($_POST['id_facture'] ?? 0);
        if ($idPay && $idFac) {
            $pdo->prepare('DELETE FROM PAIEMENT WHERE ID_PAIEMENT = ?')->execute([$idPay]);

            // Recalculer le statut
            $stmtFac = $pdo->prepare('SELECT MONTANT_FACTURE FROM FACTURE WHERE ID_FACTURE = ?');
            $stmtFac->execute([$idFac]);
            $montantTotal = (int)($stmtFac->fetchColumn() ?: 0);
            $stmtPaid = $pdo->prepare('SELECT COALESCE(SUM(MONTANT_PAIEMENT), 0) FROM PAIEMENT WHERE ID_FACTURE = ?');
            $stmtPaid->execute([$idFac]);
            $paye = (int)$stmtPaid->fetchColumn();

            if ($paye >= $montantTotal && $montantTotal > 0) {
                $newStatus = 'PAYEE';
            } elseif ($paye > 0) {
                $newStatus = 'PARTIELLEMENT PAYEE';
            } else {
                $newStatus = 'NON PAYEE';
            }
            $pdo->prepare('UPDATE FACTURE SET STATUS_FACTURE = ? WHERE ID_FACTURE = ?')->execute([$newStatus, $idFac]);
            prg_set_message('success', 'Paiement supprimé.');
        } else {
            prg_set_message('error', 'Paiement invalide.');
        }
        prg_redirect();
    }

    // ── Suppression d'une facture ─────────────────────────────────
    if ($action === 'delete') {
        $idFac = (int) ($_POST['id_facture'] ?? 0);
        if ($idFac) {
            $pdo->prepare('DELETE FROM FACTURE WHERE ID_FACTURE = ?')->execute([$idFac]);
            prg_set_message('success', 'Facture supprimée.');
        } else {
            prg_set_message('error', 'Facture invalide.');
        }
        prg_redirect();
    }
}

// ── Récupérer les messages PRG pour affichage ──────────────────
$prgMessages = prg_get_messages();

// ── Pré-sélection depuis l'URL (venant de contrats.php) ───────
$preselContrat = (int) ($_GET['id_contrat'] ?? 0);

// ── Contrats sans facture (pour le formulaire) ────────────────
$contratsSansFac = $pdo->query(
    'SELECT ct.ID_CONTRAT, ct.DATE_CONTRAT, r.DATE_RESERVATION, r.ID_RESERVATION,
            p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT
     FROM CONTRAT ct
     JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
     LEFT JOIN FACTURE f ON f.ID_CONTRAT = ct.ID_CONTRAT
     WHERE f.ID_FACTURE IS NULL
     ORDER BY ct.DATE_CONTRAT DESC'
)->fetchAll();

// ── Recherche et Liste complète des factures avec soldes ───────
$search = trim($_GET['q'] ?? '');
$sql = 'SELECT f.*, ct.DATE_CONTRAT, r.DATE_RESERVATION, r.LIEU_RESERVATION,
                p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT, c.ID_CLIENT,
                COALESCE(SUM(pay.MONTANT_PAIEMENT), 0) AS MONTANT_PAYE,
                f.MONTANT_FACTURE - COALESCE(SUM(pay.MONTANT_PAIEMENT), 0) AS RESTE_A_PAYER
         FROM FACTURE f
         JOIN CONTRAT ct ON f.ID_CONTRAT = ct.ID_CONTRAT
         JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
         JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
         JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
         LEFT JOIN PAIEMENT pay ON pay.ID_FACTURE = f.ID_FACTURE';

$params = [];
if ($search !== '') {
    $sql .= ' WHERE f.NUM_FACTURE LIKE ? OR c.NOM_CLIENT LIKE ? OR c.PRENOM_CLIENT LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}
$sql .= ' GROUP BY f.ID_FACTURE ORDER BY f.DATE_FACTURE DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$factures = $stmt->fetchAll();

// ── Stats globales ─────────────────────────────────────────────
$stmtStats = $pdo->query(
    'SELECT
        COUNT(f.ID_FACTURE) AS nb_total,
        COALESCE(SUM(f.MONTANT_FACTURE), 0) AS total_facture,
        COALESCE(SUM(pay.MONTANT_PAIEMENT), 0) AS total_paye
     FROM FACTURE f
     LEFT JOIN PAIEMENT pay ON pay.ID_FACTURE = f.ID_FACTURE'
);
$stats = $stmtStats->fetch();
$totalFacture = (int)($stats['total_facture'] ?? 0);
$totalPaye    = (int)($stats['total_paye'] ?? 0);
$totalRestant = $totalFacture - $totalPaye;

// ── Facture sélectionnée pour voir ses paiements ───────────────
$viewFacId = (int)($_GET['view_payments'] ?? 0);
$paiementsFac = [];
$facSelected  = null;
if ($viewFacId) {
    $stmtSel = $pdo->prepare(
        'SELECT f.*, c.NOM_CLIENT, c.PRENOM_CLIENT, p.LIB_PRESTATION
         FROM FACTURE f
         JOIN CONTRAT ct ON f.ID_CONTRAT = ct.ID_CONTRAT
         JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
         JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
         JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
         WHERE f.ID_FACTURE = ?'
    );
    $stmtSel->execute([$viewFacId]);
    $facSelected = $stmtSel->fetch();

    $stmtPays = $pdo->prepare(
        'SELECT * FROM PAIEMENT WHERE ID_FACTURE = ? ORDER BY DATE_PAIEMENT DESC'
    );
    $stmtPays->execute([$viewFacId]);
    $paiementsFac = $stmtPays->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factures | Admin NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        /* ── Stats cards ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .stat-icon.green {
            background: rgba(55, 125, 73, .15);
            color: var(--primary-green);
        }

        .stat-icon.blue {
            background: rgba(41, 128, 185, .15);
            color: #2980b9;
        }

        .stat-icon.orange {
            background: rgba(243, 156, 18, .15);
            color: #f39c12;
        }

        .stat-icon.red {
            background: rgba(217, 61, 61, .15);
            color: #d93d3d;
        }

        .stat-label {
            font-size: .78rem;
            color: var(--text-muted);
            margin-bottom: 2px;
        }

        .stat-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* ── Statut badge ── */
        .badge-partial {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: .72rem;
            font-weight: 700;
            background: rgba(243, 156, 18, .15);
            color: #f39c12;
            border: 1px solid rgba(243, 156, 18, .3);
        }

        .badge-confirm {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-cancel {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* ── Progress bar paiement ── */
        .payment-progress {
            margin-top: 4px;
        }

        .progress-bar-wrap {
            background: rgba(255, 255, 255, .08);
            border-radius: 4px;
            height: 6px;
            overflow: hidden;
            margin-top: 3px;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #377d49, #56b870);
            transition: width .4s ease;
        }

        .progress-label {
            font-size: .72rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* ── Modal paiement — thème blanc ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .55);
            backdrop-filter: blur(6px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.open {
            display: flex;
        }

        .modal-box {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            padding: 32px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 24px 80px rgba(0, 0, 0, .28);
            animation: modalIn .25s ease;
            position: relative;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(.95) translateY(12px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* En-tête coloré */
        .modal-header-band {
            background: linear-gradient(135deg, #377d49, #4ea865);
            margin: -32px -32px 24px -32px;
            padding: 22px 28px 20px;
            border-radius: 20px 20px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header-band .modal-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .modal-header-band .modal-title i {
            opacity: .9;
        }

        .modal-close {
            background: rgba(255, 255, 255, .2);
            border: none;
            cursor: pointer;
            color: #fff;
            font-size: .95rem;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .2s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, .35);
        }

        .modal-subtitle {
            font-size: .82rem;
            color: #666;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f0f0f0;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: .82rem;
            color: #444;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: .92rem;
            color: #1a1a1a;
            background: #fafafa;
            box-sizing: border-box;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }

        .form-group input:focus {
            border-color: #377d49;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(55, 125, 73, .12);
        }

        .modal-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f0faf4;
            border: 1px solid #c6e8d1;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: .88rem;
        }

        .modal-info-row .label {
            color: #555;
            font-weight: 500;
        }

        .modal-info-row .value {
            font-weight: 800;
            color: #377d49;
            font-size: 1rem;
        }

        /* Boutons dans le modal blanc */
        .modal-btn-submit {
            flex: 1;
            padding: 11px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            background: linear-gradient(135deg, #377d49, #4ea865);
            color: #fff;
            font-size: .92rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: opacity .2s;
        }

        .modal-btn-submit:hover {
            opacity: .88;
        }

        .modal-btn-cancel {
            flex: 1;
            padding: 11px;
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            background: #fff;
            color: #555;
            font-size: .92rem;
            font-weight: 600;
            transition: background .2s, border-color .2s;
        }

        .modal-btn-cancel:hover {
            background: #f5f5f5;
            border-color: #ccc;
        }

        /* ── Historique paiements d'une facture ── */
        .pay-hist-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            border-radius: 8px;
            background: rgba(255, 255, 255, .03);
            border: 1px solid var(--border-color);
            margin-bottom: 8px;
        }

        .pay-hist-item:hover {
            background: rgba(255, 255, 255, .06);
        }

        .pay-date {
            font-size: .8rem;
            color: var(--text-muted);
        }

        .pay-amount {
            font-weight: 700;
            color: var(--primary-green);
            font-size: .95rem;
        }

        .pay-delete-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #d93d3d;
            opacity: .6;
            font-size: .85rem;
            padding: 4px 6px;
            border-radius: 4px;
            transition: opacity .2s, background .2s;
        }

        .pay-delete-btn:hover {
            opacity: 1;
            background: rgba(217, 61, 61, .1);
        }

        /* ── Table améliorée ── */
        .amount-col {
            white-space: nowrap;
        }

        .action-btn-pay {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: .75rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: linear-gradient(135deg, #377d49, #2a5c3a);
            color: #fff;
            transition: opacity .2s;
        }

        .action-btn-pay:hover {
            opacity: .85;
        }

        .action-btn-view {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: .75rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--border-color);
            background: rgba(255, 255, 255, .05);
            color: var(--text-primary);
            text-decoration: none;
            transition: background .2s;
        }

        .action-btn-view:hover {
            background: rgba(255, 255, 255, .1);
        }

        /* ── Panel détails paiements ── */
        .pay-panel {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .pay-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .pay-panel-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pay-panel-close {
            color: var(--text-muted);
            text-decoration: none;
            font-size: .82rem;
        }

        .pay-panel-close:hover {
            color: var(--text-primary);
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
                    <a href="contrats.php">Contrats</a>
                    <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                    <span>Factures</span>
                </nav>

                <!-- STATS -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-file-invoice"></i></div>
                        <div>
                            <div class="stat-label">Total factures</div>
                            <div class="stat-value"><?= (int)($stats['nb_total'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-coins"></i></div>
                        <div>
                            <div class="stat-label">Total facturé</div>
                            <div class="stat-value"><?= number_format($totalFacture, 0, ',', ' ') ?> Ar</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <div class="stat-label">Total encaissé</div>
                            <div class="stat-value"><?= number_format($totalPaye, 0, ',', ' ') ?> Ar</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
                        <div>
                            <div class="stat-label">Reste à encaisser</div>
                            <div class="stat-value"><?= number_format($totalRestant, 0, ',', ' ') ?> Ar</div>
                        </div>
                    </div>
                </div>

                <div style="width:100%;">

                    <!-- PANEL HISTORIQUE D'UNE FACTURE -->
                    <?php if ($facSelected):
                        $sumPaye = array_sum(array_column($paiementsFac, 'MONTANT_PAIEMENT'));
                        $reste   = $facSelected['MONTANT_FACTURE'] - $sumPaye;
                        $pct     = $facSelected['MONTANT_FACTURE'] > 0 ? min(100, round($sumPaye / $facSelected['MONTANT_FACTURE'] * 100)) : 0;
                    ?>
                        <div class="pay-panel">
                            <div class="pay-panel-header">
                                <div class="pay-panel-title">
                                    <i class="fas fa-history" style="color:var(--primary-green);"></i>
                                    Historique des paiements — <?= htmlspecialchars($facSelected['NUM_FACTURE']) ?>
                                    <small style="font-weight:400;color:var(--text-muted);">
                                        (<?= htmlspecialchars($facSelected['PRENOM_CLIENT'] . ' ' . $facSelected['NOM_CLIENT']) ?>)
                                    </small>
                                </div>
                                <a href="factures.php<?= $search ? '?q=' . urlencode($search) : '' ?>" class="pay-panel-close">
                                    <i class="fas fa-times"></i> Fermer
                                </a>
                            </div>

                            <!-- Résumé solde -->
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:20px;">
                                <div style="background:rgba(255,255,255,.04);border-radius:8px;padding:12px 14px;">
                                    <div style="font-size:.76rem;color:var(--text-muted);margin-bottom:3px;">Montant total</div>
                                    <div style="font-weight:700;font-size:1rem;"><?= number_format((int)$facSelected['MONTANT_FACTURE'], 0, ',', ' ') ?> Ar</div>
                                </div>
                                <div style="background:rgba(55,125,73,.12);border-radius:8px;padding:12px 14px;">
                                    <div style="font-size:.76rem;color:var(--text-muted);margin-bottom:3px;">Déjà payé</div>
                                    <div style="font-weight:700;font-size:1rem;color:var(--primary-green);"><?= number_format($sumPaye, 0, ',', ' ') ?> Ar</div>
                                </div>
                                <div style="background:rgba(243,156,18,.1);border-radius:8px;padding:12px 14px;">
                                    <div style="font-size:.76rem;color:var(--text-muted);margin-bottom:3px;">Reste à payer</div>
                                    <div style="font-weight:700;font-size:1rem;color:#f39c12;"><?= number_format($reste, 0, ',', ' ') ?> Ar</div>
                                </div>
                            </div>

                            <!-- Barre progression -->
                            <div class="progress-bar-wrap" style="height:10px;margin-bottom:6px;">
                                <div class="progress-bar-fill" style="width:<?= $pct ?>%;"></div>
                            </div>
                            <div class="progress-label" style="text-align:right;"><?= $pct ?>% payé</div>

                            <!-- Liste des paiements -->
                            <div style="margin-top:16px;">
                                <?php if (empty($paiementsFac)): ?>
                                    <div class="empty-state" style="padding:20px 0;">
                                        <i class="fas fa-receipt"></i>
                                        <p>Aucun paiement enregistré pour cette facture.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($paiementsFac as $pay): ?>
                                        <div class="pay-hist-item">
                                            <div>
                                                <div class="pay-amount"><i class="fas fa-coins" style="font-size:.8rem;"></i> <?= number_format((int)$pay['MONTANT_PAIEMENT'], 0, ',', ' ') ?> Ar</div>
                                                <div class="pay-date"><i class="fas fa-calendar-alt" style="font-size:.72rem;"></i> <?= date('d/m/Y', strtotime($pay['DATE_PAIEMENT'])) ?></div>
                                            </div>
                                            <form method="POST" style="margin:0;" id="deletePaymentForm-<?= (int)$pay['ID_PAIEMENT'] ?>">
                                                <input type="hidden" name="action" value="delete_payment">
                                                <input type="hidden" name="id_paiement" value="<?= (int)$pay['ID_PAIEMENT'] ?>">
                                                <input type="hidden" name="id_facture" value="<?= (int)$facSelected['ID_FACTURE'] ?>">
                                                <button type="button" class="pay-delete-btn confirm-delete-btn"
                                                    data-form-id="deletePaymentForm-<?= (int)$pay['ID_PAIEMENT'] ?>"
                                                    data-message="Supprimer ce paiement ?"
                                                    title="Supprimer ce paiement">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <?php if ($reste > 0): ?>
                                <div style="margin-top:16px;text-align:right;">
                                    <button class="action-btn-pay" onclick="openPayModal(<?= (int)$facSelected['ID_FACTURE'] ?>, '<?= htmlspecialchars($facSelected['NUM_FACTURE']) ?>', <?= (int)$facSelected['MONTANT_FACTURE'] ?>, <?= $sumPaye ?>)">
                                        <i class="fas fa-plus"></i> Enregistrer un paiement
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- LISTE DES FACTURES -->
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <h3><i class="fas fa-list" style="color:var(--primary-green);margin-right:8px;"></i> Toutes les factures</h3>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span class="badge badge-confirm"><?= count($factures) ?></span>
                                <a href="paiements.php" class="action-btn-view" style="padding:6px 12px;">
                                    <i class="fas fa-history"></i> Tous les paiements
                                </a>
                            </div>
                        </div>
                        <div class="dash-card-body">
                            <!-- BARRE DE RECHERCHE -->
                            <div style="padding:16px 20px;">
                                <form method="GET" action="" style="display:flex;gap:8px;">
                                    <div class="dash-search-bar" style="margin:0;flex:1;">
                                        <i class="fas fa-search"></i>
                                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher par n° de facture ou client...">
                                    </div>
                                    <button type="submit" class="btn-dash btn-dash-primary" style="padding:10px 14px;"><i class="fas fa-search"></i> Filtrer</button>
                                    <?php if ($search !== ''): ?>
                                        <a href="factures.php" class="btn-dash btn-dash-outline" style="padding:10px 14px;text-decoration:none;"><i class="fas fa-times"></i> Ràz</a>
                                    <?php endif; ?>
                                </form>
                            </div>

                            <?php if (empty($factures)): ?>
                                <div class="empty-state"><i class="fas fa-file-invoice"></i>
                                    <p>Aucune facture trouvée.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="dash-table">
                                        <thead>
                                            <tr>
                                                <th>N° Facture</th>
                                                <th>Client</th>
                                                <th>Prestation</th>
                                                <th>Date</th>
                                                <th class="amount-col">Montant total</th>
                                                <th class="amount-col">Payé</th>
                                                <th class="amount-col">Reste</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($factures as $f):
                                                $montantTotal = (int)$f['MONTANT_FACTURE'];
                                                $montantPaye  = (int)$f['MONTANT_PAYE'];
                                                $resteAPayer  = (int)$f['RESTE_A_PAYER'];
                                                $pct          = $montantTotal > 0 ? min(100, round($montantPaye / $montantTotal * 100)) : 0;
                                                $sf           = strtoupper(trim($f['STATUS_FACTURE']));
                                            ?>
                                                <tr>
                                                    <td>
                                                        <strong style="color:var(--primary-green);">
                                                            <i class="fas fa-file-invoice"></i> <?= htmlspecialchars($f['NUM_FACTURE']) ?>
                                                        </strong>
                                                    </td>
                                                    <td><strong><?= htmlspecialchars($f['PRENOM_CLIENT'] . ' ' . $f['NOM_CLIENT']) ?></strong></td>
                                                    <td><?= htmlspecialchars($f['LIB_PRESTATION']) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($f['DATE_RESERVATION'])) ?></td>
                                                    <td class="amount-col">
                                                        <strong><?= number_format($montantTotal, 0, ',', ' ') ?> Ar</strong>
                                                    </td>
                                                    <td class="amount-col" style="color:var(--primary-green);">
                                                        <?= number_format($montantPaye, 0, ',', ' ') ?> Ar
                                                        <div class="payment-progress">
                                                            <div class="progress-bar-wrap">
                                                                <div class="progress-bar-fill" style="width:<?= $pct ?>%;"></div>
                                                            </div>
                                                            <div class="progress-label"><?= $pct ?>%</div>
                                                        </div>
                                                    </td>
                                                    <td class="amount-col" style="color:<?= $resteAPayer > 0 ? '#f39c12' : 'var(--primary-green)' ?>; font-weight:600;">
                                                        <?= number_format($resteAPayer, 0, ',', ' ') ?> Ar
                                                    </td>
                                                    <td>
                                                        <?php if ($sf === 'PAYEE' || $sf === 'PAYÉE'): ?>
                                                            <span class="badge badge-confirm"><i class="fas fa-check-circle"></i> PAYÉE</span>
                                                        <?php elseif ($sf === 'PARTIELLEMENT PAYEE' || $sf === 'PARTIELLEMENT PAYÉE'): ?>
                                                            <span class="badge-partial"><i class="fas fa-adjust"></i> PARTIELLE</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-cancel"><i class="fas fa-times-circle"></i> NON PAYÉE</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div style="display:inline-flex;gap:6px;flex-wrap:wrap;">
                                                            <?php if ($resteAPayer > 0): ?>
                                                                <button class="action-btn-pay"
                                                                    onclick="openPayModal(<?= (int)$f['ID_FACTURE'] ?>, '<?= htmlspecialchars($f['NUM_FACTURE']) ?>', <?= $montantTotal ?>, <?= $montantPaye ?>)"
                                                                    title="Enregistrer un paiement">
                                                                    <i class="fas fa-plus"></i> Paiement
                                                                </button>
                                                            <?php endif; ?>
                                                            <a href="factures.php?view_payments=<?= (int)$f['ID_FACTURE'] ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
                                                                class="action-btn-view" title="Voir l'historique">
                                                                <i class="fas fa-history"></i>
                                                            </a>
                                                            <a href="../client/generer_facture_pdf.php?id_facture=<?= (int)$f['ID_FACTURE'] ?>"
                                                                class="btn-dash btn-dash-outline btn-dash-sm" title="Télécharger le PDF">
                                                                <i class="fas fa-file-pdf"></i>
                                                            </a>
                                                            <form method="POST" style="display:inline;margin:0;" id="deleteFactureForm-<?= (int)$f['ID_FACTURE'] ?>">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="id_facture" value="<?= (int)$f['ID_FACTURE'] ?>">
                                                                <button type="button" class="btn-dash btn-dash-danger btn-dash-sm confirm-delete-btn"
                                                                    data-form-id="deleteFactureForm-<?= (int)$f['ID_FACTURE'] ?>"
                                                                    data-message="Supprimer cette facture et tous ses paiements ?">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
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

    <!-- ── MODAL PAIEMENT (thème blanc) ────────────────────────────── -->
    <div class="modal-overlay" id="payModal">
        <div class="modal-box">

            <!-- Bandeau vert en-tête -->
            <div class="modal-header-band">
                <div class="modal-title">
                    <i class="fas fa-coins"></i>
                    Enregistrer un paiement
                </div>
                <button class="modal-close" onclick="closePayModal()" title="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-subtitle" id="modalSubtitle">Facture —</div>

            <!-- Reste à payer mis en avant -->
            <div class="modal-info-row">
                <span class="label"><i class="fas fa-money-bill-wave" style="color:#377d49;"></i>&nbsp; Reste à payer</span>
                <span class="value" id="modalReste">0 Ar</span>
            </div>

            <form method="POST" action="" id="payForm">
                <input type="hidden" name="action" value="add_payment">
                <input type="hidden" name="id_facture" id="modalIdFac" value="">

                <div class="form-group">
                    <label for="montant_paiement">
                        <i class="fas fa-coins" style="color:#377d49;"></i>&nbsp; Montant du versement (Ar)
                    </label>
                    <input type="number" name="montant_paiement" id="montant_paiement"
                        min="1" placeholder="Ex : 500 000" required>
                </div>

                <div class="form-group">
                    <label for="date_paiement">
                        <i class="fas fa-calendar-alt" style="color:#377d49;"></i>&nbsp; Date du versement
                    </label>
                    <input type="date" name="date_paiement" id="date_paiement"
                        value="<?= date('Y-m-d') ?>" required>
                </div>

                <div style="display:flex;gap:10px;margin-top:22px;">
                    <button type="submit" class="modal-btn-submit">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <button type="button" class="modal-btn-cancel" onclick="closePayModal()">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- ── MODAL CONFIRMATION SUPPRESSION (générique) ─────────────────── -->
    <div class="dash-modal" id="confirmDeleteModal">
        <div class="dash-modal-content">
            <button class="dash-modal-close" id="confirmDeleteModalClose">&times;</button>
            <h3>Confirmation</h3>
            <p id="confirmDeleteMessage">Êtes-vous sûr ?</p>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" class="btn-dash btn-dash-danger" id="confirmDeleteYes">Oui, supprimer</button>
                <button type="button" class="btn-dash btn-dash-outline" id="confirmDeleteCancel">Annuler</button>
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

        // ── Modal paiement ──
        function openPayModal(idFac, numFac, total, paye) {
            const reste = total - paye;
            document.getElementById('modalIdFac').value = idFac;
            document.getElementById('modalSubtitle').textContent = 'Facture ' + numFac;
            document.getElementById('modalReste').textContent = new Intl.NumberFormat('fr-FR').format(reste) + ' Ar';
            document.getElementById('montant_paiement').max = reste;
            document.getElementById('montant_paiement').value = '';
            document.getElementById('payModal').classList.add('open');
        }

        function closePayModal() {
            document.getElementById('payModal').classList.remove('open');
        }
        document.getElementById('payModal').addEventListener('click', function(e) {
            if (e.target === this) closePayModal();
        });
        // ── Modal générique de confirmation de suppression ──
        let formToConfirmDelete = null;
        const confirmDeleteModal = document.getElementById('confirmDeleteModal');
        const confirmDeleteMessage = document.getElementById('confirmDeleteMessage');
        const confirmDeleteClose = document.getElementById('confirmDeleteModalClose');
        const confirmDeleteCancel = document.getElementById('confirmDeleteCancel');
        const confirmDeleteYes = document.getElementById('confirmDeleteYes');

        document.querySelectorAll('.confirm-delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                formToConfirmDelete = document.getElementById(btn.dataset.formId);
                confirmDeleteMessage.textContent = btn.dataset.message || 'Êtes-vous sûr ?';
                confirmDeleteModal.classList.add('open');
            });
        });

        function closeConfirmDeleteModal() {
            confirmDeleteModal.classList.remove('open');
            formToConfirmDelete = null;
        }

        confirmDeleteClose.addEventListener('click', closeConfirmDeleteModal);
        confirmDeleteCancel.addEventListener('click', closeConfirmDeleteModal);
        confirmDeleteModal.addEventListener('click', (e) => {
            if (e.target === confirmDeleteModal) closeConfirmDeleteModal();
        });

        confirmDeleteYes.addEventListener('click', () => {
            if (formToConfirmDelete) formToConfirmDelete.submit();
        });
    </script>

    <!-- Toastify pour messages PRG -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <?php if (!empty($prgMessages)): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                <?= prg_render_toasts($prgMessages) ?>
                const url = new URL(window.location);
                url.searchParams.delete('q');
                url.searchParams.delete('id_contrat');
                window.history.replaceState({}, '', url);
            });
        </script>
    <?php endif; ?>

</body>

</html>