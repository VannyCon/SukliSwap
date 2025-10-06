let notificationsAPI = null;
let headerAPI = null;
let formHeaderAPI = null;
// let authManager = null;
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
		this.load();
		this.attachEvents();
	}

	attachEvents() {
		document.getElementById('refreshNotificationsBtn')?.addEventListener('click', () => this.load());
		document.getElementById('markAllReadBtn')?.addEventListener('click', () => this.markAll());
		window.addEventListener('app:notification', () => this.load());
	}

	async load() {
		try {
			const params = new URLSearchParams({ action: 'list', limit: '101' });
			const response = await axios.get(`${notificationsAPI}?action=list&${params}`, {
				headers: headerAPI
			});
			if (response.data.success) {
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
			// Treat missing is_read as unread
			const unreadCount = items.filter(n => String(n.is_read) === '0').length;
			this.countEl.textContent = String(unreadCount);
		const html = items.map(n => {
				const typeClass = this.typeToClass(n.type);
				const readClass = String(n.is_read) === '1' ? 'opacity-75' : '';
				const idSafe = parseInt(n.id, 10) || n.id;
			return `
				<a href="#" class="list-group-item list-group-item-action ${readClass}">
					<div class="d-flex w-100 justify-content-between">
						<h6 class="mb-1"><span class="badge bg-${typeClass} me-2">${(n.type||'info').toUpperCase()}</span>${this.escape(n.title||'Notification')}</h6>
						<small class="text-muted">${this.formatDate(n.created_at)}</small>
					</div>
					<p class="mb-1">${this.escape(n.message||'')}</p>
					<div class="d-flex gap-2">
							<button class="btn btn-sm btn-outline-success" onclick="pageNotifications.mark(${idSafe})">Mark as read</button>
					</div>
				</a>`;
		}).join('');
		this.listEl.innerHTML = html;
	}

		typeToClass(type) {
		switch (String(type||'').toLowerCase()) {
				case 'match': return 'success';
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
			await axios.post(`${notificationsAPI}?action=markRead`, { id }, {
				headers: formHeaderAPI
			});
				this.load();
				// Notify global listeners (e.g., sidebar badge) to refresh counts
				try { window.dispatchEvent(new CustomEvent('app:notification')); } catch {}
				if (window.notificationClient) { try { await window.notificationClient.getUnreadCount(100); } catch {} }
		} catch {}
	}

	async markAll() {
		try {
			await axios.post(`${notificationsAPI}?action=markAllRead`, {}, {
				headers: formHeaderAPI
			});
				this.load();
				// Notify global listeners (e.g., sidebar badge) to refresh counts
				try { window.dispatchEvent(new CustomEvent('app:notification')); } catch {}
				if (window.notificationClient) { try { await window.notificationClient.getUnreadCount(100); } catch {} }
		} catch {}
	}
}

window.pageNotifications = new PageNotifications();