<?php
/**
 * Mercury Admin - Create Product API
 * 
 * Creates a new product record.
 * 
 * Method: POST (JSON or form-data)
 * URL: /admin1/api/product/create.php
 */

require_once __DIR__ . '/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError("Method not allowed. Use POST to create a product.", 405);
}

try {
    $db = getDbConnection();
    ensureProductsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$input = getApiRequestBody();

$name = trim($input['name'] ?? '');
if ($name === '') {
    sendJsonError("Product name is required.", 422);
}

$category    = trim($input['category'] ?? 'Business Solutions');
if ($category === '') $category = 'Business Solutions';

$status      = trim($input['status'] ?? 'Active');
if (!in_array($status, ['Active', 'Inactive'], true)) {
    $status = 'Active';
}

$blogs       = isset($input['blogs']) ? max(0, (int)$input['blogs']) : 0;
$description = trim($input['description'] ?? '');

try {
    // Check if a product with the same name already exists
    $nameCheck = $db->prepare("SELECT id, name FROM products WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $nameCheck->execute([$name]);
    if ($nameCheck->fetch()) {
        sendJsonError("A product with the name '{$name}' already exists.", 409);
    }

    // Insert new product
    $stmt = $db->prepare("INSERT INTO products (name, category, status, blogs, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $category, $status, $blogs, $description]);
    $newId = (int)$db->lastInsertId();

    // Fetch newly created record
    $fetchStmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $fetchStmt->execute([$newId]);
    $created = $fetchStmt->fetch();

    sendJsonSuccess(formatProduct($created), "Product created successfully.", 201);

} catch (\PDOException $e) {
    sendJsonError("Database error while creating product: " . $e->getMessage(), 500);
}
