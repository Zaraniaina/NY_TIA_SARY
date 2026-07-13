<?php
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();
require_once __DIR__.'/composante/tolbarDto.php';
$titre="Calendrier";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
</head>
<body>

<div class="dashboard-wrapper">
    
     <?php
      //menu lateral
      include __DIR__ . '/composante/sidebar.php'; 
      ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="dashboard-main">
        <?php include __DIR__ . '/composante/tolbar.php'; ?>
        <div class="dashboard-content">
            <nav class="dash-breadcrumb">
                <a href="home.php">Dashboard</a>
                <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Calendrier</span>
            </nav>
        </div>
    </div>
</div>
    
</body>
</html>