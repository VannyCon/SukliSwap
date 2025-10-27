<?php
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// Enable CORS for API requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Get file path from query parameter
$filePath = $_GET['file'] ?? '';
$index = $_GET['index'] ?? 0; // For multiple files, specify which one to serve

if (empty($filePath)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'File path is required'
    ]);
    exit;
}

// Handle comma-separated file paths (multiple files)
$filePaths = explode(',', $filePath);
$filePaths = array_map('trim', $filePaths);

// Validate index
$index = (int)$index;
if ($index < 0 || $index >= count($filePaths)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid file index'
    ]);
    exit;
}

$selectedFilePath = $filePaths[$index];

// Clean the file path - remove ../data/documents/ prefix if present
$selectedFilePath = str_replace('../data/documents/', '', $selectedFilePath);

// Validate file path to prevent directory traversal attacks
$basePath = realpath(__DIR__ . '/../data/documents/');
$fullPath = realpath($basePath . '/' . $selectedFilePath);

if ($fullPath === false || strpos($fullPath, $basePath) !== 0) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied'
    ]);
    exit;
}

// Check if file exists
if (!file_exists($fullPath)) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'File not found'
    ]);
    exit;
}

// Get file info
$fileInfo = pathinfo($fullPath);
$mimeType = mime_content_type($fullPath);

// Set appropriate headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . $fileInfo['basename'] . '"');
header('Cache-Control: private, max-age=3600');

// Output file content
readfile($fullPath);
exit;
?>
