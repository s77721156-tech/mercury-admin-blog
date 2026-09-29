<?php
/**
 * Mercury Admin - Create Tag API
 * 
 * Creates a new blog tag.
 * Automatically handles hashtags (strips leading #) and auto-generates slug.
 * 
 * Method: POST (JSON or multipart/form-data)
 * URL: /admin1/api/tag/create.php
 */

require_once __DIR__ . '/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError("Method not allowed. Use POST to create a tag.", 405);
}

try {
    $db = getDbConnection();
    ensureTagsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$input = getApiRequestBody();

$name = trim($input['name'] ?? '');
// Clean up tag name if user prefixed with '#'
$name = ltrim($name, '#');
$name = trim($name);

if ($name === '') {
    sendJsonError("Tag name is required.", 422);
}

$slug = trim($input['slug'] ?? '');
if ($slug === '') {
    $slug = generateTagSlug($name);
} else {
    $slug = generateTagSlug($slug);
}

try {
    // 1. Check if tag name already exists
    $nameCheck = $db->prepare("SELECT id, name FROM blog_tags WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $nameCheck->execute([$name]);
    if ($nameCheck->fetch()) {
        sendJsonError("A tag with the name '{$name}' already exists.", 409);
    }

    // 2. Ensure slug uniqueness
    $slugCheck = $db->prepare("SELECT id FROM blog_tags WHERE slug = ? LIMIT 1");
    $slugCheck->execute([$slug]);
    if ($slugCheck->fetch()) {
        $slug = $slug . '-' . time();
    }

    // 3. Insert record
    $insertStmt = $db->prepare("INSERT INTO blog_tags (name, slug) VALUES (?, ?)");
    $insertStmt->execute([$name, $slug]);
    $newId = (int)$db->lastInsertId();

    // 4. Return new record
    $fetchStmt = $db->prepare("SELECT t.*, 0 AS post_count FROM blog_tags t WHERE t.id = ?");
    $fetchStmt->execute([$newId]);
    $created = $fetchStmt->fetch();

    sendJsonSuccess(formatTag($created), "Tag created successfully", 201);

} catch (\PDOException $e) {
    sendJsonError("Database error while creating tag: " . $e->getMessage(), 500);
}
