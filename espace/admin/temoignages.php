<?php
declare(strict_types=1);
require_once __DIR__ . '/../../util/auth_guard.php';
requireAdmin();

require_once __DIR__.'/composante/tolbarDto.php';
$titre = "Témoignages clients";
$success = $error = '';

// ── Génération token CSRF ─────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ── Suppression d'un témoignage ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    // Vérification CSRF
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide.';
    } else {
        $idTemo = (int) ($_POST['id_temoignage'] ?? 0);
        if ($idTemo) {
            try {
                $stmt = $pdo->prepare('DELETE FROM TEMOIGNAGE WHERE ID_TEMOIGNAGE = ?');
                $stmt->execute([$idTemo]);
                if ($stmt->rowCount() > 0) {
                    $success = 'Témoignage supprimé avec succès.';
                } else {
                    $error = 'Témoignage introuvable.';
                }
            } catch (PDOException $e) {
                $error = 'Erreur lors de la suppression.';
            }
        }
    }
}

// ── Filtres & Recherche ───────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$filterNote = isset($_GET['note']) && $_GET['note'] !== '' ? (int)$_GET['note'] : null;

// ── Pagination ────────────────────────────────────────────────
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

// ── Construction requête ──────────────────────────────────────
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(c.NOM_CLIENT LIKE :search OR c.PRENOM_CLIENT LIKE :search OR t.MESS_RESERVATION LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($filterNote !== null) {
    $where[] = "t.NOTE = :note";
    $params[':note'] = $filterNote;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Compte total
$countSql = "SELECT COUNT(*) FROM TEMOIGNAGE t
             JOIN RESERVATION r ON t.ID_RESERVATION = r.ID_RESERVATION
             JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
             $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalItems / $perPage));
$page = min($page, $totalPages);

// Liste paginée
$sql = "SELECT t.*, r.DATE_RESERVATION, p.LIB_PRESTATION, c.NOM_CLIENT, c.PRENOM_CLIENT, c.PHOTO_CLIENT
        FROM TEMOIGNAGE t
        JOIN RESERVATION r ON t.ID_RESERVATION = r.ID_RESERVATION
        JOIN PRESTATIONS p ON r.ID_PRESTATION = p.ID_PRESTATION
        JOIN CLIENT c ON r.ID_CLIENT = c.ID_CLIENT
        $whereClause
        ORDER BY t.ID_TEMOIGNAGE DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$temoignages = $stmt->fetchAll();

// ── Stats globales (toujours sur tous les témoignages) ────────
$allNotes = $pdo->query('SELECT NOTE FROM TEMOIGNAGE')->fetchAll(PDO::FETCH_COLUMN);
$totalAvis = count($allNotes);
$notesMoy = $totalAvis > 0 ? round(array_sum($allNotes) / $totalAvis, 1) : null;

// Répartition par étoiles
$repartition = array_count_values($allNotes);
for ($i = 1; $i <= 5; $i++) {
    $repartition[$i] = $repartition[$i] ?? 0;
}
ksort($repartition);

