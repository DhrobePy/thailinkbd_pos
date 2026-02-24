<?php
session_start();
require_once '../../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../../modules/auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Thai Link BD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .modal { display: none; }
        .modal.active { display: flex; }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .slide-in { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-xl font-bold text-gray-900">Thai Link BD</h1>
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="../../index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Dashboard</a>
                        <a href="../products/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Products</a>
                        <a href="../inventory/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Inventory</a>
                        <a href="#" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2 text-sm font-medium">Orders</a>
                        <a href="../invoices/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Invoices</a>
                        <a href="../reports/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Reports</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></span>
                    <a href="../../api/auth.php?action=logout" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Order Management
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Manage orders, create invoices, and track deliveries
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <button onclick="openCreateOrderModal()" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus mr-2"></i>
                    Create Order
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-shopping-cart text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Orders</dt>
                                <dd class="text-lg font-medium text-gray-900" id="total-orders">Loading...</dd>
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
                                <i class="fas fa-clock text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending Orders</dt>
                                <dd class="text-lg font-medium text-gray-900" id="pending-orders">Loading...</dd>
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
                                <i class="fas fa-truck text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Partial Deliveries</dt>
                                <dd class="text-lg font-medium text-gray-900" id="partial-orders">Loading...</dd>
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
                                <i class="fas fa-check-circle text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Completed Orders</dt>
                                <dd class="text-lg font-medium text-gray-900" id="completed-orders">Loading...</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Filters</h3>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="filter-status" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial Delivery</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                        <select id="filter-customer" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Customers</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" id="filter-date-from" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" id="filter-date-to" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="mt-4 flex space-x-3">
                    <button onclick="applyFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                    <button onclick="clearFilters()" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        <i class="fas fa-times mr-2"></i>Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Orders</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Delivery</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="orders-table-body" class="bg-white divide-y divide-gray-200">
                        <!-- Orders will be loaded here -->
                    </tbody>
                </table>
            </div>
            <div id="orders-loading" class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                <p class="text-gray-500 mt-2">Loading orders...</p>
            </div>
            <div id="orders-empty" class="text-center py-8 hidden">
                <i class="fas fa-shopping-cart text-gray-400 text-4xl"></i>
                <p class="text-gray-500 mt-2">No orders found</p>
            </div>
        </div>
    </div>

    <!-- Create Order Modal -->
    <div id="create-order-modal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Create New Order</h3>
                    <button onclick="closeCreateOrderModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="create-order-form">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Customer *</label>
                            <select id="order-customer" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Customer</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Due Date</label>
                            <input type="date" id="order-due-date" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-md font-medium text-gray-900">Order Items</h4>
                            <button type="button" onclick="addOrderItem()" class="px-3 py-1 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm">
                                <i class="fas fa-plus mr-1"></i>Add Item
                            </button>
                        </div>
                        <div id="order-items-container">
                            <!-- Order items will be added here -->
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Discount Amount (৳)</label>
                            <input type="number" id="order-discount" step="0.01" min="0" value="0" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tax Rate (%)</label>
                            <input type="number" id="order-tax-rate" step="0.01" min="0" value="15" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Total Amount</label>
                            <div class="text-lg font-bold text-gray-900 py-2" id="order-total">৳0.00</div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea id="order-notes" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Order notes..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateOrderModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>Create Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="order-details-modal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Order Details</h3>
                    <button onclick="closeOrderDetailsModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="order-details-content">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Create Invoice Modal -->
    <div id="create-invoice-modal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Create Invoice</h3>
                    <button onclick="closeCreateInvoiceModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="create-invoice-content">
                    <!-- Invoice creation form will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="message-container" class="fixed top-4 right-4 z-50"></div>

    <script>
        let orders = [];
        let customers = [];
        let products = [];
        let currentOrder = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadOrders();
            loadCustomers();
            loadProducts();
            
            // Set default due date to 30 days from now
            const dueDate = new Date();
            dueDate.setDate(dueDate.getDate() + 30);
            document.getElementById('order-due-date').value = dueDate.toISOString().split('T')[0];
        });

        // Load orders
        async function loadOrders() {
            try {
                const response = await fetch('../../api/orders_invoices.php?action=get_orders');
                const data = await response.json();
                
                if (data.success) {
                    orders = data.orders;
                    updateSummaryCards(data.summary);
                    renderOrdersTable();
                } else {
                    showMessage('Error loading orders: ' + data.message, 'error');
                }
            } catch (error) {
                showMessage('Error loading orders: ' + error.message, 'error');
            }
        }

        // Load customers
        async function loadCustomers() {
            try {
                const response = await fetch('../../api/orders_invoices.php?action=get_customers');
                const data = await response.json();
                
                if (data.success) {
                    customers = data.customers;
                    populateCustomerDropdowns();
                }
            } catch (error) {
                console.error('Error loading customers:', error);
            }
        }

        // Load products
        async function loadProducts() {
            try {
                const response = await fetch('../../api/orders_invoices.php?action=get_products_for_order');
                const data = await response.json();
                
                if (data.success) {
                    products = data.products;
                }
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }

        // Update summary cards
        function updateSummaryCards(summary) {
            document.getElementById('total-orders').textContent = summary.total_orders;
            document.getElementById('pending-orders').textContent = summary.pending_orders;
            document.getElementById('partial-orders').textContent = summary.partial_orders;
            document.getElementById('completed-orders').textContent = summary.completed_orders;
        }

        // Render orders table
        function renderOrdersTable() {
            const tbody = document.getElementById('orders-table-body');
            const loading = document.getElementById('orders-loading');
            const empty = document.getElementById('orders-empty');
            
            loading.style.display = 'none';
            
            if (orders.length === 0) {
                tbody.innerHTML = '';
                empty.classList.remove('hidden');
                return;
            }
            
            empty.classList.add('hidden');
            
            tbody.innerHTML = orders.map(order => {
                const statusBadge = getStatusBadge(order.delivery_status);
                const orderStatusBadge = getOrderStatusBadge(order.status);
                
                return `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            #${order.id}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${order.customer_name || 'N/A'}</div>
                            <div class="text-sm text-gray-500">${order.customer_email || ''}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${formatDate(order.order_date)}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${order.total_items} items
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ৳${parseFloat(order.total_amount).toLocaleString()}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ${orderStatusBadge}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ${statusBadge}
                            <div class="text-xs text-gray-500 mt-1">
                                ${order.delivered_quantity}/${order.total_quantity} delivered
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="viewOrderDetails(${order.id})" class="text-blue-600 hover:text-blue-900" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="createInvoiceFromOrder(${order.id})" class="text-green-600 hover:text-green-900" title="Create Invoice">
                                    <i class="fas fa-file-invoice"></i>
                                </button>
                                ${order.delivery_status !== 'completed' ? `
                                    <button onclick="editOrder(${order.id})" class="text-yellow-600 hover:text-yellow-900" title="Edit Order">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteOrder(${order.id})" class="text-red-600 hover:text-red-900" title="Delete Order">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Get status badge
        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>',
                'partial': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Partial</span>',
                'completed': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Completed</span>'
            };
            return badges[status] || badges['pending'];
        }

        // Get order status badge
        function getOrderStatusBadge(status) {
            const badges = {
                'pending': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Pending</span>',
                'confirmed': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Confirmed</span>',
                'processing': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Processing</span>',
                'cancelled': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Cancelled</span>'
            };
            return badges[status] || badges['pending'];
        }

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        // Populate customer dropdowns
        function populateCustomerDropdowns() {
            const orderCustomer = document.getElementById('order-customer');
            const filterCustomer = document.getElementById('filter-customer');
            
            const customerOptions = customers.map(customer => 
                `<option value="${customer.id}">${customer.name} (${customer.customer_type})</option>`
            ).join('');
            
            orderCustomer.innerHTML = '<option value="">Select Customer</option>' + customerOptions;
            filterCustomer.innerHTML = '<option value="">All Customers</option>' + customerOptions;
        }

        // Modal functions
        function openCreateOrderModal() {
            document.getElementById('create-order-modal').classList.add('active');
            addOrderItem(); // Add first item
        }

        function closeCreateOrderModal() {
            document.getElementById('create-order-modal').classList.remove('active');
            document.getElementById('create-order-form').reset();
            document.getElementById('order-items-container').innerHTML = '';
            document.getElementById('order-total').textContent = '৳0.00';
        }

        function closeOrderDetailsModal() {
            document.getElementById('order-details-modal').classList.remove('active');
        }

        function closeCreateInvoiceModal() {
            document.getElementById('create-invoice-modal').classList.remove('active');
        }

        // Add order item
        function addOrderItem() {
            const container = document.getElementById('order-items-container');
            const itemIndex = container.children.length;
            
            const productOptions = products.map(product => 
                `<option value="${product.id}" data-price="${product.selling_price}" data-wholesale="${product.wholesale_price}" data-stock="${product.stock_quantity}">
                    ${product.name} (${product.sku}) - Stock: ${product.stock_quantity}
                </option>`
            ).join('');
            
            const itemHtml = `
                <div class="order-item border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="text-sm font-medium text-gray-900">Item ${itemIndex + 1}</h5>
                        <button type="button" onclick="removeOrderItem(this)" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                            <select class="item-product w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="updateItemPrice(this)">
                                <option value="">Select Product</option>
                                ${productOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                            <input type="number" class="item-quantity w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" min="1" required onchange="calculateOrderTotal()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price (৳) *</label>
                            <input type="number" class="item-price w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" step="0.01" min="0" required onchange="calculateOrderTotal()">
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', itemHtml);
        }

        // Remove order item
        function removeOrderItem(button) {
            button.closest('.order-item').remove();
            calculateOrderTotal();
            
            // Renumber items
            const items = document.querySelectorAll('.order-item');
            items.forEach((item, index) => {
                item.querySelector('h5').textContent = `Item ${index + 1}`;
            });
        }

        // Update item price based on customer type
        function updateItemPrice(select) {
            const option = select.selectedOptions[0];
            if (!option || !option.value) return;
            
            const customerSelect = document.getElementById('order-customer');
            const customerId = customerSelect.value;
            
            if (!customerId) {
                showMessage('Please select a customer first', 'warning');
                return;
            }
            
            const customer = customers.find(c => c.id == customerId);
            const priceInput = select.closest('.order-item').querySelector('.item-price');
            
            if (customer && customer.customer_type === 'wholesale') {
                priceInput.value = option.dataset.wholesale || option.dataset.price;
            } else {
                priceInput.value = option.dataset.price;
            }
            
            calculateOrderTotal();
        }

        // Calculate order total
        function calculateOrderTotal() {
            const items = document.querySelectorAll('.order-item');
            let subtotal = 0;
            
            items.forEach(item => {
                const quantity = parseFloat(item.querySelector('.item-quantity').value) || 0;
                const price = parseFloat(item.querySelector('.item-price').value) || 0;
                subtotal += quantity * price;
            });
            
            const discount = parseFloat(document.getElementById('order-discount').value) || 0;
            const taxRate = parseFloat(document.getElementById('order-tax-rate').value) || 0;
            
            const taxAmount = (subtotal - discount) * (taxRate / 100);
            const total = subtotal - discount + taxAmount;
            
            document.getElementById('order-total').textContent = `৳${total.toLocaleString()}`;
        }

        // Create order form submission
        document.getElementById('create-order-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const customerId = document.getElementById('order-customer').value;
            const dueDate = document.getElementById('order-due-date').value;
            const notes = document.getElementById('order-notes').value;
            const discount = parseFloat(document.getElementById('order-discount').value) || 0;
            const taxRate = parseFloat(document.getElementById('order-tax-rate').value) || 0;
            
            // Collect items
            const items = [];
            const itemElements = document.querySelectorAll('.order-item');
            
            for (let item of itemElements) {
                const productId = item.querySelector('.item-product').value;
                const quantity = parseInt(item.querySelector('.item-quantity').value);
                const unitPrice = parseFloat(item.querySelector('.item-price').value);
                
                if (!productId || !quantity || !unitPrice) {
                    showMessage('Please fill in all item details', 'error');
                    return;
                }
                
                items.push({
                    product_id: productId,
                    quantity: quantity,
                    unit_price: unitPrice
                });
            }
            
            if (items.length === 0) {
                showMessage('Please add at least one item', 'error');
                return;
            }
            
            try {
                const response = await fetch('../../api/orders_invoices.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'create_order',
                        customer_id: customerId,
                        due_date: dueDate,
                        notes: notes,
                        discount_amount: discount,
                        tax_rate: taxRate,
                        items: items
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Order created successfully!', 'success');
                    closeCreateOrderModal();
                    loadOrders();
                } else {
                    showMessage('Error creating order: ' + data.message, 'error');
                }
            } catch (error) {
                showMessage('Error creating order: ' + error.message, 'error');
            }
        });

        // View order details
        async function viewOrderDetails(orderId) {
            try {
                const response = await fetch(`../../api/orders_invoices.php?action=get_order&id=${orderId}`);
                const data = await response.json();
                
                if (data.success) {
                    currentOrder = data.order;
                    renderOrderDetails(data.order, data.items, data.invoices);
                    document.getElementById('order-details-modal').classList.add('active');
                } else {
                    showMessage('Error loading order details: ' + data.message, 'error');
                }
            } catch (error) {
                showMessage('Error loading order details: ' + error.message, 'error');
            }
        }

        // Render order details
        function renderOrderDetails(order, items, invoices) {
            const content = document.getElementById('order-details-content');
            
            const itemsHtml = items.map(item => `
                <tr>
                    <td class="px-4 py-2 border-b">${item.product_name}</td>
                    <td class="px-4 py-2 border-b">${item.sku}</td>
                    <td class="px-4 py-2 border-b text-center">${item.quantity}</td>
                    <td class="px-4 py-2 border-b text-center">${item.delivered_quantity}</td>
                    <td class="px-4 py-2 border-b text-center">${item.pending_quantity}</td>
                    <td class="px-4 py-2 border-b text-right">৳${parseFloat(item.unit_price).toLocaleString()}</td>
                    <td class="px-4 py-2 border-b text-right">৳${parseFloat(item.total_price).toLocaleString()}</td>
                </tr>
            `).join('');
            
            const invoicesHtml = invoices.map(invoice => `
                <tr>
                    <td class="px-4 py-2 border-b">${invoice.invoice_number}</td>
                    <td class="px-4 py-2 border-b">${formatDate(invoice.invoice_date)}</td>
                    <td class="px-4 py-2 border-b text-center">${invoice.total_quantity}</td>
                    <td class="px-4 py-2 border-b text-right">৳${parseFloat(invoice.total_amount).toLocaleString()}</td>
                    <td class="px-4 py-2 border-b">${getStatusBadge(invoice.status)}</td>
                    <td class="px-4 py-2 border-b">
                        <div class="flex space-x-2">
                            <button onclick="viewInvoice(${invoice.id})" class="text-blue-600 hover:text-blue-900" title="View Invoice">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${invoice.status !== 'delivered' ? `
                                <button onclick="markInvoiceDelivered(${invoice.id})" class="text-green-600 hover:text-green-900" title="Mark Delivered">
                                    <i class="fas fa-truck"></i>
                                </button>
                                <button onclick="deleteInvoice(${invoice.id})" class="text-red-600 hover:text-red-900" title="Delete Invoice">
                                    <i class="fas fa-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');
            
            content.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Order Information</h4>
                        <div class="space-y-2">
                            <div><strong>Order ID:</strong> #${order.id}</div>
                            <div><strong>Customer:</strong> ${order.customer_name}</div>
                            <div><strong>Email:</strong> ${order.customer_email || 'N/A'}</div>
                            <div><strong>Phone:</strong> ${order.customer_phone || 'N/A'}</div>
                            <div><strong>Order Date:</strong> ${formatDate(order.order_date)}</div>
                            <div><strong>Due Date:</strong> ${formatDate(order.due_date)}</div>
                            <div><strong>Status:</strong> ${getOrderStatusBadge(order.status)}</div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Order Summary</h4>
                        <div class="space-y-2">
                            <div><strong>Subtotal:</strong> ৳${parseFloat(order.subtotal).toLocaleString()}</div>
                            <div><strong>Discount:</strong> ৳${parseFloat(order.discount_amount).toLocaleString()}</div>
                            <div><strong>Tax (${order.tax_rate}%):</strong> ৳${parseFloat(order.tax_amount).toLocaleString()}</div>
                            <div class="text-lg"><strong>Total:</strong> ৳${parseFloat(order.total_amount).toLocaleString()}</div>
                        </div>
                        ${order.notes ? `<div class="mt-4"><strong>Notes:</strong><br>${order.notes}</div>` : ''}
                    </div>
                </div>
                
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-medium text-gray-900">Order Items</h4>
                        <button onclick="createInvoiceFromOrder(${order.id})" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            <i class="fas fa-file-invoice mr-2"></i>Create Invoice
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Ordered</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Delivered</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Pending</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml}
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Related Invoices</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Items</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${invoicesHtml || '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No invoices created yet</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        // Create invoice from order
        function createInvoiceFromOrder(orderId) {
            // Implementation for creating invoice from order
            // This would open the create invoice modal with order data pre-filled
            showMessage('Invoice creation feature will be implemented', 'info');
        }

        // Filter functions
        function applyFilters() {
            // Implementation for applying filters
            showMessage('Filter functionality will be implemented', 'info');
        }

        function clearFilters() {
            document.getElementById('filter-status').value = '';
            document.getElementById('filter-customer').value = '';
            document.getElementById('filter-date-from').value = '';
            document.getElementById('filter-date-to').value = '';
            loadOrders();
        }

        // Show message
        function showMessage(message, type = 'info') {
            const container = document.getElementById('message-container');
            const messageId = 'message-' + Date.now();
            
            const colors = {
                success: 'bg-green-100 border-green-400 text-green-700',
                error: 'bg-red-100 border-red-400 text-red-700',
                warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
                info: 'bg-blue-100 border-blue-400 text-blue-700'
            };
            
            const messageHtml = `
                <div id="${messageId}" class="border-l-4 p-4 mb-4 rounded-md ${colors[type]} fade-in">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm">${message}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button onclick="document.getElementById('${messageId}').remove()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', messageHtml);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                const element = document.getElementById(messageId);
                if (element) element.remove();
            }, 5000);
        }

        // Event listeners for real-time calculation
        document.getElementById('order-discount').addEventListener('input', calculateOrderTotal);
        document.getElementById('order-tax-rate').addEventListener('input', calculateOrderTotal);
    </script>
</body>
</html>

