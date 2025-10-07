<?php
require_once('../connection/connection.php');
class OfferService extends config {
    /**
     * Create a new coin offer
     */
    public function createOffer($data, $userId) {
        try {
            $sql = "INSERT INTO tbl_coin_offers (
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
                    'message' => 'Offer created successfully',
                    'offer_id' => $this->pdo->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create offer'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating offer: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's offers
     */
    public function getUserOffers($userId, $filters = []) {
        try {
            $sql = "SELECT co.*, ct.denomination, ct.description, ct.image_path as coin_image_path
                    FROM tbl_coin_offers co 
                    JOIN tbl_coin_types ct ON co.coin_type_id = ct.id 
                    WHERE co.user_id = ?";
            
            $params = [$userId];
            
            // Add filters
            if (!empty($filters['status'])) {
                $sql .= " AND co.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['coin_type_id'])) {
                $sql .= " AND co.coin_type_id = ?";
                $params[] = $filters['coin_type_id'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (co.preferred_meeting_location LIKE ? OR co.notes LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY co.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching offers: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get offer by ID
     */
    public function getOfferById($offerId, $userId = null) {
        try {
            $sql = "SELECT co.*, ct.denomination, ct.description 
                    FROM tbl_coin_offers co 
                    JOIN tbl_coin_types ct ON co.coin_type_id = ct.id 
                    WHERE co.id = ?";
            
            $params = [$offerId];
            
            if ($userId) {
                $sql .= " AND co.user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $offer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($offer) {
                return [
                    'success' => true,
                    'data' => $offer
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Offer not found'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching offer: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update offer
     */
    public function updateOffer($offerId, $data, $userId) {
        try {
            
            // Validate required fields
            if (!isset($data['coin_type_id']) || empty($data['coin_type_id'])) {
                return [
                    'success' => false,
                    'message' => 'Coin type is required'
                ];
            }
            
            if (!isset($data['quantity']) || empty($data['quantity'])) {
                return [
                    'success' => false,
                    'message' => 'Quantity is required'
                ];
            }
            
            // Check if coin_type_id exists
            $coinTypeCheck = "SELECT id FROM tbl_coin_types WHERE id = ?";
            $coinTypeStmt = $this->pdo->prepare($coinTypeCheck);
            $coinTypeStmt->execute([$data['coin_type_id']]);
            
            if (!$coinTypeStmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Invalid coin type selected'
                ];
            }
            
            // Check if offer belongs to user
            $checkSql = "SELECT id FROM tbl_coin_offers WHERE id = ? AND user_id = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$offerId, $userId]);
            
            if (!$checkStmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Offer not found or access denied'
                ];
            }
            
            $sql = "UPDATE tbl_coin_offers SET 
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
                $offerId,
                $userId
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Offer updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update offer'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating offer: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete offer
     */
    public function deleteOffer($offerId, $userId) {
        try {
            $sql = "DELETE FROM tbl_coin_offers WHERE id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$offerId, $userId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Offer deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Offer not found or access denied'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error deleting offer: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel offer
     */
    public function cancelOffer($offerId, $userId) {
        try {
            $sql = "UPDATE tbl_coin_offers SET status = 'cancelled', updated_at = NOW() 
                    WHERE id = ? AND user_id = ? AND status = 'active'";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$offerId, $userId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Offer cancelled successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Offer not found or cannot be cancelled'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error cancelling offer: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get offer statistics
     */
    public function getOfferStats($userId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_offers,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_offers,
                        SUM(CASE WHEN status = 'matched' THEN 1 ELSE 0 END) as matched_offers,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_offers,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_offers
                    FROM tbl_coin_offers 
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
                'message' => 'Error fetching offer statistics: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all active offers (for browsing)
     */
    public function getActiveOffers($userid = null, $filters = []) {
        try {
            $sql = "SELECT co.*, ct.denomination, ct.description, 
                           up.business_name, up.business_type, up.address
                    FROM tbl_coin_offers co 
                    JOIN tbl_coin_types ct ON co.coin_type_id = ct.id 
                    LEFT JOIN tbl_user_profiles up ON co.user_id = up.user_id
                    WHERE co.status = 'active' ";
            
            $params = [];
            
            // Add filters
            if (!empty($filters['coin_type_id'])) {
                $sql .= " AND co.coin_type_id = ?";
                $params[] = $filters['coin_type_id'];
            }

            // Add filters
            if (!empty($userid)) {
                $sql .= " AND co.user_id = ?";
                $params[] = $userid;
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (co.preferred_meeting_location LIKE ? OR co.notes LIKE ? OR up.business_name LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY co.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching active offers: ' . $e->getMessage()
            ];
        }
    }
}
?>
