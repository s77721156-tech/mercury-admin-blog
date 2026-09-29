<?php
/**
 * Mercury Admin - Blog Image Upload API
 * 
 * Handles uploading cover images and content images.
 * Validates: JPG, PNG, WebP, max 5 MB.
 * 
 * Method: POST (multipart/form-data)
 * URL: /admin1/api/upload/blog-image.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDbConnection();
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

// Validate HTTP Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError("Invalid request method. File upload requires POST method (currently '{$_SERVER['REQUEST_METHOD']}').", 405);
}

// Check for file in 'coverImage', 'cover_image', 'image', or 'file'
$fileField = null;
foreach (['coverImage', 'cover_image', 'image', 'file'] as $field) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        $fileField = $field;
        break;
    }
}

if (!$fileField) {
    $errorMsg = 'No file uploaded or upload error occurred.';
    foreach (['coverImage', 'cover_image', 'image', 'file'] as $field) {
        if (isset($_FILES[$field]['error'])) {
            $code = $_FILES[$field]['error'];
            if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
                $errorMsg = 'Uploaded file exceeds the maximum allowed file size.';
            } elseif ($code === UPLOAD_ERR_NO_FILE) {
                $errorMsg = "File field '{$field}' was submitted but no file was selected.";
            }
            break;
        }
    }
    sendJsonError($errorMsg, 400);
}

$file = $_FILES[$fileField];

// Validate File Size (Max 5 MB)
$maxSizeBytes = 5 * 1024 * 1024;
if ($file['size'] > $maxSizeBytes) {
    sendJsonError("File exceeds the maximum allowed size of 5 MB.", 400);
}

// Validate MIME type and extension
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExtMap = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
    'gif'  => 'image/gif'
];

$mimeType = null;

// 1. Try getimagesize (inspects actual image bytes)
$imgInfo = @getimagesize($file['tmp_name']);
if ($imgInfo && !empty($imgInfo['mime'])) {
    $mimeType = $imgInfo['mime'];
}

// 2. Try finfo if available
if ((!$mimeType || $mimeType === 'application/octet-stream') && function_exists('finfo_open')) {
    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $detected = @finfo_file($finfo, $file['tmp_name']);
        @finfo_close($finfo);
        if ($detected && $detected !== 'application/octet-stream') {
            $mimeType = $detected;
        }
    }
}

// 3. Try mime_content_type
if ((!$mimeType || $mimeType === 'application/octet-stream') && function_exists('mime_content_type')) {
    $detected = @mime_content_type($file['tmp_name']);
    if ($detected && $detected !== 'application/octet-stream') {
        $mimeType = $detected;
    }
}

// 4. Fallback based on extension or client header
if (!$mimeType || $mimeType === 'application/octet-stream') {
    if (isset($allowedExtMap[$extension])) {
        $mimeType = $allowedExtMap[$extension];
    } elseif (!empty($file['type']) && $file['type'] !== 'application/octet-stream') {
        $mimeType = $file['type'];
    }
}

$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($mimeType, $allowedMimeTypes, true)) {
    sendJsonError("Invalid file type ({$mimeType}). Only JPG, PNG, and WebP are allowed.", 400);
}

$cleanExtension = match ($mimeType) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
    default      => $extension ?: 'jpg'
};

// Target directory: ../../uploads/
$uploadDir = __DIR__ . '/../../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$uniqueFileName = 'cover_' . uniqid('', true) . '.' . $cleanExtension;
$destination = $uploadDir . $uniqueFileName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    sendJsonError("Failed to store the uploaded image on server.", 500);
}

// Relative path for database and public URL
$relativePath = 'uploads/' . $uniqueFileName;

sendJsonSuccess([
    'url'          => $relativePath,
    'file_name'    => $uniqueFileName,
    'original_name'=> $file['name'],
    'mime_type'    => $mimeType,
    'size_bytes'   => $file['size'],
], "Image uploaded successfully", 201);
