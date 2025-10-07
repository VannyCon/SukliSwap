<?php
require_once('../connection/connection.php');
class RequestService extends config {


    /**
     * Create a new coin request
     */
    public function createRequest($data, $userId) {
        try {
            $sql = "INSERT INTO tbl_coin_requests (
                user_id, coin_type_id, quantity, preferred_meeting_location, 
                meeting_longitude, meeting_latitude, notes, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $userId,
                $data['coin_type_id'],
                $data['quantity'],
                $data['preferred_meeting_location'] ?? null,
                $data['meeting_longitude'] ?? null,
                $data['meeting_latitude'] ?? null,
                $data['notes'] ?? null
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Request created successfully',
                    'request_id' => $this->pdo->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create request'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating request: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's requests
     */
    public function getUserRequests($userId, $filters = []) {
        try {
            $sql = "SELECT cr.*, ct.denomination, ct.description, ct.image_path as coin_image_path
                    FROM tbl_coin_requests cr 
                    JOIN tbl_coin_types ct ON cr.coin_type_id = ct.id 
                    WHERE cr.user_id = ?";
            
            $params = [$userId];
            
            // Add filters
            if (!empty($filters['status'])) {
                $sql .= " AND cr.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['coin_type_id'])) {
                $sql .= " AND cr.coin_type_id = ?";
                $params[] = $filters['coin_type_id'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (cr.preferred_meeting_location LIKE ? OR cr.notes LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY cr.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching requests: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get request by ID
     */
    public function getRequestById($requestId, $userId = null) {
        try {
            $sql = "SELECT cr.*, ct.denomination, ct.description 
                    FROM tbl_coin_requests cr 
                    JOIN tbl_coin_types ct ON cr.coin_type_id = ct.id 
                    WHERE cr.id = ?";
            
            $params = [$requestId];
            
            if ($userId) {
                $sql .= " AND cr.user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($request) {
                return [
                    'success' => true,
                    'data' => $request
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Request not found'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching request: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update request
     */
    public function updateRequest($requestId, $data, $userId) {
        try {
            // Check if request belongs to user
            $checkSql = "SELECT id FROM tbl_coin_requests WHERE id = ? AND user_id = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$requestId, $userId]);
            
            if (!$checkStmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Request not found or access denied'
                ];
            }
            
            $sql = "UPDATE tbl_coin_requests SET 
                    coin_type_id = ?, quantity = ?, preferred_meeting_location = ?, 
                    meeting_longitude = ?, meeting_latitude = ?, notes = ?, updated_at = NOW()
                    WHERE id = ? AND user_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['coin_type_id'],
                $data['quantity'],
                $data['preferred_meeting_location'] ?? null,
                $data['meeting_longitude'] ?? null,
                $data['meeting_latitude'] ?? null,
                $data['notes'] ?? null,
                $requestId,
                $userId
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Request updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update request'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating request: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete request
     */
    public function deleteRequest($requestId, $userId) {
        try {
            $sql = "DELETE FROM tbl_coin_requests WHERE id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$requestId, $userId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Request deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Request not found or access denied'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error deleting request: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel request
     */
    public function cancelRequest($requestId, $userId) {
        try {
            $sql = "UPDATE tbl_coin_requests SET status = 'cancelled', updated_at = NOW() 
                    WHERE id = ? AND user_id = ? AND status = 'active'";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$requestId, $userId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Request cancelled successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Request not found or cannot be cancelled'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error cancelling request: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get request statistics
     */
    public function getRequestStats($userId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_requests,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_requests,
                        SUM(CASE WHEN status = 'matched' THEN 1 ELSE 0 END) as matched_requests,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_requests
                    FROM tbl_coin_requests 
                    WHERE user_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $stats
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching request statistics: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all active requests (for browsing)
     */
    public function getActiveRequests($filters = []) {
        try {
            $sql = "SELECT cr.*, ct.denomination, ct.description, 
                           up.business_name, up.business_type, up.address
                    FROM tbl_coin_requests cr 
                    JOIN tbl_coin_types ct ON cr.coin_type_id = ct.id 
                    LEFT JOIN tbl_user_profiles up ON cr.user_id = up.user_id
                    WHERE cr.status = 'active'";
            
            $params = [];
            
            // Add filters
            if (!empty($filters['coin_type_id'])) {
                $sql .= " AND cr.coin_type_id = ?";
                $params[] = $filters['coin_type_id'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (cr.preferred_meeting_location LIKE ? OR cr.notes LIKE ? OR up.business_name LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY cr.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching active requests: ' . $e->getMessage()
            ];
        }
    }
}
?>
