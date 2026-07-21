<?php
declare(strict_types=1);
// api/blog_stats.php
// POST api/blog_stats.php?action=view|like|dislike|get&id=123
require_once __DIR__ . '/../config/database.php';
session_start();
header('Content-Type: application/json');

$pdo = getPDO();

// Ensure stats table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS blog_stats (
    id_blog BIGINT PRIMARY KEY,
    views INT NOT NULL DEFAULT 0,
    likes INT NOT NULL DEFAULT 0,
    dislikes INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Ensure reactions table exists (stores one reaction per client or per session)
$pdo->exec("CREATE TABLE IF NOT EXISTS reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_blog BIGINT NOT NULL,
    id_client BIGINT NULL,
    session_key VARCHAR(128) NULL,
    reaction ENUM('like','dislike') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (id_blog),
    INDEX (id_blog, id_client),
    INDEX (id_blog, session_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$action = $_POST['action'] ?? $_GET['action'] ?? 'get';
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

// current client (if logged in)
$clientId = $_SESSION['client_id'] ?? null;
$sessionKey = session_id() ?: bin2hex(random_bytes(8));

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'missing id']);
    exit;
}

// Block reactions from admin users (they should not vote)
if (!empty($_SESSION['admin_id']) && ($action === 'like' || $action === 'dislike')) {
    // return current stats without modifying
    $stmt = $pdo->prepare('SELECT COALESCE(views,0) AS views, COALESCE(likes,0) AS likes, COALESCE(dislikes,0) AS dislikes FROM blog_stats WHERE id_blog = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['views' => 0, 'likes' => 0, 'dislikes' => 0];
    http_response_code(403);
    echo json_encode(['error' => 'admins cannot react', 'id' => $id, 'stats' => $row]);
    exit;
}

try {
    if ($action === 'view') {
        // Count a view once per session per article
        if (empty($_SESSION['viewed_blogs']) || !in_array($id, $_SESSION['viewed_blogs'])) {
            $stmt = $pdo->prepare('INSERT INTO blog_stats (id_blog, views) VALUES (?,1) ON DUPLICATE KEY UPDATE views = views + 1');
            $stmt->execute([$id]);
            $_SESSION['viewed_blogs'][] = $id;
        }
    } elseif ($action === 'like' || $action === 'dislike') {
        // Handle reaction from logged-in client or fallback to session
        $reaction = $action === 'like' ? 'like' : 'dislike';

        // Find existing reaction for this user/session
        if ($clientId) {
            $stmt = $pdo->prepare('SELECT id, reaction FROM reactions WHERE id_blog = ? AND id_client = ? LIMIT 1');
            $stmt->execute([$id, $clientId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare('SELECT id, reaction FROM reactions WHERE id_blog = ? AND session_key = ? LIMIT 1');
            $stmt->execute([$id, $sessionKey]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$existing) {
            // insert reaction
            $stmt = $pdo->prepare('INSERT INTO reactions (id_blog, id_client, session_key, reaction) VALUES (?,?,?,?)');
            $stmt->execute([$id, $clientId, $clientId ? null : $sessionKey, $reaction]);
            // increment counter in blog_stats
            if ($reaction === 'like') {
                $pdo->prepare('INSERT INTO blog_stats (id_blog, likes) VALUES (?,1) ON DUPLICATE KEY UPDATE likes = likes + 1')->execute([$id]);
            } else {
                $pdo->prepare('INSERT INTO blog_stats (id_blog, dislikes) VALUES (?,1) ON DUPLICATE KEY UPDATE dislikes = dislikes + 1')->execute([$id]);
            }
        } else {
            // existing reaction found
            if ($existing['reaction'] === $reaction) {
                // same reaction clicked -> toggle off: remove reaction and decrement counter
                $stmt = $pdo->prepare('DELETE FROM reactions WHERE id = ?');
                $stmt->execute([$existing['id']]);
                if ($reaction === 'like') {
                    $pdo->prepare('UPDATE blog_stats SET likes = GREATEST(likes - 1, 0) WHERE id_blog = ?')->execute([$id]);
                } else {
                    $pdo->prepare('UPDATE blog_stats SET dislikes = GREATEST(dislikes - 1, 0) WHERE id_blog = ?')->execute([$id]);
                }
            } else {
                // change reaction
                $stmt = $pdo->prepare('UPDATE reactions SET reaction = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$reaction, $existing['id']]);
                // update blog_stats: increment new, decrement old
                if ($reaction === 'like') {
                    $pdo->prepare('UPDATE blog_stats SET likes = likes + 1, dislikes = GREATEST(dislikes - 1, 0) WHERE id_blog = ?')->execute([$id]);
                } else {
                    $pdo->prepare('UPDATE blog_stats SET dislikes = dislikes + 1, likes = GREATEST(likes - 1, 0) WHERE id_blog = ?')->execute([$id]);
                }
            }
        }
    }

    // Return current stats and user's reaction if any
    $stmt = $pdo->prepare('SELECT COALESCE(views,0) AS views, COALESCE(likes,0) AS likes, COALESCE(dislikes,0) AS dislikes FROM blog_stats WHERE id_blog = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { $row = ['views' => 0, 'likes' => 0, 'dislikes' => 0]; }

    // find user's reaction
    $userReaction = null;
    if ($clientId) {
        $stmt = $pdo->prepare('SELECT reaction FROM reactions WHERE id_blog = ? AND id_client = ? LIMIT 1');
        $stmt->execute([$id, $clientId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r) $userReaction = $r['reaction'];
    } else {
        $stmt = $pdo->prepare('SELECT reaction FROM reactions WHERE id_blog = ? AND session_key = ? LIMIT 1');
        $stmt->execute([$id, $sessionKey]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r) $userReaction = $r['reaction'];
    }

    echo json_encode(['id' => $id, 'stats' => $row, 'user_reaction' => $userReaction]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

?>
