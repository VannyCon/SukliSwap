<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/../middleware/JWTMiddleware.php';
require_once('../services/TrCoinOfferService.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

$middleware = new JWTMiddleware();
header('Content-Type: application/json');
$svc = new TrCoinOfferService();

// Public reads: list offers for a request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$action = $_GET['action'] ?? '';
    switch ($action) {
        case 'listByPostOffer':
            $postOfferId = intval($_GET['post_offer_id'] ?? 0);
            $result = $svc->listByPostOffer($postOfferId, 0);
			echo json_encode($result);
			break;
		default:
			echo json_encode(['success' => false, 'message' => 'Invalid action']);
	}
	exit;
}

// Auth-required actions
$middleware->requireAuth(function() {
	global $svc;
	$action = $_GET['action'] ?? '';
	$userId = $GLOBALS['current_user']['id'];

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		echo json_encode(['success' => false, 'message' => 'Method not allowed']);
		return;
	}

	switch ($action) {
		case 'send':
			$data = $svc->cleanArray($_POST);
			echo json_encode($svc->create($data, $userId));
			break;
		case 'myOffers':
			echo json_encode($svc->listMine($userId));
			break;
		case 'accept':
			$id = intval($_POST['id'] ?? 0);
			$scheduledMeetingTime = $_POST['scheduled_meeting_time'] ?? null;
			$quantity = intval($_POST['meeting_quantity'] ?? 0);
			echo json_encode($svc->accept($id, $userId, $scheduledMeetingTime, $quantity));
			break;
		case 'reject':
			$id = intval($_POST['id'] ?? 0);
			echo json_encode($svc->reject($id, $userId));
			break;
		case 'cancel':
			$id = intval($_POST['id'] ?? 0);
			echo json_encode($svc->cancel($id, $userId));
			break;
		default:
			echo json_encode(['success' => false, 'message' => 'Invalid action']);
	}
});

?>


