<?php
/**
 * Mercury Admin - List Users API
 * 
 * Retrieves all administrator & author profiles along with summary card metrics.
 * Supports search, role filtering, status filtering, and sorting.
 * 
 * Method: GET
 * URL: /admin1/api/user/list.php
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensureUsersSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$search = trim($_GET['search'] ?? '');
$role   = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? '');
$sort   = trim($_GET['sort'] ?? 'id');

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(name LIKE :s1 OR email LIKE :s2 OR role LIKE :s3)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
}

if ($role !== '' && $role !== 'All Roles') {
    $whereClauses[] = "role = :role";
    $params[':role'] = $role;
}

if ($status !== '' && $status !== 'All Status') {
    $whereClauses[] = "status = :stat";
    $params[':stat'] = $status;
}

$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

$orderBy = match ($sort) {
    'name'   => 'name ASC',
    'posts'  => 'posts DESC',
    'status' => 'status ASC',
    default  => 'id ASC'
};

try {
    $stmt = $db->prepare("SELECT * FROM blog_users" . $whereSql . " ORDER BY " . $orderBy);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    if (empty($rows) && empty($whereClauses)) {
        $rows = getDefaultUsersList();
    }

    $users = array_map('formatUserRecord', $rows);
    $metrics = getUserMetrics($db);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Users retrieved successfully.',
        'data'    => $users,
        'summary' => [
            'total_users'  => $metrics['total_users'],
            'active_users' => $metrics['active_users'],
            'admins'       => $metrics['admins'],
            'authors'      => $metrics['authors']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;

} catch (\PDOException $e) {
    sendJsonError("Database error while fetching users: " . $e->getMessage(), 500);
}
