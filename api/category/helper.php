<?php
/**
 * Mercury Admin - Category API Shared Helper
 * 
 * Provides database schema auto-migration for blog_categories table,
 * category input validation, and slug generation.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Ensures blog_categories table exists with all required fields
 * Auto-seeds initial default categories if the table is empty.
 */
function ensureCategoriesSchema(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS blog_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Check if description or updated_at are missing
        $cols = $pdo->query("SHOW COLUMNS FROM blog_categories")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('description', $cols, true)) {
            $pdo->exec("ALTER TABLE blog_categories ADD COLUMN description TEXT NULL AFTER slug");
        }
        if (!in_array('updated_at', $cols, true)) {
            $pdo->exec("ALTER TABLE blog_categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        }

        // Seed initial default categories if empty
        $count = (int)$pdo->query("SELECT COUNT(*) FROM blog_categories")->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO blog_categories (name, slug, description) VALUES (?, ?, ?)");
            $defaults = [
                ['AI & Automation', 'ai-automation', 'Articles focusing on enterprise artificial intelligence and automated systems.'],
                ['Business Solutions', 'business-solutions', 'Strategic technological solutions for growing and enterprise business operations.'],
                ['SaaS', 'saas', 'Software-as-a-Service platforms, cloud architectures, and subscription services.'],
                ['Web Development', 'web-development', 'Modern web technologies, frontend frameworks, APIs, and scalable backends.'],
                ['WhatsApp', 'whatsapp', 'WhatsApp Business API, customer engagement strategies, and bot automation.']
            ];
            foreach ($defaults as $d) {
                $stmt->execute($d);
            }
        }
    } catch (\Throwable $e) {
        error_log("Categories schema check error: " . $e->getMessage());
    }
    $checked = true;
}

/**
 * Generate a clean URL-friendly slug
 */
function generateCategorySlug(string $name): string {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
    return trim($slug, '-');
}

/**
 * Format category record for JSON response
 */
function formatCategory(array $cat): array {
    return [
        'id'          => (int)($cat['id'] ?? 0),
        'name'        => (string)($cat['name'] ?? ''),
        'slug'        => (string)($cat['slug'] ?? ''),
        'description' => (string)($cat['description'] ?? ''),
        'post_count'  => (int)($cat['post_count'] ?? 0),
        'created_at'  => (string)($cat['created_at'] ?? ''),
        'updated_at'  => (string)($cat['updated_at'] ?? ''),
    ];
}
