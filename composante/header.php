<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
    <!-- HEADER / MENU DE NAVIGATION -->
    <header id="header">
        <div class="container header-container">
            <a href="index.php" class="logo-box">
                <!-- IMPORTANT: logo.png will be the image referenced as "image_1.png" -->
                <img src="" alt="NY TIA SARY Logo" class="logo-img">
            </a>

            <!-- menu emburguer-->
            <div class="menu-toggle" id="mobile-menu" aria-label="Ouvrir le menu" aria-expanded="false">
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
            </div>

            <!-- menu nav-->
            <ul class="nav-menu" id="nav-list">
                <li class="nav-item"><a href="index.php" class="nav-link <?php echo ($current_page == 'index.php' || $current_page == '') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Accueil</a></li>
                <li class="nav-item"><a href="apropos.php" class="nav-link <?php echo ($current_page == 'apropos.php') ? 'active' : ''; ?>"><i class="fas fa-info-circle"></i> À Propos</a></li>
                <li class="nav-item"><a href="service.php" class="nav-link <?php echo ($current_page == 'service.php') ? 'active' : ''; ?>"><i class="fas fa-camera-retro"></i> Services</a></li>
                <li class="nav-item"><a href="portfolio.php" class="nav-link <?php echo ($current_page == 'portfolio.php') ? 'active' : ''; ?>"><i class="fas fa-images"></i> Portfolio</a></li>
                <li class="nav-item"><a href="#contact" class="nav-link"><i class="fas fa-envelope"></i> Contact</a></li>
                <li class="nav-item"><a href="login/login.php" class="btn btn-outline btn-sm"><i
                            class="far fa-calendar-check"></i>&nbsp; Réserver</a></li>
            </ul>
        </div>
    </header>