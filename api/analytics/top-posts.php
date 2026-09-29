<?php
/**
 * Mercury Admin - Top Performing Blog Posts API
 * 
 * Returns top articles ordered by views with rank and metrics.
 * Supports filtering by category and published status.
 * 
 * Method: GET
 * URL: /admin1/api/analytics/top-posts.php?limit=10
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$limit    = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$category = trim($_GET['category'] ?? '');

$where = [];
$params = [];

if ($category !== '') {
    $where[] = "category = :category";
    $params[':category'] = $category;
}

if (isset($_GET['publishedOnly']) && $_GET['publishedOnly'] === 'true') {
    $where[] = "(published = 1 OR status = 'Published')";
}

$whereSql = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT id, title, slug, excerpt, category, author, status, published, views, created_at, updated_at
        FROM posts" . $whereSql . "
        ORDER BY views DESC, id DESC
        LIMIT :limit";

try {
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $posts = $stmt->fetchAll();

    // Add rank number to each post
    $ranked = array_map(function ($p, $idx) {
        $p['rank'] = $idx + 1;
        $p['views'] = (int)($p['views'] ?? 0);
        return $p;
    }, $posts, array_keys($posts));

    sendJsonSuccess($ranked, "Top performing posts retrieved successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error while retrieving top posts: " . $e->getMessage(), 500);
}
