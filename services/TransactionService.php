<?php

require_once('../connection/connection.php');
require_once('../services/NotificationService.php');

class TransactionService extends config {

    /**
     * Create a new transaction (when request and offer are matched)
     */
    public function createTransaction($requestId, $offerId, $userId) {
        try {
            $this->pdo->beginTransaction();

            if ($requestId != null) {
                $requestSql = "SELECT * FROM tbl_coin_requests WHERE id = ? AND status = 'active'";
                $requestStmt = $this->pdo->prepare($requestSql);
                $requestStmt->execute([$requestId]);
                $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
            }else if ($offerId != null) {
                $offerSql = "SELECT * FROM tbl_coin_offers WHERE id = ? AND status = 'active'";
                $offerStmt = $this->pdo->prepare($offerSql);
                $offerStmt->execute([$offerId]);
                $offer = $offerStmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$request || !$offer) {
                $this->pdo->rollback();
                return [
                    'success' => false,
                    'message' => 'Request or offer not found or not active'
                ];
            }

            // Create transaction
            $transactionSql = "INSERT INTO tbl_transactions (
                match_id, requestor_id, offeror_id, coin_type_id, quantity, status, 
                qr_code, meeting_location, meeting_longitude, 
                meeting_latitude, created_at
            ) VALUES (?, ?, ?, ?, ?, 'scheduled', ?, ?, ?, ?, NOW())";

            // Generate QR code
            $qrCode = 'QR_' . time() . '_' . rand(1000, 9999);
            $quantity = min($request['quantity'], $offer['quantity']);
            
            $transactionStmt = $this->pdo->prepare($transactionSql);
            $result = $transactionStmt->execute([
                null, // match_id - will be set when match is created
                $request['user_id'],
                $offer['user_id'],
                $request['coin_type_id'],
                $quantity,
                $qrCode,
                $request['preferred_meeting_location'],
                $request['meeting_longitude'],
                $request['meeting_latitude']
            ]);

            if (!$result) {
                $this->pdo->rollback();
                return [
                    'success' => false,
                    'message' => 'Failed to create transaction'
                ];
            }

            $transactionId = $this->pdo->lastInsertId();

            // Update request and offer status
            $updateRequestSql = "UPDATE tbl_coin_requests SET status = 'matched', updated_at = NOW() WHERE id = ?";
            $updateOfferSql = "UPDATE tbl_coin_offers SET status = 'matched', updated_at = NOW() WHERE id = ?";

            $this->pdo->prepare($updateRequestSql)->execute([$requestId]);
            $this->pdo->prepare($updateOfferSql)->execute([$offerId]);

			$this->pdo->commit();

			// Notify both participants
			$notificationService = new NotificationService();
			$notificationData = [
				'transaction_id' => $transactionId,
				'coin_type_id' => $request['coin_type_id'],
				'quantity' => $quantity,
				'meeting_location' => $request['preferred_meeting_location']
			];
			$notificationService->createNotification(
				(int)$request['user_id'],
				'transaction',
				'Your Request Transaction Has Been Scheduled',
				'Your transaction has been scheduled.',
				$notificationData
			);
			$notificationService->createNotification(
				(int)$offer['user_id'],
				'transaction',
				'Your Offer Transaction Has Been Scheduled',
				'Your transaction has been scheduled.',
				$notificationData
			);

			return [
				'success' => true,
				'message' => 'Transaction created successfully',
				'transaction_id' => $transactionId
			];

        } catch (Exception $e) {
            $this->pdo->rollback();
            return [
                'success' => false,
                'message' => 'Error creating transaction: ' . $e->getMessage()
            ];
        }
    }


    public function createTROfferTransaction($requestId, $offerId, $userId, $scheduledMeetingTime = null) {
        try {

            // Get the transaction request
            $requestSql = "SELECT * FROM tbl_tr_coin_offer WHERE id = ?";
            $requestStmt = $this->pdo->prepare($requestSql);
            $requestStmt->execute([$requestId]);
            $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                return [
                    'success' => false,
                    'message' => 'Transaction request not found'
                ];
            }
            
            // Get the offer that the request is referencing
            $offerSql = "SELECT * FROM tbl_coin_offers WHERE id = ? AND status = 'active'";
            $offerStmt = $this->pdo->prepare($offerSql);
            $offerStmt->execute([$offerId]);
            $offer = $offerStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$offer) {
                return [
                    'success' => false,
                    'message' => 'Referenced offer not found or not active'
                ];
            }

            // Create transaction
            $transactionSql = "INSERT INTO tbl_transactions (
                isOffer, poser_id, coin_offers_id, requestor_id, offeror_id, coin_type_id, quantity, status, 
                qr_code, meeting_location, meeting_longitude, 
                meeting_latitude, scheduled_meeting_time, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled', ?, ?, ?, ?, ?, NOW())";

            // Generate QR code
            $qrCode = 'QR_' . time() . '_' . rand(1000, 9999);
            // $quantity = min($request['offered_quantity'],);
            
            $transactionStmt = $this->pdo->prepare($transactionSql);
            $result = $transactionStmt->execute([
                1,
                $userId,
                $offerId,
                $request['offeror_id'],      // requestor_id: person who made the request
                $offer['user_id'],           // offeror_id: person who made the offer
                $offer['coin_type_id'],
                $offer['quantity'],
                $qrCode,
                $offer['preferred_meeting_location'],
                $offer['meeting_longitude'],
                $offer['meeting_latitude'],
                $scheduledMeetingTime        // scheduled_meeting_time
            ]);

			$transactionId = $this->pdo->lastInsertId();

			// Notify both participants
			$notificationService = new NotificationService();
			$notificationData = [
				'transaction_id' => $transactionId,
				'coin_type_id' => $offer['coin_type_id'],
				'quantity' => $offer['quantity'],
				'meeting_location' => $offer['preferred_meeting_location']
			];
			$notificationService->createNotification(
				(int)$request['offeror_id'],
				'transaction',
				'Your Offer Transaction Has Been Scheduled',
				'Your transaction has been scheduled at '.$offer['preferred_meeting_location'].'.',
				$notificationData
			);
			$notificationService->createNotification(
				(int)$offer['user_id'],
				'transaction',
				'Your Offer Transaction Has Been Scheduled',
				'Your transaction has been scheduled at '.$offer['preferred_meeting_location'].'.',
				$notificationData
			);

			return [
				'success' => true,
				'message' => 'Transaction created successfully',
			];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating transaction: ' . $e->getMessage()
            ];
        }
    }

    public function createTRRequestTransaction($requestId, $offerId, $userId, $scheduledMeetingTime = null) {
        try {

            // Get the transaction request
            $requestSql = "SELECT * FROM tbl_tr_coin_request WHERE id = ?";
            $requestStmt = $this->pdo->prepare($requestSql);
            $requestStmt->execute([$requestId]);
            $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                return [
                    'success' => false,
                    'message' => 'Transaction request not found'
                ];
            }
            
            // Get the offer that the request is referencing
            $offerSql = "SELECT * FROM tbl_coin_requests WHERE id = ? AND status = 'active'";
            $offerStmt = $this->pdo->prepare($offerSql);
            $offerStmt->execute([$offerId]);
            $offer = $offerStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$offer) {
                return [
                    'success' => false,
                    'message' => 'Referenced request not found or not active'
                ];
            }

            // Create transaction
            $transactionSql = "INSERT INTO tbl_transactions (
                isOffer, poser_id, coin_requests_id, requestor_id, offeror_id, coin_type_id, quantity, status, 
                qr_code, meeting_location, meeting_longitude, 
                meeting_latitude, scheduled_meeting_time, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled', ?, ?, ?, ?, ?, NOW())";

            // Generate QR code
            $qrCode = 'QR_' . time() . '_' . rand(1000, 9999);
            // $quantity = min($request['offered_quantity'],);
            
            $transactionStmt = $this->pdo->prepare($transactionSql);
            $result = $transactionStmt->execute([
                0,
                $userId,
                $offerId,
                $request['requestor_id'],      // requestor_id: person who made the request
                $offer['user_id'],           // offeror_id: person who made the offer
                $offer['coin_type_id'],
                $offer['quantity'],
                $qrCode,
                $offer['preferred_meeting_location'],
                $offer['meeting_longitude'],
                $offer['meeting_latitude'],
                $scheduledMeetingTime        // scheduled_meeting_time
            ]);
            
			$transactionId = $this->pdo->lastInsertId();

			// Notify both participants
			$notificationService = new NotificationService();
			$notificationData = [
				'transaction_id' => $transactionId,
				'coin_type_id' => $offer['coin_type_id'],
				'quantity' => $offer['quantity'],
				'meeting_location' => $offer['preferred_meeting_location']
			];
			$notificationService->createNotification(
				(int)$request['requestor_id'],
				'transaction',
				'Your Request Transaction Has Been Scheduled',
				'Your transaction has been scheduled at '.$offer['preferred_meeting_location'].'.',
				$notificationData
			);
			$notificationService->createNotification(
				(int)$offer['user_id'],
				'transaction',
				'Your Offer Transaction Has Been Scheduled',
				'Your transaction has been scheduled at '.$offer['preferred_meeting_location'].'.',
				$notificationData
			);

			return [
				'success' => true,
				'message' => 'Transaction created successfully',
			];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating transaction: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's transactions
     */
    public function getUserTransactions($userId, $filters = []) {
        try {
            $sql = "SELECT t.*, 
                           ct.denomination, ct.description,
                           r.username as requestor_username, r.first_name as requestor_first_name, r.last_name as requestor_last_name,
                           o.username as offeror_username, o.first_name as offeror_first_name, o.last_name as offeror_last_name,
                           rp.business_name as requestor_business_name, rp.business_type as requestor_business_type,
                           op.business_name as offeror_business_name, op.business_type as offeror_business_type
                    FROM tbl_transactions t
                    JOIN tbl_coin_types ct ON t.coin_type_id = ct.id
                    JOIN tbl_users r ON t.requestor_id = r.id
                    JOIN tbl_users o ON t.offeror_id = o.id
                    LEFT JOIN tbl_user_profiles rp ON t.requestor_id = rp.user_id
                    LEFT JOIN tbl_user_profiles op ON t.offeror_id = op.user_id
                    WHERE (t.requestor_id = ? OR t.offeror_id = ?)";
            
            $params = [$userId, $userId];
            
            // Add filters
            if (!empty($filters['status'])) {
                $sql .= " AND t.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['type'])) {
                if ($filters['type'] === 'request') {
                    $sql .= " AND t.requestor_id = ?";
                    $params[] = $userId;
                } elseif ($filters['type'] === 'offer') {
                    $sql .= " AND t.offeror_id = ?";
                    $params[] = $userId;
                }
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(t.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(t.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (t.meeting_location LIKE ? OR r.username LIKE ? OR o.username LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY t.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching transactions: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all transactions from all users (Admin view)
     */
    public function getAllTransactions($filters = []) {
        try {
            $sql = "SELECT t.*, 
                           ct.denomination, ct.description,
                           r.username as requestor_username, r.first_name as requestor_first_name, r.last_name as requestor_last_name,
                           o.username as offeror_username, o.first_name as offeror_first_name, o.last_name as offeror_last_name,
                           rp.business_name as requestor_business_name, rp.business_type as requestor_business_type,
                           op.business_name as offeror_business_name, op.business_type as offeror_business_type
                    FROM tbl_transactions t
                    JOIN tbl_coin_types ct ON t.coin_type_id = ct.id
                    JOIN tbl_users r ON t.requestor_id = r.id
                    JOIN tbl_users o ON t.offeror_id = o.id
                    LEFT JOIN tbl_user_profiles rp ON t.requestor_id = rp.user_id
                    LEFT JOIN tbl_user_profiles op ON t.offeror_id = op.user_id
                    WHERE 1=1";
            
            $params = [];
            
            // Add filters
            if (!empty($filters['status'])) {
                $sql .= " AND t.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(t.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(t.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (t.meeting_location LIKE ? OR r.username LIKE ? OR o.username LIKE ? OR rp.business_name LIKE ? OR op.business_name LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY t.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching all transactions: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get transaction by ID
     */
    public function getTransactionById($transactionId, $userId = null) {
        try {
            $sql = "SELECT t.*, 
                           ct.denomination, ct.description,
                           r.username as requestor_username, r.first_name as requestor_first_name, r.last_name as requestor_last_name,
                           o.username as offeror_username, o.first_name as offeror_first_name, o.last_name as offeror_last_name,
                           rp.business_name as requestor_business_name, rp.business_type as requestor_business_type,
                           op.business_name as offeror_business_name, op.business_type as offeror_business_type
                    FROM tbl_transactions t
                    JOIN tbl_coin_types ct ON t.coin_type_id = ct.id
                    JOIN tbl_users r ON t.requestor_id = r.id
                    JOIN tbl_users o ON t.offeror_id = o.id
                    LEFT JOIN tbl_user_profiles rp ON t.requestor_id = rp.user_id
                    LEFT JOIN tbl_user_profiles op ON t.offeror_id = op.user_id
                    WHERE t.id = ?";
            
            $params = [$transactionId];
            
            if ($userId) {
                $sql .= " AND (t.requestor_id = ? OR t.offeror_id = ?)";
                $params[] = $userId;
                $params[] = $userId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($transaction) {
                return [
                    'success' => true,
                    'data' => $transaction
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Transaction not found'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching transaction: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update transaction status
     */
    public function updateTransactionStatus($transactionId, $status, $userId, $notes = null) {
        try {
            // Check if user is part of this transaction
            $checkSql = "SELECT id FROM tbl_transactions WHERE id = ? AND (requestor_id = ? OR offeror_id = ?)";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$transactionId, $userId, $userId]);
            
            if (!$checkStmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Transaction not found or access denied'
                ];
            }
            
            $sql = "UPDATE tbl_transactions SET status = ?, updated_at = NOW()";
            $params = [$status];

            if ($status === 'in_progress') {
                $sql .= ", actual_meeting_time = NOW()";
            }
            
            if ($notes) {
                $sql .= ", notes = ?";
                $params[] = $notes;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $transactionId;
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                // Get transaction details to notify the other party
                $transactionSql = "SELECT * FROM tbl_transactions WHERE id = ?";
                $transactionStmt = $this->pdo->prepare($transactionSql);
                $transactionStmt->execute([$transactionId]);
                $transaction = $transactionStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($transaction) {
                    // Determine who to notify (the other party)
                    $otherUserId = ($transaction['requestor_id'] == $userId) ? $transaction['offeror_id'] : $transaction['requestor_id'];
                    
                    // Create notification for status change
                    $notificationService = new NotificationService();
                    $notificationData = [
                        'transaction_id' => $transactionId,
                        'status' => $status,
                        'updated_by' => $userId
                    ];
                    
                    $statusMessages = [
                        'scheduled' => 'Transaction has been scheduled',
                        'in_progress' => 'Transaction is now in progress',
                        'completed' => 'Transaction has been completed',
                        'cancelled' => 'Transaction has been cancelled'
                    ];
                    
                    $message = $statusMessages[$status] ?? "Transaction status has been updated to {$status}";
                    
                    $notificationService->createNotification(
                        (int)$otherUserId,
                        'transaction_status',
                        'Transaction Status Update',
                        $message,
                        $notificationData
                    );
                }
                
                return [
                    'success' => true,
                    'message' => 'Transaction status updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update transaction status'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating transaction status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Complete transaction
     */
    public function completeTransaction($transactionId, $userId, $completionNotes = null, $rating = null) {
        try {
            $this->pdo->beginTransaction();

            // Check if user is part of this transaction and status allows completion
            $checkSql = "SELECT * FROM tbl_transactions WHERE id = ? AND (requestor_id = ? OR offeror_id = ?) AND status = 'in_progress'";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$transactionId, $userId, $userId]);
            $transaction = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                $this->pdo->rollback();
                return [
                    'success' => false,
                    'message' => 'Transaction not found, access denied, or cannot be completed'
                ];
            }

            // Update transaction
            $sql = "UPDATE tbl_transactions SET 
                    status = 'completed', 
                    completion_time = NOW(), 
                    updated_at = NOW() 
                    WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$transactionId]);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Transaction completed successfully'
            ];

        } catch (Exception $e) {
            $this->pdo->rollback();
            return [
                'success' => false,
                'message' => 'Error completing transaction: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify QR and complete transaction (sets actual_meeting_time if empty, then completion_time)
     */
    public function verifyAndComplete($transactionId, $qrCode, $userId) {
        try {
            $this->pdo->beginTransaction();

            // Fetch transaction and verify membership and status
            $sql = "SELECT * FROM tbl_transactions WHERE id = ? AND qr_code = ? AND (requestor_id = ? OR offeror_id = ?) AND status IN ('scheduled','in_progress')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$transactionId, $qrCode, $userId, $userId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                $this->pdo->rollback();
                return [
                    'success' => false,
                    'message' => 'Invalid QR or access denied'
                ];
            }

            // If scheduled, mark in_progress and set actual_meeting_time
            if ($transaction['status'] === 'scheduled') {
                $update = $this->pdo->prepare("UPDATE tbl_transactions SET status = 'in_progress', actual_meeting_time = NOW(), updated_at = NOW() WHERE id = ?");
                $update->execute([$transactionId]);
            }

            // Then complete
            $complete = $this->pdo->prepare("UPDATE tbl_transactions SET status = 'completed', completion_time = NOW(), updated_at = NOW() WHERE id = ?");
            $complete->execute([$transactionId]);

            $this->pdo->commit();
            return [
                'success' => true,
                'message' => 'Transaction verified and completed'
            ];
        } catch (Exception $e) {
            $this->pdo->rollback();
            return [
                'success' => false,
                'message' => 'Error verifying transaction: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Report transaction dispute
     */
    public function reportDispute($transactionId, $userId, $disputeReason, $disputeDescription) {
        try {
            // Check if user is part of this transaction
            $checkSql = "SELECT id FROM tbl_transactions WHERE id = ? AND (requestor_id = ? OR offeror_id = ?)";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$transactionId, $userId, $userId]);
            
            if (!$checkStmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Transaction not found or access denied'
                ];
            }
            
            $sql = "UPDATE tbl_transactions SET 
                    status = 'disputed', 
                    dispute_reason = ?, 
                    updated_at = NOW() 
                    WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $disputeReason,
                $transactionId
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Dispute reported successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to report dispute'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error reporting dispute: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get transaction statistics
     */
    public function getTransactionStats($userId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_transactions,
                        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_transactions,
                        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_transactions,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_transactions,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_transactions,
                        SUM(CASE WHEN status = 'disputed' THEN 1 ELSE 0 END) as disputed_transactions,
                        AVG((requestor_rating + offeror_rating) / 2) as average_rating
                    FROM tbl_transactions 
                    WHERE (requestor_id = ? OR offeror_id = ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $userId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $stats
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching transaction statistics: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel transaction
     */
    public function cancelTransaction($transactionId, $userId) {
        try {
            $this->pdo->beginTransaction();

            // Check if user is part of this transaction and can cancel
            $checkSql = "SELECT * FROM tbl_transactions WHERE id = ? AND (requestor_id = ? OR offeror_id = ?) AND status IN ('scheduled', 'in_progress')";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$transactionId, $userId, $userId]);
            $transaction = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                $this->pdo->rollback();
                return [
                    'success' => false,
                    'message' => 'Transaction not found, access denied, or cannot be cancelled'
                ];
            }

            // Update transaction
            $sql = "UPDATE tbl_transactions SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$transactionId]);

            // Note: In the actual schema, we don't have direct request_id and offer_id in transactions
            // The transaction is linked through match_id to tbl_matches table
            // This would need to be updated based on the actual matching system

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Transaction cancelled successfully'
            ];

        } catch (Exception $e) {
            $this->pdo->rollback();
            return [
                'success' => false,
                'message' => 'Error cancelling transaction: ' . $e->getMessage()
            ];
        }
    }
}
?>
