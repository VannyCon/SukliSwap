<?php
// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/../middleware/JWTMiddleware.php';
require_once('../services/CoinExchangeService.php');

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
$coinExchangeService = new CoinExchangeService();

// Handle guest-accessible (read-only) operations first
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $action = $_GET['action'] ?? '';
    error_log("GET action received: " . $action);

    switch ($action) {
        case 'getCoinTypes':
            $coinTypes = $coinExchangeService->getCoinTypes();
            echo json_encode([
                'success' => true,
                'data' => $coinTypes
            ]);
            exit;

        case 'getActiveRequests':
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $size = isset($_GET['size']) ? intval($_GET['size']) : 12;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : 'active';
            $requests = $coinExchangeService->getCoinRequests($page, $size, $search, $status);
            $total = $coinExchangeService->countCoinRequests($search, $status);
            $totalPages = $size > 0 ? (int)ceil($total / $size) : 1;
            echo json_encode([
                'success' => true,
                'data' => $requests,
                'meta' => [
                    'page' => $page,
                    'size' => $size,
                    'total' => $total,
                    'totalPages' => $totalPages,
                    'search' => $search
                ]
            ]);
            exit;


        case 'getActiveOffers':
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $size = isset($_GET['size']) ? intval($_GET['size']) : 12;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : 'active';
            $offers = $coinExchangeService->getCoinOffers($page, $size, $search, $status);
            $total = $coinExchangeService->countCoinOffers($search, $status);
            $totalPages = $size > 0 ? (int)ceil($total / $size) : 1;
            echo json_encode([
                'success' => true,
                'data' => $offers,
                'meta' => [
                    'page' => $page,
                    'size' => $size,
                    'total' => $total,
                    'totalPages' => $totalPages,
                    'search' => $search
                ]
            ]);
            exit;

        case 'getMapData':
            // Get active requests and offers for map display
            $requests = $coinExchangeService->getCoinRequests(1, 100, '', 'active');
            $offers = $coinExchangeService->getCoinOffers(1, 100, '', 'active');
            $coinTypes = $coinExchangeService->getCoinTypes();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'requests' => $requests,
                    'offers' => $offers,
                    'coin_types' => $coinTypes
                ]
            ]);
            exit;

        default:
            // Optionally handle unknown actions
            break;
    }
}

