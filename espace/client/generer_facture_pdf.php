<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['admin_id']) && empty($_SESSION['client_id'])) {
    header('Location: ' . getLoginUrl());
    exit();
}

require_once __DIR__ . '/../../config/database.php';
$pdo = getPDO();

require_once __DIR__ . '/../../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$idFacture = (int) ($_GET['id_facture'] ?? 0);

if (!$idFacture) {
    die('Facture non spécifiée.');
}

// ── Récupérer les informations de la facture ──────────────────
$sql = 'SELECT f.*, ct.DATE_CONTRAT, r.ID_RESERVATION, r.DATE_RESERVATION, r.LIEU_RESERVATION, r.HEURE_RESERVATION,
            p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT, c.TEL_CLIENT, a.EMAIL_AUTH
     FROM FACTURE f
     JOIN CONTRAT ct ON f.ID_CONTRAT = ct.ID_CONTRAT
     JOIN RESERVATION r ON ct.ID_RESERVATION = r.ID_RESERVATION
     JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
     JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
     JOIN authentification a ON c.ID_AUTH = a.ID_AUTH
     WHERE f.ID_FACTURE = ?';

$params = [$idFacture];
if (!empty($_SESSION['client_id'])) {
    $sql .= ' AND r.ID_CLIENT = ?';
    $params[] = (int)$_SESSION['client_id'];
}

$stmtFacture = $pdo->prepare($sql);
$stmtFacture->execute($params);
$facture = $stmtFacture->fetch();

if (!$facture) {
    die('Facture introuvable ou non autorisée.');
}

// ── Récupérer les lignes de catégories (détails des prix) ─────
$stmtDetails = $pdo->prepare(
    'SELECT cat.LIB_CATEGORIE, rc.PRIX
     FROM RESERVATION_CATEGORIE rc
     JOIN CATEGORIE cat ON cat.ID_CATEGORIE = rc.ID_CATEGORIE
     WHERE rc.ID_RESERVATION = ?'
);
$stmtDetails->execute([$facture['ID_RESERVATION']]);
$details = $stmtDetails->fetchAll();

// ── Récupérer les paiements de cette facture ──────────────────
$stmtPay = $pdo->prepare(
    'SELECT DATE_PAIEMENT, MONTANT_PAIEMENT
     FROM PAIEMENT
     WHERE ID_FACTURE = ?
     ORDER BY DATE_PAIEMENT ASC'
);
$stmtPay->execute([$idFacture]);
$paiements = $stmtPay->fetchAll();

$montantTotal = (int)$facture['MONTANT_FACTURE'];
$totalPaye    = (int)array_sum(array_column($paiements, 'MONTANT_PAIEMENT'));
$resteAPayer  = $montantTotal - $totalPaye;

// ── Déterminer le statut réel à 3 états ───────────────────────
if ($totalPaye >= $montantTotal && $montantTotal > 0) {
    $statutLabel = 'PAYÉE';
    $badgeColor  = '#377d49';
    $badgeBg     = '#e8f5ed';
    $badgeBorder = '#b8ddc4';
} elseif ($totalPaye > 0) {
    $statutLabel = 'PARTIELLEMENT PAYÉE';
    $badgeColor  = '#b45309';
    $badgeBg     = '#fff7ed';
    $badgeBorder = '#fbbf24';
} else {
    $statutLabel = 'NON PAYÉE';
    $badgeColor  = '#c0392b';
    $badgeBg     = '#fef2f2';
    $badgeBorder = '#fca5a5';
}

$pct = $montantTotal > 0 ? min(100, round($totalPaye / $montantTotal * 100)) : 0;
$pctFill = $pct . '%';

