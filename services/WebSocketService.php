<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../connection/connection.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class MessagingWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $userConnections;
    protected $transactionConnections;
    private $conn;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        $this->transactionConnections = [];
        $this->conn = getPDOConnection();
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['action'])) {
            $this->sendError($from, 'Invalid message format');
            return;
        }

        switch ($data['action']) {
            case 'authenticate':
                $this->handleAuthentication($from, $data);
                break;
            case 'join_transaction':
                $this->handleJoinTransaction($from, $data);
                break;
            case 'leave_transaction':
                $this->handleLeaveTransaction($from, $data);
                break;
            case 'send_message':
                $this->handleSendMessage($from, $data);
                break;
            case 'typing':
                $this->handleTyping($from, $data);
                break;
            case 'ping':
                $this->handlePing($from, $data);
                break;
            default:
                $this->sendError($from, 'Unknown action');
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->removeUserConnection($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    private function handleAuthentication(ConnectionInterface $conn, $data) {
        if (!isset($data['user_id']) || !isset($data['token'])) {
            $this->sendError($conn, 'Missing user_id or token');
            return;
        }

        // Validate JWT token (simplified - in production, use proper JWT validation)
        $userId = $data['user_id'];
        
        // Store user connection
        $this->userConnections[$userId] = $conn;
        $conn->userId = $userId;
        
        // Update connection status in database
        $this->updateConnectionStatus($userId, $conn->resourceId, 'connected');
        
        $this->sendMessage($conn, [
            'action' => 'authenticated',
            'user_id' => $userId,
            'message' => 'Successfully authenticated'
        ]);
    }

    private function handleJoinTransaction(ConnectionInterface $conn, $data) {
        if (!isset($data['transaction_id']) || !$conn->userId) {
            $this->sendError($conn, 'Missing transaction_id or not authenticated');
            return;
        }

        $transactionId = $data['transaction_id'];
        $userId = $conn->userId;

        // Validate user is participant in transaction
        if (!$this->isUserParticipant($transactionId, $userId)) {
            $this->sendError($conn, 'User is not a participant in this transaction');
            return;
        }

        // Add to transaction connections
        if (!isset($this->transactionConnections[$transactionId])) {
            $this->transactionConnections[$transactionId] = [];
        }
        $this->transactionConnections[$transactionId][$userId] = $conn;

        // Update connection status
        $this->updateConnectionStatus($userId, $conn->resourceId, 'connected', $transactionId);

        $this->sendMessage($conn, [
            'action' => 'joined_transaction',
            'transaction_id' => $transactionId,
            'message' => 'Successfully joined transaction conversation'
        ]);

        // Notify other participants
        $this->notifyTransactionParticipants($transactionId, $userId, [
            'action' => 'user_joined',
            'user_id' => $userId,
            'transaction_id' => $transactionId
        ]);
    }

    private function handleLeaveTransaction(ConnectionInterface $conn, $data) {
        if (!isset($data['transaction_id']) || !$conn->userId) {
            return;
        }

        $transactionId = $data['transaction_id'];
        $userId = $conn->userId;

        // Remove from transaction connections
        if (isset($this->transactionConnections[$transactionId][$userId])) {
            unset($this->transactionConnections[$transactionId][$userId]);
        }

        // Update connection status
        $this->updateConnectionStatus($userId, $conn->resourceId, 'disconnected', $transactionId);

        // Notify other participants
        $this->notifyTransactionParticipants($transactionId, $userId, [
            'action' => 'user_left',
            'user_id' => $userId,
            'transaction_id' => $transactionId
        ]);
    }

    private function handleSendMessage(ConnectionInterface $conn, $data) {
        if (!isset($data['transaction_id']) || !isset($data['message']) || !$conn->userId) {
            $this->sendError($conn, 'Missing required fields');
            return;
        }

        $transactionId = $data['transaction_id'];
        $userId = $conn->userId;
        $message = $data['message'];
        $messageType = $data['message_type'] ?? 'text';

        // Get receiver ID
        $receiverId = $this->getReceiverId($transactionId, $userId);
        if (!$receiverId) {
            $this->sendError($conn, 'Cannot determine receiver');
            return;
        }

        // Save message to database
        $messagingService = new MessagingService();
        $result = $messagingService->sendMessage($transactionId, $userId, $receiverId, $message, $messageType);

        if (!$result['success']) {
            $this->sendError($conn, $result['message']);
            return;
        }

        // Broadcast message to all participants in this transaction
        $broadcastData = [
            'action' => 'new_message',
            'transaction_id' => $transactionId,
            'message' => [
                'id' => $result['message_id'],
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'message' => $message,
                'message_type' => $messageType,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        $this->broadcastToTransaction($transactionId, $broadcastData);
    }

    private function handleTyping(ConnectionInterface $conn, $data) {
        if (!isset($data['transaction_id']) || !$conn->userId) {
            return;
        }

        $transactionId = $data['transaction_id'];
        $userId = $conn->userId;
        $isTyping = $data['is_typing'] ?? false;

        // Notify other participants
        $this->notifyTransactionParticipants($transactionId, $userId, [
            'action' => 'typing',
            'transaction_id' => $transactionId,
            'user_id' => $userId,
            'is_typing' => $isTyping
        ]);
    }

    private function handlePing(ConnectionInterface $conn, $data) {
        if ($conn->userId) {
            $this->updateConnectionStatus($conn->userId, $conn->resourceId, 'connected');
        }
        
        $this->sendMessage($conn, [
            'action' => 'pong',
            'timestamp' => time()
        ]);
    }

    private function isUserParticipant($transactionId, $userId) {
        $stmt = $this->conn->prepare("
            SELECT id FROM tbl_transactions 
            WHERE id = ? AND (requestor_id = ? OR offeror_id = ?)
        ");
        $stmt->execute([$transactionId, $userId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false;
    }

    private function getReceiverId($transactionId, $senderId) {
        $stmt = $this->conn->prepare("
            SELECT 
                CASE 
                    WHEN requestor_id = ? THEN offeror_id 
                    ELSE requestor_id 
                END as receiver_id
            FROM tbl_transactions 
            WHERE id = ? AND (requestor_id = ? OR offeror_id = ?)
        ");
        $stmt->execute([$senderId, $transactionId, $senderId, $senderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['receiver_id'] : null;
    }

    private function updateConnectionStatus($userId, $connectionId, $status, $transactionId = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO tbl_websocket_connections 
            (user_id, connection_id, transaction_id, connected_at, last_ping_at, is_active)
            VALUES (?, ?, ?, NOW(), NOW(), ?)
            ON DUPLICATE KEY UPDATE
            last_ping_at = NOW(),
            is_active = ?,
            transaction_id = COALESCE(?, transaction_id)
        ");
        $isActive = ($status === 'connected') ? 1 : 0;
        $stmt->execute([$userId, $connectionId, $transactionId, $isActive, $isActive, $transactionId]);
    }

    private function removeUserConnection(ConnectionInterface $conn) {
        if (isset($conn->userId)) {
            unset($this->userConnections[$conn->userId]);
            
            // Update connection status
            $this->updateConnectionStatus($conn->userId, $conn->resourceId, 'disconnected');
        }
    }

    private function notifyTransactionParticipants($transactionId, $excludeUserId, $message) {
        if (!isset($this->transactionConnections[$transactionId])) {
            return;
        }

        foreach ($this->transactionConnections[$transactionId] as $userId => $connection) {
            if ($userId !== $excludeUserId) {
                $this->sendMessage($connection, $message);
            }
        }
    }

    private function broadcastToTransaction($transactionId, $message) {
        if (!isset($this->transactionConnections[$transactionId])) {
            return;
        }

        foreach ($this->transactionConnections[$transactionId] as $userId => $connection) {
            echo "Sending message to connection for user: " . $userId . "\n";
            $this->sendMessage($connection, $message);
        }
    }

    private function sendMessage(ConnectionInterface $conn, $data) {
        $conn->send(json_encode($data));
    }

    private function sendError(ConnectionInterface $conn, $message) {
        $this->sendMessage($conn, [
            'action' => 'error',
            'message' => $message
        ]);
    }

    public function broadcastMessage($transactionId, $userId, $receiverId, $message, $messageType) {
        // Get the message ID from the database
        $stmt = $this->conn->prepare("SELECT id FROM tbl_messages WHERE transaction_id = ? AND sender_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$transactionId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            echo "Message not found in database for broadcasting\n";
            return;
        }

        $broadcastData = [
            'action' => 'new_message',
            'transaction_id' => $transactionId,
            'message' => [
                'id' => $result['id'],
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'message' => $message,
                'message_type' => $messageType,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->broadcastToTransaction($transactionId, $broadcastData);
    }
}

// WebSocket server startup script
if (php_sapi_name() === 'cli') {
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new MessagingWebSocket()
            )
        ),
        8080
    );

    echo "WebSocket server running on port 8080\n";
    $server->run();
}
