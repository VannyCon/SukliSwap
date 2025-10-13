
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">SukliSwap Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="updateMapData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

<!-- Send Targeted Offer Modal (from Active Request view) -->
<div class="modal fade" id="sendTargetedOfferModal" tabindex="-1" aria-labelledby="sendTargetedOfferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sendTargetedOfferModalLabel">Send Offer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendTargetedOfferForm">
                <div class="modal-body">
                    <input type="hidden" id="tro_coin_type_id" name="coin_type_id">
                    <input type="hidden" id="tro_my_latitude" name="my_latitude">
                    <input type="hidden" id="tro_my_longitude" name="my_longitude">
                    <input type="hidden" id="tro_post_request_id" name="post_request_id">
                    <div class="mb-3">
                        <label for="tro_requested_quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="tro_requested_quantity" name="requested_quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="tro_message" class="form-label">Message (optional)</label>
                        <textarea class="form-control" id="tro_message" name="message" rows="3"></textarea>
                    </div>
                    <!-- <div class="mb-3">
                        <label for="tro_schedule" class="form-label">Schedule (optional)</label>
                        <input type="datetime-local" class="form-control" id="tro_schedule" name="scheduled_time">
                    </div> -->
                </div>
                <div class="modal-footer d-flex flex-column flex-sm-row gap-2">
                    <button type="button" class="btn btn-secondary w-100 w-sm-auto" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success w-100 w-sm-auto">Send Offer</button>
                </div>
            </form>
        </div>
    </div>
    
</div>

