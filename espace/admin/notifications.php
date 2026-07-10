<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();
require_once __DIR__ . '/../../config/database.php';

$adminEmail = $_SESSION['admin_email'] ?? 'Admin';
$adminId    = (int) ($_SESSION['admin_id'] ?? 0);
$pdo        = getPDO();

$stmtPhoto = $pdo->prepare('SELECT PHOTO_CLIENT FROM CLIENT WHERE ID_AUTH = ?');
$stmtPhoto->execute([$adminId]);
$photoAdmin = $stmtPhoto->fetchColumn() ?: 'assets/images/avatar.png';
$isDefaultPhoto = ($photoAdmin === 'assets/images/avatar.png');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .notifications-shell {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            margin-top: 8px;
        }
        .notifications-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .notifications-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .notif-filter {
            border: 1px solid #d8d8d8;
            background: #f4f4f2;
            color: #333;
            padding: 10px 16px;
            border-radius: 999px;
            font-weight: 700;
            cursor: pointer;
        }
        .notif-filter.active,
        .notif-filter:hover {
            background: #d93d3d;
            color: #fff;
            border-color: #d93d3d;
        }
        .summary-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(55, 125, 73, 0.12);
            color: #377d49;
            font-weight: 700;
        }
        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .notification-card {
            border: 1px solid #ececec;
            border-radius: 14px;
            padding: 16px 18px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f8f6 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }
        .notification-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .notification-title-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .notification-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #377d49;
            box-shadow: 0 0 0 4px rgba(55, 125, 73, 0.16);
        }
        .notification-card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #222;
            margin: 0;
        }
        .notification-card {
            position: relative;
        }
        .notification-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .notification-time-wrap {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            position: absolute;
            top: 16px;
            right: 16px;
        }
        .notification-time {
            font-size: 0.85rem;
            color: #8a8a8a;
            font-weight: 600;
        }
        .notification-client-details {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 6px;
            color: #444;
            font-size: 0.95rem;
        }
        .notification-message {
            color: #666;
            margin: 0;
            font-size: 0.95rem;
        }
        .notification-view-btn {
            align-self: flex-start;
            margin-top: 6px;
            border: none;
            border-radius: 999px;
            padding: 8px 14px;
            background: #d93d3d;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/composante/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div style="display:flex;align-items:center;gap:14px;">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <span class="topbar-title">Notifications</span>
            </div>
            <div class="topbar-user">
                <div class="topbar-user-info">
                    <span class="topbar-user-name"><?= htmlspecialchars($adminEmail) ?></span>
                    <span class="topbar-user-role" style="color:var(--primary-red);">Administrateur</span>
                </div>
                <?php if ($isDefaultPhoto): ?>
                    <div class="topbar-avatar admin-avatar"><i class="fas fa-shield-alt" style="font-size:.85rem;"></i></div>
                <?php else: ?>
                    <img src="../../<?= htmlspecialchars($photoAdmin) ?>" alt="Avatar" class="topbar-avatar" style="object-fit: cover;">
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="dash-page-header">
                <h2>Notifications</h2>
            </div>

            <div class="notifications-shell">
                <div class="notifications-toolbar">
                    <div class="notifications-filters" role="tablist" aria-label="Filtres notifications">
                        <button class="notif-filter active" data-filter="all" type="button">Tous</button>
                        <button class="notif-filter" data-filter="unread" type="button">Nouvelle notification</button>
                    </div>
                    <div class="notifications-summary">
                        <span class="summary-pill"><i class="fas fa-bell"></i> 3 notifications</span>
                    </div>
                </div>

                <div class="notifications-list">
                    <?php
                    $notifications = [
                        [
                            'title' => 'Devis notification',
                            'nom' => 'Rajaonera',
                            'prenom' => 'Mialy',
                            'time' => 'Il y a 4h',
                            'message' => 'Nouvelle notification de demande de devis de Mialy Rajaonera.',
                            'unread' => true,
                        ],
                        [
                            'title' => 'Reservation notification',
                            'nom' => 'Rakoto',
                            'prenom' => 'Jean',
                            'time' => 'Il y a 6h',
                            'message' => 'Nouvelle notification de réservation de Jean Rakoto.',
                            'unread' => true,
                        ],
                        [
                            'title' => 'Devis notification',
                            'nom' => 'Andriami',
                            'prenom' => 'Lina',
                            'time' => 'Il y a 1j',
                            'message' => 'Nouvelle notification de demande de devis de Lina Andriami.',
                            'unread' => false,
                        ],
                    ];
                    foreach ($notifications as $notification):
                        $cardClass = $notification['unread'] ? 'notification-card unread' : 'notification-card';
                    ?>
                        <article class="<?= htmlspecialchars($cardClass) ?>" data-filter="<?= $notification['unread'] ? 'unread' : 'read' ?>">
                            <div class="notification-card-top">
                                <div class="notification-title-wrap">
                                    <h3><?= htmlspecialchars($notification['title']) ?></h3>
                                </div>
                            </div>
                            <div class="notification-body">
                                <div class="notification-client-details">
                                    <span><strong>Nom :</strong> <?= htmlspecialchars($notification['nom']) ?></span>
                                    <span><strong>Prénom :</strong> <?= htmlspecialchars($notification['prenom']) ?></span>
                                </div>
                                <p class="notification-message"><?= htmlspecialchars($notification['message']) ?></p>
                                <div class="notification-footer">
                                    <button class="notification-view-btn" type="button">Voir</button>
                                    <span class="notification-time-wrap">
                                        <?php if ($notification['unread']): ?>
                                            <span class="notification-dot" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <span class="notification-time"><?= htmlspecialchars($notification['time']) ?></span>
                                    </span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const filters = document.querySelectorAll('.notif-filter');
const cards = document.querySelectorAll('.notification-card');

toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

filters.forEach((button) => {
    button.addEventListener('click', () => {
        filters.forEach((item) => item.classList.remove('active'));
        button.classList.add('active');

        const selected = button.dataset.filter || 'all';
        cards.forEach((card) => {
            const shouldShow = selected === 'all' || card.dataset.filter === 'unread';
            card.style.display = shouldShow ? 'block' : 'none';
        });
    });
});
</script>
</body>
</html>