// Pourcentages
$percentages = [];
foreach ($repartition as $note => $count) {
    $percentages[$note] = $totalAvis > 0 ? round(($count / $totalAvis) * 100, 1) : 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Témoignages clients | Admin NY TIA SARY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        :root {
            --primary: #377d49;
            --primary-dark: #2a5c3a;
            --danger: #d93d3d;
            --danger-dark: #a82c2c;
            --bg: #f5f6fa;
            --card-bg: #ffffff;
            --text: #2d3436;
            --text-muted: #888;
            --border: #e8e8e8;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 8px 24px rgba(0,0,0,0.08);
            --shadow-lg: 0 16px 40px rgba(0,0,0,0.12);
            --radius: 14px;
            --radius-sm: 10px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { box-sizing: border-box; }

        /* ── Animations ─────────────────────────────────────── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .animate-in {
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }

        /* ── Stats Cards ────────────────────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .dash-stat-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: var(--transition);
        }
        .dash-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .stat-icon.green {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            color: var(--primary);
        }
        .stat-icon.gold {
            background: linear-gradient(135deg, #fff8e1, #ffecb3);
            color: #f5a623;
        }
        .stat-icon.blue {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #1976d2;
        }
        .stat-info { flex: 1; }
        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text);
            line-height: 1.2;
            font-family: 'Montserrat', sans-serif;
        }
        .stat-value .unit {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-left: 2px;
        }
        .stat-label {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 2px;
            font-weight: 500;
        }

        /* ── Répartition par étoiles ─────────────────────────── */
        .rating-breakdown {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 20px 22px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }
        .rating-breakdown h3 {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .rating-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .rating-row:last-child { margin-bottom: 0; }
        .rating-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            min-width: 40px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .rating-bar-bg {
            flex: 1;
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .rating-bar-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #f5c518, #f5a623);
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .rating-count {
            font-size: 0.78rem;
            color: var(--text-muted);
            min-width: 30px;
            text-align: right;
            font-weight: 600;
        }

        /* ── Filtres ───────────────────────────────────────── */
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
            align-items: center;
        }
        .search-box {
            position: relative;
            flex: 1;
            min-width: 240px;
            max-width: 400px;
        }
        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            font-family: 'Open Sans', sans-serif;
            transition: var(--transition);
            background: var(--card-bg);
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(55, 125, 73, 0.1);
        }
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .filter-select {
            padding: 12px 36px 12px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            font-family: 'Open Sans', sans-serif;
            background: var(--card-bg);
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23888' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            transition: var(--transition);
        }
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(55, 125, 73, 0.1);
        }
        .btn-filter-reset {
            padding: 12px 20px;
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--card-bg);
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Open Sans', sans-serif;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-filter-reset:hover {
            border-color: var(--danger);
            color: var(--danger);
            background: #fff5f5;
        }

        /* ── Témoignage Card ───────────────────────────────── */
        .temo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 18px;
        }
        .temo-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }
        .temo-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            opacity: 0;
            transition: var(--transition);
        }
        .temo-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(55, 125, 73, 0.2);
        }
        .temo-card:hover::before {
            opacity: 1;
        }
        .temo-header {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .temo-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f0f0f0;
            flex-shrink: 0;
            transition: var(--transition);
        }
        .temo-card:hover .temo-avatar {
            border-color: var(--primary);
            transform: scale(1.05);
        }
        .temo-author {
            flex: 1;
            min-width: 0;
        }
        .temo-author-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .temo-meta {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .temo-meta i {
            margin-right: 4px;
            font-size: 0.7rem;
        }
        .star-bar {
            display: flex;
            gap: 3px;
            align-items: center;
            flex-shrink: 0;
        }
        .star-full { color: #f5c518; font-size: 0.85rem; }
        .star-empty { color: #e0e0e0; font-size: 0.85rem; }
        .temo-msg {
            font-size: 0.92rem;
            color: var(--text);
            line-height: 1.7;
            font-style: italic;
            position: relative;
            padding-left: 16px;
            border-left: 3px solid var(--primary);
            margin: 0;
            max-height: 80px;
            overflow: hidden;
            transition: max-height 0.4s ease;
        }
        .temo-msg.expanded {
            max-height: 500px;
        }
        .temo-msg::before {
            content: '"';
            position: absolute;
            left: -2px;
            top: -8px;
            font-size: 2.5rem;
            color: var(--primary);
            opacity: 0.15;
            font-family: Georgia, serif;
            line-height: 1;
        }
        .read-more {
            font-size: 0.8rem;
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: var(--transition);
        }
        .read-more:hover {
            color: var(--primary-dark);
            gap: 8px;
        }
        .temo-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }
        .temo-date {
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-delete {
            padding: 8px 14px;
            border: none;
            border-radius: 8px;
            background: #fff5f5;
            color: var(--danger);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Open Sans', sans-serif;
        }
        .btn-delete:hover {
            background: var(--danger);
            color: white;
            transform: scale(1.05);
        }

        /* ── État vide ──────────────────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            animation: scaleIn 0.5s ease;
        }
        .empty-state-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #f5f6fa, #e8e8e8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #c0c0c0;
            animation: pulse 3s ease infinite;
        }
        .empty-state h3 {
            font-size: 1.2rem;
            color: var(--text);
            margin-bottom: 8px;
            font-weight: 700;
        }
        .empty-state p {
            font-size: 0.9rem;
            color: var(--text-muted);
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* ── Pagination ─────────────────────────────────────── */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin-top: 32px;
            flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            min-width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            font-family: 'Open Sans', sans-serif;
        }
        .pagination a {
            background: var(--card-bg);
            color: var(--text);
            border: 1px solid var(--border);
        }
        .pagination a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        .pagination .current {
            background: var(--primary);
            color: white;
            border: 1px solid var(--primary);
            box-shadow: 0 4px 12px rgba(55, 125, 73, 0.3);
        }
        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
            background: #f9f9f9;
            border: 1px solid var(--border);
        }

        /* ── Résultats info ─────────────────────────────────── */
        .results-info {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 16px;
            font-weight: 500;
        }
        .results-info strong {
            color: var(--text);
        }

        /* ── Modal de confirmation ──────────────────────────── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }
        .modal-box {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 32px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: var(--shadow-lg);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        .modal-overlay.active .modal-box {
            transform: scale(1);
        }
        .modal-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            background: #fff5f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--danger);
        }
        .modal-box h3 {
            font-size: 1.2rem;
            color: var(--text);
            margin-bottom: 8px;
            font-weight: 700;
        }
        .modal-box p {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 24px;
            line-height: 1.6;
        }
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .btn-modal {
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-family: 'Open Sans', sans-serif;
        }
        .btn-modal-cancel {
            background: #f5f6fa;
            color: var(--text);
        }
        .btn-modal-cancel:hover {
            background: #e8e8e8;
        }
        .btn-modal-confirm {
            background: linear-gradient(135deg, var(--danger), var(--danger-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(217, 61, 61, 0.3);
        }
        .btn-modal-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(217, 61, 61, 0.4);
        }

        /* ── Responsive ─────────────────────────────────────── */
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .temo-grid { grid-template-columns: 1fr; }
            .filters-bar { flex-direction: column; align-items: stretch; }
            .search-box { max-width: none; }
            .filter-select { width: 100%; }
            .temo-header { flex-wrap: wrap; }
            .star-bar { width: 100%; justify-content: flex-start; margin-top: 4px; }
        }
    </style>

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
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
                <span>Témoignages clients</span>
            </nav>

            <!-- ── Stats rapides ───────────────────────────────── -->
            <div class="stats-grid">
                <div class="dash-stat-card animate-in delay-1">
                    <div class="stat-icon green"><i class="fas fa-star"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalAvis ?></div>
                        <div class="stat-label">Avis reçus</div>
                    </div>
                </div>
                <div class="dash-stat-card animate-in delay-2">
                    <div class="stat-icon gold"><i class="fas fa-trophy"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">
                            <?= $notesMoy !== null ? $notesMoy : '—' ?>
                            <?php if ($notesMoy !== null): ?>
                                <span class="unit">/5</span>
                            <?php endif; ?>
                        </div>
                        <div class="stat-label">Note moyenne</div>
                    </div>
                </div>
                <div class="dash-stat-card animate-in delay-3">
                    <div class="stat-icon blue"><i class="fas fa-chart-pie"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalPages ?></div>
                        <div class="stat-label">Pages totales</div>
                    </div>
                </div>
            </div>

            <!-- ── Répartition par étoiles ─────────────────────── -->
            <?php if ($totalAvis > 0): ?>
            <div class="rating-breakdown animate-in delay-2" style="margin-bottom:28px;">
                <h3><i class="fas fa-chart-bar" style="color:var(--primary);"></i> Répartition des notes</h3>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <div class="rating-row">
                    <div class="rating-label">
                        <?= $i ?> <i class="fas fa-star" style="font-size:0.7rem;color:#f5c518;"></i>
                    </div>
                    <div class="rating-bar-bg">
                        <div class="rating-bar-fill" style="width:<?= $percentages[$i] ?>%;"></div>
                    </div>
                    <div class="rating-count"><?= $repartition[$i] ?></div>
                </div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <!-- ── Filtres ─────────────────────────────────────── -->
            <form method="GET" class="filters-bar animate-in delay-3">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Rechercher un client, une prestation..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <select name="note" class="filter-select">
                    <option value="">Toutes les notes</option>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?= $i ?>" <?= $filterNote === $i ? 'selected' : '' ?>>
                            <?= $i ?> étoile<?= $i > 1 ? 's' : '' ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-filter-reset" style="border-color:var(--primary);color:var(--primary);">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
                <?php if ($search !== '' || $filterNote !== null): ?>
                    <a href="?" class="btn-filter-reset">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                <?php endif; ?>
            </form>

            <!-- ── Résultats info ──────────────────────────────── -->
            <?php if ($totalItems > 0): ?>
                <div class="results-info">
                    Affichage de <strong><?= count($temoignages) ?></strong> sur <strong><?= $totalItems ?></strong> témoignage<?= $totalItems > 1 ? 's' : '' ?>
                    <?= $search !== '' ? ' correspondant à "' . htmlspecialchars($search) . '"' : '' ?>
                </div>
            <?php endif; ?>

            <!-- ── LISTE DES TÉMOIGNAGES ───────────────────────── -->
            <?php if (empty($temoignages)): ?>
                <div class="dash-card animate-in"><div class="dash-card-body">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>Aucun témoignage trouvé</h3>
                        <p>
                            <?php if ($search !== '' || $filterNote !== null): ?>
                                Aucun résultat ne correspond à vos critères de recherche. Essayez d'autres filtres.
                            <?php else: ?>
                                Les avis de vos clients apparaîtront ici une fois qu'ils auront laissé un témoignage.
                            <?php endif; ?>
                        </p>
                    </div>
                </div></div>
            <?php else: ?>
                <div class="temo-grid">
                <?php foreach ($temoignages as $index => $t): ?>
                    <div class="temo-card animate-in delay-<?= ($index % 4) + 1 ?>">
                        <div class="temo-header">
                            <img src="../../<?= htmlspecialchars($t['PHOTO_CLIENT']) ?>"
                                 alt="Photo de <?= htmlspecialchars($t['PRENOM_CLIENT']) ?>"
                                 class="temo-avatar"
                                 loading="lazy"
                                 onerror="this.src='../../assets/images/avatar.png';this.onerror=null;">
                            <div class="temo-author">
                                <div class="temo-author-name"><?= htmlspecialchars($t['PRENOM_CLIENT'].' '.$t['NOM_CLIENT']) ?></div>
                                <div class="temo-meta">
                                    <i class="fas fa-camera"></i><?= htmlspecialchars($t['LIB_PRESTATION']) ?>
                                </div>
                            </div>
                            <div class="star-bar" title="Note : <?= (int)$t['NOTE'] ?>/5">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?= $i <= (int)$t['NOTE'] ? 'star-full' : 'star-empty' ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <blockquote class="temo-msg" id="msg-<?= $t['ID_TEMOIGNAGE'] ?>">
                            <?= htmlspecialchars($t['MESS_RESERVATION']) ?>
                        </blockquote>
                        <?php if (mb_strlen($t['MESS_RESERVATION']) > 120): ?>
                            <span class="read-more" onclick="toggleReadMore(<?= $t['ID_TEMOIGNAGE'] ?>, this)">
                                Lire la suite <i class="fas fa-chevron-down"></i>
                            </span>
                        <?php endif; ?>
                        <div class="temo-footer">
                            <span class="temo-date">
                                <i class="far fa-calendar-alt"></i>
                                <?= date('d/m/Y', strtotime($t['DATE_RESERVATION'])) ?>
                            </span>
                            <button type="button" class="btn-delete" onclick="confirmDelete(<?= (int)$t['ID_TEMOIGNAGE'] ?>)">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>

                <!-- ── Pagination ─────────────────────────────── -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination animate-in">
                    <?php
                    $queryParams = [];
                    if ($search !== '') $queryParams['search'] = $search;
                    if ($filterNote !== null) $queryParams['note'] = $filterNote;
                    $baseUrl = '?' . http_build_query($queryParams);
                    $sep = empty($queryParams) ? '' : '&';
                    
                    // Previous
                    if ($page > 1): ?>
                        <a href="<?= $baseUrl . $sep ?>page=<?= $page - 1 ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif;
                    
                    // Pages
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    if ($start > 1) { echo '<a href="' . $baseUrl . $sep . 'page=1">1</a>'; if ($start > 2) echo '<span>...</span>'; }
                    for ($i = $start; $i <= $end; $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= $baseUrl . $sep ?>page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor;
                    if ($end < $totalPages) { if ($end < $totalPages - 1) echo '<span>...</span>'; echo '<a href="' . $baseUrl . $sep . 'page=' . $totalPages . '">' . $totalPages . '</a>'; }
                    
                    // Next
                    if ($page < $totalPages): ?>
                        <a href="<?= $baseUrl . $sep ?>page=<?= $page + 1 ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Modal de confirmation ─────────────────────────────────── -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h3>Confirmer la suppression</h3>
        <p>Êtes-vous sûr de vouloir supprimer ce témoignage ? Cette action est irréversible.</p>
        <div class="modal-actions">
            <button class="btn-modal btn-modal-cancel" onclick="closeModal()">Annuler</button>
            <form method="POST" id="deleteForm" style="margin:0;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_temoignage" id="deleteId">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="submit" class="btn-modal btn-modal-confirm">Supprimer</button>
            </form>
        </div>
    </div>
</div>

<script>
// ── Sidebar Toggle ────────────────────────────────────────────
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => { 
    sidebar.classList.toggle('open'); 
    overlay.classList.toggle('open'); 
});
overlay?.addEventListener('click', () => { 
    sidebar.classList.remove('open'); 
    overlay.classList.remove('open'); 
});

