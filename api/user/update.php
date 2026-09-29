<?php
/**
 * Mercury Admin - Update User API
 * 
 * Method: POST, PUT, PATCH
 * URL: /admin1/api/user/update.php?id=1
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
    sendJsonError("User ID is required for update.", 400);
}

try {
    $check = $db->prepare("SELECT * FROM blog_users WHERE id = ? LIMIT 1");
    $check->execute([$id]);
    $existing = $check->fetch();

    if (!$existing) {
        sendJsonError("User not found with ID {$id}.", 404);
    }

    $name = isset($input['name']) ? trim($input['name']) : $existing['name'];
    if ($name === '') {
        sendJsonError("Name cannot be empty.", 422);
    }

    $email = isset($input['email']) ? trim($input['email']) : $existing['email'];
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonError("A valid email address is required.", 422);
    }

    // Email duplicate check
    $dup = $db->prepare("SELECT id FROM blog_users WHERE LOWER(email) = LOWER(?) AND id != ? LIMIT 1");
    $dup->execute([$email, $id]);
    if ($dup->fetch()) {
        sendJsonError("Another user is already registered with this email.", 409);
    }

    $role = isset($input['role']) && trim($input['role']) !== '' ? trim($input['role']) : $existing['role'];
    $status = isset($input['status']) && in_array(trim($input['status']), ['Active', 'Inactive'], true)
        ? trim($input['status'])
        : $existing['status'];
    $posts = isset($input['posts']) ? max(0, (int)$input['posts']) : (int)$existing['posts'];
    $last_login = isset($input['last_login']) && trim($input['last_login']) !== '' 
        ? trim($input['last_login']) 
        : ($existing['last_login'] ?? 'Today');

    $update = $db->prepare("UPDATE blog_users SET name = ?, email = ?, role = ?, status = ?, posts = ?, last_login = ? WHERE id = ?");
    $update->execute([$name, $email, $role, $status, $posts, $last_login, $id]);

    $fetch = $db->prepare("SELECT * FROM blog_users WHERE id = ?");
    $fetch->execute([$id]);
    $updated = $fetch->fetch();

    sendJsonSuccess(formatUserRecord($updated), "User updated successfully.");

} catch (\PDOException $e) {
    sendJsonError("Database error while updating user: " . $e->getMessage(), 500);
}
