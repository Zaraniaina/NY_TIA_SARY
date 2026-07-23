<?php
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();

// Récupération des témoignages depuis la base de données
$temoignages_db = [];
try {
    $temoignages_db = $pdo->query(
        'SELECT t.*, c.NOM_CLIENT, c.PRENOM_CLIENT, c.PHOTO_CLIENT, p.LIB_PRESTATION
         FROM TEMOIGNAGE t
         JOIN RESERVATION r ON t.ID_RESERVATION = r.ID_RESERVATION
         JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
         JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
         ORDER BY t.ID_TEMOIGNAGE DESC LIMIT 10'
    )->fetchAll();
} catch (Exception $e) {
    // Si la table n'existe pas ou erreur, on garde un tableau vide
}

// Fallback to static if empty
$temoignages = count($temoignages_db) > 0 ? $temoignages_db : [
    [
        'NOM_CLIENT' => 'Rakotondrabe', 'PRENOM_CLIENT' => 'Ranja',
        'PHOTO_CLIENT' => '',
        'NOTE' => 5, 'MESS_RESERVATION' => 'Un travail absolument remarquable ! Les photos de notre conférence annuelle ont dépassé toutes nos attentes. L\'équipe NY TIA SARY sait capter l\'émotion et le professionnalisme dans chaque cliché.',
        'LIB_PRESTATION' => 'Événementiel Corporate'
    ],
    [
        'NOM_CLIENT' => 'Sandra & Hery', 'PRENOM_CLIENT' => '',
        'PHOTO_CLIENT' => '',
        'NOTE' => 5, 'MESS_RESERVATION' => 'Notre mariage était le plus beau jour de notre vie, et NY TIA SARY l\'a immortalisé avec une sensibilité rare. Le clip cinématique nous fait revivre chaque instant.',
        'LIB_PRESTATION' => 'Reportage Mariage'
    ],
    [
        'NOM_CLIENT' => 'Andriamahefa', 'PRENOM_CLIENT' => 'Marie-Luce',
        'PHOTO_CLIENT' => '',
        'NOTE' => 4, 'MESS_RESERVATION' => 'Les packshots réalisés pour notre catalogue ont transformé l\'image de notre marque. Résultat ultra-professionnel, délais respectés et équipe très à l\'écoute.',
        'LIB_PRESTATION' => 'Photographie Produit'
    ]
];

