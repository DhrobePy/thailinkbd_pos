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
    <title>Product Management - Thai Link BD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="../../index.php" class="text-xl font-bold">Thai Link BD</a>
                <span class="text-blue-200">Product Management</span>
            </div>
            <div class="flex items-center space-x-4">
                <span>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></span>
                <a href="../../modules/auth/logout.php" class="bg-blue-700 px-3 py-1 rounded">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-shopping-bag text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Products</p>
                        <p class="text-2xl font-bold text-gray-900" id="totalProducts">0</p>
                        <p class="text-xs text-gray-500">Active products</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Low Stock</p>
                        <p class="text-2xl font-bold text-gray-900" id="lowStockCount">0</p>
                        <p class="text-xs text-gray-500">Below minimum</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Expiring Soon</p>
                        <p class="text-2xl font-bold text-gray-900" id="expiringSoonCount">0</p>
                        <p class="text-xs text-gray-500">Next 30 days</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-dollar-sign text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Value</p>
                        <p class="text-2xl font-bold text-gray-900" id="totalValue">৳0</p>
                        <p class="text-xs text-gray-500">Inventory value</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Actions -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search:</label>
                        <input type="text" id="searchInput" placeholder="Search products..." 
                               class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status:</label>
                        <select id="statusFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                            <option value="expiring">Expiring Soon</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <button onclick="exportProducts()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        <i class="fas fa-download mr-2"></i>Export
                    </button>
                    <button onclick="openProductModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Add Product
                    </button>
                </div>
            </div>
        </div>

        <!-- Alerts Section -->
        <div id="alertsSection" class="mb-6"></div>

        <!-- Products Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-table mr-2 text-blue-600"></i>
                    Products Inventory
                </h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                <p>Loading products...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Add New Product</h3>
                </div>
                
                <form id="productForm" class="p-6" enctype="multipart/form-data">
                    <input type="hidden" id="productId" value="">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic Information -->
                        <div class="space-y-4">
                            <h4 class="text-md font-semibold text-gray-800 border-b pb-2">Basic Information</h4>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                                <input type="text" id="productName" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">SKU *</label>
                                <input type="text" id="productSku" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="button" onclick="generateSku()" class="mt-1 text-sm text-blue-600 hover:text-blue-800">
                                    Generate SKU
                                </button>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea id="productDescription" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                                <select id="productCategory" required 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Category</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                                <select id="productBrand" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Brand</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                                <select id="productSupplier" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Supplier</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Pricing & Inventory -->
                        <div class="space-y-4">
                            <h4 class="text-md font-semibold text-gray-800 border-b pb-2">Pricing & Inventory</h4>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Cost Price *</label>
                                    <input type="number" id="costPrice" step="0.01" min="0" required 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Selling Price *</label>
                                    <input type="number" id="sellingPrice" step="0.01" min="0" required 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Wholesale Price</label>
                                    <input type="number" id="wholesalePrice" step="0.01" min="0" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Min Stock Level *</label>
                                    <input type="number" id="minStockLevel" min="0" required 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Weight</label>
                                    <input type="text" id="productWeight" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Dimensions</label>
                                    <input type="text" id="productDimensions" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <!-- Expiry Tracking -->
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" id="expiryTracking" class="mr-2">
                                    <span class="text-sm font-medium text-gray-700">Enable Expiry Tracking</span>
                                </label>
                            </div>
                            
                            <div id="expiryFields" class="hidden space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Shelf Life (days)</label>
                                    <input type="number" id="shelfLife" min="1" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Alert Days Before Expiry</label>
                                    <input type="number" id="alertDays" min="1" value="30" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Product Image -->
                    <div class="mt-6">
                        <h4 class="text-md font-semibold text-gray-800 border-b pb-2 mb-4">Product Image</h4>
                        
                        <div class="flex items-center space-x-4">
                            <div id="imagePreview" class="w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center">
                                <span class="text-gray-400">No Image</span>
                            </div>
                            
                            <div>
                                <input type="file" id="productImage" accept="image/*" class="hidden" onchange="previewImage(this)">
                                <button type="button" onclick="document.getElementById('productImage').click()" 
                                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                                    Choose Image
                                </button>
                                <button type="button" onclick="removeImage()" class="ml-2 text-red-600 hover:text-red-800">
                                    Remove
                                </button>
                                <p class="text-sm text-gray-500 mt-1">Max size: 2MB. Formats: JPG, PNG, GIF</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <div class="mt-6">
                        <label class="flex items-center">
                            <input type="checkbox" id="productActive" checked class="mr-2">
                            <span class="text-sm font-medium text-gray-700">Product is Active</span>
                        </label>
                    </div>
                </form>
                
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-2">
                    <button onclick="closeProductModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                        Cancel
                    </button>
                    <button onclick="saveProduct()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Save Product
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Batch Modal -->
    <div id="batchModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Manage Product Batches</h3>
                </div>
                
                <div class="p-6">
                    <div id="batchContent">
                        <p class="text-gray-500">Loading batch information...</p>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button onclick="closeBatchModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Confirm Delete</h3>
                </div>
                
                <div class="p-6">
                    <p class="text-gray-700">Are you sure you want to delete this product? This action cannot be undone.</p>
                    <div id="deleteProductInfo" class="mt-4 p-3 bg-gray-50 rounded"></div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-2">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                        Cancel
                    </button>
                    <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                        Delete Product
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="messageContainer" class="fixed top-4 right-4 z-50"></div>

    <script>
        let productsData = [];
        let categories = [];
        let brands = [];
        let suppliers = [];
        let deleteProductId = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadFilters();
            loadProducts();
            setupEventListeners();
        });

        // Setup event listeners
        function setupEventListeners() {
            document.getElementById('searchInput').addEventListener('input', filterProducts);
            document.getElementById('categoryFilter').addEventListener('change', filterProducts);
            document.getElementById('brandFilter').addEventListener('change', filterProducts);
            document.getElementById('statusFilter').addEventListener('change', filterProducts);
            document.getElementById('expiryTracking').addEventListener('change', toggleExpiryFields);
            document.getElementById('costPrice').addEventListener('input', calculatePrices);
        }

        // Load filter options
        async function loadFilters() {
            try {
                const response = await fetch('../../api/products.php?action=get_filters');
                const data = await response.json();
                
                if (data.success) {
                    categories = data.categories;
                    brands = data.brands;
                    suppliers = data.suppliers;
                    
                    populateSelect('categoryFilter', categories);
                    populateSelect('brandFilter', brands);
                    populateSelect('productCategory', categories);
                    populateSelect('productBrand', brands);
                    populateSelect('productSupplier', suppliers);
                }
            } catch (error) {
                showMessage('Error loading filters: ' + error.message, 'error');
            }
        }

        // Populate select options
        function populateSelect(selectId, options) {
            const select = document.getElementById(selectId);
            const isFilter = selectId.includes('Filter');
            
            if (!isFilter) {
                select.innerHTML = '<option value="">Select ' + selectId.replace('product', '').replace('Category', 'Category').replace('Brand', 'Brand').replace('Supplier', 'Supplier') + '</option>';
            }
            
            options.forEach(option => {
                const optionElement = new Option(option.name, option.id);
                select.add(optionElement);
            });
        }

        // Load products
        async function loadProducts() {
            try {
                const response = await fetch('../../api/products.php?action=get_products');
                const data = await response.json();
                
                if (data.success) {
                    productsData = data.products;
                    updateSummaryCards(data.summary);
                    renderProductsTable();
                    updateAlerts(data.alerts);
                }
            } catch (error) {
                showMessage('Error loading products: ' + error.message, 'error');
            }
        }

        // Update summary cards
        function updateSummaryCards(summary) {
            document.getElementById('totalProducts').textContent = summary.total_products || 0;
            document.getElementById('lowStockCount').textContent = summary.low_stock || 0;
            document.getElementById('expiringSoonCount').textContent = summary.expiring_soon || 0;
            document.getElementById('totalValue').textContent = '৳' + (summary.total_value || 0).toLocaleString();
        }

        // Update alerts
        function updateAlerts(alerts) {
            const alertsSection = document.getElementById('alertsSection');
            
            if (!alerts || alerts.length === 0) {
                alertsSection.innerHTML = '';
                return;
            }
            
            alertsSection.innerHTML = alerts.map(alert => `
                <div class="bg-${alert.type === 'warning' ? 'yellow' : 'red'}-50 border border-${alert.type === 'warning' ? 'yellow' : 'red'}-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-${alert.type === 'warning' ? 'exclamation-triangle' : 'times-circle'} text-${alert.type === 'warning' ? 'yellow' : 'red'}-600 mr-3"></i>
                        <div>
                            <h3 class="text-${alert.type === 'warning' ? 'yellow' : 'red'}-800 font-semibold">${alert.title}</h3>
                            <p class="text-${alert.type === 'warning' ? 'yellow' : 'red'}-700 text-sm">${alert.message}</p>
                        </div>
                    </div>
                    ${alert.products ? `
                        <div class="mt-3 space-y-1">
                            ${alert.products.map(product => `
                                <div class="text-sm text-${alert.type === 'warning' ? 'yellow' : 'red'}-700">
                                    • ${product.name} (${product.sku}) - ${product.detail}
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            `).join('');
        }

        // Render products table
        function renderProductsTable() {
            const tbody = document.getElementById('productsTableBody');
            
            if (productsData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-box-open text-4xl mb-2"></i>
                            <p>No products found. Add your first product to get started.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = productsData.map(product => {
                const stockStatus = getStockStatus(product);
                const expiryStatus = getExpiryStatus(product);
                
                return `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                ${product.image ? 
                                    `<img src="../../uploads/products/${product.image}" alt="${product.name}" class="w-10 h-10 rounded-lg object-cover mr-3">` :
                                    `<div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>`
                                }
                                <div>
                                    <div class="text-sm font-medium text-gray-900">${product.name}</div>
                                    ${product.description ? `<div class="text-sm text-gray-500">${product.description.substring(0, 50)}${product.description.length > 50 ? '...' : ''}</div>` : ''}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${product.sku}
                            ${product.barcode ? `<div class="text-xs text-gray-500">${product.barcode}</div>` : ''}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${product.category_name || 'N/A'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${product.brand_name || 'N/A'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="text-gray-900">${product.current_stock || 0}</div>
                            <div class="text-xs text-gray-500">Min: ${product.min_stock_level}</div>
                            ${stockStatus.alert ? `<div class="text-xs ${stockStatus.class}">${stockStatus.text}</div>` : ''}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div>৳${parseFloat(product.selling_price).toLocaleString()}</div>
                            ${product.wholesale_price ? `<div class="text-xs text-gray-500">Wholesale: ৳${parseFloat(product.wholesale_price).toLocaleString()}</div>` : ''}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="space-y-1">
                                <span class="px-2 py-1 text-xs rounded-full ${product.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${product.is_active ? 'Active' : 'Inactive'}
                                </span>
                                ${stockStatus.alert ? `<div><span class="px-2 py-1 text-xs rounded-full ${stockStatus.class}">${stockStatus.text}</span></div>` : ''}
                                ${expiryStatus.alert ? `<div><span class="px-2 py-1 text-xs rounded-full ${expiryStatus.class}">${expiryStatus.text}</span></div>` : ''}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="editProduct(${product.id})" class="text-blue-600 hover:text-blue-900" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="viewBatches(${product.id})" class="text-green-600 hover:text-green-900" title="Batches">
                                    <i class="fas fa-boxes"></i>
                                </button>
                                <button onclick="generateBarcode(${product.id})" class="text-purple-600 hover:text-purple-900" title="Barcode">
                                    <i class="fas fa-barcode"></i>
                                </button>
                                <button onclick="deleteProduct(${product.id})" class="text-red-600 hover:text-red-900" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Get stock status
        function getStockStatus(product) {
            const currentStock = product.current_stock || 0;
            const minLevel = product.min_stock_level || 0;
            
            if (currentStock === 0) {
                return { alert: true, class: 'text-red-600', text: 'Out of Stock' };
            } else if (currentStock <= minLevel) {
                return { alert: true, class: 'text-yellow-600', text: 'Low Stock' };
            }
            return { alert: false };
        }

        // Get expiry status
        function getExpiryStatus(product) {
            if (!product.expiry_tracking || !product.nearest_expiry) {
                return { alert: false };
            }
            
            const daysToExpiry = Math.ceil((new Date(product.nearest_expiry) - new Date()) / (1000 * 60 * 60 * 24));
            
            if (daysToExpiry < 0) {
                return { alert: true, class: 'bg-red-100 text-red-800', text: 'Expired' };
            } else if (daysToExpiry <= 30) {
                return { alert: true, class: 'bg-yellow-100 text-yellow-800', text: `Expires in ${daysToExpiry} days` };
            }
            return { alert: false };
        }

        // Filter products
        function filterProducts() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilter').value;
            const brandFilter = document.getElementById('brandFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            
            let filteredData = [...productsData];
            
            // Search filter
            if (search) {
                filteredData = filteredData.filter(product => 
                    product.name.toLowerCase().includes(search) ||
                    product.sku.toLowerCase().includes(search) ||
                    (product.description && product.description.toLowerCase().includes(search))
                );
            }
            
            // Category filter
            if (categoryFilter) {
                filteredData = filteredData.filter(product => product.category_id == categoryFilter);
            }
            
            // Brand filter
            if (brandFilter) {
                filteredData = filteredData.filter(product => product.brand_id == brandFilter);
            }
            
            // Status filter
            if (statusFilter) {
                filteredData = filteredData.filter(product => {
                    switch (statusFilter) {
                        case 'active':
                            return product.is_active;
                        case 'inactive':
                            return !product.is_active;
                        case 'low_stock':
                            return (product.current_stock || 0) <= (product.min_stock_level || 0) && (product.current_stock || 0) > 0;
                        case 'out_of_stock':
                            return (product.current_stock || 0) === 0;
                        case 'expiring':
                            if (!product.expiry_tracking || !product.nearest_expiry) return false;
                            const daysToExpiry = Math.ceil((new Date(product.nearest_expiry) - new Date()) / (1000 * 60 * 60 * 24));
                            return daysToExpiry <= 30 && daysToExpiry >= 0;
                        default:
                            return true;
                    }
                });
            }
            
            // Update table with filtered data
            const originalData = productsData;
            productsData = filteredData;
            renderProductsTable();
            productsData = originalData;
        }

        // Modal functions
        function openProductModal(productId = null) {
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = productId ? 'Edit Product' : 'Add New Product';
            document.getElementById('productId').value = productId || '';
            
            if (productId) {
                loadProductData(productId);
            } else {
                document.getElementById('productForm').reset();
                document.getElementById('productActive').checked = true;
                document.getElementById('imagePreview').innerHTML = '<span class="text-gray-400">No Image</span>';
                toggleExpiryFields();
            }
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.add('hidden');
        }

        // Load product data for editing
        async function loadProductData(productId) {
            try {
                const response = await fetch(`../../api/products.php?action=get_product&id=${productId}`);
                const data = await response.json();
                
                if (data.success) {
                    const product = data.product;
                    
                    document.getElementById('productName').value = product.name || '';
                    document.getElementById('productSku').value = product.sku || '';
                    document.getElementById('productDescription').value = product.description || '';
                    document.getElementById('productCategory').value = product.category_id || '';
                    document.getElementById('productBrand').value = product.brand_id || '';
                    document.getElementById('productSupplier').value = product.supplier_id || '';
                    document.getElementById('costPrice').value = product.cost_price || '';
                    document.getElementById('sellingPrice').value = product.selling_price || '';
                    document.getElementById('wholesalePrice').value = product.wholesale_price || '';
                    document.getElementById('minStockLevel').value = product.min_stock_level || '';
                    document.getElementById('productWeight').value = product.weight || '';
                    document.getElementById('productDimensions').value = product.dimensions || '';
                    document.getElementById('expiryTracking').checked = product.expiry_tracking || false;
                    document.getElementById('shelfLife').value = product.shelf_life || '';
                    document.getElementById('alertDays').value = product.alert_days || 30;
                    document.getElementById('productActive').checked = product.is_active || false;
                    
                    // Handle image
                    if (product.image) {
                        document.getElementById('imagePreview').innerHTML = 
                            `<img src="../../uploads/products/${product.image}" alt="Product Image" class="w-32 h-32 object-cover rounded-lg">`;
                    } else {
                        document.getElementById('imagePreview').innerHTML = '<span class="text-gray-400">No Image</span>';
                    }
                    
                    toggleExpiryFields();
                }
            } catch (error) {
                showMessage('Error loading product data: ' + error.message, 'error');
            }
        }

        // Save product
        async function saveProduct() {
            const formData = new FormData();
            const productId = document.getElementById('productId').value;
            
            // Basic validation
            const requiredFields = ['productName', 'productSku', 'productCategory', 'costPrice', 'sellingPrice', 'minStockLevel'];
            for (const field of requiredFields) {
                if (!document.getElementById(field).value.trim()) {
                    showMessage(`Please fill in the ${field.replace('product', '').replace(/([A-Z])/g, ' $1').toLowerCase()} field`, 'error');
                    return;
                }
            }
            
            // Append form data
            formData.append('action', productId ? 'update_product' : 'create_product');
            if (productId) formData.append('id', productId);
            formData.append('name', document.getElementById('productName').value);
            formData.append('sku', document.getElementById('productSku').value);
            formData.append('description', document.getElementById('productDescription').value);
            formData.append('category_id', document.getElementById('productCategory').value);
            formData.append('brand_id', document.getElementById('productBrand').value || null);
            formData.append('supplier_id', document.getElementById('productSupplier').value || null);
            formData.append('cost_price', document.getElementById('costPrice').value);
            formData.append('selling_price', document.getElementById('sellingPrice').value);
            formData.append('wholesale_price', document.getElementById('wholesalePrice').value || null);
            formData.append('min_stock_level', document.getElementById('minStockLevel').value);
            formData.append('weight', document.getElementById('productWeight').value || null);
            formData.append('dimensions', document.getElementById('productDimensions').value || null);
            formData.append('expiry_tracking', document.getElementById('expiryTracking').checked ? 1 : 0);
            formData.append('shelf_life', document.getElementById('shelfLife').value || null);
            formData.append('alert_days', document.getElementById('alertDays').value || 30);
            formData.append('is_active', document.getElementById('productActive').checked ? 1 : 0);
            
            // Handle image upload
            const imageFile = document.getElementById('productImage').files[0];
            if (imageFile) {
                formData.append('image', imageFile);
            }
            
            try {
                const response = await fetch('../../api/products.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(data.message, 'success');
                    closeProductModal();
                    loadProducts();
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('Error saving product: ' + error.message, 'error');
            }
        }

        // Edit product
        function editProduct(productId) {
            openProductModal(productId);
        }

        // Delete product
        function deleteProduct(productId) {
            const product = productsData.find(p => p.id == productId);
            if (!product) return;
            
            deleteProductId = productId;
            document.getElementById('deleteProductInfo').innerHTML = `
                <div class="font-medium">${product.name}</div>
                <div class="text-sm text-gray-600">SKU: ${product.sku}</div>
                <div class="text-sm text-gray-600">Current Stock: ${product.current_stock || 0}</div>
            `;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            deleteProductId = null;
        }

        async function confirmDelete() {
            if (!deleteProductId) return;
            
            try {
                const response = await fetch('../../api/products.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete_product',
                        id: deleteProductId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Product deleted successfully', 'success');
                    closeDeleteModal();
                    loadProducts();
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('Error deleting product: ' + error.message, 'error');
            }
        }

        // View batches
        async function viewBatches(productId) {
            const product = productsData.find(p => p.id == productId);
            if (!product) return;
            
            try {
                const response = await fetch(`../../api/products.php?action=get_batches&product_id=${productId}`);
                const data = await response.json();
                
                if (data.success) {
                    const batchContent = document.getElementById('batchContent');
                    
                    if (data.batches.length === 0) {
                        batchContent.innerHTML = `
                            <div class="text-center py-8">
                                <i class="fas fa-boxes text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">No batches found for this product.</p>
                                <button onclick="addBatch(${productId})" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                    Add First Batch
                                </button>
                            </div>
                        `;
                    } else {
                        batchContent.innerHTML = `
                            <div class="mb-4 flex justify-between items-center">
                                <h4 class="font-semibold">Batches for ${product.name}</h4>
                                <button onclick="addBatch(${productId})" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                                    Add Batch
                                </button>
                            </div>
                            <div class="space-y-3">
                                ${data.batches.map(batch => {
                                    const expiryDate = batch.expiry_date ? new Date(batch.expiry_date) : null;
                                    const daysToExpiry = expiryDate ? Math.ceil((expiryDate - new Date()) / (1000 * 60 * 60 * 24)) : null;
                                    
                                    return `
                                        <div class="border rounded-lg p-4">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="font-medium">Batch: ${batch.batch_number || 'N/A'}</div>
                                                    <div class="text-sm text-gray-600">Quantity: ${batch.quantity}</div>
                                                    <div class="text-sm text-gray-600">Location: ${batch.location}</div>
                                                    ${expiryDate ? `
                                                        <div class="text-sm ${daysToExpiry < 0 ? 'text-red-600' : daysToExpiry <= 30 ? 'text-yellow-600' : 'text-gray-600'}">
                                                            Expires: ${expiryDate.toLocaleDateString()} 
                                                            ${daysToExpiry !== null ? `(${daysToExpiry >= 0 ? daysToExpiry + ' days' : Math.abs(daysToExpiry) + ' days ago'})` : ''}
                                                        </div>
                                                    ` : ''}
                                                </div>
                                                <div class="flex space-x-2">
                                                    <button onclick="editBatch(${batch.id})" class="text-blue-600 hover:text-blue-800 text-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button onclick="deleteBatch(${batch.id})" class="text-red-600 hover:text-red-800 text-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        `;
                    }
                    
                    document.getElementById('batchModal').classList.remove('hidden');
                }
            } catch (error) {
                showMessage('Error loading batches: ' + error.message, 'error');
            }
        }

        function closeBatchModal() {
            document.getElementById('batchModal').classList.add('hidden');
        }

        // Generate barcode
        async function generateBarcode(productId) {
            try {
                const response = await fetch('../../api/products.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'generate_barcode',
                        product_id: productId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Open barcode in new window for printing
                    const barcodeWindow = window.open('', '_blank');
                    barcodeWindow.document.write(`
                        <html>
                            <head>
                                <title>Product Barcode</title>
                                <style>
                                    body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
                                    .barcode { margin: 20px 0; }
                                    .product-info { margin-bottom: 20px; }
                                    @media print { 
                                        body { margin: 0; padding: 10px; }
                                        .no-print { display: none; }
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="product-info">
                                    <h3>${data.product.name}</h3>
                                    <p>SKU: ${data.product.sku}</p>
                                    <p>Price: ৳${parseFloat(data.product.selling_price).toLocaleString()}</p>
                                </div>
                                <div class="barcode">
                                    <img src="data:image/png;base64,${data.barcode_image}" alt="Barcode">
                                    <p>${data.barcode}</p>
                                </div>
                                <div class="no-print">
                                    <button onclick="window.print()">Print Barcode</button>
                                    <button onclick="window.close()">Close</button>
                                </div>
                            </body>
                        </html>
                    `);
                    barcodeWindow.document.close();
                    
                    showMessage('Barcode generated successfully', 'success');
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('Error generating barcode: ' + error.message, 'error');
            }
        }

        // Export products
        async function exportProducts() {
            try {
                const response = await fetch('../../api/products.php?action=export_products');
                const data = await response.json();
                
                if (data.success) {
                    // Create and download CSV
                    const csv = data.csv_data;
                    const blob = new Blob([csv], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `products_export_${new Date().toISOString().split('T')[0]}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    showMessage('Products exported successfully', 'success');
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('Error exporting products: ' + error.message, 'error');
            }
        }

        // Utility functions
        function generateSku() {
            const name = document.getElementById('productName').value;
            if (!name) {
                showMessage('Please enter product name first', 'warning');
                return;
            }
            
            const sku = name.toUpperCase()
                .replace(/[^A-Z0-9]/g, '')
                .substring(0, 6) + 
                Math.random().toString(36).substring(2, 5).toUpperCase();
            
            document.getElementById('productSku').value = sku;
        }

        function toggleExpiryFields() {
            const expiryTracking = document.getElementById('expiryTracking').checked;
            const expiryFields = document.getElementById('expiryFields');
            
            if (expiryTracking) {
                expiryFields.classList.remove('hidden');
            } else {
                expiryFields.classList.add('hidden');
            }
        }

        function calculatePrices() {
            const costPrice = parseFloat(document.getElementById('costPrice').value) || 0;
            if (costPrice > 0) {
                // Suggest selling price (cost + 60%)
                const suggestedSelling = costPrice * 1.6;
                if (!document.getElementById('sellingPrice').value) {
                    document.getElementById('sellingPrice').value = suggestedSelling.toFixed(2);
                }
                
                // Suggest wholesale price (cost + 30%)
                const suggestedWholesale = costPrice * 1.3;
                if (!document.getElementById('wholesalePrice').value) {
                    document.getElementById('wholesalePrice').value = suggestedWholesale.toFixed(2);
                }
            }
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    showMessage('Image size must be less than 2MB', 'error');
                    input.value = '';
                    return;
                }
                
                // Validate file type
                if (!file.type.match('image.*')) {
                    showMessage('Please select a valid image file', 'error');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').innerHTML = 
                        `<img src="${e.target.result}" alt="Preview" class="w-32 h-32 object-cover rounded-lg">`;
                };
                reader.readAsDataURL(file);
            }
        }

        function removeImage() {
            document.getElementById('productImage').value = '';
            document.getElementById('imagePreview').innerHTML = '<span class="text-gray-400">No Image</span>';
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

