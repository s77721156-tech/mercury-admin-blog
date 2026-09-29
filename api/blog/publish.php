<?php
/**
 * Mercury Admin - Publish Blog Post API
 * 
 * Sets a post's status to 'Published', updates the publication timestamp,
 * and sets published = 1.
 * 
 * Method: POST
 * URL: /admin1/api/blog/publish.php?id=123
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensurePostsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$id = $_GET['id'] ?? null;
if (!$id) {
    $body = getApiRequestBody();
    $id = $body['id'] ?? null;
}
$id = (int)$id;

if ($id <= 0) {
    sendJsonError("A valid post ID is required to publish.", 400);
}

try {
    $check = $db->prepare("SELECT id, title, published, status FROM posts WHERE id = ? LIMIT 1");
    $check->execute([$id]);
    $post = $check->fetch();

    if (!$post) {
        sendJsonError("Blog post not found with ID {$id}.", 404);
    }

    $now = date('Y-m-d H:i:s');
    $todayDate = date('Y-m-d');
    $todayTime = date('H:i');

    $sql = "UPDATE posts SET
        status = 'Published',
        published = 1,
        publish_date = CASE WHEN publish_date IS NULL OR publish_date = '' THEN :todayDate ELSE publish_date END,
        publish_time = CASE WHEN publish_time IS NULL OR publish_time = '' THEN :todayTime ELSE publish_time END,
        updated_at = :now
    WHERE id = :id";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':todayDate' => $todayDate,
        ':todayTime' => $todayTime,
        ':now'       => $now,
        ':id'        => $id,
    ]);

    sendJsonSuccess([
        'id'             => $id,
        'title'          => $post['title'],
        'status'         => 'Published',
        'published'      => 1,
        'published_at'   => $now,
    ], "Blog post published successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error while publishing post: " . $e->getMessage(), 500);
}
