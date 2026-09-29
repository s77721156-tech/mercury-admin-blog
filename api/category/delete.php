<?php
/**
 * Mercury Admin - Delete Category API
 * 
 * Deletes a category by ID.
 * Safely updates any posts assigned to this category to 'Uncategorized'.
 * 
 * Method: POST or DELETE
 * URL: /admin1/api/category/delete.php?id=1
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensureCategoriesSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$input = getApiRequestBody();
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : (int)($input['id'] ?? 0);

if (!$id) {
    sendJsonError("Category ID is required for deletion.", 400);
}

try {
    // 1. Verify existence
    $checkStmt = $db->prepare("SELECT * FROM blog_categories WHERE id = ? LIMIT 1");
    $checkStmt->execute([$id]);
    $cat = $checkStmt->fetch();

    if (!$cat) {
        sendJsonError("Category not found with ID {$id}.", 404);
    }

    $catName = $cat['name'];

    // 2. Count affected posts
    $postCountStmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE category = ?");
    $postCountStmt->execute([$catName]);
    $affectedPosts = (int)$postCountStmt->fetchColumn();

    // 3. Reassign posts to 'Uncategorized'
    if ($affectedPosts > 0) {
        try {
            $reassignStmt = $db->prepare("UPDATE posts SET category = 'Uncategorized' WHERE category = ?");
            $reassignStmt->execute([$catName]);
        } catch (\Throwable $e) {
            error_log("Failed to reassign posts upon category deletion: " . $e->getMessage());
        }
    }

    // 4. Delete category
    $delStmt = $db->prepare("DELETE FROM blog_categories WHERE id = ?");
    $delStmt->execute([$id]);

    sendJsonSuccess([
        'deleted_id'     => $id,
        'deleted_name'   => $catName,
        'reassigned_posts' => $affectedPosts
    ], "Category '{$catName}' deleted successfully.");

} catch (\PDOException $e) {
    sendJsonError("Database error while deleting category: " . $e->getMessage(), 500);
}
