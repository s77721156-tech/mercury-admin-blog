<?php
/**
 * Mercury Admin - Analytics Overview API
 * 
 * Returns full KPI metrics, top performing articles,
 * category distribution, and monthly publication trends.
 * 
 * Method: GET
 * URL: /admin1/api/analytics/overview.php
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$topLimit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

try {
    $data = getBlogAnalyticsData($db, $topLimit);
    sendJsonSuccess($data, "Analytics data retrieved successfully");
} catch (\PDOException $e) {
    sendJsonError("Database error while retrieving analytics: " . $e->getMessage(), 500);
}
