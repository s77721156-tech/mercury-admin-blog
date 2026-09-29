<?php
/**
 * Mercury Admin - Delete Blog Post API
 * 
 * Deletes a blog post by ID and cleans up associated cover image if desired.
 * 
 * Method: DELETE or POST
 * URL: /admin1/api/blog/delete.php?id=123
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

// Get ID from query param or request body
$id = $_GET['id'] ?? null;
if (!$id) {
    $body = getApiRequestBody();
    $id = $body['id'] ?? null;
}
$id = (int)$id;

if ($id <= 0) {
    sendJsonError("A valid post ID is required for deletion.", 400);
}

try {
    // Check if post exists
    $checkStmt = $db->prepare("SELECT id, title, cover_image FROM posts WHERE id = ? LIMIT 1");
    $checkStmt->execute([$id]);
    $post = $checkStmt->fetch();

    if (!$post) {
        sendJsonError("Blog post not found with ID {$id}.", 404);
    }

    // Delete post record
    $delStmt = $db->prepare("DELETE FROM posts WHERE id = ?");
    $delStmt->execute([$id]);

    sendJsonSuccess([
        'deleted_id'    => $id,
        'deleted_title' => $post['title'],
    ], "Blog post deleted successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error while deleting post: " . $e->getMessage(), 500);
}