// ── Lire la suite ───────────────────────────────────────────
function toggleReadMore(id, btn) {
    const msg = document.getElementById('msg-' + id);
    const isExpanded = msg.classList.contains('expanded');
    msg.classList.toggle('expanded');
    btn.innerHTML = isExpanded 
        ? 'Lire la suite <i class="fas fa-chevron-down"></i>' 
        : 'Réduire <i class="fas fa-chevron-up"></i>';
}

// ── Modal de suppression ────────────────────────────────────
function confirmDelete(id) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('deleteModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Fermer au clic sur l'overlay
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Fermer avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>

<!-- ── Toastify ──────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
window.addEventListener('DOMContentLoaded', () => {
    const errorMsg = <?= json_encode($error, JSON_UNESCAPED_UNICODE) ?>;
    const successMsg = <?= json_encode($success, JSON_UNESCAPED_UNICODE) ?>;
    
    if (errorMsg) {
        Toastify({
            text: errorMsg,
            duration: 6000,
            gravity: "top",
            position: "right",
            close: true,
            style: {
                background: "linear-gradient(135deg, #d93d3d, #a82c2c)",
                borderRadius: "10px",
                fontFamily: "'Open Sans', sans-serif",
                fontWeight: "600",
                boxShadow: "0 10px 30px rgba(0,0,0,0.2)",
                padding: "14px 20px"
            }
        }).showToast();
    }
    
    if (successMsg) {
        Toastify({
            text: successMsg,
            duration: 5000,
            gravity: "top",
            position: "right",
            close: true,
            style: {
                background: "linear-gradient(135deg, #377d49, #2a5c3a)",
                borderRadius: "10px",
                fontFamily: "'Open Sans', sans-serif",
                fontWeight: "600",
                boxShadow: "0 10px 30px rgba(0,0,0,0.2)",
                padding: "14px 20px"
            }
        }).showToast();
    }
});
</script>

</body>
</html>