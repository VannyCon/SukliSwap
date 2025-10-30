/**
 * Requests Manager - Handles coin request operations
 */
let coinExchangeAPI = null;
let headerAPI = null;
let formHeaderAPI = null;
let userRequestsAPI = null;
let trCoinRequestAPI = null;
let safePlaceAPI = null;
let coinTypes = [];
let requests = [];
class RequestsManager {
    constructor() {
        const authManager = new AuthManager();
        coinExchangeAPI = authManager.API_CONFIG.baseURL + 'coin_exchange.php';
        userRequestsAPI = authManager.API_CONFIG.baseURL + 'user_requests.php';
        trCoinRequestAPI = authManager.API_CONFIG.baseURL + 'tr_coin_request.php';
        safePlaceAPI = authManager.API_CONFIG.baseURL + 'safe_places.php';
        headerAPI = authManager.API_CONFIG.getHeaders();
        formHeaderAPI = authManager.API_CONFIG.getFormHeaders();
        this.authManager = authManager;
        this.requests = [];
        this.coinTypes = [];
        this.filters = {
            status: '',
            coin_type_id: '',
            search: ''
        };
        this.safePlaces = [];
        this.map = null;
        this.markers = [];
        this.init();
    }

    init() {
        this.loadCoinTypes();
        this.loadRequests();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Admin view is read-only, no event listeners needed for forms
    }

    async loadCoinTypes() {
        try {
            const response = await axios.get(`${coinExchangeAPI}?action=getCoinTypes`, {
                headers: headerAPI
            });
            
            if (response.data.success) {
                this.coinTypes = response.data.data;
                this.populateCoinTypeSelects();
            
            }
        } catch (error) {
            console.error('Error loading coin types:', error);
        }
    }

    populateCoinTypeSelects() {
        const selects = document.querySelectorAll('.coin-type-select');
        selects.forEach(select => {
            select.innerHTML = '<option value="">Select coin type</option>';
            this.coinTypes.forEach(coinType => {
                const option = document.createElement('option');
                option.value = coinType.id;
                // Use the correct field names from API response
                option.textContent = `${coinType.description} (₱${coinType.denomination})`;
                select.appendChild(option);
            });
        });

        // Populate filter dropdown
        const filterSelect = document.getElementById('coinTypeFilter');
        if (filterSelect) {
            filterSelect.innerHTML = '<option value="">All Coin Types</option>';
            this.coinTypes.forEach(coinType => {
                const option = document.createElement('option');
                option.value = coinType.id;
                // Use the correct field names from API response
                option.textContent = `${coinType.description} (₱${coinType.denomination})`;
                filterSelect.appendChild(option);
            });
        }
    }

    async loadRequests() {
        try {
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    params.append(key, this.filters[key]);
                }
            });

            // Admin view: load all requests from all users
            const response = await axios.get(`${userRequestsAPI}?action=getAllRequests&${params}`, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.requests = response.data.data;
                this.renderRequests();
                this.updateStats();
            }
        } catch (error) {
            console.error('Error loading requests:', error);
            CustomToast.show('error', 'Failed to load requests');
        }
    }

    renderRequests() {
        const container = document.getElementById('requestsContainer');
        if (!container) return;

        if (this.requests.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No requests found</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.requests.map(request => {
            return `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-1 justify-content-center text-center">
                            <img src="../../../../${request.coin_image_path || '/assets/images/default-coin.png'}" 
                                 alt="${request.description}" class=""s style="width: 50px; height: 50px;">
                        </div>
                        <div class="col-md-2">
                            <h6 class="mb-1">${request.coin_description}</h6>
                            <small class="text-muted">Value: ₱${request.denomination}</small>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted"><strong>User:</strong> ${request.username || request.first_name || 'N/A'}</small><br>
                            <small class="text-muted">${request.business_name || ''}</small>
                        </div>
                        <div class="col-md-2">
                            <strong>${request.quantity}</strong> pieces
                        </div>
                        <div class="col-md-2">
                            <span class="badge bg-${this.getStatusColor(request.status)}">${request.status}</span>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">${new Date(request.created_at).toLocaleDateString()}</small>
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-outline-info btn-sm" title="View Details" onclick="requestsManager.viewRequest(${request.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    ${request.notes ? `
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">
                                    <strong>Notes:</strong> ${request.notes}
                                </small>
                            </div>
                        </div>
                    ` : ''}
                    ${request.preferred_meeting_location ? `
                        <div class="row mt-1">
                            <div class="col-12">
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> ${request.preferred_meeting_location}
                                </small>
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `}).join('');
    }

    getStatusColor(status) {
        const colors = {
            'active': 'primary',
            'matched': 'warning',
            'completed': 'success',
            'cancelled': 'secondary'
        };
        return colors[status] || 'secondary';
    }

    updateStats() {
        const stats = {
            total_requests: this.requests.length,
            active_requests: this.requests.filter(r => r.status === 'active').length,
            matched_requests: this.requests.filter(r => r.status === 'matched').length,
            completed_requests: this.requests.filter(r => r.status === 'completed').length
        };

        Object.keys(stats).forEach(key => {
            const element = document.getElementById(key.replace('_requests', 'RequestsCount'));
            if (element) {
                element.textContent = stats[key];
            }
        });
    }

    viewRequest(requestId) {
        const request = this.requests.find(r => r.id == requestId);
        if (!request) {
            CustomToast.show('error', 'Request not found');
            return;
        }

        // Build detailed view
        const details = `
            <div class="request-details">
                <div class="row mb-3">
                    <div class="col-6"><strong>User:</strong></div>
                    <div class="col-6">${request.username || request.first_name || 'N/A'}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Coin Type:</strong></div>
                    <div class="col-6">${request.coin_description}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Denomination:</strong></div>
                    <div class="col-6">₱${request.denomination}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Quantity:</strong></div>
                    <div class="col-6">${request.quantity} pieces</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Status:</strong></div>
                    <div class="col-6"><span class="badge bg-${this.getStatusColor(request.status)}">${request.status}</span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><strong>Meeting Location:</strong></div>
                    <div class="col-6">${request.preferred_meeting_location || 'Not specified'}</div>
                </div>
                ${request.notes ? `
                    <div class="row mb-3">
                        <div class="col-6"><strong>Notes:</strong></div>
                        <div class="col-6">${request.notes}</div>
                    </div>
                ` : ''}
                <div class="row mb-3">
                    <div class="col-6"><strong>Created:</strong></div>
                    <div class="col-6">${new Date(request.created_at).toLocaleString()}</div>
                </div>
            </div>
        `;

        // Show in a custom modal
        this.showDetailsModal('Request Details', details);
    }

    showDetailsModal(title, content) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
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

    // Admin view is read-only - no create, update, delete, or accept/reject functionality

    filterRequests() {
        this.filters.status = document.getElementById('statusFilter').value;
        this.filters.coin_type_id = document.getElementById('coinTypeFilter').value;
        this.loadRequests();
    }

    searchRequests() {
        this.filters.search = document.getElementById('searchRequests').value;
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.loadRequests();
        }, 500);
    }

    clearFilters() {
        this.filters = {
            status: '',
            coin_type_id: '',
            search: ''
        };
        
        document.getElementById('statusFilter').value = '';
        document.getElementById('coinTypeFilter').value = '';
        document.getElementById('searchRequests').value = '';
        
        this.loadRequests();
    }

    // Removed location picker and other edit functionality - admin view is read-only
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.requestsManager = new RequestsManager();
});
