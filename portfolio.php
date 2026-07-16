<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'composante/csslink.php'; ?>
    <title>Portfolio</title>
</head>

<body>
    <?php include "composante/header.php";?>
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
    <!-- Boutons Flottants -->
<div class="floating-buttons">
    <button class="btn-float btn-devis" id="btn-open-devis-float" title="Demander un Devis">
        <i class="fas fa-file-invoice-dollar"></i> Demander devis
    </button>
    <a href="login/login.php" class="btn-float btn-reserver" title="Réserver">
        <i class="far fa-calendar-check"></i> Réserver
    </a>
</div>
    <!-- Footer page -->
    <?php include "composante/footer.php" ?>
</body>

</html>