<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../services/MessagingService.php';
require_once __DIR__ . '/../services/WebSocketService.php';
require_once __DIR__ . '/../middleware/JWTMiddleware.php';

// Initialize services
$messagingService = new MessagingService();
$websocketService = new MessagingWebSocket();
$jwtMiddleware = new JWTMiddleware();

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // Authenticate user
    $user = $jwtMiddleware->validateToken();
    if (!$user) {
        throw new Exception('Authentication required');
    }

    $userId = $user['id'];

    switch ($action) {
        case 'send_message':
            handleSendMessage($messagingService, $websocketService, $userId);
            break;
            
        case 'get_messages':
            handleGetMessages($messagingService, $userId);
            break;
            
            
        case 'mark_messages_read':
            handleMarkMessagesRead($messagingService, $userId);
            break;
            
        case 'get_unread_count':
            handleGetUnreadCount($messagingService, $userId);
            break;
            
        case 'delete_message':
            handleDeleteMessage($messagingService, $userId);
            break;
            
        case 'add_reaction':
            handleAddReaction($messagingService, $userId);
            break;
            
        case 'get_message_reactions':
            handleGetMessageReactions($messagingService, $userId);
            break;
            
        case 'get_user_conversations':
            handleGetUserConversations($messagingService, $userId);
            break;
            
        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleSendMessage($messagingService, $websocketService, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $transactionId = $_POST['transaction_id'] ?? null;
    $receiverId = $_POST['receiver_id'] ?? null;
    $message = $_POST['message'] ?? '';
    $messageType = $_POST['message_type'] ?? 'text';
    $replyToMessageId = $_POST['reply_to_message_id'] ?? null;

    if (!$transactionId || !$receiverId || !$message) {
        throw new Exception('Missing required fields');
    }

    // Handle file upload
    $attachment = null;
    if ($messageType === 'image' && isset($_FILES['attachment'])) {
        $attachment = $_FILES['attachment'];
    }

    $result = $messagingService->sendMessage(
        $transactionId, 
        $userId, 
        $receiverId, 
        $message, 
        $messageType, 
        $attachment, 
        $replyToMessageId
    );

    // If message was sent successfully, broadcast via WebSocket
    if ($result['success']) {
        $websocketService->broadcastMessage(
            $transactionId,
            $userId,
            $receiverId,
            $message,
            $messageType
        );
    }

    echo json_encode($result);
}

function handleGetMessages($messagingService, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed');
    }

    $transactionId = $_GET['transaction_id'] ?? null;
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;

    if (!$transactionId) {
        throw new Exception('Transaction ID is required');
    }

    $result = $messagingService->getMessages($transactionId, $userId, $limit, $offset);
    echo json_encode($result);
}


function handleMarkMessagesRead($messagingService, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $transactionId = $_POST['transaction_id'] ?? null;

    if (!$transactionId) {
        throw new Exception('Transaction ID is required');
    }

    $result = $messagingService->markMessagesAsRead($transactionId, $userId);
    echo json_encode($result);
}

function handleGetUnreadCount($messagingService, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed');
    }

    $result = $messagingService->getUnreadMessageCount($userId);
    echo json_encode($result);
}

function handleDeleteMessage($messagingService, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $messageId = $_POST['message_id'] ?? null;
    $reason = $_POST['reason'] ?? null;

    if (!$messageId) {
        throw new Exception('Message ID is required');
    }

    $result = $messagingService->deleteMessage($messageId, $userId, $reason);
    echo json_encode($result);
}

function handleAddReaction($messagingService, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $messageId = $_POST['message_id'] ?? null;
    $reaction = $_POST['reaction'] ?? null;

    if (!$messageId || !$reaction) {
        throw new Exception('Message ID and reaction are required');
    }

    $result = $messagingService->addReaction($messageId, $userId, $reaction);
    echo json_encode($result);
}

function handleGetMessageReactions($messagingService, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed');
    }

    $messageId = $_GET['message_id'] ?? null;

    if (!$messageId) {
        throw new Exception('Message ID is required');
    }

    $result = $messagingService->getMessageReactions($messageId);
    echo json_encode($result);
}

function handleGetUserConversations($messagingService, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed');
    }

    $limit = $_GET['limit'] ?? 20;
    $offset = $_GET['offset'] ?? 0;

    $conn = getConnection();
    
    $stmt = $conn->prepare("
        SELECT DISTINCT
            t.id as transaction_id,
            t.status as transaction_status,
            t.created_at as transaction_created_at,
            ct.denomination,
            ct.description as coin_description,
            t.quantity,
            CASE 
                WHEN t.requestor_id = ? THEN CONCAT(o.first_name, ' ', o.last_name)
                ELSE CONCAT(r.first_name, ' ', r.last_name)
            END as other_party_name,
            CASE 
                WHEN t.requestor_id = ? THEN o.profile_image
                ELSE r.profile_image
            END as other_party_image,
            CASE 
                WHEN t.requestor_id = ? THEN t.offeror_id
                ELSE t.requestor_id
            END as other_party_id,
            (
                SELECT m.message 
                FROM tbl_messages m 
                WHERE m.transaction_id = t.id 
                ORDER BY m.created_at DESC 
                LIMIT 1
            ) as last_message,
            (
                SELECT m.created_at 
                FROM tbl_messages m 
                WHERE m.transaction_id = t.id 
                ORDER BY m.created_at DESC 
                LIMIT 1
            ) as last_message_time,
            (
                SELECT COUNT(*) 
                FROM tbl_messages m 
                WHERE m.transaction_id = t.id 
                AND m.receiver_id = ? 
                AND m.is_read = 0
            ) as unread_count
        FROM tbl_transactions t
        JOIN tbl_coin_types ct ON t.coin_type_id = ct.id
        JOIN tbl_users r ON t.requestor_id = r.id
        JOIN tbl_users o ON t.offeror_id = o.id
        JOIN tbl_conversation_participants cp ON t.id = cp.transaction_id
        WHERE cp.user_id = ? AND cp.is_active = 1
        ORDER BY last_message_time DESC, t.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->bind_param("iiiiiii", $userId, $userId, $userId, $userId, $userId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $conversations = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'success' => true,
        'conversations' => $conversations
    ]);
}
