<?php
/**
 * Mercury Admin - Update Category API
 * 
 * Updates an existing blog category.
 * If category name is updated, optionally cascades the update to posts with this category.
 * 
 * Method: POST or PUT
 * URL: /admin1/api/category/update.php?id=1
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
    sendJsonError("Category ID is required for updating.", 400);
}

try {
    // 1. Verify existence
    $checkStmt = $db->prepare("SELECT * FROM blog_categories WHERE id = ? LIMIT 1");
    $checkStmt->execute([$id]);
    $existing = $checkStmt->fetch();

    if (!$existing) {
        sendJsonError("Category not found with ID {$id}.", 404);
    }

    $oldName = $existing['name'];

    // 2. Prepare updated values
    $name = isset($input['name']) ? trim($input['name']) : $existing['name'];
    if ($name === '') {
        sendJsonError("Category name cannot be empty.", 422);
    }

    $slug = isset($input['slug']) && trim($input['slug']) !== '' 
        ? generateCategorySlug($input['slug']) 
        : ($name !== $oldName ? generateCategorySlug($name) : $existing['slug']);

    $description = isset($input['description']) ? trim($input['description']) : ($existing['description'] ?? '');

    // 3. Check for name collision with other categories
    $nameDup = $db->prepare("SELECT id FROM blog_categories WHERE LOWER(name) = LOWER(?) AND id != ? LIMIT 1");
    $nameDup->execute([$name, $id]);
    if ($nameDup->fetch()) {
        sendJsonError("Another category already exists with the name '{$name}'.", 409);
    }

    // 4. Check for slug collision
    $slugDup = $db->prepare("SELECT id FROM blog_categories WHERE slug = ? AND id != ? LIMIT 1");
    $slugDup->execute([$slug, $id]);
    if ($slugDup->fetch()) {
        $slug = $slug . '-' . time();
    }

    // 5. Update category record
    $updateStmt = $db->prepare("UPDATE blog_categories SET name = ?, slug = ?, description = ? WHERE id = ?");
    $updateStmt->execute([$name, $slug, $description, $id]);

    // 6. If name changed, synchronize existing posts
    if ($name !== $oldName) {
        try {
            $syncPosts = $db->prepare("UPDATE posts SET category = ? WHERE category = ?");
            $syncPosts->execute([$name, $oldName]);
        } catch (\Throwable $e) {
            error_log("Failed to sync posts category: " . $e->getMessage());
        }
    }

    // 7. Return updated category with post count
    $fetchStmt = $db->prepare("SELECT c.*, 
                               (SELECT COUNT(*) FROM posts WHERE posts.category = c.name) AS post_count 
                               FROM blog_categories c WHERE c.id = ?");
    $fetchStmt->execute([$id]);
    $updated = $fetchStmt->fetch();

    sendJsonSuccess(formatCategory($updated), "Category updated successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error while updating category: " . $e->getMessage(), 500);
}
