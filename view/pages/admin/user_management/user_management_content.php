<?php require_once '../../../components/toast.php'; ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">User Management</h2>
                    <p class="text-muted mb-0">Manage user accounts, verification status, and permissions</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="refreshUserTable()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                    <button class="btn btn-primary" onclick="exportUsers()">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-1" id="totalUsersCount">0</h4>
                            <p class="card-text mb-0">Total Users</p>
                        </div>
                        <div>
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-1" id="pendingUsersCount">0</h4>
                            <p class="card-text mb-0">Pending Verification</p>
                        </div>
                        <div>
                            <i class="fas fa-clock fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-1" id="verifiedUsersCount">0</h4>
                            <p class="card-text mb-0">Verified Users</p>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-1" id="declinedUsersCount">0</h4>
                            <p class="card-text mb-0">Declined Users</p>
                        </div>
                        <div>
                            <i class="fas fa-times-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="statusFilter" class="form-label">Status Filter</label>
                    <select class="form-select" id="statusFilter" onchange="filterUsers()">
                        <option value="">All Users</option>
                        <option value="pending">Pending Verification</option>
                        <option value="verified">Verified</option>
                        <option value="declined">Declined</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="roleFilter" class="form-label">Role Filter</label>
                    <select class="form-select" id="roleFilter" onchange="filterUsers()">
                        <option value="">All Roles</option>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="moderator">Moderator</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="searchInput" class="form-label">Search Users</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by name, email, or username...">
                        <button class="btn btn-outline-secondary" type="button" onclick="searchUsers()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Users List</h5>
                <div class="d-flex align-items-center gap-2">
                    <label for="itemsPerPage" class="form-label mb-0">Show:</label>
                    <select class="form-select form-select-sm" id="itemsPerPage" style="width: auto;" onchange="changeItemsPerPage()">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-muted ms-2" id="showingInfo">Showing 0-0 of 0</span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Verification</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 mb-0 text-muted">Loading users...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <!-- Pagination -->
            <nav aria-label="Users pagination">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted" id="paginationInfo">Page 1 of 1</span>
                    </div>
                    <ul class="pagination pagination-sm mb-0" id="paginationContainer">
                        <!-- Pagination will be generated by JavaScript -->
                    </ul>
                </div>
            </nav>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="card mt-3" id="bulkActionsCard" style="display: none;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted" id="selectedCount">0 users selected</span>
                </div>
                <div class="btn-group">
                    <button class="btn btn-sm btn-success" onclick="bulkAction('verify')">
                        <i class="fas fa-check me-1"></i>Verify Selected
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="bulkAction('unverify')">
                        <i class="fas fa-times me-1"></i>Unverify Selected
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkAction('decline')">
                        <i class="fas fa-times-circle me-1"></i>Decline Selected
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="bulkAction('activate')">
                        <i class="fas fa-user-check me-1"></i>Activate Selected
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="bulkAction('deactivate')">
                        <i class="fas fa-user-slash me-1"></i>Deactivate Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <!-- User details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Overlay -->
<div id="imagePreviewOverlay" class="image-preview-overlay" style="display: none;">
    <div class="image-preview-container">
        <div class="image-preview-header">
            <h5 class="image-preview-title">Valid ID Preview</h5>
            <button type="button" class="btn-close" id="closeImagePreview" aria-label="Close"></button>
        </div>
        <div class="image-preview-body">
            <img id="previewImage" src="" class="img-fluid" style="max-height: 70vh; object-fit: contain;" alt="Valid ID Preview">
        </div>
        <div class="image-preview-footer">
            <button type="button" class="btn btn-secondary" id="closeImagePreviewBtn">Close</button>
            <a id="downloadImage" href="" download class="btn btn-primary">
                <i class="fas fa-download me-1"></i>Download
            </a>
        </div>
    </div>
</div>

<style>
.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
    color: #6c757d;
}

/* Image Preview Overlay Styles */
.image-preview-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.image-preview-container {
    background: white;
    border-radius: 8px;
    max-width: 90%;
    max-height: 90%;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.image-preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
}

.image-preview-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.image-preview-body {
    padding: 20px;
    text-align: center;
    flex: 1;
    overflow: auto;
}

.image-preview-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 20px;
    border-top: 1px solid #dee2e6;
}

#closeImagePreview, #closeImagePreviewBtn {
    cursor: pointer;
}

#closeImagePreview {
    background: none;
    border: none;
    font-size: 1.5rem;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.table td {
    vertical-align: middle;
    font-size: 0.875rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.action-buttons .btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    margin: 0 1px;
}

.pagination .page-link {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
}

.card-header h5 {
    font-weight: 600;
}

/* Valid ID Images Styles */
.valid-id-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    border: 1px solid #dee2e6;
}

.valid-id-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.valid-id-card img {
    transition: opacity 0.2s ease-in-out;
}

.valid-id-card:hover img {
    opacity: 0.8;
}

.valid-id-preview-btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Image Preview Modal Styles */
#previewImage {
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .action-buttons .btn {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
    
    .btn-group .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .valid-id-card {
        margin-bottom: 1rem;
    }
    
    .valid-id-preview-btn {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
}
</style>
