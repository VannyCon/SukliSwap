<?php

require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/FileUploadService.php';

class MessagingService {
    private $conn;
    private $fileUploadService;

    public function __construct() {
        $this->conn = getPDOConnection();
        $this->fileUploadService = new FileUploadService();
    }

    /**
     * Send a message in a transaction conversation
     */
    public function sendMessage($transactionId, $senderId, $receiverId, $message, $messageType = 'text', $attachment = null, $replyToMessageId = null) {
        try {
            $this->conn->beginTransaction();

            // Validate transaction and participants
            if (!$this->validateTransactionParticipants($transactionId, $senderId, $receiverId)) {
                throw new Exception("Invalid transaction or participants");
            }

            // Check if conversation is still active
            if (!$this->isConversationActive($transactionId)) {
                throw new Exception("Conversation is closed for this transaction. Messaging is only available for active transactions.");
            }

            $attachmentPath = null;
            $attachmentOriginalName = null;
            $attachmentSize = null;

            // Handle file attachment
            if ($attachment && $messageType !== 'text') {
                $uploadResult = $this->fileUploadService->uploadMessageAttachment($attachment, $transactionId);
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }
                $attachmentPath = $uploadResult['file_path'];
                $attachmentOriginalName = $uploadResult['original_name'];
                $attachmentSize = $uploadResult['file_size'];
            }

            // Convert file path to URL for storage
            if ($attachmentPath) {
                $attachmentPath = str_replace('../data/documents/', '', $attachmentPath);
            }

            // Insert message
            $stmt = $this->conn->prepare("
                INSERT INTO tbl_messages 
                (transaction_id, sender_id, receiver_id, message, message_type, attachment_path, 
                 attachment_original_name, attachment_size, reply_to_message_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            if (!$stmt->execute([
                $transactionId, $senderId, $receiverId, $message, $messageType, 
                $attachmentPath, $attachmentOriginalName, $attachmentSize, $replyToMessageId
            ])) {
                throw new Exception("Failed to send message");
            }

            $messageId = $this->conn->lastInsertId();

            // Update last read status for sender
            $this->updateLastReadStatus($transactionId, $senderId);

            // Create notification for receiver
            $this->createMessageNotification($receiverId, $transactionId, $messageId);

            $this->conn->commit();

            return [
                'success' => true,
                'message_id' => $messageId,
                'message' => 'Message sent successfully'
            ];

        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get messages for a transaction conversation
     */
    public function getMessages($transactionId, $userId, $limit = 50, $offset = 0) {
        try {
            // Validate user is participant
            if (!$this->isUserParticipant($transactionId, $userId)) {
                throw new Exception("User is not a participant in this conversation");
            }

            $stmt = $this->conn->prepare("
                SELECT 
                    m.id,
                    m.sender_id,
                    m.receiver_id,
                    m.message,
                    m.message_type,
                    m.attachment_path,
                    m.attachment_original_name,
                    m.attachment_size,
                    m.is_read,
                    m.is_edited,
                    m.edited_at,
                    m.reply_to_message_id,
                    m.created_at,
                    u.first_name as sender_first_name,
                    u.last_name as sender_last_name,
                    u.profile_image as sender_profile_image,
                    reply.message as reply_message,
                    reply.message_type as reply_message_type
                FROM tbl_messages m
                JOIN tbl_users u ON m.sender_id = u.id
                LEFT JOIN tbl_messages reply ON m.reply_to_message_id = reply.id
                WHERE m.transaction_id = ?
                ORDER BY m.created_at DESC
                LIMIT " . intval($limit) . " OFFSET " . intval($offset)
            );

            $stmt->execute([$transactionId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Mark messages as read for current user
            $this->markMessagesAsRead($transactionId, $userId);

            return [
                'success' => true,
                'messages' => array_reverse($messages), // Return in chronological order
                'total' => $this->getMessageCount($transactionId)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }


    /**
     * Mark messages as read
     */
    public function markMessagesAsRead($transactionId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE tbl_messages 
                SET is_read = 1 
                WHERE transaction_id = ? AND receiver_id = ? AND is_read = 0
            ");

            $stmt->execute([$transactionId, $userId]);

            // Update last read status
            $this->updateLastReadStatus($transactionId, $userId);

            return ['success' => true];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get unread message count for user
     */
    public function getUnreadMessageCount($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as unread_count
                FROM tbl_messages m
                JOIN tbl_conversation_participants cp ON m.transaction_id = cp.transaction_id
                WHERE m.receiver_id = ? 
                AND m.is_read = 0 
                AND cp.user_id = ?
                AND cp.is_active = 1
            ");

            $stmt->execute([$userId, $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result['unread_count'];

            return [
                'success' => true,
                'unread_count' => $count
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete a message
     */
    public function deleteMessage($messageId, $userId, $reason = null) {
        try {
            $this->conn->beginTransaction();

            // Check if user owns the message
            $stmt = $this->conn->prepare("
                SELECT id, attachment_path FROM tbl_messages 
                WHERE id = ? AND sender_id = ?
            ");
            $stmt->execute([$messageId, $userId]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$message) {
                throw new Exception("Message not found or access denied");
            }

            // Log deletion
            $stmt = $this->conn->prepare("
                INSERT INTO tbl_message_deletions (message_id, deleted_by, reason)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$messageId, $userId, $reason]);

            // Delete attachment file if exists
            if ($message['attachment_path']) {
                $this->fileUploadService->deleteFile($message['attachment_path']);
            }

            // Soft delete message (replace content)
            $stmt = $this->conn->prepare("
                UPDATE tbl_messages 
                SET message = '[Message deleted]', 
                    message_type = 'text',
                    attachment_path = NULL,
                    attachment_original_name = NULL,
                    attachment_size = NULL
                WHERE id = ?
            ");
            $stmt->execute([$messageId]);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Message deleted successfully'
            ];

        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Add reaction to message
     */
    public function addReaction($messageId, $userId, $reaction) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO tbl_message_reactions (message_id, user_id, reaction)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE reaction = VALUES(reaction)
            ");

            $stmt->execute([$messageId, $userId, $reaction]);

            return [
                'success' => true,
                'message' => 'Reaction added successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get message reactions
     */
    public function getMessageReactions($messageId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    mr.reaction,
                    COUNT(*) as count,
                    GROUP_CONCAT(u.first_name, ' ', u.last_name) as users
                FROM tbl_message_reactions mr
                JOIN tbl_users u ON mr.user_id = u.id
                WHERE mr.message_id = ?
                GROUP BY mr.reaction
                ORDER BY count DESC
            ");

            $stmt->execute([$messageId]);
            $reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'reactions' => $reactions
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate transaction participants
     */
    private function validateTransactionParticipants($transactionId, $senderId, $receiverId) {
        $stmt = $this->conn->prepare("
            SELECT id FROM tbl_transactions 
            WHERE id = ? AND ((requestor_id = ? AND offeror_id = ?) OR (requestor_id = ? AND offeror_id = ?))
        ");
        $stmt->execute([$transactionId, $senderId, $receiverId, $receiverId, $senderId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false;
    }

    /**
     * Check if conversation is still active
     */
    private function isConversationActive($transactionId) {
        $stmt = $this->conn->prepare("
            SELECT status FROM tbl_transactions WHERE id = ?
        ");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $transaction && !in_array($transaction['status'], ['completed', 'cancelled', 'disputed']);
    }

    /**
     * Check if user is participant in conversation
     */
    private function isUserParticipant($transactionId, $userId) {
        $stmt = $this->conn->prepare("
            SELECT id FROM tbl_conversation_participants 
            WHERE transaction_id = ? AND user_id = ? AND is_active = 1
        ");
        $stmt->execute([$transactionId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false;
    }

    /**
     * Update last read status
     */
    private function updateLastReadStatus($transactionId, $userId) {
        $stmt = $this->conn->prepare("
            UPDATE tbl_conversation_participants 
            SET last_read_at = NOW() 
            WHERE transaction_id = ? AND user_id = ?
        ");
        $stmt->execute([$transactionId, $userId]);
    }

    /**
     * Create message notification
     */
    private function createMessageNotification($userId, $transactionId, $messageId) {
        $stmt = $this->conn->prepare("
            INSERT INTO tbl_notifications (user_id, type, title, message, data)
            VALUES (?, 'message', 'New Message', 'You have received a new message', ?)
        ");
        $data = json_encode(['transaction_id' => $transactionId, 'message_id' => $messageId]);
        $stmt->execute([$userId, $data]);
    }

    /**
     * Get message count for transaction
     */
    private function getMessageCount($transactionId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count FROM tbl_messages WHERE transaction_id = ?
        ");
        $stmt->execute([$transactionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
