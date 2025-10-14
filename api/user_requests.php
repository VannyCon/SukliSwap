<?php
// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/../middleware/JWTMiddleware.php';
require_once('../services/RequestService.php');

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
$requestService = new RequestService();

// Handle guest-accessible (read-only) operations first
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $action = $_GET['action'] ?? '';
    error_log("GET action received: " . $action);

    switch ($action) {
        case 'getActiveRequests':
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $size = isset($_GET['size']) ? intval($_GET['size']) : 12;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : 'active';
            $coinTypeId = isset($_GET['coin_type_id']) ? trim($_GET['coin_type_id']) : '';
            
            $filters = [
                'coin_type_id' => $coinTypeId,
                'search' => $search
            ];
            
            $result = $requestService->getActiveRequests($filters);
            echo json_encode([
                'success' => true,
                'data' => $result['data'] ?? $result,
                'meta' => [
                    'page' => $page,
                    'size' => $size,
                    'search' => $search
                ]
            ]);
            exit;

        default:
            // Optionally handle unknown actions
            break;
    }
}

// Require authentication for all other request operations
$middleware->requireAuth(function() {
    error_log("Inside authenticated callback");
    global $requestService;
    
    // Handle all actions via POST method
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'createRequest':
                $data = $requestService->cleanArray($_POST);
                $data['user_id'] = $GLOBALS['current_user']['id'];
                $result = $requestService->createRequest($data, $GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;
                
            case 'getMyRequests':
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $size = isset($_GET['size']) ? intval($_GET['size']) : 12;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $status = isset($_GET['status']) ? trim($_GET['status']) : '';
                $coinTypeId = isset($_GET['coin_type_id']) ? trim($_GET['coin_type_id']) : '';
                
                $filters = [
                    'status' => $status,
                    'coin_type_id' => $coinTypeId,
                    'search' => $search
                ];
                
                $result = $requestService->getUserRequests($GLOBALS['current_user']['id'], $filters);
                echo json_encode([
                    'success' => true,
                    'data' => $result['data'] ?? $result,
                    'meta' => [
                        'page' => $page,
                        'size' => $size,
                        'search' => $search
                    ]
                ]);
                break;

            case 'getRequestById':
                $requestId = $_GET['request_id'] ?? '';
                if ($requestId) {
                    $result = $requestService->getRequestById($requestId, $GLOBALS['current_user']['id']);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Request ID is required'
                    ]);
                }
                break;

            case 'getRequestStats':
                $result = $requestService->getRequestStats($GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;
                
            case 'updateRequest':
                $requestId = $_GET['request_id'] ?? '';
                if ($requestId) {
                    $data = $requestService->cleanArray($_POST);
                    $result = $requestService->updateRequest($requestId, $data, $GLOBALS['current_user']['id']);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Request ID is required'
                    ]);
                }
                break;
                
            case 'deleteRequest':
                $requestId = $_GET['request_id'] ?? '';
                if ($requestId) {
                    $result = $requestService->deleteRequest($requestId, $GLOBALS['current_user']['id']);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Request ID is required'
                    ]);
                }
                break;

            case 'cancelRequest':
                $requestId = $_GET['request_id'] ?? '';
                if ($requestId) {
                    $result = $requestService->cancelRequest($requestId, $GLOBALS['current_user']['id']);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Request ID is required'
                    ]);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid action',
                    'available_actions' => [
                        'createRequest', 'getMyRequests', 'getRequestById', 'getRequestStats',
                        'updateRequest', 'deleteRequest', 'cancelRequest'
                    ]
                ]);
                break;
        }
    }if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'getAllRequests':
                // Admin: Get all requests from all users
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $size = isset($_GET['size']) ? intval($_GET['size']) : 12;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $status = isset($_GET['status']) ? trim($_GET['status']) : '';
                $coinTypeId = isset($_GET['coin_type_id']) ? trim($_GET['coin_type_id']) : '';
                
                $filters = [
                    'status' => $status,
                    'coin_type_id' => $coinTypeId,
                    'search' => $search
                ];
                
                $result = $requestService->getAllRequests($filters);
                echo json_encode([
                    'success' => true,
                    'data' => $result['data'] ?? $result,
                    'meta' => [
                        'page' => $page,
                        'size' => $size,
                        'search' => $search
                    ]
                ]);
                break;
            }
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed',
            'allowed_methods' => ['POST']
        ]);
    }
});
?>
