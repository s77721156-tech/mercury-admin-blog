<?php
/**
 * Mercury Admin - Get Single Product API
 * 
 * Retrieves details for a specific product by ID or name.
 * Also returns blog posts associated with this product.
 * 
 * Method: GET
 * URL: /admin1/api/product/get.php?id=1
 *  or: /admin1/api/product/get.php?name=WatsGo
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensureProductsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$id   = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$name = trim($_GET['name'] ?? '');

if (!$id && $name === '') {
    sendJsonError("Please provide a product 'id' or 'name' to retrieve.", 400);
}

try {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("SELECT * FROM products WHERE LOWER(name) = LOWER(?) LIMIT 1");
        $stmt->execute([$name]);
    }

    $product = $stmt->fetch();

    // Fallback search against defaults if database table had no row matching
    if (!$product) {
        $defaults = getDefaultProductsList();
        foreach ($defaults as $d) {
            if (($id && $d['id'] === $id) || ($name !== '' && strtolower($d['name']) === strtolower($name))) {
                $product = $d;
                break;
            }
        }
    }

    if (!$product) {
        sendJsonError("Product not found.", 404);
    }

    $formatted = formatProduct($product);

    // Fetch linked blog posts (matching by related_products or tags)
    $linkedPosts = [];
    try {
        $postsStmt = $db->prepare("SELECT id, title, slug, excerpt, category, status, published, views, created_at 
                                  FROM posts 
                                  WHERE related_products LIKE ? OR tags LIKE ? 
                                  ORDER BY created_at DESC LIMIT 10");
        $postsStmt->execute(["%{$product['name']}%", "%{$product['name']}%"]);
        $linkedPosts = $postsStmt->fetchAll();
    } catch (\Throwable $e) {
        // Soft fail if posts table is missing or doesn't have related_products column
        $linkedPosts = [];
    }

    $formatted['linked_posts'] = $linkedPosts;
    $formatted['post_count']   = count($linkedPosts) > 0 ? count($linkedPosts) : $formatted['blogs'];

    sendJsonSuccess($formatted, "Product retrieved successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error while retrieving product: " . $e->getMessage(), 500);
}
