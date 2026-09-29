<?php
/**
 * Mercury Admin - Tag API Shared Helper
 * 
 * Provides database schema auto-migration for blog_tags table,
 * tag validation, slug generation, and post usage calculation.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Ensures blog_tags table exists with all required fields
 * Auto-seeds initial default tags if the table is empty.
 */
function ensureTagsSchema(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS blog_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            slug VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Check if updated_at is missing in existing table
        $cols = $pdo->query("SHOW COLUMNS FROM blog_tags")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('updated_at', $cols, true)) {
            $pdo->exec("ALTER TABLE blog_tags ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        }

        // Seed initial default tags if empty
        $count = (int)$pdo->query("SELECT COUNT(*) FROM blog_tags")->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO blog_tags (name, slug) VALUES (?, ?)");
            $defaults = [
                ['Artificial Intelligence', 'artificial-intelligence'],
                ['Automation', 'automation'],
                ['CRM', 'crm'],
                ['Cloud Software', 'cloud-software'],
                ['Web Tech', 'web-tech'],
                ['SaaS', 'saas'],
                ['Machine Learning', 'machine-learning']
            ];
            foreach ($defaults as $d) {
                $stmt->execute($d);
            }
        }
    } catch (\Throwable $e) {
        error_log("Tags schema check error: " . $e->getMessage());
    }
    $checked = true;
}

/**
 * Generate a clean URL-friendly tag slug
 */
function generateTagSlug(string $name): string {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
    return trim($slug, '-');
}

/**
 * Format tag record for JSON response
 */
function formatTag(array $tag): array {
    return [
        'id'          => (int)($tag['id'] ?? 0),
        'name'        => (string)($tag['name'] ?? ''),
        'slug'        => (string)($tag['slug'] ?? ''),
        'post_count'  => (int)($tag['post_count'] ?? 0),
        'created_at'  => (string)($tag['created_at'] ?? ''),
        'updated_at'  => (string)($tag['updated_at'] ?? ''),
    ];
}
