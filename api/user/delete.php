<?php
/**
 * Mercury Admin - Delete User API
 * 
 * Method: DELETE or POST
 * URL: /admin1/api/user/delete.php?id=1
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensureUsersSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$input = getApiRequestBody();
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : (int)($input['id'] ?? 0);

if (!$id) {
    sendJsonError("User ID is required for deletion.", 400);
}

try {
    $check = $db->prepare("SELECT id, name FROM blog_users WHERE id = ? LIMIT 1");
    $check->execute([$id]);
    $existing = $check->fetch();

    if (!$existing) {
        sendJsonError("User not found with ID {$id}.", 404);
    }

    $del = $db->prepare("DELETE FROM blog_users WHERE id = ?");
    $del->execute([$id]);

    sendJsonSuccess([
        'deleted_id'   => $id,
        'deleted_name' => $existing['name']
    ], "User '{$existing['name']}' deleted successfully.");

} catch (\PDOException $e) {
    sendJsonError("Database error while deleting user: " . $e->getMessage(), 500);
}
