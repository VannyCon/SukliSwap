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
    error_log("Inside authenticated callback");
    global $profileService;
    
    // Handle different actions based on HTTP method
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'uploadProfilePicture':
                if (!isset($_FILES['profile_picture'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
                    break;
                }
                
                $result = $profileService->uploadProfilePicture($GLOBALS['current_user']['id'], $_FILES['profile_picture']);
                echo json_encode($result);
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid action',
                    'available_actions' => [
                        'uploadProfilePicture'
                    ]
                ]);
                break;
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
        $action = $_GET['action'] ?? '';
        error_log("GET action received: " . $action);
        
        switch ($action) {
            case 'getUserProfile':
                $result = $profileService->getUserProfile($GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;

            case 'getUserStats':
                $result = $profileService->getUserStats($GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;

            case 'getUserActivity':
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                $result = $profileService->getUserActivity($GLOBALS['current_user']['id'], $limit);
                echo json_encode($result);
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid action',
                    'available_actions' => [
                        'getUserProfile', 'getUserStats', 'getUserActivity'
                    ]
                ]);
                break;
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "PUT") {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'updateUserProfile':
                $data = $profileService->cleanArray($_POST);
                $result = $profileService->updateUserProfile($GLOBALS['current_user']['id'], $data);
                echo json_encode($result);
                break;

            case 'changePassword':
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                
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
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid action',
                    'available_actions' => [
                        'updateUserProfile', 'changePassword'
                    ]
                ]);
                break;
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "DELETE") {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'deleteUserAccount':
                $confirmation = $_POST['confirmation'] ?? '';
                
                if ($confirmation !== 'DELETE') {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Account deletion confirmation required']);
                    break;
                }
                
                $result = $profileService->deleteUserAccount($GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid action',
                    'available_actions' => [
                        'deleteUserAccount'
                    ]
                ]);
                break;
        }
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed',
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE']
        ]);
    }
});
?>