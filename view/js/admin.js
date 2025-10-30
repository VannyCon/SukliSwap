/**
 * SukliSwap Admin JavaScript
 * Handles admin dashboard functionality including user management, transactions, reports, and analytics
 */

class AdminManager {
    constructor() {
        this.apiConfig = {
            adminAPI: 'api/admin.php'
        };
        
        this.authManager = new AuthManager();
        this.currentUser = null;
        this.dashboardStats = null;
        this.recentActivity = [];
        this.users = [];
        this.transactions = [];
        this.reports = [];
        this.systemSettings = {};
        
        this.init();
    }

    async init() {
        try {
            // Check authentication
            if (!this.authManager.isAuthenticated()) {
                window.location.href = 'auth/login.php';
                return;
            }

            this.currentUser = this.authManager.getCurrentUser();
            
            // Check if user is admin
            if (this.currentUser.role !== 'admin') {
                window.location.href = 'user/dashboard.php';
                return;
            }

            // Load initial data
            await this.loadDashboardStats();
            await this.loadRecentActivity();
            
            // Initialize event listeners
            this.initEventListeners();
            
            // Start real-time updates
            this.startRealTimeUpdates();
            
        } catch (error) {
            console.error('Failed to initialize AdminManager:', error);
            this.showToast('Failed to initialize admin dashboard', 'error');
        }
    }

