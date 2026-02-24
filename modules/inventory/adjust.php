<?php
session_start();
require_once '../../config/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Stock Adjustment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../../index.php" class="text-xl font-bold text-blue-600">Thai Link BD</a>
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="../../index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Dashboard</a>
                        <a href="../products/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Products</a>
                        <a href="../inventory/index.php" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2 text-sm font-medium">Inventory</a>
                        <a href="../pos/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">POS</a>
                        <a href="../invoices/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Invoices</a>
                        <a href="../reports/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Reports</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                    <a href="../auth/login.php?logout=1" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Stock Adjustment
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Adjust inventory levels for products in your system
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left -ml-1 mr-2 h-4 w-4"></i>
                    Back to Inventory
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div id="messageContainer" class="mb-6 hidden">
            <div id="successMessage" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg hidden">
                <div class="flex">
                    <i class="fas fa-check-circle text-green-400 mr-3 mt-0.5"></i>
                    <span id="successText"></span>
                </div>
            </div>
            <div id="errorMessage" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg hidden">
                <div class="flex">
                    <i class="fas fa-exclamation-circle text-red-400 mr-3 mt-0.5"></i>
                    <span id="errorText"></span>
                </div>
            </div>
        </div>

        <!-- Stock Adjustment Form -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Stock Adjustment Details</h3>
            </div>
            
            <form id="adjustmentForm" class="space-y-6 p-6">
                <!-- Product Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Product *</label>
                    <div class="relative">
                        <select id="productSelect" name="product_id" required 
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Choose a product...</option>
                            <option value="1" data-current-stock="45" data-sku="LIP001">Lipstick Red Velvet (SKU: LIP001) - Current Stock: 45</option>
                            <option value="2" data-current-stock="3" data-sku="FND002">Foundation Beige (SKU: FND002) - Current Stock: 3</option>
                            <option value="3" data-current-stock="28" data-sku="MAS003">Mascara Black (SKU: MAS003) - Current Stock: 28</option>
                            <option value="4" data-current-stock="15" data-sku="EYE004">Eyeshadow Palette (SKU: EYE004) - Current Stock: 15</option>
                            <option value="5" data-current-stock="0" data-sku="BLU005">Blush Pink (SKU: BLU005) - Current Stock: 0</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Current Stock Display -->
                <div id="currentStockDisplay" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-3"></i>
                        <div>
                            <p class="text-sm font-medium text-blue-900">Current Stock Information</p>
                            <p class="text-sm text-blue-700">
                                <span class="font-medium">SKU:</span> <span id="displaySku">-</span> | 
                                <span class="font-medium">Current Stock:</span> <span id="displayCurrentStock">-</span> units
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Adjustment Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Adjustment Type *</label>
                        <select id="adjustmentType" name="adjustment_type" required 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select adjustment type...</option>
                            <option value="increase">Increase Stock (+)</option>
                            <option value="decrease">Decrease Stock (-)</option>
                            <option value="set">Set Stock Level (=)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Quantity *</label>
                        <input type="number" id="adjustmentQuantity" name="quantity" min="0" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter quantity">
                    </div>
                </div>

                <!-- New Stock Preview -->
                <div id="stockPreview" class="hidden bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Stock Level Preview</p>
                            <p class="text-sm text-gray-600">
                                Current: <span id="previewCurrent">-</span> → 
                                New: <span id="previewNew" class="font-medium">-</span>
                            </p>
                        </div>
                        <div id="previewIcon" class="text-2xl">
                            <!-- Icon will be updated based on adjustment -->
                        </div>
                    </div>
                </div>

                <!-- Reason -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Reason for Adjustment *</label>
                    <select id="adjustmentReason" name="reason" required 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select reason...</option>
                        <option value="stock_received">Stock Received</option>
                        <option value="stock_damaged">Stock Damaged</option>
                        <option value="stock_expired">Stock Expired</option>
                        <option value="stock_returned">Stock Returned</option>
                        <option value="stock_sold">Stock Sold (Manual)</option>
                        <option value="stock_transfer">Stock Transfer</option>
                        <option value="stock_count">Physical Stock Count</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <!-- Additional Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Additional Notes</label>
                    <textarea id="adjustmentNotes" name="notes" rows="3" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Optional: Add any additional details about this adjustment..."></textarea>
                </div>

                <!-- Reference Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reference Number</label>
                        <input type="text" id="referenceNumber" name="reference" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., PO-2024-001, INV-001">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Adjustment Date</label>
                        <input type="date" id="adjustmentDate" name="adjustment_date" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <a href="index.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Save Adjustment
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Adjustments -->
        <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Recent Stock Adjustments</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-08-28</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Lipstick Red Velvet</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-plus mr-1"></i>Increase
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">+20</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Stock Received</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Admin User</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-08-27</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Foundation Beige</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    <i class="fas fa-minus mr-1"></i>Decrease
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">-2</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Stock Damaged</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Manager User</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Set today's date as default
        document.getElementById('adjustmentDate').value = new Date().toISOString().split('T')[0];

        function showMessage(type, message) {
            const container = document.getElementById('messageContainer');
            const successDiv = document.getElementById('successMessage');
            const errorDiv = document.getElementById('errorMessage');
            
            successDiv.classList.add('hidden');
            errorDiv.classList.add('hidden');
            
            if (type === 'success') {
                document.getElementById('successText').textContent = message;
                successDiv.classList.remove('hidden');
            } else {
                document.getElementById('errorText').textContent = message;
                errorDiv.classList.remove('hidden');
            }
            
            container.classList.remove('hidden');
            
            setTimeout(() => {
                container.classList.add('hidden');
            }, 5000);
        }

        function updateStockPreview() {
            const productSelect = document.getElementById('productSelect');
            const adjustmentType = document.getElementById('adjustmentType').value;
            const quantity = parseInt(document.getElementById('adjustmentQuantity').value) || 0;
            
            if (!productSelect.value || !adjustmentType || quantity <= 0) {
                document.getElementById('stockPreview').classList.add('hidden');
                return;
            }
            
            const currentStock = parseInt(productSelect.selectedOptions[0].dataset.currentStock) || 0;
            let newStock = currentStock;
            let icon = '';
            let iconColor = '';
            
            switch (adjustmentType) {
                case 'increase':
                    newStock = currentStock + quantity;
                    icon = '<i class="fas fa-arrow-up text-green-500"></i>';
                    break;
                case 'decrease':
                    newStock = Math.max(0, currentStock - quantity);
                    icon = '<i class="fas fa-arrow-down text-red-500"></i>';
                    break;
                case 'set':
                    newStock = quantity;
                    icon = '<i class="fas fa-equals text-blue-500"></i>';
                    break;
            }
            
            document.getElementById('previewCurrent').textContent = currentStock;
            document.getElementById('previewNew').textContent = newStock;
            document.getElementById('previewIcon').innerHTML = icon;
            document.getElementById('stockPreview').classList.remove('hidden');
        }

        // Product selection handler
        document.getElementById('productSelect').addEventListener('change', function() {
            const selectedOption = this.selectedOptions[0];
            
            if (this.value) {
                const currentStock = selectedOption.dataset.currentStock;
                const sku = selectedOption.dataset.sku;
                
                document.getElementById('displaySku').textContent = sku;
                document.getElementById('displayCurrentStock').textContent = currentStock;
                document.getElementById('currentStockDisplay').classList.remove('hidden');
            } else {
                document.getElementById('currentStockDisplay').classList.add('hidden');
                document.getElementById('stockPreview').classList.add('hidden');
            }
            
            updateStockPreview();
        });

        // Update preview when adjustment details change
        document.getElementById('adjustmentType').addEventListener('change', updateStockPreview);
        document.getElementById('adjustmentQuantity').addEventListener('input', updateStockPreview);

        // Form submission
        document.getElementById('adjustmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const productSelect = document.getElementById('productSelect');
            const adjustmentType = document.getElementById('adjustmentType').value;
            const quantity = parseInt(document.getElementById('adjustmentQuantity').value) || 0;
            const reason = document.getElementById('adjustmentReason').value;
            
            // Validation
            if (!productSelect.value) {
                showMessage('error', 'Please select a product');
                return;
            }
            
            if (!adjustmentType) {
                showMessage('error', 'Please select an adjustment type');
                return;
            }
            
            if (quantity <= 0) {
                showMessage('error', 'Please enter a valid quantity');
                return;
            }
            
            if (!reason) {
                showMessage('error', 'Please select a reason for adjustment');
                return;
            }
            
            // Get product info
            const productName = productSelect.selectedOptions[0].text.split(' (SKU:')[0];
            const currentStock = parseInt(productSelect.selectedOptions[0].dataset.currentStock) || 0;
            
            // Calculate new stock
            let newStock = currentStock;
            let changeText = '';
            
            switch (adjustmentType) {
                case 'increase':
                    newStock = currentStock + quantity;
                    changeText = `+${quantity}`;
                    break;
                case 'decrease':
                    newStock = Math.max(0, currentStock - quantity);
                    changeText = `-${quantity}`;
                    break;
                case 'set':
                    newStock = quantity;
                    changeText = `set to ${quantity}`;
                    break;
            }
            
            // Simulate API call
            setTimeout(() => {
                showMessage('success', `Stock adjustment completed! ${productName} stock ${changeText} (${currentStock} → ${newStock})`);
                
                // Reset form
                setTimeout(() => {
                    this.reset();
                    document.getElementById('currentStockDisplay').classList.add('hidden');
                    document.getElementById('stockPreview').classList.add('hidden');
                    document.getElementById('adjustmentDate').value = new Date().toISOString().split('T')[0];
                }, 2000);
            }, 1000);
        });
    </script>
</body>
</html>

