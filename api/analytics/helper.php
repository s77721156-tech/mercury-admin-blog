<?php
/**
 * Mercury Admin - Analytics API Shared Helper
 * 
 * Provides database aggregation queries for blog metrics,
 * top performing posts, category distribution, and view tracking.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Ensures blog_views table exists for granular page view tracking
 */
function ensureBlogViewsSchema(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS blog_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_post_id (post_id),
            INDEX idx_viewed_at (viewed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (\Throwable $e) {
        error_log("Blog views schema check error: " . $e->getMessage());
    }
    $checked = true;
}

/**
 * Comprehensive blog analytics aggregation
 */
function getBlogAnalyticsData(PDO $pdo, int $topLimit = 5): array {
    // 1. Post counts
    $totalPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $published  = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE published = 1 OR status = 'Published'")->fetchColumn();
    $drafts     = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE published = 0 AND (status = 'Draft' OR status IS NULL OR status = '')")->fetchColumn();
    $scheduled  = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'Scheduled'")->fetchColumn();

    // 2. View statistics
    $totalViews = 0;
    try {
        $totalViews = (int)$pdo->query("SELECT COALESCE(SUM(views), 0) FROM posts")->fetchColumn();
    } catch (\Throwable $e) {}

    $publishedPct = $totalPosts > 0 ? round(($published / $totalPosts) * 100) : 0;
    $avgViews = $published > 0 ? round($totalViews / $published) : 0;

    // 3. Categories & Tags counts
    $categoriesCount = 0;
    try {
        $categoriesCount = (int)$pdo->query("SELECT COUNT(*) FROM blog_categories")->fetchColumn();
    } catch (\Throwable $e) {}

    $tagsCount = 0;
    try {
        $tagsCount = (int)$pdo->query("SELECT COUNT(*) FROM blog_tags")->fetchColumn();
    } catch (\Throwable $e) {}

    // 4. Top performing posts (by views DESC, id DESC)
    $topPosts = [];
    try {
        $stmt = $pdo->prepare("SELECT id, title, slug, category, author, status, published, views, created_at, updated_at
                               FROM posts
                               ORDER BY views DESC, id DESC
                               LIMIT :limit");
        $stmt->bindValue(':limit', max(1, min(50, $topLimit)), PDO::PARAM_INT);
        $stmt->execute();
        $topPosts = $stmt->fetchAll();
    } catch (\Throwable $e) {
        $stmt = $pdo->query("SELECT id, title, slug, category, author, status, published, views, created_at, updated_at
                             FROM posts ORDER BY id DESC LIMIT 5");
        $topPosts = $stmt->fetchAll();
    }

    // 5. Category breakdown
    $categoryDistribution = [];
    try {
        $catStmt = $pdo->query("SELECT category, COUNT(*) as post_count, COALESCE(SUM(views), 0) as total_views 
                                FROM posts 
                                WHERE category IS NOT NULL AND category != '' 
                                GROUP BY category 
                                ORDER BY post_count DESC");
        $categoryDistribution = $catStmt->fetchAll();
    } catch (\Throwable $e) {}

    // 6. Monthly trend (last 6 months)
    $monthlyTrends = [];
    try {
        $trendStmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%b %Y') as month_label,
                                         DATE_FORMAT(created_at, '%Y-%m') as year_month,
                                         COUNT(*) as post_count,
                                         COALESCE(SUM(views), 0) as views
                                  FROM posts 
                                  GROUP BY year_month, month_label
                                  ORDER BY year_month DESC 
                                  LIMIT 6");
        $monthlyTrends = $trendStmt->fetchAll();
    } catch (\Throwable $e) {}

    return [
        'total_posts'           => $totalPosts,
        'published'             => $published,
        'published_pct'         => $publishedPct,
        'drafts'                => $drafts,
        'scheduled'             => $scheduled,
        'total_views'           => $totalViews,
        'avg_views_per_post'    => $avgViews,
        'total_categories'      => $categoriesCount,
        'total_tags'            => $tagsCount,
        'top_posts'             => $topPosts,
        'category_distribution' => $categoryDistribution,
        'monthly_trends'        => $monthlyTrends,
    ];
}
