<?php
/**
 * Role-based Sidebar Component
 * Displays different navigation items based on user role (admin/staff)
 */
?>

<nav class="sidebar admin-sidebar">
    <!-- <header>
        <div class="image-text">
            <span class="image">
                <img src="../../../../assets/images/logo.png" alt="SukliSwap Logo">
            </span>
            <div class="text logo-text">
                <span class="name">SukliSwap</span>
            </div>
        </div>
        <i class='bx bx-chevron-right toggle'></i>
    </header> -->

    <div class="user-info-section d-flex align-items-center p-3 border-bottom bg-light">
        <div class="user-avatar flex-shrink-0">
            <img 
                id="userProfileImage" 
                src="../../../../assets/images/logo.png" 
                alt="User Avatar" 
                class="rounded-circle border border-2 border-secondary"
                style="width: 40px; height: 40px; object-fit: cover;"
            >
        </div>
        <div class="user-details ms-3 flex-grow-1">
            <div class="user-name d-flex align-items-center">
                <span id="userName" class="fw-bold me-2">Loading...</span>
                
            </div>
            <div class="user-badges">
                <span id="verificationBadge" class="badge bg-success text-white me-1" style="display:none;">
                    <i class="fas fa-check-circle me-1"></i>Verified
                </span>
                <span id="adminBadge" class="badge bg-danger text-white me-1" style="display:none;">
                    <i class="fas fa-crown me-1"></i>Admin
                </span>
                <span id="pendingBadge" class="badge bg-warning text-dark me-1" style="display:none;">
                    <i class="fas fa-clock me-1"></i>Pending
                </span>
            </div>
            <!-- <div class="user-email">
                <small style="font-size: 10px;" id="userEmail" class="text-muted">Loading...</small>
            </div> -->
        </div>
    </div>

    <div class="menu-bar">
        <div class="menu">
            <!-- Search Box -->
            <!-- <li class="search-box">
                <i class='bx bx-search icon'></i>
                <input type="text" placeholder="Search...">
            </li> -->

            <!-- Admin Navigation Items -->
            <div id="adminNavigation" style="display: none;">
                <li class="nav-link">
                    <a href="../../admin/dashboard/">
                        <i class='bx bx-tachometer icon'></i>
                        <span class="text nav-text">Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-link">
                    <a href="../../admin/coin_requests/">
                        <i class='fas fa-hand-holding-usd icon'></i>
                        <span class="text nav-text">Coin Requests</span>
                    </a>
                </li>
                
                <li class="nav-link">
                    <a href="../../admin/coin_offers/">
                        <i class='fas fa-coins icon'></i>
                        <span class="text nav-text">Coin Offers</span>
                    </a>
                </li>
                
                <li class="nav-link">
                    <a href="../../admin/transactions/">
                        <i class='fas fa-exchange-alt icon'></i>
                        <span class="text nav-text">Transactions</span>
                    </a>
                </li>

                <!-- <li class="nav-link">
                    <a href="../../admin/notifications/">
                        <i class='fas fa-bell icon'></i>
                        <span class="text nav-text">Notifications</span>
                        <span id="adminNotifBadge" class="badge bg-danger ms-2" style="display:none;">0</span>
                    </a>
                </li> -->
                
                <li class="nav-link">
                    <a href="../../admin/safe_places/">
                        <i class='fas fa-map-marker-alt icon'></i>
                        <span class="text nav-text">Safe Places</span>
                    </a>
                </li>
                
                <li class="nav-link">
                    <a href="../../admin/user_management/">
                        <i class='fas fa-users icon'></i>
                        <span class="text nav-text">User Management</span>
                    </a>
                </li>
                
                <li class="nav-link">
                    <a href="../../admin/reports/">
                        <i class='fas fa-flag icon'></i>
                        <span class="text nav-text">Reports</span>
                    </a>
                </li>
                
                <!-- <li class="nav-link">
                    <a href="../../admin/analytics/">
                        <i class='fas fa-chart-bar icon'></i>
                        <span class="text nav-text">Analytics</span>
                    </a>
                </li> -->

                <!-- <li class="nav-link">
                    <a href="../../admin/reports/">
                        <i class='fas fa-file-alt icon'></i>
                        <span class="text nav-text">Reports</span>
                    </a>
                </li> -->
                

            </div>

            <!-- User Navigation Items -->
            <div id="userNavigation" style="display: none;">
                <li class="nav-link">
                    <a href="../../user/dashboard/">
                        <i class='bx bx-home icon'></i>
                        <span class="text nav-text">Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-link">
                    <a href="../../user/requests/">
                        <i class='fas fa-hand-holding-usd icon'></i>
                        <span class="text nav-text">My Requests</span>
                    </a>
                </li>
                
                <li class="nav-link">
                    <a href="../../user/offers/">
                        <i class='fas fa-coins icon'></i>
                        <span class="text nav-text">My Offers</span>
                    </a>
                </li>
                
                <li class="nav-link">
                    <a href="../../user/transactions/">
                        <i class='fas fa-exchange-alt icon'></i>
                        <span class="text nav-text">My Transactions</span>
                    </a>
                </li>
                <li class="nav-link">
                    <a href="../../user/notifications/">
                        <i class='fas fa-bell icon'></i>
                        <span class="text nav-text">Notifications</span>
                        <span id="userNotifBadge" class="badge bg-danger ms-2" style="display:none;">0</span>
                    </a>
                </li>
                
                <li class="nav-link">
                    <a href="../../user/profile/">
                        <i class='bx bx-user icon'></i>
                        <span class="text nav-text">Profile</span>
                    </a>
                </li>
            </div>

            <!-- Common Navigation Items -->
            <!-- <li class="nav-link">
                <a href="#">
                    <i class='bx bx-help-circle icon'></i>
                    <span class="text nav-text">Help & Support</span>
                </a>
            </li> -->
        </div>

        <div class="bottom-content">
            <!-- Logout Button -->
            <li class="nav-link">
                <button class="btn-logout" onclick="handleLogout()">
                    <i class='bx bx-log-out icon'></i>
                    <span class="text nav-text">Logout</span>
                </button>
            </li>

            <!-- Dark/Light Mode Toggle -->
            <li class="mode">
                <div class="sun-moon">
                    <i class='bx bx-moon icon sun'></i>
                    <i class='bx bx-sun icon moon'></i>
                </div>
                <span class="mode-text text">Dark mode</span>
                <div class="toggle-switch">
                    <span class="switch"></span>
                </div>
            </li>
        </div>
    </div>
