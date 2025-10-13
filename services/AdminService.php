<?php
require_once('../connection/connection.php');

class AdminService extends config {
    
    // ============ DASHBOARD ANALYTICS ============
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        try {
            $query = "SELECT 
                         (SELECT COUNT(*) FROM tbl_users WHERE is_active = 1) as total_users,
                         (SELECT COUNT(*) FROM tbl_users WHERE DATE(created_at) = CURDATE()) as new_users_today,
                         (SELECT COUNT(*) FROM tbl_coin_requests WHERE status = 'active') as active_requests,
                         (SELECT COUNT(*) FROM tbl_coin_offers WHERE status = 'active') as active_offers,
                         (SELECT COUNT(*) FROM tbl_matches WHERE status = 'pending') as pending_matches,
                         (SELECT COUNT(*) FROM tbl_transactions WHERE status = 'scheduled') as scheduled_transactions,
                         (SELECT COUNT(*) FROM tbl_transactions WHERE status = 'completed' AND DATE(completion_time) = CURDATE()) as completed_today,
                         (SELECT COUNT(*) FROM tbl_reports WHERE status = 'pending') as pending_reports,
                         (SELECT COALESCE(SUM(t.quantity * ct.denomination), 0) 
                          FROM tbl_transactions t 
                          JOIN tbl_coin_types ct ON t.coin_type_id = ct.id 
                          WHERE t.status = 'completed' AND DATE(t.completion_time) = CURDATE()) as volume_today,
                         (SELECT COALESCE(AVG((t.requestor_rating + t.offeror_rating) / 2), 0) 
                          FROM tbl_transactions t 
                          WHERE t.status = 'completed' 
                          AND t.requestor_rating IS NOT NULL 
                          AND t.offeror_rating IS NOT NULL 
                          AND DATE(t.completion_time) = CURDATE()) as avg_rating_today";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get dashboard stats error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity($limit = 20) {
        try {
            $query = "SELECT 
                         'user_registration' as activity_type,
                         u.username,
                         u.created_at,
                         NULL as details
                      FROM tbl_users u
                      WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                      
                      UNION ALL
                      
                      SELECT 
                         'transaction_completed' as activity_type,
                         CONCAT(requestor.username, ' & ', offeror.username) as username,
                         t.completion_time as created_at,
                         CONCAT('Exchanged ', t.quantity, ' coins') as details
                      FROM tbl_transactions t
                      JOIN tbl_users requestor ON t.requestor_id = requestor.id
                      JOIN tbl_users offeror ON t.offeror_id = offeror.id
                      WHERE t.status = 'completed' 
                      AND t.completion_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                      
                      UNION ALL
                      
                      SELECT 
                         'new_report' as activity_type,
                         reporter.username,
                         r.created_at,
                         r.title as details
                      FROM tbl_reports r
                      JOIN tbl_users reporter ON r.reporter_id = reporter.id
                      WHERE r.status = 'pending'
                      AND r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                      
                      ORDER BY created_at DESC
                      LIMIT ?";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get recent activity error: " . $e->getMessage());
            return [];
        }
    }

    // ============ USER MANAGEMENT ============
    
    /**
     * Get all users with pagination
     */
    public function getAllUsers($page = 1, $size = 20, $search = '', $filter = '', $role = '') {
        try {
            $page = max(1, (int)$page);
            $size = max(1, min(100, (int)$size));
            $offset = ($page - 1) * $size;

            $whereClauses = [];
            $params = [];
            
            if ($search !== '') {
                $whereClauses[] = "(u.username LIKE :search OR u.email LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
                $params[':search'] = "%" . $search . "%";
            }
            
            if ($filter !== '') {
                if ($filter === 'active') {
                    $whereClauses[] = "u.is_active = 1";
                } elseif ($filter === 'inactive') {
                    $whereClauses[] = "u.is_active = 0";
                } elseif ($filter === 'verified') {
                    $whereClauses[] = "u.is_verified = 1";
                } elseif ($filter === 'pending') {
                    $whereClauses[] = "u.is_verified = 0 AND u.is_active = 1";
                } elseif ($filter === 'declined') {
                    $whereClauses[] = "u.is_verified = 0 AND u.is_active = 0";
                }
            }

            $whereClauses[] = "u.role != 'admin'";
            
            if ($role !== '') {
                $whereClauses[] = "u.role = :role";
                $params[':role'] = $role;
            }
            
            $whereSql = count($whereClauses) ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

            $query = "SELECT u.*, up.business_name, up.business_type, up.rating, up.total_transactions,
                             (SELECT COUNT(*) FROM tbl_coin_requests WHERE user_id = u.id) as total_requests,
                             (SELECT COUNT(*) FROM tbl_coin_offers WHERE user_id = u.id) as total_offers,
                             (SELECT COUNT(*) FROM tbl_transactions WHERE requestor_id = u.id OR offeror_id = u.id) as total_transactions_count
                      FROM tbl_users u
                      LEFT JOIN tbl_user_profiles up ON u.id = up.user_id
                      $whereSql
                      ORDER BY u.created_at DESC
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
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics() {
        try {
            $stats = [];
            
            // Total users
            $query = "SELECT COUNT(*) as total FROM tbl_users";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['total'] = $stmt->fetchColumn();
            
            // Pending users
            $query = "SELECT COUNT(*) as pending FROM tbl_users WHERE is_verified = 0 AND is_active = 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['pending'] = $stmt->fetchColumn();
            
            // Verified users
            $query = "SELECT COUNT(*) as verified FROM tbl_users WHERE is_verified = 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['verified'] = $stmt->fetchColumn();
            
            // Declined users
            $query = "SELECT COUNT(*) as declined FROM tbl_users WHERE is_verified = 0 AND is_active = 0";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['declined'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Get user statistics error: " . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'verified' => 0,
                'declined' => 0
            ];
        }
    }

    /**
     * Count users
     */
    public function countUsers($search = '', $filter = '', $role = '') {
        try {
            $whereClauses = [];
            $params = [];
            
            if ($search !== '') {
                $whereClauses[] = "(u.username LIKE :search OR u.email LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
                $params[':search'] = "%" . $search . "%";
            }
            
            if ($filter !== '') {
                if ($filter === 'active') {
                    $whereClauses[] = "u.is_active = 1";
                } elseif ($filter === 'inactive') {
                    $whereClauses[] = "u.is_active = 0";
                } elseif ($filter === 'verified') {
                    $whereClauses[] = "u.is_verified = 1";
                } elseif ($filter === 'pending') {
                    $whereClauses[] = "u.is_verified = 0 AND u.is_active = 1";
                } elseif ($filter === 'declined') {
                    $whereClauses[] = "u.is_verified = 0 AND u.is_active = 0";
                }
            }

            $whereClauses[] = "u.role != 'admin'";
            
            if ($role !== '') {
                $whereClauses[] = "u.role = :role";
                $params[':role'] = $role;
            }
            
            $whereSql = count($whereClauses) ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

            $query = "SELECT COUNT(*) as total FROM tbl_users u $whereSql";
            
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Count users error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update user status
     */
    public function updateUserStatus($userId, $status, $adminId) {
        try {
            $this->beginTransaction();

            if ($status === 'activate') {
                $query = "UPDATE tbl_users SET is_active = 1 WHERE id = ?";
            } elseif ($status === 'deactivate') {
                $query = "UPDATE tbl_users SET is_active = 0 WHERE id = ?";
            } elseif ($status === 'verify') {
                $query = "UPDATE tbl_users SET is_verified = 1 WHERE id = ?";
            } elseif ($status === 'unverify') {
                $query = "UPDATE tbl_users SET is_verified = 0 WHERE id = ?";
            } else {
                $this->rollback();
                return ['success' => false, 'message' => 'Invalid status'];
            }

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);

            // Log activity
            $this->logActivity($adminId, "update_user_status_{$status}", 'user', $userId);

            $this->commit();

            return [
                'success' => true,
                'message' => 'User status updated successfully'
            ];
        } catch (PDOException $e) {
            $this->rollback();
            error_log("Update user status error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update user status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete user
     */
    public function deleteUser($userId, $adminId) {
        try {
            $this->beginTransaction();

            // Check if user has active transactions
            $checkQuery = "SELECT COUNT(*) as count FROM tbl_transactions 
                          WHERE (requestor_id = ? OR offeror_id = ?) 
                          AND status IN ('scheduled', 'in_progress')";
            $checkStmt = $this->pdo->prepare($checkQuery);
            $checkStmt->execute([$userId, $userId]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                $this->rollback();
                return [
                    'success' => false,
                    'message' => 'Cannot delete user with active transactions'
                ];
            }

            // Delete user (cascade will handle related records)
            $query = "DELETE FROM tbl_users WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);

            // Log activity
            $this->logActivity($adminId, 'delete_user', 'user', $userId);

            $this->commit();

            return [
                'success' => true,
                'message' => 'User deleted successfully'
            ];
        } catch (PDOException $e) {
            $this->rollback();
            error_log("Delete user error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify user account
     */
    public function verifyUser($userId, $adminId) {
        try {
            $this->beginTransaction();

            $query = "UPDATE tbl_users SET is_verified = 1 WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);

            // Log activity
            $this->logActivity($adminId, 'verify_user', 'user', $userId);

            $this->commit();

            return [
                'success' => true,
                'message' => 'User verified successfully'
            ];
        } catch (PDOException $e) {
            $this->rollback();
            error_log("Verify user error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to verify user: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Unverify user account
     */
    public function unverifyUser($userId, $adminId) {
        try {
            $this->beginTransaction();

            $query = "UPDATE tbl_users SET is_verified = 0 WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);

            // Log activity
            $this->logActivity($adminId, 'unverify_user', 'user', $userId);

            $this->commit();

            return [
                'success' => true,
                'message' => 'User unverified successfully'
            ];
        } catch (PDOException $e) {
            $this->rollback();
            error_log("Unverify user error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to unverify user: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Decline user account (permanently reject registration)
     */
    public function declineUser($userId, $adminId) {
        try {
            $this->beginTransaction();

            // Set user as declined (inactive and unverified)
            $query = "UPDATE tbl_users SET is_verified = 0, is_active = 0, updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);

            // Log activity
            $this->logActivity($adminId, 'decline_user', 'user', $userId);

            $this->commit();

            return [
                'success' => true,
                'message' => 'User declined successfully'
            ];
        } catch (PDOException $e) {
            $this->rollback();
            error_log("Decline user error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to decline user: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user details by ID
     */
    public function getUserDetails($userId) {
        try {
            $query = "SELECT u.*, up.business_name, up.business_type, up.rating,
                             (SELECT COUNT(*) FROM tbl_coin_requests WHERE user_id = u.id) as total_requests,
                             (SELECT COUNT(*) FROM tbl_coin_offers WHERE user_id = u.id) as total_offers,
                             (SELECT COUNT(*) FROM tbl_transactions WHERE requestor_id = u.id OR offeror_id = u.id) as total_transactions_count
                      FROM tbl_users u
                      LEFT JOIN tbl_user_profiles up ON u.id = up.user_id
                      WHERE u.id = ?";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user details error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Export users to CSV
     */
    public function exportUsers($format = 'csv') {
        try {
            $query = "SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.phone, 
                             u.role, u.is_verified, u.is_active, u.created_at, u.updated_at,
                             up.business_name, up.business_type
                      FROM tbl_users u
                      LEFT JOIN tbl_user_profiles up ON u.id = up.user_id
                      ORDER BY u.created_at DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($format === 'csv') {
                return $this->generateCSV($users);
            }
            
            return $users;
        } catch (PDOException $e) {
            error_log("Export users error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate CSV content from user data
     */
    private function generateCSV($users) {
        $csv = "ID,Username,Email,First Name,Last Name,Phone,Role,Verified,Active,Business Name,Business Type,Created At,Updated At\n";
        
        foreach ($users as $user) {
            $csv .= sprintf('"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $user['id'],
                $user['username'],
                $user['email'],
                $user['first_name'] ?? '',
                $user['last_name'] ?? '',
                $user['phone'] ?? '',
                $user['role'],
                $user['is_verified'] ? 'Yes' : 'No',
                $user['is_active'] ? 'Yes' : 'No',
                $user['business_name'] ?? '',
                $user['business_type'] ?? '',
                $user['created_at'],
                $user['updated_at']
            );
        }
        
        return $csv;
    }

    // ============ TRANSACTION MANAGEMENT ============
    
    /**
     * Get all transactions with pagination
     */
    public function getAllTransactions($page = 1, $size = 20, $search = '', $status = '') {
        try {
            $page = max(1, (int)$page);
            $size = max(1, min(100, (int)$size));
            $offset = ($page - 1) * $size;

            $whereClauses = [];
            $params = [];
            
            if ($search !== '') {
                $whereClauses[] = "(requestor.username LIKE :search OR offeror.username LIKE :search OR t.qr_code LIKE :search)";
                $params[':search'] = "%" . $search . "%";
            }
            
            if ($status !== '') {
                $whereClauses[] = "t.status = :status";
                $params[':status'] = $status;
            }
            
            $whereSql = count($whereClauses) ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

            $query = "SELECT t.*, ct.denomination, ct.description as coin_description,
                             requestor.username as requestor_username, requestor.first_name as requestor_first_name,
                             offeror.username as offeror_username, offeror.first_name as offeror_first_name
                      FROM tbl_transactions t
                      JOIN tbl_coin_types ct ON t.coin_type_id = ct.id
                      JOIN tbl_users requestor ON t.requestor_id = requestor.id
                      JOIN tbl_users offeror ON t.offeror_id = offeror.id
                      $whereSql
                      ORDER BY t.created_at DESC
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
            error_log("Get all transactions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count transactions
     */
    public function countTransactions($search = '', $status = '') {
        try {
            $whereClauses = [];
            $params = [];
            
            if ($search !== '') {
                $whereClauses[] = "(requestor.username LIKE :search OR offeror.username LIKE :search OR t.qr_code LIKE :search)";
                $params[':search'] = "%" . $search . "%";
            }
            
            if ($status !== '') {
                $whereClauses[] = "t.status = :status";
                $params[':status'] = $status;
            }
            
            $whereSql = count($whereClauses) ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

            $query = "SELECT COUNT(*) as total
                      FROM tbl_transactions t
                      JOIN tbl_users requestor ON t.requestor_id = requestor.id
                      JOIN tbl_users offeror ON t.offeror_id = offeror.id
                      $whereSql";
            
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Count transactions error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Cancel transaction
     */
    public function cancelTransaction($transactionId, $reason, $adminId) {
        try {
            $this->beginTransaction();

            // Update transaction
            $query = "UPDATE tbl_transactions 
                     SET status = 'cancelled', admin_notes = ? 
                     WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$reason, $transactionId]);

            // Log activity
            $this->logActivity($adminId, 'cancel_transaction', 'transaction', $transactionId, ['reason' => $reason]);

            $this->commit();

            return [
                'success' => true,
                'message' => 'Transaction cancelled successfully'
            ];
        } catch (PDOException $e) {
            $this->rollback();
            error_log("Cancel transaction error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to cancel transaction: ' . $e->getMessage()
            ];
        }
    }

    // ============ REPORTS MANAGEMENT ============
    
    /**
     * Get all reports with pagination
     */
    public function getAllReports($page = 1, $size = 20, $status = '') {
        try {
            $page = max(1, (int)$page);
            $size = max(1, min(100, (int)$size));
            $offset = ($page - 1) * $size;

            $whereClause = '';
            $params = [];
            
            if ($status !== '') {
                $whereClause = 'WHERE r.status = ?';
                $params[] = $status;
            }

            $query = "SELECT r.*, 
                             reporter.username as reporter_username,
                             reported.username as reported_username,
                             resolved.username as resolved_by_username
                      FROM tbl_reports r
                      JOIN tbl_users reporter ON r.reporter_id = reporter.id
                      LEFT JOIN tbl_users reported ON r.reported_user_id = reported.id
                      LEFT JOIN tbl_users resolved ON r.resolved_by = resolved.id
                      $whereClause
                      ORDER BY r.created_at DESC
                      LIMIT ? OFFSET ?";
            
            $params[] = $size;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all reports error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count reports
     */
    public function countReports($status = '') {
        try {
            $whereClause = '';
            $params = [];
            
            if ($status !== '') {
                $whereClause = 'WHERE status = ?';
                $params[] = $status;
            }

            $query = "SELECT COUNT(*) as total FROM tbl_reports r $whereClause";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Count reports error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Resolve report
     */
    public function resolveReport($reportId, $status, $adminNotes, $adminId) {
        try {
            $query = "UPDATE tbl_reports 
                     SET status = ?, admin_notes = ?, resolved_by = ?, resolved_at = NOW() 
                     WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$status, $adminNotes, $adminId, $reportId]);

            // Log activity
            $this->logActivity($adminId, 'resolve_report', 'report', $reportId, [
                'status' => $status,
                'notes' => $adminNotes
            ]);

            return [
                'success' => true,
                'message' => 'Report resolved successfully'
            ];
        } catch (PDOException $e) {
            error_log("Resolve report error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to resolve report: ' . $e->getMessage()
            ];
        }
    }

    // ============ SYSTEM SETTINGS ============
    
    /**
     * Get system settings
     */
    public function getSystemSettings() {
        try {
            $query = "SELECT * FROM tbl_system_settings ORDER BY setting_key";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $settings = [];
            foreach ($results as $result) {
                $settings[$result['setting_key']] = $result;
            }
            return $settings;
        } catch (PDOException $e) {
            error_log("Get system settings error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update system setting
     */
    public function updateSystemSetting($key, $value, $adminId) {
        try {
            $query = "UPDATE tbl_system_settings 
                     SET setting_value = ?, updated_by = ? 
                     WHERE setting_key = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$value, $adminId, $key]);

            // Log activity
            $this->logActivity($adminId, 'update_system_setting', 'system_setting', null, [
                'key' => $key,
                'value' => $value
            ]);

            return [
                'success' => true,
                'message' => 'System setting updated successfully'
            ];
        } catch (PDOException $e) {
            error_log("Update system setting error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update system setting: ' . $e->getMessage()
            ];
        }
    }

    // ============ ANALYTICS AND REPORTING ============
    
    /**
     * Generate analytics report
     */
    public function generateAnalyticsReport($startDate, $endDate) {
        try {
            $query = "SELECT 
                         DATE(created_at) as date,
                         COUNT(*) as total_users,
                         (SELECT COUNT(*) FROM tbl_coin_requests WHERE DATE(created_at) = DATE(u.created_at)) as total_requests,
                         (SELECT COUNT(*) FROM tbl_coin_offers WHERE DATE(created_at) = DATE(u.created_at)) as total_offers,
                         (SELECT COUNT(*) FROM tbl_transactions WHERE DATE(created_at) = DATE(u.created_at)) as total_transactions,
                         (SELECT COUNT(*) FROM tbl_transactions WHERE DATE(created_at) = DATE(u.created_at) AND status = 'completed') as completed_transactions,
                         (SELECT COALESCE(SUM(t.quantity * ct.denomination), 0) 
                          FROM tbl_transactions t 
                          JOIN tbl_coin_types ct ON t.coin_type_id = ct.id 
                          WHERE DATE(t.created_at) = DATE(u.created_at) AND t.status = 'completed') as total_volume
                      FROM tbl_users u
                      WHERE DATE(created_at) BETWEEN ? AND ?
                      GROUP BY DATE(created_at)
                      ORDER BY date ASC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Generate analytics report error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get transaction volume by coin type
     */
    public function getTransactionVolumeByCoinType($startDate, $endDate) {
        try {
            $query = "SELECT 
                         ct.denomination,
                         ct.description,
                         COUNT(t.id) as transaction_count,
                         SUM(t.quantity) as total_quantity,
                         SUM(t.quantity * ct.denomination) as total_value
                      FROM tbl_transactions t
                      JOIN tbl_coin_types ct ON t.coin_type_id = ct.id
                      WHERE DATE(t.created_at) BETWEEN ? AND ? 
                      AND t.status = 'completed'
                      GROUP BY ct.id, ct.denomination, ct.description
                      ORDER BY total_value DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get transaction volume by coin type error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user activity summary
     */
    public function getUserActivitySummary($startDate, $endDate) {
        try {
            $query = "SELECT 
                         u.username,
                         u.first_name,
                         u.last_name,
                         up.business_name,
                         up.business_type,
                         (SELECT COUNT(*) FROM tbl_coin_requests WHERE user_id = u.id AND DATE(created_at) BETWEEN ? AND ?) as requests_made,
                         (SELECT COUNT(*) FROM tbl_coin_offers WHERE user_id = u.id AND DATE(created_at) BETWEEN ? AND ?) as offers_made,
                         (SELECT COUNT(*) FROM tbl_transactions WHERE (requestor_id = u.id OR offeror_id = u.id) AND DATE(created_at) BETWEEN ? AND ?) as transactions_involved,
                         (SELECT COUNT(*) FROM tbl_transactions WHERE (requestor_id = u.id OR offeror_id = u.id) AND status = 'completed' AND DATE(completion_time) BETWEEN ? AND ?) as transactions_completed,
                         up.rating
                      FROM tbl_users u
                      LEFT JOIN tbl_user_profiles up ON u.id = up.user_id
                      WHERE u.created_at <= ?
                      HAVING (requests_made > 0 OR offers_made > 0 OR transactions_involved > 0)
                      ORDER BY transactions_completed DESC, rating DESC
                      LIMIT 50";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $startDate, $endDate, $startDate, $endDate, 
                $startDate, $endDate, $startDate, $endDate, $endDate
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user activity summary error: " . $e->getMessage());
            return [];
        }
    }

    // ============ UTILITY METHODS ============
    
}
?>