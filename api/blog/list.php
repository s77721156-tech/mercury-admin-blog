<?php
/**
 * Mercury Admin - List Blog Posts API
 * 
 * Retrieves blog posts with support for:
 * - Search by title, content, or excerpt
 * - Filter by category, status (Published/Draft/Scheduled), or author
 * - Sorting by latest, views, or title
 * - Pagination (page, limit)
 * 
 * Method: GET
 * URL: /admin1/api/blog/list.php
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensurePostsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

// Extract filter parameters
$search        = trim($_GET['search'] ?? '');
$category      = trim($_GET['category'] ?? '');
$status        = trim($_GET['status'] ?? '');
$author        = trim($_GET['author'] ?? '');
$publishedOnly = isset($_GET['publishedOnly']) && $_GET['publishedOnly'] === 'true';
$sort          = trim($_GET['sort'] ?? 'latest');
$page          = max(1, (int)($_GET['page'] ?? 1));
$limit         = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$offset        = ($page - 1) * $limit;

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(title LIKE :search1 OR content LIKE :search2 OR excerpt LIKE :search3 OR tags LIKE :search4)";
    $params[':search1'] = "%{$search}%";
    $params[':search2'] = "%{$search}%";
    $params[':search3'] = "%{$search}%";
    $params[':search4'] = "%{$search}%";
}

if ($category !== '') {
    $whereClauses[] = "category = :category";
    $params[':category'] = $category;
}

if ($author !== '') {
    $whereClauses[] = "author = :author";
    $params[':author'] = $author;
}

if ($status !== '') {
    if ($status === 'Published') {
        $whereClauses[] = "(status = 'Published' OR published = 1)";
    } elseif ($status === 'Draft') {
        $whereClauses[] = "(status = 'Draft' AND published = 0)";
    } elseif ($status === 'Scheduled') {
        $whereClauses[] = "status = 'Scheduled'";
    } else {
        $whereClauses[] = "status = :status";
        $params[':status'] = $status;
    }
} elseif ($publishedOnly) {
    $whereClauses[] = "published = 1";
}

$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

// Sorting
$orderBy = match ($sort) {
    'views'   => 'views DESC, created_at DESC',
    'title'   => 'title ASC',
    'oldest'  => 'created_at ASC',
    default   => 'created_at DESC, id DESC'
};

try {
    // 1. Total count query
    $countSql = "SELECT COUNT(*) FROM posts" . $whereSql;
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();

    // 2. Data query (integers limit and offset safely embedded directly)
    $safeLimit = (int)$limit;
    $safeOffset = (int)$offset;
    $dataSql = "SELECT id, title, slug, excerpt, cover_image, author, category, tags,
                       status, published, views, created_at, updated_at, publish_date
                FROM posts" . $whereSql . " ORDER BY " . $orderBy . " LIMIT {$safeLimit} OFFSET {$safeOffset}";

    $dataStmt = $db->prepare($dataSql);
    $dataStmt->execute($params);

    $posts = $dataStmt->fetchAll();

    $pagination = [
        'current_page' => $page,
        'per_page'     => $limit,
        'total'        => $totalRecords,
        'total_pages'  => ceil($totalRecords / $limit) ?: 1,
    ];

    sendJsonSuccess($posts, "Blog posts retrieved successfully", 200, $pagination);

} catch (\PDOException $e) {
    sendJsonError("Database error while listing posts: " . $e->getMessage(), 500);
}
