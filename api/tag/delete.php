<?php
/**
 * Mercury Admin - Delete Tag API
 * 
 * Deletes a tag by ID.
 * 
 * Method: POST or DELETE
 * URL: /admin1/api/tag/delete.php?id=1
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
    sendJsonError("Tag ID is required for deletion.", 400);
}

try {
    // 1. Verify existence
    $checkStmt = $db->prepare("SELECT * FROM blog_tags WHERE id = ? LIMIT 1");
    $checkStmt->execute([$id]);
    $tag = $checkStmt->fetch();

    if (!$tag) {
        sendJsonError("Tag not found with ID {$id}.", 404);
    }

    $tagName = $tag['name'];

    // 2. Count posts where this tag is used
    $postCountStmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE tags LIKE ?");
    $postCountStmt->execute(["%{$tagName}%"]);
    $taggedPostsCount = (int)$postCountStmt->fetchColumn();

    // 3. Delete tag from blog_tags
    $delStmt = $db->prepare("DELETE FROM blog_tags WHERE id = ?");
    $delStmt->execute([$id]);

    sendJsonSuccess([
        'deleted_id'          => $id,
        'deleted_name'        => $tagName,
        'formerly_used_posts' => $taggedPostsCount
    ], "Tag '#{$tagName}' deleted successfully.");

} catch (\PDOException $e) {
    sendJsonError("Database error while deleting tag: " . $e->getMessage(), 500);
}
