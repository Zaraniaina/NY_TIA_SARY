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
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <div class="footer-col">
                <h4>Liens Utiles</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="apropos.php">À Propos</a></li>
                    <li><a href="service.php">Nos Services</a></li>
                    <li><a href="portfolio.php">Notre Portfolio</a></li>
                    <li><a href="#">Réserver</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Contactez-Nous</h4>
                <ul class="footer-contact">
                    <li><i class="fas fa-map-marker-alt"></i> Toamasina, Madagascar</li>
                    <li><i class="fas fa-phone-alt"></i> +261 34 xx xxx xx</li>
                    <li><i class="fas fa-envelope"></i> contact@nytiasary.mg</li>
                    <li><i class="fas fa-clock"></i> Lun - Sam: 8h00 - 18h00</li>
                </ul>
            </div>
        </div>

        <div class="container footer-bottom">
            <p>&copy; 2026 <strong>NY TIA SARY</strong>. Tous droits réservés. <br>Hatsarao sy ho Tiava. Professionnel
                sy Mendrika.</p>
        </div>

    </footer>

    <?php
    if (!isset($pdo)) {
        require_once __DIR__ . '/../config/database.php';
        $pdo = getPDO();
    }

    try {
        $prestationsList = $pdo->query(
            "SELECT ID_PRESTATION, LIB_PRESTATION FROM prestations ORDER BY LIB_PRESTATION ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $prestationsList = [];
    }
    ?>

    <!-- DEMANDE DE DEVIS MODAL -->
    <div class="devis-modal" id="devis-modal">
        <div class="devis-modal-overlay" id="devis-modal-overlay"></div>
        <div class="devis-modal-content">
            <div class="devis-modal-header">
                <h3>Demande de Devis</h3>
                <span class="devis-modal-close" id="devis-modal-close">&times;</span>
            </div>

            <form id="devis-form" class="devis-form" action="traitement_devis.php" method="POST" enctype="multipart/form-data">
                <div class="devis-form-row">
                    <div class="devis-form-group">
                        <label for="devis-nom">Nom <span class="required">*</span></label>
                        <input type="text" id="devis-nom" name="nom" required placeholder="Ex: RAKOTONDRABE">
                    </div>
                    <div class="devis-form-group">
                        <label for="devis-prenom">Prénom <span class="required">*</span></label>
                        <input type="text" id="devis-prenom" name="prenom" required placeholder="Ex: Ranja">
                    </div>
                </div>

                <div class="devis-form-row">
                    <div class="devis-form-group">
                        <label for="devis-email">Email <span class="required">*</span></label>
                        <input type="email" id="devis-email" name="email" required placeholder="Ex: ranja@gmail.com">
                    </div>
                    <div class="devis-form-group">
                        <label for="devis-telephone">Téléphone <span class="required">*</span></label>
                        <input type="tel" id="devis-telephone" name="telephone" required placeholder="Ex: +261 34 12 345 67">
                    </div>
                </div>
                <div class="devis-form-group devis-form-full">
                    <label for="devis-prestation">Prestation souhaitée <span class="required">*</span></label>
                    <select id="devis-prestation" name="id_prestation" required>
                        <option value="" disabled selected>Sélectionnez une prestation</option>
                        <?php foreach ($prestationsList as $prestation): ?>
                            <option value="<?= htmlspecialchars($prestation['ID_PRESTATION']) ?>">
                                <?= htmlspecialchars($prestation['LIB_PRESTATION']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <fieldset class="devis-categories-fieldset" id="devis-categories-fieldset">
                    <legend>Categories</legend>
                    <small class="devis-categories-hint">Cochez une ou plusieurs catégories</small>
                    <div class="devis-categories-list" id="devis-categories-list"></div>
                </fieldset>

                <div class="devis-form-row">
                    <div class="devis-form-group">
                        <label for="devis-type">Type de visiteur <span class="required">*</span></label>
                        <select id="devis-type" name="type_visiteur" required>
                            <option value="" disabled selected>Sélectionnez une option</option>
                            <option value="Entreprise">Entreprise</option>
                            <option value="ONG">ONG</option>
                            <option value="Institution">Institution</option>
                            <option value="Collectivités">Collectivités</option>
                            <option value="Couples">Couples</option>
                            <option value="Familles">Familles</option>
                            <option value="Organisateurs d'événement">Organisateurs d'événement</option>
                            <option value="Artistes">Artistes</option>
                            <option value="Agences de communication">Agences de communication</option>
                            <option value="Particuliers">Particuliers</option>
                        </select>
                    </div>
                    <div class="devis-form-group">
                        <label for="devis-budget">Budget estimatif (Ar)</label>
                        <input type="number" id="devis-budget" name="budget" min="0" step="1000" placeholder="Ex: 500000">
                    </div>
                </div>

                <div class="devis-form-row">
                    <div class="devis-form-group">
                        <label for="devis-date">Date souhaitée</label>
                        <input type="date" id="devis-date" name="date_souhaitee">
                    </div>
                    <div class="devis-form-group">
                        <label for="devis-fichier">Pièce jointe</label>
                        <input type="file" id="devis-fichier" name="piece_jointe">
                    </div>
                </div>

                <div class="devis-form-group devis-form-full">
                    <label for="devis-description">Description du projet <span class="required">*</span></label>
                    <textarea id="devis-description" name="description" rows="4" required placeholder="Décrivez votre besoin, le lieu, le nombre de personnes concernées, etc."></textarea>
                </div>

                <div class="devis-form-actions">
                    <button type="button" class="btn btn-outline" id="devis-annuler">Annuler</button>
                    <button type="submit" class="btn btn-green">Confirmer</button>
                </div>
            </form>
        </div>
    </div>

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
    