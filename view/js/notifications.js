
class NotificationClient {
	constructor() {
		// const authManager = new AuthManager();
        // notificationsAPI = authManager.API_CONFIG.baseURL + 'notifications.php';
		// headerAPI = authManager.API_CONFIG.getHeaders();
        // formHeaderAPI = authManager.API_CONFIG.getFormHeaders();
		this.apiBase = '../../../../api/notifications.php';
		this.token = localStorage.getItem('auth_token');
		this.eventSource = null;
		this.lastId = parseInt(localStorage.getItem('notif_last_id') || '0', 10) || null;
		this.pollTimer = null;
		this.visible = true;
		this.initVisibilityHandler();
	}

	initVisibilityHandler() {
		document.addEventListener('visibilitychange', () => {
			this.visible = document.visibilityState === 'visible';
		});
	}

	start() {
		if (!this.token) return;
		this.startPolling();
	}

	// SSE disabled; axios polling is the supported path

	stopSSE() {
		if (this.eventSource) {
			this.eventSource.close();
			this.eventSource = null;
		}
	}

	startPolling(intervalMs = 5000) {
		if (this.pollTimer) clearInterval(this.pollTimer);
		this.pollTimer = setInterval(() => this.fetchNew(), intervalMs);
		this.fetchNew();
	}

	async fetchNew() {
		try {
			const params = { action: 'list', limit: 20 };
			if (this.lastId) params.since_id = this.lastId;
			const res = await axios.get(this.apiBase, {
				params,
				headers: { 'Authorization': this.token ? `Bearer ${this.token}` : '' }
			});
			const json = res.data;
			if (json && json.success && Array.isArray(json.data)) {
				json.data.sort((a, b) => a.id - b.id).forEach(n => {
					this.lastId = Math.max(this.lastId || 0, n.id);
					localStorage.setItem('notif_last_id', String(this.lastId));
					this.show(n);
				});
			}
		} catch (_) {}
	}

	show(n) {
		const type = (n.type || 'info').toLowerCase();
		const title = n.title || 'Notification';
		const message = n.message || '';
		if (window.CustomToast && CustomToast.show) {
			CustomToast.show(type, title, message, 4000);
		}
		// Emit event for sidebar badges, etc.
		try {
			const evt = new CustomEvent('app:notification', { detail: n });
			window.dispatchEvent(evt);
		} catch (_) {}
	}

	async markRead(id) {
		try {
			await axios.post(`${this.apiBase}?action=markRead`, { id }, {
				headers: {
					'Authorization': this.token ? `Bearer ${this.token}` : ''
				}
			});
		} catch (_) {}
	}

	async markAllRead() {
		try {
			await axios.post(`${this.apiBase}?action=markAllRead`, {}, {
				headers: { 'Authorization': this.token ? `Bearer ${this.token}` : '' }
			});
		} catch (_) {}
	}

	async getUnreadCount(maxFetch = 100) {
		try {
			const res = await axios.get(this.apiBase, {
				params: { action: 'list', limit: maxFetch },
				headers: { 'Authorization': this.token ? `Bearer ${this.token}` : '' }
			});
			const json = res.data;
			if (json && json.success && Array.isArray(json.data)) {
				return json.data.reduce((acc, n) => acc + (String(n.is_read) === '0' ? 1 : 0), 0);
			}
		} catch (_) {}
		return 0;
	}
}

window.notificationClient = new NotificationClient();
document.addEventListener('DOMContentLoaded', () => {
	window.notificationClient.start();
});


