<?php
require_once('../connection/connection.php');
require_once('../services/TransactionService.php');
require_once('../services/NotificationService.php');

class TrCoinOfferService extends config {

	public function create($data, $userId) {
		try {
			$sql = "INSERT INTO tbl_tr_coin_offer (
				post_offer_id, offeror_id, coin_type_id, offered_quantity, message,
				my_longitude, my_latitude, scheduled_time,
				status, created_at
			) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

			$stmt = $this->pdo->prepare($sql);
			$stmt->execute([
				$data['post_offer_id'] ?? null,
				$userId,
				$data['coin_type_id'],
				$data['offered_quantity'],
				$data['message'] ?? null,
				$data['my_longitude'] ?? null,
				$data['my_latitude'] ?? null,
				$data['scheduled_time'] ?? null
			]);

			// Get the post offer owner to send notification to
			$ownerQuery = "SELECT user_id FROM tbl_coin_offers WHERE id = ?";
			$ownerStmt = $this->pdo->prepare($ownerQuery);
			$ownerStmt->execute([$data['post_offer_id']]);
			$owner = $ownerStmt->fetch(PDO::FETCH_ASSOC);
			
			if ($owner) {
				$notificationService = new NotificationService();
				$notificationService->createNotification(
					(int)$owner['user_id'],
					'tr_coin_offer',
					'New Coin Request',
					'Someone has requested to exchange coins.',
					$data
				);
			}

			return [
				'success' => true,
				'message' => 'Offer sent successfully',
				'id' => $this->pdo->lastInsertId()
			];
		} catch (Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to send offer: ' . $e->getMessage() ];
		}
	}

	public function listByPostOffer($postOfferId, $currentUserId) {
		try {
			$sql = "SELECT tro.*, u.username, u.first_name, u.last_name
					FROM tbl_tr_coin_offer tro
					JOIN tbl_users u ON tro.offeror_id = u.id
					WHERE tro.post_offer_id = ?
					ORDER BY tro.created_at DESC";
			$params = [$postOfferId];
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute($params);
			return [ 'success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC) ];
		} catch (Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to load offers: ' . $e->getMessage() ];
		}
	}

	public function listMine($userId) {
		try {
			$sql = "SELECT tro.* FROM tbl_tr_coin_offer tro WHERE tro.offeror_id = ? ORDER BY tro.created_at DESC";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute([$userId]);
			return [ 'success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC) ];
		} catch (Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to load my offers: ' . $e->getMessage() ];
		}
	}

	public function accept($id, $ownerUserId, $scheduledMeetingTime = null) {
		try {
			$this->pdo->beginTransaction();

			// Debug logging
			error_log("TrCoinOfferService::accept - ID: $id, OwnerUserId: $ownerUserId, ScheduledTime: $scheduledMeetingTime");

			// Load offer and validate post offer ownership
			$getSql = "SELECT tro.*, co.user_id AS post_offer_owner
					FROM tbl_tr_coin_offer tro
					JOIN tbl_coin_offers co ON tro.post_offer_id = co.id
					WHERE tro.id = ? FOR UPDATE";
			$stmt = $this->pdo->prepare($getSql);
			$stmt->execute([$id]);
			$offer = $stmt->fetch(PDO::FETCH_ASSOC);

			if (!$offer || (int)$offer['post_offer_owner'] !== (int)$ownerUserId || $offer['status'] !== 'pending') {
				$this->pdo->rollback();
				return [ 'success' => false, 'message' => 'Offer not found or not authorized' ];
			}

			// Mark accepted
			$upd = $this->pdo->prepare("UPDATE tbl_tr_coin_offer SET status='accepted', updated_at=NOW() WHERE id=?");
			$upd->execute([$id]);

			$newRequestId = $this->pdo->lastInsertId();

			// Create transaction using existing method with scheduled meeting time
			$transactionService = new TransactionService();
			$tx = $transactionService->createTROfferTransaction($id, $offer['post_offer_id'], $ownerUserId, $scheduledMeetingTime);

			// Notify the offeror that their offer was accepted
			$notificationService = new NotificationService();
			$notificationService->createNotification(
				(int)$offer['offeror_id'],
				'tr_coin_offer_accepted',
				'Coin Offer Accepted',
				'Your coin offer has been accepted.',
				['offer_id' => $id, 'post_offer_id' => $offer['post_offer_id']]
			);

			$this->pdo->commit();
			return $tx;
		} catch (Exception $e) {
			$this->pdo->rollback();
			return [ 'success' => false, 'message' => 'Failed to accept offer: ' . $e->getMessage() ];
		}
	}

	public function reject($id, $ownerUserId) {
		try {
			// First get the offer details to notify the offeror
			$getSql = "SELECT tro.* FROM tbl_tr_coin_offer tro
					   JOIN tbl_coin_offers co ON tro.post_offer_id = co.id
					   WHERE tro.id=? AND co.user_id=? AND tro.status='pending'";
			$getStmt = $this->pdo->prepare($getSql);
			$getStmt->execute([$id, $ownerUserId]);
			$offer = $getStmt->fetch(PDO::FETCH_ASSOC);
			
			if (!$offer) {
				return [ 'success' => false, 'message' => 'Offer not found or cannot be rejected' ];
			}
			
			$sql = "UPDATE tbl_tr_coin_offer tro
					JOIN tbl_coin_offers co ON tro.post_offer_id = co.id
					SET tro.status='rejected', tro.updated_at=NOW()
					WHERE tro.id=? AND co.user_id=? AND tro.status='pending'";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute([$id, $ownerUserId]);
			
			if ($stmt->rowCount() > 0) {
				// Notify the offeror that their offer was rejected
				$notificationService = new NotificationService();
				$notificationService->createNotification(
					(int)$offer['offeror_id'],
					'tr_coin_offer_rejected',
					'Coin Offer Rejected',
					'Your coin offer has been rejected.',
					['offer_id' => $id, 'post_offer_id' => $offer['post_offer_id']]
				);
				
				return [ 'success' => true, 'message' => 'Offer rejected' ];
			} else {
				return [ 'success' => false, 'message' => 'Offer not found or cannot be rejected' ];
			}
		} catch (Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to reject offer: ' . $e->getMessage() ];
		}
	}

	public function cancel($id, $userId) {
		try {
			$sql = "UPDATE tbl_tr_coin_offer SET status='cancelled', updated_at=NOW() WHERE id=? AND offeror_id=? AND status='pending'";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute([$id, $userId]);
			return [ 'success' => $stmt->rowCount() > 0, 'message' => $stmt->rowCount() ? 'Offer cancelled' : 'Offer not found or cannot be cancelled' ];
		} catch (Exception $e) {
			return [ 'success' => false, 'message' => 'Failed to cancel offer: ' . $e->getMessage() ];
		}
	}
}

?>


