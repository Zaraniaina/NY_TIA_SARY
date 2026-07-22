<?php
// Connexion à la base de données
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=ny_tia_sary_db;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// 1. Récupérer toutes les prestations pour créer les boutons de filtres dynamiques
try {
    $prestationsQuery = $pdo->query("SELECT * FROM prestations");
    $prestationsList = $prestationsQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $prestationsList = [];
}

// 2. Récupérer les médias avec leurs prestations et catégories associées
try {
    $query = $pdo->query("
        SELECT m.PATH_MEDIA, m.TYPE_MEDIA, c.LIB_CATEGORIE, p.ID_PRESTATION, p.LIB_PRESTATION 
        FROM media m
        JOIN reservation r ON m.ID_RESERVATION = r.ID_RESERVATION
        JOIN reservation_categorie rc ON r.ID_RESERVATION = rc.ID_RESERVATION
        JOIN categorie c ON rc.ID_CATEGORIE = c.ID_CATEGORIE
        JOIN prestations p ON c.ID_PRESTATION = p.ID_PRESTATION
        GROUP BY m.PATH_MEDIA
    ");
    $portfolioItems = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $portfolioItems = [];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'composante/csslink.php'; ?>
    <title>Portfolio - Ny Tia Sary</title>
</head>

<body>
    <?php include "composante/header.php"; ?>

    <!-- SECTION PORTFOLIO -->
    <section id="portfolio" class="portfolio">
        <div class="container">
            <h2 class="section-title">Notre <span>Savoir-Faire</span></h2>

            <!-- Boutons de filtres générés dynamiquement depuis la table prestations -->
            <div class="portfolio-filters">
                <button class="filter-btn active" data-filter="all">Tout</button>
                <?php foreach ($prestationsList as $pres): ?>
                    <button class="filter-btn" data-filter="pres-<?= $pres['ID_PRESTATION']; ?>">
                        <?= htmlspecialchars($pres['LIB_PRESTATION']); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="portfolio-grid" id="portfolio-grid">
                <?php if (!empty($portfolioItems)): ?>
                    <?php foreach ($portfolioItems as $item): 
                        $idPresta = $item['ID_PRESTATION'];
                        $isvideo = (strtolower($item['TYPE_MEDIA'] ?? '') === 'video');
                    ?>
                        <div class="portfolio-item" data-category="pres-<?= $idPresta; ?>">
                            <?php if ($isvideo): ?>
                                <video controls width="100%">
                                    <source src="<?= htmlspecialchars($item['PATH_MEDIA']); ?>" type="video/mp4">
                                    Votre navigateur ne supporte pas la vidéo.
                                </video>
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($item['PATH_MEDIA']); ?>" 
                                     alt="<?= htmlspecialchars($item['LIB_CATEGORIE']); ?> NY TIA SARY">
                            <?php endif; ?>

                            <div class="portfolio-overlay">
                                <span><?= htmlspecialchars($item['LIB_PRESTATION']); ?></span>
                                <h4><?= htmlspecialchars($item['LIB_CATEGORIE']); ?></h4>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="portfolio-item" data-category="pres-1">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=800&q=80" alt="Exemple NY TIA SARY">
                        <div class="portfolio-overlay">
                            <span>Exemple</span>
                            <h4>Aucun média dans la base de données</h4>
                        </div>
                    </div>
                <?php endif; ?>
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
    <?php include "composante/footer.php"; ?>
</body>

</html>