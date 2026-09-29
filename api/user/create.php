<?php
/**
 * Mercury Admin - Create User API
 * 
 * Method: POST
 * URL: /admin1/api/user/create.php
 */

require_once __DIR__ . '/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError("Method not allowed. Use POST to create a user.", 405);
}

try {
    $db = getDbConnection();
    ensureUsersSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$input = getApiRequestBody();

$name  = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$role  = trim($input['role'] ?? 'Author');
$status = trim($input['status'] ?? 'Active');
$posts = isset($input['posts']) ? max(0, (int)$input['posts']) : 0;
$last_login = trim($input['last_login'] ?? 'Today');

if ($name === '') {
    sendJsonError("User name is required.", 422);
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonError("A valid email address is required.", 422);
}

try {
    $check = $db->prepare("SELECT id FROM blog_users WHERE LOWER(email) = LOWER(?) LIMIT 1");
    $check->execute([$email]);
    if ($check->fetch()) {
        sendJsonError("A user with this email address already exists.", 409);
    }

    $stmt = $db->prepare("INSERT INTO blog_users (name, email, role, status, posts, last_login) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $role, $status, $posts, $last_login]);
    $newId = (int)$db->lastInsertId();

    $fetch = $db->prepare("SELECT * FROM blog_users WHERE id = ?");
    $fetch->execute([$newId]);
    $created = $fetch->fetch();

    sendJsonSuccess(formatUserRecord($created), "User created successfully.", 201);

} catch (\PDOException $e) {
    sendJsonError("Database error while creating user: " . $e->getMessage(), 500);
}
