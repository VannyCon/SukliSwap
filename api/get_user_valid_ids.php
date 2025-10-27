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

// Get user ID from query parameter
$userId = $_GET['user_id'] ?? '';

if (empty($userId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit;
}

try {
    // Get user data from database
    $config = new config();
    $pdo = $config->pdo;
    
    // Validate user ID is numeric
    if (!is_numeric($userId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid user ID format'
        ]);
        exit;
    }
    
    $query = "SELECT valid_id FROM tbl_users WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    if (empty($user['valid_id'])) {
        echo json_encode([
            'success' => true,
            'data' => [
                'valid_ids' => [],
                'count' => 0
            ]
        ]);
        exit;
    }
    
    // Split comma-separated file paths
    $filePaths = explode(',', $user['valid_id']);
    $filePaths = array_map('trim', $filePaths);
    
    // Generate URLs for each file
    $validIds = [];
    foreach ($filePaths as $index => $filePath) {
        // Extract relative path from the full path for URL generation
        $relativePath = str_replace('../data/documents/', '', $filePath);
        $validIds[] = [
            'index' => $index,
            'file_path' => $filePath,
            'relative_path' => $relativePath,
            'url' => '' . urlencode($user['valid_id']) . '&index=' . $index,
            'filename' => basename($filePath)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'valid_ids' => $validIds,
            'count' => count($validIds)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get user valid IDs error: " . $e->getMessage());
    error_log("User ID: " . $userId);
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>
