<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/composante/tolbarDto.php';
session_start();
requireAdmin();

$pdo = getPDO();

// Fetch blog stats with titles
$stmt = $pdo->prepare("SELECT b.ID_BLOG, b.TITRE_BLOG, COALESCE(bs.views,0) AS views, COALESCE(bs.likes,0) AS likes, COALESCE(bs.dislikes,0) AS dislikes
FROM blog b
LEFT JOIN blog_stats bs ON b.ID_BLOG = bs.id_blog
ORDER BY b.DATE_PUBLICATION DESC");
$stmt->execute();

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="blog_stats_' . date('Ymd') . '.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['ID_BLOG', 'TITRE_BLOG', 'VIEWS', 'LIKES', 'DISLIKES']);
foreach ($rows as $r) {
    fputcsv($out, [$r['ID_BLOG'], $r['TITRE_BLOG'], $r['views'], $r['likes'], $r['dislikes']]);
}
fclose($out);
exit;

?>
