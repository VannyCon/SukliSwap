<?php
// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/../middleware/JWTMiddleware.php';
require_once('../services/OfferService.php');

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
$offerService = new OfferService();

// Handle guest-accessible (read-only) operations first
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $action = $_GET['action'] ?? '';
    error_log("GET action received: " . $action);

    switch ($action) {
        case 'getActiveOffers':
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $size = isset($_GET['size']) ? intval($_GET['size']) : 12;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : 'active';
            $coinTypeId = isset($_GET['coin_type_id']) ? trim($_GET['coin_type_id']) : '';
            
            $filters = [
                'coin_type_id' => $coinTypeId,
                'search' => $search
            ];
            
            $result = $offerService->getActiveOffers($filters);
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

// Require authentication for all other offer operations
$middleware->requireAuth(function() {
    error_log("Inside authenticated callback");
    global $offerService;
    
    // Handle all actions via POST method
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'createOffer':
                $data = $offerService->cleanArray($_POST);
                $data['user_id'] = $GLOBALS['current_user']['id'];
                $result = $offerService->createOffer($data, $GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;

            case 'getOfferById':
                $offerId = $_GET['offer_id'] ?? '';
                if ($offerId) {
                    $result = $offerService->getOfferById($offerId, $GLOBALS['current_user']['id']);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Offer ID is required'
                    ]);
                }
                break;

            case 'getOfferStats':
                $result = $offerService->getOfferStats($GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;
                
            case 'updateOffer':
                $offerId = $_GET['offer_id'] ?? '';
                if ($offerId) {
                    $data = $offerService->cleanArray($_POST);
                    $result = $offerService->updateOffer($offerId, $data, $GLOBALS['current_user']['id']);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Offer ID is required'
                    ]);
                }
                break;
                
            case 'deleteOffer':
                $offerId = $_GET['offer_id'] ?? '';
                if ($offerId) {
                    $result = $offerService->deleteOffer($offerId, $GLOBALS['current_user']['id']);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Offer ID is required'
                    ]);
                }
                break;

            case 'cancelOffer':
                $offerId = $_GET['offer_id'] ?? '';
                if ($offerId) {
                    $result = $offerService->cancelOffer($offerId, $GLOBALS['current_user']['id']);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Offer ID is required'
                    ]);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid action',
                    'available_actions' => [
                        'createOffer', 'getMyOffers', 'getOfferById', 'getOfferStats', 
                        'updateOffer', 'deleteOffer', 'cancelOffer'
                    ]
                ]);
                break;
        }
    } else if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'getMyOffers':
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
                
                $result = $offerService->getUserOffers($GLOBALS['current_user']['id'], $filters);
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

            case 'getAllOffers':
                // Admin: Get all offers from all users
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
                
                $result = $offerService->getAllOffers($filters);
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
    } 
    // else {
    //     http_response_code(405);
    //     echo json_encode([
    //         'success' => false,
    //         'message' => 'Method not allowed',
    //         'allowed_methods' => ['POST']
    //     ]);
    // }
});
?>
