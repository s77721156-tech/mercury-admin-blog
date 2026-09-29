<?php
/**
 * Mercury Admin - Update Product API
 * 
 * Updates an existing product record.
 * 
 * Method: POST, PUT, PATCH
 * URL: /admin1/api/product/update.php?id=1
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensureProductsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$input = getApiRequestBody();
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : (int)($input['id'] ?? 0);

if (!$id) {
    sendJsonError("Product ID is required for updating.", 400);
}

try {
    // 1. Verify existence
    $checkStmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $checkStmt->execute([$id]);
    $existing = $checkStmt->fetch();

    if (!$existing) {
        sendJsonError("Product not found with ID {$id}.", 404);
    }

    // 2. Extract and validate fields
    $name = isset($input['name']) ? trim($input['name']) : $existing['name'];
    if ($name === '') {
        sendJsonError("Product name cannot be empty.", 422);
    }

    $category = isset($input['category']) && trim($input['category']) !== '' 
        ? trim($input['category']) 
        : $existing['category'];

    $status = isset($input['status']) && in_array(trim($input['status']), ['Active', 'Inactive'], true)
        ? trim($input['status'])
        : $existing['status'];

    $blogs = isset($input['blogs']) ? max(0, (int)$input['blogs']) : (int)($existing['blogs'] ?? 0);
    $description = isset($input['description']) ? trim($input['description']) : ($existing['description'] ?? '');

    // 3. Prevent duplicate name with another product
    $dupStmt = $db->prepare("SELECT id FROM products WHERE LOWER(name) = LOWER(?) AND id != ? LIMIT 1");
    $dupStmt->execute([$name, $id]);
    if ($dupStmt->fetch()) {
        sendJsonError("Another product already exists with the name '{$name}'.", 409);
    }

    // 4. Update product
    $updateStmt = $db->prepare("UPDATE products SET name = ?, category = ?, status = ?, blogs = ?, description = ? WHERE id = ?");
    $updateStmt->execute([$name, $category, $status, $blogs, $description, $id]);

    // 5. Fetch updated record
    $fetchStmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $fetchStmt->execute([$id]);
    $updated = $fetchStmt->fetch();

    sendJsonSuccess(formatProduct($updated), "Product updated successfully.");

} catch (\PDOException $e) {
    sendJsonError("Database error while updating product: " . $e->getMessage(), 500);
}
