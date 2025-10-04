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
        // Complete transaction form
        document.getElementById('completeTransactionForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.completeTransaction();
        });

        // Dispute transaction form
        document.getElementById('disputeTransactionForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.reportDispute();
        });

        // Delete account confirmation
        document.getElementById('deleteConfirmation')?.addEventListener('input', (e) => {
            const confirmBtn = document.getElementById('confirmDeleteAccount');
            confirmBtn.disabled = e.target.value !== 'DELETE';
        });
    }

    async loadTransactions() {
        try {
            const response = await axios.get(`${userTransactionsAPI}?action=getMyTransactions`, {
                headers: headerAPI
            });
            if (response.data.success) {
                this.transactions = response.data.data;
                this.renderTransactions();
                this.updateStats();
            }

        } catch (error) {
            console.error('Error loading transactions:', error);
            this.showError('Failed to load transactions');
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
                    <p class="small">Start by creating requests or offers to begin trading coins!</p>
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
                                <i class="fas fa-exchange-alt fa-2x text-primary"></i>
                                <div class="small text-muted">Transaction #${transaction.id}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Request:</strong><br>
                                    <small>${transaction.request_coin_name}</small><br>
                                    <small class="text-muted">${transaction.request_quantity} pieces</small>
                                </div>
                                <div class="col-6">
                                    <strong>Offer:</strong><br>
                                    <small>${transaction.offer_coin_name}</small><br>
                                    <small class="text-muted">${transaction.offer_quantity} pieces</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="row">
                                <div class="col-12">
                                    <strong>Requester:</strong><br>
                                    <small>${transaction.requester_name}</small>
                                </div>
                                <div class="col-12 mt-1">
                                    <strong>Offerer:</strong><br>
                                    <small>${transaction.offerer_name}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <span class="badge bg-${this.getStatusColor(transaction.status)}">${transaction.status}</span><br>
                            <small class="text-muted">${new Date(transaction.created_at).toLocaleDateString()}</small>
                        </div>
                        <div class="col-md-1">
                            <small class="text-muted">${transaction.meeting_location || 'TBD'}</small>
                        </div>
                        <div class="col-md-2">
                            <div class="btn-group-vertical btn-group-sm">
                                <button class="btn btn-outline-info btn-sm" onclick="transactionsManager.viewTransactionDetails(${transaction.id})">
                                    <i class="fas fa-eye"></i> Details
                                </button>
                                ${this.getActionButtons(transaction)}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    getActionButtons(transaction) {
        const buttons = [];
        
        switch (transaction.status) {
            case 'scheduled':
                buttons.push(`
                    <button class="btn btn-outline-success btn-sm" onclick="transactionsManager.startTransaction(${transaction.id})">
                        <i class="fas fa-play"></i> Start
                    </button>
                `);
                break;
            case 'in_progress':
                buttons.push(`
                    <button class="btn btn-outline-success btn-sm" onclick="transactionsManager.completeTransactionModal(${transaction.id})">
                        <i class="fas fa-check"></i> Complete
                    </button>
                `);
                break;
            case 'completed':
                if (transaction.rating) {
                    buttons.push(`
                        <span class="badge bg-success">
                            <i class="fas fa-star"></i> ${transaction.rating}/5
                        </span>
                    `);
                }
                break;
        }

        if (['scheduled', 'in_progress'].includes(transaction.status)) {
            buttons.push(`
                <button class="btn btn-outline-danger btn-sm" onclick="transactionsManager.cancelTransaction(${transaction.id})">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-outline-warning btn-sm" onclick="transactionsManager.reportDisputeModal(${transaction.id})">
                    <i class="fas fa-exclamation-triangle"></i> Dispute
                </button>
            `);
        }

        return buttons.join('');
    }

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

    updateStats() {
        const stats = {
            total_transactions: this.transactions.length,
            scheduled_transactions: this.transactions.filter(t => t.status === 'scheduled').length,
            in_progress_transactions: this.transactions.filter(t => t.status === 'in_progress').length,
            completed_transactions: this.transactions.filter(t => t.status === 'completed').length,
            cancelled_transactions: this.transactions.filter(t => t.status === 'cancelled').length,
            disputed_transactions: this.transactions.filter(t => t.status === 'disputed').length
        };

        Object.keys(stats).forEach(key => {
            const element = document.getElementById(key.replace('_transactions', 'TransactionsCount'));
            if (element) {
                element.textContent = stats[key];
            }
        });
    }

    async viewTransactionDetails(transactionId) {
        try {
            const response = await axios.get(`${userTransactionsAPI}?action=getTransactionById&id=${transactionId}`, {
                headers: headerAPI
            });

            if (response.data.success) {
                const data = await response.data.data;
                this.showTransactionDetails(data);
            }
        } catch (error) {
            console.error('Error loading transaction details:', error);
            this.showError('Failed to load transaction details');
        }
    }

    showTransactionDetails(transaction) {
        const modalContent = document.getElementById('transactionDetailsContent');
        modalContent.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Request Details</h6>
                    <p><strong>Coin:</strong> ${transaction.request_coin_name}</p>
                    <p><strong>Quantity:</strong> ${transaction.request_quantity} pieces</p>
                    <p><strong>Requester:</strong> ${transaction.requester_name}</p>
                    ${transaction.request_notes ? `<p><strong>Notes:</strong> ${transaction.request_notes}</p>` : ''}
                </div>
                <div class="col-md-6">
                    <h6>Offer Details</h6>
                    <p><strong>Coin:</strong> ${transaction.offer_coin_name}</p>
                    <p><strong>Quantity:</strong> ${transaction.offer_quantity} pieces</p>
                    <p><strong>Offerer:</strong> ${transaction.offerer_name}</p>
                    ${transaction.offer_notes ? `<p><strong>Notes:</strong> ${transaction.offer_notes}</p>` : ''}
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h6>Meeting Information</h6>
                    <p><strong>Location:</strong> ${transaction.meeting_location || 'To be determined'}</p>
                    <p><strong>Status:</strong> <span class="badge bg-${this.getStatusColor(transaction.status)}">${transaction.status}</span></p>
                </div>
                <div class="col-md-6">
                    <h6>Timeline</h6>
                    <p><strong>Created:</strong> ${new Date(transaction.created_at).toLocaleString()}</p>
                    ${transaction.completed_at ? `<p><strong>Completed:</strong> ${new Date(transaction.completed_at).toLocaleString()}</p>` : ''}
                    ${transaction.rating ? `<p><strong>Rating:</strong> ${transaction.rating}/5 stars</p>` : ''}
                </div>
            </div>
        `;

        new bootstrap.Modal(document.getElementById('transactionDetailsModal')).show();
    }

    async startTransaction(transactionId) {
        if (!confirm('Are you sure you want to start this transaction?')) return;

        try {
            const response = await axios.put(`${userTransactionsAPI}?action=updateTransactionStatus&id=${transactionId}`, {
                status: 'in_progress'
            }, {
                headers: formHeaderAPI
            });


            const result = await response.data.data;
            
            if (result.success) {
                CustomToast.show('success', 'Transaction started successfully');
                this.loadTransactions();
            } else {
                CustomToast.show('error', result.message);
            }
        } catch (error) {
            console.error('Error starting transaction:', error);
            CustomToast.show('error', 'Failed to start transaction');
        }
    }

    completeTransactionModal(transactionId) {
        document.getElementById('complete_transaction_id').value = transactionId;
        new bootstrap.Modal(document.getElementById('completeTransactionModal')).show();
    }

    async completeTransaction() {
        const form = document.getElementById('completeTransactionForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await axios.post(`${userTransactionsAPI}?action=completeTransaction&id=${data.transaction_id}`, {
                headers: formHeaderAPI
            });

            const result = await response.data.data;

            if (result.success) {
                CustomToast.show('success', 'Transaction completed successfully');
                form.reset();
                bootstrap.Modal.getInstance(document.getElementById('completeTransactionModal')).hide();
                this.loadTransactions();
            }

        } catch (error) {
            console.error('Error completing transaction:', error);
            CustomToast.show('error', 'Failed to complete transaction');
        }
    }

    async cancelTransaction(transactionId) {
        if (!confirm('Are you sure you want to cancel this transaction?')) return;

        try {
            const response = await axios.delete(`${userTransactionsAPI}?action=cancelTransaction&id=${transactionId}`, {
                headers: formHeaderAPI
            });

            const result = await response.data.data;

            if (result.success) {
                CustomToast.show('success', 'Transaction cancelled successfully');
                this.loadTransactions();
            }

        } catch (error) {
            console.error('Error cancelling transaction:', error);
            this.showError('Failed to cancel transaction');
        }
    }

    reportDisputeModal(transactionId) {
        document.getElementById('dispute_transaction_id').value = transactionId;
        new bootstrap.Modal(document.getElementById('disputeTransactionModal')).show();
    }

    async reportDispute() {
        const form = document.getElementById('disputeTransactionForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await axios.post(`${userTransactionsAPI}?action=reportDispute&id=${data.transaction_id}`, {
                headers: formHeaderAPI
            });

            const result = await response.data.data;

            if (result.success) {
                CustomToast.show('success', 'Dispute reported successfully');
                form.reset();
                bootstrap.Modal.getInstance(document.getElementById('disputeTransactionModal')).hide();
                this.loadTransactions();
            }
        } catch (error) {
            console.error('Error reporting dispute:', error);
            this.showError('Failed to report dispute');
        }
    }

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

    showSuccess(message) {
        // You can implement a toast notification system here
        alert(message);
    }

    showError(message) {
        // You can implement a toast notification system here
        alert('Error: ' + message);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.transactionsManager = new TransactionsManager();
});
