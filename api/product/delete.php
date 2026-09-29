<?php
/**
 * Mercury Admin - Delete Product API
 * 
 * Deletes a product by ID.
 * 
 * Method: DELETE or POST
 * URL: /admin1/api/product/delete.php?id=1
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
    sendJsonError("Product ID is required for deletion.", 400);
}

try {
    // Check if product exists first
    $checkStmt = $db->prepare("SELECT id, name FROM products WHERE id = ? LIMIT 1");
    $checkStmt->execute([$id]);
    $existing = $checkStmt->fetch();

    if (!$existing) {
        sendJsonError("Product not found with ID {$id}.", 404);
    }

    $delStmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $delStmt->execute([$id]);

    sendJsonSuccess([
        'deleted_id'   => $id,
        'deleted_name' => $existing['name']
    ], "Product '{$existing['name']}' deleted successfully.");

} catch (\PDOException $e) {
    sendJsonError("Database error while deleting product: " . $e->getMessage(), 500);
}
