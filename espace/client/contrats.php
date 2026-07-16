<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();

require_once __DIR__ . '/composante/tolbarDto.php';
$titre = "Mes Contrats";

$success = '';
$error = '';

// ── Traitement des décisions du client ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $idContrat = (int) ($_POST['id_contrat'] ?? 0);
    $action = $_POST['action'];

    if ($idContrat > 0) {
        // Vérifier que le contrat appartient bien à une réservation de ce client
        $stmtChk = $pdo->prepare(
            'SELECT ct.*, r.ID_RESERVATION, r.STATUS_RESERVATION, 
                    (SELECT SUM(rc.PRIX) FROM RESERVATION_CATEGORIE rc WHERE rc.ID_RESERVATION = r.ID_RESERVATION) AS TOTAL_PRIX
             FROM CONTRAT ct
             JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
             WHERE ct.ID_CONTRAT = ? AND r.ID_CLIENT = ?'
        );
        $stmtChk->execute([$idContrat, $clientId]);
        $contratInfo = $stmtChk->fetch();

        if ($contratInfo) {
            if ($contratInfo['STATUS_CONTRAT'] !== 'EN ATTENTE') {
                $error = 'Ce contrat a déjà été traité.';
            } elseif ($action === 'accept') {
                try {
                    $pdo->beginTransaction();

                    // 1. Mettre à jour le statut du contrat
                    $stmtUp = $pdo->prepare("UPDATE CONTRAT SET STATUS_CONTRAT = 'CONFIRME' WHERE ID_CONTRAT = ?");
                    $stmtUp->execute([$idContrat]);

                    // 2. Générer le numéro de facture FAC-YYYY-XXXX
                    $annee  = date('Y');
                    $cntStmt = $pdo->query("SELECT COUNT(*) FROM FACTURE WHERE YEAR(DATE_FACTURE) = $annee");
                    $cnt    = (int)$cntStmt->fetchColumn() + 1;
                    $numFac = 'FAC-' . $annee . '-' . str_pad((string)$cnt, 4, '0', STR_PAD_LEFT);

                    // 3. Récupérer le montant de la facture
                    $montant = (int)($contratInfo['TOTAL_PRIX'] ?? 0);

                    // 4. Insérer la facture
                    $stmtFact = $pdo->prepare(
                        'INSERT INTO FACTURE (ID_CONTRAT, NUM_FACTURE, STATUS_FACTURE, MONTANT_FACTURE, DATE_FACTURE) 
                         VALUES (?, ?, ?, ?, CURDATE())'
                    );
                    $stmtFact->execute([$idContrat, $numFac, 'NON PAYEE', $montant]);

                    $pdo->commit();
                    $success = 'Contrat accepté avec succès ! Votre facture a été générée.';
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    error_log('[contrats_client.php] ' . $e->getMessage());
                    $error = 'Une erreur est survenue lors de la validation du contrat.';
                }
            } elseif ($action === 'reject') {
                try {
                    $pdo->beginTransaction();

                    // 1. Mettre à jour le statut du contrat
                    $stmtUp = $pdo->prepare("UPDATE CONTRAT SET STATUS_CONTRAT = 'REFUSE' WHERE ID_CONTRAT = ?");
                    $stmtUp->execute([$idContrat]);

                    // 2. Annuler la réservation liée
                    $stmtResa = $pdo->prepare("UPDATE RESERVATION SET STATUS_RESERVATION = 'ANNULEE' WHERE ID_RESERVATION = ?");
                    $stmtResa->execute([$contratInfo['ID_RESERVATION']]);

                    $pdo->commit();
                    $success = 'Contrat refusé. La réservation a été annulée.';
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    error_log('[contrats_client.php] ' . $e->getMessage());
                    $error = 'Une erreur est survenue lors du refus du contrat.';
                }
            }
        } else {
            $error = 'Contrat introuvable ou non autorisé.';
        }
    }
}

