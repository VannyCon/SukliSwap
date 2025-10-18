<?php
// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/../middleware/JWTMiddleware.php';
require_once('../services/TransactionService.php');

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
$transactionService = new TransactionService();

// All transaction operations require authentication
$middleware->requireAuth(function() {
    error_log("Inside authenticated callback");
    global $transactionService;
    
    // Handle all actions via POST method
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'createTransaction':
                $requestId = $_POST['request_id'] ?? '';
                $offerId = $_POST['offer_id'] ?? '';
                
                if (!$requestId || !$offerId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Request ID and Offer ID are required']);
                    break;
                }
                
                $result = $transactionService->createTransaction(
                    $requestId, 
                    $offerId, 
                    $GLOBALS['current_user']['id']
                );
                echo json_encode($result);
                break;

            case 'completeTransaction':
                $transactionId = $_POST['transaction_id'] ?? '';
                $completionNotes = $_POST['completion_notes'] ?? null;
                $rating = $_POST['rating'] ?? null;
                
                if (!$transactionId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Transaction ID is required']);
                    break;
                }
                
                $result = $transactionService->completeTransaction(
                    $transactionId,
                    $GLOBALS['current_user']['id'],
                    $completionNotes,
                    $rating
                );
                echo json_encode($result);
                break;

            case 'verifyAndComplete':
                $transactionId = $_POST['transaction_id'] ?? '';
                $qrCode = $_POST['qr_code'] ?? '';
                if (!$transactionId || !$qrCode) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Transaction ID and QR code are required']);
                    break;
                }
                $result = $transactionService->verifyAndComplete(
                    $transactionId,
                    $qrCode,
                    $GLOBALS['current_user']['id']
                );
                echo json_encode($result);
                break;

            case 'reportDispute':
                $transactionId = $_POST['transaction_id'] ?? '';
                $disputeReason = $_POST['dispute_reason'] ?? '';
                $disputeDescription = $_POST['dispute_description'] ?? '';
                
                if (!$transactionId || !$disputeReason || !$disputeDescription) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Transaction ID, dispute reason and description are required']);
                    break;
                }
                
                $result = $transactionService->reportDispute(
                    $transactionId,
                    $GLOBALS['current_user']['id'],
                    $disputeReason,
                    $disputeDescription
                );
                echo json_encode($result);
                break;
                

            case 'getTransactionById':
                $transactionId = $_GET['transaction_id'] ?? $_GET['id'] ?? '';
                if ($transactionId) {
                    $result = $transactionService->getTransactionById($transactionId, $GLOBALS['current_user']['id']);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Transaction ID is required'
                    ]);
                }
                break;

            case 'getTransactionStats':
                $result = $transactionService->getTransactionStats($GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;
                
            case 'updateTransactionStatus':
                $transactionId = $_POST['transaction_id'] ?? '';
                $status = $_POST['status'] ?? '';
                $notes = $_POST['notes'] ?? null;
                
                if (!$transactionId || !$status) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Transaction ID and status are required']);
                    break;
                }
                
                $result = $transactionService->updateTransactionStatus(
                    $transactionId,
                    $status,
                    $GLOBALS['current_user']['id'],
                    $notes
                );
                echo json_encode($result);
                break;
                
            case 'cancelTransaction':
                $transactionId = $_GET['transaction_id'] ?? $_GET['id'] ?? '';
                if ($transactionId) {
                    $result = $transactionService->cancelTransaction($transactionId, $GLOBALS['current_user']['id']);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Transaction ID is required'
                    ]);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid action',
                    'available_actions' => [
                        'createTransaction', 'completeTransaction', 'reportDispute',
                        'getMyTransactions', 'getTransactionById', 'getTransactionStats',
                        'updateTransactionStatus', 'cancelTransaction'
                    ]
                ]);
                break;
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'getMyTransactions':
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $size = isset($_GET['size']) ? intval($_GET['size']) : 12;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $status = isset($_GET['status']) ? trim($_GET['status']) : '';
                $type = isset($_GET['type']) ? trim($_GET['type']) : '';
                $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
                $dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
                
                $filters = [
                    'status' => $status,
                    'type' => $type,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'search' => $search
                ];
                
                $result = $transactionService->getUserTransactions($GLOBALS['current_user']['id'], $filters);
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

            case 'getAllTransactions':
                // Admin: Get all transactions from all users
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $size = isset($_GET['size']) ? intval($_GET['size']) : 12;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $status = isset($_GET['status']) ? trim($_GET['status']) : '';
                $type = isset($_GET['type']) ? trim($_GET['type']) : '';
                $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
                $dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
                
                $filters = [
                    'status' => $status,
                    'type' => $type,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'search' => $search
                ];
                
                $result = $transactionService->getAllTransactions($filters);
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
            case 'getTransactionById':
                $transactionId = $_GET['transaction_id'] ?? $_GET['id'] ?? '';
                if ($transactionId) {
                    $result = $transactionService->getTransactionById($transactionId, $GLOBALS['current_user']['id']);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Transaction ID is required'
                    ]);
                }
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
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
