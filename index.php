<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable for development; comment out for production

session_start();

// Include required files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: modules/auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Thai Link BD Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .notification-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-xl font-bold text-blue-600">Thai Link BD</h1>
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="index.php" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2 text-sm font-medium">Dashboard</a>
                        <a href="modules/products/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Products</a>
                        <a href="modules/inventory/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Inventory</a>
                        <a href="modules/orders/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Orders</a>
                        <a href="modules/invoices/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Invoices</a>
                        <a href="modules/reports/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Reports</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="notifications-btn" class="relative text-gray-500 hover:text-gray-700">
                        <i class="fas fa-bell text-lg"></i>
                        <span id="notification-count" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center notification-badge hidden">0</span>
                    </button>
                    <div class="relative">
                        <button id="user-menu-btn" class="flex items-center text-sm text-gray-700 hover:text-gray-900">
                            <i class="fas fa-user-circle text-lg mr-2"></i>
                            <span><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></span>
                            <i class="fas fa-chevron-down ml-1"></i>
                        </button>
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="#" onclick="openProfileModal()" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                            <a href="#" onclick="openSettingsModal()" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                            <a href="api/auth.php?action=logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Welcome back, <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>!</h2>
            <p class="text-gray-600">Here's what's happening with your inventory today.</p>
        </div>

        <!-- Error Display -->
        <div id="error-container" class="hidden mb-6">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium">Failed to load dashboard data</h3>
                        <div class="mt-2 text-sm" id="error-message"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-box text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Products</dt>
                                <dd class="text-lg font-medium text-gray-900" id="total-products">Loading...</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Low Stock Items</dt>
                                <dd class="text-lg font-medium text-gray-900" id="low-stock-items">Loading...</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-shopping-cart text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Today's Orders</dt>
                                <dd class="text-lg font-medium text-gray-900" id="todays-orders">Loading...</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-dollar-sign text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Today's Revenue</dt>
                                <dd class="text-lg font-medium text-gray-900" id="todays-revenue">Loading...</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-users text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Customers</dt>
                                <dd class="text-lg font-medium text-gray-900" id="total-customers">Loading...</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-warehouse text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Inventory Value</dt>
                                <dd class="text-lg font-medium text-gray-900" id="inventory-value">Loading...</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-file-invoice text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending Invoices</dt>
                                <dd class="text-lg font-medium text-gray-900" id="pending-invoices">Loading...</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-600 rounded-md flex items-center justify-center">
                                <i class="fas fa-ban text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Out of Stock</dt>
                                <dd class="text-lg font-medium text-gray-900" id="out-of-stock">Loading...</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="modules/products/add.php" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mb-3">
                            <i class="fas fa-plus text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-900">Add Product</span>
                        <span class="text-xs text-gray-500">Create new product</span>
                    </a>

                    <a href="modules/orders/index.php" class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                        <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mb-3">
                            <i class="fas fa-shopping-cart text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-900">New Order</span>
                        <span class="text-xs text-gray-500">Process sale</span>
                    </a>

                    <a href="modules/inventory/adjust.php" class="flex flex-col items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                        <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center mb-3">
                            <i class="fas fa-edit text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-900">Adjust Stock</span>
                        <span class="text-xs text-gray-500">Update inventory</span>
                    </a>

                    <a href="modules/invoices/create.php" class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                        <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mb-3">
                            <i class="fas fa-file-invoice text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-900">Create Invoice</span>
                        <span class="text-xs text-gray-500">Generate invoice</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Alerts and Recent Activities -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Alerts -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">System Alerts</h3>
                </div>
                <div class="p-6">
                    <div id="alerts-container">
                        <div class="text-center text-gray-500">Loading alerts...</div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Activities</h3>
                </div>
                <div class="p-6">
                    <div id="recent-activities">
                        <div class="text-center text-gray-500">Loading activities...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profile-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">User Profile</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <p class="mt-1 text-sm text-gray-900 capitalize"><?php echo htmlspecialchars($user['role']); ?></p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button onclick="closeProfileModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div id="settings-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Dashboard Settings</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Auto Refresh</label>
                        <select id="refresh-interval" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="300000">5 minutes</option>
                            <option value="600000">10 minutes</option>
                            <option value="1800000">30 minutes</option>
                            <option value="3600000">1 hour</option>
                        </select>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" id="show-notifications" class="rounded">
                            <span class="ml-2 text-sm text-gray-700">Show notifications</span>
                        </label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button onclick="closeSettingsModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                    <button onclick="saveSettings()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Panel -->
    <div id="notifications-panel" class="hidden fixed top-16 right-4 w-80 bg-white shadow-lg rounded-lg border z-50">
        <div class="p-4 border-b">
            <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
        </div>
        <div id="notifications-list" class="max-h-96 overflow-y-auto">
            <div class="p-4 text-center text-gray-500">Loading notifications...</div>
        </div>
    </div>

    <script>
        let dashboardData = null;
        let refreshInterval = 300000; // 5 minutes default

        // Load dashboard data
        async function loadDashboardData() {
            try {
                const response = await fetch('api/dashboard.php', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    dashboardData = data.data;
                    updateSummaryCards();
                    updateAlerts();
                    updateRecentActivities();
                    updateNotifications();
                    hideError();
                    console.log('Dashboard data loaded successfully:', data);
                } else {
                    console.error('Failed to load dashboard data:', data.error);
                    showError('Failed to load dashboard data: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                showError('Error loading dashboard data: ' + error.message);
            }
        }

        // Update summary cards
        function updateSummaryCards() {
            const summary = dashboardData.summary;
            
            updateElement('total-products', summary.total_products || '0');
            updateElement('low-stock-items', summary.low_stock_items || '0');
            updateElement('todays-orders', summary.todays_orders || '0');
            updateElement('todays-revenue', '৳' + (summary.todays_revenue || '0.00'));
            updateElement('total-customers', summary.total_customers || '0');
            updateElement('inventory-value', '৳' + (summary.inventory_value || '0.00'));
            updateElement('pending-invoices', summary.pending_invoices || '0');
            updateElement('out-of-stock', summary.out_of_stock_items || '0');
        }

        // Helper to update element text
        function updateElement(id, value) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        }

        // Update alerts
        function updateAlerts() {
            const container = document.getElementById('alerts-container');
            const notifications = dashboardData.notifications || [];
            
            if (notifications.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500">No alerts at this time</div>';
                return;
            }
            
            container.innerHTML = notifications.map(notification => {
                const iconClass = notification.type === 'error' ? 'text-red-500' : 'text-yellow-500';
                const bgClass = notification.type === 'error' ? 'bg-red-50' : 'bg-yellow-50';
                
                return `
                    <div class="flex items-start p-3 ${bgClass} rounded-lg mb-3">
                        <i class="fas fa-exclamation-triangle ${iconClass} mt-1 mr-3"></i>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900">${notification.title}</div>
                            <div class="text-xs text-gray-600">${notification.message}</div>
                            ${notification.action ? `<a href="${notification.link}" class="text-xs text-blue-600 hover:text-blue-800">${notification.action}</a>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Update recent activities
        function updateRecentActivities() {
            const container = document.getElementById('recent-activities');
            const activities = dashboardData.recent_activities || [];
            
            if (activities.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500">No recent activities</div>';
                return;
            }
            
            container.innerHTML = activities.slice(0, 5).map((activity, index) => `
                <div class="flex items-start py-2 ${index < 4 ? 'border-b border-gray-200' : ''}">
                    <div class="flex-1">
                        <div class="text-sm text-gray-900">${activity.description}</div>
                        <div class="text-xs text-gray-500">by ${activity.full_name} • ${new Date(activity.created_at).toLocaleString()}</div>
                    </div>
                </div>
            `).join('');
        }

        // Update notifications
        function updateNotifications() {
            const notifications = dashboardData.notifications || [];
            const notificationCount = document.getElementById('notification-count');
            const notificationsList = document.getElementById('notifications-list');
            
            if (notifications.length > 0) {
                notificationCount.textContent = notifications.length;
                notificationCount.classList.remove('hidden');
                
                notificationsList.innerHTML = notifications.map(notification => `
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle ${notification.type === 'error' ? 'text-red-500' : 'text-yellow-500'}"></i>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900">${notification.title}</p>
                                <p class="text-xs text-gray-500">${notification.message}</p>
                                ${notification.action ? `<a href="${notification.link}" class="text-xs text-blue-600 hover:text-blue-800">${notification.action}</a>` : ''}
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                notificationCount.classList.add('hidden');
                notificationsList.innerHTML = '<div class="p-4 text-center text-gray-500">No notifications</div>';
            }
        }

        // Show error message
        function showError(message) {
            const errorContainer = document.getElementById('error-container');
            const errorMessage = document.getElementById('error-message');
            errorMessage.textContent = message;
            errorContainer.classList.remove('hidden');
        }

        // Hide error message
        function hideError() {
            const errorContainer = document.getElementById('error-container');
            errorContainer.classList.add('hidden');
        }

        // Modal functions
        function openProfileModal() {
            document.getElementById('profile-modal').classList.remove('hidden');
        }

        function closeProfileModal() {
            document.getElementById('profile-modal').classList.add('hidden');
        }

        function openSettingsModal() {
            document.getElementById('settings-modal').classList.remove('hidden');
            // Load saved settings
            const savedInterval = localStorage.getItem('refreshInterval') || '300000';
            const showNotifications = localStorage.getItem('showNotifications') !== 'false';
            
            document.getElementById('refresh-interval').value = savedInterval;
            document.getElementById('show-notifications').checked = showNotifications;
        }

        function closeSettingsModal() {
            document.getElementById('settings-modal').classList.add('hidden');
        }

        function saveSettings() {
            const interval = document.getElementById('refresh-interval').value;
            const showNotifications = document.getElementById('show-notifications').checked;
            
            localStorage.setItem('refreshInterval', interval);
            localStorage.setItem('showNotifications', showNotifications);
            
            refreshInterval = parseInt(interval);
            setupAutoRefresh();
            
            closeSettingsModal();
        }

        // Setup auto refresh
        function setupAutoRefresh() {
            // Clear existing interval
            if (window.dashboardInterval) {
                clearInterval(window.dashboardInterval);
            }
            
            // Set new interval
            window.dashboardInterval = setInterval(loadDashboardData, refreshInterval);
        }

        // Event listeners
        document.getElementById('user-menu-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        });

        document.getElementById('notifications-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            const panel = document.getElementById('notifications-panel');
            panel.classList.toggle('hidden');
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            const notificationsPanel = document.getElementById('notifications-panel');
            
            if (!event.target.closest('#user-menu-btn')) {
                userMenu.classList.add('hidden');
            }
            
            if (!event.target.closest('#notifications-btn')) {
                notificationsPanel.classList.add('hidden');
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load saved settings
            const savedInterval = localStorage.getItem('refreshInterval') || '300000';
            refreshInterval = parseInt(savedInterval);
            
            // Load initial data
            loadDashboardData();
            
            // Setup auto refresh
            setupAutoRefresh();
        });
    </script>
</body>
</html>

<?php
ob_end_flush();
?>