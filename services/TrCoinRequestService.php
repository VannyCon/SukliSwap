<?php
require_once('../connection/connection.php');
require_once('../services/TransactionService.php');

class TrCoinRequestService extends config {

	public function create($data, $userId) {
		try {
			$sql = "INSERT INTO tbl_tr_coin_request (
				post_request_id, requestor_id, coin_type_id, requested_quantity, message,
				my_longitude, my_latitude, scheduled_time,
				status, created_at
			) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

			$stmt = $this->pdo->prepare($sql);
			$stmt->execute([
				$data['post_request_id'] ?? null,
				$userId,
				$data['coin_type_id'],
				$data['requested_quantity'],
				$data['message'] ?? null,
				$data['my_longitude'] ?? null,
				$data['my_latitude'] ?? null,
				$data['scheduled_time'] ?? null
			]);

			return [
				'success' => true,
				'message' => 'Request sent successfully',
				'id' => $this->pdo->lastInsertId()
			];
		} catch (Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to send request: ' . $e->getMessage() ];
		}
	}

	public function listByPostRequest($postRequestId, $currentUserId) {
		try {
			$sql = "SELECT trr.*, u.username, u.first_name, u.last_name
					FROM tbl_tr_coin_request trr
					JOIN tbl_users u ON trr.requestor_id = u.id
					WHERE trr.post_request_id = ?
					ORDER BY trr.created_at DESC";
			$params = [$postRequestId];
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute($params);
			return [ 'success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC) ];
		} catch (Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to load requests: ' . $e->getMessage() ];
		}
	}

	public function listMine($userId) {
		try {
			$sql = "SELECT trr.* FROM tbl_tr_coin_request trr WHERE trr.requestor_id = ? ORDER BY trr.created_at DESC";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute([$userId]);
			return [ 'success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC) ];
		} catch (Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to load my requests: ' . $e->getMessage() ];
		}
	}

	public function accept($id, $ownerUserId, $scheduledMeetingTime = null) {
		try {
			$this->pdo->beginTransaction();

			// Debug logging
			error_log("TrCoinRequestService::accept - ID: $id, OwnerUserId: $ownerUserId, ScheduledTime: $scheduledMeetingTime");

			// Load targeted request and validate post request ownership
			$getSql = "SELECT trr.*, cr.user_id AS post_request_owner
					FROM tbl_tr_coin_request trr
					JOIN tbl_coin_requests cr ON trr.post_request_id = cr.id
					WHERE trr.id = ? FOR UPDATE";
			$stmt = $this->pdo->prepare($getSql);
			$stmt->execute([$id]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);

			// Debug logging
			error_log("Query result: " . print_r($row, true));

			if (!$row) {
				$this->pdo->rollback();
				return [ 'success' => false, 'message' => 'Request not found' ];
			}

			if ((int)$row['post_request_owner'] !== (int)$ownerUserId) {
				$this->pdo->rollback();
				error_log("DEBUG: Authorization failed - Request owner: {$row['post_request_owner']}, Current user: $ownerUserId");
				return [ 'success' => false, 'message' => 'Not authorized to accept this request' ];
			}

			if ($row['status'] !== 'pending') {
				$this->pdo->rollback();
				error_log("DEBUG: Request status is not pending - Status: {$row['status']}");
				return [ 'success' => false, 'message' => 'Request is not in pending status' ];
			}

			// Mark accepted
			$upd = $this->pdo->prepare("UPDATE tbl_tr_coin_request SET status='accepted', updated_at=NOW() WHERE id=?");
			$upd->execute([$id]);
			$newOfferId = $this->pdo->lastInsertId();

			// Create transaction using existing method with scheduled meeting time
			$transactionService = new TransactionService();
			$tx = $transactionService->createTRRequestTransaction($id, $row['post_request_id'], $ownerUserId, $scheduledMeetingTime);

			$this->pdo->commit();
			return $tx;
		} catch (Exception $e) {
			$this->pdo->rollback();
			return [ 'success' => false, 'message' => 'Failed to accept request: ' . $e->getMessage() ];
		}
	}

	public function reject($id, $ownerUserId) {
		try {
			$sql = "UPDATE tbl_tr_coin_request trr
					JOIN tbl_coin_requests cr ON trr.post_request_id = cr.id
					SET trr.status='rejected', trr.updated_at=NOW()
					WHERE trr.id=? AND cr.user_id=? AND trr.status='pending'";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute([$id, $ownerUserId]);
			return [ 'success' => $stmt->rowCount() > 0, 'message' => $stmt->rowCount() ? 'Request rejected' : 'Request not found or cannot be rejected' ];
		} catch (Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to reject request: ' . $e->getMessage() ];
		}
	}

	public function cancel($id, $userId) {
		try {
			$sql = "UPDATE tbl_tr_coin_request SET status='cancelled', updated_at=NOW() WHERE id=? AND requestor_id=? AND status='pending'";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute([$id, $userId]);
			return [ 'success' => $stmt->rowCount() > 0, 'message' => $stmt->rowCount() ? 'Request cancelled' : 'Request not found or cannot be cancelled' ];
		} catch (Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to cancel request: ' . $e->getMessage() ];
		}
	}
}

?>


