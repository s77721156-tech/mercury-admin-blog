<?php
/**
 * Mercury Admin - Get Single Category API
 * 
 * Retrieves details for a specific category by ID or slug.
 * Also returns posts associated with this category.
 * 
 * Method: GET
 * URL: /admin1/api/category/get.php?id=1
 *  or: /admin1/api/category/get.php?slug=ai-automation
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensureCategoriesSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$id   = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$slug = trim($_GET['slug'] ?? '');

if (!$id && $slug === '') {
    sendJsonError("Please provide a category 'id' or 'slug' to retrieve.", 400);
}

try {
    if ($id) {
        $stmt = $db->prepare("SELECT c.*, 
                              (SELECT COUNT(*) FROM posts WHERE posts.category = c.name) AS post_count
                              FROM blog_categories c WHERE c.id = ? LIMIT 1");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("SELECT c.*, 
                              (SELECT COUNT(*) FROM posts WHERE posts.category = c.name) AS post_count
                              FROM blog_categories c WHERE c.slug = ? LIMIT 1");
        $stmt->execute([$slug]);
    }

    $category = $stmt->fetch();
    if (!$category) {
        sendJsonError("Category not found.", 404);
    }

    $formatted = formatCategory($category);

    // Also fetch recent posts in this category
    $postsStmt = $db->prepare("SELECT id, title, slug, excerpt, status, published, views, created_at 
                              FROM posts WHERE category = ? ORDER BY created_at DESC LIMIT 10");
    $postsStmt->execute([$category['name']]);
    $formatted['recent_posts'] = $postsStmt->fetchAll();

    sendJsonSuccess($formatted, "Category retrieved successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error while retrieving category: " . $e->getMessage(), 500);
}
