<?php
/**
 * Mercury Admin - Database Diagnostic Tool
 * 
 * Inspects connected database, active tables, row counts, and environment paths.
 * URL: /admin1/api/db-check.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';

try {
    $db = getDbConnection();

    // 1. Current Database & User info
    $currentDb = $db->query("SELECT DATABASE()")->fetchColumn();
    $currentUser = $db->query("SELECT USER()")->fetchColumn();
    $serverVersion = $db->getAttribute(PDO::ATTR_SERVER_VERSION);

    // 2. All tables in current database
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    // 3. Check posts table
    $postsCount = 0;
    $latestPosts = [];
    if (in_array('posts', $tables, true)) {
        $postsCount = (int)$db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        $latestPosts = $db->query("SELECT id, title, category, status, published, created_at FROM posts ORDER BY id DESC LIMIT 5")->fetchAll();
    }

    // 4. Check category tables
    $categoryTables = [];
    foreach ($tables as $t) {
        if (str_contains($t, 'cat')) {
            $cnt = (int)$db->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
            $categoryTables[$t] = $cnt;
        }
    }

    // 5. Accessible databases
    $allDatabases = [];
    try {
        $allDatabases = $db->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    } catch (\Throwable $e) {}

    // 6. Checked .env locations
    $envChecked = [];
    foreach ([
        __DIR__ . '/../.env.local',
        __DIR__ . '/../.env',
        __DIR__ . '/../../.env.local',
        __DIR__ . '/../../.env',
        __DIR__ . '/../../../.env.local',
        __DIR__ . '/../../../.env'
    ] as $p) {
        $envChecked[$p] = file_exists($p);
    }

    echo json_encode([
        'success'           => true,
        'current_database'  => $currentDb,
        'current_user'      => $currentUser,
        'server_version'    => $serverVersion,
        'accessible_databases' => $allDatabases,
        'tables_in_database'=> $tables,
        'posts_table_exists'=> in_array('posts', $tables, true),
        'posts_total_count' => $postsCount,
        'latest_posts'      => $latestPosts,
        'category_tables'   => $categoryTables,
        'env_files_detected'=> array_filter($envChecked),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
