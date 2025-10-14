<?php
// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/../middleware/JWTMiddleware.php';
require_once('../services/SafePlaceService.php');

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
$safePlaceService = new SafePlaceService();

// Handle GET requests for public data (no authentication required)
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'getAllSafePlaces':
            $activeOnly = isset($_GET['active_only']) ? filter_var($_GET['active_only'], FILTER_VALIDATE_BOOLEAN) : true;
            $result = $safePlaceService->getAllSafePlaces($activeOnly);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'getSafePlacesForMapLibre':
            $activeOnly = isset($_GET['active_only']) ? filter_var($_GET['active_only'], FILTER_VALIDATE_BOOLEAN) : true;
            $result = $safePlaceService->getSafePlacesForMapLibre($activeOnly);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'getSafePlacesNearby':
            $lat = $_GET['lat'] ?? '';
            $lng = $_GET['long'] ?? '';
            $radius = isset($_GET['radius']) ? intval($_GET['radius']) : 5000;
            
            if (!$lat || !$lng || !is_numeric($lat) || !is_numeric($lng)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Valid latitude and longitude are required']);
                break;
            }
            
            $result = $safePlaceService->getSafePlacesNearby($lat, $lng, $radius);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'getSafePlacesInBounds':
            $north = $_GET['north'] ?? '';
            $south = $_GET['south'] ?? '';
            $east = $_GET['east'] ?? '';
            $west = $_GET['west'] ?? '';
            
            if (!$north || !$south || !$east || !$west) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'North, south, east, and west coordinates are required']);
                break;
            }
            
            $result = $safePlaceService->getSafePlacesInBounds($north, $south, $east, $west);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'searchSafePlaces':
            $searchTerm = $_GET['search'] ?? '';
            $activeOnly = isset($_GET['active_only']) ? filter_var($_GET['active_only'], FILTER_VALIDATE_BOOLEAN) : true;
            
            if (empty($searchTerm)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Search term is required']);
                break;
            }
            
            $result = $safePlaceService->searchSafePlaces($searchTerm, $activeOnly);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'getSafePlaceStats':
            $result = $safePlaceService->getSafePlaceStats();
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'getSafePlacesPaginated':
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $activeOnly = isset($_GET['active_only']) ? filter_var($_GET['active_only'], FILTER_VALIDATE_BOOLEAN) : true;
            
            $result = $safePlaceService->getSafePlacesPaginated($page, $limit, $activeOnly);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'getSafePlaceById':
            $id = $_GET['id'] ?? '';
            
            if (!$id || !is_numeric($id)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Valid safe place ID is required']);
                break;
            }
            
            $result = $safePlaceService->getSafePlaceById($id);
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Safe place not found']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action',
                'available_actions' => [
                    'getAllSafePlaces', 'getSafePlacesForMapLibre', 'getSafePlacesNearby', 
                    'getSafePlacesInBounds', 'searchSafePlaces', 'getSafePlaceStats',
                    'getSafePlacesPaginated', 'getSafePlaceById'
                ]
            ]);
            break;
    }
}
// Handle POST requests for authenticated operations
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // All POST operations require authentication
    $middleware->requireAuth(function() {
        global $safePlaceService;
        
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'createSafePlace':
                $data = $safePlaceService->cleanArray($_POST);
                
                // Validate data
                // $errors = $safePlaceService->validateSafePlaceData($data);
                // if (!empty($errors)) {
                //     http_response_code(400);
                //     echo json_encode([
                //         'success' => false,
                //         'message' => 'Validation failed',
                //         'errors' => $errors
                //     ]);
                //     break;
                // }
                
                $result = $safePlaceService->createSafePlace($data, $GLOBALS['current_user']['id']);
                if ($result['success']) {
                    http_response_code(201);
                } else {
                    http_response_code(400);
                }
                echo json_encode($result);
                break;
                
            case 'updateSafePlace':
                $id = $_POST['id'] ?? '';
                $data = $safePlaceService->cleanArray($_POST);
                unset($data['id']); // Remove ID from data array
                
                if (!$id || !is_numeric($id)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Valid safe place ID is required']);
                    break;
                }
                
                // Validate data
                $errors = $safePlaceService->validateSafePlaceData($data);
                if (!empty($errors)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $errors
                    ]);
                    break;
                }
                
                $result = $safePlaceService->updateSafePlace($id, $data);
                echo json_encode($result);
                break;
                
            case 'deleteSafePlace':
                $id = $_POST['id'] ?? '';
                
                if (!$id || !is_numeric($id)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Valid safe place ID is required']);
                    break;
                }
                
                $result = $safePlaceService->deleteSafePlace($id);
                echo json_encode($result);
                break;
                
            case 'restoreSafePlace':
                $id = $_POST['id'] ?? '';
                
                if (!$id || !is_numeric($id)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Valid safe place ID is required']);
                    break;
                }
                
                $result = $safePlaceService->restoreSafePlace($id);
                echo json_encode($result);
                break;
                
            case 'getSafePlacesByUser':
                $result = $safePlaceService->getSafePlacesByUser($GLOBALS['current_user']['id']);
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid action',
                    'available_actions' => [
                        'createSafePlace', 'updateSafePlace', 'deleteSafePlace', 
                        'restoreSafePlace', 'getSafePlacesByUser'
                    ]
                ]);
                break;
        }
    });
}
else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
        'allowed_methods' => ['GET', 'POST']
    ]);
}
?>
