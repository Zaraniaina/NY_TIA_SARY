<?php
require_once __DIR__.'/tolbarDto.php';
?>
<div class="dashboard-topbar">
            <div style="display:flex;align-items:center;gap:14px;">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <span class="topbar-title"><?php echo $titre; ?></span>
            </div>
            <div class="topbar-user">
                <div class="topbar-user-info">
                    <span class="topbar-user-name"><?= htmlspecialchars($adminNom." ".$adminPrenom) ?></span>
                    <span class="topbar-user-role" style="color:var(--primary-red);">Administrateur</span>
                </div>
                <img src="../../<?= htmlspecialchars($photoAdmin) ?>" alt="Avatar" class="topbar-avatar" style="object-fit: cover;">
                
            </div>
</div>
