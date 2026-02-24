<?php
session_start();
require_once '../../config/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$currentUser = $auth->getCurrentUser();

// Database connection for dropdowns
$database = new Database();
$db = $database->getConnection();

// Get categories for dropdown
$categoriesQuery = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name";
$categoriesStmt = $db->prepare($categoriesQuery);
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll();

// Get brands for dropdown
$brandsQuery = "SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name";
$brandsStmt = $db->prepare($brandsQuery);
$brandsStmt->execute();
$brands = $brandsStmt->fetchAll();

// Get suppliers for dropdown
$suppliersQuery = "SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name";
$suppliersStmt = $db->prepare($suppliersQuery);
$suppliersStmt->execute();
$suppliers = $suppliersStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Add Product</title>
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
                        <a href="../products/index.php" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2 text-sm font-medium">Products</a>
                        <a href="../inventory/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Inventory</a>
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

    <div class="max-w-6xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Add New Product
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Create a new product in your cosmetics inventory (Complete Schema)
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left -ml-1 mr-2 h-4 w-4"></i>
                    Back to Products
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

        <!-- Product Form -->
        <div class="bg-white shadow rounded-lg">
            <form id="productForm" class="space-y-8 p-6">
                <!-- Basic Information -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Product Name *</label>
                            <input type="text" id="productName" name="name" required 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., Lipstick Red Velvet">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">SKU *</label>
                            <input type="text" id="productSku" name="sku" required 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., LIP001">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category *</label>
                            <select id="productCategory" name="category_id" required 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Brand</label>
                            <select id="productBrand" name="brand_id" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Brand</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" id="newBrandName" name="new_brand_name" 
                                   class="mt-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Or enter new brand name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Supplier</label>
                            <select id="productSupplier" name="supplier_id" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Barcode</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="text" id="productBarcode" name="barcode" 
                                       class="flex-1 block w-full border-gray-300 rounded-l-md focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter or generate barcode">
                                <button type="button" onclick="generateBarcode()" 
                                        class="inline-flex items-center px-3 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 text-gray-500 text-sm">
                                    Generate
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="productDescription" name="description" rows="3" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Detailed product description..."></textarea>
                </div>

                <!-- Pricing -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Pricing</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cost Price (৳) *</label>
                            <input type="number" id="costPrice" name="cost_price" step="0.01" min="0" required 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Selling Price (৳) *</label>
                            <input type="number" id="sellingPrice" name="selling_price" step="0.01" min="0" required 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Wholesale Price (৳)</label>
                            <input type="number" id="wholesalePrice" name="wholesale_price" step="0.01" min="0" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="0.00">
                        </div>
                    </div>
                </div>

                <!-- Physical Properties -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Physical Properties</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Weight (grams)</label>
                            <input type="number" id="productWeight" name="weight" step="0.001" min="0" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="0.000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Dimensions</label>
                            <input type="text" id="productDimensions" name="dimensions" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., 10x5x2 cm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Product Image URL</label>
                            <input type="url" id="productImage" name="image" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="https://example.com/image.jpg">
                        </div>
                    </div>
                </div>

                <!-- Inventory Settings -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Inventory Settings</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Initial Stock *</label>
                            <input type="number" id="initialStock" name="initial_stock" min="0" required 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Minimum Stock Level *</label>
                            <input type="number" id="minStock" name="min_stock_level" min="0" required 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Maximum Stock Level</label>
                            <input type="number" id="maxStock" name="max_stock_level" min="0" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reorder Point *</label>
                            <input type="number" id="reorderPoint" name="reorder_point" min="0" required 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="0">
                        </div>
                    </div>
                </div>

                <!-- Product Variants (Schema Compliant) -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Product Variants</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Colors</label>
                            <input type="text" id="productColors" name="colors" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., Red, Pink, Nude (comma separated)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sizes</label>
                            <input type="text" id="productSizes" name="sizes" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., 30ml, 50ml, 100ml (comma separated)">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Scents</label>
                            <input type="text" id="productScents" name="scents" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., Vanilla, Rose, Lavender (comma separated)">
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Settings</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="productStatus" name="is_active" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Track Inventory</label>
                            <select id="trackInventory" name="track_inventory" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Expiry Tracking</label>
                            <select id="expiryTracking" name="expiry_tracking" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <a href="index.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function generateBarcode() {
            const barcode = '8' + Math.floor(Math.random() * 1000000000000).toString().padStart(12, '0');
            document.getElementById('productBarcode').value = barcode;
        }

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

        document.getElementById('productForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const productData = {};
            
            for (let [key, value] of formData.entries()) {
                if (['cost_price', 'selling_price', 'wholesale_price', 'weight'].includes(key)) {
                    productData[key] = parseFloat(value) || 0;
                } else if (['min_stock_level', 'max_stock_level', 'reorder_point', 'initial_stock', 'category_id', 'brand_id', 'supplier_id', 'is_active', 'track_inventory', 'expiry_tracking'].includes(key)) {
                    productData[key] = parseInt(value) || 0;
                } else {
                    productData[key] = value;
                }
            }

            // Handle new brand name
            if (productData.new_brand_name && !productData.brand_id) {
                productData.brand_id = productData.new_brand_name;
            }
            delete productData.new_brand_name;
            
            // Validation
            if (!productData.name || !productData.sku || !productData.category_id) {
                showMessage('error', 'Please fill in all required fields');
                return;
            }
            
            if (productData.cost_price <= 0 || productData.selling_price <= 0) {
                showMessage('error', 'Prices must be greater than 0');
                return;
            }
            
            if (productData.min_stock_level < 0 || productData.reorder_point < 0 || productData.initial_stock < 0) {
                showMessage('error', 'Stock quantities cannot be negative');
                return;
            }
            
            fetch('../../api/products/add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(productData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', data.message);
                    setTimeout(() => {
                        this.reset();
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    showMessage('error', data.error || 'Failed to save product');
                }
            })
            .catch(error => {
                showMessage('error', 'Error saving product: ' + error.message);
            });
        });

        // Auto-generate SKU from product name
        document.getElementById('productName').addEventListener('input', function() {
            const name = this.value;
            if (name && !document.getElementById('productSku').value) {
                const sku = name.substring(0, 3).toUpperCase() + Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                document.getElementById('productSku').value = sku;
            }
        });

        // Auto-calculate prices
        document.getElementById('costPrice').addEventListener('input', function() {
            const costPrice = parseFloat(this.value) || 0;
            if (costPrice > 0) {
                const wholesalePrice = (costPrice * 1.3).toFixed(2);
                if (!document.getElementById('wholesalePrice').value) {
                    document.getElementById('wholesalePrice').value = wholesalePrice;
                }
                
                const sellingPrice = (costPrice * 1.6).toFixed(2);
                if (!document.getElementById('sellingPrice').value) {
                    document.getElementById('sellingPrice').value = sellingPrice;
                }
            }
        });

        // Handle brand selection vs new brand
        document.getElementById('productBrand').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('newBrandName').value = '';
            }
        });

        document.getElementById('newBrandName').addEventListener('input', function() {
            if (this.value) {
                document.getElementById('productBrand').value = '';
            }
        });
    </script>
</body>
</html>

