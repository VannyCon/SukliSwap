<?php
require_once('../connection/connection.php');

class SafePlaceService extends config {
    
    // ============ SAFE PLACE CRUD OPERATIONS ============
    
    /**
     * Create a new safe place
     */
    public function createSafePlace($data, $created_by) {
        try {
            $query = "INSERT INTO tbl_safeplace (lat, `long`, location_name, description, created_by) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $data['lat'],
                $data['long'],
                $data['location_name'],
                $data['description'] ?? null,
                $created_by
            ]);

            return [
                'success' => true,
                'message' => 'Safe place created successfully',
                'id' => $this->pdo->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log("Create safe place error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create safe place: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all safe places
     */
    public function getAllSafePlaces($activeOnly = true) {
        try {
            $whereClause = $activeOnly ? "WHERE is_active = 1" : "";
            $query = "SELECT sp.*, u.username as created_by_username 
                     FROM tbl_safeplace sp
                     LEFT JOIN tbl_users u ON sp.created_by = u.id
                     $whereClause
                     ORDER BY sp.created_at DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all safe places error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get safe place by ID
     */
    public function getSafePlaceById($id) {
        try {
            $query = "SELECT sp.*, u.username as created_by_username 
                     FROM tbl_safeplace sp
                     LEFT JOIN tbl_users u ON sp.created_by = u.id
                     WHERE sp.id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get safe place by ID error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update safe place
     */
    public function updateSafePlace($id, $data) {
        try {
            $query = "UPDATE tbl_safeplace 
                     SET lat = ?, `long` = ?, location_name = ?, description = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $data['lat'],
                $data['long'],
                $data['location_name'],
                $data['description'] ?? null,
                $id
            ]);

            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Safe place updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Safe place not found or no changes made'
                ];
            }
        } catch (PDOException $e) {
            error_log("Update safe place error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update safe place: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete safe place (soft delete by setting is_active = 0)
     */
    public function deleteSafePlace($id) {
        try {
            $query = "UPDATE tbl_safeplace 
                     SET is_active = 0, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Safe place deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Safe place not found'
                ];
            }
        } catch (PDOException $e) {
            error_log("Delete safe place error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete safe place: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Restore safe place (set is_active = 1)
     */
    public function restoreSafePlace($id) {
        try {
            $query = "UPDATE tbl_safeplace 
                     SET is_active = 1, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Safe place restored successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Safe place not found'
                ];
            }
        } catch (PDOException $e) {
            error_log("Restore safe place error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to restore safe place: ' . $e->getMessage()
            ];
        }
    }

    // ============ LOCATION-BASED QUERIES ============

    /**
     * Get safe places within a radius of given coordinates
     */
    public function getSafePlacesNearby($latitude, $longitude, $radius = 5000) {
        try {
            $query = "SELECT sp.*, u.username as created_by_username,
                             ROUND(6371000 * ACOS(
                                 COS(RADIANS(?)) * 
                                 COS(RADIANS(sp.lat)) * 
                                 COS(RADIANS(sp.`long`) - RADIANS(?)) + 
                                 SIN(RADIANS(?)) * 
                                 SIN(RADIANS(sp.lat))
                             )) AS distance_meters
                      FROM tbl_safeplace sp
                      LEFT JOIN tbl_users u ON sp.created_by = u.id
                      WHERE sp.is_active = 1
                      HAVING distance_meters <= ?
                      ORDER BY distance_meters ASC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$latitude, $longitude, $latitude, $radius]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get nearby safe places error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get safe places within a bounding box
     */
    public function getSafePlacesInBounds($north, $south, $east, $west) {
        try {
            $query = "SELECT sp.*, u.username as created_by_username
                     FROM tbl_safeplace sp
                     LEFT JOIN tbl_users u ON sp.created_by = u.id
                     WHERE sp.is_active = 1
                     AND sp.lat BETWEEN ? AND ?
                     AND sp.`long` BETWEEN ? AND ?
                     ORDER BY sp.created_at DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$south, $north, $west, $east]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get safe places in bounds error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search safe places by location name
     */
    public function searchSafePlaces($searchTerm, $activeOnly = true) {
        try {
            $whereClause = $activeOnly ? "WHERE sp.is_active = 1 AND" : "WHERE";
            $query = "SELECT sp.*, u.username as created_by_username
                     FROM tbl_safeplace sp
                     LEFT JOIN tbl_users u ON sp.created_by = u.id
                     $whereClause (sp.location_name LIKE ? OR sp.description LIKE ?)
                     ORDER BY sp.created_at DESC";

            $searchPattern = "%$searchTerm%";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$searchPattern, $searchPattern]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Search safe places error: " . $e->getMessage());
            return [];
        }
    }

    // ============ STATISTICS AND ANALYTICS ============

    /**
     * Get safe place statistics
     */
    public function getSafePlaceStats() {
        try {
            $query = "SELECT 
                         COUNT(*) as total_safe_places,
                         COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_safe_places,
                         COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_safe_places,
                         COUNT(DISTINCT created_by) as unique_creators,
                         MIN(created_at) as first_created,
                         MAX(created_at) as last_created
                      FROM tbl_safeplace";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get safe place stats error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get safe places created by user
     */
    public function getSafePlacesByUser($userId) {
        try {
            $query = "SELECT sp.*, u.username as created_by_username
                     FROM tbl_safeplace sp
                     LEFT JOIN tbl_users u ON sp.created_by = u.id
                     WHERE sp.created_by = ?
                     ORDER BY sp.created_at DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get safe places by user error: " . $e->getMessage());
            return [];
        }
    }

    // ============ MAPLIBRE INTEGRATION HELPERS ============

    /**
     * Get safe places formatted for MapLibre GeoJSON
     */
    public function getSafePlacesForMapLibre($activeOnly = true) {
        try {
            $whereClause = $activeOnly ? "WHERE sp.is_active = 1" : "";
            $query = "SELECT sp.id, sp.lat, sp.`long`, sp.location_name, sp.description, 
                             sp.is_active, sp.created_by, sp.created_at, sp.updated_at,
                             u.username as created_by_username
                     FROM tbl_safeplace sp
                     LEFT JOIN tbl_users u ON sp.created_by = u.id
                     $whereClause
                     ORDER BY sp.created_at DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $safePlaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convert to GeoJSON format for MapLibre
            $features = [];
            foreach ($safePlaces as $place) {
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [(float)$place['long'], (float)$place['lat']]
                    ],
                    'properties' => [
                        'id' => $place['id'],
                        'name' => $place['location_name'],
                        'description' => $place['description'],
                        'is_active' => $place['is_active'],
                        'created_by' => $place['created_by'],
                        'created_by_username' => $place['created_by_username'],
                        'created_at' => $place['created_at'],
                        'updated_at' => $place['updated_at']
                    ]
                ];
            }

            return [
                'type' => 'FeatureCollection',
                'features' => $features
            ];
        } catch (PDOException $e) {
            error_log("Get safe places for MapLibre error: " . $e->getMessage());
            return [
                'type' => 'FeatureCollection',
                'features' => []
            ];
        }
    }

    // ============ VALIDATION HELPERS ============

    /**
     * Validate safe place data
     */
    public function validateSafePlaceData($data) {
        $errors = [];

        // Validate latitude
        if (empty($data['lat'])) {
            $errors[] = 'Latitude is required';
        } else {
            // Clean and validate latitude
            $lat = trim($data['lat']);
            if (!is_numeric($lat)) {
                $errors[] = 'Latitude must be a valid number';
            } else {
                $latFloat = floatval($lat);
                if ($latFloat < -90 || $latFloat > 90) {
                    $errors[] = 'Latitude must be between -90 and 90';
                }
            }
        }

        // Validate longitude
        if (empty($data['long'])) {
            $errors[] = 'Longitude is required';
        } else {
            // Clean and validate longitude
            $long = trim($data['long']);
            if (!is_numeric($long)) {
                $errors[] = 'Longitude must be a valid number';
            } else {
                $longFloat = floatval($long);
                if ($longFloat < -180 || $longFloat > 180) {
                    $errors[] = 'Longitude must be between -180 and 180';
                }
            }
        }

        // Validate location name
        if (empty($data['location_name']) || strlen(trim($data['location_name'])) < 2) {
            $errors[] = 'Location name is required and must be at least 2 characters';
        }

        // Validate description length
        if (isset($data['description']) && strlen($data['description']) > 1000) {
            $errors[] = 'Description must be less than 1000 characters';
        }

        return $errors;
    }

    // ============ UTILITY METHODS ============

    /**
     * Calculate distance between two coordinates
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // Earth's radius in meters

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLatRad = deg2rad($lat2 - $lat1);
        $deltaLonRad = deg2rad($lon2 - $lon1);

        $a = sin($deltaLatRad / 2) * sin($deltaLatRad / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLonRad / 2) * sin($deltaLonRad / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get paginated safe places
     */
    public function getSafePlacesPaginated($page = 1, $limit = 20, $activeOnly = true) {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = $activeOnly ? "WHERE is_active = 1" : "";
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM tbl_safeplace $whereClause";
            $countStmt = $this->pdo->prepare($countQuery);
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get paginated results
            $query = "SELECT sp.*, u.username as created_by_username 
                     FROM tbl_safeplace sp
                     LEFT JOIN tbl_users u ON sp.created_by = u.id
                     $whereClause
                     ORDER BY sp.created_at DESC
                     LIMIT ? OFFSET ?";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$limit, $offset]);
            $safePlaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $safePlaces,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ];
        } catch (PDOException $e) {
            error_log("Get paginated safe places error: " . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => 0,
                    'total_pages' => 0
                ]
            ];
        }
    }
}
?>
