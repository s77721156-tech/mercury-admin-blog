<?php
/**
 * Mercury Admin - Category Analytics API
 * 
 * Returns category breakdown with total posts, total views, and average views.
 * 
 * Method: GET
 * URL: /admin1/api/analytics/categories.php
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

try {
    $sql = "SELECT 
                c.id,
                c.name,
                c.slug,
                COUNT(p.id) AS post_count,
                COALESCE(SUM(p.views), 0) AS total_views,
                COALESCE(ROUND(AVG(p.views)), 0) AS avg_views,
                COUNT(CASE WHEN p.published = 1 THEN 1 END) AS published_count,
                COUNT(CASE WHEN p.published = 0 THEN 1 END) AS draft_count
            FROM blog_categories c
            LEFT JOIN posts p ON p.category = c.name
            GROUP BY c.id, c.name, c.slug
            ORDER BY post_count DESC, total_views DESC";

    $stmt = $db->query($sql);
    $results = $stmt->fetchAll();

    sendJsonSuccess($results, "Category analytics retrieved successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error while retrieving category analytics: " . $e->getMessage(), 500);
}
