<?php
require_once('../connection/connection.php');

class UserProfileService extends config {
    
    // ============ USER PROFILE MANAGEMENT ============
    
    /**
     * Get user profile
     */
    public function getUserProfile($userId) {
        try {
            $query = "SELECT up.*, u.username, u.email, u.first_name, u.last_name, u.phone, u.profile_image, u.is_verified
                     FROM tbl_user_profiles up
                     JOIN tbl_users u ON up.user_id = u.id
                     WHERE up.user_id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user profile error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create or update user profile
     */
    public function updateUserProfile($userId, $data) {
        try {
            // Check if profile exists
            $checkQuery = "SELECT id FROM tbl_user_profiles WHERE user_id = ?";
            $checkStmt = $this->pdo->prepare($checkQuery);
            $checkStmt->execute([$userId]);
            $profileExists = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($profileExists) {
                // Update existing profile
                $query = "UPDATE tbl_user_profiles 
                         SET business_name = ?, business_type = ?, address = ?, 
                             latitude = ?, longitude = ?, preferred_meeting_location = ?,
                             meeting_location_lat = ?, meeting_location_lng = ?, bio = ?
                         WHERE user_id = ?";
                $params = [
                    $data['business_name'] ?? null,
                    $data['business_type'] ?? null,
                    $data['address'] ?? null,
                    $data['latitude'] ?? null,
                    $data['longitude'] ?? null,
                    $data['preferred_meeting_location'] ?? null,
                    $data['meeting_location_lat'] ?? null,
                    $data['meeting_location_lng'] ?? null,
                    $data['bio'] ?? null,
                    $userId
                ];
            } else {
                // Create new profile
                $query = "INSERT INTO tbl_user_profiles 
                         (user_id, business_name, business_type, address, 
                          latitude, longitude, preferred_meeting_location,
                          meeting_location_lat, meeting_location_lng, bio) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $userId,
                    $data['business_name'] ?? null,
                    $data['business_type'] ?? null,
                    $data['address'] ?? null,
                    $data['latitude'] ?? null,
                    $data['longitude'] ?? null,
                    $data['preferred_meeting_location'] ?? null,
                    $data['meeting_location_lat'] ?? null,
                    $data['meeting_location_lng'] ?? null,
                    $data['bio'] ?? null
                ];
            }

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            return [
                'success' => true,
                'message' => 'Profile updated successfully'
            ];
        } catch (PDOException $e) {
            error_log("Update user profile error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStats($userId) {
        try {
            $query = "SELECT 
                         up.rating,
                         up.total_transactions,
                         (SELECT COUNT(*) FROM tbl_coin_requests WHERE user_id = ? AND status = 'active') as active_requests,
                         (SELECT COUNT(*) FROM tbl_coin_offers WHERE user_id = ? AND status = 'active') as active_offers,
                         (SELECT COUNT(*) FROM tbl_transactions WHERE requestor_id = ? OR offeror_id = ?) as total_transactions,
                         (SELECT COUNT(*) FROM tbl_transactions WHERE (requestor_id = ? OR offeror_id = ?) AND status = 'completed') as completed_transactions,
                         (SELECT COUNT(*) FROM tbl_transactions WHERE (requestor_id = ? OR offeror_id = ?) AND status = 'cancelled') as cancelled_transactions,
                         (SELECT COALESCE(AVG((requestor_rating + offeror_rating) / 2), 0) 
                          FROM tbl_transactions 
                          WHERE (requestor_id = ? OR offeror_id = ?) 
                          AND requestor_rating IS NOT NULL 
                          AND offeror_rating IS NOT NULL 
                          AND status = 'completed') as average_rating
                      FROM tbl_user_profiles up
                      WHERE up.user_id = ?";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $userId, $userId, $userId, $userId, $userId, $userId, 
                $userId, $userId, $userId, $userId, $userId
            ]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user stats error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get nearby users for matching
     */
    public function getNearbyUsers($latitude, $longitude, $radius = 5000, $excludeUserId = null) {
        try {
            $whereClause = "WHERE up.latitude IS NOT NULL AND up.longitude IS NOT NULL";
            $params = [$latitude, $longitude, $latitude, $radius];
            
            if ($excludeUserId) {
                $whereClause .= " AND up.user_id != ?";
                $params[] = $excludeUserId;
            }

            $query = "SELECT up.*, u.username, u.first_name, u.last_name, u.profile_image,
                             ROUND(6371000 * ACOS(
                                 COS(RADIANS(?)) * 
                                 COS(RADIANS(up.latitude)) * 
                                 COS(RADIANS(up.longitude) - RADIANS(?)) + 
                                 SIN(RADIANS(?)) * 
                                 SIN(RADIANS(up.latitude))
                             )) AS distance_meters
                      FROM tbl_user_profiles up
                      JOIN tbl_users u ON up.user_id = u.id
                      $whereClause
                      HAVING distance_meters <= ?
                      ORDER BY distance_meters ASC
                      LIMIT 50";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get nearby users error: " . $e->getMessage());
            return [];
        }
    }

    // ============ NOTIFICATION MANAGEMENT ============
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $limit = 20, $offset = 0) {
        try {
            $query = "SELECT * FROM tbl_notifications 
                     WHERE user_id = ? 
                     ORDER BY created_at DESC 
                     LIMIT ? OFFSET ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user notifications error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($notificationId, $userId) {
        try {
            $query = "UPDATE tbl_notifications 
                     SET is_read = 1 
                     WHERE id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$notificationId, $userId]);

            return [
                'success' => true,
                'message' => 'Notification marked as read'
            ];
        } catch (PDOException $e) {
            error_log("Mark notification as read error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to mark notification as read: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsAsRead($userId) {
        try {
            $query = "UPDATE tbl_notifications 
                     SET is_read = 1 
                     WHERE user_id = ? AND is_read = 0";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);

            return [
                'success' => true,
                'message' => 'All notifications marked as read'
            ];
        } catch (PDOException $e) {
            error_log("Mark all notifications as read error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to mark all notifications as read: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get unread notification count
     */
    public function getUnreadNotificationCount($userId) {
        try {
            $query = "SELECT COUNT(*) as count FROM tbl_notifications 
                     WHERE user_id = ? AND is_read = 0";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Get unread notification count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Create notification
     */
    public function createNotification($userId, $type, $title, $message, $data = null) {
        try {
            $query = "INSERT INTO tbl_notifications 
                     (user_id, type, title, message, data) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $userId,
                $type,
                $title,
                $message,
                $data ? json_encode($data) : null
            ]);

            return [
                'success' => true,
                'message' => 'Notification created successfully',
                'id' => $this->pdo->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log("Create notification error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create notification: ' . $e->getMessage()
            ];
        }
    }

    // ============ MESSAGING SYSTEM ============
    
    /**
     * Get transaction messages
     */
    public function getTransactionMessages($transactionId, $limit = 50) {
        try {
            $query = "SELECT m.*, u.username, u.first_name, u.last_name, u.profile_image
                     FROM tbl_messages m
                     JOIN tbl_users u ON m.sender_id = u.id
                     WHERE m.transaction_id = ?
                     ORDER BY m.created_at ASC
                     LIMIT ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$transactionId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get transaction messages error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Send message
     */
    public function sendMessage($transactionId, $senderId, $receiverId, $message) {
        try {
            $query = "INSERT INTO tbl_messages 
                     (transaction_id, sender_id, receiver_id, message) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$transactionId, $senderId, $receiverId, $message]);

            // Create notification for receiver
            $this->createNotification(
                $receiverId,
                'message',
                'New Message',
                'You have received a new message',
                ['transaction_id' => $transactionId, 'sender_id' => $senderId]
            );

            return [
                'success' => true,
                'message' => 'Message sent successfully',
                'id' => $this->pdo->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log("Send message error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead($transactionId, $userId) {
        try {
            $query = "UPDATE tbl_messages 
                     SET is_read = 1 
                     WHERE transaction_id = ? AND receiver_id = ? AND is_read = 0";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$transactionId, $userId]);

            return [
                'success' => true,
                'message' => 'Messages marked as read'
            ];
        } catch (PDOException $e) {
            error_log("Mark messages as read error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to mark messages as read: ' . $e->getMessage()
            ];
        }
    }

    // ============ RATING SYSTEM ============
    
    /**
     * Rate user after transaction
     */
    public function rateUser($transactionId, $raterId, $ratedUserId, $rating, $feedback = null) {
        try {
            // Verify transaction and user relationship
            $query = "SELECT id FROM tbl_transactions 
                     WHERE id = ? AND status = 'completed' 
                     AND (requestor_id = ? OR offeror_id = ?)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$transactionId, $raterId, $raterId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                return ['success' => false, 'message' => 'Transaction not found or not completed'];
            }

            // Update rating in transaction
            $isRequestor = $raterId == $transaction['requestor_id'];
            $ratingField = $isRequestor ? 'requestor_rating' : 'offeror_rating';
            $feedbackField = $isRequestor ? 'requestor_feedback' : 'offeror_feedback';

            $updateQuery = "UPDATE tbl_transactions 
                           SET $ratingField = ?, $feedbackField = ? 
                           WHERE id = ?";
            $updateStmt = $this->pdo->prepare($updateQuery);
            $updateStmt->execute([$rating, $feedback, $transactionId]);

            // Update user profile rating
            $profileQuery = "UPDATE tbl_user_profiles 
                            SET rating = (
                                SELECT COALESCE(AVG((t.requestor_rating + t.offeror_rating) / 2), 0)
                                FROM tbl_transactions t
                                WHERE (t.requestor_id = ? OR t.offeror_id = ?) 
                                AND t.status = 'completed'
                                AND t.requestor_rating IS NOT NULL 
                                AND t.offeror_rating IS NOT NULL
                            )
                            WHERE user_id = ?";
            $profileStmt = $this->pdo->prepare($profileQuery);
            $profileStmt->execute([$ratedUserId, $ratedUserId, $ratedUserId]);

            // Create notification for rated user
            $this->createNotification(
                $ratedUserId,
                'rating',
                'New Rating Received',
                "You received a {$rating}-star rating",
                ['transaction_id' => $transactionId, 'rating' => $rating]
            );

            return [
                'success' => true,
                'message' => 'Rating submitted successfully'
            ];
        } catch (PDOException $e) {
            error_log("Rate user error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to submit rating: ' . $e->getMessage()
            ];
        }
    }

    // ============ REPORTING SYSTEM ============
    
    /**
     * Create report
     */
    public function createReport($reporterId, $data) {
        try {
            $query = "INSERT INTO tbl_reports 
                     (reporter_id, reported_user_id, transaction_id, type, title, description) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $reporterId,
                $data['reported_user_id'] ?? null,
                $data['transaction_id'] ?? null,
                $data['type'],
                $data['title'],
                $data['description']
            ]);

            return [
                'success' => true,
                'message' => 'Report submitted successfully',
                'id' => $this->pdo->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log("Create report error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to submit report: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user reports
     */
    public function getUserReports($userId, $status = '') {
        try {
            $whereClause = "WHERE r.reporter_id = ?";
            $params = [$userId];
            
            if ($status !== '') {
                $whereClause .= " AND r.status = ?";
                $params[] = $status;
            }

            $query = "SELECT r.*, 
                             reported.username as reported_username,
                             resolved.username as resolved_by_username
                      FROM tbl_reports r
                      LEFT JOIN tbl_users reported ON r.reported_user_id = reported.id
                      LEFT JOIN tbl_users resolved ON r.resolved_by = resolved.id
                      $whereClause
                      ORDER BY r.created_at DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user reports error: " . $e->getMessage());
            return [];
        }
    }

    // ============ UTILITY METHODS ============
}
?>
