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
    <title>Batch & Expiry Management - Thai Link BD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="../../index.php" class="text-xl font-bold">Thai Link BD</a>
                <span class="text-blue-200">Batch & Expiry Management</span>
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
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
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
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Expired</p>
                        <p class="text-2xl font-bold text-gray-900" id="expiredCount">0</p>
                        <p class="text-xs text-gray-500">Past expiry date</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-boxes text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Batches</p>
                        <p class="text-2xl font-bold text-gray-900" id="totalBatches">0</p>
                        <p class="text-xs text-gray-500">Active batches</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">FIFO Value</p>
                        <p class="text-2xl font-bold text-gray-900" id="fifoValue">৳0</p>
                        <p class="text-xs text-gray-500">Next out value</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Actions -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Status:</label>
                        <select id="statusFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all">All Batches</option>
                            <option value="expiring">Expiring Soon (30 days)</option>
                            <option value="expired">Expired</option>
                            <option value="active">Active</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product:</label>
                        <select id="productFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Products</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location:</label>
                        <select id="locationFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Locations</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <button onclick="exportBatchReport()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        <i class="fas fa-download mr-2"></i>Export Report
                    </button>
                    <button onclick="openBatchModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Add Batch
                    </button>
                </div>
            </div>
        </div>

        <!-- Expiring Soon Alert -->
        <div id="expiringAlert" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                <div>
                    <h3 class="text-red-800 font-semibold">Products Expiring Soon!</h3>
                    <p class="text-red-700 text-sm">The following products have batches expiring within 30 days. Consider applying discounts or promotions.</p>
                </div>
            </div>
            <div id="expiringList" class="mt-3"></div>
        </div>

        <!-- Batch Inventory Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-table mr-2 text-blue-600"></i>
                    Batch Inventory (FIFO Order)
                </h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Left</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="batchTableBody" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                <p>Loading batch data...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- FIFO Recommendation Panel -->
        <div class="mt-6 bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-lightbulb mr-2 text-yellow-600"></i>
                FIFO Recommendations
            </h3>
            <div id="fifoRecommendations" class="space-y-3">
                <p class="text-gray-500">Loading recommendations...</p>
            </div>
        </div>
    </div>

    <!-- Add/Edit Batch Modal -->
    <div id="batchModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Add New Batch</h3>
                </div>
                
                <form id="batchForm" class="p-6 space-y-4">
                    <input type="hidden" id="batchId" value="">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product:</label>
                        <select id="modalProduct" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Variant (Optional):</label>
                        <select id="modalVariant" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">No Variant</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Batch Number:</label>
                        <input type="text" id="modalBatchNumber" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity:</label>
                        <input type="number" id="modalQuantity" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Location:</label>
                        <input type="text" id="modalLocation" value="Main Store" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date:</label>
                        <input type="date" id="modalExpiryDate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </form>
                
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-2">
                    <button onclick="closeBatchModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                        Cancel
                    </button>
                    <button onclick="saveBatch()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Save Batch
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="messageContainer" class="fixed top-4 right-4 z-50"></div>

    <script>
        let batchData = [];
        let products = [];
        let variants = [];

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
            loadBatchData();
            setupEventListeners();
        });

        // Setup event listeners
        function setupEventListeners() {
            document.getElementById('statusFilter').addEventListener('change', filterBatches);
            document.getElementById('productFilter').addEventListener('change', filterBatches);
            document.getElementById('locationFilter').addEventListener('change', filterBatches);
            document.getElementById('modalProduct').addEventListener('change', loadProductVariants);
        }

        // Load products for filters and modal
        async function loadProducts() {
            try {
                const response = await fetch('../../api/batch_expiry.php?action=get_products');
                const data = await response.json();
                
                if (data.success) {
                    products = data.products;
                    
                    const productFilter = document.getElementById('productFilter');
                    const modalProduct = document.getElementById('modalProduct');
                    
                    products.forEach(product => {
                        const option1 = new Option(product.name, product.id);
                        const option2 = new Option(product.name, product.id);
                        productFilter.add(option1);
                        modalProduct.add(option2);
                    });
                }
            } catch (error) {
                showMessage('Error loading products: ' + error.message, 'error');
            }
        }

        // Load product variants
        async function loadProductVariants() {
            const productId = document.getElementById('modalProduct').value;
            const variantSelect = document.getElementById('modalVariant');
            
            // Clear existing variants
            variantSelect.innerHTML = '<option value="">No Variant</option>';
            
            if (!productId) return;
            
            try {
                const response = await fetch(`../../api/batch_expiry.php?action=get_variants&product_id=${productId}`);
                const data = await response.json();
                
                if (data.success && data.variants.length > 0) {
                    data.variants.forEach(variant => {
                        const option = new Option(variant.variant_name, variant.id);
                        variantSelect.add(option);
                    });
                }
            } catch (error) {
                console.error('Error loading variants:', error);
            }
        }

        // Load batch data
        async function loadBatchData() {
            try {
                const response = await fetch('../../api/batch_expiry.php?action=get_batches');
                const data = await response.json();
                
                if (data.success) {
                    batchData = data.batches;
                    updateSummaryCards(data.summary);
                    renderBatchTable();
                    updateFIFORecommendations(data.fifo_recommendations);
                    updateExpiringAlert(data.expiring_soon);
                    populateLocationFilter();
                }
            } catch (error) {
                showMessage('Error loading batch data: ' + error.message, 'error');
            }
        }

        // Update summary cards
        function updateSummaryCards(summary) {
            document.getElementById('expiringSoonCount').textContent = summary.expiring_soon || 0;
            document.getElementById('expiredCount').textContent = summary.expired || 0;
            document.getElementById('totalBatches').textContent = summary.total_batches || 0;
            document.getElementById('fifoValue').textContent = '৳' + (summary.fifo_value || 0).toLocaleString();
        }

        // Render batch table
        function renderBatchTable() {
            const tbody = document.getElementById('batchTableBody');
            
            if (batchData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-box-open text-4xl mb-2"></i>
                            <p>No batch data found. Add your first batch to get started.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = batchData.map(batch => {
                const daysLeft = batch.days_left;
                const statusClass = getStatusClass(daysLeft, batch.quantity);
                const statusText = getStatusText(daysLeft, batch.quantity);
                
                return `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${batch.product_name}</div>
                            ${batch.variant_name ? `<div class="text-sm text-gray-500">${batch.variant_name}</div>` : ''}
                            <div class="text-xs text-gray-400">SKU: ${batch.sku}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${batch.batch_number || 'N/A'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${batch.quantity}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${batch.location}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${batch.expiry_date ? new Date(batch.expiry_date).toLocaleDateString() : 'No Expiry'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            ${daysLeft !== null ? (daysLeft >= 0 ? daysLeft + ' days' : Math.abs(daysLeft) + ' days ago') : 'N/A'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full ${statusClass}">
                                ${statusText}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ৳${(batch.quantity * batch.cost_price).toLocaleString()}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="editBatch(${batch.id})" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="adjustBatch(${batch.id})" class="text-green-600 hover:text-green-900 mr-3">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                            ${daysLeft !== null && daysLeft <= 30 && daysLeft >= 0 ? 
                                `<button onclick="applyDiscount(${batch.id})" class="text-yellow-600 hover:text-yellow-900 mr-3" title="Apply Discount">
                                    <i class="fas fa-percentage"></i>
                                </button>` : ''
                            }
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Get status class for styling
        function getStatusClass(daysLeft, quantity) {
            if (quantity === 0) return 'bg-gray-100 text-gray-800';
            if (daysLeft === null) return 'bg-blue-100 text-blue-800';
            if (daysLeft < 0) return 'bg-red-100 text-red-800';
            if (daysLeft <= 7) return 'bg-red-100 text-red-800';
            if (daysLeft <= 30) return 'bg-yellow-100 text-yellow-800';
            return 'bg-green-100 text-green-800';
        }

        // Get status text
        function getStatusText(daysLeft, quantity) {
            if (quantity === 0) return 'Out of Stock';
            if (daysLeft === null) return 'No Expiry';
            if (daysLeft < 0) return 'Expired';
            if (daysLeft <= 7) return 'Critical';
            if (daysLeft <= 30) return 'Expiring Soon';
            return 'Good';
        }

        // Filter batches
        function filterBatches() {
            const statusFilter = document.getElementById('statusFilter').value;
            const productFilter = document.getElementById('productFilter').value;
            const locationFilter = document.getElementById('locationFilter').value;
            
            let filteredData = [...batchData];
            
            // Filter by status
            if (statusFilter !== 'all') {
                filteredData = filteredData.filter(batch => {
                    switch (statusFilter) {
                        case 'expiring':
                            return batch.days_left !== null && batch.days_left <= 30 && batch.days_left >= 0;
                        case 'expired':
                            return batch.days_left !== null && batch.days_left < 0;
                        case 'active':
                            return batch.quantity > 0 && (batch.days_left === null || batch.days_left > 30);
                        default:
                            return true;
                    }
                });
            }
            
            // Filter by product
            if (productFilter) {
                filteredData = filteredData.filter(batch => batch.product_id == productFilter);
            }
            
            // Filter by location
            if (locationFilter) {
                filteredData = filteredData.filter(batch => batch.location === locationFilter);
            }
            
            // Update table with filtered data
            const originalData = batchData;
            batchData = filteredData;
            renderBatchTable();
            batchData = originalData;
        }

        // Populate location filter
        function populateLocationFilter() {
            const locations = [...new Set(batchData.map(batch => batch.location))];
            const locationFilter = document.getElementById('locationFilter');
            
            locations.forEach(location => {
                const option = new Option(location, location);
                locationFilter.add(option);
            });
        }

        // Update FIFO recommendations
        function updateFIFORecommendations(recommendations) {
            const container = document.getElementById('fifoRecommendations');
            
            if (!recommendations || recommendations.length === 0) {
                container.innerHTML = '<p class="text-gray-500">No specific recommendations at this time.</p>';
                return;
            }
            
            container.innerHTML = recommendations.map(rec => `
                <div class="flex items-start p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <i class="fas fa-lightbulb text-yellow-600 mt-1 mr-3"></i>
                    <div>
                        <p class="text-sm font-medium text-yellow-800">${rec.title}</p>
                        <p class="text-sm text-yellow-700">${rec.message}</p>
                        ${rec.action ? `<button onclick="${rec.action}" class="mt-2 text-xs bg-yellow-600 text-white px-2 py-1 rounded hover:bg-yellow-700">${rec.action_text}</button>` : ''}
                    </div>
                </div>
            `).join('');
        }

        // Update expiring alert
        function updateExpiringAlert(expiringProducts) {
            const alert = document.getElementById('expiringAlert');
            const list = document.getElementById('expiringList');
            
            if (!expiringProducts || expiringProducts.length === 0) {
                alert.classList.add('hidden');
                return;
            }
            
            alert.classList.remove('hidden');
            list.innerHTML = expiringProducts.map(product => `
                <div class="flex items-center justify-between p-2 bg-white border border-red-200 rounded mt-2">
                    <div>
                        <span class="font-medium">${product.product_name}</span>
                        ${product.variant_name ? ` - ${product.variant_name}` : ''}
                        <span class="text-sm text-gray-600">(${product.quantity} units, expires in ${product.days_left} days)</span>
                    </div>
                    <button onclick="applyDiscount(${product.id})" class="text-sm bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                        Apply Discount
                    </button>
                </div>
            `).join('');
        }

        // Modal functions
        function openBatchModal(batchId = null) {
            document.getElementById('batchModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = batchId ? 'Edit Batch' : 'Add New Batch';
            document.getElementById('batchId').value = batchId || '';
            
            if (batchId) {
                // Load batch data for editing
                const batch = batchData.find(b => b.id == batchId);
                if (batch) {
                    document.getElementById('modalProduct').value = batch.product_id;
                    loadProductVariants().then(() => {
                        document.getElementById('modalVariant').value = batch.variant_id || '';
                    });
                    document.getElementById('modalBatchNumber').value = batch.batch_number || '';
                    document.getElementById('modalQuantity').value = batch.quantity;
                    document.getElementById('modalLocation').value = batch.location;
                    document.getElementById('modalExpiryDate').value = batch.expiry_date || '';
                }
            } else {
                document.getElementById('batchForm').reset();
                document.getElementById('modalLocation').value = 'Main Store';
            }
        }

        function closeBatchModal() {
            document.getElementById('batchModal').classList.add('hidden');
        }

        // Save batch
        async function saveBatch() {
            const formData = {
                id: document.getElementById('batchId').value,
                product_id: document.getElementById('modalProduct').value,
                variant_id: document.getElementById('modalVariant').value || null,
                batch_number: document.getElementById('modalBatchNumber').value,
                quantity: parseInt(document.getElementById('modalQuantity').value),
                location: document.getElementById('modalLocation').value,
                expiry_date: document.getElementById('modalExpiryDate').value || null
            };
            
            if (!formData.product_id || !formData.quantity || formData.quantity <= 0) {
                showMessage('Please fill in all required fields', 'error');
                return;
            }
            
            try {
                const response = await fetch('../../api/batch_expiry.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: formData.id ? 'update_batch' : 'create_batch',
                        ...formData
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(data.message, 'success');
                    closeBatchModal();
                    loadBatchData();
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('Error saving batch: ' + error.message, 'error');
            }
        }

        // Edit batch
        function editBatch(batchId) {
            openBatchModal(batchId);
        }

        // Adjust batch quantity
        async function adjustBatch(batchId) {
            const batch = batchData.find(b => b.id == batchId);
            if (!batch) return;
            
            const newQuantity = prompt(`Current quantity: ${batch.quantity}\nEnter new quantity:`, batch.quantity);
            if (newQuantity === null || newQuantity === '' || isNaN(newQuantity) || newQuantity < 0) {
                return;
            }
            
            try {
                const response = await fetch('../../api/batch_expiry.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'adjust_batch',
                        id: batchId,
                        quantity: parseInt(newQuantity)
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Batch quantity updated successfully', 'success');
                    loadBatchData();
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('Error adjusting batch: ' + error.message, 'error');
            }
        }

        // Apply discount to expiring batch
        async function applyDiscount(batchId) {
            const batch = batchData.find(b => b.id == batchId);
            if (!batch) return;
            
            const discount = prompt(`Apply discount to ${batch.product_name}\nCurrent price: ৳${batch.selling_price}\nEnter discount percentage (0-100):`, '20');
            if (discount === null || discount === '' || isNaN(discount) || discount < 0 || discount > 100) {
                return;
            }
            
            try {
                const response = await fetch('../../api/batch_expiry.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'apply_discount',
                        id: batchId,
                        discount_percentage: parseFloat(discount)
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(`Discount applied successfully. New price: ৳${data.new_price}`, 'success');
                    loadBatchData();
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('Error applying discount: ' + error.message, 'error');
            }
        }

        // Export batch report
        async function exportBatchReport() {
            try {
                const response = await fetch('../../api/batch_expiry.php?action=export_report');
                const data = await response.json();
                
                if (data.success) {
                    // Create and download CSV
                    const csv = data.csv_data;
                    const blob = new Blob([csv], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `batch_expiry_report_${new Date().toISOString().split('T')[0]}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    showMessage('Report exported successfully', 'success');
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('Error exporting report: ' + error.message, 'error');
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

