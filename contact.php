<?php
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();

$contact = null;
try {
    // Only one active contact record is expected
    $contact = $pdo->query("SELECT * FROM contact LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Messages for PRG
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$contactStatus = $_SESSION['contact_status'] ?? null;
$contactMessage = $_SESSION['contact_message'] ?? null;
unset($_SESSION['contact_status'], $_SESSION['contact_message']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contactez-nous | NY TIA SARY</title>
    <?php include 'composante/csslink.php'; ?>
    <style>
        .contact-hero {
            padding: 120px 0 60px;
            background: linear-gradient(135deg, rgba(44,95,45,0.95) 0%, rgba(20,20,20,0.9) 100%), url('https://images.unsplash.com/photo-1423666639041-f56000c27a9a?auto=format&fit=crop&w=1920&q=80') center/cover;
            color: var(--white);
            text-align: center;
        }
        .contact-hero h1 {
            font-family: var(--font-headings);
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .contact-hero p {
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.9;
        }
        .contact-section {
            padding: 80px 0;
            background: #fff;
        }
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 50px;
            align-items: flex-start;
        }
        @media (max-width: 992px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
        }
        .contact-info-card {
            background: #fdfdfd;
            border-radius: 12px;
            padding: 40px 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
        }
        .contact-info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
        }
        .contact-info-item:last-child {
            margin-bottom: 0;
        }
        .contact-info-icon {
            width: 50px;
            height: 50px;
            background: rgba(55, 125, 73, 0.1);
            color: var(--primary-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 20px;
            flex-shrink: 0;
        }
        .contact-info-content h3 {
            font-family: var(--font-headings);
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: var(--logo-black);
        }
        .contact-info-content p, .contact-info-content a {
            color: var(--text-body);
            font-size: 0.95rem;
            text-decoration: none;
            transition: color 0.3s;
        }
        .contact-info-content a:hover {
            color: var(--primary-green);
        }
        
        .contact-form-card {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        .contact-form-card h2 {
            font-family: var(--font-headings);
            font-size: 2rem;
            margin-bottom: 30px;
            color: var(--logo-black);
        }
        
        .map-section {
            width: 100%;
            height: 400px;
            background: #eee;
            margin-top: 0;
        }
        .map-section iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }
        
        .social-links-contact {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .social-link-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: #f1f1f1;
            color: var(--logo-black);
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        .social-link-icon:hover {
            background: var(--primary-green);
            color: var(--white);
            transform: translateY(-3px);
        }
    </style>
</head>
<body>

<?php include 'composante/header.php'; ?>

<section class="contact-hero">
    <div class="container">
        <h1>Contactez-nous</h1>
        <p>Une question, un projet ou une demande de devis ? N'hésitez pas à nous écrire, notre équipe vous répondra dans les plus brefs délais.</p>
    </div>
</section>

<section class="contact-section">
    <div class="container">
        <div class="contact-grid">
            
            <!-- Informations de contact -->
            <div class="contact-info-card">
                <?php if ($contact): ?>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="contact-info-content">
                            <h3>Adresse</h3>
                            <p><?= htmlspecialchars($contact['ADRESSE_CONTACT']) ?></p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="contact-info-content">
                            <h3>Téléphone</h3>
                            <p><a href="tel:<?= htmlspecialchars($contact['TEL_CONTACT']) ?>"><?= htmlspecialchars($contact['TEL_CONTACT']) ?></a></p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-envelope"></i></div>
                        <div class="contact-info-content">
                            <h3>E-mail</h3>
                            <p><a href="mailto:<?= htmlspecialchars($contact['EMAIL_CONTACT']) ?>"><?= htmlspecialchars($contact['EMAIL_CONTACT']) ?></a></p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-clock"></i></div>
                        <div class="contact-info-content">
                            <h3>Horaires d'ouverture</h3>
                            <p><?= htmlspecialchars($contact['HORAIRE_CONTACT']) ?></p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="fas fa-share-alt"></i></div>
                        <div class="contact-info-content">
                            <h3>Réseaux Sociaux</h3>
                            <div class="social-links-contact">
                                <?php if (!empty($contact['WHATSAPP_LIEN'])): ?>
                                    <a href="<?= htmlspecialchars($contact['WHATSAPP_LIEN']) ?>" target="_blank" class="social-link-icon" title="WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($contact['MESSENGER_LIEN'])): ?>
                                    <a href="<?= htmlspecialchars($contact['MESSENGER_LIEN']) ?>" target="_blank" class="social-link-icon" title="Messenger">
                                        <i class="fab fa-facebook-messenger"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <p>Informations de contact non disponibles pour le moment.</p>
                <?php endif; ?>
            </div>

            <!-- Formulaire de contact -->
            <div class="contact-form-card">
                <h2>Envoyez-nous un message</h2>
                <form action="traitement_contact.php" method="POST" class="devis-form">
                    <div class="devis-form-row">
                        <div class="devis-form-group">
                            <label for="nom">Nom <span class="required">*</span></label>
                            <input type="text" id="nom" name="nom" required placeholder="Votre nom">
                        </div>
                        <div class="devis-form-group">
                            <label for="prenom">Prénom <span class="required">*</span></label>
                            <input type="text" id="prenom" name="prenom" required placeholder="Votre prénom">
                        </div>
                    </div>

                    <div class="devis-form-row">
                        <div class="devis-form-group">
                            <label for="email">E-mail <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required placeholder="Votre adresse e-mail">
                        </div>
                        <div class="devis-form-group">
                            <label for="telephone">Téléphone <span class="required">*</span></label>
                            <input type="tel" id="telephone" name="telephone" required placeholder="Votre numéro de téléphone">
                        </div>
                    </div>

                    <div class="devis-form-group devis-form-full">
                        <label for="objet">Objet <span class="required">*</span></label>
                        <input type="text" id="objet" name="objet" required placeholder="Sujet de votre message">
                    </div>

                    <div class="devis-form-group devis-form-full">
                        <label for="message">Message <span class="required">*</span></label>
                        <textarea id="message" name="message" rows="5" required placeholder="Comment pouvons-nous vous aider ?"></textarea>
                    </div>

                    <button type="submit" class="btn btn-green" style="width: 100%; margin-top: 10px;">Envoyer le message <i class="fas fa-paper-plane" style="margin-left: 8px;"></i></button>
                </form>
            </div>
            
        </div>
    </div>
</section>

<!-- SECTION CARTE GOOGLE MAPS -->
<?php if ($contact && !empty($contact['ADRESSE_CONTACT'])): 
    // Utiliser l'adresse pour générer un lien Google Maps
    $addressQuery = urlencode($contact['ADRESSE_CONTACT'] . ' Madagascar');
?>
<div class="map-section">
    <iframe 
        src="https://maps.google.com/maps?q=<?= $addressQuery ?>&t=&z=13&ie=UTF8&iwloc=&output=embed" 
        frameborder="0" 
        style="border:0;" 
        allowfullscreen="" 
        aria-hidden="false" 
        tabindex="0">
    </iframe>
</div>
<?php endif; ?>

<!-- Boutons Flottants -->
<div class="floating-buttons">
    <button class="btn-float btn-devis" id="btn-open-devis-float" title="Demander un Devis">
        <i class="fas fa-file-invoice-dollar"></i> Demander devis
    </button>
    <a href="login/login.php" class="btn-float btn-reserver" title="Réserver">
        <i class="far fa-calendar-check"></i> Réserver
    </a>
</div>

<?php include 'composante/footer.php'; ?>
<?php include "composante/devis_visiteur.php"?> 

<?php if ($contactStatus && $contactMessage): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        Toastify({
            text: <?= json_encode($contactMessage, JSON_UNESCAPED_UNICODE) ?>,
            duration: 6000,
            gravity: "top",
            position: "right",
            close: true,
            stopOnFocus: true,
            style: {
                background: <?= $contactStatus === 'success'
                    ? "'linear-gradient(135deg, #377d49, #2a5c3a)'"
                    : "'linear-gradient(135deg, #d93d3d, #a82c2c)'" ?>,
                borderRadius: "6px",
                fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
                fontWeight: "600",
                boxShadow: "0 10px 30px rgba(0, 0, 0, 0.15)",
            },
        }).showToast();
    });
</script>
<?php endif; ?>  

</body>
</html>
