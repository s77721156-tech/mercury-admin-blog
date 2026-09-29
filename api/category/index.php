<?php
/**
 * Mercury Admin - Category Unified REST Gateway
 * 
 * Routes HTTP verbs:
 * - GET: list or single category
 * - POST: create category
 * - PUT / PATCH: update category
 * - DELETE: delete category
 */

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($method === 'GET') {
    if (isset($_GET['id']) || isset($_GET['slug'])) {
        require __DIR__ . '/get.php';
    } else {
        require __DIR__ . '/list.php';
    }
} elseif ($method === 'POST') {
    if (isset($_GET['id'])) {
        require __DIR__ . '/update.php';
    } else {
        require __DIR__ . '/create.php';
    }
} elseif ($method === 'PUT' || $method === 'PATCH') {
    require __DIR__ . '/update.php';
} elseif ($method === 'DELETE') {
    require __DIR__ . '/delete.php';
} else {
    require_once __DIR__ . '/helper.php';
    sendJsonError("Method {$method} not allowed", 405);
}