<!-- Send Targeted Request Modal (from Active Offer view) -->
<div class="modal fade" id="sendTargetedRequestModal" tabindex="-1" aria-labelledby="sendTargetedRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sendTargetedRequestModalLabel">Send Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendTargetedRequestForm">
                <div class="modal-body">
                    <input type="hidden" id="trr_coin_type_id" name="coin_type_id">
                    <input type="hidden" id="trr_my_latitude" name="my_latitude">
                    <input type="hidden" id="trr_my_longitude" name="my_longitude">
                    <input type="hidden" id="trr_post_offer_id" name="post_offer_id">
                    <div class="mb-3">
                        <label for="trr_offered_quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="trr_offered_quantity" name="offered_quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="trr_message" class="form-label">Message (optional)</label>
                        <textarea class="form-control" id="trr_message" name="message" rows="3"></textarea>
                    </div>
                    <!-- <div class="mb-3">
                        <label for="trr_schedule" class="form-label">Schedule (optional)</label>
                        <input type="datetime-local" class="form-control" id="trr_schedule" name="scheduled_time">
                    </div> -->
                </div>
                <div class="modal-footer d-flex flex-column flex-sm-row gap-2">
                    <button type="button" class="btn btn-secondary w-100 w-sm-auto" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success w-100 w-sm-auto">Send Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
    <!-- Tab content -->
    <div class="tab-content" id="dashboardTabs">
        <!-- Dashboard Tab -->
        <div class="tab-pane fade show active" id="dashboard">
            <div class="row">
                <!-- Stats Cards -->
                <div class="col-6 col-md-3 mb-3 mb-md-4">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h4 class="card-title mb-1 mb-md-2" id="activeRequestsCount">0</h4>
                                    <p class="card-text mb-0 small">Active Requests</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-hand-holding-usd fa-lg fa-md-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3 mb-3 mb-md-4">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h4 class="card-title mb-1 mb-md-2" id="activeOffersCount">0</h4>
                                    <p class="card-text mb-0 small">Active Offers</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-coins fa-lg fa-md-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3 mb-3 mb-md-4">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h4 class="card-title mb-1 mb-md-2" id="pendingMatchesCount">0</h4>
                                    <p class="card-text mb-0 small">Pending Matches</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-handshake fa-lg fa-md-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3 mb-3 mb-md-4">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h4 class="card-title mb-1 mb-md-2" id="activeTransactionsCount">0</h4>
                                    <p class="card-text mb-0 small">Active Transactions</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exchange-alt fa-lg fa-md-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <div class="row g-2">
                                <div class="col-12 col-sm-6">
                                    <button class="btn btn-info w-100 d-flex align-items-center justify-content-center" onclick="coinExchangeManager.loadActiveRequests()">
                                        <i class="fas fa-search me-2"></i> 
                                        <span class="d-none d-sm-inline">Browse</span> Requests
                                    </button>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <button class="btn btn-warning w-100 d-flex align-items-center justify-content-center" onclick="coinExchangeManager.loadActiveOffers()">
                                        <i class="fas fa-search me-2"></i> 
                                        <span class="d-none d-sm-inline">Browse</span> Offers
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <div id="recentActivityContainer">
                                <div class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>

            <!-- Browse Results Section -->
            <div class="row mt-2" id="browseResultsSection" style="display: none;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0" id="browseResultsTitle">Browse Results</h5>
                            <button class="btn btn-sm btn-secondary" onclick="document.getElementById('browseResultsSection').style.display='none'">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="activeRequestsContainer">
                                <!-- Active requests will be loaded here -->
                            </div>
                            <div id="activeOffersContainer">
                                <!-- Active offers will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Requests Tab -->
        <div class="tab-pane fade" id="requests">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 gap-2">
                <h3 class="mb-0">My Coin Requests</h3>
                <button class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                    <i class="fas fa-plus me-2"></i> 
                    <span class="d-none d-sm-inline">New</span> Request
                </button>
            </div>
            <div id="userRequestsContainer">
                <div class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>

        <!-- My Offers Tab -->
        <div class="tab-pane fade" id="offers">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 gap-2">
                <h3 class="mb-0">My Coin Offers</h3>
                <button class="btn btn-success d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#createOfferModal">
                    <i class="fas fa-plus me-2"></i> 
                    <span class="d-none d-sm-inline">New</span> Offer
                </button>
            </div>
            <div id="userOffersContainer">
                <div class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>

        <!-- Matches Tab -->
        <div class="tab-pane fade" id="matches">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-3 gap-2">
                <h3 class="mb-0">My Matches</h3>
                <div class="btn-group w-100 w-lg-auto" role="group">
                    <input type="radio" class="btn-check" name="matchFilter" id="allMatches" value="" checked>
                    <label class="btn btn-outline-primary" for="allMatches">All</label>

                    <input type="radio" class="btn-check" name="matchFilter" id="pendingMatches" value="pending">
                    <label class="btn btn-outline-warning" for="pendingMatches">Pending</label>

                    <input type="radio" class="btn-check" name="matchFilter" id="acceptedMatches" value="accepted">
                    <label class="btn btn-outline-success" for="acceptedMatches">Accepted</label>
                </div>
            </div>
            <div id="userMatchesContainer">
                <div class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>

        <!-- Transactions Tab -->
        <div class="tab-pane fade" id="transactions">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-3 gap-2">
                <h3 class="mb-0">My Transactions</h3>
                <div class="btn-group w-100 w-lg-auto" role="group">
                    <input type="radio" class="btn-check" name="transactionFilter" id="allTransactions" value="" checked>
                    <label class="btn btn-outline-primary" for="allTransactions">All</label>

                    <input type="radio" class="btn-check" name="transactionFilter" id="scheduledTransactions" value="scheduled">
                    <label class="btn btn-outline-warning" for="scheduledTransactions">Scheduled</label>

                    <input type="radio" class="btn-check" name="transactionFilter" id="completedTransactions" value="completed">
                    <label class="btn btn-outline-success" for="completedTransactions">Completed</label>
                </div>
            </div>
            <div id="userTransactionsContainer">
                <div class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>

        <!-- Map View Tab -->
        <div class="tab-pane fade" id="map">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-3 gap-2">
                <h3 class="mb-0">Map View</h3>
                <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-lg-auto">
                    <button type="button" class="btn btn-outline-primary d-flex align-items-center justify-content-center" id="getCurrentLocationBtn">
                        <i class="fas fa-location-arrow me-2"></i> 
                        <span class="d-none d-sm-inline">Get</span> Location
                    </button>
                    <select class="form-select" id="mapTypeSelect">
                        <option value="streets">Streets</option>
                        <option value="satellite">Satellite</option>
                        <option value="terrain">Terrain</option>
                    </select>
                </div>
            </div>
            <div class="card">
                <div class="card-body p-0">
                    <div id="mapContainer" style="height: 400px; min-height: 300px; width: 100%;"></div>
                </div>
            </div>
        </div>

        <!-- Profile Tab -->
        <div class="tab-pane fade" id="profile">
            <div class="row">
                <div class="col-12 col-lg-8 mb-4 mb-lg-0">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form id="profileForm">
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="mb-3">
                                            <label for="business_name" class="form-label">Business Name</label>
                                            <input type="text" class="form-control" id="business_name" name="business_name">
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="mb-3">
                                            <label for="business_type" class="form-label">Business Type</label>
                                            <select class="form-control" id="business_type" name="business_type">
                                                <option value="">Select business type</option>
                                                <option value="store">Store</option>
                                                <option value="piso_wifi">PisoWiFi</option>
                                                <option value="restaurant">Restaurant</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Bio</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Tell us about yourself..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 w-md-auto">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div id="userStatsContainer">
                                <div class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Request Modal -->
<div class="modal fade" id="createRequestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Coin Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="coinRequestForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="request_coin_type" class="form-label">Coin Type</label>
                                <select class="form-control coin-type-select" id="request_coin_type" name="coin_type_id" required>
                                    <option value="">Select coin type</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="request_quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="request_quantity" name="quantity" min="1" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="request_location" class="form-label">Preferred Meeting Location</label>
                        <input type="text" class="form-control" id="request_location" name="preferred_meeting_location" placeholder="e.g., SM Mall, Quezon City">
                    </div>
                    <input type="hidden" class="form-control" id="request_meeting_longitude" name="meeting_longitude" step="any">
                    <input type="hidden" class="form-control" id="request_meeting_latitude" name="meeting_latitude" step="any">
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-secondary w-100 w-md-auto" id="getLocationBtn">
                            <i class="fas fa-location-arrow me-2"></i> Get Current Location
                        </button>
                    </div>
                    <div class="mb-3">
                        <label for="request_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="request_notes" name="notes" rows="3" placeholder="Any additional information..."></textarea>
                    </div>
                </div>
                <div class="modal-footer d-flex flex-column flex-sm-row gap-2">
                    <button type="button" class="btn btn-secondary w-100 w-sm-auto" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary w-100 w-sm-auto">Create Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Offer Modal -->
