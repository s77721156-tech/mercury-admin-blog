<?php
/**
 * Mercury Admin - Analytics Unified REST Gateway
 * 
 * Routes:
 * - GET /api/analytics/                 -> Overview KPIs & top posts
 * - GET /api/analytics/?type=top-posts  -> Ranked top posts
 * - GET /api/analytics/?type=categories -> Category analytics breakdown
 * - POST /api/analytics/?action=track   -> Track view for a post
 */

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$type   = $_GET['type'] ?? '';
$action = $_GET['action'] ?? '';

if ($action === 'track' || ($method === 'POST' && (isset($_GET['post_id']) || isset($_GET['id'])))) {
    require __DIR__ . '/track-view.php';
} elseif ($type === 'top-posts' || $type === 'top') {
    require __DIR__ . '/top-posts.php';
} elseif ($type === 'categories' || $type === 'category') {
    require __DIR__ . '/categories.php';
} else {
    require __DIR__ . '/overview.php';
}
