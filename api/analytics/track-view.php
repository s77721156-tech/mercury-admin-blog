<?php
/**
 * Mercury Admin - Track Post View API
 * 
 * Atomically increments the view count for a specific blog post.
 * Optionally logs timestamp, IP, and User Agent to blog_views.
 * 
 * Method: POST (or GET)
 * URL: /admin1/api/analytics/track-view.php?id=10
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensureBlogViewsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$input = getApiRequestBody();
$id = isset($_GET['id']) && is_numeric($_GET['id']) 
    ? (int)$_GET['id'] 
    : (isset($input['id']) && is_numeric($input['id']) ? (int)$input['id'] : (int)($input['post_id'] ?? 0));

$slug = trim($_GET['slug'] ?? ($input['slug'] ?? ''));

if (!$id && $slug === '') {
    sendJsonError("Please provide post 'id' or 'slug' to track view.", 400);
}

try {
    // 1. Locate post
    if ($id) {
        $stmt = $db->prepare("SELECT id, title, views FROM posts WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("SELECT id, title, views FROM posts WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
    }

    $post = $stmt->fetch();
    if (!$post) {
        sendJsonError("Blog post not found.", 404);
    }

    $postId = (int)$post['id'];

    // 2. Increment view count
    $updStmt = $db->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
    $updStmt->execute([$postId]);

    // 3. Optional audit log to blog_views
    try {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $logStmt = $db->prepare("INSERT INTO blog_views (post_id, ip_address, user_agent) VALUES (?, ?, ?)");
        $logStmt->execute([$postId, $ip, $ua]);
    } catch (\Throwable $e) {}

    $newViews = (int)$post['views'] + 1;

    sendJsonSuccess([
        'post_id'   => $postId,
        'title'     => $post['title'],
        'views'     => $newViews,
    ], "View recorded successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error while recording view: " . $e->getMessage(), 500);
}
