<?php
require_once __DIR__.'/tolbarDto.php';
?>
<style>
    #notifDropdownPanel {
        display: none;
        position: fixed;
        width: 360px;
        max-height: 480px;
        overflow-y: auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
        z-index: 9999;
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
    .fb-notif-item:hover { background: #f2f2f2; }
    .fb-notif-item.unread { background: #e7f3ff; }
    .fb-notif-item.unread:hover { background: #dbeaff; }
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
    .fb-notif-text strong { font-weight: 700; }
    .fb-notif-message { display: block; color: #050505; margin-top: 1px; }
    .fb-notif-details { display: block; color: #444; font-size: 0.78rem; margin-top: 1px; }
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

<div class="dashboard-topbar">
            <div style="display:flex;align-items:center;gap:14px;">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <span class="topbar-title"><?php echo $titre; ?></span>
            </div>
            <div class="topbar-user" style="position:relative;">
                <button type="button" class="topbar-notification" id="notifBellBtn" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <?php if ($notifCount > 0): ?>
                        <span class="topbar-notification-count"><?= $notifCount ?></span>
                    <?php endif; ?>
                </button>

                <!-- Panel dropdown, caché par défaut -->
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
                                $icon = fb_notif_icon($notification['type']);
                                $badgeClass = fb_notif_badge_class($notification['type']);
                            ?>
                                <?php $targetUrl = fb_notif_target_url($notification['type'], $notification['id_ref']); ?>
                            <a href="notifications.php?mark_notif=<?= $notification['id_notif'] ?>&goto=<?= rawurlencode($targetUrl) ?>"
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
                                        <span><strong><?= htmlspecialchars($notification['title']) ?></strong></span>
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

                <div class="topbar-user-info">
                    <span class="topbar-user-name"><?= htmlspecialchars($clientNom." ".$clientPrenom) ?></span>
                </div>
                <img src="../../<?= htmlspecialchars($photoClient) ?>" alt="Avatar" class="topbar-avatar" style="object-fit: cover;">
            </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const bellBtn = document.getElementById('notifBellBtn');
    const panel = document.getElementById('notifDropdownPanel');
    if (!bellBtn || !panel) return;

    function positionNotifPanel() {
        const rect = bellBtn.getBoundingClientRect();
        const panelWidth = Math.min(360, window.innerWidth - 20);
        let left = rect.right - panelWidth;

        if (left < 10) left = 10;
        if (left + panelWidth > window.innerWidth - 10) {
            left = window.innerWidth - panelWidth - 10;
        }

        panel.style.top = (rect.bottom + 10) + 'px';
        panel.style.left = left + 'px';
        panel.style.right = 'auto';
        panel.style.width = panelWidth + 'px';
    }

    bellBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = panel.classList.contains('open');
        if (isOpen) {
            panel.classList.remove('open');
            bellBtn.setAttribute('aria-expanded', 'false');
        } else {
            panel.classList.add('open');
            bellBtn.setAttribute('aria-expanded', 'true');
            positionNotifPanel();
        }
    });

    document.addEventListener('click', function (e) {
        if (!panel.classList.contains('open')) return;
        if (panel.contains(e.target) || bellBtn.contains(e.target)) return;
        panel.classList.remove('open');
        bellBtn.setAttribute('aria-expanded', 'false');
    });

    const tabs = panel.querySelectorAll('.notif-tab');
    const items = panel.querySelectorAll('.fb-notif-item');
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

    window.addEventListener('resize', function () {
        if (panel.classList.contains('open')) positionNotifPanel();
    });
});
</script>