<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../middleware/JWTMiddleware.php';

// Initialize services
$jwtMiddleware = new JWTMiddleware();

// Get file path from request
$filePath = $_GET['file'] ?? '';

if (empty($filePath)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'File path is required'
    ]);
    exit();
}

try {
    // Authenticate user
    $user = $jwtMiddleware->validateToken();
    if (!$user) {
        throw new Exception('Authentication required');
    }

    // Validate file path (security check)
    $allowedPath = '../data/documents/messages/';
    $fullPath = realpath($allowedPath . $filePath);
    
    if (!$fullPath || strpos($fullPath, realpath($allowedPath)) !== 0) {
        throw new Exception('Invalid file path');
    }

    // Check if file exists
    if (!file_exists($fullPath)) {
        throw new Exception('File not found');
    }

    // Get file info
    $fileInfo = pathinfo($fullPath);
    $mimeType = mime_content_type($fullPath);
    $fileSize = filesize($fullPath);

    // Set appropriate headers
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . $fileSize);
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
    header('Cache-Control: public, max-age=3600');

    // Output file
    readfile($fullPath);
    exit();

} catch (Exception $e) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