// ── Liste des contrats du client ──────────────────────────────
$stmtContrats = $pdo->prepare(
    'SELECT ct.*, r.DATE_RESERVATION, r.LIEU_RESERVATION, r.STATUS_RESERVATION,
            p.LIB_PRESTATION,
            SUM(rc.PRIX) AS TOTAL_PRIX, 
            GROUP_CONCAT(cat.LIB_CATEGORIE SEPARATOR \'<br>\') AS LIBS_CATEGORIES,
            f.ID_FACTURE, f.NUM_FACTURE
     FROM CONTRAT ct
     JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     LEFT JOIN RESERVATION_CATEGORIE rc ON rc.ID_RESERVATION = r.ID_RESERVATION
     LEFT JOIN CATEGORIE cat ON cat.ID_CATEGORIE = rc.ID_CATEGORIE
     LEFT JOIN FACTURE f ON f.ID_CONTRAT = ct.ID_CONTRAT
     WHERE r.ID_CLIENT = ?
     GROUP BY ct.ID_CONTRAT
     ORDER BY ct.DATE_CONTRAT DESC'
);
$stmtContrats->execute([$clientId]);
$contrats = $stmtContrats->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Contrats | NY TIA SARY</title>
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
                <span>Mes Contrats</span>
            </nav>

            <?php if ($success): ?><div class="dash-alert dash-alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="dash-alert dash-alert-error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-file-signature" style="color:var(--primary-green);margin-right:8px;"></i> Mes Contrats</h3>
                    <span class="badge badge-confirm"><?= count($contrats) ?> contrat(s)</span>
                </div>
                <div class="dash-card-body">
                    <?php if (empty($contrats)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-signature"></i>
                            <p>Aucun contrat disponible pour vos réservations actuellement.</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Contrat</th>
                                    <th>Prestation</th>
                                    <th>Date Prestation</th>
                                    <th>Date Contrat</th>
                                    <th>Tarif Total</th>
                                    <th>Statut Contrat</th>
                                    <th>Facture</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($contrats as $ct): ?>
                                <?php
                                $status = trim($ct['STATUS_CONTRAT'] ?: 'EN ATTENTE');
                                $bc = match(strtoupper($status)) {
                                    'CONFIRME', 'ACCEPTE' => 'badge-confirm',
                                    'REFUSE'  => 'badge-cancel',
                                    default   => 'badge-waiting',
                                };
                                ?>
                                <tr>
                                    <td>#<?= (int)$ct['ID_CONTRAT'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($ct['LIB_PRESTATION']) ?></strong><br>
                                        <span style="font-size:0.8rem;color:#aaa;"><?= $ct['LIBS_CATEGORIES'] ? $ct['LIBS_CATEGORIES'] : 'Formule non spécifiée' ?></span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($ct['DATE_RESERVATION'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($ct['DATE_CONTRAT'])) ?></td>
                                    <td>
                                        <strong style="color:var(--primary-green);"><?= number_format((int)$ct['TOTAL_PRIX'], 0, ',', ' ') ?> Ar</strong>
                                    </td>
                                    <td>
                                        <span class="badge <?= $bc ?>"><?= htmlspecialchars($status) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($ct['ID_FACTURE']): ?>
                                            <a href="factures.php" class="badge badge-confirm" style="text-decoration:none;">
                                                <i class="fas fa-file-invoice"></i> <?= htmlspecialchars($ct['NUM_FACTURE']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#777;font-size:0.85rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($status === 'EN ATTENTE'): ?>
                                            <div style="display:flex;gap:8px;">
                                                <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr d\'accepter ce contrat ?');">
                                                    <input type="hidden" name="id_contrat" value="<?= (int)$ct['ID_CONTRAT'] ?>">
                                                    <input type="hidden" name="action" value="accept">
                                                    <button type="submit" class="btn-dash btn-dash-primary btn-dash-sm">
                                                        <i class="fas fa-check"></i> Accepter
                                                    </button>
                                                </form>
                                                <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir refuser ce contrat ? Cela annulera votre réservation.');">
                                                    <input type="hidden" name="id_contrat" value="<?= (int)$ct['ID_CONTRAT'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn-dash btn-dash-danger btn-dash-sm">
                                                        <i class="fas fa-times"></i> Refuser
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span style="color:#555;font-size:0.85rem;"><i class="fas fa-lock"></i> Traité</span>
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

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });
</script>
</body>
</html>