</nav>

<script>
// Initialize theme on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set initial theme attribute if not already set
    const savedTheme = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute('data-bs-theme', savedTheme);
    document.body.setAttribute('data-bs-theme', savedTheme);
});

/**
 * Sidebar functionality with role-based navigation
 */
let authManager = null;
class SidebarManager {
    constructor() {
        console.log("SidebarManager constructor");
        // this.authManager = window.authManager;
        const authManager = new AuthManager();
        this.authManager = authManager;
        this.init();
    }

    async init() {
        // Wait for authManager to be available if it's not ready yet
        if (!this.authManager && authManager) {
            this.authManager = authManager;
        }

        // Check if user is authenticated
        try {
            // Load user data and setup sidebar
            this.loadUserData();
            this.setupEventListeners();
            this.setActiveNavigation();
        } catch (error) {
            console.error('Error initializing sidebar:', error);
            // Fallback: try to load user data from localStorage
            this.loadUserDataFallback();
        }
    }

    loadUserData() {
        // Check if authManager is available
        if (!this.authManager) {
            console.warn('AuthManager not available, using fallback');
            this.loadUserDataFallback();
            return;
        }

        const user = this.authManager.getUser();
        console.log('User data loaded:', user);
        if (user) {
            this.updateUserInfo(user);
            console.log('User data loaded:', user.role);
            // Show appropriate navigation based on role
            this.showRoleBasedNavigation(user.role);
        } else {
            // No user data from authManager, try fallback
            this.loadUserDataFallback();
        }
    }

    loadUserDataFallback() {
        try {
            // Try to get user data from localStorage directly
            const userData = localStorage.getItem('user_data');
            if (userData) {
                const user = JSON.parse(userData);
                
                this.updateUserInfo(user);
                console.log('User data fallback:', user.role);
                // Show appropriate navigation based on role
                this.showRoleBasedNavigation(user.role);
                this.setupEventListeners();
                this.setActiveNavigation();
            } else {
                // No user data found, redirect to login
                this.redirectToLogin();
            }
        } catch (error) {
            console.error('Error loading user data fallback:', error);
            this.redirectToLogin();
        }
    }

    updateUserInfo(user) {
        try {
            // Update username display
            const userNameElement = document.getElementById('userName');
            if (userNameElement) {
                userNameElement.textContent = user.username || user.first_name || 'User';
                localStorage.setItem('user_id', user.id);
            }

            // Update email display
            const userEmailElement = document.getElementById('userEmail');
            if (userEmailElement) {
                userEmailElement.textContent = user.email || 'No email';
            }

            // Update profile image
            const userProfileImage = document.getElementById('userProfileImage');
            if (userProfileImage) {
                if (user.profile_image) {
                    // Construct the full path to the profile image
                    const profileImagePath = `../../../../data/profile/customer/${user.profile_image}`;
                    userProfileImage.src = profileImagePath;
                    userProfileImage.onerror = function() {
                        // Fallback to logo if image fails to load
                        this.src = '../../../../assets/images/logo.png';
                    };
                } else {
                    // No profile image, use logo as default
                    userProfileImage.src = '../../../../assets/images/logo.png';
                }
            }

            // Show/hide verification badge
            const verificationBadge = document.getElementById('verificationBadge');
            const pendingBadge = document.getElementById('pendingBadge');
            
            if (verificationBadge && pendingBadge) {
                if (user.is_verified == 1) {
                    verificationBadge.style.display = 'inline-block';
                    pendingBadge.style.display = 'none';
                } else {
                    verificationBadge.style.display = 'none';
                    pendingBadge.style.display = 'inline-block';
                }
            }

            // Show/hide admin badge
            const adminBadge = document.getElementById('adminBadge');
            if (adminBadge) {
                if (user.role === 'admin') {
                    verificationBadge.style.display = 'none';
                    adminBadge.style.display = 'inline-block';
                } else {
                    adminBadge.style.display = 'none';
                }
            }

            console.log('User info updated successfully:', {
                username: user.username,
                email: user.email,
                role: user.role,
                is_verified: user.is_verified
            });

        } catch (error) {
            console.error('Error updating user info:', error);
        }
    }

