<?php
require_once('../connection/connection.php');

class NotificationService extends config {
	/**
	 * Get notifications for a user with optional since_id for incremental fetch
	 */
	public function getUserNotifications($userId, $limit = 50, $sinceId = null) {
		try {
			$limitInt = max(1, min(200, (int)$limit));
			$query = "SELECT id, user_id, type, title, message, data, is_read, created_at
					FROM tbl_notifications
					WHERE user_id = :userId";
			if ($sinceId !== null) {
				$query .= " AND id > :sinceId";
			}
			// Important: inject validated integer for LIMIT to avoid driver issues with bound params
			$query .= " ORDER BY id DESC LIMIT " . $limitInt;

			$stmt = $this->pdo->prepare($query);
			$stmt->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
			if ($sinceId !== null) {
				$stmt->bindValue(':sinceId', (int)$sinceId, PDO::PARAM_INT);
			}
			$stmt->execute();
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return [ 'success' => true, 'data' => $rows ];
		} catch (PDOException $e) {
			error_log('getUserNotifications error: ' . $e->getMessage());
			return [ 'success' => false, 'message' => 'Failed to fetch notifications' ];
		}
	}

	/** Mark one notification as read */
	public function markAsRead($userId, $notificationId) {
		try {
			$stmt = $this->pdo->prepare("UPDATE tbl_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
			$stmt->execute([(int)$notificationId, (int)$userId]);
			return [ 'success' => true, 'updated' => $stmt->rowCount() ];
		} catch (PDOException $e) {
			error_log('markAsRead error: ' . $e->getMessage());
			return [ 'success' => false, 'message' => 'Failed to update notification' ];
		}
	}

	/** Mark all notifications as read for user */
	public function markAllAsRead($userId) {
		try {
			$stmt = $this->pdo->prepare("UPDATE tbl_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
			$stmt->execute([(int)$userId]);
			return [ 'success' => true, 'updated' => $stmt->rowCount() ];
		} catch (PDOException $e) {
			error_log('markAllAsRead error: ' . $e->getMessage());
			return [ 'success' => false, 'message' => 'Failed to update notifications' ];
		}
	}

	/** Create a notification */
	public function createNotification($userId, $type, $title, $message = '', $data = null) {
		try {
			$stmt = $this->pdo->prepare("INSERT INTO tbl_notifications (user_id, type, title, message, data, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
			$stmt->execute([
				(int)$userId,
				(string)$type,
				(string)$title,
				(string)$message,
				$data !== null ? json_encode($data) : null
			]);
			return [ 'success' => true, 'id' => $this->pdo->lastInsertId() ];
		} catch (PDOException $e) {
			error_log('createNotification error: ' . $e->getMessage());
			return [ 'success' => false, 'message' => 'Failed to create notification' ];
		}
	}
}

?>

