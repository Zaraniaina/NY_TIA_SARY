<?php
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();

// Récupérer les articles de blog publiés avec leur type
$blogs = [];
try {
    $blogs = $pdo->query(
        "SELECT b.ID_BLOG, b.TITRE_BLOG, b.CONTENU, b.IMAGE_COURVERTURE, 
                b.DATE_PUBLICATION, b.DATE_MODIFICATION, 
                t.LIB_TYPE_BLOG as TYPE_BLOG
         FROM blog b
         JOIN type_blog t ON b.ID_TYPE_BLOG = t.ID_TYPE_BLOG
         WHERE b.STATUS_BLOG = 'PUBLIER'
         ORDER BY b.DATE_PUBLICATION DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // En cas d'erreur, garder un tableau vide
}

// Récupérer les types de blog pour les filtres
$typesBlog = [];
try {
    $typesBlog = $pdo->query(
        "SELECT ID_TYPE_BLOG, LIB_TYPE_BLOG FROM type_blog ORDER BY LIB_TYPE_BLOG ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // En cas d'erreur
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog | NY TIA SARY - Conseil Photo & Vidéo</title>
    <?php include 'composante/csslink.php'; ?>
</head>

<body>

    <?php include 'composante/header.php'; ?>

    <!-- HERO SECTION -->
    <section class="blog-hero">
        <div class="container">
            <div class="blog-hero-content">
                <span class="blog-tag">NOTRE BLOG</span>
                <h1 class="blog-title">Des conseils, des tendances et des inspirations</h1>
                <p class="blog-subtitle">Découvrez nos réflexions sur la photographie, la vidéo et la création visuelle.</p>
            </div>
        </div>
    </section>

    <!-- BLOG SECTION -->
    <section class="blog">
        <div class="container">
            <h2 class="section-title">Articles récents<span></span></h2>

            <!-- FILTRAGE PAR CATEGORIE -->
            <?php if (count($typesBlog) > 0): ?>
            <div class="blog-filters" id="blog-filters">
                <button class="filter-btn active" data-filter="all">Tous les articles</button>
                <?php foreach ($typesBlog as $type): ?>
                <button class="filter-btn" data-filter="<?= htmlspecialchars($type['LIB_TYPE_BLOG']) ?>">
                    <?= htmlspecialchars($type['LIB_TYPE_BLOG']) ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- GRILLE DES ARTICLES -->
            <?php if (count($blogs) > 0): ?>
            <div class="blog-grid" id="blog-grid">
                <?php foreach ($blogs as $blog): 
                    $date = new DateTime($blog['DATE_PUBLICATION']);
                    $extrait = substr(strip_tags($blog['CONTENU']), 0, 150);
                    // Générer l'ID pour le lien "Lire la suite"
                    $articleId = $blog['ID_BLOG'];
                ?>
                <article class="blog-card" data-category="<?= htmlspecialchars($blog['TYPE_BLOG']) ?>">
                    <div class="blog-card-image">
                        <?php if (!empty($blog['IMAGE_COURVERTURE']) && $blog['IMAGE_COURVERTURE'] != 'aucune_image'): ?>
                            <img src="<?= htmlspecialchars($blog['IMAGE_COURVERTURE']) ?>" 
                                 alt="<?= htmlspecialchars($blog['TITRE_BLOG']) ?>" 
                                 loading="lazy">
                        <?php else: ?>
                            <div class="blog-card-placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="blog-card-badge">
                            <?= htmlspecialchars($blog['TYPE_BLOG']) ?>
                        </div>
                    </div>
                    
                    <div class="blog-card-content">
                        <div class="blog-card-meta">
                            <time datetime="<?= $date->format('Y-m-d') ?>">
                                <?= $date->format('d m Y') ?>
                            </time>
                        </div>
                        
                        <h3 class="blog-card-title">
                            <?= htmlspecialchars($blog['TITRE_BLOG']) ?>
                        </h3>
                        
                        <p class="blog-card-excerpt">
                            <?= htmlspecialchars($extrait) ?>...
                        </p>
                        
                        <a href="#article-<?= $articleId ?>" class="blog-card-link">
                            Lire l'article
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <!-- LOADER/SIÈGE POUR LADE -->
            <div class="blog-loading" id="blog-loading" style="display: none;">
                <div class="loading-spinner"></div>
            </div>

            <!-- PAGINATION -->
            <?php if (count($blogs) > 6): ?>
            <div class="blog-pagination" id="blog-pagination">
                <button class="pagination-btn active" data-page="1">1</button>
                <button class="pagination-btn" data-page="2">2</button>
                <button class="pagination-btn" data-page="3">3</button>
                <!-- Plus de pages selon le nombre d'articles -->
            </div>
            <?php endif; ?>

            <?php else: ?>
                <!-- ÉTAT VIDE -->
                <div class="blog-empty-state">
                    <div class="blog-empty-icon">
                        <i class="fas fa-pen-nib"></i>
                    </div>
                    <h3>Aucun article disponible</h3>
                    <p>Le blog sera bientôt mis à jour avec nos conseils et connaissances.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- JavaScript pour le blog -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Filtrage des articles par catégorie
            const filterButtons = document.querySelectorAll('.filter-btn');
            const blogCards = document.querySelectorAll('.blog-card');

            if (filterButtons.length > 0 && blogCards.length > 0) {
                filterButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        // Gérer l'active state des boutons
                        filterButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');

                        const filterValue = this.getAttribute('data-filter');

                        blogCards.forEach(card => {
                            const cardCategory = card.getAttribute('data-category');
                            
                            if (filterValue === 'all' || cardCategory === filterValue) {
                                card.style.display = 'block';
                                // Animation d'apparition
                                card.style.opacity = '0';
                                card.style.transform = 'translateY(20px)';
                                setTimeout(() => {
                                    card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                                    card.style.opacity = '1';
                                    card.style.transform = 'translateY(0)';
                                }, 50);
                            } else {
                                card.style.display = 'none';
                            }
                        });
                    });
                });
            }

            // 2. Pagination (fonctionnalité basique)
            const paginationBtns = document.querySelectorAll('.pagination-btn');
            if (paginationBtns.length > 0) {
                paginationBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        paginationBtns.forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                        // Ici, vous pourriez implémenter la logique de pagination réelle
                        // en masquant/montrant les articles correspondants
                    });
                });
            }

            // 3. Animation d'apparition au scroll (Intersection Observer)
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observer les cartes de blog
            document.querySelectorAll('.blog-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });
        });
    </script>
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
</body>

</html>