    showRoleBasedNavigation(role) {
        const adminNav = document.getElementById('adminNavigation');
        const userNav = document.getElementById('userNavigation');
        const sidebar = document.getElementById('sidebar');
        if (role === 'admin') {
            if (adminNav) adminNav.style.display = 'block';
            if (userNav) userNav.style.display = 'none';
        } else if (role === 'user') {
            if (userNav) userNav.style.display = 'block';
            if (adminNav) adminNav.style.display = 'none';
            if (sidebar) sidebar.style.display = 'none';
        }
    }

    setActiveNavigation() {
        // Get current page URL
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link a');

        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentPath.includes(href.replace('../', ''))) {
                link.closest('.nav-link').classList.add('active');
            }
        });
    }

    setupEventListeners() {
        // Handle navigation clicks
        const navLinks = document.querySelectorAll('.nav-link a');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Remove active class from all links
                navLinks.forEach(l => l.closest('.nav-link').classList.remove('active'));
                // Add active class to clicked link
                e.target.closest('.nav-link').classList.add('active');
            });
        });

        // Handle search functionality
        const searchInput = document.querySelector('.search-box input');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.handleSearch(e.target.value);
                }
            });
        }
    }

    handleSearch(query) {
        if (!query.trim()) return;

        // Implement search functionality based on user role
        let user = null;
        
        if (this.authManager) {
            user = this.authManager.getUser();
        } else {
            // Fallback: get user from localStorage
            const userData = localStorage.getItem('user_data');
            if (userData) {
                user = JSON.parse(userData);
            }
        }
        
        if (user) {
            if (user.role === 'admin') {
                // Admin search - search users, transactions, requests, offers
                console.log('Admin search:', query);
                // TODO: Implement admin search
            } else {
                // User search - search requests, offers, transactions
                console.log('User search:', query);
                // TODO: Implement user search
            }
        }
    }

    redirectToLogin() {
        window.location.href = '../../auth/login.php';
    }
}

/**
 * Handle logout functionality with modern modal
 */
async function handleLogout() {
    // Wait a bit for modal to be ready
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // Use modern confirmation modal
    if (window.confirmActions && window.confirmActions.logout) {
        window.confirmActions.logout(async () => {
            await performLogout();
        });
    } else if (window.modernConfirm) {
        // Fallback to modern confirm if confirmActions not available
        window.modernConfirm({
            message: 'Are you sure you want to logout? You will need to sign in again.',
            title: 'Logout Confirmation',
            type: 'warning',
            confirmText: 'Logout',
            cancelText: 'Stay Logged In',
            onConfirm: async () => {
                await performLogout();
            }
        });
    } else {
        // Final fallback to basic confirm
        const confirmed = confirm('Are you sure you want to logout?');
        if (confirmed) {
            await performLogout();
        }
    }
}

/**
 * Perform the actual logout process
 */
async function performLogout() {
    try {
        // Show loading state
        CustomToast.show('Logging out...', 'info');

        // Try to use auth manager if available
        if (window.authManager) {
            await window.authManager.logout();
        } else {
            // Fallback: manually clear auth data and redirect
            clearAuthData();
            window.location.href = '../../auth/login.php';
        }
        
        // Success message
        CustomToast.show('Logged out successfully', 'success');
        
    } catch (error) {
        console.error('Logout error:', error);
        CustomToast.show('Error during logout', 'error');
        
        // Force redirect even if API call fails
        clearAuthData();
        setTimeout(() => {
            window.location.href = '../../auth/login.php';
        }, 1000);
    }
}

/**
 * Clear authentication data manually
 */
function clearAuthData() {
    try {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        sessionStorage.clear();
    } catch (error) {
        console.error('Error clearing auth data:', error);
    }
}

// Initialize sidebar when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new SidebarManager();
    // Initialize notification badges
    try {
        const updateBadge = (el, count) => {
            if (!el) return;
            if (count > 0) {
                el.textContent = String(count > 99 ? '99+' : count);
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        };

        const adminBadge = document.getElementById('adminNotifBadge');
        const userBadge = document.getElementById('userNotifBadge');

        const refreshCounts = async () => {
            if (window.notificationClient && typeof window.notificationClient.getUnreadCount === 'function') {
                const count = await window.notificationClient.getUnreadCount(100);
                updateBadge(adminBadge, count);
                updateBadge(userBadge, count);
            }
        };

        // Initial load after a short delay to allow auth
        setTimeout(refreshCounts, 500);

        // Increment badge on each new notification
        window.addEventListener('app:notification', () => {
            // Lazy refresh to avoid drift
            refreshCounts();
        });

    } catch (e) { console.warn('Notifications badge init failed', e); }
});
</script>
