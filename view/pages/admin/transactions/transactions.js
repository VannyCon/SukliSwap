/**
 * Transactions Manager - Handles transaction operations
 */
let userTransactionsAPI = null;
let headerAPI = null;
let formHeaderAPI = null;
class TransactionsManager {
    constructor() {
        const authManager = new AuthManager();
        userTransactionsAPI = authManager.API_CONFIG.baseURL + 'user_transactions.php';
        headerAPI = authManager.API_CONFIG.getHeaders();
        formHeaderAPI = authManager.API_CONFIG.getFormHeaders();
        this.authManager = authManager;
        this.currentUserId = this.authManager.getUser().id;
        this.transactions = [];
        this.filters = {
            status: '',
            type: '',
            date_from: '',
            date_to: '',
            search: ''
        };
        this.init();
    }

    init() {
        this.loadTransactions();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Admin view is read-only, no event listeners needed for forms
    }

    async loadTransactions() {
        try {
            // Admin view: load all transactions from all users
            const response = await axios.get(`${userTransactionsAPI}?action=getAllTransactions`, {
                headers: headerAPI
            });
            if (response.data.success) {
                this.transactions = response.data.data;
                this.renderTransactions();
                this.updateStats();
            }

        } catch (error) {
            console.error('Error loading transactions:', error);
            CustomToast.show('error', 'Failed to load transactions');
        }
    }

