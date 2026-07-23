<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();
require_once __DIR__.'/composante/tolbarDto.php';
$titre = "Calendrier des réservations";

// ── Chargement des réservations ───────────────────────────────
$stmt = $pdo->query(
    "SELECT
        r.ID_RESERVATION,
        r.DATE_RESERVATION,
        r.HEURE_RESERVATION,
        r.LIEU_RESERVATION,
        r.COMME_RESERVATION,
        r.STATUS_RESERVATION,
        c.NOM_CLIENT,
        c.PRENOM_CLIENT,
        c.TEL_CLIENT,
        c.PHOTO_CLIENT,
        a.EMAIL_AUTH,
        p.LIB_PRESTATION,
        COALESCE(SUM(rc.PRIX), 0)                           AS TOTAL_PRIX,
        COALESCE(GROUP_CONCAT(cat.LIB_CATEGORIE SEPARATOR ', '), 'N/A') AS FORMULES
     FROM RESERVATION r
     JOIN CLIENT c ON c.ID_CLIENT = r.ID_CLIENT
     JOIN AUTHENTIFICATION a ON a.ID_AUTH = c.ID_AUTH
     JOIN PRESTATIONS p ON p.ID_PRESTATION = r.ID_PRESTATION
     LEFT JOIN RESERVATION_CATEGORIE rc ON rc.ID_RESERVATION = r.ID_RESERVATION
     LEFT JOIN CATEGORIE cat ON cat.ID_CATEGORIE = rc.ID_CATEGORIE
     GROUP BY r.ID_RESERVATION
     ORDER BY r.DATE_RESERVATION ASC, r.HEURE_RESERVATION ASC"
);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Conversion en format FullCalendar ─────────────────────────
$colorMap = [
    'EN ATTENTE' => ['bg' => '#f59e0b', 'border' => '#b45309'],
    'CONFIRMEE'  => ['bg' => '#377d49', 'border' => '#166534'],
    'ANNULEE'    => ['bg' => '#d93d3d', 'border' => '#991b1b'],
    'TERMINEE'   => ['bg' => '#3b82f6', 'border' => '#1d4ed8'],
];

$events = [];
foreach ($reservations as $r) {
    $statut = strtoupper($r['STATUS_RESERVATION']);
    $colors = $colorMap[$statut] ?? ['bg' => '#888', 'border' => '#555'];

    $events[] = [
        'id'              => (int) $r['ID_RESERVATION'],
        'title'           => $r['PRENOM_CLIENT'] . ' ' . $r['NOM_CLIENT'] . ' — ' . $r['LIB_PRESTATION'],
        'start'           => $r['DATE_RESERVATION'] . 'T' . $r['HEURE_RESERVATION'],
        'backgroundColor' => $colors['bg'],
        'borderColor'     => $colors['border'],
        'textColor'       => '#ffffff',
        'extendedProps'   => [
            'client_nom'    => $r['PRENOM_CLIENT'] . ' ' . $r['NOM_CLIENT'],
            'client_tel'    => $r['TEL_CLIENT'],
            'client_email'  => $r['EMAIL_AUTH'],
            'client_photo'  => '../../' . ($r['PHOTO_CLIENT'] ?: 'assets/images/avatar.png'),
            'prestation'    => $r['LIB_PRESTATION'],
            'formules'      => $r['FORMULES'],
            'tarif'         => (int) $r['TOTAL_PRIX'],
            'lieu'          => $r['LIEU_RESERVATION'],
            'heure'         => substr($r['HEURE_RESERVATION'], 0, 5),
            'statut'        => $r['STATUS_RESERVATION'],
            'commentaire'   => $r['COMME_RESERVATION'] ?: '—',
        ],
    ];
}
$eventsJson = json_encode($events, JSON_UNESCAPED_UNICODE);

