<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">My Coin Requests</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                    <i class="fas fa-plus"></i> New Request
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="requestsManager.loadRequests()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

<!-- Targeted Offers Modal -->
<div class="modal fade" id="targetedOffersModal" tabindex="-1" aria-labelledby="targetedOffersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="targetedOffersModalLabel">Offers for this Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Quantity</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="targetedOffersTbody">
                            <tr><td colspan="7" class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <span class="text-muted me-auto" id="targetedOffersCount"></span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
    
</div>

<!-- Meeting Schedule Modal -->
<div class="modal fade" id="meetingScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Meeting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="meetingScheduleForm">
                <input type="hidden" id="schedule_request_id" name="id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Please select a date and time for the coin exchange meeting.
                    </div>
                    <div class="mb-3">
                        <label for="meeting_date" class="form-label">Meeting Date</label>
                        <input type="date" class="form-control" id="meeting_date" name="meeting_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="meeting_time" class="form-label">Meeting Time</label>
                        <input type="time" class="form-control" id="meeting_time" name="meeting_time" required>
                    </div>
                    <input type="hidden" class="form-control" id="meeting_quantity" name="meeting_quantity" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Accept & Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Status Filter</label>
                            <select class="form-select" id="statusFilter" onchange="requestsManager.filterRequests()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="matched">Matched</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="coinTypeFilter" class="form-label">Coin Type</label>
                            <select class="form-select" id="coinTypeFilter" onchange="requestsManager.filterRequests()">
                                <option value="">All Coin Types</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="searchRequests" class="form-label">Search</label>
                            <input type="text" class="form-control" id="searchRequests" placeholder="Search by location, notes..." onkeyup="requestsManager.searchRequests()">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button class="btn btn-outline-secondary" onclick="requestsManager.clearFilters()">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="totalRequestsCount">0</h4>
                            <p class="card-text">Total Requests</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-hand-holding-usd fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="activeRequestsCount">0</h4>
                            <p class="card-text">Active</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-play fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="matchedRequestsCount">0</h4>
                            <p class="card-text">Matched</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-handshake fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="completedRequestsCount">0</h4>
                            <p class="card-text">Completed</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Requests List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">My Requests</h5>
                </div>
                <div class="card-body">
                    <div id="requestsContainer">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading requests...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Request Modal -->
<div class="modal fade" id="createRequestModal" tabindex="-1" aria-labelledby="createRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createRequestModalLabel">Create Coin Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="coinRequestForm" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="request_coin_type" class="form-label">Coin Type</label>
                        <select class="form-select coin-type-select" id="request_coin_type" name="coin_type_id" required>
                            <option value="">Select coin type</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="request_quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="request_quantity" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="request_location" class="form-label">Preferred Meeting Location</label>
                        <input type="text" class="form-control" id="request_location" name="preferred_meeting_location" placeholder="e.g., SM Mall, Quezon City">
                    </div>
                    <input type="hidden" class="form-control" id="request_meeting_longitude" name="meeting_longitude" step="any">
                    <input type="hidden" class="form-control" id="request_meeting_latitude" name="meeting_latitude" step="any">
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-secondary" id="getLocationBtn">
                            <i class="fas fa-location-arrow"></i> Pick Meeting Location
                        </button>
                    </div>
                    <div class="mb-3">
                        <label for="request_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="request_notes" name="notes" rows="3" placeholder="Any additional information..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Location Picker Modal -->
<div class="modal fade" id="locationPickerModal" tabindex="-1" aria-labelledby="locationPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationPickerModalLabel">Select Meeting Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="height: 480px; padding: 0;">
                <div id="locationPickerMap" style="height: 100%; width: 100%;"></div>
            </div>
            <div class="modal-footer">
                <small class="text-muted me-auto">Click on the map to place the pin. Zoom 19 for precise selection.</small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
    
</div>
<!-- Edit Request Modal -->
<div class="modal fade" id="editRequestModal" tabindex="-1" aria-labelledby="editRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRequestModalLabel">Edit Coin Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editRequestForm" novalidate>
                <input type="hidden" id="edit_request_id" name="request_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_request_coin_type" class="form-label">Coin Type</label>
                        <select class="form-select coin-type-select" id="edit_request_coin_type" name="coin_type_id" required>
                            <option value="">Select coin type</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_request_quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="edit_request_quantity" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_request_location" class="form-label">Preferred Meeting Location</label>
                        <input type="text" class="form-control" id="edit_request_location" name="preferred_meeting_location" placeholder="e.g., SM Mall, Quezon City">
                    </div>
                    <input type="hidden" class="form-control" id="edit_request_meeting_longitude" name="meeting_longitude" step="any">
                    <input type="hidden" class="form-control" id="edit_request_meeting_latitude" name="meeting_latitude" step="any">
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-secondary" id="getEditLocationBtn">
                            <i class="fas fa-location-arrow"></i> Get Current Location
                        </button>
                    </div>
                    <div class="mb-3">
                        <label for="edit_request_notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="edit_request_notes" name="notes" rows="3" placeholder="Any additional information..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
