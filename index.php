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
                    <a href="#devis" class="btn btn-outline">DEMANDER UN DEVIS</a>
                </div>
            </div>
            <div class="hero-brackets">
                <!-- Visual placeholder: a beautiful image shot with professional gear -->
                <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=800&q=80](https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=800&q=80"
                    alt="Focus brackets on professional gear">
            </div>
        </div>
    </section>

    <!-- Footer page -->
     <?php include "composante/footer.php"?>

    <!-- LIGHTBOX COMPONENT (UX Feature) -->
    <div class="lightbox" id="lightbox">
        <div class="lightbox-content">
            <span class="lightbox-close" id="lightbox-close">&times;</span>
            <img id="lightbox-img" class="lightbox-img" src="" alt="Portfolio View">
        </div>
    </div>

    <!-- The Javascript is essential for all interactivity, loaded via footer.php -->
</body>

</html>