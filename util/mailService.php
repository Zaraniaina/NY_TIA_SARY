<?php

require_once __DIR__ . '/../phpMailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpMailer/src/SMTP.php';
require_once __DIR__ . '/../phpMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private $config;
    private $mailer;

    public function __construct()
    {
        $this->config = require_once __DIR__ . '/../config/mail.php';
        $this->mailer = new PHPMailer(true);
        $this->setupMailer();
    }

    /**
     * Configuration de PHPMailer avec les paramètres du fichier config
     */
    private function setupMailer(): void
    {
        // Configuration du serveur SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->config['host'];
        $this->mailer->SMTPAuth = !empty($this->config['username']);

        if ($this->mailer->SMTPAuth) {
            $this->mailer->Username = $this->config['username'];
            $this->mailer->Password = $this->config['password'];
        }

                // Après
        if (!empty($this->config['encryption'])) {
            $this->mailer->SMTPSecure = $this->config['encryption'];
        } else {
            // Pas de chiffrement (ex: Mailpit en local) : évite tout STARTTLS opportuniste
            $this->mailer->SMTPSecure = '';
            $this->mailer->SMTPAutoTLS = false;
        }
        $this->mailer->Port = $this->config['port'];

        // Configuration de l'expéditeur
        $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);

        // Format des pièces jointes
        $this->mailer->CharSet = 'UTF-8';
    }

    /**
     * Envoyer un email
     *
     * @param string|array $to Adresse(s) du destinataire
     * @param string $subject Sujet de l'email
     * @param string $body Contenu de l'email (HTML ou texte)
     * @param bool $isHtml Si true, le corps est traité comme HTML
     * @return bool True si l'envoi a réussi, false sinon
     */
    public function send($to, string $subject, string $body, bool $isHtml = true): bool
    {
        try {
            // Ajouter le(s) destinataire(s)
            if (is_array($to)) {
                foreach ($to as $recipient) {
                    $this->mailer->addAddress($recipient);
                }
            } else {
                $this->mailer->addAddress($to);
            }

            // Configuration du contenu
            if ($isHtml) {
                $this->mailer->isHTML(true);
                $this->mailer->Subject = $subject;
                $this->mailer->Body = $body;
            } else {
                $this->mailer->isHTML(false);
                $this->mailer->Subject = $subject;
                $this->mailer->Body = $body;
            }

            // Envoyer l'email
            $this->mailer->send();

            // Nettoyer les destinataires pour le prochain envoi
            $this->mailer->clearAddresses();

            return true;
        } catch (Exception $e) {
            // Nettoyer les destinataires même en cas d'erreur
            $this->mailer->clearAddresses();

            // Log de l'erreur
            error_log('Erreur d\'envoi d\'email: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Envoyer un email avec pièce(s) jointe(s)
     *
     * @param string|array $to Adresse(s) du destinataire
     * @param string $subject Sujet de l'email
     * @param string $body Contenu de l'email (HTML ou texte)
     * @param array $attachments Chemins des fichiers à attacher
     * @param bool $isHtml Si true, le corps est traité comme HTML
     * @return bool True si l'envoi a réussi, false sinon
     */
    public function sendWithAttachments($to, string $subject, string $body, array $attachments = [], bool $isHtml = true): bool
    {
        try {
            // Ajouter le(s) destinataire(s)
            if (is_array($to)) {
                foreach ($to as $recipient) {
                    $this->mailer->addAddress($recipient);
                }
            } else {
                $this->mailer->addAddress($to);
            }

            // Ajouter les pièces jointes
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $this->mailer->addAttachment($attachment);
                }
            }

            // Configuration du contenu
            if ($isHtml) {
                $this->mailer->isHTML(true);
                $this->mailer->Subject = $subject;
                $this->mailer->Body = $body;
            } else {
                $this->mailer->isHTML(false);
                $this->mailer->Subject = $subject;
                $this->mailer->Body = $body;
            }

            // Envoyer l'email
            $this->mailer->send();

            // Nettoyer
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            return true;
        } catch (Exception $e) {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            error_log('Erreur d\'envoi d\'email avec pièces jointes: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Envoyer un email de notification simple
     *
     * @param string $to Destinataire
     * @param string $template Type de template ('reservation', 'contact', etc.)
     * @param array $data Données à insérer dans le template
     * @return bool
     */
    public function sendNotification(string $to, string $template, array $data = []): bool
    {
        $templates = [
            'reservation' => [
                'subject' => 'Confirmation de réservation',
                'body' => '<p>Votre réservation a été confirmée avec succès.</p>'
            ],
            'contact' => [
                'subject' => 'Nouveau message de contact',
                'body' => '<p>Un nouveau message a été reçu.</p>'
            ]
        ];

        if (!isset($templates[$template])) {
            return false;
        }

        $subject = $templates[$template]['subject'];
        $body = $templates[$template]['body'];

        // Remplacer les placeholders avec les données
        foreach ($data as $key => $value) {
            $body = str_replace('{{' . $key . '}}', $value, $body);
            $subject = str_replace('{{' . $key . '}}', $value, $subject);
        }

        return $this->send($to, $subject, $body, true);
    }

    /**
     * Envoyer une notification de nouvelle réservation à l'administrateur
     *
     * @param string $adminEmail Email de l'administrateur
     * @param array $reservationData Données de la réservation
     *   - client_nom: Nom du client
     *   - client_prenom: Prénom du client
     *   - prestation: Type de prestation
     *   - date_reservation: Date de la réservation
     *   - heure_reservation: Heure de la réservation
     *   - lieu_reservation: Lieu de la réservation
     *   - categories: Tableau des catégories/formules
     *   - total_prix: Prix total
     * @return bool
     */
    public function sendReservationNew(string $adminEmail, array $reservationData): bool
    {
        $clientNom = $reservationData['client_nom'] ?? 'Inconnu';
        $clientPrenom = $reservationData['client_prenom'] ?? '';
        $clientFull = trim($clientPrenom . ' ' . $clientNom);
        
        $prestation = $reservationData['prestation'] ?? 'Non spécifiée';
        $dateResa = $reservationData['date_reservation'] ?? 'Non définie';
        $heureResa = $reservationData['heure_reservation'] ?? 'Non définie';
        $lieuResa = $reservationData['lieu_reservation'] ?? 'Non défini';
        $categories = $reservationData['categories'] ?? [];
        $totalPrix = $reservationData['total_prix'] ?? 0;
        $idResa = $reservationData['id_reservation'] ?? '';

        $categoriesHtml = '';
        if (!empty($categories)) {
            $catList = is_array($categories) ? $categories : [$categories];
            foreach ($catList as $cat) {
                $categoriesHtml .= '<li>' . htmlspecialchars($cat) . '</li>';
            }
            $categoriesHtml = '<ul style="margin: 8px 0; padding-left: 20px;">' . $categoriesHtml . '</ul>';
        }

        $prixFormatted = $totalPrix > 0 ? number_format($totalPrix, 0, ',', ' ') . ' Ar' : 'À définir';

        $body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h2 style="color: #2c5f2d; border-bottom: 2px solid #377d49; padding-bottom: 10px;">Nouvelle Réservation</h2>
            
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Client :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($clientFull) . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Prestation :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($prestation) . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Date :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($dateResa) . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Heure :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($heureResa) . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Lieu :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($lieuResa) . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Catégories :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . $categoriesHtml . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Total :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; color: #2c5f2d; font-weight: bold;">' . $prixFormatted . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">ID Réservation :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($idResa) . '</td>
                </tr>
            </table>
            
            <p style="color: #666; font-size: 12px;">Cette réservation est en attente de confirmation.</p>
        </div>';

        $subject = 'Nouvelle réservation - ' . htmlspecialchars($clientFull);

        return $this->send($adminEmail, $subject, $body, true);
    }

    /**
     * Envoyer une notification de confirmation de réservation au client
     *
     * @param string $clientEmail Email du client
     * @param array $reservationData Données de la réservation
     *   - client_nom: Nom du client
     *   - client_prenom: Prénom du client
     *   - prestation: Type de prestation
     *   - date_reservation: Date de la réservation
     *   - heure_reservation: Heure de la réservation
     *   - lieu_reservation: Lieu de la réservation
     *   - categories: Tableau des catégories/formules
     *   - total_prix: Prix total
     *   - id_reservation: ID de la réservation
     *   - id_contrat: ID du contrat (optionnel)
     * @return bool
     */
    public function sendReservationConfirmed(string $clientEmail, array $reservationData): bool
    {
        $clientNom = $reservationData['client_nom'] ?? 'Inconnu';
        $clientPrenom = $reservationData['client_prenom'] ?? '';
        $clientFull = trim($clientPrenom . ' ' . $clientNom);
        
        $prestation = $reservationData['prestation'] ?? 'Non spécifiée';
        $dateResa = $reservationData['date_reservation'] ?? 'Non définie';
        $heureResa = $reservationData['heure_reservation'] ?? 'Non définie';
        $lieuResa = $reservationData['lieu_reservation'] ?? 'Non défini';
        $categories = $reservationData['categories'] ?? [];
        $totalPrix = $reservationData['total_prix'] ?? 0;
        $idResa = $reservationData['id_reservation'] ?? '';
        $idContrat = $reservationData['id_contrat'] ?? '';

        $categoriesHtml = '';
        if (!empty($categories)) {
            $catList = is_array($categories) ? $categories : [$categories];
            foreach ($catList as $cat) {
                $categoriesHtml .= '<li>' . htmlspecialchars($cat) . '</li>';
            }
            $categoriesHtml = '<ul style="margin: 8px 0; padding-left: 20px;">' . $categoriesHtml . '</ul>';
        }

        $prixFormatted = $totalPrix > 0 ? number_format($totalPrix, 0, ',', ' ') . ' Ar' : 'À définir';

        $contratLink = '';
        if ($idContrat) {
            $contratLink = '<p style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #377d49;">
                <strong style="color: #2c5f2d;">Votre contrat est disponible :</strong><br>
                <a href="https://nytiasary.com/espace/admin/contrats.php" style="color: #377d49; text-decoration: none;">Voir/Consulter le contrat</a>
            </p>';
        }

        $body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h2 style="color: #2c5f2d; border-bottom: 2px solid #377d49; padding-bottom: 10px;">Confirmation de Réservation</h2>
            
            <p>Bonjour ' . htmlspecialchars($clientFull) . ',</p>
            
            <p>Nous vous confirmons la réception de votre réservation. Voici les détails :</p>
            
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Prestation :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($prestation) . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Date :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($dateResa) . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Heure :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($heureResa) . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Lieu :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($lieuResa) . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Catégories :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . $categoriesHtml . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Total :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; color: #2c5f2d; font-weight: bold;">' . $prixFormatted . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">ID Réservation :</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($idResa) . '</td>
                </tr>
            </table>
            
            ' . $contratLink . '
            
            <p style="margin-top: 20px;">Merci de votre confiance.<br>
            <strong>Ny Tia Sary - Studio de Photographie</strong></p>
        </div>';

        $subject = 'Confirmation de réservation n°' . htmlspecialchars($idResa);

        return $this->send($clientEmail, $subject, $body, true);
    }
}