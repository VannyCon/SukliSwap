<div class="container-fluid">
	<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
		<h1 class="h4 mb-0">Notifications</h1>
		<div class="btn-group">
			<button type="button" class="btn btn-sm btn-outline-secondary" id="refreshNotificationsBtn">
				<i class="fas fa-sync-alt"></i> Refresh
			</button>
			<button type="button" class="btn btn-sm btn-success" id="markAllReadBtn">
				<i class="fas fa-check"></i> Mark all as read
			</button>
		</div>
	</div>

	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center">
					<span class="card-title mb-0">Your Notifications</span>
					<span class="badge bg-secondary" id="notifCountBadge">0</span>
				</div>
				<div class="card-body p-0">
					<div id="notificationsList" class="list-group list-group-flush">
						<div class="text-center text-muted py-4">
							<i class="fas fa-bell fa-2x mb-2"></i>
							<div>Loading notifications...</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


