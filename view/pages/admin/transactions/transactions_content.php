<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">All Transactions </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="transactionsManager.loadTransactions()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <label for="statusFilter" class="form-label">Status Filter</label>
                            <select class="form-select" id="statusFilter" onchange="transactionsManager.filterTransactions()">
                                <option value="">All Status</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="disputed">Disputed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="typeFilter" class="form-label">Type</label>
                            <select class="form-select" id="typeFilter" onchange="transactionsManager.filterTransactions()">
                                <option value="">All Types</option>
                                <option value="request">Request</option>
                                <option value="offer">Offer</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="dateFromFilter" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="dateFromFilter" onchange="transactionsManager.filterTransactions()">
                        </div>
                        <div class="col-md-2">
                            <label for="dateToFilter" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="dateToFilter" onchange="transactionsManager.filterTransactions()">
                        </div>
                        <div class="col-md-3">
                            <label for="searchTransactions" class="form-label">Search</label>
                            <input type="text" class="form-control" id="searchTransactions" placeholder="Search by location, notes..." onkeyup="transactionsManager.searchTransactions()">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button class="btn btn-outline-secondary" onclick="transactionsManager.clearFilters()">
                                    <i class="fas fa-times"></i>
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
        <!-- <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="totalTransactionsCount">0</h4>
                            <p class="card-text">Total</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exchange-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="scheduledTransactionsCount">0</h4>
                            <p class="card-text">Scheduled</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
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
                            <h4 class="card-title" id="inProgressTransactionsCount">0</h4>
                            <p class="card-text">In Progress</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-play fa-2x"></i>
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
                            <h4 class="card-title" id="completedTransactionsCount">0</h4>
                            <p class="card-text">Completed</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="cancelledTransactionsCount">0</h4>
                            <p class="card-text">Cancelled</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title" id="disputedTransactionsCount">0</h4>
                            <p class="card-text">Disputed</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
    </div>

    <!-- Transactions List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Transaction History</h5>
                </div>
                <div class="card-body">
                    <div id="transactionsContainer">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading transactions...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin view is read-only. Modals for transaction actions removed. Details modal is generated dynamically. -->
