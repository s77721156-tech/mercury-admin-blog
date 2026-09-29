<?php
/**
 * Mercury Admin - Product API Shared Helper
 * 
 * Provides database schema auto-migration for products table,
 * product validation, metric computation, and formatting utilities.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Returns baseline default products matching Mercury Softech requirements
 */
function getDefaultProductsList(): array {
    return [
        ['id' => 1, 'name' => 'WatsGo', 'category' => 'WhatsApp Automation', 'status' => 'Active', 'blogs' => 8, 'description' => 'Enterprise WhatsApp automation & chatbot platform.'],
        ['id' => 2, 'name' => 'Mercury One', 'category' => 'SaaS', 'status' => 'Active', 'blogs' => 5, 'description' => 'Comprehensive cloud business management & operational suite.'],
        ['id' => 3, 'name' => 'Purely', 'category' => 'Subscription', 'status' => 'Active', 'blogs' => 3, 'description' => 'Smart automated billing and subscription lifecycle management.'],
        ['id' => 4, 'name' => 'Nash', 'category' => 'Rewards', 'status' => 'Active', 'blogs' => 4, 'description' => 'Customer loyalty, referral tracking and reward programs.'],
        ['id' => 5, 'name' => 'Verify Hub', 'category' => 'Verification', 'status' => 'Active', 'blogs' => 2, 'description' => 'Identity verification, KYC workflows and secure document validation.'],
        ['id' => 6, 'name' => 'Creato', 'category' => 'Business Software', 'status' => 'Inactive', 'blogs' => 2, 'description' => 'Creative asset workflow, digital publishing & marketing software.']
    ];
}

/**
 * Ensures products table exists with all required fields
 * Auto-seeds initial default products if empty.
 */
function ensureProductsSchema(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            category VARCHAR(100) NOT NULL DEFAULT 'Business Solutions',
            status VARCHAR(50) NOT NULL DEFAULT 'Active',
            blogs INT DEFAULT 0,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Inspect existing columns to guarantee backwards compatibility
        $cols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('blogs', $cols, true)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN blogs INT DEFAULT 0 AFTER status");
        }
        if (!in_array('description', $cols, true)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN description TEXT NULL AFTER blogs");
        }
        if (!in_array('updated_at', $cols, true)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        }

        // Seed initial default products if table is currently empty
        $count = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare("INSERT INTO products (name, category, status, blogs, description) VALUES (?, ?, ?, ?, ?)");
            $defaults = getDefaultProductsList();
            foreach ($defaults as $d) {
                $stmt->execute([$d['name'], $d['category'], $d['status'], $d['blogs'], $d['description']]);
            }
        }
    } catch (\Throwable $e) {
        error_log("Products schema check error: " . $e->getMessage());
    }
    $checked = true;
}

/**
 * Format product record for API responses
 */
function formatProduct(array $p): array {
    return [
        'id'          => (int)($p['id'] ?? 0),
        'name'        => (string)($p['name'] ?? ''),
        'category'    => (string)($p['category'] ?? 'Business Solutions'),
        'status'      => (string)($p['status'] ?? 'Active'),
        'blogs'       => (int)($p['blogs'] ?? 0),
        'description' => (string)($p['description'] ?? ''),
        'created_at'  => (string)($p['created_at'] ?? ''),
        'updated_at'  => (string)($p['updated_at'] ?? ''),
    ];
}

/**
 * Calculate summary metrics for the 4 stat cards
 */
function getProductMetrics(PDO $pdo): array {
    try {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $active = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'Active'")->fetchColumn();
        $inactive = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'Inactive'")->fetchColumn();
        $blogUsage = (int)$pdo->query("SELECT COALESCE(SUM(blogs), 0) FROM products")->fetchColumn();

        if ($total === 0) {
            $defaults = getDefaultProductsList();
            return [
                'total'      => count($defaults),
                'active'     => count(array_filter($defaults, fn($p) => $p['status'] === 'Active')),
                'inactive'   => count(array_filter($defaults, fn($p) => $p['status'] === 'Inactive')),
                'blog_usage' => array_sum(array_column($defaults, 'blogs'))
            ];
        }

        return [
            'total'      => $total,
            'active'     => $active,
            'inactive'   => $inactive,
            'blog_usage' => $blogUsage
        ];
    } catch (\Throwable $e) {
        $defaults = getDefaultProductsList();
        return [
            'total'      => count($defaults),
            'active'     => 5,
            'inactive'   => 1,
            'blog_usage' => 24
        ];
    }
}
