<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/../middleware/JWTMiddleware.php';
require_once('../services/NotificationService.php');

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
$notificationService = new NotificationService();

// SSE stream endpoint does not send JSON header
$isStream = isset($_GET['action']) && $_GET['action'] === 'stream';
if (!$isStream) {
	header('Content-Type: application/json');
}

$middleware->requireAuth(function() {
	global $notificationService, $isStream;

	$currentUser = $GLOBALS['current_user'];
	if (!$currentUser) {
		http_response_code(401);
		echo json_encode(['success' => false, 'message' => 'User not authenticated']);
		exit;
	}

	$userId = $GLOBALS['current_user']['id'];
	$method = $_SERVER['REQUEST_METHOD'];
	$action = $_GET['action'] ?? '';

	if ($method === 'GET') {
		if ($action === 'list') {
			$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
			$sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : null;
			$result = $notificationService->getUserNotifications($userId, $limit, $sinceId);
			echo json_encode($result);
			return;
		}

		if ($action === 'stream') {
			// Server-Sent Events (SSE)
			header('Content-Type: text/event-stream');
			header('Cache-Control: no-cache');
			header('Connection: keep-alive');
			@ob_end_flush();
			@ob_implicit_flush(1);

			$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : null;
			$startTime = time();
			$timeoutSeconds = 300; // 5 minutes per connection

			while (true) {
				// Break after timeout to allow client to reconnect
				if ((time() - $startTime) > $timeoutSeconds) {
					echo "event: ping\n";
					echo "data: {}\n\n";
					flush();
					break;
				}

				$result = $notificationService->getUserNotifications($userId, 50, $lastId);
				if ($result['success'] && !empty($result['data'])) {
					// Send newest first for consistency
					$rows = $result['data'];
					usort($rows, function($a, $b) { return $a['id'] <=> $b['id']; });
					foreach ($rows as $row) {
						$lastId = max($lastId ?? 0, (int)$row['id']);
						echo 'id: ' . $row['id'] . "\n";
						echo "event: notification\n";
						echo 'data: ' . json_encode($row) . "\n\n";
						flush();
					}
				}

				// Sleep briefly to avoid hammering DB
				usleep(800000); // 0.8s
			}
			return;
		}

		// Default unsupported GET
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Invalid action', 'available_actions' => ['list', 'stream']]);
		return;
	}

	if ($method === 'POST') {
		if ($action === 'markRead') {
			$payload = json_decode(file_get_contents('php://input'), true) ?: [];
			$notificationId = isset($payload['id']) ? (int)$payload['id'] : 0;
			if ($notificationId <= 0) {
				http_response_code(400);
				echo json_encode(['success' => false, 'message' => 'id is required']);
				return;
			}
			$result = $notificationService->markAsRead($userId, $notificationId);
			echo json_encode($result);
			return;
		}

		if ($action === 'markAllRead') {
			$result = $notificationService->markAllAsRead($userId);
			echo json_encode($result);
			return;
		}

		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Invalid action', 'available_actions' => ['markRead', 'markAllRead']]);
		return;
	}

	// http_response_code(405);
	// echo json_encode(['success' => false, 'message' => 'Method not allowed', 'allowed_methods' => ['GET', 'POST']]);
});

?>

