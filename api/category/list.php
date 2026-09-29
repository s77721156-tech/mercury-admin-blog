<?php
/**
 * Mercury Admin - List Categories API
 * 
 * Retrieves all blog categories along with live post counts.
 * Supports optional search filter and pagination.
 * 
 * Method: GET
 * URL: /admin1/api/category/list.php
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensureCategoriesSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$search = trim($_GET['search'] ?? '');
$sort   = trim($_GET['sort'] ?? 'name');
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : null;
$limit  = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : null;

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(c.name LIKE :s1 OR c.slug LIKE :s2 OR c.description LIKE :s3)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
}

$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

$orderBy = match ($sort) {
    'count'  => 'post_count DESC, c.name ASC',
    'latest' => 'c.created_at DESC',
    'id'     => 'c.id ASC',
    default  => 'c.name ASC'
};

try {
    // Total count
    $countSql = "SELECT COUNT(*) FROM blog_categories c" . $whereSql;
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();

    // Query data with correlated count from posts table
    $dataSql = "SELECT c.*, 
                (SELECT COUNT(*) FROM posts WHERE posts.category = c.name) AS post_count
                FROM blog_categories c" . $whereSql . " ORDER BY " . $orderBy;

    if ($page !== null && $limit !== null) {
        $offset = ($page - 1) * $limit;
        $dataSql .= " LIMIT {$limit} OFFSET {$offset}";
    }

    $stmt = $db->prepare($dataSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $categories = array_map('formatCategory', $rows);

    $pagination = null;
    if ($page !== null && $limit !== null) {
        $pagination = [
            'current_page' => $page,
            'per_page'     => $limit,
            'total'        => $totalRecords,
            'total_pages'  => ceil($totalRecords / $limit) ?: 1,
        ];
    }

    sendJsonSuccess($categories, "Categories retrieved successfully", 200, $pagination);

} catch (\PDOException $e) {
    sendJsonError("Database error while listing categories: " . $e->getMessage(), 500);
}
