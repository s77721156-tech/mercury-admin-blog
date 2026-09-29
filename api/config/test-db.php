<?php
/**
 * Phase 1 Database Connection & Schema Diagnostic Endpoint
 * 
 * Verifies PDO connectivity and inspects table schemas for:
 * blog_posts, blog_categories, blog_tags, blog_post_tags,
 * blog_post_related_products, blog_views, users, products, posts, etc.
 * 
 * URL: /admin1/api/config/test-db.php
 */

require_once __DIR__ . '/database.php';

try {
    $db = getDbConnection();

    // 1. Check basic connection info
    $driverName = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $serverVersion = $db->getAttribute(PDO::ATTR_SERVER_VERSION);

    // 2. Fetch all tables in current database
    $stmt = $db->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Inspect target tables specifically
    $targetTables = [
        'blog_posts',
        'blog_categories',
        'blog_tags',
        'blog_post_tags',
        'blog_post_related_products',
        'blog_views',
        'users',
        'products',
        'posts',
        'gallery_photos',
        'gallery_videos',
        'jobs',
        'job_applications',
    ];

    $tablesStatus = [];
    $schemas = [];

    foreach ($targetTables as $tableName) {
        $exists = in_array($tableName, $allTables, true);
        $tablesStatus[$tableName] = $exists ? 'exists' : 'missing';

        if ($exists) {
            // Retrieve column details safely via prepared query
            $colStmt = $db->prepare("DESCRIBE `" . str_replace("`", "``", $tableName) . "`");
            $colStmt->execute();
            $columns = $colStmt->fetchAll();

            $schemas[$tableName] = array_map(function ($col) {
                return [
                    'field'   => $col['Field'] ?? '',
                    'type'    => $col['Type'] ?? '',
                    'null'    => $col['Null'] ?? '',
                    'key'     => $col['Key'] ?? '',
                    'default' => $col['Default'] ?? null,
                    'extra'   => $col['Extra'] ?? '',
                ];
            }, $columns);
        }
    }

    sendJsonSuccess([
        'database_connection' => 'connected',
        'driver'             => $driverName,
        'server_version'     => $serverVersion,
        'total_tables_found' => count($allTables),
        'target_tables'      => $tablesStatus,
        'schemas'            => $schemas,
    ], "Database connection and schema inspection completed successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error during schema inspection: " . $e->getMessage(), 500);
} catch (\Throwable $e) {
    sendJsonError("Unexpected error: " . $e->getMessage(), 500);
}
