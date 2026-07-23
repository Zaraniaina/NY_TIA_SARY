<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getPDO();
}

$contact_footer = null;
try {
    $contact_footer = $pdo->query("SELECT * FROM contact LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$adresse = $contact_footer['ADRESSE_CONTACT'] ?? 'Toamasina, Madagascar';
$telephone = $contact_footer['TEL_CONTACT'] ?? '+261 34 xx xxx xx';
$email = $contact_footer['EMAIL_CONTACT'] ?? 'contact@nytiasary.mg';
$horaire = $contact_footer['HORAIRE_CONTACT'] ?? 'Lun - Sam: 8h00 - 18h00';
$whatsapp = $contact_footer['WHATSAPP_LIEN'] ?? '#';
$messenger = $contact_footer['MESSENGER_LIEN'] ?? '#';
?>
<!-- FOOTER / FARA-PEJY -->
    <footer id="contact">
        <div class="container footer-grid">
            <div class="footer-logo-box">
                <a href="#" class="logo-box">
                    <img src="logo.png" alt="NY TIA SARY Logo" class="logo-img">
                </a>
                <p>Créateur de contenus visuels d'exception pour les professionnels et les particuliers à Madagascar.
                </p>
                <div class="footer-socials">
                    <a href="<?= htmlspecialchars($whatsapp) ?>" class="social-link" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <a href="<?= htmlspecialchars($messenger) ?>" class="social-link" target="_blank" title="Messenger"><i class="fab fa-facebook-messenger"></i></a>
                </div>
            </div>

            <div class="footer-col">
                <h4>Liens Utiles</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="apropos.php">À Propos</a></li>
                    <li><a href="service.php">Nos Services</a></li>
                    <li><a href="portfolio.php">Notre Portfolio</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Contactez-Nous</h4>
                <ul class="footer-contact">
                    <li><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($adresse) ?></li>
                    <li><i class="fas fa-phone-alt"></i> <?= htmlspecialchars($telephone) ?></li>
                    <li><i class="fas fa-envelope"></i> <?= htmlspecialchars($email) ?></li>
                    <li><i class="fas fa-clock"></i> <?= htmlspecialchars($horaire) ?></li>
                </ul>
            </div>
        </div>

        <div class="container footer-bottom">
            <p>&copy; 2026 <strong>NY TIA SARY</strong>. Tous droits réservés. <br>Hatsarao sy ho Tiava. Professionnel
                sy Mendrika.</p>
        </div>

    </footer>
     <!-- LIGHTBOX COMPONENT (UX Feature) -->
    <div class="lightbox" id="lightbox">
        <div class="lightbox-content">
            <span class="lightbox-close" id="lightbox-close">&times;</span>
            <img id="lightbox-img" class="lightbox-img" src="" alt="Portfolio View">
        </div>
    </div>
    
    <!-- ton script.js existant vient juste après -->
    <script src="https://cdn.jsdelivr.net/npm/scrollreveal@4.0.9/dist/scrollreveal.min.js"></script>
    <!-- JavaScript logic for site-wide interactivity (menus, scrolls, controls) -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="script.js"></script>
    