// Stat du mois courant
$mois = date('Y-m');
$stmtMois = $pdo->prepare("SELECT COUNT(*) FROM RESERVATION WHERE DATE_FORMAT(DATE_RESERVATION,'%Y-%m') = ?");
$stmtMois->execute([$mois]);
$countMois = (int) $stmtMois->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier des Réservations | Admin NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <!-- FullCalendar CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/composante/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-main">
        <?php include __DIR__ . '/composante/tolbar.php'; ?>

        <div class="dashboard-content">
            <nav class="dash-breadcrumb">
                <a href="home.php">Dashboard</a>
                <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                <span>Calendrier</span>
            </nav>

            <!-- STATISTIQUE MOIS -->
            <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));margin-bottom:28px;">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-number"><?= $countMois ?></div>
                        <div class="stat-label">Réservations ce mois-ci</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-list-ol"></i></div>
                    <div class="stat-info">
                        <div class="stat-number"><?= count($reservations) ?></div>
                        <div class="stat-label">Réservations au total</div>
                    </div>
                </div>
                <!-- Légende couleurs -->
                <div class="stat-card" style="flex-direction:column;align-items:flex-start;gap:10px;">
                    <div style="font-family:var(--font-headings);font-weight:700;font-size:0.85rem;color:var(--logo-black);margin-bottom:4px;">Légende des statuts</div>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;">
                        <span class="cal-legend" style="background:#f59e0b;"><i class="fas fa-clock"></i> En attente</span>
                        <span class="cal-legend" style="background:#377d49;"><i class="fas fa-check"></i> Confirmée</span>
                        <span class="cal-legend" style="background:#d93d3d;"><i class="fas fa-times"></i> Annulée</span>
                        <span class="cal-legend" style="background:#3b82f6;"><i class="fas fa-flag-checkered"></i> Terminée</span>
                    </div>
                </div>
            </div>

            <!-- CALENDRIER -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3><i class="fas fa-calendar-alt" style="color:var(--primary-green);margin-right:8px;"></i> Calendrier des réservations</h3>
                    <a href="reservations.php" class="btn-dash btn-dash-outline btn-dash-sm">
                        <i class="fas fa-table"></i> Vue tableau
                    </a>
                </div>
                <div class="dash-card-body padded">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL DÉTAIL ══ -->
<div id="eventModal" class="cal-modal-overlay" style="display:none;">
    <div class="cal-modal">
        <button class="cal-modal-close" id="closeModal"><i class="fas fa-times"></i></button>

        <div class="cal-modal-header" id="modalHeader">
            <div class="cal-modal-avatar-wrapper">
                <img id="modalAvatar" src="" alt="Avatar" class="cal-modal-avatar">
                <div class="cal-modal-avatar-initials" id="modalInitials"></div>
            </div>
            <div>
                <h2 id="modalTitle" class="cal-modal-title"></h2>
                <span id="modalStatutBadge" class="badge"></span>
            </div>
        </div>

        <div class="cal-modal-body">
            <div class="cal-modal-section">
                <div class="cal-modal-section-title"><i class="fas fa-user"></i> Client</div>
                <div class="cal-modal-grid">
                    <div class="cal-modal-item">
                        <span class="cal-modal-label">Nom complet</span>
                        <span class="cal-modal-value" id="modalClient"></span>
                    </div>
                    <div class="cal-modal-item">
                        <span class="cal-modal-label">Téléphone</span>
                        <span class="cal-modal-value" id="modalTel"></span>
                    </div>
                    <div class="cal-modal-item" style="grid-column:1/-1;">
                        <span class="cal-modal-label">Email</span>
                        <span class="cal-modal-value" id="modalEmail"></span>
                    </div>
                </div>
            </div>

            <div class="cal-modal-section">
                <div class="cal-modal-section-title"><i class="fas fa-camera"></i> Réservation</div>
                <div class="cal-modal-grid">
                    <div class="cal-modal-item">
                        <span class="cal-modal-label">Prestation</span>
                        <span class="cal-modal-value" id="modalPrestation"></span>
                    </div>
                    <div class="cal-modal-item">
                        <span class="cal-modal-label">Formule(s)</span>
                        <span class="cal-modal-value" id="modalFormules"></span>
                    </div>
                    <div class="cal-modal-item">
                        <span class="cal-modal-label">Date</span>
                        <span class="cal-modal-value" id="modalDate"></span>
                    </div>
                    <div class="cal-modal-item">
                        <span class="cal-modal-label">Heure</span>
                        <span class="cal-modal-value" id="modalHeure"></span>
                    </div>
                    <div class="cal-modal-item" style="grid-column:1/-1;">
                        <span class="cal-modal-label">Lieu</span>
                        <span class="cal-modal-value" id="modalLieu"></span>
                    </div>
                    <div class="cal-modal-item" style="grid-column:1/-1;">
                        <span class="cal-modal-label">Commentaire</span>
                        <span class="cal-modal-value" id="modalCommentaire"></span>
                    </div>
                </div>
            </div>

            <div class="cal-modal-footer">
                <div class="cal-modal-tarif">
                    <i class="fas fa-tag"></i> Total estimatif : <strong id="modalTarif"></strong>
                </div>
                <a id="modalLink" href="reservations.php" class="btn-dash btn-dash-primary btn-dash-sm">
                    <i class="fas fa-external-link-alt"></i> Voir dans le tableau
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
// ── Sidebar toggle
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });

