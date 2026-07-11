<?php
require_once 'config/database.php';
$pdo        = getPDO();
 // fournit $pdo — adapte le chemin si besoin
$prestationsList = $pdo->query(
    "SELECT ID_PRESTATION, LIB_PRESTATION FROM prestations ORDER BY LIB_PRESTATION ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$devisStatus  = $_GET['devis'] ?? null;
$devisMessage = $_GET['message'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NY TIA SARY | Studio Photo, Vidéo & Conception Graphique</title>
    <?php include 'composante/csslink.php'; ?>
</head>

<body>

    <?php include 'composante/header.php'; ?>
    <!-- BANNER VIDEO SECTION (Immersive & Premium) -->
    <section class="video-banner-section" id="video-banner">
        <div class="banner-video-container">
            <video autoplay muted loop playsinline id="banner-video">
                <source src="assets/videos/Photographer.mp4" type="video/mp4">
                Votre navigateur ne supporte pas la lecture de vidéos HTML5.
            </video>
        </div>
        <div class="banner-video-overlay"></div>
        <div class="banner-content">
            <span class="banner-tag">NY TIA SARY | PRODUCTION</span>
            <h2 class="banner-title">L'art de capturer l'instant</h2>
            <p class="banner-desc">Découvrez l'excellence visuelle à travers nos réalisations photo et vidéo.</p>
            <div class="banner-actions">
                <a href="#hero" class="banner-scroll-btn" id="scroll-to-hero" aria-label="Défiler vers le bas">
                    <i class="fas fa-chevron-down"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- HERO SECTION (Light & Text-Focused for Professionalism) -->
    <section class="hero" id="hero">
        <div class="container hero-container">
            <div class="hero-text">
                <span class="hero-tag">VOS HISTOIRES EN IMAGES</span>
                <h1>Hatsarao sy ho <span>Tiava</span> avy hatrany.</h1>
                <p>Studio professionnel de photographie et production vidéo, capturant l'essence unique de votre
                    entreprise, de vos événements et de vos projets les plus chers.</p>
                <div class="hero-btns">
                    <a href="#services" class="btn btn-green">DÉCOUVRIR NOS OFFRES</a>
                    <!--<a href="#devis" class="btn btn-outline">DEMANDER UN DEVIS</a> -->
                    
                   <button  class="btn btn-outline" id="btn-open-devis">DEMANDER UN DEVIS</button>
                </div>
            </div>
            <div class="hero-brackets">
                <!-- Visual placeholder: a beautiful image shot with professional gear -->
                <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=800&q=80](https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=800&q=80"
                    alt="Focus brackets on professional gear">
            </div>
        </div>
    </section>
     <!-- SECTION A PROPOS (Human & Unique Asymmetric Layout) -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-grid">
                <div class="about-image">
                    <!-- Image of photographers/videographers working, looking human and approachable -->
                    <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=800&q=80](https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=800&q=80"
                        alt="L'équipe de NY TIA SARY en action">
                </div>
                <div class="about-content">
                    <h2>Nous sommes l'équipe derrière NY TIA <span>SARY</span>.</h2>
                    <p class="about-lead">Forts de plus de 10 ans d'expérience, nous unissons créativité technique et
                        sensibilité humaine pour sublimer votre identité visuelle.</p>
                    <p>Fondé à Toamasina et rayonnant dans tout Madagascar, NY TIA SARY n'est pas seulement un studio
                        visuel, c'est un collectif de passionnés. Notre approche unique consiste à écouter votre
                        histoire avant de déclencher l'objectif. Que vous soyez une multinationale ou un couple
                        célébrant l'amour, nous capturons l'émotion et l'authenticité qui vous rendent uniques.</p>
                    <ul class="about-list">
                        <li><i class="fas fa-check-circle"></i> Expertise Technique de Pointe</li>
                        <li><i class="fas fa-check-circle"></i> Équipement Professionnel</li>
                        <li><i class="fas fa-check-circle"></i> Approche Humaniste</li>
                        <li><i class="fas fa-check-circle"></i> Livraison Rapide et Soignée</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Presentations services-->
    <section id="services" class="services">
        <div class="container">
            <h2 class="section-title">Nos <span>Prestations</span> Visuelles</h2>
            <div class="services-grid">
                <!-- Service 1: Photo Corporate -->
                <div class="service-card">
                    <div class="icon-box"><i class="fas fa-building"></i></div>
                    <h3>Photographie Corporate</h3>
                    <p>Hatsarao ny endrika professionnelle anao. Portraits d'équipe, trombinoscopes et photos
                        d'entreprise.</p>
                    <a href="service.php#corporate" class="service-link">EN SAVOIR PLUS <i class="fas fa-arrow-right"></i></a>
                </div>
                <!-- Service 2: Photo Evenementielle -->
                <div class="service-card">
                    <div class="icon-box"><i class="fas fa-calendar-alt"></i></div>
                    <h3>Événementiel & Mode</h3>
                    <p>Capturer l'ambiance et les moments clés de vos conférences, concerts, défilés et mariages.</p>
                    <a href="service.php#evenementiel" class="service-link">EN SAVOIR PLUS <i class="fas fa-arrow-right"></i></a>
                </div>
                <!-- Service 3: Video Production -->
                <div class="service-card">
                    <div class="icon-box"><i class="fas fa-video"></i></div>
                    <h3>Production Vidéo</h3>
                    <p>Films institutionnels, spots publicitaires, interviews et clips musicaux qui racontent une
                        histoire.</p>
                    <a href="service.php#video" class="service-link">EN SAVOIR PLUS <i class="fas fa-arrow-right"></i></a>
                </div>

                <!-- Service 4: Prises de vue par Drone -->
                <div class="service-card">
                    <div class="icon-box"><i class="fas fa-paper-plane"></i></div>
                    <h3>Prises de vue par Drone</h3>
                    <p>Prenez de la hauteur. Photos et vidéos aériennes spectaculaires pour valoriser vos projets immobiliers ou événementiels.</p>
                    <a href="service.php#drone" class="service-link">EN SAVOIR PLUS <i class="fas fa-arrow-right"></i></a>
                </div>
                <!-- Service 5: Productions Produits -->
                <div class="service-card">
                    <div class="icon-box"><i class="fas fa-box"></i></div>
                    <h3>Photographie de Produits</h3>
                    <p>Sublimez vos produits. Packshots haut de gamme et mises en scène créatives pour catalogues et e-commerce.</p>
                    <a href="service.php#produits" class="service-link">EN SAVOIR PLUS <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION PORTFOLIO (Hatsaraina miaraka amin'ireo sary mivantana) -->
    <section id="portfolio" class="portfolio">
        <div class="container">
            <h2 class="section-title">Notre <span>Savoir-Faire</span></h2>

            <div class="portfolio-filters">
                <button class="filter-btn active" data-filter="all">Tout</button>
                <button class="filter-btn" data-filter="corporate">Corporate</button>
                <button class="filter-btn" data-filter="evenement">Événements</button>
                <button class="filter-btn" data-filter="video">Vidéo</button>
                <button class="filter-btn" data-filter="design">Design</button>
            </div>

            <div class="portfolio-grid" id="portfolio-grid">
                <!-- Sary 1: Corporate (Portrait olona mitsiky, tsotra sy matihanina) -->
                <div class="portfolio-item" data-category="corporate">
                    <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=800&q=80"
                        alt="Executive Portrait NY TIA SARY">
                    <div class="portfolio-overlay">
                        <span>Corporate</span>
                        <h4>Executive Portrait</h4>
                    </div>
                </div>

                <!-- Sary 2: Événement (Fankalazana sy fiaraha-monina feno hafaliana) -->
                <div class="portfolio-item" data-category="evenement">
                    <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=800&q=80"
                        alt="Événementiel NY TIA SARY">
                    <div class="portfolio-overlay">
                        <span>Événements</span>
                        <h4>Gala & Conférence</h4>
                    </div>
                </div>

                <!-- Sary 3: Vidéo (Sehatra fitarihana sy fakana sary mihetsika) -->
                <div class="portfolio-item" data-category="video">
                    <img src="https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?auto=format&fit=crop&w=800&q=80"
                        alt="Production Vidéo NY TIA SARY">
                    <div class="portfolio-overlay">
                        <span>Production Vidéo</span>
                        <h4>Tournage Institutionnel</h4>
                    </div>
                </div>

                <!-- Sary 4: Design / Conception Graphique (Fandrafetana sy logo) -->
                <div class="portfolio-item" data-category="design">
                    <img src="https://images.unsplash.com/photo-1626785774573-4b799315345d?auto=format&fit=crop&w=800&q=80"
                        alt="Conception Graphique NY TIA SARY">
                    <div class="portfolio-overlay">
                        <span>Conception Graphique</span>
                        <h4>Identité Visuelle</h4>
                    </div>
                </div>

                <!-- Sary 5: Corporate / Produit (Packshot vokatra madio) -->
                <div class="portfolio-item" data-category="corporate">
                    <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80"
                        alt="Photographie Produit NY TIA SARY">
                    <div class="portfolio-overlay">
                        <span>Corporate / Produit</span>
                        <h4>Packshot Studio</h4>
                    </div>
                </div>

                <!-- Sary 6: Événement / Drone (Fijery ambony nampiasana Drone) -->
                <div class="portfolio-item" data-category="evenement">
                    <img src="https://images.unsplash.com/photo-1508849789987-4e5333c12b78?auto=format&fit=crop&w=800&q=80"
                        alt="Drone View NY TIA SARY">
                    <div class="portfolio-overlay">
                        <span>Drone / Événements</span>
                        <h4>Vue Aérienne Festival</h4>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         SECTION TÉMOIGNAGES CLIENTS
         ============================================================ -->
    <section id="temoignages" class="temoignages">
        <div class="temoignages-bg-deco" aria-hidden="true"></div>
        <div class="container">
            <span class="section-pretitle temoignages-pretitle">ILS NOUS FONT CONFIANCE</span>
            <h2 class="section-title temoignages-title">Ce que disent <span>nos clients</span></h2>

            <!-- Carrousel wrapper -->
            <div class="temoignages-track-wrap" id="temoignages-track-wrap">
                <div class="temoignages-track" id="temoignages-track">

                    <!-- Témoignage 1 -->
                    <div class="temoignage-card">
                        <div class="temoignage-quote-icon"><i class="fas fa-quote-left"></i></div>
                        <div class="temoignage-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="temoignage-text">
                            "Un travail absolument remarquable ! Les photos de notre conférence annuelle ont dépassé toutes nos attentes.<br/> L'équipe NY TIA SARY sait capter l'émotion et le professionnalisme dans chaque cliché.<br/>  Nous les recommandons vivement."
                        </p>
                        <div class="temoignage-author">
                            <div class="temoignage-avatar" style="background-color: #377d49;">
                                <span>RR</span>
                            </div>
                            <div class="temoignage-info">
                                <strong>Ranja Rakotondrabe</strong>
                                <span>Directeur Général — Groupe Tana Business</span>
                            </div>
                        </div>
                        <div class="temoignage-service-badge"><i class="fas fa-calendar-alt"></i> Événementiel Corporate</div>
                    </div>

                    <!-- Témoignage 2 -->
                    <div class="temoignage-card">
                        <div class="temoignage-quote-icon"><i class="fas fa-quote-left"></i></div>
                        <div class="temoignage-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="temoignage-text">
                            "Notre mariage était le plus beau jour de notre vie,<br/>  et NY TIA SARY l'a immortalisé avec une sensibilité rare.<br/>Le clip cinématique nous fait revivre chaque instant. <br/> Merci du fond du cœur pour ce cadeau inestimable."
                        </p>
                        <div class="temoignage-author">
                            <div class="temoignage-avatar" style="background-color: #d93d3d;">
                                <span>SH</span>
                            </div>
                            <div class="temoignage-info">
                                <strong>Sandra & Hery</strong>
                                <span>Jeunes mariés — Antananarivo</span>
                            </div>
                        </div>
                        <div class="temoignage-service-badge"><i class="fas fa-heart"></i> Reportage Mariage</div>
                    </div>

                    <!-- Témoignage 3 -->
                    <div class="temoignage-card">
                        <div class="temoignage-quote-icon"><i class="fas fa-quote-left"></i></div>
                        <div class="temoignage-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="temoignage-text">
                            "Les packshots réalisés pour notre catalogue ont transformé l'image de notre marque.<br/> 
                             Résultat ultra-professionnel, délais respectés et équipe très à l'écoute.<br/>  Nos ventes en ligne ont augmenté de 30% après la publication des nouvelles photos !"
                        </p>
                        <div class="temoignage-author">
                            <div class="temoignage-avatar" style="background-color: #2a5c8a;">
                                <span>ML</span>
                            </div>
                            <div class="temoignage-info">
                                <strong>Marie-Luce Andriamahefa</strong>
                                <span>Fondatrice — Bijouterie Lova</span>
                            </div>
                        </div>
                        <div class="temoignage-service-badge"><i class="fas fa-box"></i> Photographie Produit</div>
                    </div>

                    <!-- Témoignage 4 -->
                    <div class="temoignage-card" >
                        <div class="temoignage-quote-icon"><i class="fas fa-quote-left"></i></div>
                        <div class="temoignage-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="temoignage-text">
                            "Le film institutionnel réalisé pour notre ONG est d'une qualité cinématographique impressionnante.<br/> 
                            NY TIA SARY a su comprendre notre mission et la traduire en images puissantes. Un vrai partenaire créatif."
                        </p>
                        <div class="temoignage-author">
                            <div class="temoignage-avatar" style="background-color: #7d5a2a;">
                                <span>TF</span>
                            </div>
                            <div class="temoignage-info">
                                <strong>Toky Fandresena</strong>
                                <span>Coordinateur — ONG Avotra Mada</span>
                            </div>
                        </div>
                        <div class="temoignage-service-badge"><i class="fas fa-video"></i> Production Vidéo</div>
                    </div>

                    <!-- Témoignage 5 -->
                    <div class="temoignage-card">
                        <div class="temoignage-quote-icon"><i class="fas fa-quote-left"></i></div>
                        <div class="temoignage-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="temoignage-text">
                            "Les prises de vue drone de notre résidence hôtelière sont spectaculaires.<br/>
                            La qualité aérienne a séduit nos partenaires investisseurs dès la première présentation. <br/>
                            Professionnalisme et créativité au rendez-vous !"
                        </p>
                        <div class="temoignage-author">
                            <div class="temoignage-avatar" style="background-color: #5a3a7d;">
                                <span>JR</span>
                            </div>
                            <div class="temoignage-info">
                                <strong>Jean-Paul Razafy</strong>
                                <span>PDG — Résidence Belle Vue Nosy Be</span>
                            </div>
                        </div>
                        <div class="temoignage-service-badge"><i class="fas fa-paper-plane"></i> Drone Immobilier</div>
                    </div>

                </div><!-- /.temoignages-track -->
            </div><!-- /.temoignages-track-wrap -->

            <!-- Contrôles de navigation -->
            <div class="temoignages-controls">
                <button class="temoignage-btn" id="temoignage-prev" aria-label="Témoignage précédent">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="temoignage-dots" id="temoignage-dots">
                    <span class="temoignage-dot active" data-index="0"></span>
                    <span class="temoignage-dot" data-index="1"></span>
                    <span class="temoignage-dot" data-index="2"></span>
                    <span class="temoignage-dot" data-index="3"></span>
                    <span class="temoignage-dot" data-index="4"></span>
                </div>
                <button class="temoignage-btn" id="temoignage-next" aria-label="Témoignage suivant">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <!-- Stats globales -->
            <div class="temoignages-stats">
                <div class="temoignages-stat">
                    <strong>4.9<i class="fas fa-star"></i></strong>
                    <span>Note moyenne</span>
                </div>
                <div class="temoignages-stat-sep"></div>
                <div class="temoignages-stat">
                    <strong>120+</strong>
                    <span>Clients satisfaits</span>
                </div>
                <div class="temoignages-stat-sep"></div>
                <div class="temoignages-stat">
                    <strong>100%</strong>
                    <span>Recommandés</span>
                </div>
            </div>

        </div>
    </section>
    

    <!-- Footer page -->
    <?php include "composante/footer.php"?>
    
 <!-- MODAL DEMANDE DE DEVIS -->
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
            <div class="devis-form-row">
    <div class="devis-form-group">
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
    <div class="devis-form-group">
        <label for="devis-categorie">Catégorie</label>
        <select id="devis-categorie" name="id_categorie" disabled>
            <option value="" selected>Choisissez d'abord une prestation</option>
        </select>
    </div>
</div>

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
<?php if ($devisStatus && $devisMessage): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        Toastify({
            text: <?= json_encode($devisMessage, JSON_UNESCAPED_UNICODE) ?>,
            duration: 6000,
            gravity: "top",
            position: "right",
            close: true,
            stopOnFocus: true,
            style: {
                background: <?= $devisStatus === 'success'
                    ? "'linear-gradient(135deg, #377d49, #2a5c3a)'"
                    : "'linear-gradient(135deg, #d93d3d, #a82c2c)'" ?>,
                borderRadius: "6px",
                fontFamily: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
                fontWeight: "600",
                boxShadow: "0 10px 30px rgba(0, 0, 0, 0.15)",
            },
        }).showToast();

        // Nettoie l'URL pour qu'un rafraîchissement ne réaffiche pas le toast
        const url = new URL(window.location);
        url.searchParams.delete('devis');
        url.searchParams.delete('message');
        url.searchParams.delete('id_devis');
        window.history.replaceState({}, '', url);
    });
</script>
<?php endif; ?>  
</body>

</html>