/**
 * SukliSwap User Management JavaScript
 * Handles user management functionality including user verification, filtering, and bulk operations
 */
let adminAPI = null;
let validIdAPI = null;
let headerAPI = null;
let formHeaderAPI = null;
let currentUser = null;

class UserManagementManager {
    constructor() {
        const authManager = new AuthManager();
        this.authManager = authManager;
        adminAPI = authManager.API_CONFIG.baseURL + 'admin.php';
        validIdAPI = authManager.API_CONFIG.baseURL + 'get_user_valid_ids.php';
        headerAPI = authManager.API_CONFIG.getHeaders();
        formHeaderAPI = authManager.API_CONFIG.getFormHeaders();
        this.currentUser = authManager.getUser();

        if (!authManager.isAuthenticated()) {
            window.location.href = '../../auth/login.php';
            return;
        }

        // Check if user is admin
        if (this.currentUser.role !== 'admin') {
            window.location.href = '../../user/dashboard/';
            return;
        }

        this.users = [];
        this.filteredUsers = [];
        this.currentPage = 1;
        this.itemsPerPage = 25;
        this.totalPages = 1;
        this.totalUsers = 0;
        this.selectedUsers = new Set();
        this.currentFilter = '';
        this.currentRoleFilter = '';
        this.searchTerm = '';
        
        this.init();
    }

    async init() {
        try {
            await this.loadUsers();
            this.setupEventListeners();
        } catch (error) {
            console.error('Failed to initialize UserManagementManager:', error);
            this.showToast('Failed to initialize user management', 'error');
        }
    }

