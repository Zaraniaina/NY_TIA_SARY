<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireClient();
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
       <?php include __DIR__ . '/composante/tolbar.php'; ?>

        <div class="dashboard-content">
            <div class="dash-page-header">
                <h2>Notifications</h2>
            </div>

            <div class="notifications-shell">
                <?php
                $notifications = [];
                try {
                    if ($clientId > 0) {
                        $stmt = $pdo->prepare("
                            SELECT ID_NOTIF, TYPE_NOTIF, ID_REF_NOTIF, TITRE_NOTIF, MESS_NOTIF, LU_NOTIF, DATE_NOTIF
                            FROM notification
                            WHERE ID_CLIENT = ? AND SUP_NOTIF = 0
                            ORDER BY DATE_NOTIF DESC, ID_NOTIF DESC
                        ");
                        $stmt->execute([$clientId]);
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($rows as $row) {
                            $targetPage = '#';
                            switch ($row['TYPE_NOTIF']) {
                                case 'client_contrat':
                                    $targetPage = 'contrats.php';
                                    break;
                                case 'client_devis':
                                    $targetPage = 'devis.php';
                                    break;
                                case 'client_facture':
                                    $targetPage = 'factures.php';
                                    break;
                                case 'client_paiement':
                                    $targetPage = 'paiements.php';
                                    break;
                            }

                            $notifications[] = [
                                'id_notif' => (int) $row['ID_NOTIF'],
                                'id_ref'   => (int) $row['ID_REF_NOTIF'],
                                'type'     => $row['TYPE_NOTIF'],
                                'title'    => $row['TITRE_NOTIF'],
                                'time'     => date('d/m/Y', strtotime($row['DATE_NOTIF'])),
                                'message'  => $row['MESS_NOTIF'],
                                'unread'   => ((int) $row['LU_NOTIF']) === 0,
                                'targetPage' => $targetPage
                            ];
                        }
                    }
                } catch (PDOException $e) {
                    $notifications = [];
                }
                ?>
                <div class="notifications-toolbar">
                    <div class="notifications-filters" role="tablist" aria-label="Filtres notifications">
                        <button class="notif-filter active" data-filter="all" type="button">Tous</button>
                        <button class="notif-filter" data-filter="unread" type="button">Non lues (<?= 
                            $unreadCount = 0;
                            foreach ($notifications as $n) {
                                if ($n['unread']) $unreadCount++;
                            }
                            echo $unreadCount;
                        ?>)</button>
                    </div>
                    <div class="notifications-summary">
                        <span class="summary-pill" id="notifCountPill"><i class="fas fa-bell"></i> <span id="notifCount"><?= count($notifications) ?></span> notification<?= count($notifications) > 1 ? 's' : '' ?></span>
                    </div>
                </div>

                <div class="notifications-list">
                    <?php
                    foreach ($notifications as $notification):
                        $cardClass = $notification['unread'] ? 'notification-card unread' : 'notification-card';
                    ?>
                        <article class="<?= htmlspecialchars($cardClass) ?>" data-filter="<?= $notification['unread'] ? 'unread' : 'read' ?>">
                            <div class="notification-card-top">
                                <div class="notification-title-wrap">
                                    <h3><?= htmlspecialchars($notification['title']) ?></h3>
                                </div>
                                <?php if ($notification['unread']): ?>
                                    <span class="notification-dot" aria-hidden="true"></span>
                                <?php endif; ?>
                            </div>
                            <div class="notification-body">
                                <p class="notification-message"><?= htmlspecialchars($notification['message']) ?></p>
                                <div class="notification-footer">
                                    <a href="<?= $notification['targetPage'] ?>?id=<?= $notification['id_ref'] ?>&mark_notif=<?= $notification['id_notif'] ?>"
                                       class="notification-view-btn" style="display:inline-block;text-decoration:none;">Voir</a>
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