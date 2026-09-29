<?php
/**
 * Mercury Admin - Get Single User API
 * 
 * Method: GET
 * URL: /admin1/api/user/get.php?id=1
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensureUsersSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$id    = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$email = trim($_GET['email'] ?? '');

if (!$id && $email === '') {
    sendJsonError("User ID or Email is required.", 400);
}

try {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM blog_users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("SELECT * FROM blog_users WHERE LOWER(email) = LOWER(?) LIMIT 1");
        $stmt->execute([$email]);
    }

    $user = $stmt->fetch();

    if (!$user) {
        $defaults = getDefaultUsersList();
        foreach ($defaults as $d) {
            if (($id && $d['id'] === $id) || ($email !== '' && strtolower($d['email']) === strtolower($email))) {
                $user = $d;
                break;
            }
        }
    }

    if (!$user) {
        sendJsonError("User not found.", 404);
    }

    sendJsonSuccess(formatUserRecord($user), "User retrieved successfully.");

} catch (\PDOException $e) {
    sendJsonError("Database error while retrieving user: " . $e->getMessage(), 500);
}
