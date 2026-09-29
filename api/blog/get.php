<?php
/**
 * Mercury Admin - Get Single Blog Post API
 * 
 * Retrieves complete blog post details by ID or Slug.
 * Also increments views count when requested with ?increment_view=true.
 * 
 * Method: GET
 * URL: /admin1/api/blog/get.php?id=123 OR ?slug=my-post-slug
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensurePostsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$id   = isset($_GET['id']) ? (int)$_GET['id'] : null;
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;

if (!$id && !$slug) {
    sendJsonError("Post ID or Slug parameter is required.", 400);
}

try {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM posts WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("SELECT * FROM posts WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
    }

    $post = $stmt->fetch();
    if (!$post) {
        sendJsonError("Blog post not found.", 404);
    }

    // Optional: increment view counter
    if (isset($_GET['increment_view']) && $_GET['increment_view'] === 'true') {
        $viewStmt = $db->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
        $viewStmt->execute([$post['id']]);
        $post['views'] = ((int)$post['views']) + 1;
    }

    // Decode JSON fields if stored as JSON
    if (!empty($post['faq_items'])) {
        $decoded = json_decode($post['faq_items'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $post['faq_items_parsed'] = $decoded;
        }
    }

    if (!empty($post['related_products'])) {
        $decoded = json_decode($post['related_products'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $post['related_products_parsed'] = $decoded;
        }
    }

    sendJsonSuccess($post, "Blog post retrieved successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error while retrieving post: " . $e->getMessage(), 500);
}
