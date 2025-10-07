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

        Object.keys(stats).forEach(key => {
            const element = document.getElementById(key.replace('_transactions', 'TransactionsCount'));
            if (element) {
                element.textContent = stats[key];
            }
        });
    }

    async viewTransactionDetails(transactionId) {
        try {
            const response = await axios.get(`${userTransactionsAPI}?action=getTransactionById&transaction_id=${transactionId}`, {
                headers: headerAPI
            });

            if (response.data.success) {
                const data = response.data.data;
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
                    <h6>Transaction Details</h6>
                    <p><strong>Coin:</strong> ${transaction.denomination} ${transaction.description}</p>
                    <p><strong>Quantity:</strong> ${transaction.quantity} pieces</p>
                    <p><strong>Transaction Type:</strong> ${transaction.isOffer === '1' ? 'Offer-based' : 'Request-based'}</p>
                    ${transaction.description ? `<p><strong>Description:</strong> ${transaction.description}</p>` : ''}
                </div>
                <div class="col-md-6">
                    <h6>Participants</h6>
                    <p><strong>Requestor:</strong> ${transaction.requestor_first_name} ${transaction.requestor_last_name}</p>
                    <p><strong>Requestor Username:</strong> @${transaction.requestor_username}</p>
                    <p><strong>Offeror:</strong> ${transaction.offeror_first_name} ${transaction.offeror_last_name}</p>
                    <p><strong>Offeror Username:</strong> @${transaction.offeror_username}</p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h6>Meeting Information</h6>
                    <p><strong>Location:</strong> ${transaction.meeting_location || 'To be determined'}</p>
                    <p><strong>Scheduled Time:</strong> ${transaction.scheduled_meeting_time ? this.formatMeetingTime(transaction.scheduled_meeting_time) : 'Not scheduled'}</p>
                    <p><strong>QR Code:</strong> ${transaction.qr_code}</p>
                    <p><strong>Status:</strong> <span class="badge bg-${this.getStatusColor(transaction.status)}">${transaction.status}</span></p>
                </div>
                <div class="col-md-6">
                    <h6>Timeline</h6>
                    <p><strong>Created:</strong> ${new Date(transaction.created_at).toLocaleString()}</p>
                    ${transaction.completion_time ? `<p><strong>Completed:</strong> ${new Date(transaction.completion_time).toLocaleString()}</p>` : ''}
                    ${transaction.requestor_rating ? `<p><strong>Requestor Rating:</strong> ${transaction.requestor_rating}/5 stars</p>` : ''}
                    ${transaction.offeror_rating ? `<p><strong>Offeror Rating:</strong> ${transaction.offeror_rating}/5 stars</p>` : ''}
                </div>
            </div>
        `;

        new bootstrap.Modal(document.getElementById('transactionDetailsModal')).show();
    }

    async startTransaction(transactionId) {
        try {
            // Fetch transaction to know roles and qr_code
            const resp = await axios.get(`${userTransactionsAPI}?action=getTransactionById&transaction_id=${transactionId}`, { headers: headerAPI });
            if (!resp.data.success) {
                CustomToast?.show?.('error', resp.data.message || 'Unable to start');
                return;
            }
            const t = resp.data.data;

            // Determine if current user is poser (scanner) or presenter
            const isPoser = String(t.poser_id) === String(this.currentUserId);

            // Prepare modal sections
            const modalEl = document.getElementById('startTransactionModal');
            const scannerSec = document.getElementById('qrScannerSection');
            const presentSec = document.getElementById('qrPresentSection');
            const roleNotice = document.getElementById('qrRoleNotice');
            const qrText = document.getElementById('qrCodeText');
            const qrCanvas = document.getElementById('qrCodeCanvas');
            const scanStatus = document.getElementById('qrScanStatus');

            // Reset UI
            scannerSec.classList.add('d-none');
            presentSec.classList.add('d-none');
            qrCanvas.innerHTML = '';
            qrText.textContent = '';
            scanStatus.textContent = '';

            if (isPoser) {
                roleNotice.innerHTML = '<i class="fas fa-camera"></i> You are the scanner. Please scan the other party\'s QR.';
                scannerSec.classList.remove('d-none');
                // Start scanner after modal shows
                setTimeout(() => this._initiateQrScanner(transactionId), 300);
            } else {
                roleNotice.innerHTML = '<i class="fas fa-qrcode"></i> Present this QR to the scanner.';
                presentSec.classList.remove('d-none');
                // Render QR code (t.qr_code)
                const qr = new QRCode(qrCanvas, {
                    text: t.qr_code,
                    width: 256,
                    height: 256,
                });
                qrText.textContent = t.qr_code;
            }

            new bootstrap.Modal(modalEl).show();
        } catch (e) {
            console.error('startTransaction error', e);
            CustomToast?.show?.('error', 'Failed to start transaction');
        }
    }

    async _initiateQrScanner(transactionId) {
        const scanStatus = document.getElementById('qrScanStatus');
        const readerEl = document.getElementById('qrReader');
        if (!window.Html5Qrcode || !readerEl) {
            scanStatus.textContent = 'Scanner not available on this device.';
            return;
        }
        try {
            const html5QrCode = new Html5Qrcode('qrReader');
            const config = { fps: 10, qrbox: 250 };
            const onScanSuccess = async (decodedText) => {
                scanStatus.textContent = 'QR scanned. Verifying...';
                try {
                    const formData = new FormData();
                    formData.append('transaction_id', transactionId);
                    formData.append('qr_code', decodedText);

                    const resp = await axios.post(`${userTransactionsAPI}?action=verifyAndComplete`, formData, { headers: formHeaderAPI });
                    const result = resp.data;
                    await html5QrCode.stop();
                    if (result.success) {
                        bootstrap.Modal.getInstance(document.getElementById('startTransactionModal'))?.hide();
                        new bootstrap.Modal(document.getElementById('qrSuccessModal')).show();
                        this.loadTransactions();
                    } else {
                        scanStatus.textContent = result.message || 'Verification failed. Try again.';
                    }
                } catch (err) {
                    console.error(err);
                    scanStatus.textContent = 'Network error. Try again.';
                }
            };
            const onScanFailure = (error) => {
                // No-op; keep scanning
            };
            await html5QrCode.start({ facingMode: 'environment' }, config, onScanSuccess, onScanFailure);
        } catch (err) {
            console.error('init scanner error', err);
            scanStatus.textContent = 'Unable to start camera.';
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
            const response = await axios.post(`${userTransactionsAPI}?action=completeTransaction`, formData, {
                headers: formHeaderAPI
            });

            const result = response.data;

            if (result.success) {
                CustomToast.show('success', 'Transaction completed successfully');
                form.reset();
                bootstrap.Modal.getInstance(document.getElementById('completeTransactionModal')).hide();
                this.loadTransactions();
            } else {
                CustomToast.show('error', result.message || 'Failed to complete transaction');
            }

        } catch (error) {
            console.error('Error completing transaction:', error);
            CustomToast.show('error', 'Failed to complete transaction');
        }
    }

    async cancelTransaction(transactionId) {
        if (!confirm('Are you sure you want to cancel this transaction?')) return;

        try {
            const response = await axios.delete(`${userTransactionsAPI}?action=cancelTransaction&transaction_id=${transactionId}`, {
                headers: formHeaderAPI
            });

            const result = response.data;

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
            const response = await axios.post(`${userTransactionsAPI}?action=reportDispute`, formData, {
                headers: formHeaderAPI
            });

            const result = response.data;

            if (result.success) {
                CustomToast.show('success', 'Dispute reported successfully');
                form.reset();
                bootstrap.Modal.getInstance(document.getElementById('disputeTransactionModal')).hide();
                this.loadTransactions();
            } else {
                CustomToast.show('error', result.message || 'Failed to report dispute');
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
