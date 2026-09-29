<?php
/**
 * Mercury Admin - List Products API
 * 
 * Retrieves all products along with summary metrics (Total, Active, Inactive, Blog Usage).
 * Supports search, category filter, status filter, sorting, and pagination.
 * 
 * Method: GET
 * URL: /admin1/api/product/list.php
 * Examples:
 *   /admin1/api/product/list.php?search=WatsGo
 *   /admin1/api/product/list.php?category=SaaS
 *   /admin1/api/product/list.php?status=Active
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensureProductsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$status   = trim($_GET['status'] ?? '');
$sort     = trim($_GET['sort'] ?? 'name');
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : null;
$limit    = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : null;

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(name LIKE :s1 OR category LIKE :s2 OR description LIKE :s3)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
}

if ($category !== '' && $category !== 'All Categories') {
    $whereClauses[] = "category = :cat";
    $params[':cat'] = $category;
}

if ($status !== '' && $status !== 'All Status') {
    $whereClauses[] = "status = :stat";
    $params[':stat'] = $status;
}

$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

$orderBy = match ($sort) {
    'blogs'   => 'blogs DESC, name ASC',
    'status'  => 'status ASC, name ASC',
    'latest'  => 'id DESC',
    'id'      => 'id ASC',
    default   => 'name ASC'
};

try {
    // Total count for current filter
    $countSql = "SELECT COUNT(*) FROM products" . $whereSql;
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();

    // Query data
    $dataSql = "SELECT * FROM products" . $whereSql . " ORDER BY " . $orderBy;

    if ($page !== null && $limit !== null) {
        $offset = ($page - 1) * $limit;
        $dataSql .= " LIMIT {$limit} OFFSET {$offset}";
    }

    $stmt = $db->prepare($dataSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Baseline fallback if database is completely empty
    if (empty($rows) && empty($whereClauses)) {
        $rows = getDefaultProductsList();
        $totalRecords = count($rows);
    }

    $products = array_map('formatProduct', $rows);
    $metrics = getProductMetrics($db);

    $pagination = null;
    if ($page !== null && $limit !== null) {
        $pagination = [
            'current_page' => $page,
            'per_page'     => $limit,
            'total'        => $totalRecords,
            'total_pages'  => ceil($totalRecords / $limit) ?: 1,
        ];
    }

    // Build payload including summary card metrics
    http_response_code(200);
    $response = [
        'success'    => true,
        'message'    => "Products retrieved successfully.",
        'data'       => $products,
        'summary'    => [
            'total_products'    => $metrics['total'],
            'active_products'   => $metrics['active'],
            'inactive_products' => $metrics['inactive'],
            'blog_usage'        => $metrics['blog_usage']
        ]
    ];

    if ($pagination !== null) {
        $response['pagination'] = $pagination;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;

} catch (\PDOException $e) {
    sendJsonError("Database error while listing products: " . $e->getMessage(), 500);
}
