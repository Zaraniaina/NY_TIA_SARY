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

// Récupérer les informations de la facture
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

// Récupérer les lignes de catégories (détails des prix)
$stmtDetails = $pdo->prepare(
    'SELECT cat.LIB_CATEGORIE, rc.PRIX
     FROM RESERVATION_CATEGORIE rc
     JOIN CATEGORIE cat ON cat.ID_CATEGORIE = rc.ID_CATEGORIE
     WHERE rc.ID_RESERVATION = ?'
);
$stmtDetails->execute([$facture['ID_RESERVATION']]);
$details = $stmtDetails->fetchAll();

// Génération du contenu HTML pour le PDF
$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture ' . htmlspecialchars($facture['NUM_FACTURE']) . '</title>
    <style>
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .header {
            margin-bottom: 40px;
            border-bottom: 2px solid #377d49;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #377d49;
            text-transform: uppercase;
        }
        .logo-sub {
            font-size: 12px;
            color: #666;
            margin-top: -5px;
        }
        .invoice-title {
            text-align: right;
            font-size: 24px;
            color: #333;
            font-weight: bold;
            margin-top: -45px;
        }
        .company-details, .client-details {
            width: 50%;
            float: left;
            margin-bottom: 30px;
        }
        .client-details {
            text-align: right;
            float: right;
        }
        .clear {
            clear: both;
        }
        .invoice-info {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        .invoice-info table {
            width: 100%;
        }
        .invoice-info td {
            padding: 5px 0;
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 35px;
        }
        .table-items th {
            background-color: #377d49;
            color: #fff;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .table-items td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            float: right;
            width: 40%;
            margin-top: 10px;
        }
        .total-table {
            width: 100%;
            border-collapse: collapse;
        }
        .total-table td {
            padding: 10px;
            font-size: 16px;
        }
        .total-table .grand-total {
            background-color: #377d49;
            color: #fff;
            font-weight: bold;
        }
        .footer {
            margin-top: 120px;
            text-align: center;
            font-size: 11px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .badge {
            background-color: #377d49;
            color: #fff;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">NY TIA SARY</div>
        <div class="logo-sub">Studio Photo & Vidéo Professionnel</div>
        <div class="invoice-title">FACTURE</div>
    </div>

    <div>
        <div class="company-details">
            <strong>NY TIA SARY Production</strong><br>
            Mangarano, Toamasina<br>
            Madagascar<br>
            Tél: +261 34 12 345 67<br>
            Email: contact@nytiasary.mg
        </div>
        <div class="client-details">
            <strong>Facturé à :</strong><br>
            ' . htmlspecialchars($facture['PRENOM_CLIENT'] . ' ' . $facture['NOM_CLIENT']) . '<br>
            Tél: ' . htmlspecialchars($facture['TEL_CLIENT']) . '<br>
            Email: ' . htmlspecialchars($facture['EMAIL_AUTH']) . '
        </div>
        <div class="clear"></div>
    </div>

    ';
    $sf = strtoupper(trim($facture['STATUS_FACTURE']));
    $statutLabel = ($sf === 'PAYEE' || $sf === 'PAYÉE' || $sf === 'REGLÉE') ? 'PAYÉE' : 'NON PAYÉE';
    $badgeStyle = ($sf === 'PAYEE' || $sf === 'PAYÉE' || $sf === 'REGLÉE') ? 'background-color: #377d49;' : 'background-color: #d93d3d;';
    $html .= '
    <div class="invoice-info">
        <table>
            <tr>
                <td><strong>N° de Facture :</strong> ' . htmlspecialchars($facture['NUM_FACTURE']) . '</td>
                <td class="text-right"><strong>Date de Facturation :</strong> ' . date('d/m/Y', strtotime($facture['DATE_FACTURE'])) . '</td>
            </tr>
            <tr>
                <td><strong>Prestation :</strong> ' . htmlspecialchars($facture['LIB_PRESTATION']) . '</td>
                <td class="text-right"><strong>Statut :</strong> <span class="badge" style="' . $badgeStyle . '">' . $statutLabel . '</span></td>
            </tr>
            <tr>
                <td><strong>Date Prestation :</strong> ' . date('d/m/Y', strtotime($facture['DATE_RESERVATION'])) . ' à ' . substr($facture['HEURE_RESERVATION'], 0, 5) . '</td>
                <td class="text-right"><strong>Lieu :</strong> ' . htmlspecialchars($facture['LIEU_RESERVATION']) . '</td>
            </tr>
        </table>
    </div>

    <table class="table-items">
        <thead>
            <tr>
                <th>Désignation / Formule</th>
                <th class="text-right" style="width: 150px;">Prix Unitaire</th>
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
                <td>Formule générale - ' . htmlspecialchars($facture['LIB_PRESTATION']) . '</td>
                <td class="text-right">' . number_format((int)$facture['MONTANT_FACTURE'], 0, ',', ' ') . ' Ar</td>
            </tr>';
        }

$html .= '
        </tbody>
    </table>

    <div class="total-section">
        <table class="total-table">
            <tr class="grand-total">
                <td>Total Net à payer</td>
                <td class="text-right">' . number_format((int)$facture['MONTANT_FACTURE'], 0, ',', ' ') . ' Ar</td>
            </tr>
        </table>
    </div>
    <div class="clear"></div>

    <div class="footer">
        Merci pour votre confiance et pour votre collaboration avec NY TIA SARY.<br>
        <em>Ce document est une facture officielle certifiée conforme.</em>
    </div>

</body>
</html>
';

// Initialiser Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// (Optionnel) Configurer le format du papier
$dompdf->setPaper('A4', 'portrait');

// Rendre le HTML en PDF
$dompdf->render();

// Envoyer le PDF généré au navigateur
$dompdf->stream('Facture_' . $facture['NUM_FACTURE'] . '.pdf', [
    'Attachment' => true // true = force le téléchargement, false = ouvre dans le navigateur
]);
exit();
