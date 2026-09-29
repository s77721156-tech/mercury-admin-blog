<?php
/**
 * Mercury Admin - Blog Statistics API
 * 
 * Returns summary metrics for the top stat cards:
 * - Total Posts (+ % change)
 * - Published Count (and % of total)
 * - Drafts Count
 * - Scheduled Count
 * - Total Views
 * 
 * Method: GET
 * URL: /admin1/api/blog/stats.php
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensurePostsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

try {
    $totalPosts = (int)$db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $published  = (int)$db->query("SELECT COUNT(*) FROM posts WHERE published = 1 OR status = 'Published'")->fetchColumn();
    $drafts     = (int)$db->query("SELECT COUNT(*) FROM posts WHERE published = 0 AND (status = 'Draft' OR status IS NULL)")->fetchColumn();
    $scheduled  = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status = 'Scheduled'")->fetchColumn();
    
    $totalViews = 0;
    try {
        $totalViews = (int)$db->query("SELECT COALESCE(SUM(views), 0) FROM posts")->fetchColumn();
    } catch (\Throwable $e) {}

    $publishedPct = $totalPosts > 0 ? round(($published / $totalPosts) * 100) : 0;

    sendJsonSuccess([
        'total_posts'     => $totalPosts,
        'published'       => $published,
        'published_pct'   => $publishedPct,
        'drafts'          => $drafts,
        'scheduled'       => $scheduled,
        'total_views'     => $totalViews,
    ], "Blog statistics retrieved successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error while retrieving stats: " . $e->getMessage(), 500);
}
