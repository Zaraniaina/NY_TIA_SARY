<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();
require_once __DIR__ . '/../../config/database.php';

require_once __DIR__.'/composante/tolbarDto.php';
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
        /* ===== Panel dropdown façon Facebook, caché par défaut ===== */
        #notifDropdownPanel {
            display: none;
            position: fixed;
            width: 360px;
            max-height: 480px;
            overflow-y: auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
            z-index: 999;
            padding: 6px;
        }
        #notifDropdownPanel.open {
            display: block;
        }

        .notif-panel-header {
            display: flex;
            gap: 6px;
            padding: 8px 8px 10px 8px;
            border-bottom: 1px solid #ececec;
            margin-bottom: 4px;
        }
        .notif-tab {
            border: none;
            background: #f0f2f5;
            color: #050505;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 7px 14px;
            border-radius: 999px;
            cursor: pointer;
        }
        .notif-tab.active {
            background: #e7f3ff;
            color: #1877f2;
        }

        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .fb-notif-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            text-decoration: none;
            color: inherit;
            position: relative;
            transition: background 0.15s ease;
        }
        .fb-notif-item:hover {
            background: #f2f2f2;
        }
        .fb-notif-item.unread {
            background: #e7f3ff;
        }
        .fb-notif-item.unread:hover {
            background: #dbeaff;
        }

        .fb-notif-avatar {
            position: relative;
            width: 42px;
            height: 42px;
            flex-shrink: 0;
        }
        .fb-notif-avatar-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e4e6eb;
            color: #65676b;
            font-size: 1rem;
        }
        .fb-notif-badge {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.62rem;
            border: 2px solid #fff;
        }
        .fb-notif-badge.type-devis { background: #f7b928; }
        .fb-notif-badge.type-reservation { background: #42b72a; }
        .fb-notif-badge.type-reaction { background: #e41e3f; }
        .fb-notif-badge.type-default { background: #1877f2; }

        .fb-notif-text {
            flex: 1;
            min-width: 0;
            font-size: 0.85rem;
            line-height: 1.3;
            color: #050505;
        }
        .fb-notif-text strong {
            font-weight: 700;
        }
        .fb-notif-message {
            display: block;
            color: #050505;
            margin-top: 1px;
        }
        .fb-notif-details {
            display: block;
            color: #444;
            font-size: 0.78rem;
            margin-top: 1px;
        }
        .fb-notif-time {
            display: block;
            color: #1877f2;
            font-weight: 700;
            font-size: 0.75rem;
            margin-top: 3px;
        }

        .fb-notif-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #1877f2;
            flex-shrink: 0;
            margin-top: 8px;
        }

        .fb-notif-empty {
            padding: 30px 12px;
            text-align: center;
            color: #65676b;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/composante/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-main">
       <?php include __DIR__ . '/composante/tolbar.php'; ?>

        <div class="dashboard-content">
            <div class="dash-page-header">
                <h2>Notifications</h2>
            </div>

            <?php
    $notifications = [];
    try {
        $stmt = $pdo->prepare("
            SELECT n.ID_NOTIF, n.TYPE_NOTIF, n.ID_REF_NOTIF, n.TITRE_NOTIF, n.MESS_NOTIF, n.LU_NOTIF, n.DATE_NOTIF,
                   d.NOM AS NOM_DEVIS, d.PRENOMS AS PRENOM_DEVIS,
                   c.NOM_CLIENT AS NOM_RESA, c.PRENOM_CLIENT AS PRENOM_RESA
            FROM notification n
            LEFT JOIN devis d ON n.TYPE_NOTIF = 'devis' AND n.ID_REF_NOTIF = d.ID
            LEFT JOIN RESERVATION r ON n.TYPE_NOTIF = 'Reservation' AND n.ID_REF_NOTIF = r.ID_RESERVATION
            LEFT JOIN CLIENT c ON c.ID_CLIENT = r.ID_CLIENT
            LEFT JOIN BLOG b ON n.TYPE_NOTIF = 'reaction' AND n.ID_REF_NOTIF = b.ID_BLOG
            WHERE n.SUP_NOTIF = 0 AND n.TYPE_NOTIF != 'blog'
            ORDER BY n.DATE_NOTIF DESC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $isResa = $row['TYPE_NOTIF'] === 'Reservation';

            $notifications[] = [
                'id_notif' => (int) $row['ID_NOTIF'],
                'id_ref'   => (int) $row['ID_REF_NOTIF'],
                'type'     => $row['TYPE_NOTIF'],
                'title'    => $row['TYPE_NOTIF'] === 'reaction' ? 'Nouvelle réaction' : $row['TITRE_NOTIF'],
                'blog_title' => $row['TYPE_NOTIF'] === 'reaction' ? ($row['TITRE_BLOG'] ?? 'Article') : null,
                'nom'      => $isResa ? ($row['NOM_RESA'] ?? '')    : ($row['NOM_DEVIS'] ?? ''),
                'prenom'   => $isResa ? ($row['PRENOM_RESA'] ?? '') : ($row['PRENOM_DEVIS'] ?? ''),
                'time'     => date('d/m/Y', strtotime($row['DATE_NOTIF'])),
                'message'  => $row['MESS_NOTIF'],
                'unread'   => ((int) $row['LU_NOTIF']) === 0,
            ];
        }
    } catch (PDOException $e) {
        $notifications = [];
    }

    // Icône + badge selon le type de notification
    function fb_notif_icon(string $type): string {
        return match ($type) {
            'devis' => 'fa-file-invoice',
            'Reservation' => 'fa-calendar-check',
            'reaction' => 'fa-heart',
            default => 'fa-bell',
        };
    }
    function fb_notif_badge_class(string $type): string {
        return match ($type) {
            'devis' => 'type-devis',
            'Reservation' => 'type-reservation',
            'reaction' => 'type-reaction',
            default => 'type-default',
        };
    }
    ?>

            <!-- Panel dropdown, caché par défaut, ouvert au clic sur la cloche -->
            <div id="notifDropdownPanel">
                <div class="notif-panel-header">
                    <button class="notif-tab active" data-filter="all" type="button">Tout</button>
                    <button class="notif-tab" data-filter="unread" type="button">Non lu</button>
                </div>

                <div class="notifications-list">
                    <?php if (empty($notifications)): ?>
                        <div class="fb-notif-empty">Aucune notification pour le moment</div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification):
                            $isUnread = $notification['unread'];
                            $itemClass = $isUnread ? 'fb-notif-item unread' : 'fb-notif-item';

                            if ($notification['type'] === 'Reservation') {
                                $targetPage = 'reservations.php';
                            } elseif ($notification['type'] === 'devis') {
                                $targetPage = 'devis.php';
                            } elseif ($notification['type'] === 'reaction') {
                                $targetPage = 'blog.php';
                            } else {
                                $targetPage = 'notifications.php';
                            }

                            $icon = fb_notif_icon($notification['type']);
                            $badgeClass = fb_notif_badge_class($notification['type']);
                        ?>
                            <a href="<?= $targetPage ?>?id=<?= $notification['id_ref'] ?>&mark_notif=<?= $notification['id_notif'] ?>"
                               class="<?= htmlspecialchars($itemClass) ?>"
                               data-filter="<?= $isUnread ? 'unread' : 'read' ?>">

                                <div class="fb-notif-avatar">
                                    <div class="fb-notif-avatar-circle">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <span class="fb-notif-badge <?= $badgeClass ?>">
                                        <i class="fas <?= $icon ?>"></i>
                                    </span>
                                </div>

                                <div class="fb-notif-text">
                                    <?php if ($notification['type'] === 'reaction'): ?>
                                        <span><strong><?= htmlspecialchars($notification['title']) ?></strong></span>
                                        <span class="fb-notif-details">Article : <?= htmlspecialchars($notification['blog_title'] ?? 'Article inconnu') ?></span>
                                    <?php else: ?>
                                        <span><strong><?= htmlspecialchars(trim($notification['prenom'].' '.$notification['nom'])) ?></strong> — <?= htmlspecialchars($notification['title']) ?></span>
                                    <?php endif; ?>

                                    <span class="fb-notif-message"><?= htmlspecialchars($notification['message']) ?></span>
                                    <span class="fb-notif-time"><?= htmlspecialchars($notification['time']) ?></span>
                                </div>

                                <?php if ($isUnread): ?>
                                    <span class="fb-notif-dot" aria-hidden="true"></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// ===== Onglets Tout / Non lu à l'intérieur du panel =====
const tabs = document.querySelectorAll('.notif-tab');
const items = document.querySelectorAll('.fb-notif-item');

tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
        tabs.forEach((t) => t.classList.remove('active'));
        tab.classList.add('active');

        const selected = tab.dataset.filter || 'all';
        items.forEach((item) => {
            const show = selected === 'all' || item.dataset.filter === 'unread';
            item.style.display = show ? 'flex' : 'none';
        });
    });
});

// ===== Positionne le panel directement sous la cloche =====
function positionNotifPanel() {
    const bell = document.getElementById('notifBellBtn');
    const panel = document.getElementById('notifDropdownPanel');
    if (!bell || !panel) return;

    const rect = bell.getBoundingClientRect();
    const panelWidth = panel.offsetWidth || 360;

    let left = rect.left + rect.width / 2 - panelWidth + 50;
    if (left < 8) left = 8;

    panel.style.top = (rect.bottom + 10) + 'px';
    panel.style.left = left + 'px';
}

window.addEventListener('resize', positionNotifPanel);
</script>
</body>
</html>