<div class="modal fade" id="createOfferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Coin Offer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="coinOfferForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="offer_coin_type" class="form-label">Coin Type</label>
                                <select class="form-control coin-type-select" id="offer_coin_type" name="coin_type_id" required>
                                    <option value="">Select coin type</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="offer_quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="offer_quantity" name="quantity" min="1" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="offer_location" class="form-label">Preferred Meeting Location</label>
                        <input type="text" class="form-control" id="offer_location" name="preferred_meeting_location" placeholder="e.g., SM Mall, Quezon City">
                    </div>
                    <input type="hidden" class="form-control" id="offer_meeting_longitude" name="meeting_longitude" step="any">
                    <input type="hidden" class="form-control" id="offer_meeting_latitude" name="meeting_latitude" step="any">
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-secondary w-100 w-md-auto" id="getOfferLocationBtn">
                            <i class="fas fa-location-arrow me-2"></i> Get Current Location
                        </button>
                    </div>
                    <div class="mb-3">
                        <label for="offer_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="offer_notes" name="notes" rows="3" placeholder="Any additional information..."></textarea>
                    </div>
                </div>
                <div class="modal-footer d-flex flex-column flex-sm-row gap-2">
                    <button type="button" class="btn btn-secondary w-100 w-sm-auto" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success w-100 w-sm-auto">Create Offer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include required scripts -->
<script src="https://unpkg.com/maplibre-gl@2.4.0/dist/maplibre-gl.js"></script>
<link href="https://unpkg.com/maplibre-gl@2.4.0/dist/maplibre-gl.css" rel="stylesheet" />

<!-- Custom Responsive Styles -->
<style>
/* Mobile-first responsive adjustments */
@media (max-width: 576px) {
    .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .modal-footer {
        padding: 0.75rem 1rem;
    }
    
    /* Stats cards on mobile */
    .card-title {
        font-size: 1.5rem;
    }
    
    .card-text {
        font-size: 0.875rem;
    }
    
    /* Map container responsive height */
    #mapContainer {
        height: 300px !important;
        min-height: 250px !important;
    }
    
    /* Button adjustments */
    .btn {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    /* Form controls */
    .form-control, .form-select {
        font-size: 16px; /* Prevents zoom on iOS */
    }
    
    /* Modal adjustments */
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .modal-content {
        border-radius: 0.5rem;
    }
}

@media (max-width: 768px) {
    /* Tab content adjustments */
    .tab-content {
        padding-top: 1rem;
    }
    
    /* Card spacing */
    .card {
        margin-bottom: 1rem;
    }
    
    /* Button group responsive */
    .btn-group {
        width: 100%;
    }
    
    .btn-group .btn {
        flex: 1;
    }
}

/* Tablet adjustments */
@media (min-width: 577px) and (max-width: 991px) {
    .col-md-3 {
        margin-bottom: 1rem;
    }
    
    .modal-dialog.modal-lg {
        max-width: 90%;
    }
}

/* Large screen adjustments */
@media (min-width: 1200px) {
    .container-fluid {
        max-width: 1400px;
        margin: 0 auto;
    }
}

/* Icon size adjustments */
@media (max-width: 576px) {
    .fas.fa-lg {
        font-size: 1.25rem !important;
    }
}

@media (min-width: 577px) {
    .fas.fa-md-2x {
        font-size: 2rem !important;
    }
}

/* Loading states */
/* .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
} */

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
// Initialize tab functionality
$(document).ready(function() {
    // Debug: Test modal functionality
    console.log('Dashboard initialized - testing modal...');
    
    // Test modal opening
    $('#createRequestModal').on('show.bs.modal', function (e) {
        console.log('Create Request Modal is opening...');
    });
    
    $('#createRequestModal').on('shown.bs.modal', function (e) {
        console.log('Create Request Modal is now visible!');
    });
    
    // Handle tab changes
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');
        
        switch(target) {
            case '#requests':
                loadUserRequests();
                break;
            case '#offers':
                loadUserOffers();
                break;
            case '#matches':
                loadUserMatches();
                break;
            case '#transactions':
                loadUserTransactions();
                break;
            case '#map':
                // Map is already initialized
                break;
            case '#profile':
                // Load profile data
                break;
        }
    });

    // Handle filter changes
    $('input[name="matchFilter"]').change(function() {
        const status = $(this).val();
        loadUserMatches(status);
    });

    $('input[name="transactionFilter"]').change(function() {
        const status = $(this).val();
        loadUserTransactions(status);
    });
});
</script>