    renderTransactions() {
        const container = document.getElementById('transactionsContainer');
        if (!container) return;

        if (this.transactions.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-exchange-alt fa-3x mb-3"></i>
                    <p>No transactions found</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.transactions.map(transaction => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <div class="text-center">
                                <i class="fas fa-exchange-alt fa-2x ${this.getIconColor(transaction)}"></i>
                                <div class="small text-muted">Transaction #${transaction.id}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="row">
                                <div class="col-12">
                                    <strong>Coin Type:</strong><br>
                                    <small>${transaction.denomination} ${transaction.description}</small><br>
                                    <small class="text-muted">${transaction.quantity} pieces</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="row">
                                <div class="col-12">
                                    <strong>Requestor:</strong><br>
                                    <small>${transaction.requestor_first_name} ${transaction.requestor_last_name}</small><br>       
                                </div>
                                <div class="col-12 mt-1">
                                    <strong>Offeror:</strong><br>
                                    <small>${transaction.offeror_first_name} ${transaction.offeror_last_name}</small><br>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <span class="badge bg-${this.getStatusColor(transaction.status)}">${transaction.status}</span><br>
                            <small class="text-muted">${transaction.scheduled_meeting_time ? this.formatMeetingTime(transaction.scheduled_meeting_time) : 'Not scheduled'}</small>
                        </div>
                        <div class="col-md-1">
                            <small class="text-muted">${transaction.meeting_location || 'TBD'}</small>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-info btn-sm" onclick="transactionsManager.viewTransactionDetails(${transaction.id})">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Admin view is read-only - no action buttons

    getStatusColor(status) {
        const colors = {
            'scheduled': 'warning',
            'in_progress': 'info',
            'completed': 'success',
            'cancelled': 'secondary',
            'disputed': 'danger'
        };
        return colors[status] || 'secondary';
    }

    getIconColor(transaction) {
        // If current user is the offeror, show green icon
        if (parseInt(transaction.offeror_id) === parseInt(this.currentUserId)) {
            return 'text-success';
        }
        // Otherwise show blue (primary) icon
        return 'text-primary';
    }

    formatMeetingTime(datetimeString) {
        try {
            const date = new Date(datetimeString);
            
            // Format options for readable date and time
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            };
            
            return date.toLocaleDateString('en-US', options);
        } catch (error) {
            console.error('Error formatting meeting time:', error);
            return datetimeString; // Return original string if formatting fails
        }
    }

    updateStats() {
        const stats = {
            total_transactions: this.transactions.length,
            scheduled_transactions: this.transactions.filter(t => t.status === 'scheduled').length,
            in_progress_transactions: this.transactions.filter(t => t.status === 'in_progress').length,
            completed_transactions: this.transactions.filter(t => t.status === 'completed').length,
            cancelled_transactions: this.transactions.filter(t => t.status === 'cancelled').length,
            disputed_transactions: this.transactions.filter(t => t.status === 'disputed').length
        };

        // Update stat cards if they exist
        if (document.getElementById('totalTransactionsCount')) {
            document.getElementById('totalTransactionsCount').textContent = stats.total_transactions;
        }
        if (document.getElementById('scheduledTransactionsCount')) {
            document.getElementById('scheduledTransactionsCount').textContent = stats.scheduled_transactions;
        }
        if (document.getElementById('inProgressTransactionsCount')) {
            document.getElementById('inProgressTransactionsCount').textContent = stats.in_progress_transactions;
        }
        if (document.getElementById('completedTransactionsCount')) {
            document.getElementById('completedTransactionsCount').textContent = stats.completed_transactions;
        }
    }

    viewTransactionDetails(transactionId) {
        const transaction = this.transactions.find(t => t.id == transactionId);
        if (!transaction) {
            CustomToast?.show?.('error', 'Transaction not found');
            return;
        }

        // Build detailed view
        const details = `
            <div class="transaction-details">
                <div class="row mb-3">
                    <div class="col-6"><strong>Transaction ID:</strong></div>
                    <div class="col-6">#${transaction.id}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Coin Type:</strong></div>
                    <div class="col-6">${transaction.denomination} ${transaction.description}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Quantity:</strong></div>
                    <div class="col-6">${transaction.quantity} pieces</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Requestor:</strong></div>
                    <div class="col-6">${transaction.requestor_first_name} ${transaction.requestor_last_name}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Offeror:</strong></div>
                    <div class="col-6">${transaction.offeror_first_name} ${transaction.offeror_last_name}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Status:</strong></div>
                    <div class="col-6"><span class="badge bg-${this.getStatusColor(transaction.status)}">${transaction.status}</span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Meeting Location:</strong></div>
                    <div class="col-6">${transaction.meeting_location || 'Not specified'}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Scheduled Time:</strong></div>
                    <div class="col-6">${transaction.scheduled_meeting_time ? this.formatMeetingTime(transaction.scheduled_meeting_time) : 'Not scheduled'}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Created:</strong></div>
                    <div class="col-6">${new Date(transaction.created_at).toLocaleString()}</div>
                </div>
                ${transaction.completion_time ? `
                    <div class="row mb-3">
                        <div class="col-6"><strong>Completed:</strong></div>
                        <div class="col-6">${new Date(transaction.completion_time).toLocaleString()}</div>
                    </div>
                ` : ''}
                ${transaction.requestor_rating ? `
                    <div class="row mb-3">
                        <div class="col-6"><strong>Requestor Rating:</strong></div>
                        <div class="col-6">${transaction.requestor_rating}/5 stars</div>
                    </div>
                ` : ''}
                ${transaction.offeror_rating ? `
                    <div class="row mb-3">
                        <div class="col-6"><strong>Offeror Rating:</strong></div>
                        <div class="col-6">${transaction.offeror_rating}/5 stars</div>
                    </div>
                ` : ''}
            </div>
        `;

        this.showDetailsModal('Transaction Details', details);
    }

    showDetailsModal(title, content) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${content}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
        bsModal.show();
    }

    // Admin view is read-only - no start, complete, cancel, or dispute functionality

    filterTransactions() {
        this.filters.status = document.getElementById('statusFilter').value;
        this.filters.type = document.getElementById('typeFilter').value;
        this.filters.date_from = document.getElementById('dateFromFilter').value;
        this.filters.date_to = document.getElementById('dateToFilter').value;
        this.loadTransactions();
    }

    searchTransactions() {
        this.filters.search = document.getElementById('searchTransactions').value;
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.loadTransactions();
        }, 500);
    }

    clearFilters() {
        this.filters = {
            status: '',
            type: '',
            date_from: '',
            date_to: '',
            search: ''
        };
        
        document.getElementById('statusFilter').value = '';
        document.getElementById('typeFilter').value = '';
        document.getElementById('dateFromFilter').value = '';
        document.getElementById('dateToFilter').value = '';
        document.getElementById('searchTransactions').value = '';
        
        this.loadTransactions();
    }

}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.transactionsManager = new TransactionsManager();
});
