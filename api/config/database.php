<?php
/**
 * Mercury Admin - Database Connection & Core API Utilities
 * 
 * Provides production-ready PDO connection using PHP 8+ standards,
 * secure credential handling, and standardized JSON response helpers.
 */

// Global CORS & JSON Content Type
if (!headers_sent()) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Admin-Key");
    header("Content-Type: application/json; charset=UTF-8");
}

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

/**
 * Safely parse a .env file into environment variables without exposing sensitive values
 */
function loadApiEnv(string $path): bool {
    if (!file_exists($path) || !is_readable($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return false;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }
        
        $parts = explode('=', $trimmed, 2);
        if (count($parts) < 2) {
            continue;
        }
        
        $name = trim($parts[0]);
        $val = trim($parts[1]);
        
        // Strip surrounding quotes
        if (preg_match('/^"(.*)"$/', $val, $m) || preg_match('/^\'(.*)\'$/', $val, $m)) {
            $val = $m[1];
        }
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $val));
            $_ENV[$name] = $val;
            $_SERVER[$name] = $val;
        }
    }
    return true;
}

// Search and load environment configuration from potential locations
$envCandidatePaths = [
    __DIR__ . '/../../.env.local',
    __DIR__ . '/../../.env',
    __DIR__ . '/../../../.env.local',
    __DIR__ . '/../../../.env',
];

foreach ($envCandidatePaths as $candidate) {
    if (file_exists($candidate)) {
        loadApiEnv($candidate);
        break;
    }
}

/**
 * Returns a configured PDO instance or terminates with a secure JSON error
 */
function getDbConnection(): PDO {
    static $pdoInstance = null;
    if ($pdoInstance !== null) {
        return $pdoInstance;
    }

    // Retrieve database configuration (defaults match existing production setup in db.php)
    $host    = $_ENV['DB_HOST'] ?? 'localhost';
    $port    = $_ENV['DB_PORT'] ?? '3306';
    $dbName  = $_ENV['DB_NAME'] ?? 'mercuryone_mercurysoftech_dotin';
    $dbUser  = $_ENV['DB_USER'] ?? 'mercuryone_mercurysoftech_dotin';
    $dbPass  = $_ENV['DB_PASSWORD'] ?? 'Mercurysoftechdotin@2026';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
    ];

    try {
        $pdoInstance = new PDO($dsn, $dbUser, $dbPass, $options);
        return $pdoInstance;
    } catch (\PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        sendJsonError("Unable to connect to the database. Please verify service configuration.", 500);
        exit;
    }
}

// Shared $pdo variable for script inclusion
$pdo = null;
try {
    $pdo = getDbConnection();
} catch (\Throwable $e) {
    // Error response handled inside getDbConnection()
}

/**
 * Standardized JSON Success Response
 */
function sendJsonSuccess(mixed $data = null, string $message = "Operation completed successfully", int $statusCode = 200, ?array $pagination = null): never {
    http_response_code($statusCode);
    
    $payload = [
        'success' => true,
        'message' => $message,
    ];

    if ($data !== null) {
        $payload['data'] = $data;
    }

    if ($pagination !== null) {
        $payload['pagination'] = $pagination;
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Standardized JSON Error Response
 */
function sendJsonError(string $message = "An error occurred", int $statusCode = 400, mixed $errors = null): never {
    http_response_code($statusCode);
    
    $payload = [
        'success' => false,
        'message' => $message,
    ];

    if ($errors !== null) {
        $payload['errors'] = $errors;
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Read and decode incoming JSON request body or return $_POST data
 */
function getApiRequestBody(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $data = json_decode($raw, true);
            if (is_array($data)) {
                return $data;
            }
        }
        return [];
    }
    return $_POST ?: [];
}

/**
 * Admin authentication check supporting both PHP Session and API Authorization headers
 */
function requireAdminAuth(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Session check (standard browser admin panel flow)
    if (!empty($_SESSION['blog_admin_logged_in']) && $_SESSION['blog_admin_logged_in'] === true) {
        return;
    }

    // 2. Header check (Postman / programmatic API requests)
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_ADMIN_KEY'] ?? '';
    if (!empty($authHeader)) {
        $token = trim(str_replace('Bearer', '', $authHeader));
        if ($token === 'mercury2026' || $token === 'Mercurysoftechdotin@2026') {
            return;
        }
    }

    sendJsonError("Unauthorized. Please log in to access this resource.", 401);
}
