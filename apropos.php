<?php
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();

$partenaires = [];
try {
    $partenaires = $pdo->query("SELECT * FROM partenaire")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'composante/csslink.php'; ?>
    <title>A propos</title>
</head>

<body>
    <?php include 'composante/header.php'; ?>

    <!-- SECTION A PROPOS (Human & Unique Asymmetric Layout) -->
    <section id="about" class="about">
        <div class="container">
            <h2 class="section-title">&Agrave; <span>propos</span> de nous</h2>
            <div class="about-grid">
                
                <div class="about-image">
                    <img src="https://images.pexels.com/photos/3182812/pexels-photo-3182812.jpeg?auto=compress&cs=tinysrgb&h=720&w=1080" alt="L'équipe de NY TIA SARY en action">
                </div>
                <div class="about-content">
                    <h2>Nous sommes l'équipe derrière NY TIA <span>SARY</span>.</h2>
                    <p class="about-lead">Forts de plus de 10 ans d'expérience, nous unissons créativité technique et sensibilité humaine pour sublimer votre identité visuelle.</p>
                    <p>Fondé à Toamasina et rayonnant dans tout Madagascar, NY TIA SARY est un collectif de passionnés. Nous privilégions l'écoute et l'authenticité pour raconter votre histoire à travers l'image.</p>
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

    <!-- HISTOIRE -->
    <section id="histoire" class="history">
        <div class="container">
            <h2 class="section-title">Histoire</h2>
            <p>NY TIA SARY est né d'une rencontre entre photographes et vidéastes locaux qui partageaient une volonté commune : mettre en valeur les histoires malgaches avec sens et qualité. Depuis nos débuts, nous avons accompagné des entreprises, des ONG et des familles dans tout Madagascar.</p>
        </div>
    </section>

    <!-- VISION & MISSION -->
    <section id="vision-mission" class="vm">
        <div class="container">
            <h2 class="section-title">Vision & Mission</h2>
            <div class="service-detail-row">
                <div class="service-detail-content">
                    <h3 class="service-detail-tag">Vision</h3>
                    <p>Devenir le studio de référence à Madagascar pour la narration visuelle professionnelle, ancrée dans la culture locale et reconnue internationalement.</p>
                </div>
                <div class="service-detail-content">
                    <h3 class="service-detail-tag">Mission</h3>
                    <p>Permettre à chaque client d'exprimer son identité par des images fortes, en combinant savoir-faire technique et démarche créative personnalisée.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- VALEURS -->
    <section id="valeurs" class="values">
        <div class="container">
            <h2 class="section-title">Valeurs</h2>
            <ul class="about-list">
                <li><i class="fas fa-check-circle"></i> Authenticité</li>
                <li><i class="fas fa-check-circle"></i> Respect</li>
                <li><i class="fas fa-check-circle"></i> Excellence</li>
                <li><i class="fas fa-check-circle"></i> Collaboration</li>
            </ul>
        </div>
    </section>

    <!-- EQUIPE -->
    <section id="equipe" class="team">
        <div class="container">
            <h2 class="section-title">L'équipe</h2>
            <div class="services-grid team-grid">
                <div class="service-card">
                    <div class="team-photo">
                        <img src="https://images.pexels.com/photos/1181686/pexels-photo-1181686.jpeg?auto=compress&cs=tinysrgb&h=900&w=1200" alt="Rasoa photographe">
                    </div>
                    <div class="service-card-content">
                        <h3>Rasoa — Photographe</h3>
                        <p>Spécialiste portrait et reportage, Rasoa capte les émotions avec sensibilité.</p>
                    </div>
                </div>
                <div class="service-card">
                    <div class="team-photo">
                        <img src="https://images.pexels.com/photos/220453/pexels-photo-220453.jpeg?auto=compress&cs=tinysrgb&h=900&w=1200" alt="Hery vidéaste">
                    </div>
                    <div class="service-card-content">
                        <h3>Hery — Vidéaste</h3>
                        <p>Chef opérateur et monteur, Hery transforme les idées en récits visuels puissants.</p>
                    </div>
                </div>
                <div class="service-card">
                    <div class="team-photo">
                        <img src="https://images.pexels.com/photos/614810/pexels-photo-614810.jpeg?auto=compress&cs=tinysrgb&h=900&w=1200" alt="Membre de l'équipe">
                    </div>
                    <div class="service-card-content">
                        <h3>Fara — Assistant</h3>
                        <p>Coordination terrain et logistique pour chaque production.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MATERIEL PROFESSIONNEL -->
    <section id="materiel" class="equipment">
        <div class="container">
            <h2 class="section-title">Matériel professionnel</h2>
            <div class="service-detail-row">
                <div class="service-detail-image-box">
                    <img src="https://images.pexels.com/photos/274973/pexels-photo-274973.jpeg?auto=compress&cs=tinysrgb&h=900&w=1200" alt="Matériel professionnel">
                </div>
                <div class="service-detail-content">
                    <p>Nous utilisons du matériel professionnel : appareils hybrides et reflex, objectifs lumineux, stabilisateurs, drones et éclairage pro pour garantir une qualité d'image optimale.</p>
                    <ul class="about-list">
                        <li><i class="fas fa-check-circle"></i> Appareils plein format</li>
                        <li><i class="fas fa-check-circle"></i> Drones pour prises aériennes</li>
                        <li><i class="fas fa-check-circle"></i> Éclairage LED professionnel</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CERTIFICATIONS -->
    <section id="certifications" class="certifications">
        <div class="container">
            <h2 class="section-title">Certifications</h2>
            <div class="services-grid certifications-grid">
                <div class="service-card certification-card">
                    <span class="certification-badge">1</span>
                    <h3>Certification qualité</h3>
                    <p>Engagement sur la qualité et les bonnes pratiques professionnelles.</p>
                </div>
                <div class="service-card certification-card">
                    <span class="certification-badge">2</span>
                    <h3>Formation continue</h3>
                    <p>Mise à jour régulière des compétences techniques et artistiques.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- PARTENAIRES DÉTAILLÉS -->
    <?php if (!empty($partenaires)): ?>
    <section id="partenaires-details" class="partenaires-details" style="padding: 60px 0; background-color: #f9f9f9;">
        <div class="container">
            <h2 class="section-title">Nos <span>Partenaires</span></h2>
            <p style="text-align: center; max-width: 800px; margin: 0 auto 40px auto; color: var(--text-body);">Nous collaborons avec des acteurs de confiance pour vous offrir les meilleurs services.</p>
            
            <div class="services-grid" style="gap: 30px;">
                <?php foreach ($partenaires as $partenaire): ?>
                    <div class="service-card" style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 30px;">
                        <div class="partenaire-logo" style="height: 100px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                            <img src="<?= htmlspecialchars($partenaire['PATH_LOGO']) ?>" alt="Logo partenaire" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                        </div>
                        <div class="service-card-content" style="flex: 1; display: flex; flex-direction: column;">
                            <p style="margin-bottom: 20px; font-size: 0.95rem; color: #555;"><?= nl2br(htmlspecialchars($partenaire['DESCRIPTIONS'])) ?></p>
                            <?php if (!empty($partenaire['LIEN_PARTENAIRE'])): ?>
                                <a href="<?= htmlspecialchars($partenaire['LIEN_PARTENAIRE']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="margin-top: auto; align-self: center;">
                                    Visiter le site <i class="fas fa-external-link-alt" style="font-size: 0.8rem; margin-left: 5px;"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
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

</body>

</html>