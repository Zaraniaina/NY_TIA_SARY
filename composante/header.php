<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);

// Retrieve the database connection if not already present in the context
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getPDO();
}

// Fetch admin email and store in session if not set
if (!isset($_SESSION['email_admin_contact'])) {
    try {
        $stmt = $pdo->prepare("SELECT EMAIL_AUTH FROM authentification WHERE ROLE_AUTH = 'ADMIN' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin && !empty($admin['EMAIL_AUTH'])) {
            $_SESSION['email_admin_contact'] = $admin['EMAIL_AUTH'];
        }
    } catch (Exception $e) {
        // Handle gracefully
    }
}
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
                <li class="nav-item"><a href="blog.php" class="nav-link <?php echo ($current_page == 'blog.php') ? 'active' : ''; ?>"><i class="fas fa-newspaper"></i> Blog</a></li>
                <li class="nav-item"><a href="contact.php" class="nav-link <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>"><i class="fas fa-phone"></i> Contact</a></li>
                <li class="nav-item"><a href="login/login.php" class="btn-login"><i
                            class="fas fa-sign-in-alt"></i> Se connecter</a></li>
            </ul>
        </div>
    </header>