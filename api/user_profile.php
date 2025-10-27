<?php
// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/../middleware/JWTMiddleware.php';
require_once('../services/UserProfileService.php');

// Enable CORS for API requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$middleware = new JWTMiddleware();
header('Content-Type: application/json');
$profileService = new UserProfileService();

// All profile operations require authentication
$middleware->requireAuth(function() {
    global $profileService;
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    try {
        // Get action parameter from GET or POST
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        // Get request data - handle both JSON and form data
        $requestData = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                // Handle JSON data
                $json = file_get_contents('php://input');
                $requestData = json_decode($json, true) ?? [];
            } else {
                // Handle form data
                $requestData = $_POST;
            }
        }
        
        // Handle all requests through action parameter
        switch ($action) {
            case 'getUserProfile':
                // Get user profile
                $result = $profileService->getUserProfile($GLOBALS['current_user']['id']);
                if ($result) {
                    echo json_encode(['success' => true, 'data' => $result]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Profile not found']);
                }
                break;
                
            case 'getUserStats':
                // Get user stats
                $result = $profileService->getUserStats($GLOBALS['current_user']['id']);
                if ($result) {
                    echo json_encode(['success' => true, 'data' => $result]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Stats not found']);
                }
                break;
                
            case 'getUserActivity':
                // Get user activity
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                $result = $profileService->getUserActivity($GLOBALS['current_user']['id'], $limit);
                if ($result !== false) {
                    echo json_encode(['success' => true, 'data' => $result]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Activity not found']);
                }
                break;
                
            case 'updateUserProfile':
                // Update user profile
                $data = $profileService->cleanArray($requestData);
                $result = $profileService->updateUserProfile($GLOBALS['current_user']['id'], $data);
                echo json_encode($result);
                break;
                
            case 'changePassword':
                // Change password
                $currentPassword = $requestData['current_password'] ?? '';
                $newPassword = $requestData['new_password'] ?? '';
                
                if (!$currentPassword || !$newPassword) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Current password and new password are required']);
                    break;
                }
                
                $result = $profileService->changePassword(
                    $GLOBALS['current_user']['id'],
                    $currentPassword,
                    $newPassword
                );
                echo json_encode($result);
                break;
                
            case 'uploadProfilePicture':
                // Upload profile picture
                if (!isset($_FILES['profile_picture'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
                    break;
                }
                
                $result = $profileService->uploadProfilePicture($GLOBALS['current_user']['id'], $_FILES['profile_picture']);
                echo json_encode($result);
                break;
                
            case 'deleteUserAccount':
                // Delete user account
                $confirmation = $requestData['confirmation'] ?? '';
                
                if ($confirmation !== 'DELETE') {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Account deletion confirmation required']);
                    break;
                }
                
                $result = $profileService->deleteUserAccount($GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;
                
            default:
                // If no action specified, default to getting user profile
                $result = $profileService->getUserProfile($GLOBALS['current_user']['id']);
                if ($result) {
                    echo json_encode(['success' => true, 'data' => $result]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Profile not found']);
                }
                break;
        }
    } catch (Exception $e) {
        error_log("Profile API error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }});

?>