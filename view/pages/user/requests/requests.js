/**
 * Requests Manager - Handles coin request operations
 */
let coinExchangeAPI = null;
let headerAPI = null;
let formHeaderAPI = null;
let userRequestsAPI = null;
let coinTypes = [];
let requests = [];
class RequestsManager {
    constructor() {
        const authManager = new AuthManager();
        coinExchangeAPI = authManager.API_CONFIG.baseURL + 'coin_exchange.php';
        userRequestsAPI = authManager.API_CONFIG.baseURL + 'user_requests.php';
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
        this.init();
    }

    init() {
        this.loadCoinTypes();
        this.loadRequests();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Create request form
        document.getElementById('coinRequestForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.createRequest();
        });

        // Edit request form
        document.getElementById('editRequestForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateRequest();
        });

        // Location buttons
        document.getElementById('getLocationBtn')?.addEventListener('click', () => {
            this.getCurrentLocation('request');
        });

        document.getElementById('getEditLocationBtn')?.addEventListener('click', () => {
            this.getCurrentLocation('edit_request');
        });
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
                option.textContent = `${coinType.coin_name} (${coinType.coin_value})`;
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
                option.textContent = `${coinType.coin_name} (${coinType.coin_value})`;
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

            const response = await axios.get(`${coinExchangeAPI}?action=getMyRequests&${params}`, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.requests = response.data.data;
                this.renderRequests();
                this.updateStats();
            }
        } catch (error) {
            console.error('Error loading requests:', error);
            this.showError('Failed to load requests');
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
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                        <i class="fas fa-plus"></i> Create Your First Request
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = this.requests.map(request => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-1">
                            <img src="${request.coin_image || '/assets/images/default-coin.png'}" 
                                 alt="${request.coin_name}" class="img-thumbnail" style="width: 50px; height: 50px;">
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">${request.coin_name}</h6>
                            <small class="text-muted">Value: ${request.coin_value}</small>
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
                        <div class="col-md-2">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="requestsManager.editRequest(${request.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${request.status === 'active' ? `
                                    <button class="btn btn-outline-danger" onclick="requestsManager.cancelRequest(${request.id})">
                                        <i class="fas fa-times"></i>
                                    </button>
                                ` : ''}
                                <button class="btn btn-outline-secondary" onclick="requestsManager.viewRequest(${request.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
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
                </div>
            </div>
        `).join('');
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

    async createRequest() {
        const form = document.getElementById('coinRequestForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await axios.post(`${userRequestsAPI}`, data, {
                headers: formHeaderAPI
            });


            const result = response.data.data;
            
            if (result.success) {
                this.showSuccess('Request created successfully');
                form.reset();
                bootstrap.Modal.getInstance(document.getElementById('createRequestModal')).hide();
                this.loadRequests();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error creating request:', error);
            this.showError('Failed to create request');
        }
    }

    async updateRequest() {
        const form = document.getElementById('editRequestForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const requestId = data.request_id;

        try {
            const response = await axios.put(`${coinExchangeAPI}?action=updateRequest&request_id=${requestId}`, data, {
                headers: formHeaderAPI
            });

            const result = response.data.data;
            
            if (result.success) {
                this.showSuccess('Request updated successfully');
                bootstrap.Modal.getInstance(document.getElementById('editRequestModal')).hide();
                this.loadRequests();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error updating request:', error);
            this.showError('Failed to update request');
        }
    }

    editRequest(requestId) {
        const request = this.requests.find(r => r.id === requestId);
        if (!request) return;

        // Populate edit form
        document.getElementById('edit_request_id').value = request.id;
        document.getElementById('edit_request_coin_type').value = request.coin_type_id;
        document.getElementById('edit_request_quantity').value = request.quantity;
        document.getElementById('edit_request_location').value = request.preferred_meeting_location || '';
        document.getElementById('edit_request_meeting_longitude').value = request.meeting_longitude || '';
        document.getElementById('edit_request_meeting_latitude').value = request.meeting_latitude || '';
        document.getElementById('edit_request_notes').value = request.notes || '';

        // Show modal
        new bootstrap.Modal(document.getElementById('editRequestModal')).show();
    }

    async cancelRequest(requestId) {
        if (!confirm('Are you sure you want to cancel this request?')) return;

        try {
            const response = await axios.delete(`${coinExchangeAPI}?action=cancelRequest&request_id=${requestId}`, {
                headers: formHeaderAPI
            });

            const result = response.data.data;
            
            if (result.success) {
                CustomToast.success('Request cancelled successfully');
                this.loadRequests();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            console.error('Error cancelling request:', error);
            this.showError('Failed to cancel request');
        }
    }

    viewRequest(requestId) {
        const request = this.requests.find(r => r.id === requestId);
        if (!request) return;

        // Show request details in a modal or redirect to details page
        alert(`Request Details:\n\nCoin: ${request.coin_name}\nQuantity: ${request.quantity}\nStatus: ${request.status}\nLocation: ${request.preferred_meeting_location || 'Not specified'}\nNotes: ${request.notes || 'None'}`);
    }

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

    getCurrentLocation(prefix) {
        if (!navigator.geolocation) {
            this.showError('Geolocation is not supported by this browser');
            return;
        }

        const locationBtn = document.getElementById(prefix === 'request' ? 'getLocationBtn' : 'getEditLocationBtn');
        locationBtn.disabled = true;
        locationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                document.getElementById(`${prefix}_meeting_latitude`).value = lat;
                document.getElementById(`${prefix}_meeting_longitude`).value = lng;
                
                // Get address from coordinates (you might want to use a geocoding service)
                document.getElementById(`${prefix}_location`).value = `${lat}, ${lng}`;
                
                locationBtn.disabled = false;
                locationBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Get Current Location';
                
                this.showSuccess('Location updated successfully');
            },
            (error) => {
                locationBtn.disabled = false;
                locationBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Get Current Location';
                
                let message = 'Failed to get location';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message = 'Location access denied by user';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = 'Location information unavailable';
                        break;
                    case error.TIMEOUT:
                        message = 'Location request timed out';
                        break;
                }
                this.showError(message);
            }
        );
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
    window.requestsManager = new RequestsManager();
});
