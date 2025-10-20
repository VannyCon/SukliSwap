
<div class="container-fluid">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshData()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab content -->
            <div class="tab-content" id="adminTabs">
                <!-- Dashboard Tab -->
                <div class="tab-pane fade show active" id="dashboard">
                    <div class="row">
                        <!-- Stats Cards -->
                        <div class="col-md-3 mb-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title" id="totalUsers">0</h4>
                                            <p class="card-text">Total Users</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-users fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title" id="activeRequests">0</h4>
                                            <p class="card-text">Active Requests</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-hand-holding-usd fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title" id="activeOffers">0</h4>
                                            <p class="card-text">Active Offers</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-coins fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="card-title" id="totalTransactions">0</h4>
                                            <p class="card-text">Total Transactions</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-exchange-alt fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="row">
                        <!-- <div class="col-md-8">
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

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">System Status</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>System Status:</span>
                                        <span class="badge badge-success">Online</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Database:</span>
                                        <span class="badge badge-success">Connected</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>API Status:</span>
                                        <span class="badge badge-success">Active</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Pending Reports:</span>
                                        <span class="badge badge-warning" id="pendingReportsCount">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Tab -->
                <div class="tab-pane fade" id="users">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>User Management</h3>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="userFilter" id="allUsers" value="" checked>
                            <label class="btn btn-outline-primary" for="allUsers">All</label>

                            <input type="radio" class="btn-check" name="userFilter" id="pendingUsers" value="pending">
                            <label class="btn btn-outline-warning" for="pendingUsers">Pending</label>

                            <input type="radio" class="btn-check" name="userFilter" id="verifiedUsers" value="verified">
                            <label class="btn btn-outline-success" for="verifiedUsers">Verified</label>

                            <input type="radio" class="btn-check" name="userFilter" id="inactiveUsers" value="inactive">
                            <label class="btn btn-outline-danger" for="inactiveUsers">Inactive</label>
                        </div>
                    </div>

                    <!-- Search and filters -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="userSearchInput" placeholder="Search users...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="userRoleFilter">
                                <option value="">All Roles</option>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                                <option value="moderator">Moderator</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary" onclick="searchUsers()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>

                    <div id="usersContainer">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </div>
                    </div>
                </div>

                <!-- Transactions Tab -->
                <div class="tab-pane fade" id="transactions">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Transaction Management</h3>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="transactionFilter" id="allTransactions" value="" checked>
                            <label class="btn btn-outline-primary" for="allTransactions">All</label>

                            <input type="radio" class="btn-check" name="transactionFilter" id="scheduledTransactions" value="scheduled">
                            <label class="btn btn-outline-warning" for="scheduledTransactions">Scheduled</label>

                            <input type="radio" class="btn-check" name="transactionFilter" id="completedTransactions" value="completed">
                            <label class="btn btn-outline-success" for="completedTransactions">Completed</label>

                            <input type="radio" class="btn-check" name="transactionFilter" id="cancelledTransactions" value="cancelled">
                            <label class="btn btn-outline-danger" for="cancelledTransactions">Cancelled</label>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="transactionSearchInput" placeholder="Search transactions...">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary" onclick="searchTransactions()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>

                    <div id="transactionsContainer">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </div>
                    </div>
                </div>

                <!-- Reports Tab -->
                <div class="tab-pane fade" id="reports">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Reports & Disputes</h3>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="reportFilter" id="allReports" value="" checked>
                            <label class="btn btn-outline-primary" for="allReports">All</label>

                            <input type="radio" class="btn-check" name="reportFilter" id="pendingReports" value="pending">
                            <label class="btn btn-outline-warning" for="pendingReports">Pending</label>

                            <input type="radio" class="btn-check" name="reportFilter" id="resolvedReports" value="resolved">
                            <label class="btn btn-outline-success" for="resolvedReports">Resolved</label>
                        </div>
                    </div>

                    <div id="reportsContainer">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </div>
                    </div>
                </div>

                <!-- Analytics Tab -->
                <div class="tab-pane fade" id="analytics">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Analytics & Reports</h3>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="analyticsFilter" id="last7Days" value="7" checked>
                            <label class="btn btn-outline-primary" for="last7Days">Last 7 Days</label>

                            <input type="radio" class="btn-check" name="analyticsFilter" id="last30Days" value="30">
                            <label class="btn btn-outline-primary" for="last30Days">Last 30 Days</label>

                            <input type="radio" class="btn-check" name="analyticsFilter" id="last90Days" value="90">
                            <label class="btn btn-outline-primary" for="last90Days">Last 90 Days</label>
                        </div>
                    </div>

                    <!-- Analytics Charts -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Transaction Volume by Coin Type</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="coinTypeChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Daily Transaction Trends</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="dailyTrendsChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Activity Summary -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">User Activity Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div id="userActivitySummaryContainer">
                                        <div class="text-center text-muted">
                                            <i class="fas fa-spinner fa-spin"></i> Loading...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div class="tab-pane fade" id="settings">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">System Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form id="systemSettingsForm">
                                        <div class="mb-3">
                                            <label for="max_meeting_radius" class="form-label">Maximum Meeting Radius (meters)</label>
                                            <input type="number" class="form-control" id="max_meeting_radius" name="max_meeting_radius" min="100" max="50000">
                                        </div>
                                        <div class="mb-3">
                                            <label for="default_request_expiry_hours" class="form-label">Default Request Expiry (hours)</label>
                                            <input type="number" class="form-control" id="default_request_expiry_hours" name="default_request_expiry_hours" min="1" max="168">
                                        </div>
                                        <div class="mb-3">
                                            <label for="default_offer_expiry_hours" class="form-label">Default Offer Expiry (hours)</label>
                                            <input type="number" class="form-control" id="default_offer_expiry_hours" name="default_offer_expiry_hours" min="1" max="168">
                                        </div>
                                        <div class="mb-3">
                                            <label for="min_rating_for_matching" class="form-label">Minimum Rating for Matching</label>
                                            <input type="number" class="form-control" id="min_rating_for_matching" name="min_rating_for_matching" min="0" max="5" step="0.1">
                                        </div>
                                        <div class="mb-3">
                                            <label for="max_active_requests_per_user" class="form-label">Max Active Requests per User</label>
                                            <input type="number" class="form-control" id="max_active_requests_per_user" name="max_active_requests_per_user" min="1" max="20">
                                        </div>
                                        <div class="mb-3">
                                            <label for="max_active_offers_per_user" class="form-label">Max Active Offers per User</label>
                                            <input type="number" class="form-control" id="max_active_offers_per_user" name="max_active_offers_per_user" min="1" max="20">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Update Settings</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">System Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>PHP Version:</span>
                                        <span><?php echo PHP_VERSION; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Database:</span>
                                        <span>MySQL</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Server:</span>
                                        <span><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Last Updated:</span>
                                        <span><?php echo date('Y-m-d H:i:s'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</div>

<!-- Include required scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Initialize admin functionality
$(document).ready(function() {
    // Handle tab changes
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');
        
        switch(target) {
            case '#users':
                loadUsers();
                break;
            case '#transactions':
                loadTransactions();
                break;
            case '#reports':
                loadReports();
                break;
            case '#analytics':
                loadAnalytics();
                break;
            case '#settings':
                loadSettings();
                break;
        }
    });

    // Handle filter changes
    $('input[name="userFilter"]').change(function() {
        const status = $(this).val();
        loadUsers(status);
    });

    $('input[name="transactionFilter"]').change(function() {
        const status = $(this).val();
        loadTransactions(status);
    });

    $('input[name="reportFilter"]').change(function() {
        const status = $(this).val();
        loadReports(status);
    });

    $('input[name="analyticsFilter"]').change(function() {
        const days = $(this).val();
        loadAnalytics(days);
    });
});
</script>