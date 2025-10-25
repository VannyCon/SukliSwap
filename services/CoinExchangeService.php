<?php
require_once('../connection/connection.php');

class CoinExchangeService extends config {
    
    // ============ COIN TYPES MANAGEMENT ============
    
    /**
     * Get all coin types
     */
    public function getCoinTypes() {
        try {
            $query = "SELECT * FROM tbl_coin_types WHERE is_active = 1 ORDER BY denomination ASC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get coin types error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new coin type
     */
    public function createCoinType($data) {
        try {
            $query = "INSERT INTO tbl_coin_types (denomination, description) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $data['denomination'],
                $data['description'] ?? null
            ]);

            return [
                'success' => true,
                'message' => 'Coin type created successfully',
                'id' => $this->pdo->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log("Create coin type error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create coin type: ' . $e->getMessage()
            ];
        }
    }

    // ============ COIN REQUESTS MANAGEMENT ============
    
    /**
     * Get all coin requests with pagination and filters
     */
    public function getCoinRequests($page = 1, $size = 12, $search = '', $status = '', $userId = null) {
        try {
            $page = max(1, (int)$page);
            $size = max(1, min(100, (int)$size));
            $offset = ($page - 1) * $size;

            $whereClauses = [];
            $params = [];
            
            if ($search !== null && $search !== '') {
                $whereClauses[] = "(u.username LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR ct.description LIKE :search)";
                $params[':search'] = "%" . $search . "%";
            }
            
            if ($status !== null && $status !== '') {
                $whereClauses[] = "cr.status = :status";
                $params[':status'] = $status;
            }
            
            if ($userId !== null) {
                $whereClauses[] = "cr.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $whereSql = count($whereClauses) ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

            $query = "SELECT cr.*, u.username, u.first_name, u.last_name, u.email,
                             ct.denomination, ct.description as coin_description,
                             up.business_name, up.business_type, ct.image_path as coin_image_path
                      FROM tbl_coin_requests cr
                      JOIN tbl_users u ON cr.user_id = u.id
                      JOIN tbl_coin_types ct ON cr.coin_type_id = ct.id
                      LEFT JOIN tbl_user_profiles up ON u.id = up.user_id
                      $whereSql
                      ORDER BY cr.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', (int)$size, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get coin requests error: " . $e->getMessage());
            return [];
        }
    }



    public function getAvailableCoinRequests($page = 1, $size = 12, $search = '', $status = '', $userId = null) {
        try {
            $page = max(1, (int)$page);
            $size = max(1, min(100, (int)$size));
            $offset = ($page - 1) * $size;

            $whereClauses = [];
            $params = [];
            
            if ($search !== null && $search !== '') {
                $whereClauses[] = "(u.username LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR ct.description LIKE :search)";
                $params[':search'] = "%" . $search . "%";
            }
            
            if ($status !== null && $status !== '') {
                $whereClauses[] = "cr.status = :status";
                $params[':status'] = $status;
            }
            
            if ($userId !== null) {
                $whereClauses[] = "cr.user_id != :user_id";
                $params[':user_id'] = $userId;
            }
            $whereClauses[] = "cr.quantity > 0";
            $whereSql = count($whereClauses) ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

            $query = "SELECT cr.*, u.username, u.first_name, u.last_name, u.email,
                             ct.denomination, ct.description as coin_description,
                             up.business_name, up.business_type
                      FROM tbl_coin_requests cr
                      JOIN tbl_users u ON cr.user_id = u.id
                      JOIN tbl_coin_types ct ON cr.coin_type_id = ct.id
                      LEFT JOIN tbl_user_profiles up ON u.id = up.user_id
                      $whereSql
                      ORDER BY cr.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', (int)$size, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get coin requests error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count coin requests
     */
    public function countCoinRequests($search = '', $status = '', $userId = null) {
        try {
            $whereClauses = [];
            $params = [];
            
            if ($search !== null && $search !== '') {
                $whereClauses[] = "(u.username LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR ct.description LIKE :search)";
                $params[':search'] = "%" . $search . "%";
            }
            
            if ($status !== null && $status !== '') {
                $whereClauses[] = "cr.status = :status";
                $params[':status'] = $status;
            }
            
            if ($userId !== null) {
                $whereClauses[] = "cr.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $whereSql = count($whereClauses) ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

            $query = "SELECT COUNT(*) as total
                      FROM tbl_coin_requests cr
                      JOIN tbl_users u ON cr.user_id = u.id
                      JOIN tbl_coin_types ct ON cr.coin_type_id = ct.id
                      $whereSql";
            
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Count coin requests error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Create a new coin request
     */
    public function createCoinRequest($data) {
        try {
            // Set default expiry time if not provided
            if (empty($data['expires_at'])) {
                $expiryHours = $this->getSystemSetting('default_request_expiry_hours', 24);
                $data['expires_at'] = date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours"));
            }

            $query = "INSERT INTO tbl_coin_requests 
                     (user_id, coin_type_id, quantity, preferred_meeting_location, 
                      meeting_latitude, meeting_longitude, meeting_radius, preferred_time, 
                      notes, expires_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $data['user_id'],
                $data['coin_type_id'],
                $data['quantity'],
                $data['preferred_meeting_location'] ?? null,
                $data['meeting_latitude'] ?? null,
                $data['meeting_longitude'] ?? null,
                $data['meeting_radius'] ?? 5000,
                $data['preferred_time'] ?? null,
                $data['notes'] ?? null,
                $data['expires_at']
            ]);

            $requestId = $this->pdo->lastInsertId();

            // Log activity
            $this->logActivity($data['user_id'], 'create_coin_request', 'coin_request', $requestId, [
                'coin_type_id' => $data['coin_type_id'],
                'quantity' => $data['quantity']
            ]);

            // Find potential matches
            $this->findMatchesForRequest($requestId);

            return [
                'success' => true,
                'message' => 'Coin request created successfully',
                'id' => $requestId
            ];
        } catch (PDOException $e) {
            error_log("Create coin request error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create coin request: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update coin request
     */
    public function updateCoinRequest($id, $data) {
        try {
            $query = "UPDATE tbl_coin_requests 
                     SET coin_type_id = ?, quantity = ?, preferred_meeting_location = ?, 
                         meeting_latitude = ?, meeting_longitude = ?, meeting_radius = ?, 
                         preferred_time = ?, notes = ?, expires_at = ? 
                     WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $data['coin_type_id'],
                $data['quantity'],
                $data['preferred_meeting_location'] ?? null,
                $data['meeting_latitude'] ?? null,
                $data['meeting_longitude'] ?? null,
                $data['meeting_radius'] ?? 5000,
                $data['preferred_time'] ?? null,
                $data['notes'] ?? null,
                $data['expires_at'] ?? null,
                $id
            ]);

            // Log activity
            $this->logActivity($data['user_id'] ?? null, 'update_coin_request', 'coin_request', $id);

            return [
                'success' => true,
                'message' => 'Coin request updated successfully'
            ];
        } catch (PDOException $e) {
            error_log("Update coin request error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update coin request: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete coin request
     */
    public function deleteCoinRequest($id) {
        try {
            $query = "DELETE FROM tbl_coin_requests WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Coin request deleted successfully'
            ];
        } catch (PDOException $e) {
            error_log("Delete coin request error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete coin request: ' . $e->getMessage()
            ];
        }
    }

    // ============ COIN OFFERS MANAGEMENT ============
    
    /**
     * Get all coin offers with pagination and filters
     */
    public function getCoinOffers($page = 1, $size = 12, $search = '', $status = '', $userId = null) {
        try {
            $page = max(1, (int)$page);
            $size = max(1, min(100, (int)$size));
            $offset = ($page - 1) * $size;

            $whereClauses = [];
            $params = [];
            
            if ($search !== null && $search !== '') {
                $whereClauses[] = "(u.username LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR ct.description LIKE :search)";
                $params[':search'] = "%" . $search . "%";
            }
            
            if ($status !== null && $status !== '') {
                $whereClauses[] = "co.status = :status";
                $params[':status'] = $status;
            }
            
            if ($userId !== null) {
                $whereClauses[] = "co.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $whereSql = count($whereClauses) ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

            $query = "SELECT co.*, u.username, u.first_name, u.last_name, u.email,
                             ct.denomination, ct.description as coin_description, ct.image_path as coin_image_path,
                             up.business_name, up.business_type, ct.description as coin_type_description
                      FROM tbl_coin_offers co
                      JOIN tbl_users u ON co.user_id = u.id
                      JOIN tbl_coin_types ct ON co.coin_type_id = ct.id
                      LEFT JOIN tbl_user_profiles up ON u.id = up.user_id
                      $whereSql
                      ORDER BY co.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', (int)$size, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get coin offers error: " . $e->getMessage());
            return [];
        }
    }


    public function getAvailableCoinOffers($page = 1, $size = 12, $search = '', $status = '', $userId = null) {
        try {
            $page = max(1, (int)$page);
            $size = max(1, min(100, (int)$size));
            $offset = ($page - 1) * $size;

            $whereClauses = [];
            $params = [];
            
            if ($search !== null && $search !== '') {
                $whereClauses[] = "(u.username LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR ct.description LIKE :search)";
                $params[':search'] = "%" . $search . "%";
            }
            
            if ($status !== null && $status !== '') {
                $whereClauses[] = "co.status = :status";
                $params[':status'] = $status;
            }
            
            if ($userId !== null) {
                $whereClauses[] = "co.user_id != :user_id";
                $params[':user_id'] = $userId;
            }
            $whereClauses[] = "co.quantity > 0";
            $whereSql = count($whereClauses) ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

            $query = "SELECT co.*, u.username, u.first_name, u.last_name, u.email,
                             ct.denomination, ct.description as coin_description,
                             up.business_name, up.business_type
                      FROM tbl_coin_offers co
                      JOIN tbl_users u ON co.user_id = u.id
                      JOIN tbl_coin_types ct ON co.coin_type_id = ct.id
                      LEFT JOIN tbl_user_profiles up ON u.id = up.user_id
                      $whereSql
                      ORDER BY co.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', (int)$size, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get coin offers error: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Count coin offers
     */
    public function countCoinOffers($search = '', $status = '', $userId = null) {
        try {
            $whereClauses = [];
            $params = [];
            
            if ($search !== null && $search !== '') {
                $whereClauses[] = "(u.username LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR ct.description LIKE :search)";
                $params[':search'] = "%" . $search . "%";
            }
            
            if ($status !== null && $status !== '') {
                $whereClauses[] = "co.status = :status";
                $params[':status'] = $status;
            }
            
            if ($userId !== null) {
                $whereClauses[] = "co.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $whereSql = count($whereClauses) ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

            $query = "SELECT COUNT(*) as total
                      FROM tbl_coin_offers co
                      JOIN tbl_users u ON co.user_id = u.id
                      JOIN tbl_coin_types ct ON co.coin_type_id = ct.id
                      $whereSql";
            
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Count coin offers error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Create a new coin offer
     */
    public function createCoinOffer($data) {
        try {
            // Set default expiry time if not provided
            if (empty($data['expires_at'])) {
                $expiryHours = $this->getSystemSetting('default_offer_expiry_hours', 24);
                $data['expires_at'] = date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours"));
            }

            $query = "INSERT INTO tbl_coin_offers 
                     (user_id, coin_type_id, quantity, preferred_meeting_location, 
                      meeting_latitude, meeting_longitude, meeting_radius, preferred_time, 
                      notes, expires_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $data['user_id'],
                $data['coin_type_id'],
                $data['quantity'],
                $data['preferred_meeting_location'] ?? null,
                $data['meeting_latitude'] ?? null,
                $data['meeting_longitude'] ?? null,
                $data['meeting_radius'] ?? 5000,
                $data['preferred_time'] ?? null,
                $data['notes'] ?? null,
                $data['expires_at']
            ]);

            $offerId = $this->pdo->lastInsertId();

            // Log activity
            $this->logActivity($data['user_id'], 'create_coin_offer', 'coin_offer', $offerId, [
                'coin_type_id' => $data['coin_type_id'],
                'quantity' => $data['quantity']
            ]);

            // Find potential matches
            $this->findMatchesForOffer($offerId);

            return [
                'success' => true,
                'message' => 'Coin offer created successfully',
                'id' => $offerId
            ];
        } catch (PDOException $e) {
            error_log("Create coin offer error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create coin offer: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update coin offer
     */
    public function updateCoinOffer($id, $data) {
        try {
            $query = "UPDATE tbl_coin_offers 
                     SET coin_type_id = ?, quantity = ?, preferred_meeting_location = ?, 
                         meeting_latitude = ?, meeting_longitude = ?, meeting_radius = ?, 
                         preferred_time = ?, notes = ?, expires_at = ? 
                     WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $data['coin_type_id'],
                $data['quantity'],
                $data['preferred_meeting_location'] ?? null,
                $data['meeting_latitude'] ?? null,
                $data['meeting_longitude'] ?? null,
                $data['meeting_radius'] ?? 5000,
                $data['preferred_time'] ?? null,
                $data['notes'] ?? null,
                $data['expires_at'] ?? null,
                $id
            ]);

            // Log activity
            $this->logActivity($data['user_id'] ?? null, 'update_coin_offer', 'coin_offer', $id);

            return [
                'success' => true,
                'message' => 'Coin offer updated successfully'
            ];
        } catch (PDOException $e) {
            error_log("Update coin offer error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update coin offer: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete coin offer
     */
    public function deleteCoinOffer($id) {
        try {
            $query = "DELETE FROM tbl_coin_offers WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Coin offer deleted successfully'
            ];
        } catch (PDOException $e) {
            error_log("Delete coin offer error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete coin offer: ' . $e->getMessage()
            ];
        }
    }

    // ============ MATCHING SYSTEM ============
    
    /**
     * Find matches for a specific request
     */
    public function findMatchesForRequest($requestId) {
        try {
            // Get request details
            $query = "SELECT user_id, coin_type_id, quantity, meeting_latitude, meeting_longitude, meeting_radius
                     FROM tbl_coin_requests 
                     WHERE id = ? AND status = 'active'";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                return ['success' => false, 'message' => 'Request not found or not active'];
            }

            // Find matching offers
            $query = "SELECT co.id, co.user_id, co.quantity, co.meeting_latitude, co.meeting_longitude,
                             ROUND(6371000 * ACOS(
                                 COS(RADIANS(?)) * 
                                 COS(RADIANS(co.meeting_latitude)) * 
                                 COS(RADIANS(co.meeting_longitude) - RADIANS(?)) + 
                                 SIN(RADIANS(?)) * 
                                 SIN(RADIANS(co.meeting_latitude))
                             )) AS distance_meters,
                             CASE 
                                 WHEN co.quantity >= ? THEN 100
                                 ELSE (co.quantity / ?) * 100
                             END AS quantity_match_score
                      FROM tbl_coin_offers co
                      WHERE co.coin_type_id = ?
                      AND co.status = 'active'
                      AND co.user_id != ?
                      AND co.quantity > 0
                      AND (co.expires_at IS NULL OR co.expires_at > NOW())
                      AND (
                          ? IS NULL OR ? IS NULL OR
                          ROUND(6371000 * ACOS(
                              COS(RADIANS(?)) * 
                              COS(RADIANS(co.meeting_latitude)) * 
                              COS(RADIANS(co.meeting_longitude) - RADIANS(?)) + 
                              SIN(RADIANS(?)) * 
                              SIN(RADIANS(co.meeting_latitude))
                          )) <= GREATEST(?, co.meeting_radius)
                      )
                      ORDER BY quantity_match_score DESC, distance_meters ASC
                      LIMIT 10";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $request['meeting_latitude'], $request['meeting_longitude'], $request['meeting_latitude'],
                $request['quantity'], $request['quantity'],
                $request['coin_type_id'], $request['user_id'],
                $request['meeting_latitude'], $request['meeting_longitude'],
                $request['meeting_latitude'], $request['meeting_longitude'], $request['meeting_latitude'],
                $request['meeting_radius']
            ]);

            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Create match records
            foreach ($matches as $match) {
                $matchScore = ($match['quantity_match_score'] * 0.7) + ((100 - min($match['distance_meters'] / 100, 100)) * 0.3);
                
                $insertQuery = "INSERT INTO tbl_matches 
                               (request_id, offer_id, match_score, distance, status) 
                               VALUES (?, ?, ?, ?, 'pending')
                               ON DUPLICATE KEY UPDATE 
                               match_score = VALUES(match_score), 
                               distance = VALUES(distance)";
                $insertStmt = $this->pdo->prepare($insertQuery);
                $insertStmt->execute([
                    $requestId,
                    $match['id'],
                    $matchScore,
                    $match['distance_meters']
                ]);
            }

            return [
                'success' => true,
                'message' => 'Matches found and created',
                'matches_count' => count($matches)
            ];
        } catch (PDOException $e) {
            error_log("Find matches for request error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to find matches: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Find matches for a specific offer
     */
    public function findMatchesForOffer($offerId) {
        try {
            // Get offer details
            $query = "SELECT user_id, coin_type_id, quantity, meeting_latitude, meeting_longitude, meeting_radius
                     FROM tbl_coin_offers 
                     WHERE id = ? AND status = 'active'";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$offerId]);
            $offer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$offer) {
                return ['success' => false, 'message' => 'Offer not found or not active'];
            }

            // Find matching requests
            $query = "SELECT cr.id, cr.user_id, cr.quantity, cr.meeting_latitude, cr.meeting_longitude,
                             ROUND(6371000 * ACOS(
                                 COS(RADIANS(?)) * 
                                 COS(RADIANS(cr.meeting_latitude)) * 
                                 COS(RADIANS(cr.meeting_longitude) - RADIANS(?)) + 
                                 SIN(RADIANS(?)) * 
                                 SIN(RADIANS(cr.meeting_latitude))
                             )) AS distance_meters,
                             CASE 
                                 WHEN ? >= cr.quantity THEN 100
                                 ELSE (? / cr.quantity) * 100
                             END AS quantity_match_score
                      FROM tbl_coin_requests cr
                      WHERE cr.coin_type_id = ?
                      AND cr.status = 'active'
                      AND cr.user_id != ?
                      AND cr.quantity > 0
                      AND (cr.expires_at IS NULL OR cr.expires_at > NOW())
                      AND (
                          ? IS NULL OR ? IS NULL OR
                          ROUND(6371000 * ACOS(
                              COS(RADIANS(?)) * 
                              COS(RADIANS(cr.meeting_latitude)) * 
                              COS(RADIANS(cr.meeting_longitude) - RADIANS(?)) + 
                              SIN(RADIANS(?)) * 
                              SIN(RADIANS(cr.meeting_latitude))
                          )) <= GREATEST(?, cr.meeting_radius)
                      )
                      ORDER BY quantity_match_score DESC, distance_meters ASC
                      LIMIT 10";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $offer['meeting_latitude'], $offer['meeting_longitude'], $offer['meeting_latitude'],
                $offer['quantity'], $offer['quantity'],
                $offer['coin_type_id'], $offer['user_id'],
                $offer['meeting_latitude'], $offer['meeting_longitude'],
                $offer['meeting_latitude'], $offer['meeting_longitude'], $offer['meeting_latitude'],
                $offer['meeting_radius']
            ]);

            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Create match records
            foreach ($matches as $match) {
                $matchScore = ($match['quantity_match_score'] * 0.7) + ((100 - min($match['distance_meters'] / 100, 100)) * 0.3);
                
                $insertQuery = "INSERT INTO tbl_matches 
                               (request_id, offer_id, match_score, distance, status) 
                               VALUES (?, ?, ?, ?, 'pending')
                               ON DUPLICATE KEY UPDATE 
                               match_score = VALUES(match_score), 
                               distance = VALUES(distance)";
                $insertStmt = $this->pdo->prepare($insertQuery);
                $insertStmt->execute([
                    $match['id'],
                    $offerId,
                    $matchScore,
                    $match['distance_meters']
                ]);
            }

            return [
                'success' => true,
                'message' => 'Matches found and created',
                'matches_count' => count($matches)
            ];
        } catch (PDOException $e) {
            error_log("Find matches for offer error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to find matches: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get matches for a user
     */
    public function getUserMatches($userId, $status = '') {
        try {
            $whereClause = "WHERE (cr.user_id = ? OR co.user_id = ?)";
            $params = [$userId, $userId];
            
            if ($status !== '') {
                $whereClause .= " AND m.status = ?";
                $params[] = $status;
            }

            $query = "SELECT m.*, 
                             cr.quantity as request_quantity, cr.preferred_meeting_location as request_location,
                             co.quantity as offer_quantity, co.preferred_meeting_location as offer_location,
                             ct.denomination, ct.description as coin_description,
                             requestor.username as requestor_username, requestor.first_name as requestor_first_name,
                             offeror.username as offeror_username, offeror.first_name as offeror_first_name
                      FROM tbl_matches m
                      JOIN tbl_coin_requests cr ON m.request_id = cr.id
                      JOIN tbl_coin_offers co ON m.offer_id = co.id
                      JOIN tbl_coin_types ct ON cr.coin_type_id = ct.id
                      JOIN tbl_users requestor ON cr.user_id = requestor.id
                      JOIN tbl_users offeror ON co.user_id = offeror.id
                      $whereClause
                      ORDER BY m.match_score DESC, m.created_at DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user matches error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Accept a match
     */
    public function acceptMatch($matchId, $userId) {
        try {
            $this->beginTransaction();

            // Verify user is part of the match
            $query = "SELECT m.*, cr.user_id as requestor_id, co.user_id as offeror_id
                     FROM tbl_matches m
                     JOIN tbl_coin_requests cr ON m.request_id = cr.id
                     JOIN tbl_coin_offers co ON m.offer_id = co.id
                     WHERE m.id = ? AND (cr.user_id = ? OR co.user_id = ?)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$matchId, $userId, $userId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$match) {
                $this->rollback();
                return ['success' => false, 'message' => 'Match not found or access denied'];
            }

            // Update match status
            $updateQuery = "UPDATE tbl_matches SET status = 'accepted' WHERE id = ?";
            $updateStmt = $this->pdo->prepare($updateQuery);
            $updateStmt->execute([$matchId]);

            // Create transaction
            $qrCode = $this->generateQRCode();
            $transactionQuery = "INSERT INTO tbl_transactions 
                               (match_id, requestor_id, offeror_id, coin_type_id, quantity, qr_code, status) 
                               VALUES (?, ?, ?, ?, ?, ?, 'scheduled')";
            $transactionStmt = $this->pdo->prepare($transactionQuery);
            $transactionStmt->execute([
                $matchId,
                $match['requestor_id'],
                $match['offeror_id'],
                $match['coin_type_id'],
                min($match['request_quantity'], $match['offer_quantity']),
                $qrCode
            ]);

            // Update request and offer status
            $updateRequestQuery = "UPDATE tbl_coin_requests SET status = 'matched' WHERE id = ?";
            $updateRequestStmt = $this->pdo->prepare($updateRequestQuery);
            $updateRequestStmt->execute([$match['request_id']]);

            $updateOfferQuery = "UPDATE tbl_coin_offers SET status = 'matched' WHERE id = ?";
            $updateOfferStmt = $this->pdo->prepare($updateOfferQuery);
            $updateOfferStmt->execute([$match['offer_id']]);

            $this->commit();

            // Log activity
            $this->logActivity($userId, 'accept_match', 'match', $matchId);

            return [
                'success' => true,
                'message' => 'Match accepted successfully',
                'transaction_id' => $this->pdo->lastInsertId(),
                'qr_code' => $qrCode
            ];
        } catch (PDOException $e) {
            $this->rollback();
            error_log("Accept match error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to accept match: ' . $e->getMessage()
            ];
        }
    }

    // ============ TRANSACTION MANAGEMENT ============
    
    /**
     * Get user transactions
     */
    public function getUserTransactions($userId, $status = '') {
        try {
            $whereClause = "WHERE (t.requestor_id = ? OR t.offeror_id = ?)";
            $params = [$userId, $userId];
            
            if ($status !== '') {
                $whereClause .= " AND t.status = ?";
                $params[] = $status;
            }

            $query = "SELECT t.*, ct.denomination, ct.description as coin_description,
                             requestor.username as requestor_username, requestor.first_name as requestor_first_name,
                             offeror.username as offeror_username, offeror.first_name as offeror_first_name
                      FROM tbl_transactions t
                      JOIN tbl_coin_types ct ON t.coin_type_id = ct.id
                      JOIN tbl_users requestor ON t.requestor_id = requestor.id
                      JOIN tbl_users offeror ON t.offeror_id = offeror.id
                      $whereClause
                      ORDER BY t.created_at DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user transactions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Complete transaction with QR code
     */
    public function completeTransaction($qrCode, $userId) {
        try {
            $this->beginTransaction();

            // Get transaction details
            $query = "SELECT t.*, cr.coin_type_id
                     FROM tbl_transactions t
                     JOIN tbl_matches m ON t.match_id = m.id
                     JOIN tbl_coin_requests cr ON m.request_id = cr.id
                     WHERE t.qr_code = ? AND t.status = 'scheduled'";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$qrCode]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                $this->rollback();
                return ['success' => false, 'message' => 'Invalid QR code or transaction not found'];
            }

            // Verify user is part of the transaction
            if ($transaction['requestor_id'] != $userId && $transaction['offeror_id'] != $userId) {
                $this->rollback();
                return ['success' => false, 'message' => 'Access denied'];
            }

            // Update transaction status
            $updateQuery = "UPDATE tbl_transactions 
                           SET status = 'completed', completion_time = NOW() 
                           WHERE id = ?";
            $updateStmt = $this->pdo->prepare($updateQuery);
            $updateStmt->execute([$transaction['id']]);

            $this->commit();

            // Log activity
            $this->logActivity($userId, 'complete_transaction', 'transaction', $transaction['id']);

            return [
                'success' => true,
                'message' => 'Transaction completed successfully'
            ];
        } catch (PDOException $e) {
            $this->rollback();
            error_log("Complete transaction error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to complete transaction: ' . $e->getMessage()
            ];
        }
    }

    // ============ UTILITY METHODS ============
    
    /**
     * Get system setting
     */
    private function getSystemSetting($key, $default = null) {
        try {
            $query = "SELECT setting_value FROM tbl_system_settings WHERE setting_key = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['setting_value'] : $default;
        } catch (PDOException $e) {
            error_log("Get system setting error: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Generate unique QR code
     */
    private function generateQRCode() {
        return 'SUKLI_' . uniqid() . '_' . time();
    }

}
?>
