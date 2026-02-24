<?php
session_start();
require_once '../../config/config.php';

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
    <title>Reports & Analytics - Thai Link BD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="../../index.php" class="text-xl font-bold">Thai Link BD</a>
                <span class="text-blue-200">Reports & Analytics</span>
            </div>
            <div class="flex items-center space-x-4">
                <span>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></span>
                <a href="../../modules/auth/logout.php" class="bg-blue-700 px-3 py-1 rounded">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <!-- Report Categories -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 cursor-pointer hover:shadow-lg transition-shadow" onclick="showReport('sales')">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Sales Reports</h3>
                        <p class="text-sm text-gray-600">Revenue, orders, trends</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 cursor-pointer hover:shadow-lg transition-shadow" onclick="showReport('inventory')">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-boxes text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Inventory Reports</h3>
                        <p class="text-sm text-gray-600">Stock levels, valuation</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 cursor-pointer hover:shadow-lg transition-shadow" onclick="showReport('products')">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-shopping-bag text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Product Reports</h3>
                        <p class="text-sm text-gray-600">Performance, categories</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 cursor-pointer hover:shadow-lg transition-shadow" onclick="showReport('customers')">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Customer Reports</h3>
                        <p class="text-sm text-gray-600">Analysis, segments</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Range:</label>
                    <select id="dateRange" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="this_week">This Week</option>
                        <option value="last_week">Last Week</option>
                        <option value="this_month" selected>This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="this_year">This Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>

                <div id="customDateRange" class="hidden flex gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From:</label>
                        <input type="date" id="startDate" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To:</label>
                        <input type="date" id="endDate" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category:</label>
                    <select id="categoryFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand:</label>
                    <select id="brandFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Brands</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button onclick="applyFilters()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Sales Report Section -->
        <div id="salesReport" class="report-section">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-chart-line mr-2 text-green-600"></i>
                        Sales Analytics
                    </h2>
                    <div class="flex gap-2">
                        <button onclick="exportReport('sales', 'csv')" class="bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700 text-sm">
                            <i class="fas fa-file-csv mr-1"></i>Export CSV
                        </button>
                        <button onclick="exportReport('sales', 'pdf')" class="bg-red-600 text-white px-3 py-2 rounded hover:bg-red-700 text-sm">
                            <i class="fas fa-file-pdf mr-1"></i>Export PDF
                        </button>
                    </div>
                </div>

                <!-- Sales Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-r from-green-400 to-green-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm">Total Revenue</p>
                                <p class="text-2xl font-bold" id="totalRevenue">৳0</p>
                            </div>
                            <i class="fas fa-dollar-sign text-2xl text-green-200"></i>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-blue-400 to-blue-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm">Total Orders</p>
                                <p class="text-2xl font-bold" id="totalOrders">0</p>
                            </div>
                            <i class="fas fa-shopping-cart text-2xl text-blue-200"></i>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-purple-400 to-purple-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm">Average Order</p>
                                <p class="text-2xl font-bold" id="averageOrder">৳0</p>
                            </div>
                            <i class="fas fa-chart-bar text-2xl text-purple-200"></i>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-orange-400 to-orange-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-100 text-sm">Profit Margin</p>
                                <p class="text-2xl font-bold" id="profitMargin">0%</p>
                            </div>
                            <i class="fas fa-percentage text-2xl text-orange-200"></i>
                        </div>
                    </div>
                </div>

                <!-- Sales Chart -->
                <div class="mb-6">
                    <canvas id="salesChart" width="400" height="200"></canvas>
                </div>

                <!-- Sales Details Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit</th>
                            </tr>
                        </thead>
                        <tbody id="salesTableBody" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                    <p>Loading sales data...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Inventory Report Section -->
        <div id="inventoryReport" class="report-section hidden">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-boxes mr-2 text-blue-600"></i>
                        Inventory Analysis
                    </h2>
                    <div class="flex gap-2">
                        <button onclick="exportReport('inventory', 'csv')" class="bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700 text-sm">
                            <i class="fas fa-file-csv mr-1"></i>Export CSV
                        </button>
                        <button onclick="exportReport('inventory', 'pdf')" class="bg-red-600 text-white px-3 py-2 rounded hover:bg-red-700 text-sm">
                            <i class="fas fa-file-pdf mr-1"></i>Export PDF
                        </button>
                    </div>
                </div>

                <!-- Inventory Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-r from-blue-400 to-blue-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm">Total Value</p>
                                <p class="text-2xl font-bold" id="inventoryValue">৳0</p>
                            </div>
                            <i class="fas fa-warehouse text-2xl text-blue-200"></i>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-green-400 to-green-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm">Total Items</p>
                                <p class="text-2xl font-bold" id="totalItems">0</p>
                            </div>
                            <i class="fas fa-cubes text-2xl text-green-200"></i>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-yellow-100 text-sm">Low Stock</p>
                                <p class="text-2xl font-bold" id="lowStockItems">0</p>
                            </div>
                            <i class="fas fa-exclamation-triangle text-2xl text-yellow-200"></i>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-red-400 to-red-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-100 text-sm">Out of Stock</p>
                                <p class="text-2xl font-bold" id="outOfStockItems">0</p>
                            </div>
                            <i class="fas fa-times-circle text-2xl text-red-200"></i>
                        </div>
                    </div>
                </div>

                <!-- Inventory Details Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Min Level</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody id="inventoryTableBody" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                    <p>Loading inventory data...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Product Report Section -->
        <div id="productReport" class="report-section hidden">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-shopping-bag mr-2 text-purple-600"></i>
                        Product Performance
                    </h2>
                    <div class="flex gap-2">
                        <button onclick="exportReport('products', 'csv')" class="bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700 text-sm">
                            <i class="fas fa-file-csv mr-1"></i>Export CSV
                        </button>
                        <button onclick="exportReport('products', 'pdf')" class="bg-red-600 text-white px-3 py-2 rounded hover:bg-red-700 text-sm">
                            <i class="fas fa-file-pdf mr-1"></i>Export PDF
                        </button>
                    </div>
                </div>

                <!-- Product Performance Chart -->
                <div class="mb-6">
                    <canvas id="productChart" width="400" height="200"></canvas>
                </div>

                <!-- Product Details Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Units Sold</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Margin %</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                            </tr>
                        </thead>
                        <tbody id="productTableBody" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                    <p>Loading product data...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Customer Report Section -->
        <div id="customerReport" class="report-section hidden">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-users mr-2 text-orange-600"></i>
                        Customer Analysis
                    </h2>
                    <div class="flex gap-2">
                        <button onclick="exportReport('customers', 'csv')" class="bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700 text-sm">
                            <i class="fas fa-file-csv mr-1"></i>Export CSV
                        </button>
                        <button onclick="exportReport('customers', 'pdf')" class="bg-red-600 text-white px-3 py-2 rounded hover:bg-red-700 text-sm">
                            <i class="fas fa-file-pdf mr-1"></i>Export PDF
                        </button>
                    </div>
                </div>

                <!-- Customer Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gradient-to-r from-orange-400 to-orange-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-100 text-sm">Total Customers</p>
                                <p class="text-2xl font-bold" id="totalCustomers">0</p>
                            </div>
                            <i class="fas fa-users text-2xl text-orange-200"></i>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-green-400 to-green-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm">Avg. Order Value</p>
                                <p class="text-2xl font-bold" id="avgCustomerOrder">৳0</p>
                            </div>
                            <i class="fas fa-chart-line text-2xl text-green-200"></i>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-blue-400 to-blue-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm">Repeat Customers</p>
                                <p class="text-2xl font-bold" id="repeatCustomers">0</p>
                            </div>
                            <i class="fas fa-redo text-2xl text-blue-200"></i>
                        </div>
                    </div>
                </div>

                <!-- Customer Details Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Spent</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody id="customerTableBody" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                    <p>Loading customer data...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 flex items-center">
            <i class="fas fa-spinner fa-spin text-2xl text-blue-600 mr-3"></i>
            <span class="text-lg">Generating report...</span>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="messageContainer" class="fixed top-4 right-4 z-50"></div>

    <script>
        let currentReport = 'sales';
        let reportData = {};
        let charts = {};

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadFilters();
            showReport('sales');
            setupEventListeners();
        });

        // Setup event listeners
        function setupEventListeners() {
            document.getElementById('dateRange').addEventListener('change', function() {
                const customRange = document.getElementById('customDateRange');
                if (this.value === 'custom') {
                    customRange.classList.remove('hidden');
                } else {
                    customRange.classList.add('hidden');
                }
            });
        }

        // Load filter options
        async function loadFilters() {
            try {
                const response = await fetch('../../api/reports.php?action=get_filters');
                const data = await response.json();
                
                if (data.success) {
                    // Populate category filter
                    const categoryFilter = document.getElementById('categoryFilter');
                    data.categories.forEach(category => {
                        const option = new Option(category.name, category.id);
                        categoryFilter.add(option);
                    });
                    
                    // Populate brand filter
                    const brandFilter = document.getElementById('brandFilter');
                    data.brands.forEach(brand => {
                        const option = new Option(brand.name, brand.id);
                        brandFilter.add(option);
                    });
                }
            } catch (error) {
                console.error('Error loading filters:', error);
            }
        }

        // Show specific report
        function showReport(reportType) {
            // Hide all report sections
            document.querySelectorAll('.report-section').forEach(section => {
                section.classList.add('hidden');
            });
            
            // Show selected report
            document.getElementById(reportType + 'Report').classList.remove('hidden');
            currentReport = reportType;
            
            // Load report data
            loadReportData(reportType);
        }

        // Apply filters and reload current report
        function applyFilters() {
            loadReportData(currentReport);
        }

        // Load report data
        async function loadReportData(reportType) {
            try {
                const filters = getFilters();
                const response = await fetch('../../api/reports.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'get_report',
                        report_type: reportType,
                        filters: filters
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    reportData[reportType] = data.data;
                    renderReport(reportType, data.data);
                } else {
                    showMessage(data.message || 'Error loading report', 'error');
                }
            } catch (error) {
                showMessage('Error loading report: ' + error.message, 'error');
            }
        }

        // Get current filters
        function getFilters() {
            const dateRange = document.getElementById('dateRange').value;
            const filters = {
                date_range: dateRange,
                category_id: document.getElementById('categoryFilter').value,
                brand_id: document.getElementById('brandFilter').value
            };
            
            if (dateRange === 'custom') {
                filters.start_date = document.getElementById('startDate').value;
                filters.end_date = document.getElementById('endDate').value;
            }
            
            return filters;
        }

        // Render report based on type
        function renderReport(reportType, data) {
            switch (reportType) {
                case 'sales':
                    renderSalesReport(data);
                    break;
                case 'inventory':
                    renderInventoryReport(data);
                    break;
                case 'products':
                    renderProductReport(data);
                    break;
                case 'customers':
                    renderCustomerReport(data);
                    break;
            }
        }

        // Render sales report
        function renderSalesReport(data) {
            // Update summary cards
            document.getElementById('totalRevenue').textContent = '৳' + (data.summary.total_revenue || 0).toLocaleString();
            document.getElementById('totalOrders').textContent = data.summary.total_orders || 0;
            document.getElementById('averageOrder').textContent = '৳' + (data.summary.average_order || 0).toLocaleString();
            document.getElementById('profitMargin').textContent = (data.summary.profit_margin || 0).toFixed(1) + '%';
            
            // Render sales chart
            renderSalesChart(data.chart_data);
            
            // Render sales table
            const tbody = document.getElementById('salesTableBody');
            if (data.sales.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            No sales data found for the selected period.
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = data.sales.map(sale => `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${new Date(sale.sale_date).toLocaleDateString()}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${sale.sale_number}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${sale.customer_name || 'Walk-in Customer'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${sale.total_items}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ৳${parseFloat(sale.total_amount).toLocaleString()}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="capitalize">${sale.payment_method}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ৳${parseFloat(sale.profit || 0).toLocaleString()}
                        </td>
                    </tr>
                `).join('');
            }
        }

        // Render inventory report
        function renderInventoryReport(data) {
            // Update summary cards
            document.getElementById('inventoryValue').textContent = '৳' + (data.summary.total_value || 0).toLocaleString();
            document.getElementById('totalItems').textContent = data.summary.total_items || 0;
            document.getElementById('lowStockItems').textContent = data.summary.low_stock || 0;
            document.getElementById('outOfStockItems').textContent = data.summary.out_of_stock || 0;
            
            // Render inventory table
            const tbody = document.getElementById('inventoryTableBody');
            if (data.inventory.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            No inventory data found.
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = data.inventory.map(item => {
                    const status = getStockStatus(item.current_stock, item.min_stock_level);
                    return `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">${item.product_name}</div>
                                ${item.variant_name ? `<div class="text-sm text-gray-500">${item.variant_name}</div>` : ''}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${item.sku}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${item.category_name || 'N/A'}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${item.brand_name || 'N/A'}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${item.current_stock || 0}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${item.min_stock_level || 0}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ৳${((item.current_stock || 0) * parseFloat(item.cost_price || 0)).toLocaleString()}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full ${status.class}">
                                    ${status.text}
                                </span>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        }

        // Render product report
        function renderProductReport(data) {
            // Render product chart
            renderProductChart(data.chart_data);
            
            // Render product table
            const tbody = document.getElementById('productTableBody');
            if (data.products.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            No product sales data found for the selected period.
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = data.products.map((product, index) => `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${product.product_name}</div>
                            <div class="text-sm text-gray-500">SKU: ${product.sku}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${product.category_name || 'N/A'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${product.brand_name || 'N/A'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${product.units_sold || 0}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ৳${parseFloat(product.revenue || 0).toLocaleString()}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ৳${parseFloat(product.profit || 0).toLocaleString()}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${parseFloat(product.margin_percentage || 0).toFixed(1)}%
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            #${index + 1}
                        </td>
                    </tr>
                `).join('');
            }
        }

        // Render customer report
        function renderCustomerReport(data) {
            // Update summary cards
            document.getElementById('totalCustomers').textContent = data.summary.total_customers || 0;
            document.getElementById('avgCustomerOrder').textContent = '৳' + (data.summary.avg_order_value || 0).toLocaleString();
            document.getElementById('repeatCustomers').textContent = data.summary.repeat_customers || 0;
            
            // Render customer table
            const tbody = document.getElementById('customerTableBody');
            if (data.customers.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            No customer data found.
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = data.customers.map(customer => `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${customer.name}</div>
                            ${customer.email ? `<div class="text-sm text-gray-500">${customer.email}</div>` : ''}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="capitalize">${customer.customer_type}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${customer.total_orders || 0}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ৳${parseFloat(customer.total_spent || 0).toLocaleString()}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ৳${parseFloat(customer.avg_order_value || 0).toLocaleString()}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${customer.last_order_date ? new Date(customer.last_order_date).toLocaleDateString() : 'Never'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full ${customer.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${customer.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                    </tr>
                `).join('');
            }
        }

        // Render sales chart
        function renderSalesChart(chartData) {
            const ctx = document.getElementById('salesChart').getContext('2d');
            
            if (charts.salesChart) {
                charts.salesChart.destroy();
            }
            
            charts.salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels || [],
                    datasets: [{
                        label: 'Revenue (৳)',
                        data: chartData.revenue || [],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1
                    }, {
                        label: 'Orders',
                        data: chartData.orders || [],
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }

        // Render product chart
        function renderProductChart(chartData) {
            const ctx = document.getElementById('productChart').getContext('2d');
            
            if (charts.productChart) {
                charts.productChart.destroy();
            }
            
            charts.productChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels || [],
                    datasets: [{
                        label: 'Units Sold',
                        data: chartData.units_sold || [],
                        backgroundColor: 'rgba(147, 51, 234, 0.8)',
                        borderColor: 'rgb(147, 51, 234)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Get stock status
        function getStockStatus(currentStock, minLevel) {
            if (currentStock === 0) {
                return { class: 'bg-red-100 text-red-800', text: 'Out of Stock' };
            } else if (currentStock <= minLevel) {
                return { class: 'bg-yellow-100 text-yellow-800', text: 'Low Stock' };
            } else {
                return { class: 'bg-green-100 text-green-800', text: 'In Stock' };
            }
        }

        // Export report
        async function exportReport(reportType, format) {
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.classList.remove('hidden');
            
            try {
                const filters = getFilters();
                const response = await fetch('../../api/reports.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'export_report',
                        report_type: reportType,
                        format: format,
                        filters: filters
                    })
                });
                
                if (format === 'csv') {
                    const data = await response.json();
                    if (data.success) {
                        // Download CSV
                        const blob = new Blob([data.csv_data], { type: 'text/csv' });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `${reportType}_report_${new Date().toISOString().split('T')[0]}.csv`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);
                        
                        showMessage('Report exported successfully', 'success');
                    } else {
                        showMessage(data.message || 'Export failed', 'error');
                    }
                } else if (format === 'pdf') {
                    // Handle PDF download
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `${reportType}_report_${new Date().toISOString().split('T')[0]}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    showMessage('PDF report downloaded successfully', 'success');
                }
                
            } catch (error) {
                showMessage('Error exporting report: ' + error.message, 'error');
            } finally {
                loadingOverlay.classList.add('hidden');
            }
        }

        // Show message
        function showMessage(message, type) {
            const container = document.getElementById('messageContainer');
            const messageDiv = document.createElement('div');
            
            const bgColor = {
                'success': 'bg-green-500',
                'error': 'bg-red-500',
                'warning': 'bg-yellow-500',
                'info': 'bg-blue-500'
            }[type] || 'bg-gray-500';
            
            messageDiv.className = `${bgColor} text-white px-4 py-2 rounded-md shadow-lg mb-2 max-w-sm`;
            messageDiv.textContent = message;
            
            container.appendChild(messageDiv);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>