    setupEventListeners() {
        // Search input with debounce
        const searchInput = document.getElementById('searchInput');
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.searchTerm = e.target.value;
                this.searchUsers();
            }, 300);
        });

        // Enter key for search
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.searchTerm = e.target.value;
                this.searchUsers();
            }
        });
    }

    async loadUsers() {
        try {
            const params = new URLSearchParams({
                action: 'getAllUsers',
                page: this.currentPage,
                size: this.itemsPerPage,
                filter: this.currentFilter,
                search: this.searchTerm,
                role: this.currentRoleFilter
            });

            const response = await axios.get(`${adminAPI}?action=getAllUsers&${params}`, {
                headers: headerAPI
            });

            if (response.data.success) {
                this.users = response.data.data.users || response.data.data;
                this.filteredUsers = this.users;
                this.totalUsers = response.data.meta?.total || this.users.length;
                this.totalPages = Math.ceil(this.totalUsers / this.itemsPerPage);
                
                // Update statistics if available
                if (response.data.data.statistics) {
                    this.updateStatistics(response.data.data.statistics);
                }
                
                this.renderTable();
                this.renderPagination();
                this.updatePaginationInfo();
            } else {
                this.showToast('Failed to load users: ' + response.data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading users:', error);
            this.showToast('Error loading users', 'error');
        }
    }

    updateStatistics(stats) {
        document.getElementById('totalUsersCount').textContent = stats.total || 0;
        document.getElementById('pendingUsersCount').textContent = stats.pending || 0;
        document.getElementById('verifiedUsersCount').textContent = stats.verified || 0;
        document.getElementById('declinedUsersCount').textContent = stats.declined || 0;
    }

    renderTable() {
        const tbody = document.getElementById('usersTableBody');
        
        if (this.filteredUsers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-users text-muted fa-3x mb-3"></i>
                        <p class="text-muted mb-0">No users found</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.filteredUsers.map(user => `
            <tr>
                <td>
                    <input type="checkbox" class="user-checkbox" value="${user.id}" onchange="userManager.toggleUserSelection(${user.id})">
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${user.profile_image ? `../../../../data/profile/customer/${user.profile_image}` : '../../../../assets/images/logo.png'}" 
                             class="user-avatar me-3" alt="User Avatar"
                             onerror="this.src='../../../../assets/images/logo.png'">
                        <div>
                            <div class="fw-bold">${user.username}</div>
                            <small class="text-muted">${user.first_name || ''} ${user.last_name || ''}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div>
                        <div class="small">${user.email}</div>
                        <small class="text-muted">${user.phone || 'No phone'}</small>
                    </div>
                </td>
                <td>
                    <span class="badge bg-${this.getRoleBadgeClass(user.role)}">${user.role}</span>
                </td>
                <td>
                    <span class="badge bg-${user.is_active == '1' ? 'success' : 'danger'} status-badge">
                        ${user.is_active == '1' ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <span class="badge bg-${user.is_verified == '1' ? 'success' : user.is_verified == '2' ? 'danger' : 'warning'} status-badge">
                        ${user.is_verified == '1' ? 'Verified' : user.is_verified == '2' ? 'Declined' : 'Pending'}
                    </span>
                </td>
                <td>
                    <small class="text-muted">${this.formatDate(user.created_at)}</small>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline-info" onclick="userManager.viewUserDetails(${user.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${user.is_verified == '0' ? 
                            `<button class="btn btn-sm btn-outline-success" onclick="userManager.verifyUser(${user.id})" title="Verify User">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="userManager.declineUser(${user.id})" title="Decline User">
                                <i class="fas fa-times-circle"></i>
                            </button>` : ''
                        }
                        ${user.is_verified == '1' ? 
                            ` <button class="btn btn-sm btn-outline-danger" onclick="userManager.declineUser(${user.id})" title="Block User">
                                <i class="fas fa-user-times"></i>
                            </button>` : ''
                        }
                 
                        <button class="btn btn-sm btn-outline-danger" onclick="userManager.deleteUser(${user.id})" title="Delete User">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    // ${user.is_active == '1' ? 
    //     `<button class="btn btn-sm btn-outline-warning" onclick="userManager.updateUserStatus(${user.id}, 'deactivate')" title="Deactivate User">
    //         <i class="fas fa-user-slash"></i>
    //     </button>` :
    //     `<button class="btn btn-sm btn-outline-success" onclick="userManager.updateUserStatus(${user.id}, 'activate')" title="Activate User">
    //         <i class="fas fa-user-check"></i>
    //     </button>`
    // }
    renderPagination() {
        const container = document.getElementById('paginationContainer');
        
        if (this.totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        
        // Previous button
        html += `
            <li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="userManager.goToPage(${this.currentPage - 1})">Previous</a>
            </li>
        `;

        // Page numbers
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(this.totalPages, this.currentPage + 2);

        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="userManager.goToPage(1)">1</a></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${i === this.currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="userManager.goToPage(${i})">${i}</a>
                </li>
            `;
        }

        if (endPage < this.totalPages) {
            if (endPage < this.totalPages - 1) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="#" onclick="userManager.goToPage(${this.totalPages})">${this.totalPages}</a></li>`;
        }

        // Next button
        html += `
            <li class="page-item ${this.currentPage === this.totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="userManager.goToPage(${this.currentPage + 1})">Next</a>
            </li>
        `;

        container.innerHTML = html;
    }

    updatePaginationInfo() {
        const start = (this.currentPage - 1) * this.itemsPerPage + 1;
        const end = Math.min(this.currentPage * this.itemsPerPage, this.totalUsers);
        
        document.getElementById('showingInfo').textContent = `Showing ${start}-${end} of ${this.totalUsers}`;
        document.getElementById('paginationInfo').textContent = `Page ${this.currentPage} of ${this.totalPages}`;
    }

    goToPage(page) {
        if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
            this.currentPage = page;
            this.loadUsers();
        }
    }

    changeItemsPerPage() {
        this.itemsPerPage = parseInt(document.getElementById('itemsPerPage').value);
        this.currentPage = 1;
        this.loadUsers();
    }

    async filterUsers() {
        this.currentFilter = document.getElementById('statusFilter').value;
        this.currentRoleFilter = document.getElementById('roleFilter').value;
        this.currentPage = 1;
        await this.loadUsers();
    }

    async searchUsers() {
        this.currentPage = 1;
        await this.loadUsers();
    }

    toggleUserSelection(userId) {
        if (this.selectedUsers.has(userId)) {
            this.selectedUsers.delete(userId);
        } else {
            this.selectedUsers.add(userId);
        }
        this.updateBulkActions();
    }

    toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const userCheckboxes = document.querySelectorAll('.user-checkbox');
        
        if (selectAllCheckbox.checked) {
            this.filteredUsers.forEach(user => {
                this.selectedUsers.add(user.id);
            });
            userCheckboxes.forEach(checkbox => checkbox.checked = true);
        } else {
            this.selectedUsers.clear();
            userCheckboxes.forEach(checkbox => checkbox.checked = false);
        }
        this.updateBulkActions();
    }

    updateBulkActions() {
        const bulkActionsCard = document.getElementById('bulkActionsCard');
        const selectedCount = document.getElementById('selectedCount');
        
        if (this.selectedUsers.size > 0) {
            bulkActionsCard.style.display = 'block';
            selectedCount.textContent = `${this.selectedUsers.size} user${this.selectedUsers.size > 1 ? 's' : ''} selected`;
        } else {
            bulkActionsCard.style.display = 'none';
        }
    }

    async bulkAction(action) {
        if (this.selectedUsers.size === 0) return;

        const actionText = {
            'verify': 'verify',
            'unverify': 'unverify', 
            'decline': 'decline',
            'activate': 'activate',
            'deactivate': 'deactivate'
        };

        const selectedCount = this.selectedUsers.size;
        if (!confirm(`Are you sure you want to ${actionText[action]} ${selectedCount} selected user(s)?`)) {
            return;
        }

        try {
            const promises = Array.from(this.selectedUsers).map(userId => {
                return this.performUserAction(action, userId);
            });

            await Promise.all(promises);
            
            this.selectedUsers.clear();
            document.getElementById('selectAll').checked = false;
            this.updateBulkActions();
            await this.loadUsers();
            
            this.showToast(`Successfully ${actionText[action]}ed ${selectedCount} user(s)`, 'success');
        } catch (error) {
            console.error('Bulk action error:', error);
            this.showToast('Error performing bulk action', 'error');
        }
    }

    async performUserAction(action, userId) {
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', action);
            if (action === 'verify') {
                formData.append('action', 'verifyUser');
            } else if (action === 'decline') {
                formData.append('action', 'declineUser');
            } else if (action === 'delete') {
                formData.append('action', 'deleteUser');
            } else if (action === 'activate') {
                formData.append('action', 'activateUser');
            } else if (action === 'deactivate') {
                formData.append('action', 'deactivateUser');
            }
            
            const response = await axios.post(`${adminAPI}`, formData, {
                headers: formHeaderAPI
            });

            if (!response.data.success) {
                throw new Error(response.data.message || 'Failed to perform action');
            }
        } catch (error) {
            console.error(`Failed to ${action} user:`, error);
            throw error;
        }
    }

    // Individual user actions
    async verifyUser(userId) {
        // Use modern confirmation modal
        if (window.confirmActions && window.confirmActions.logout) {
            window.confirmActions.success("Are you sure you want to verify this user?", async () => {
                await this.performUserAction('verify', userId);
                CustomToast.show('success', 'User verified successfully!');
                await this.loadUsers();
            });
        }
    }

    async unverifyUser(userId) {
        if (!confirm('Are you sure you want to unverify this user?')) return;
        await this.performUserAction('unverify', userId);
        this.showToast('User unverified successfully!', 'success');
        await this.loadUsers();
    }

    async declineUser(userId) {
        if (window.confirmActions && window.confirmActions.logout) {
            window.confirmActions.success("Are you sure you want to decline this user?", async () => {
                await this.performUserAction('decline', userId);
                CustomToast.show('success', 'User declined successfully!');
                await this.loadUsers();
            });
        }
    }

    async updateUserStatus(userId, status) {
        if (window.confirmActions && window.confirmActions.logout) {
            window.confirmActions.success(`Are you sure you want to ${status} this user?`, async () => {
                await this.performUserAction(status, userId);
                CustomToast.show('success', `User ${status}d successfully!`);
                await this.loadUsers();
            });
        }
    }

    async deleteUser(userId) {
        if (window.confirmActions && window.confirmActions.logout) {
            window.confirmActions.success("Are you sure you want to delete this user?", async () => {
                await this.performUserAction('delete', userId);
                CustomToast.show('success', 'User deleted successfully!');
                await this.loadUsers();
            });
        }
    }

    async viewUserDetails(userId) {
        try {
            const response = await axios.get(`${adminAPI}?action=getUserDetails&user_id=${userId}`, {
                headers: headerAPI
            });

            if (response.data.success) {
                const user = response.data.data;
                this.renderUserDetailsModal(user);
            } else {
                this.showToast('Failed to load user details', 'error');
            }
        } catch (error) {
            console.error('Error loading user details:', error);
            this.showToast('Error loading user details', 'error');
        }
    }

    async renderUserDetailsModal(user) {
        const modalContent = document.getElementById('userDetailsContent');
        
        // Load valid ID images
        let validIdImages = `
            <div class="mt-4">
                <h6 class="mb-3"><i class="fas fa-id-card me-2"></i>Valid ID Documents</h6>
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading valid ID documents...</span>
                    </div>
                    <p class="mt-2 mb-0 text-muted">Loading valid ID documents...</p>
                </div>
            </div>
        `;
        
        modalContent.innerHTML = `
            <div class="row">
                <div class="col-md-4 text-center">
                    <img src="${user.profile_image ? `../../../../data/profile/customer/${user.profile_image}` : '../../../../assets/images/logo.png'}" 
                         class="img-fluid rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;"
                         onerror="this.src='../../../../assets/images/logo.png'">
                    <h5>${user.username}</h5>
                    <p class="text-muted">${user.first_name || ''} ${user.last_name || ''}</p>
                </div>
                <div class="col-md-8">
                    <table class="table table-sm">
                        <tr><td><strong>Email:</strong></td><td>${user.email}</td></tr>
                        <tr><td><strong>Phone:</strong></td><td>${user.phone || 'N/A'}</td></tr>
                        <tr><td><strong>Role:</strong></td><td><span class="badge bg-${this.getRoleBadgeClass(user.role)}">${user.role}</span></td></tr>
                        <tr><td><strong>Status:</strong></td><td><span class="badge bg-${user.is_active ? 'success' : 'danger'}">${user.is_active ? 'Active' : 'Inactive'}</span></td></tr>
                        <tr><td><strong>Verification:</strong></td><td><span class="badge bg-${user.is_verified ? 'success' : 'warning'}">${user.is_verified ? 'Verified' : 'Pending'}</span></td></tr>
                        <tr><td><strong>Joined:</strong></td><td>${this.formatDate(user.created_at)}</td></tr>
                        <tr><td><strong>Last Updated:</strong></td><td>${this.formatDate(user.updated_at)}</td></tr>
                    </table>
                </div>
            </div>
            ${validIdImages}
        `;

        const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
        modal.show();

        // Load valid ID images asynchronously
        if (user.valid_id) {
            try {
                const validIdResponse = await axios.get(`${validIdAPI}?user_id=${user.id}`);
                console.log("validIdResponse", validIdResponse.data);
                if (validIdResponse.data.success && validIdResponse.data.data.valid_ids.length > 0) {
                    const images = validIdResponse.data.data.valid_ids;
                    validIdImages = `
                        <div class="mt-4">
                            <h6 class="mb-3"><i class="fas fa-id-card me-2"></i>Valid ID Documents (${images.length})</h6>
                            <div class="row g-2">
                                ${images.map((img, index) => `
                                    <div class="col-md-3 col-6">
                                        <div class="card h-100 valid-id-card">
                                            <img src="${img.file_path.startsWith('http') ? img.file_path : '../../../' + img.file_path}" 
                                                 class="card-img-top" 
                                                 style="height: 150px; object-fit: cover; cursor: pointer;"
                                                 onclick="previewValidIdImage('${img.file_path.startsWith('http') ? img.file_path : '../../../' + img.file_path}', '${img.filename}')"
                                                 alt="Valid ID ${index + 1}">
                                            <div class="card-body p-2">
                                                <small class="text-muted d-block">${img.filename}</small>
                                                <button class="btn btn-sm btn-outline-primary w-100 mt-1 valid-id-preview-btn" 
                                                        onclick="previewValidIdImage('${img.file_path.startsWith('http') ? img.file_path : '../../../' + img.file_path}', '${img.filename}')">
                                                    <i class="fas fa-eye me-1"></i>Preview
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                } else {
                    validIdImages = `
                        <div class="mt-4">
                            <h6 class="mb-3"><i class="fas fa-id-card me-2"></i>Valid ID Documents</h6>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No valid ID documents uploaded
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading valid ID images:', error);
                validIdImages = `
                    <div class="mt-4">
                        <h6 class="mb-3"><i class="fas fa-id-card me-2"></i>Valid ID Documents</h6>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Error loading valid ID documents
                        </div>
                    </div>
                `;
            }
        } else {
            validIdImages = `
                <div class="mt-4">
                    <h6 class="mb-3"><i class="fas fa-id-card me-2"></i>Valid ID Documents</h6>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No valid ID documents uploaded
                    </div>
                </div>
            `;
        }

        // Update the modal content with the loaded valid ID images
        const validIdContainer = modalContent.querySelector('.mt-4');
        if (validIdContainer) {
            validIdContainer.outerHTML = validIdImages;
        }
    }

    async exportUsers() {
        try {
            const response = await axios.get(`${adminAPI}?action=exportUsers&format=csv`, {
                headers: headerAPI
            });

            if (response.data.success) {
                // Create download link
                const blob = new Blob([response.data.data], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `users_export_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                this.showToast('Users exported successfully!', 'success');
            } else {
                this.showToast('Failed to export users', 'error');
            }
        } catch (error) {
            console.error('Export error:', error);
            this.showToast('Error exporting users', 'error');
        }
    }

    async refreshUserTable() {
        this.currentPage = 1;
        await this.loadUsers();
        this.showToast('User table refreshed!', 'success');
    }

    // Utility methods
    getRoleBadgeClass(role) {
        const classes = {
            'admin': 'danger',
            'moderator': 'warning',
            'user': 'primary'
        };
        return classes[role] || 'secondary';
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    showToast(message, type = 'info') {
        console.log(`Toast [${type}]: ${message}`);
        CustomToast.show(type, message);
    }
}

// Global functions for HTML onclick handlers
function filterUsers() {
    userManager.filterUsers();
}

function searchUsers() {
    userManager.searchUsers();
}

function changeItemsPerPage() {
    userManager.changeItemsPerPage();
}

function toggleSelectAll() {
    userManager.toggleSelectAll();
}

function refreshUserTable() {
    userManager.refreshUserTable();
}

function exportUsers() {
    userManager.exportUsers();
}

function bulkAction(action) {
    userManager.bulkAction(action);
}

// Global function for previewing valid ID images
function previewValidIdImage(imageUrl, filename) {
    const previewImage = document.getElementById('previewImage');
    const downloadLink = document.getElementById('downloadImage');
    const overlay = document.getElementById('imagePreviewOverlay');
    
    previewImage.src = imageUrl;
    downloadLink.href = imageUrl;
    downloadLink.download = filename;
    
    // Show the custom overlay
    overlay.style.display = 'flex';
    
    // Add event listeners for closing the overlay
    const closeBtn = document.getElementById('closeImagePreview');
    const closeBtn2 = document.getElementById('closeImagePreviewBtn');
    
    const closeOverlay = () => {
        overlay.style.display = 'none';
    };
    
    closeBtn.onclick = closeOverlay;
    closeBtn2.onclick = closeOverlay;
    
    // Close when clicking outside the container
    overlay.onclick = (e) => {
        if (e.target === overlay) {
            closeOverlay();
        }
    };
    
    // Close with Escape key
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            closeOverlay();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}

// Initialize user manager when DOM is loaded
let userManager;
document.addEventListener('DOMContentLoaded', function() {
    userManager = new UserManagementManager();
});