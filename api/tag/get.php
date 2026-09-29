<?php
/**
 * Mercury Admin - Get Single Tag API
 * 
 * Retrieves details for a specific tag by ID or slug.
 * Also returns posts that include this tag.
 * 
 * Method: GET
 * URL: /admin1/api/tag/get.php?id=1
 *  or: /admin1/api/tag/get.php?slug=automation
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensureTagsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$id   = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$slug = trim($_GET['slug'] ?? '');

if (!$id && $slug === '') {
    sendJsonError("Please provide a tag 'id' or 'slug' to retrieve.", 400);
}

try {
    if ($id) {
        $stmt = $db->prepare("SELECT t.*, 
                              (SELECT COUNT(*) FROM posts WHERE posts.tags LIKE CONCAT('%', t.name, '%')) AS post_count
                              FROM blog_tags t WHERE t.id = ? LIMIT 1");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("SELECT t.*, 
                              (SELECT COUNT(*) FROM posts WHERE posts.tags LIKE CONCAT('%', t.name, '%')) AS post_count
                              FROM blog_tags t WHERE t.slug = ? LIMIT 1");
        $stmt->execute([$slug]);
    }

    $tag = $stmt->fetch();
    if (!$tag) {
        sendJsonError("Tag not found.", 404);
    }

    $formatted = formatTag($tag);

    // Also fetch recent posts tagged with this keyword
    $postsStmt = $db->prepare("SELECT id, title, slug, excerpt, category, status, published, views, created_at 
                              FROM posts WHERE tags LIKE ? ORDER BY created_at DESC LIMIT 10");
    $postsStmt->execute(["%{$tag['name']}%"]);
    $formatted['tagged_posts'] = $postsStmt->fetchAll();

    sendJsonSuccess($formatted, "Tag retrieved successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error while retrieving tag: " . $e->getMessage(), 500);
}
