<?php
// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/../middleware/JWTMiddleware.php';
require_once('../services/AdminService.php');

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
$adminService = new AdminService();

// Require authentication and admin role for all admin operations
$middleware->requireAdmin(function() {
    error_log("Inside authenticated callback");
    global $adminService;
    
    // Get current user from token
    $currentUser = $GLOBALS['current_user'];
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        exit;
    }
    
    // Check if user is admin
    if ($currentUser['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Admin access required'
        ]);
        exit;
    }
    
    // Handle different actions based on HTTP method
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'updateUserStatus':
                $userId = $_POST['user_id'] ?? '';
                $status = $_POST['status'] ?? '';
                
                if (!$userId || !$status) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'User ID and status are required'
                    ]);
                    break;
                }
                
                $result = $adminService->updateUserStatus($userId, $status, $GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;

            case 'deleteUser':
                $userId = $_POST['user_id'] ?? '';
                
                if (!$userId) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'User ID is required'
                    ]);
                    break;
                }
                
                $result = $adminService->deleteUser($userId, $GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;

            case 'cancelTransaction':
                $transactionId = $_POST['transaction_id'] ?? '';
                $reason = $_POST['reason'] ?? '';
                
                if (!$transactionId || !$reason) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Transaction ID and reason are required'
                    ]);
                    break;
                }
                
                $result = $adminService->cancelTransaction($transactionId, $reason, $GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;

            case 'resolveReport':
                $reportId = $_POST['report_id'] ?? '';
                $status = $_POST['status'] ?? '';
                $adminNotes = $_POST['admin_notes'] ?? '';
                
                if (!$reportId || !$status) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Report ID and status are required'
                    ]);
                    break;
                }
                
                $result = $adminService->resolveReport($reportId, $status, $adminNotes, $GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;

            case 'verifyUser':
                $userId = $_POST['user_id'] ?? '';
                
                if (!$userId) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'User ID is required'
                    ]);
                    break;
                }
                
                $result = $adminService->verifyUser($userId, $GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;

            case 'unverifyUser':
                $userId = $_POST['user_id'] ?? '';
                
                if (!$userId) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'User ID is required'
                    ]);
                    break;
                }
                
                $result = $adminService->unverifyUser($userId, $GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;

            case 'declineUser':
                $userId = $_POST['user_id'] ?? '';
                
                if (!$userId) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'User ID is required'
                    ]);
                    break;
                }
                
                $result = $adminService->declineUser($userId, $GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;

            case 'updateSystemSetting':
                $key = $_POST['key'] ?? '';
                $value = $_POST['value'] ?? '';
                
                if (!$key || !$value) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Setting key and value are required'
                    ]);
                    break;
                }
                
                $result = $adminService->updateSystemSetting($key, $value, $GLOBALS['current_user']['id']);
                echo json_encode($result);
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid action',
                    'available_actions' => [
                        'updateUserStatus', 'deleteUser', 'cancelTransaction', 'resolveReport', 'updateSystemSetting'
                    ]
                ]);
                break;
        }
    } elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
        $action = $_GET['action'] ?? '';
        error_log("GET action received: " . $action);
        
        switch ($action) {
            case 'getDashboardStats':
                $stats = $adminService->getDashboardStats();
                if ($stats) {
                    echo json_encode([
                        'success' => true,
                        'data' => $stats
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to get dashboard stats'
                    ]);
                }
                break;

            case 'getRecentActivity':
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
                $activity = $adminService->getRecentActivity($limit);
                echo json_encode([
                    'success' => true,
                    'data' => $activity
                ]);
                break;

            case 'getAllUsers':
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $size = isset($_GET['size']) ? intval($_GET['size']) : 20;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';
                $role = isset($_GET['role']) ? trim($_GET['role']) : '';
                
                $users = $adminService->getAllUsers($page, $size, $search, $filter, $role);
                $total = $adminService->countUsers($search, $filter, $role);
                $totalPages = $size > 0 ? (int)ceil($total / $size) : 1;
                $statistics = $adminService->getUserStatistics();
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'users' => $users,
                        'statistics' => $statistics
                    ],
                    'meta' => [
                        'page' => $page,
                        'size' => $size,
                        'total' => $total,
                        'totalPages' => $totalPages,
                        'search' => $search,
                        'filter' => $filter
                    ]
                ]);
                break;

            case 'getUserDetails':
                $userId = $_GET['user_id'] ?? '';
                
                if (!$userId) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'User ID is required'
                    ]);
                    break;
                }
                
                $user = $adminService->getUserDetails($userId);
                
                if ($user) {
                    echo json_encode([
                        'success' => true,
                        'data' => $user
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'User not found'
                    ]);
                }
                break;

            case 'exportUsers':
                $format = $_GET['format'] ?? 'csv';
                
                $exportData = $adminService->exportUsers($format);
                
                if ($exportData) {
                    echo json_encode([
                        'success' => true,
                        'data' => $exportData
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to export users'
                    ]);
                }
                break;

            case 'getAllTransactions':
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $size = isset($_GET['size']) ? intval($_GET['size']) : 20;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $status = isset($_GET['status']) ? trim($_GET['status']) : '';
                
                $transactions = $adminService->getAllTransactions($page, $size, $search, $status);
                $total = $adminService->countTransactions($search, $status);
                $totalPages = $size > 0 ? (int)ceil($total / $size) : 1;
                
                echo json_encode([
                    'success' => true,
                    'data' => $transactions,
                    'meta' => [
                        'page' => $page,
                        'size' => $size,
                        'total' => $total,
                        'totalPages' => $totalPages,
                        'search' => $search
                    ]
                ]);
                break;

            case 'getAllReports':
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $size = isset($_GET['size']) ? intval($_GET['size']) : 20;
                $status = isset($_GET['status']) ? trim($_GET['status']) : '';
                
                $reports = $adminService->getAllReports($page, $size, $status);
                $total = $adminService->countReports($status);
                $totalPages = $size > 0 ? (int)ceil($total / $size) : 1;
                
                echo json_encode([
                    'success' => true,
                    'data' => $reports,
                    'meta' => [
                        'page' => $page,
                        'size' => $size,
                        'total' => $total,
                        'totalPages' => $totalPages
                    ]
                ]);
                break;

            case 'getSystemSettings':
                $settings = $adminService->getSystemSettings();
                echo json_encode([
                    'success' => true,
                    'data' => $settings
                ]);
                break;

            case 'generateAnalyticsReport':
                $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
                $endDate = $_GET['end_date'] ?? date('Y-m-d');
                
                $report = $adminService->generateAnalyticsReport($startDate, $endDate);
                echo json_encode([
                    'success' => true,
                    'data' => $report
                ]);
                break;

            case 'getTransactionVolumeByCoinType':
                $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
                $endDate = $_GET['end_date'] ?? date('Y-m-d');
                
                $volume = $adminService->getTransactionVolumeByCoinType($startDate, $endDate);
                echo json_encode([
                    'success' => true,
                    'data' => $volume
                ]);
                break;

            case 'getUserActivitySummary':
                $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
                $endDate = $_GET['end_date'] ?? date('Y-m-d');
                
                $summary = $adminService->getUserActivitySummary($startDate, $endDate);
                echo json_encode([
                    'success' => true,
                    'data' => $summary
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid action',
                    'available_actions' => [
                        'getDashboardStats', 'getRecentActivity', 'getAllUsers', 'getAllTransactions',
                        'getAllReports', 'getSystemSettings', 'generateAnalyticsReport',
                        'getTransactionVolumeByCoinType', 'getUserActivitySummary'
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
