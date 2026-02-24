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
    <title>Invoice Management - Thai Link BD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .modal { display: none; }
        .modal.active { display: flex; }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .slide-in { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .invoice-preview { max-height: 600px; overflow-y: auto; }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
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
                        <h1 class="text-xl font-bold text-gray-900">Thai Link BD</h1>
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="../../index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Dashboard</a>
                        <a href="../products/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Products</a>
                        <a href="../inventory/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Inventory</a>
                        <a href="../orders/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Orders</a>
                        <a href="#" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2 text-sm font-medium">Invoices</a>
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
                    Invoice Management
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Create, manage, and track invoices and deliveries
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <button onclick="openCreateInvoiceModal()" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus mr-2"></i>
                    Create Invoice
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-file-invoice text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Invoices</dt>
                                <dd class="text-lg font-medium text-gray-900" id="total-invoices">Loading...</dd>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
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
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-truck text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Delivered</dt>
                                <dd class="text-lg font-medium text-gray-900" id="delivered-invoices">Loading...</dd>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Overdue</dt>
                                <dd class="text-lg font-medium text-gray-900" id="overdue-invoices">Loading...</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-dollar-sign text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Value</dt>
                                <dd class="text-lg font-medium text-gray-900" id="total-value">Loading...</dd>
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
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="filter-status" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="sent">Sent</option>
                            <option value="delivered">Delivered</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="cancelled">Cancelled</option>
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" id="filter-search" placeholder="Invoice number, customer..." class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="mt-4 flex space-x-3">
                    <button onclick="applyFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                    <button onclick="clearFilters()" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        <i class="fas fa-times mr-2"></i>Clear
                    </button>
                    <button onclick="exportInvoices()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <i class="fas fa-download mr-2"></i>Export CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Invoices</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="invoices-table-body" class="bg-white divide-y divide-gray-200">
                        <!-- Invoices will be loaded here -->
                    </tbody>
                </table>
            </div>
            <div id="invoices-loading" class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                <p class="text-gray-500 mt-2">Loading invoices...</p>
            </div>
            <div id="invoices-empty" class="text-center py-8 hidden">
                <i class="fas fa-file-invoice text-gray-400 text-4xl"></i>
                <p class="text-gray-500 mt-2">No invoices found</p>
            </div>
        </div>
    </div>

    <!-- Create Invoice Modal -->
    <div id="create-invoice-modal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Create New Invoice</h3>
                    <button onclick="closeCreateInvoiceModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="create-invoice-form">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Order (Optional)</label>
                            <select id="invoice-order" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Create standalone invoice</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Customer *</label>
                            <select id="invoice-customer" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Customer</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Due Date</label>
                            <input type="date" id="invoice-due-date" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-md font-medium text-gray-900">Invoice Items</h4>
                            <button type="button" onclick="addInvoiceItem()" class="px-3 py-1 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm">
                                <i class="fas fa-plus mr-1"></i>Add Item
                            </button>
                        </div>
                        <div id="invoice-items-container">
                            <!-- Invoice items will be added here -->
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Discount Amount (৳)</label>
                            <input type="number" id="invoice-discount" step="0.01" min="0" value="0" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tax Rate (%)</label>
                            <input type="number" id="invoice-tax-rate" step="0.01" min="0" value="15" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Total Amount</label>
                            <div class="text-lg font-bold text-gray-900 py-2" id="invoice-total">৳0.00</div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea id="invoice-notes" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Invoice notes..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateInvoiceModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>Create Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Invoice Details Modal -->
    <div id="invoice-details-modal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Invoice Details</h3>
                    <div class="flex space-x-2">
                        <button onclick="printInvoice()" class="px-3 py-1 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm">
                            <i class="fas fa-print mr-1"></i>Print
                        </button>
                        <button onclick="downloadInvoicePDF()" class="px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm">
                            <i class="fas fa-file-pdf mr-1"></i>PDF
                        </button>
                        <button onclick="emailInvoice()" class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                            <i class="fas fa-envelope mr-1"></i>Email
                        </button>
                        <button onclick="closeInvoiceDetailsModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div id="invoice-details-content" class="invoice-preview">
                    <!-- Invoice details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Confirmation Modal -->
    <div id="delivery-modal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Mark as Delivered</h3>
                    <button onclick="closeDeliveryModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-sm text-gray-600">Are you sure you want to mark this invoice as delivered? This will:</p>
                    <ul class="mt-2 text-sm text-gray-600 list-disc list-inside">
                        <li>Update inventory quantities</li>
                        <li>Create sale records</li>
                        <li>Log inventory transactions</li>
                        <li>Cannot be undone</li>
                    </ul>
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeDeliveryModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button onclick="confirmDelivery()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        <i class="fas fa-truck mr-2"></i>Mark Delivered
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="message-container" class="fixed top-4 right-4 z-50"></div>

    <script>
        let invoices = [];
        let customers = [];
        let products = [];
        let orders = [];
        let currentInvoice = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadInvoices();
            loadCustomers();
            loadProducts();
            loadOrders();
            
            // Set default due date to 30 days from now
            const dueDate = new Date();
            dueDate.setDate(dueDate.getDate() + 30);
            document.getElementById('invoice-due-date').value = dueDate.toISOString().split('T')[0];
        });

        // Load invoices
        async function loadInvoices() {
            try {
                const response = await fetch('../../api/orders_invoices.php?action=get_invoices');
                const data = await response.json();
                
                if (data.success) {
                    invoices = data.invoices;
                    updateSummaryCards(data.summary);
                    renderInvoicesTable();
                } else {
                    showMessage('Error loading invoices: ' + data.message, 'error');
                }
            } catch (error) {
                showMessage('Error loading invoices: ' + error.message, 'error');
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

        // Load orders
        async function loadOrders() {
            try {
                const response = await fetch('../../api/orders_invoices.php?action=get_orders');
                const data = await response.json();
                
                if (data.success) {
                    orders = data.orders.filter(order => order.delivery_status !== 'completed');
                    populateOrderDropdown();
                }
            } catch (error) {
                console.error('Error loading orders:', error);
            }
        }

        // Update summary cards
        function updateSummaryCards(summary) {
            document.getElementById('total-invoices').textContent = summary.total_invoices || 0;
            document.getElementById('pending-invoices').textContent = summary.pending_invoices || 0;
            document.getElementById('delivered-invoices').textContent = summary.delivered_invoices || 0;
            document.getElementById('overdue-invoices').textContent = summary.overdue_invoices || 0;
            document.getElementById('total-value').textContent = '৳' + (summary.total_value || 0).toLocaleString();
        }

        // Render invoices table
        function renderInvoicesTable() {
            const tbody = document.getElementById('invoices-table-body');
            const loading = document.getElementById('invoices-loading');
            const empty = document.getElementById('invoices-empty');
            
            loading.style.display = 'none';
            
            if (invoices.length === 0) {
                tbody.innerHTML = '';
                empty.classList.remove('hidden');
                return;
            }
            
            empty.classList.add('hidden');
            
            tbody.innerHTML = invoices.map(invoice => {
                const statusBadge = getInvoiceStatusBadge(invoice.status);
                const isOverdue = new Date(invoice.due_date) < new Date() && invoice.status !== 'delivered' && invoice.status !== 'paid';
                
                return `
                    <tr class="hover:bg-gray-50 ${isOverdue ? 'bg-red-50' : ''}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ${invoice.invoice_number}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${invoice.order_id ? '#' + invoice.order_id : 'Standalone'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${invoice.customer_name || 'N/A'}</div>
                            <div class="text-sm text-gray-500">${invoice.customer_email || ''}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${formatDate(invoice.invoice_date)}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 ${isOverdue ? 'text-red-600 font-medium' : ''}">
                            ${formatDate(invoice.due_date)}
                            ${isOverdue ? '<br><span class="text-xs text-red-500">OVERDUE</span>' : ''}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ৳${parseFloat(invoice.total_amount).toLocaleString()}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ${statusBadge}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="viewInvoiceDetails(${invoice.id})" class="text-blue-600 hover:text-blue-900" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="downloadInvoicePDF(${invoice.id})" class="text-red-600 hover:text-red-900" title="Download PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button onclick="emailInvoice(${invoice.id})" class="text-green-600 hover:text-green-900" title="Email Invoice">
                                    <i class="fas fa-envelope"></i>
                                </button>
                                ${invoice.status !== 'delivered' && invoice.status !== 'paid' ? `
                                    <button onclick="markAsDelivered(${invoice.id})" class="text-purple-600 hover:text-purple-900" title="Mark Delivered">
                                        <i class="fas fa-truck"></i>
                                    </button>
                                    <button onclick="editInvoice(${invoice.id})" class="text-yellow-600 hover:text-yellow-900" title="Edit Invoice">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteInvoice(${invoice.id})" class="text-red-600 hover:text-red-900" title="Delete Invoice">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Get invoice status badge
        function getInvoiceStatusBadge(status) {
            const badges = {
                'pending': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>',
                'sent': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Sent</span>',
                'delivered': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Delivered</span>',
                'paid': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Paid</span>',
                'overdue': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Overdue</span>',
                'cancelled': '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Cancelled</span>'
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

        // Populate dropdowns
        function populateCustomerDropdowns() {
            const invoiceCustomer = document.getElementById('invoice-customer');
            const filterCustomer = document.getElementById('filter-customer');
            
            const customerOptions = customers.map(customer => 
                `<option value="${customer.id}">${customer.name} (${customer.customer_type})</option>`
            ).join('');
            
            invoiceCustomer.innerHTML = '<option value="">Select Customer</option>' + customerOptions;
            filterCustomer.innerHTML = '<option value="">All Customers</option>' + customerOptions;
        }

        function populateOrderDropdown() {
            const orderSelect = document.getElementById('invoice-order');
            
            const orderOptions = orders.map(order => 
                `<option value="${order.id}">Order #${order.id} - ${order.customer_name} (${order.delivery_status})</option>`
            ).join('');
            
            orderSelect.innerHTML = '<option value="">Create standalone invoice</option>' + orderOptions;
        }

        // Modal functions
        function openCreateInvoiceModal() {
            document.getElementById('create-invoice-modal').classList.add('active');
            addInvoiceItem(); // Add first item
        }

        function closeCreateInvoiceModal() {
            document.getElementById('create-invoice-modal').classList.remove('active');
            document.getElementById('create-invoice-form').reset();
            document.getElementById('invoice-items-container').innerHTML = '';
            document.getElementById('invoice-total').textContent = '৳0.00';
        }

        function closeInvoiceDetailsModal() {
            document.getElementById('invoice-details-modal').classList.remove('active');
        }

        function closeDeliveryModal() {
            document.getElementById('delivery-modal').classList.remove('active');
            currentInvoice = null;
        }

        // Add invoice item
        function addInvoiceItem() {
            const container = document.getElementById('invoice-items-container');
            const itemIndex = container.children.length;
            
            const productOptions = products.map(product => 
                `<option value="${product.id}" data-price="${product.selling_price}" data-wholesale="${product.wholesale_price}" data-stock="${product.stock_quantity}">
                    ${product.name} (${product.sku}) - Stock: ${product.stock_quantity}
                </option>`
            ).join('');
            
            const itemHtml = `
                <div class="invoice-item border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h5 class="text-sm font-medium text-gray-900">Item ${itemIndex + 1}</h5>
                        <button type="button" onclick="removeInvoiceItem(this)" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                            <select class="item-product w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required onchange="updateInvoiceItemPrice(this)">
                                <option value="">Select Product</option>
                                ${productOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                            <input type="number" class="item-quantity w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" min="1" required onchange="calculateInvoiceTotal()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price (৳) *</label>
                            <input type="number" class="item-price w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" step="0.01" min="0" required onchange="calculateInvoiceTotal()">
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', itemHtml);
        }

        // Remove invoice item
        function removeInvoiceItem(button) {
            button.closest('.invoice-item').remove();
            calculateInvoiceTotal();
            
            // Renumber items
            const items = document.querySelectorAll('.invoice-item');
            items.forEach((item, index) => {
                item.querySelector('h5').textContent = `Item ${index + 1}`;
            });
        }

        // Update item price based on customer type
        function updateInvoiceItemPrice(select) {
            const option = select.selectedOptions[0];
            if (!option || !option.value) return;
            
            const customerSelect = document.getElementById('invoice-customer');
            const customerId = customerSelect.value;
            
            if (!customerId) {
                showMessage('Please select a customer first', 'warning');
                return;
            }
            
            const customer = customers.find(c => c.id == customerId);
            const priceInput = select.closest('.invoice-item').querySelector('.item-price');
            
            if (customer && customer.customer_type === 'wholesale') {
                priceInput.value = option.dataset.wholesale || option.dataset.price;
            } else {
                priceInput.value = option.dataset.price;
            }
            
            calculateInvoiceTotal();
        }

        // Calculate invoice total
        function calculateInvoiceTotal() {
            const items = document.querySelectorAll('.invoice-item');
            let subtotal = 0;
            
            items.forEach(item => {
                const quantity = parseFloat(item.querySelector('.item-quantity').value) || 0;
                const price = parseFloat(item.querySelector('.item-price').value) || 0;
                subtotal += quantity * price;
            });
            
            const discount = parseFloat(document.getElementById('invoice-discount').value) || 0;
            const taxRate = parseFloat(document.getElementById('invoice-tax-rate').value) || 0;
            
            const taxAmount = (subtotal - discount) * (taxRate / 100);
            const total = subtotal - discount + taxAmount;
            
            document.getElementById('invoice-total').textContent = `৳${total.toLocaleString()}`;
        }

        // Create invoice form submission
        document.getElementById('create-invoice-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const orderId = document.getElementById('invoice-order').value || null;
            const customerId = document.getElementById('invoice-customer').value;
            const dueDate = document.getElementById('invoice-due-date').value;
            const notes = document.getElementById('invoice-notes').value;
            const discount = parseFloat(document.getElementById('invoice-discount').value) || 0;
            const taxRate = parseFloat(document.getElementById('invoice-tax-rate').value) || 0;
            
            // Collect items
            const items = [];
            const itemElements = document.querySelectorAll('.invoice-item');
            
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
                        action: 'create_invoice',
                        order_id: orderId,
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
                    showMessage('Invoice created successfully!', 'success');
                    closeCreateInvoiceModal();
                    loadInvoices();
                } else {
                    showMessage('Error creating invoice: ' + data.message, 'error');
                }
            } catch (error) {
                showMessage('Error creating invoice: ' + error.message, 'error');
            }
        });

        // View invoice details
        async function viewInvoiceDetails(invoiceId) {
            try {
                const response = await fetch(`../../api/orders_invoices.php?action=get_invoice&id=${invoiceId}`);
                const data = await response.json();
                
                if (data.success) {
                    renderInvoiceDetails(data.invoice, data.items);
                    document.getElementById('invoice-details-modal').classList.add('active');
                } else {
                    showMessage('Error loading invoice details: ' + data.message, 'error');
                }
            } catch (error) {
                showMessage('Error loading invoice details: ' + error.message, 'error');
            }
        }

        // Render invoice details
        function renderInvoiceDetails(invoice, items) {
            const content = document.getElementById('invoice-details-content');
            
            const itemsHtml = items.map(item => `
                <tr>
                    <td class="px-4 py-2 border-b">${item.product_name}</td>
                    <td class="px-4 py-2 border-b text-center">${item.quantity}</td>
                    <td class="px-4 py-2 border-b text-right">৳${parseFloat(item.unit_price).toLocaleString()}</td>
                    <td class="px-4 py-2 border-b text-right">৳${parseFloat(item.total_price).toLocaleString()}</td>
                </tr>
            `).join('');
            
            content.innerHTML = `
                <div class="bg-white p-8 print-only">
                    <!-- Invoice Header -->
                    <div class="flex justify-between items-start mb-8">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Thai Link BD</h1>
                            <p class="text-gray-600">Cosmetics Wholesale & Retail</p>
                            <p class="text-gray-600">Dhaka, Bangladesh</p>
                        </div>
                        <div class="text-right">
                            <h2 class="text-2xl font-bold text-gray-900">INVOICE</h2>
                            <p class="text-gray-600">Invoice #: ${invoice.invoice_number}</p>
                            <p class="text-gray-600">Date: ${formatDate(invoice.invoice_date)}</p>
                            <p class="text-gray-600">Due Date: ${formatDate(invoice.due_date)}</p>
                        </div>
                    </div>
                    
                    <!-- Customer Info -->
                    <div class="grid grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Bill To:</h3>
                            <p class="font-medium">${invoice.customer_name}</p>
                            <p class="text-gray-600">${invoice.customer_email || ''}</p>
                            <p class="text-gray-600">${invoice.customer_phone || ''}</p>
                            <p class="text-gray-600">${invoice.customer_address || ''}</p>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Invoice Details:</h3>
                            <p><strong>Status:</strong> ${getInvoiceStatusBadge(invoice.status)}</p>
                            ${invoice.order_id ? `<p><strong>Order #:</strong> ${invoice.order_id}</p>` : ''}
                            <p><strong>Customer Type:</strong> ${invoice.customer_type}</p>
                        </div>
                    </div>
                    
                    <!-- Items Table -->
                    <div class="mb-8">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-900">Product</th>
                                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-900">Quantity</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-900">Unit Price</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-900">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml}
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Totals -->
                    <div class="flex justify-end">
                        <div class="w-64">
                            <div class="flex justify-between py-2">
                                <span>Subtotal:</span>
                                <span>৳${parseFloat(invoice.subtotal).toLocaleString()}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span>Discount:</span>
                                <span>৳${parseFloat(invoice.discount_amount).toLocaleString()}</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span>Tax (${invoice.tax_rate}%):</span>
                                <span>৳${parseFloat(invoice.tax_amount).toLocaleString()}</span>
                            </div>
                            <div class="flex justify-between py-2 border-t border-gray-300 font-bold text-lg">
                                <span>Total:</span>
                                <span>৳${parseFloat(invoice.total_amount).toLocaleString()}</span>
                            </div>
                        </div>
                    </div>
                    
                    ${invoice.notes ? `
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Notes:</h3>
                            <p class="text-gray-600">${invoice.notes}</p>
                        </div>
                    ` : ''}
                    
                    <!-- Footer -->
                    <div class="mt-12 pt-8 border-t border-gray-300 text-center text-gray-600">
                        <p>Thank you for your business!</p>
                        <p class="text-sm">For any questions, please contact us at info@thailinkbd.com</p>
                    </div>
                </div>
            `;
        }

        // Mark as delivered
        function markAsDelivered(invoiceId) {
            currentInvoice = invoiceId;
            document.getElementById('delivery-modal').classList.add('active');
        }

        async function confirmDelivery() {
            if (!currentInvoice) return;
            
            try {
                const response = await fetch('../../api/orders_invoices.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'mark_delivered',
                        invoice_id: currentInvoice
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Invoice marked as delivered successfully!', 'success');
                    closeDeliveryModal();
                    loadInvoices();
                } else {
                    showMessage('Error marking invoice as delivered: ' + data.message, 'error');
                }
            } catch (error) {
                showMessage('Error marking invoice as delivered: ' + error.message, 'error');
            }
        }

        // Delete invoice
        async function deleteInvoice(invoiceId) {
            if (!confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
                return;
            }
            
            try {
                const response = await fetch('../../api/orders_invoices.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete_invoice',
                        invoice_id: invoiceId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Invoice deleted successfully!', 'success');
                    loadInvoices();
                } else {
                    showMessage('Error deleting invoice: ' + data.message, 'error');
                }
            } catch (error) {
                showMessage('Error deleting invoice: ' + error.message, 'error');
            }
        }

        // Print invoice
        function printInvoice() {
            window.print();
        }

        // Download PDF
        function downloadInvoicePDF(invoiceId) {
            showMessage('PDF download feature will be implemented', 'info');
        }

        // Email invoice
        function emailInvoice(invoiceId) {
            showMessage('Email feature will be implemented', 'info');
        }

        // Filter functions
        function applyFilters() {
            showMessage('Filter functionality will be implemented', 'info');
        }

        function clearFilters() {
            document.getElementById('filter-status').value = '';
            document.getElementById('filter-customer').value = '';
            document.getElementById('filter-date-from').value = '';
            document.getElementById('filter-date-to').value = '';
            document.getElementById('filter-search').value = '';
            loadInvoices();
        }

        // Export invoices
        function exportInvoices() {
            showMessage('Export functionality will be implemented', 'info');
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
        document.getElementById('invoice-discount').addEventListener('input', calculateInvoiceTotal);
        document.getElementById('invoice-tax-rate').addEventListener('input', calculateInvoiceTotal);
    </script>
</body>
</html>

