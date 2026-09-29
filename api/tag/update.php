<?php
/**
 * Mercury Admin - Update Tag API
 * 
 * Updates an existing blog tag.
 * 
 * Method: POST or PUT
 * URL: /admin1/api/tag/update.php?id=1
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensureTagsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$input = getApiRequestBody();
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : (int)($input['id'] ?? 0);

if (!$id) {
    sendJsonError("Tag ID is required for updating.", 400);
}

try {
    // 1. Verify existence
    $checkStmt = $db->prepare("SELECT * FROM blog_tags WHERE id = ? LIMIT 1");
    $checkStmt->execute([$id]);
    $existing = $checkStmt->fetch();

    if (!$existing) {
        sendJsonError("Tag not found with ID {$id}.", 404);
    }

    $oldName = $existing['name'];

    // 2. Prepare updated values
    $name = isset($input['name']) ? trim($input['name']) : $existing['name'];
    $name = ltrim($name, '#');
    $name = trim($name);

    if ($name === '') {
        sendJsonError("Tag name cannot be empty.", 422);
    }

    $slug = isset($input['slug']) && trim($input['slug']) !== '' 
        ? generateTagSlug($input['slug']) 
        : ($name !== $oldName ? generateTagSlug($name) : $existing['slug']);

    // 3. Check for name collision
    $nameDup = $db->prepare("SELECT id FROM blog_tags WHERE LOWER(name) = LOWER(?) AND id != ? LIMIT 1");
    $nameDup->execute([$name, $id]);
    if ($nameDup->fetch()) {
        sendJsonError("Another tag already exists with the name '{$name}'.", 409);
    }

    // 4. Check for slug collision
    $slugDup = $db->prepare("SELECT id FROM blog_tags WHERE slug = ? AND id != ? LIMIT 1");
    $slugDup->execute([$slug, $id]);
    if ($slugDup->fetch()) {
        $slug = $slug . '-' . time();
    }

    // 5. Update tag record
    $updateStmt = $db->prepare("UPDATE blog_tags SET name = ?, slug = ? WHERE id = ?");
    $updateStmt->execute([$name, $slug, $id]);

    // 6. Return updated record
    $fetchStmt = $db->prepare("SELECT t.*, 
                               (SELECT COUNT(*) FROM posts WHERE posts.tags LIKE CONCAT('%', t.name, '%')) AS post_count 
                               FROM blog_tags t WHERE t.id = ?");
    $fetchStmt->execute([$id]);
    $updated = $fetchStmt->fetch();

    sendJsonSuccess(formatTag($updated), "Tag updated successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error while updating tag: " . $e->getMessage(), 500);
}
