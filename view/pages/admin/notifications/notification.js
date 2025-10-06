let notificationsAPI = null;
let headerAPI = null;
let formHeaderAPI = null;
let authManager = null;
let currentUser = null;

class PageNotifications {
	constructor() {
		const authManager = new AuthManager();
        notificationsAPI = authManager.API_CONFIG.baseURL + 'notifications.php';
        headerAPI = authManager.API_CONFIG.getHeaders();
        formHeaderAPI = authManager.API_CONFIG.getFormHeaders();
		this.lastId = parseInt(localStorage.getItem('notif_last_id') || '0', 10) || null;
		this.listEl = document.getElementById('notificationsList');
		this.countEl = document.getElementById('notifCountBadge');
		this.attachEvents();
		this.load();
	}

	attachEvents() {
		document.getElementById('refreshNotificationsBtn')?.addEventListener('click', () => this.load());
		document.getElementById('markAllReadBtn')?.addEventListener('click', () => this.markAll());
		window.addEventListener('app:notification', () => this.load());
	}

	async load() {
		try {
			const params = new URLSearchParams({ action: 'list', limit: '100' });
			const response = await axios.get(`${notificationsAPI}?${params.toString()}`, {
				headers: headerAPI
			});

			if (response.data.success) {
				console.log("notifications", response.data.data);
				this.render(response.data.data || []);
			}
			
		} catch (e) {
			this.listEl.innerHTML = '<div class="text-center text-danger py-4">Failed to load notifications</div>';
		}
	}

	render(items) {
		if (!Array.isArray(items) || items.length === 0) {
			this.countEl.textContent = '0';
			this.listEl.innerHTML = '<div class="text-center text-muted py-4">No notifications yet</div>';
			return;
		}
		this.countEl.textContent = String(items.filter(n => String(n.is_read) === '0').length);
		const html = items.map(n => {
			const typeClass = this.typeToClass(n.type);
			const readClass = String(n.is_read) === '1' ? 'opacity-75' : '';
			return `
				<a href="#" class="list-group-item list-group-item-action ${readClass}">
					<div class="d-flex w-100 justify-content-between">
						<h6 class="mb-1"><span class="badge bg-${typeClass} me-2">${(n.type||'info').toUpperCase()}</span>${this.escape(n.title||'Notification')}</h6>
						<small class="text-muted">${this.formatDate(n.created_at)}</small>
					</div>
					<p class="mb-1">${this.escape(n.message||'')}</p>
					<div class="d-flex gap-2">
						<button class="btn btn-sm btn-outline-success" onclick="pageNotifications.mark(${n.id})">Mark as read</button>
					</div>
				</a>`;
		}).join('');
		this.listEl.innerHTML = html;
	}

	typeToClass(type) {
		switch (String(type||'').toLowerCase()) {
			case 'success': return 'success';
			case 'warning': return 'warning';
			case 'error':
			case 'danger': return 'danger';
			default: return 'info';
		}
	}

	escape(str) {
		const div = document.createElement('div');
		div.textContent = String(str);
		return div.innerHTML;
	}

	formatDate(dt) {
		if (!dt) return '';
		try { return new Date(dt).toLocaleString(); } catch { return dt; }
	}

	async mark(id) {
		try {
			await fetch(`${notificationsAPI}?action=markRead`, {
				method: 'POST',
				headers: { 'Authorization': this.token ? `Bearer ${this.token}` : '', 'Content-Type': 'application/json' },
				body: JSON.stringify({ id })
			});
			this.load();
			// Notify global listeners (e.g., sidebar badge) to refresh counts
			try { window.dispatchEvent(new CustomEvent('app:notification')); } catch {}
			if (window.notificationClient) window.notificationClient.getUnreadCount().then(()=>{});
		} catch {}
	}

	async markAll() {
		try {
			await fetch(`${notificationsAPI}?action=markAllRead`, {
				method: 'POST',
				headers: { 'Authorization': this.token ? `Bearer ${this.token}` : '' }
			});
			this.load();
			// Notify global listeners (e.g., sidebar badge) to refresh counts
			try { window.dispatchEvent(new CustomEvent('app:notification')); } catch {}
			if (window.notificationClient) window.notificationClient.getUnreadCount().then(()=>{});
		} catch {}
	}
}

window.pageNotifications = new PageNotifications();