// Récupération des prestations pour le formulaire de devis
$prestationsList = $pdo->query(
    "SELECT ID_PRESTATION, LIB_PRESTATION FROM prestations ORDER BY LIB_PRESTATION ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Récupération dynamique des éléments du portfolio / médias (Évite les doublons avec GROUP BY m.ID_MEDIA ou m.PATH_MEDIA si besoin)
try {
    $portfolioQuery = $pdo->query("
        SELECT m.PATH_MEDIA, m.TYPE_MEDIA, c.LIB_CATEGORIE, p.LIB_PRESTATION 
        FROM media m
        JOIN reservation r ON m.ID_RESERVATION = r.ID_RESERVATION
        JOIN reservation_categorie rc ON r.ID_RESERVATION = rc.ID_RESERVATION
        JOIN categorie c ON rc.ID_CATEGORIE = c.ID_CATEGORIE
        JOIN prestations p ON c.ID_PRESTATION = p.ID_PRESTATION
        GROUP BY m.PATH_MEDIA
    ");
    $portfolioItems = $portfolioQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $portfolioItems = [];
}

$devisStatus  = $_GET['devis'] ?? null;
$devisMessage = $_GET['message'] ?? null;

// Fetch partenaires
$partenaires = [];
try {
    $partenaires = $pdo->query("SELECT * FROM partenaire")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Graceful fallback
}
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
                <p>Studio professionnel de photographie et production vidéo, capturant l'essence unique de votre entreprise, de vos événements et de vos projets les plus chers.</p>
                <div class="hero-btns">
                    <a href="#services" class="btn btn-green">DÉCOUVRIR NOS OFFRES</a>
                </div>
            </div>
            <div class="hero-brackets">
                <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=800&q=80" alt="Focus brackets on professional gear">
            </div>
        </div>
    </section>

    <!-- SECTION A PROPOS (Human & Unique Asymmetric Layout) -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-grid">
                <div class="about-image">
                    <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=800&q=80" alt="L'équipe de NY TIA SARY en action">
                </div>
                <div class="about-content">
                    <h2>Nous sommes l'équipe derrière NY TIA <span>SARY</span>.</h2>
                    <p class="about-lead">Forts de plus de 10 ans d'expérience, nous unissons créativité technique et sensibilité humaine pour sublimer votre identité visuelle.</p>
                    <p>Fondé à Toamasina et rayonnant dans tout Madagascar, NY TIA SARY n'est pas seulement un studio visuel, c'est un collectif de passionnés. Notre approche unique consiste à écouter votre histoire avant de déclencher l'objectif.</p>
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
                <div class="service-card">
                    <div class="icon-box"><i class="fas fa-building"></i></div>
                    <h3>Photographie Corporate</h3>
                    <p>Hatsarao ny endrika professionnelle anao. Portraits d'équipe, trombinoscopes et photos d'entreprise.</p>
                    <a href="service.php#corporate" class="service-link">EN SAVOIR PLUS <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card">
                    <div class="icon-box"><i class="fas fa-calendar-alt"></i></div>
                    <h3>Événementiel & Mode</h3>
                    <p>Capturer l'ambiance et les moments clés de vos conférences, concerts, défilés et mariages.</p>
                    <a href="service.php#evenementiel" class="service-link">EN SAVOIR PLUS <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card">
                    <div class="icon-box"><i class="fas fa-video"></i></div>
                    <h3>Production Vidéo</h3>
                    <p>Films institutionnels, spots publicitaires, interviews et clips musicaux qui racontent une histoire.</p>
                    <a href="service.php#video" class="service-link">EN SAVOIR PLUS <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card">
                    <div class="icon-box"><i class="fas fa-paper-plane"></i></div>
                    <h3>Prises de vue par Drone</h3>
                    <p>Prenez de la hauteur. Photos et vidéos aériennes spectaculaires pour valoriser vos projets immobiliers ou événementiels.</p>
                    <a href="service.php#drone" class="service-link">EN SAVOIR PLUS <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card">
                    <div class="icon-box"><i class="fas fa-box"></i></div>
                    <h3>Photographie de Produits</h3>
                    <p>Sublimez vos produits. Packshots haut de gamme et mises en scène créatives pour catalogues et e-commerce.</p>
                    <a href="service.php#produits" class="service-link">EN SAVOIR PLUS <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION PORTFOLIO (Dynamique sans doublons grâce à GROUP BY) -->
    <section id="portfolio" class="portfolio">
        <div class="container">
            <h2 class="section-title">Notre <span>Savoir-Faire</span></h2>
            
            <div class="portfolio-grid" id="portfolio-grid">
                <?php if (!empty($portfolioItems)): ?>
                    <?php foreach ($portfolioItems as $item): ?>
                        <div class="portfolio-item" data-category="corporate">
                            <?php if (strtolower($item['TYPE_MEDIA'] ?? '') === 'video'): ?>
                                <video controls width="100%">
                                    <source src="<?php echo htmlspecialchars($item['PATH_MEDIA']); ?>" type="video/mp4">
                                    Votre navigateur ne supporte pas la vidéo.
                                </video>
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars($item['PATH_MEDIA']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['LIB_CATEGORIE'] ?? 'Portfolio'); ?> NY TIA SARY">
                            <?php endif; ?>

                            <div class="portfolio-overlay">
                                <span><?php echo htmlspecialchars($item['LIB_PRESTATION']); ?></span>
                                <h4><?php echo htmlspecialchars($item['LIB_CATEGORIE']); ?></h4>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback statique si la base de données ne renvoie aucun média -->
                    <div class="portfolio-item" data-category="corporate">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=800&q=80" alt="Executive Portrait NY TIA SARY">
                        <div class="portfolio-overlay">
                            <span>Corporate</span>
                            <h4>Executive Portrait</h4>
                        </div>
                    </div>
                    <div class="portfolio-item" data-category="evenement">
                        <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=800&q=80" alt="Événementiel NY TIA SARY">
                        <div class="portfolio-overlay">
                            <span>Événements</span>
                            <h4>Gala & Conférence</h4>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- SECTION TÉMOIGNAGES CLIENTS -->
    <section id="temoignages" class="temoignages">
        <div class="container">
            <div class="temoignages-header">
                <span class="section-pretitle temoignages-pretitle">ILS NOUS FONT CONFIANCE</span>
                <h2 class="section-title temoignages-title">Ce que disent <span>nos clients</span></h2>
            </div>

            <div class="temoignages-slider-container">
                <button class="slider-nav-btn prev-btn" id="temo-prev" aria-label="Précédent">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="temoignages-slider-track" id="temo-track">
                    <?php foreach ($temoignages as $index => $temo): 
                        $nom = htmlspecialchars($temo['NOM_CLIENT'] ?? '');
                        $prenom = htmlspecialchars($temo['PRENOM_CLIENT'] ?? '');
                        $initials = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
                        if(empty($initials)) $initials = 'CL';
                        
                        $note = (int)($temo['NOTE'] ?? 5);
                        $photo = $temo['PHOTO_CLIENT'] ?? '';
                    ?>
                    <div class="temoignage-slide <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
                        <div class="slide-content">
                            <i class="fas fa-quote-left quote-icon"></i>
                            <div class="temoignage-stars">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="fas fa-star <?= $i <= $note ? 'filled' : 'empty' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="temoignage-text">"<?= nl2br(htmlspecialchars($temo['MESS_RESERVATION'] ?? '')) ?>"</p>
                            
                            <div class="temoignage-author">
                                <?php if (!empty($photo) && file_exists(__DIR__ . '/' . $photo)): ?>
                                    <img src="<?= htmlspecialchars($photo) ?>" alt="Avatar" class="temoignage-avatar">
                                <?php else: ?>
                                    <div class="temoignage-avatar-placeholder" style="background-color: var(--primary-green);">
                                        <span><?= $initials ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="temoignage-info">
                                    <strong><?= $prenom . ' ' . $nom ?></strong>
                                    <span><?= htmlspecialchars($temo['LIB_PRESTATION'] ?? 'Client satisfait') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button class="slider-nav-btn next-btn" id="temo-next" aria-label="Suivant">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <div class="temoignages-dots" id="temo-dots">
                <?php foreach ($temoignages as $index => $temo): ?>
                    <span class="temo-dot <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>"></span>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECTION PARTENAIRES -->
    <?php if (!empty($partenaires)): ?>
    <section id="partenaires" class="partenaires-section" style="padding: 60px 0; background: #fff; overflow: hidden;">
        <div class="container">
            <h2 class="section-title" style="text-align: center; margin-bottom: 40px;">Nos <span>Partenaires</span></h2>
            <div class="partenaires-slider">
                <div class="partenaires-track">
                    <?php 
                    // Make sure we have enough items to fill the screen (at least ~8 items)
                    $multiplier = ceil(8 / (count($partenaires) ?: 1));
                    $baseItems = [];
                    for ($i = 0; $i < $multiplier; $i++) {
                        $baseItems = array_merge($baseItems, $partenaires);
                    }
                    // Duplicate exactly once for infinite scroll effect (50% translation)
                    $sliderItems = array_merge($baseItems, $baseItems);
                    foreach ($sliderItems as $partenaire): 
                    ?>
                        <div class="partenaire-slide">
                            <?php if (!empty($partenaire['LIEN_PARTENAIRE'])): ?>
                                <a href="<?= htmlspecialchars($partenaire['LIEN_PARTENAIRE']) ?>" target="_blank" rel="noopener noreferrer">
                            <?php endif; ?>
                            
                            <img src="<?= htmlspecialchars($partenaire['PATH_LOGO']) ?>" alt="Logo partenaire" title="<?= htmlspecialchars($partenaire['DESCRIPTIONS'] ?? '') ?>">
                            
                            <?php if (!empty($partenaire['LIEN_PARTENAIRE'])): ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer page -->
    <?php include "composante/footer.php"; ?>
    

    <?php include "composante/devis_visiteur.php"?>                   
 
    <!-- Boutons Flottants -->
    <div class="floating-buttons">
        <button class="btn-float btn-devis" id="btn-open-devis-float" title="Demander un Devis">
            <i class="fas fa-file-invoice-dollar"></i> Demander devis
        </button>
        <a href="login/login.php" class="btn-float btn-reserver" title="Réserver">
            <i class="far fa-calendar-check"></i> Réserver
        </a>
    </div>

</body>

</html>