<?php
/**
 * Mercury Admin - Create Category API
 * 
 * Creates a new blog category.
 * Automatically generates a URL slug if not provided.
 * 
 * Method: POST (JSON or multipart/form-data)
 * URL: /admin1/api/category/create.php
 */

require_once __DIR__ . '/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError("Method not allowed. Use POST to create a category.", 405);
}

try {
    $db = getDbConnection();
    ensureCategoriesSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$input = getApiRequestBody();

$name = trim($input['name'] ?? '');
if ($name === '') {
    sendJsonError("Category name is required.", 422);
}

$slug = trim($input['slug'] ?? '');
if ($slug === '') {
    $slug = generateCategorySlug($name);
} else {
    $slug = generateCategorySlug($slug);
}

$description = trim($input['description'] ?? '');

try {
    // 1. Check if category name already exists
    $nameCheck = $db->prepare("SELECT id, name FROM blog_categories WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $nameCheck->execute([$name]);
    if ($nameCheck->fetch()) {
        sendJsonError("A category with the name '{$name}' already exists.", 409);
    }

    // 2. Ensure slug uniqueness
    $slugCheck = $db->prepare("SELECT id FROM blog_categories WHERE slug = ? LIMIT 1");
    $slugCheck->execute([$slug]);
    if ($slugCheck->fetch()) {
        $slug = $slug . '-' . time();
    }

    // 3. Insert record
    $insertStmt = $db->prepare("INSERT INTO blog_categories (name, slug, description) VALUES (?, ?, ?)");
    $insertStmt->execute([$name, $slug, $description]);
    $newId = (int)$db->lastInsertId();

    // 4. Return new record
    $fetchStmt = $db->prepare("SELECT c.*, 0 AS post_count FROM blog_categories c WHERE c.id = ?");
    $fetchStmt->execute([$newId]);
    $created = $fetchStmt->fetch();

    sendJsonSuccess(formatCategory($created), "Category created successfully", 201);

} catch (\PDOException $e) {
    sendJsonError("Database error while creating category: " . $e->getMessage(), 500);
}