$contact_info = null;
try {
    $contact_info = $pdo->query("SELECT * FROM contact LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$adresse = $contact_info['ADRESSE_CONTACT'] ?? 'Toamasina, Madagascar';
$telephone = $contact_info['TEL_CONTACT'] ?? '+261 34 xx xxx xx';
$email = $contact_info['EMAIL_CONTACT'] ?? 'contact@nytiasary.mg';

// ── Génération du HTML pour le PDF ────────────────────────────
$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture ' . htmlspecialchars($facture['NUM_FACTURE']) . '</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            color: #2d2d2d; margin: 0; padding: 24px 28px;
            font-size: 13px; line-height: 1.55;
        }

        /* ── En-tête ── */
        .header {
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 3px solid #377d49;
        }
        .logo { font-size: 26px; font-weight: bold; color: #377d49; text-transform: uppercase; letter-spacing: 1px; }
        .logo-sub { font-size: 11px; color: #777; margin-top: -4px; }
        .invoice-title { text-align: right; font-size: 22px; color: #1a1a1a; font-weight: bold; margin-top: -44px; }

        /* ── Infos société / client ── */
        .two-col { width: 100%; margin-bottom: 24px; }
        .two-col td { vertical-align: top; padding: 0; width: 50%; }
        .company-block { font-size: 12.5px; color: #444; line-height: 1.7; }
        .company-block strong { color: #1a1a1a; display: block; margin-bottom: 4px; font-size: 13px; }
        .client-block { font-size: 12.5px; color: #444; text-align: right; line-height: 1.7; }
        .client-block strong { color: #1a1a1a; display: block; margin-bottom: 4px; font-size: 13px; }

        /* ── Bandeau infos facture ── */
        .invoice-info {
            background-color: #f7f9f7; border: 1px solid #d8ead8;
            padding: 14px 16px; margin-bottom: 26px; border-radius: 6px;
        }
        .invoice-info table { width: 100%; }
        .invoice-info td { padding: 5px 4px; font-size: 12.5px; }
        .badge-status {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: bold; letter-spacing: .3px;
            background-color: ' . $badgeBg . ';
            color: ' . $badgeColor . ';
            border: 1px solid ' . $badgeBorder . ';
        }

        /* ── Tableau des formules ── */
        .table-items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-items th {
            background-color: #377d49; color: #fff;
            padding: 10px 12px; text-align: left; font-size: 12px; font-weight: bold;
        }
        .table-items td { padding: 10px 12px; border-bottom: 1px solid #ebebeb; font-size: 12.5px; }
        .table-items tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }

        /* ── Section totaux ── */
        .totals-table { width: 44%; float: right; border-collapse: collapse; margin-bottom: 10px; }
        .totals-table td { padding: 8px 12px; font-size: 13px; }
        .totals-table .row-sep td { border-top: 1px solid #e0e0e0; }
        .totals-table .row-total td {
            background-color: #377d49; color: #fff; font-weight: bold; font-size: 14px;
        }
        .totals-table .row-paye td { background-color: #f0faf4; color: #377d49; font-weight: 600; }
        .totals-table .row-reste td {
            background-color: ' . ($resteAPayer > 0 ? '#fff8f0' : '#f0faf4') . ';
            color: ' . ($resteAPayer > 0 ? '#b45309' : '#377d49') . ';
            font-weight: 700;
        }
        .clear { clear: both; }

        /* ── Section historique paiements ── */
        .pay-section {
            margin-top: 22px; padding-top: 18px; border-top: 1px solid #ddd;
        }
        .pay-section-title {
            font-size: 13px; font-weight: bold; color: #377d49;
            margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
        }
        .pay-table { width: 100%; border-collapse: collapse; }
        .pay-table th {
            background: #f0faf4; color: #2d6b3a; font-size: 11.5px;
            padding: 7px 12px; text-align: left; border-bottom: 1.5px solid #b8ddc4;
        }
        .pay-table td { padding: 8px 12px; font-size: 12.5px; border-bottom: 1px solid #f0f0f0; }
        .pay-table tr:last-child td { border-bottom: none; }

        /* ── Barre de progression ── */
        .progress-wrap {
            background: #e8e8e8; border-radius: 4px; height: 7px;
            margin: 6px 0 2px; overflow: hidden; width: 100%;
        }
        .progress-fill {
            background: linear-gradient(90deg, #377d49, #4ea865);
            height: 100%; border-radius: 4px; width: ' . $pctFill . ';
        }
        .progress-pct { font-size: 10.5px; color: #666; text-align: right; }

        /* ── Pied de page ── */
        .footer {
            margin-top: 36px; text-align: center; font-size: 11px;
            color: #999; border-top: 1px solid #e0e0e0; padding-top: 14px;
        }
        .no-pay-msg { color: #888; font-style: italic; font-size: 12px; padding: 8px 0; }
    </style>
</head>
<body>

    <!-- EN-TÊTE -->
    <div class="header">
        <div class="logo">NY TIA SARY</div>
        <div class="logo-sub">Studio Photo &amp; Vidéo Professionnel</div>
        <div class="invoice-title">FACTURE</div>
    </div>

    <!-- SOCIÉTÉ & CLIENT -->
    <table class="two-col">
        <tr>
            <td>
                <div class="company-block">
                    <strong>NY TIA SARY Production</strong>
                     ' . htmlspecialchars($contact_info['ADRESSE_CONTACT'] ?? 'Mangarano, Toamasina, Madagascar') . '<br>
                     Tél : ' . htmlspecialchars($contact_info['TEL_CONTACT'] ?? '+261 34 12 345 67') . '<br>
                     Email : ' . htmlspecialchars($contact_info['EMAIL_CONTACT'] ?? 'contact@nytiasary.mg') . '
                </div>
            </td>
            <td>
                <div class="client-block">
                    <strong>Facturé à :</strong>
                    ' . htmlspecialchars($facture['PRENOM_CLIENT'] . ' ' . $facture['NOM_CLIENT']) . '<br>
                    Tél : ' . htmlspecialchars($facture['TEL_CLIENT']) . '<br>
                    Email : ' . htmlspecialchars($facture['EMAIL_AUTH']) . '
                </div>
            </td>
        </tr>
    </table>

    <!-- INFOS FACTURE -->
    <div class="invoice-info">
        <table>
            <tr>
                <td><strong>N° Facture :</strong> ' . htmlspecialchars($facture['NUM_FACTURE']) . '</td>
                <td class="text-right"><strong>Date de facturation :</strong> ' . date('d/m/Y', strtotime($facture['DATE_FACTURE'])) . '</td>
            </tr>
            <tr>
                <td><strong>Prestation :</strong> ' . htmlspecialchars($facture['LIB_PRESTATION']) . '</td>
                <td class="text-right"><strong>Statut :</strong> <span class="badge-status">' . $statutLabel . '</span></td>
            </tr>
            <tr>
                <td><strong>Date prestation :</strong> ' . date('d/m/Y', strtotime($facture['DATE_RESERVATION'])) . ' à ' . substr($facture['HEURE_RESERVATION'], 0, 5) . '</td>
                <td class="text-right"><strong>Lieu :</strong> ' . htmlspecialchars($facture['LIEU_RESERVATION']) . '</td>
            </tr>
        </table>
    </div>

    <!-- TABLEAU DES FORMULES -->
    <table class="table-items">
        <thead>
            <tr>
                <th>Désignation / Formule</th>
                <th class="text-right" style="width:150px;">Prix unitaire</th>
            </tr>
        </thead>
        <tbody>';

foreach ($details as $row) {
    $html .= '
            <tr>
                <td>' . htmlspecialchars($row['LIB_CATEGORIE']) . '</td>
                <td class="text-right">' . number_format((int)$row['PRIX'], 0, ',', ' ') . ' Ar</td>
            </tr>';
}

if (empty($details)) {
    $html .= '
            <tr>
                <td>Formule générale — ' . htmlspecialchars($facture['LIB_PRESTATION']) . '</td>
                <td class="text-right">' . number_format($montantTotal, 0, ',', ' ') . ' Ar</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>

    <!-- BLOC TOTAUX -->
    <table class="totals-table">
        <tr class="row-total">
            <td>Montant total</td>
            <td class="text-right">' . number_format($montantTotal, 0, ',', ' ') . ' Ar</td>
        </tr>
        <tr class="row-sep row-paye">
            <td>Total versé</td>
            <td class="text-right">' . number_format($totalPaye, 0, ',', ' ') . ' Ar</td>
        </tr>
        <tr class="row-reste">
            <td>Reste à payer</td>
            <td class="text-right">' . number_format($resteAPayer, 0, ',', ' ') . ' Ar</td>
        </tr>
    </table>
    <div class="clear"></div>';

$html .= '
    </div>

    <!-- PIED DE PAGE -->
    <div class="footer">
        Merci pour votre confiance et pour votre collaboration avec NY TIA SARY.<br>
        <em>Ce document est une facture officielle certifiée conforme.</em>
    </div>

</body>
</html>';

// ── Initialiser Dompdf ─────────────────────────────────────────
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream('Facture_' . $facture['NUM_FACTURE'] . '.pdf', [
    'Attachment' => true
]);
exit();