// ── Données injectées depuis PHP
const events = <?= $eventsJson ?>;

// ── Éléments du modal
const modal        = document.getElementById('eventModal');
const closeModal   = document.getElementById('closeModal');
const modalHeader  = document.getElementById('modalHeader');

const statutBadgeMap = {
    'EN ATTENTE': { cls: 'badge-waiting', label: 'En attente' },
    'CONFIRMEE':  { cls: 'badge-confirm', label: 'Confirmée'  },
    'ANNULEE':    { cls: 'badge-cancel',  label: 'Annulée'    },
    'TERMINEE':   { cls: 'badge-done',    label: 'Terminée'   },
};

const statutColorMap = {
    'EN ATTENTE': '#f59e0b',
    'CONFIRMEE':  '#377d49',
    'ANNULEE':    '#d93d3d',
    'TERMINEE':   '#3b82f6',
};

function openModal(info) {
    const p    = info.event.extendedProps;
    const date = new Date(info.event.start);

    // Header color
    const color = statutColorMap[p.statut] || '#377d49';
    modalHeader.style.borderLeft = `5px solid ${color}`;

    // Titre
    document.getElementById('modalTitle').textContent = p.client_nom;

    // Avatar client
    const avatar   = document.getElementById('modalAvatar');
    const initials = document.getElementById('modalInitials');
    if (p.client_photo) {
        avatar.src         = p.client_photo;
        avatar.style.display = 'block';
        initials.style.display = 'none';
        avatar.onerror = () => {
            avatar.style.display = 'none';
            const parts = p.client_nom.split(' ');
            initials.textContent = (parts[0]?.[0] || '') + (parts[1]?.[0] || '');
            initials.style.display = 'flex';
        };
    } else {
        avatar.style.display = 'none';
        const parts = p.client_nom.split(' ');
        initials.textContent = (parts[0]?.[0] || '') + (parts[1]?.[0] || '');
        initials.style.display = 'flex';
    }

    // Badge statut
    const badge = document.getElementById('modalStatutBadge');
    const bMap  = statutBadgeMap[p.statut] || { cls: 'badge-waiting', label: p.statut };
    badge.className = 'badge ' + bMap.cls;
    badge.textContent = bMap.label;

    // Infos client
    document.getElementById('modalClient').textContent     = p.client_nom;
    document.getElementById('modalTel').textContent        = p.client_tel;
    document.getElementById('modalEmail').textContent      = p.client_email;

    // Infos réservation
    document.getElementById('modalPrestation').textContent = p.prestation;
    document.getElementById('modalFormules').textContent   = p.formules;
    document.getElementById('modalDate').textContent       = date.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('modalHeure').textContent      = p.heure;
    document.getElementById('modalLieu').textContent       = p.lieu;
    document.getElementById('modalCommentaire').textContent = p.commentaire;

    // Tarif
    const tarif = parseInt(p.tarif) || 0;
    document.getElementById('modalTarif').textContent = tarif > 0 ? tarif.toLocaleString('fr-FR') + ' Ar' : 'À définir';

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

closeModal?.addEventListener('click', () => {
    modal.style.display = 'none';
    document.body.style.overflow = '';
});
modal?.addEventListener('click', e => { if (e.target === modal) { modal.style.display = 'none'; document.body.style.overflow = ''; } });

// ── Init FullCalendar
document.addEventListener('DOMContentLoaded', () => {
    const calEl = document.getElementById('calendar');
    const isMobile = window.innerWidth <= 768;
    const isTablet = window.innerWidth <= 1024 && window.innerWidth > 768;

    const cal = new FullCalendar.Calendar(calEl, {
        locale: 'fr',
        initialView: isMobile ? 'listWeek' : (isTablet ? 'timeGridWeek' : 'dayGridMonth'),
        headerToolbar: isMobile
            ? { left: 'prev,next', center: 'title', right: 'listWeek,dayGridMonth' }
            : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
        buttonText: {
            today:    "Aujourd'hui",
            month:    'Mois',
            week:     'Semaine',
            day:      'Jour',
            list:     'Liste',
        },
        events: events,
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        eventClick: openModal,
        dayMaxEvents: isMobile ? 2 : 3,
        height: 'auto',
        windowResize: function(view) {
            const w = window.innerWidth;
            if (w <= 768) {
                cal.changeView('listWeek');
                cal.setOption('headerToolbar', { left: 'prev,next', center: 'title', right: 'listWeek,dayGridMonth' });
                cal.setOption('dayMaxEvents', 2);
            } else if (w <= 1024) {
                cal.changeView('timeGridWeek');
                cal.setOption('headerToolbar', { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' });
                cal.setOption('dayMaxEvents', 3);
            } else {
                cal.changeView('dayGridMonth');
                cal.setOption('headerToolbar', { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' });
                cal.setOption('dayMaxEvents', 3);
            }
        },

        // ── Rendu personnalisé des événements
        eventContent: function(arg) {
            const p     = arg.event.extendedProps;
            const color = arg.event.backgroundColor;
            const heure = arg.timeText || p.heure;

            // Avatar : photo ou initiales
            let avatarHtml;
            if (p.client_photo) {
                avatarHtml = `<img src="${p.client_photo}" alt="" class="fc-event-avatar" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                              <span class="fc-event-initials" style="display:none;background:${color}">${(p.client_nom.split(' ')[0]?.[0]||'') + (p.client_nom.split(' ')[1]?.[0]||'')}</span>`;
            } else {
                const initials = (p.client_nom.split(' ')[0]?.[0]||'') + (p.client_nom.split(' ')[1]?.[0]||'');
                avatarHtml = `<span class="fc-event-initials" style="display:flex;background:${color}">${initials}</span>`;
            }

            const html = document.createElement('div');
            html.className = 'fc-event-custom';
            html.style.borderLeft = `3px solid ${color}`;
            html.innerHTML = `
                <div class="fc-event-avatar-wrap">${avatarHtml}</div>
                <div class="fc-event-info">
                    <span class="fc-event-name">${p.client_nom}</span>
                    ${heure ? `<span class="fc-event-time">${heure}</span>` : ''}
                </div>
            `;
            return { domNodes: [html] };
        },
    });
    cal.render();
});
</script>
</body>
</html>