// Require authentication for all other coin exchange operations
$middleware->requireAuth(function() {
    error_log("Inside authenticated callback");
    global $coinExchangeService;
    
    // Handle different actions based on HTTP method
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'createCoinRequest':
                $data = $coinExchangeService->cleanArray($_POST);
                $data['user_id'] = $GLOBALS['current_user']['id'];
                $result = $coinExchangeService->createCoinRequest($data);
                echo json_encode($result);
                break;
                
            case 'updateCoinRequest':
                $id = $_POST['id'] ?? '';
                $data = $coinExchangeService->cleanArray($_POST);
                $data['user_id'] = $GLOBALS['current_user']['id'];
                $result = $coinExchangeService->updateCoinRequest($id, $data);
                echo json_encode($result);
                break;
                
            case 'deleteCoinRequest':
                $id = $_POST['id'] ?? '';
                $result = $coinExchangeService->deleteCoinRequest($id);
                echo json_encode($result);
                break;

            case 'createCoinOffer':
                $data = $coinExchangeService->cleanArray($_POST);
                $data['user_id'] = $GLOBALS['current_user']['id'];
                $result = $coinExchangeService->createCoinOffer($data);
                echo json_encode($result);
                break;
                
            case 'updateCoinOffer':
                $id = $_POST['id'] ?? '';
                $data = $coinExchangeService->cleanArray($_POST);
                $data['user_id'] = $GLOBALS['current_user']['id'];
                $result = $coinExchangeService->updateCoinOffer($id, $data);
                echo json_encode($result);
                break;
                
            case 'deleteCoinOffer':
                $id = $_POST['id'] ?? '';
                $result = $coinExchangeService->deleteCoinOffer($id);
                echo json_encode($result);
                break;

            case 'acceptMatch':
                $matchId = $_POST['match_id'] ?? '';
                $result = $coinExchangeService->acceptMatch($matchId, $GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;

            case 'completeTransaction':
                $qrCode = $_POST['qr_code'] ?? '';
                $result = $coinExchangeService->completeTransaction($qrCode, $GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid action',
                    'available_actions' => [
                        'Nothing'
                    ]
                ]);
                break;
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
        $action = $_GET['action'] ?? '';
        error_log("GET action received: " . $action);
        
        switch ($action) {
            case 'getAvailableRequests':
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $size = isset($_GET['size']) ? intval($_GET['size']) : 12;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $status = isset($_GET['status']) ? trim($_GET['status']) : 'active';
                $userId = isset($GLOBALS['current_user']['id']) ? intval($GLOBALS['current_user']['id']) : null;
                $requests = $coinExchangeService->getAvailableCoinRequests($page, $size, $search, $status, $userId);
                $total = $coinExchangeService->countCoinRequests($search, $status);
                $totalPages = $size > 0 ? (int)ceil($total / $size) : 1;
                echo json_encode([
                    'success' => true,
                    'data' => $requests,
                    'meta' => [
                        'page' => $page,
                        'size' => $size,
                        'total' => $total,
                        'totalPages' => $totalPages,
                        'search' => $search
                    ]
                ]);
                break;

            case 'getAvailableOffers':
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $size = isset($_GET['size']) ? intval($_GET['size']) : 12;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $status = isset($_GET['status']) ? trim($_GET['status']) : 'active';
                $userId = isset($GLOBALS['current_user']['id']) ? intval($GLOBALS['current_user']['id']) : null;
                $offers = $coinExchangeService->getAvailableCoinOffers($page, $size, $search, $status, $userId);
                $total = $coinExchangeService->countCoinOffers($search, $status);
                $totalPages = $size > 0 ? (int)ceil($total / $size) : 1;
                echo json_encode([
                    'success' => true,
                    'data' => $offers,
                    'meta' => [
                        'page' => $page,
                        'size' => $size,
                        'total' => $total,
                        'totalPages' => $totalPages,
                        'search' => $search
                    ]
                ]);
                break;

            case 'getMyRequests':
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $size = isset($_GET['size']) ? intval($_GET['size']) : 12;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $status = isset($_GET['status']) ? trim($_GET['status']) : '';
                $requests = $coinExchangeService->getCoinRequests($page, $size, $search, $status, $GLOBALS['current_user']['id']);
                $total = $coinExchangeService->countCoinRequests($search, $status, $GLOBALS['current_user']['id']);
                $totalPages = $size > 0 ? (int)ceil($total / $size) : 1;
                echo json_encode([
                    'success' => true,
                    'data' => $requests,
                    'meta' => [
                        'page' => $page,
                        'size' => $size,
                        'total' => $total,
                        'totalPages' => $totalPages,
                        'search' => $search
                    ]
                ]);
                break;

            case 'getMyOffers':
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $size = isset($_GET['size']) ? intval($_GET['size']) : 12;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $status = isset($_GET['status']) ? trim($_GET['status']) : '';
                $offers = $coinExchangeService->getCoinOffers($page, $size, $search, $status, $GLOBALS['current_user']['id']);
                $total = $coinExchangeService->countCoinOffers($search, $status, $GLOBALS['current_user']['id']);
                $totalPages = $size > 0 ? (int)ceil($total / $size) : 1;
                echo json_encode([
                    'success' => true,
                    'data' => $offers,
                    'meta' => [
                        'page' => $page,
                        'size' => $size,
                        'total' => $total,
                        'totalPages' => $totalPages,
                        'search' => $search
                    ]
                ]);
                break;

            case 'getMyMatches':
                $status = isset($_GET['status']) ? trim($_GET['status']) : '';
                $matches = $coinExchangeService->getUserMatches($GLOBALS['current_user']['id'], $status);
                echo json_encode([
                    'success' => true,
                    'data' => $matches
                ]);
                break;
                

            case 'getMyTransactions':
                $status = isset($_GET['status']) ? trim($_GET['status']) : '';
                $transactions = $coinExchangeService->getUserTransactions($GLOBALS['current_user']['id'], $status);
                echo json_encode([
                    'success' => true,
                    'data' => $transactions
                ]);
                break;

            case 'findMatches':
                $requestId = $_GET['request_id'] ?? '';
                if ($requestId) {
                    $result = $coinExchangeService->findMatchesForRequest($requestId);
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
                        'getMyRequests', 'getMyOffers', 'getMyMatches', 'getMyTransactions', 'findMatches'
                    ]
                ]);
                break;
        }
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed',
            'allowed_methods' => ['GET', 'POST']
        ]);
    }
});
?>