    initEventListeners() {
        // System settings form
        const settingsForm = document.getElementById('systemSettingsForm');
        if (settingsForm) {
            settingsForm.addEventListener('submit', (e) => this.handleUpdateSettings(e));
        }

        // Search functionality
        const userSearchInput = document.getElementById('userSearchInput');
        if (userSearchInput) {
            userSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.searchUsers();
                }
            });
        }

        const transactionSearchInput = document.getElementById('transactionSearchInput');
        if (transactionSearchInput) {
            transactionSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.searchTransactions();
                }
            });
        }
    }

    // ============ DASHBOARD DATA LOADING ============

    async loadDashboardStats() {
        try {
            const response = await axios.get(`${this.apiConfig.adminAPI}?action=getDashboardStats`, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                this.dashboardStats = response.data.data;
                this.updateDashboardStats();
            }
        } catch (error) {
            console.error('Failed to load dashboard stats:', error);
        }
    }

    async loadRecentActivity() {
        try {
            const response = await axios.get(`${this.apiConfig.adminAPI}?action=getRecentActivity`, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                this.recentActivity = response.data.data;
                this.renderRecentActivity();
            }
        } catch (error) {
            console.error('Failed to load recent activity:', error);
        }
    }

    updateDashboardStats() {
        if (!this.dashboardStats) return;

        // Update stat cards
        const elements = {
            'totalUsers': this.dashboardStats.total_users || 0,
            'activeRequests': this.dashboardStats.active_requests || 0,
            'activeOffers': this.dashboardStats.active_offers || 0,
            'totalTransactions': this.dashboardStats.total_transactions || 0,
            'pendingReportsCount': this.dashboardStats.pending_reports || 0
        };

        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        });
    }

    renderRecentActivity() {
        const container = document.getElementById('recentActivityContainer');
        if (!container) return;

        if (this.recentActivity.length === 0) {
            container.innerHTML = '<div class="text-center text-muted">No recent activity</div>';
            return;
        }

        const html = this.recentActivity.map(activity => `
            <div class="d-flex align-items-center mb-3">
                <div class="flex-shrink-0">
                    <i class="fas fa-${this.getActivityIcon(activity.activity_type)} text-primary"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="fw-bold">${activity.username}</div>
                    <div class="text-muted small">${activity.activity_type.replace('_', ' ').toUpperCase()}</div>
                    ${activity.details ? `<div class="text-muted small">${activity.details}</div>` : ''}
                </div>
                <div class="flex-shrink-0 text-muted small">
                    ${this.formatDate(activity.created_at)}
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    getActivityIcon(activityType) {
        const icons = {
            'user_registration': 'user-plus',
            'transaction_completed': 'check-circle',
            'new_report': 'flag'
        };
        return icons[activityType] || 'info-circle';
    }

    // ============ USER MANAGEMENT ============

    async loadUsers(status = '', search = '') {
        try {
            const params = new URLSearchParams({
                action: 'getAllUsers',
                page: 1,
                size: 50,
                status: status,
                search: search
            });

            const response = await axios.get(`${this.apiConfig.adminAPI}?${params}`, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                this.users = response.data.data;
                this.renderUsers();
            }
        } catch (error) {
            console.error('Failed to load users:', error);
        }
    }

    renderUsers() {
        const container = document.getElementById('usersContainer');
        if (!container) return;

        if (this.users.length === 0) {
            container.innerHTML = '<div class="text-center text-muted">No users found</div>';
            return;
        }

        const html = this.users.map(user => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="card-title">${user.username}</h6>
                            <p class="card-text">
                                <strong>Email:</strong> ${user.email}<br>
                                <strong>Name:</strong> ${user.first_name || ''} ${user.last_name || ''}<br>
                                <strong>Role:</strong> <span class="badge badge-${this.getRoleBadgeClass(user.role)}">${user.role}</span><br>
                                <strong>Status:</strong> <span class="badge badge-${user.is_active ? 'success' : 'danger'}">${user.is_active ? 'Active' : 'Inactive'}</span>
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <small class="text-muted">${this.formatDate(user.created_at)}</small>
                            <div class="mt-2">
                                <div class="btn-group" role="group">
                                    ${user.is_active ? 
                                        `<button class="btn btn-sm btn-outline-warning" onclick="adminManager.updateUserStatus(${user.id}, 'deactivate')">Deactivate</button>` :
                                        `<button class="btn btn-sm btn-outline-success" onclick="adminManager.updateUserStatus(${user.id}, 'activate')">Activate</button>`
                                    }
                                    <button class="btn btn-sm btn-outline-danger" onclick="adminManager.deleteUser(${user.id})">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    async updateUserStatus(userId, status) {
        if (!confirm(`Are you sure you want to ${status} this user?`)) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'updateUserStatus');
            formData.append('user_id', userId);
            formData.append('status', status);

            const response = await axios.post(this.apiConfig.adminAPI, formData, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                this.showToast(`User ${status}d successfully!`, 'success');
                await this.loadUsers();
            } else {
                this.showToast(response.data.message, 'error');
            }
        } catch (error) {
            console.error('Failed to update user status:', error);
            this.showToast('Failed to update user status', 'error');
        }
    }

    async deleteUser(userId) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'deleteUser');
            formData.append('user_id', userId);

            const response = await axios.post(this.apiConfig.adminAPI, formData, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                this.showToast('User deleted successfully!', 'success');
                await this.loadUsers();
            } else {
                this.showToast(response.data.message, 'error');
            }
        } catch (error) {
            console.error('Failed to delete user:', error);
            this.showToast('Failed to delete user', 'error');
        }
    }

    searchUsers() {
        const search = document.getElementById('userSearchInput').value;
        const status = document.querySelector('input[name="userFilter"]:checked').value;
        this.loadUsers(status, search);
    }

    // ============ TRANSACTION MANAGEMENT ============

    async loadTransactions(status = '', search = '') {
        try {
            const params = new URLSearchParams({
                action: 'getAllTransactions',
                page: 1,
                size: 50,
                status: status,
                search: search
            });

            const response = await axios.get(`${this.apiConfig.adminAPI}?${params}`, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                this.transactions = response.data.data;
                this.renderTransactions();
            }
        } catch (error) {
            console.error('Failed to load transactions:', error);
        }
    }

    renderTransactions() {
        const container = document.getElementById('transactionsContainer');
        if (!container) return;

        if (this.transactions.length === 0) {
            container.innerHTML = '<div class="text-center text-muted">No transactions found</div>';
            return;
        }

        const html = this.transactions.map(transaction => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="card-title">₱${transaction.denomination} Transaction</h6>
                            <p class="card-text">
                                <strong>Requestor:</strong> ${transaction.requestor_username}<br>
                                <strong>Offeror:</strong> ${transaction.offeror_username}<br>
                                <strong>Quantity:</strong> ${transaction.quantity}<br>
                                <strong>QR Code:</strong> ${transaction.qr_code}<br>
                                <strong>Status:</strong> <span class="badge badge-${this.getStatusBadgeClass(transaction.status)}">${transaction.status}</span>
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <small class="text-muted">${this.formatDate(transaction.created_at)}</small>
                            <div class="mt-2">
                                ${transaction.status === 'scheduled' ? 
                                    `<button class="btn btn-sm btn-outline-danger" onclick="adminManager.cancelTransaction(${transaction.id})">Cancel</button>` : 
                                    ''
                                }
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    async cancelTransaction(transactionId) {
        const reason = prompt('Please provide a reason for cancelling this transaction:');
        if (!reason) return;

        try {
            const formData = new FormData();
            formData.append('action', 'cancelTransaction');
            formData.append('transaction_id', transactionId);
            formData.append('reason', reason);

            const response = await axios.post(this.apiConfig.adminAPI, formData, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                this.showToast('Transaction cancelled successfully!', 'success');
                await this.loadTransactions();
            } else {
                this.showToast(response.data.message, 'error');
            }
        } catch (error) {
            console.error('Failed to cancel transaction:', error);
            this.showToast('Failed to cancel transaction', 'error');
        }
    }

    searchTransactions() {
        const search = document.getElementById('transactionSearchInput').value;
        const status = document.querySelector('input[name="transactionFilter"]:checked').value;
        this.loadTransactions(status, search);
    }

    // ============ REPORTS MANAGEMENT ============

    async loadReports(status = '') {
        try {
            const params = new URLSearchParams({
                action: 'getAllReports',
                page: 1,
                size: 50,
                status: status
            });

            const response = await axios.get(`${this.apiConfig.adminAPI}?${params}`, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                this.reports = response.data.data;
                this.renderReports();
            }
        } catch (error) {
            console.error('Failed to load reports:', error);
        }
    }

    renderReports() {
        const container = document.getElementById('reportsContainer');
        if (!container) return;

        if (this.reports.length === 0) {
            container.innerHTML = '<div class="text-center text-muted">No reports found</div>';
            return;
        }

        const html = this.reports.map(report => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="card-title">${report.title}</h6>
                            <p class="card-text">
                                <strong>Reporter:</strong> ${report.reporter_username}<br>
                                <strong>Reported User:</strong> ${report.reported_username || 'N/A'}<br>
                                <strong>Type:</strong> <span class="badge badge-${this.getReportTypeBadgeClass(report.type)}">${report.type.replace('_', ' ')}</span><br>
                                <strong>Status:</strong> <span class="badge badge-${this.getStatusBadgeClass(report.status)}">${report.status}</span><br>
                                <strong>Description:</strong> ${report.description}
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <small class="text-muted">${this.formatDate(report.created_at)}</small>
                            <div class="mt-2">
                                ${report.status === 'pending' ? 
                                    `<div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-success" onclick="adminManager.resolveReport(${report.id}, 'resolved')">Resolve</button>
                                        <button class="btn btn-sm btn-danger" onclick="adminManager.resolveReport(${report.id}, 'dismissed')">Dismiss</button>
                                    </div>` : 
                                    ''
                                }
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    async resolveReport(reportId, status) {
        const adminNotes = prompt('Please provide admin notes:');
        if (!adminNotes) return;

        try {
            const formData = new FormData();
            formData.append('action', 'resolveReport');
            formData.append('report_id', reportId);
            formData.append('status', status);
            formData.append('admin_notes', adminNotes);

            const response = await axios.post(this.apiConfig.adminAPI, formData, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                this.showToast(`Report ${status} successfully!`, 'success');
                await this.loadReports();
            } else {
                this.showToast(response.data.message, 'error');
            }
        } catch (error) {
            console.error('Failed to resolve report:', error);
            this.showToast('Failed to resolve report', 'error');
        }
    }

    // ============ ANALYTICS ============

    async loadAnalytics(days = 7) {
        try {
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - days);
            const endDate = new Date();

            const params = new URLSearchParams({
                action: 'generateAnalyticsReport',
                start_date: startDate.toISOString().split('T')[0],
                end_date: endDate.toISOString().split('T')[0]
            });

            const response = await axios.get(`${this.apiConfig.adminAPI}?${params}`, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                this.renderAnalytics(response.data.data);
            }
        } catch (error) {
            console.error('Failed to load analytics:', error);
        }
    }

    renderAnalytics(data) {
        // Render coin type volume chart
        this.renderCoinTypeChart(data);
        
        // Render daily trends chart
        this.renderDailyTrendsChart(data);
        
        // Render user activity summary
        this.renderUserActivitySummary();
    }

    renderCoinTypeChart(data) {
        const ctx = document.getElementById('coinTypeChart');
        if (!ctx) return;

        // This is a placeholder - you would need to implement actual chart rendering
        ctx.innerHTML = '<div class="text-center text-muted">Chart implementation needed</div>';
    }

    renderDailyTrendsChart(data) {
        const ctx = document.getElementById('dailyTrendsChart');
        if (!ctx) return;

        // This is a placeholder - you would need to implement actual chart rendering
        ctx.innerHTML = '<div class="text-center text-muted">Chart implementation needed</div>';
    }

    async renderUserActivitySummary() {
        try {
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - 30);
            const endDate = new Date();

            const params = new URLSearchParams({
                action: 'getUserActivitySummary',
                start_date: startDate.toISOString().split('T')[0],
                end_date: endDate.toISOString().split('T')[0]
            });

            const response = await axios.get(`${this.apiConfig.adminAPI}?${params}`, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                const container = document.getElementById('userActivitySummaryContainer');
                if (container) {
                    const users = response.data.data;
                    const html = users.map(user => `
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <strong>${user.username}</strong>
                            </div>
                            <div class="col-md-2">
                                <span class="badge badge-primary">${user.requests_made} requests</span>
                            </div>
                            <div class="col-md-2">
                                <span class="badge badge-success">${user.offers_made} offers</span>
                            </div>
                            <div class="col-md-2">
                                <span class="badge badge-info">${user.transactions_completed} completed</span>
                            </div>
                            <div class="col-md-3">
                                <span class="badge badge-warning">Rating: ${user.rating || 'N/A'}</span>
                            </div>
                        </div>
                    `).join('');
                    
                    container.innerHTML = html;
                }
            }
        } catch (error) {
            console.error('Failed to load user activity summary:', error);
        }
    }

    // ============ SETTINGS ============

    async loadSettings() {
        try {
            const response = await axios.get(`${this.apiConfig.adminAPI}?action=getSystemSettings`, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (response.data.success) {
                this.systemSettings = response.data.data;
                this.populateSettingsForm();
            }
        } catch (error) {
            console.error('Failed to load settings:', error);
        }
    }

    populateSettingsForm() {
        Object.entries(this.systemSettings).forEach(([key, setting]) => {
            const element = document.getElementById(key);
            if (element) {
                element.value = setting.setting_value;
            }
        });
    }

    async handleUpdateSettings(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        
        try {
            // Update each setting
            for (const [key, value] of formData.entries()) {
                await this.updateSystemSetting(key, value);
            }
            
            this.showToast('Settings updated successfully!', 'success');
        } catch (error) {
            console.error('Failed to update settings:', error);
            this.showToast('Failed to update settings', 'error');
        }
    }

    async updateSystemSetting(key, value) {
        try {
            const formData = new FormData();
            formData.append('action', 'updateSystemSetting');
            formData.append('key', key);
            formData.append('value', value);

            const response = await axios.post(this.apiConfig.adminAPI, formData, {
                headers: this.authManager.API_CONFIG.getHeaders()
            });

            if (!response.data.success) {
                throw new Error(response.data.message);
            }
        } catch (error) {
            console.error(`Failed to update setting ${key}:`, error);
            throw error;
        }
    }

    // ============ UTILITY METHODS ============

    getRoleBadgeClass(role) {
        const classes = {
            'admin': 'danger',
            'moderator': 'warning',
            'user': 'primary'
        };
        return classes[role] || 'secondary';
    }

    getStatusBadgeClass(status) {
        const classes = {
            'active': 'success',
            'pending': 'warning',
            'completed': 'success',
            'cancelled': 'danger',
            'scheduled': 'primary',
            'resolved': 'success',
            'dismissed': 'secondary'
        };
        return classes[status] || 'secondary';
    }

    getReportTypeBadgeClass(type) {
        const classes = {
            'user_behavior': 'danger',
            'transaction_issue': 'warning',
            'system_bug': 'info',
            'other': 'secondary'
        };
        return classes[type] || 'secondary';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    showToast(message, type = 'info') {
        console.log(`Toast [${type}]: ${message}`);
        
        if (typeof showToast === 'function') {
            showToast(message, type);
        }
    }

    // ============ REAL-TIME UPDATES ============

    startRealTimeUpdates() {
        // Update dashboard stats every 30 seconds
        setInterval(async () => {
            try {
                await this.loadDashboardStats();
                await this.loadRecentActivity();
            } catch (error) {
                console.error('Failed to update dashboard data:', error);
            }
        }, 30000);
    }

    // ============ PUBLIC METHODS ============

    async refreshData() {
        try {
            await this.loadDashboardStats();
            await this.loadRecentActivity();
            this.showToast('Data refreshed successfully!', 'success');
        } catch (error) {
            console.error('Failed to refresh data:', error);
            this.showToast('Failed to refresh data', 'error');
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.adminManager = new AdminManager();
});
