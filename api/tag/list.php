<?php
/**
 * Mercury Admin - List Tags API
 * 
 * Retrieves all blog tags along with post usage counts.
 * Supports optional search filter and sorting.
 * 
 * Method: GET
 * URL: /admin1/api/tag/list.php
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensureTagsSchema($db);
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
    $whereClauses[] = "(t.name LIKE :s1 OR t.slug LIKE :s2)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
}

$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

$orderBy = match ($sort) {
    'count'  => 'post_count DESC, t.name ASC',
    'latest' => 't.created_at DESC',
    'id'     => 't.id ASC',
    default  => 't.name ASC'
};

try {
    // Total count
    $countSql = "SELECT COUNT(*) FROM blog_tags t" . $whereSql;
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();

    // Query data with post usage count from posts table
    $dataSql = "SELECT t.*, 
                (SELECT COUNT(*) FROM posts WHERE posts.tags LIKE CONCAT('%', t.name, '%')) AS post_count
                FROM blog_tags t" . $whereSql . " ORDER BY " . $orderBy;

    if ($page !== null && $limit !== null) {
        $offset = ($page - 1) * $limit;
        $dataSql .= " LIMIT {$limit} OFFSET {$offset}";
    }

    $stmt = $db->prepare($dataSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $tags = array_map('formatTag', $rows);

    $pagination = null;
    if ($page !== null && $limit !== null) {
        $pagination = [
            'current_page' => $page,
            'per_page'     => $limit,
            'total'        => $totalRecords,
            'total_pages'  => ceil($totalRecords / $limit) ?: 1,
        ];
    }

    sendJsonSuccess($tags, "Tags retrieved successfully", 200, $pagination);

} catch (\PDOException $e) {
    sendJsonError("Database error while listing tags: " . $e->getMessage(), 500);
}
