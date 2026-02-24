<?php
require_once '../../config/config.php';

$auth = new Auth();
$auth->requireAuth();

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Point of Sale</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#64748B',
                        success: '#10B981',
                        warning: '#F59E0B',
                        danger: '#EF4444',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="../../index.php" class="text-xl font-bold text-gray-900">Thai Link BD</a>
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="../../index.php" class="text-gray-500 hover:text-gray-700 px-1 pt-1 pb-4 text-sm font-medium">Dashboard</a>
                        <a href="../products/index.php" class="text-gray-500 hover:text-gray-700 px-1 pt-1 pb-4 text-sm font-medium">Products</a>
                        <a href="../inventory/index.php" class="text-gray-500 hover:text-gray-700 px-1 pt-1 pb-4 text-sm font-medium">Inventory</a>
                        <a href="index.php" class="text-primary border-b-2 border-primary px-1 pt-1 pb-4 text-sm font-medium">POS</a>
                        <a href="../invoices/index.php" class="text-gray-500 hover:text-gray-700 px-1 pt-1 pb-4 text-sm font-medium">Invoices</a>
                        <a href="../reports/index.php" class="text-gray-500 hover:text-gray-700 px-1 pt-1 pb-4 text-sm font-medium">Reports</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button onclick="toggleUserMenu()" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none">
                            <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white text-sm font-medium">
                                <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                            </div>
                            <span class="hidden md:block text-sm font-medium"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                            <a href="../settings/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                            <hr class="my-1">
                            <a href="#" onclick="logout()" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Product Search & Cart -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Product Search -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add Products</h3>
                    
                    <!-- Barcode Scanner -->
                    <div class="mb-4">
                        <label for="barcodeInput" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-barcode mr-2"></i>Scan Barcode or Search
                        </label>
                        <div class="flex space-x-2">
                            <div class="flex-1 relative">
                                <input type="text" id="barcodeInput" placeholder="Scan barcode or type product name/SKU..."
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                            <button onclick="startBarcodeScanner()" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Product Search Results -->
                    <div id="searchResults" class="hidden">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Search Results</h4>
                        <div id="searchResultsList" class="space-y-2 max-h-60 overflow-y-auto">
                            <!-- Search results will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Shopping Cart -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Shopping Cart</h3>
                        <button onclick="clearCart()" class="text-sm text-red-600 hover:text-red-700">
                            <i class="fas fa-trash mr-1"></i>Clear Cart
                        </button>
                    </div>
                    
                    <div id="cartItems" class="space-y-3">
                        <!-- Cart items will be populated here -->
                    </div>
                    
                    <div id="emptyCart" class="text-center py-8 text-gray-500">
                        <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                        <p>Your cart is empty</p>
                        <p class="text-sm">Scan or search for products to add them</p>
                    </div>
                </div>
            </div>

            <!-- Order Summary & Payment -->
            <div class="space-y-6">
                <!-- Customer Selection -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Customer</h3>
                    
                    <div class="space-y-3">
                        <div>
                            <label for="customerSearch" class="block text-sm font-medium text-gray-700 mb-2">Search Customer</label>
                            <input type="text" id="customerSearch" placeholder="Search by name, phone, or email..."
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                        </div>
                        
                        <div id="customerResults" class="hidden space-y-2 max-h-40 overflow-y-auto">
                            <!-- Customer search results -->
                        </div>
                        
                        <div class="flex space-x-2">
                            <button onclick="selectWalkInCustomer()" class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Walk-in Customer
                            </button>
                            <button onclick="showAddCustomerModal()" class="px-3 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-blue-700">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        
                        <div id="selectedCustomer" class="hidden p-3 bg-blue-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900" id="customerName"></p>
                                    <p class="text-xs text-gray-500" id="customerDetails"></p>
                                </div>
                                <button onclick="clearCustomer()" class="text-red-600 hover:text-red-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Order Summary</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal:</span>
                            <span id="subtotal" class="font-medium">৳ 0.00</span>
                        </div>
                        
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Discount:</span>
                            <div class="flex items-center space-x-2">
                                <input type="number" id="discountAmount" value="0" min="0" step="0.01"
                                       class="w-20 px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary"
                                       onchange="updateTotals()">
                                <span class="text-red-600 font-medium">৳ <span id="discount">0.00</span></span>
                            </div>
                        </div>
                        
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Tax (<?php echo DEFAULT_TAX_RATE; ?>%):</span>
                            <span id="tax" class="font-medium">৳ 0.00</span>
                        </div>
                        
                        <hr>
                        
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total:</span>
                            <span id="total" class="text-primary">৳ 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Payment -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Payment</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                            <select id="paymentMethod" class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile">Mobile Payment</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="amountPaid" class="block text-sm font-medium text-gray-700 mb-2">Amount Paid</label>
                            <input type="number" id="amountPaid" value="0" min="0" step="0.01"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                   onchange="calculateChange()">
                        </div>
                        
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Change:</span>
                            <span id="change" class="font-medium text-green-600">৳ 0.00</span>
                        </div>
                        
                        <div>
                            <label for="saleNotes" class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                            <textarea id="saleNotes" rows="2" placeholder="Add notes for this sale..."
                                      class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"></textarea>
                        </div>
                        
                        <button onclick="processSale()" id="processSaleBtn" 
                                class="w-full bg-success text-white py-3 px-4 rounded-md font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-success disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-cash-register mr-2"></i>
                            Process Sale
                        </button>
                        
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="holdSale()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-pause mr-1"></i>Hold
                            </button>
                            <button onclick="printReceipt()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-print mr-1"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div id="addCustomerModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add New Customer</h3>
                    <button onclick="closeAddCustomerModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="addCustomerForm" class="space-y-4">
                    <div>
                        <label for="newCustomerName" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" id="newCustomerName" required
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div>
                        <label for="newCustomerPhone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" id="newCustomerPhone"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div>
                        <label for="newCustomerEmail" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="newCustomerEmail"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div>
                        <label for="newCustomerType" class="block text-sm font-medium text-gray-700 mb-1">Customer Type</label>
                        <select id="newCustomerType" class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                            <option value="retail">Retail</option>
                            <option value="wholesale">Wholesale</option>
                        </select>
                    </div>
                    
                    <div class="flex space-x-3 pt-4">
                        <button type="button" onclick="closeAddCustomerModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-blue-700">
                            Add Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let cart = [];
        let selectedCustomer = null;
        let searchTimeout = null;

        // Initialize POS
        document.addEventListener('DOMContentLoaded', function() {
            updateCartDisplay();
            updateTotals();
            
            // Setup event listeners
            document.getElementById('barcodeInput').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchProducts(this.value);
                }, 300);
            });
            
            document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchProducts(this.value);
                }
            });
            
            document.getElementById('customerSearch').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchCustomers(this.value);
                }, 300);
            });
            
            document.getElementById('addCustomerForm').addEventListener('submit', function(e) {
                e.preventDefault();
                addNewCustomer();
            });
            
            // Set default amount paid to total when total changes
            document.getElementById('amountPaid').addEventListener('focus', function() {
                if (this.value == 0) {
                    const total = parseFloat(document.getElementById('total').textContent.replace('৳ ', ''));
                    this.value = total.toFixed(2);
                    calculateChange();
                }
            });
        });

        function searchProducts(term) {
            if (term.length < 2) {
                hideSearchResults();
                return;
            }

            fetch(`../../api/pos.php?action=search_products&term=${encodeURIComponent(term)}`)
                .then(response => response.json())
                .then(data => {
                    displaySearchResults(data.products);
                })
                .catch(error => {
                    console.error('Error searching products:', error);
                });
        }

        function displaySearchResults(products) {
            const resultsDiv = document.getElementById('searchResults');
            const resultsList = document.getElementById('searchResultsList');
            
            if (products.length === 0) {
                hideSearchResults();
                return;
            }
            
            resultsList.innerHTML = products.map(product => `
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                     onclick="addProductToCart(${product.id}, ${product.has_variants ? 'true' : 'false'})">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center">
                                <i class="fas fa-box text-gray-500"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">${product.name}</p>
                                <p class="text-xs text-gray-500">SKU: ${product.sku} | Stock: ${product.available_stock}</p>
                                <p class="text-xs text-gray-500">${product.category_name || ''} ${product.brand_name ? '• ' + product.brand_name : ''}</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900">৳ ${parseFloat(product.selling_price).toFixed(2)}</p>
                        ${product.wholesale_price ? `<p class="text-xs text-gray-500">Wholesale: ৳ ${parseFloat(product.wholesale_price).toFixed(2)}</p>` : ''}
                    </div>
                </div>
            `).join('');
            
            resultsDiv.classList.remove('hidden');
        }

        function hideSearchResults() {
            document.getElementById('searchResults').classList.add('hidden');
        }

        function addProductToCart(productId, hasVariants = false) {
            if (hasVariants) {
                // Show variant selection modal
                showVariantSelectionModal(productId);
                return;
            }
            
            fetch(`../../api/pos.php?action=get_product&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.product) {
                        addToCart(data.product);
                        document.getElementById('barcodeInput').value = '';
                        hideSearchResults();
                    }
                })
                .catch(error => {
                    console.error('Error getting product:', error);
                });
        }

        function addToCart(product, variant = null) {
            const existingItemIndex = cart.findIndex(item => 
                item.product_id === product.id && 
                item.variant_id === (variant ? variant.id : null)
            );
            
            if (existingItemIndex >= 0) {
                // Increase quantity
                cart[existingItemIndex].quantity += 1;
                cart[existingItemIndex].total_price = cart[existingItemIndex].quantity * cart[existingItemIndex].unit_price;
            } else {
                // Add new item
                const price = variant ? parseFloat(variant.selling_price) : parseFloat(product.selling_price);
                const cartItem = {
                    product_id: product.id,
                    variant_id: variant ? variant.id : null,
                    name: variant ? `${product.name} - ${variant.variant_name}` : product.name,
                    sku: variant ? variant.sku : product.sku,
                    unit_price: price,
                    quantity: 1,
                    discount_amount: 0,
                    total_price: price,
                    available_stock: product.available_stock
                };
                cart.push(cartItem);
            }
            
            updateCartDisplay();
            updateTotals();
        }

        function updateCartDisplay() {
            const cartItemsDiv = document.getElementById('cartItems');
            const emptyCartDiv = document.getElementById('emptyCart');
            
            if (cart.length === 0) {
                cartItemsDiv.innerHTML = '';
                emptyCartDiv.classList.remove('hidden');
                return;
            }
            
            emptyCartDiv.classList.add('hidden');
            
            cartItemsDiv.innerHTML = cart.map((item, index) => `
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">${item.name}</p>
                        <p class="text-xs text-gray-500">SKU: ${item.sku}</p>
                        <p class="text-xs text-gray-500">৳ ${item.unit_price.toFixed(2)} each</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="updateCartItemQuantity(${index}, ${item.quantity - 1})" 
                                class="w-6 h-6 bg-gray-200 rounded text-xs hover:bg-gray-300 ${item.quantity <= 1 ? 'opacity-50 cursor-not-allowed' : ''}">
                            <i class="fas fa-minus"></i>
                        </button>
                        <span class="w-8 text-center text-sm font-medium">${item.quantity}</span>
                        <button onclick="updateCartItemQuantity(${index}, ${item.quantity + 1})" 
                                class="w-6 h-6 bg-gray-200 rounded text-xs hover:bg-gray-300 ${item.quantity >= item.available_stock ? 'opacity-50 cursor-not-allowed' : ''}">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button onclick="removeFromCart(${index})" class="w-6 h-6 bg-red-200 text-red-600 rounded text-xs hover:bg-red-300">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-sm font-medium">৳ ${item.total_price.toFixed(2)}</span>
                </div>
            `).join('');
        }

        function updateCartItemQuantity(index, newQuantity) {
            if (newQuantity <= 0) {
                removeFromCart(index);
                return;
            }
            
            if (newQuantity > cart[index].available_stock) {
                alert('Insufficient stock available');
                return;
            }
            
            cart[index].quantity = newQuantity;
            cart[index].total_price = cart[index].quantity * cart[index].unit_price;
            
            updateCartDisplay();
            updateTotals();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
            updateTotals();
        }

        function clearCart() {
            if (cart.length > 0 && confirm('Are you sure you want to clear the cart?')) {
                cart = [];
                updateCartDisplay();
                updateTotals();
            }
        }

        function updateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + item.total_price, 0);
            const discountAmount = parseFloat(document.getElementById('discountAmount').value) || 0;
            const taxableAmount = subtotal - discountAmount;
            const taxAmount = calculateTax(taxableAmount, <?php echo DEFAULT_TAX_RATE; ?>);
            const total = taxableAmount + taxAmount;
            
            document.getElementById('subtotal').textContent = `৳ ${subtotal.toFixed(2)}`;
            document.getElementById('discount').textContent = discountAmount.toFixed(2);
            document.getElementById('tax').textContent = `৳ ${taxAmount.toFixed(2)}`;
            document.getElementById('total').textContent = `৳ ${total.toFixed(2)}`;
            
            // Update process sale button state
            const processSaleBtn = document.getElementById('processSaleBtn');
            processSaleBtn.disabled = cart.length === 0;
            
            calculateChange();
        }

        function calculateTax(amount, rate) {
            return (amount * rate) / 100;
        }

        function calculateChange() {
            const total = parseFloat(document.getElementById('total').textContent.replace('৳ ', ''));
            const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
            const change = Math.max(0, amountPaid - total);
            
            document.getElementById('change').textContent = `৳ ${change.toFixed(2)}`;
        }

        function searchCustomers(term) {
            if (term.length < 2) {
                hideCustomerResults();
                return;
            }

            fetch(`../../api/pos.php?action=customers&search=${encodeURIComponent(term)}`)
                .then(response => response.json())
                .then(data => {
                    displayCustomerResults(data.customers);
                })
                .catch(error => {
                    console.error('Error searching customers:', error);
                });
        }

        function displayCustomerResults(customers) {
            const resultsDiv = document.getElementById('customerResults');
            
            if (customers.length === 0) {
                hideCustomerResults();
                return;
            }
            
            resultsDiv.innerHTML = customers.map(customer => `
                <div class="p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50"
                     onclick="selectCustomer(${customer.id}, '${customer.name}', '${customer.phone || ''}', '${customer.customer_type}')">
                    <p class="text-sm font-medium">${customer.name}</p>
                    <p class="text-xs text-gray-500">${customer.phone || ''} ${customer.email || ''}</p>
                    <p class="text-xs text-gray-500">${customer.customer_type}</p>
                </div>
            `).join('');
            
            resultsDiv.classList.remove('hidden');
        }

        function hideCustomerResults() {
            document.getElementById('customerResults').classList.add('hidden');
        }

        function selectCustomer(id, name, phone, type) {
            selectedCustomer = { id, name, phone, type };
            
            document.getElementById('customerName').textContent = name;
            document.getElementById('customerDetails').textContent = `${phone} • ${type}`;
            document.getElementById('selectedCustomer').classList.remove('hidden');
            document.getElementById('customerSearch').value = '';
            
            hideCustomerResults();
        }

        function selectWalkInCustomer() {
            selectedCustomer = { id: 1, name: 'Walk-in Customer', phone: '', type: 'retail' };
            
            document.getElementById('customerName').textContent = 'Walk-in Customer';
            document.getElementById('customerDetails').textContent = 'Retail Customer';
            document.getElementById('selectedCustomer').classList.remove('hidden');
            document.getElementById('customerSearch').value = '';
        }

        function clearCustomer() {
            selectedCustomer = null;
            document.getElementById('selectedCustomer').classList.add('hidden');
        }

        function showAddCustomerModal() {
            document.getElementById('addCustomerModal').classList.remove('hidden');
        }

        function closeAddCustomerModal() {
            document.getElementById('addCustomerModal').classList.add('hidden');
            document.getElementById('addCustomerForm').reset();
        }

        function addNewCustomer() {
            const formData = {
                action: 'add_customer',
                name: document.getElementById('newCustomerName').value,
                phone: document.getElementById('newCustomerPhone').value,
                email: document.getElementById('newCustomerEmail').value,
                customer_type: document.getElementById('newCustomerType').value
            };

            fetch('../../api/pos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    selectCustomer(data.customer_id, formData.name, formData.phone, formData.customer_type);
                    closeAddCustomerModal();
                    showSuccess('Customer added successfully');
                } else {
                    showError(data.error || 'Failed to add customer');
                }
            })
            .catch(error => {
                console.error('Error adding customer:', error);
                showError('Failed to add customer');
            });
        }

        function processSale() {
            if (cart.length === 0) {
                showError('Cart is empty');
                return;
            }
            
            const subtotal = cart.reduce((sum, item) => sum + item.total_price, 0);
            const discountAmount = parseFloat(document.getElementById('discountAmount').value) || 0;
            const taxableAmount = subtotal - discountAmount;
            const taxAmount = calculateTax(taxableAmount, <?php echo DEFAULT_TAX_RATE; ?>);
            const totalAmount = taxableAmount + taxAmount;
            const paidAmount = parseFloat(document.getElementById('amountPaid').value) || 0;
            const changeAmount = Math.max(0, paidAmount - totalAmount);
            
            if (paidAmount < totalAmount && document.getElementById('paymentMethod').value !== 'credit') {
                showError('Insufficient payment amount');
                return;
            }
            
            const saleData = {
                action: 'process_sale',
                customer_id: selectedCustomer ? selectedCustomer.id : null,
                items: cart,
                subtotal: subtotal,
                tax_amount: taxAmount,
                discount_amount: discountAmount,
                total_amount: totalAmount,
                paid_amount: paidAmount,
                change_amount: changeAmount,
                payment_method: document.getElementById('paymentMethod').value,
                notes: document.getElementById('saleNotes').value
            };
            
            const processSaleBtn = document.getElementById('processSaleBtn');
            processSaleBtn.disabled = true;
            processSaleBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            
            fetch('../../api/pos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(saleData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(`Sale processed successfully! Sale #${data.sale_number}`);
                    
                    // Reset form
                    cart = [];
                    selectedCustomer = null;
                    document.getElementById('discountAmount').value = 0;
                    document.getElementById('amountPaid').value = 0;
                    document.getElementById('saleNotes').value = '';
                    document.getElementById('selectedCustomer').classList.add('hidden');
                    
                    updateCartDisplay();
                    updateTotals();
                    
                    // Offer to print receipt
                    if (confirm('Sale completed successfully! Would you like to print the receipt?')) {
                        printReceipt(data.sale_id);
                    }
                } else {
                    showError(data.error || 'Failed to process sale');
                }
            })
            .catch(error => {
                console.error('Error processing sale:', error);
                showError('Failed to process sale');
            })
            .finally(() => {
                processSaleBtn.disabled = false;
                processSaleBtn.innerHTML = '<i class="fas fa-cash-register mr-2"></i>Process Sale';
            });
        }

        function holdSale() {
            // Implementation for holding sale
            showInfo('Hold sale functionality will be implemented');
        }

        function printReceipt(saleId = null) {
            // Implementation for printing receipt
            showInfo('Print receipt functionality will be implemented');
        }

        function startBarcodeScanner() {
            // Implementation for barcode scanner using camera
            showInfo('Camera barcode scanner will be implemented');
        }

        // Utility functions
        function showSuccess(message) {
            alert('Success: ' + message);
        }

        function showError(message) {
            alert('Error: ' + message);
        }

        function showInfo(message) {
            alert('Info: ' + message);
        }

        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.classList.toggle('hidden');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('../../api/auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'logout' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '../../index.php';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.location.href = '../../index.php';
                });
            }
        }

        // Close modals when clicking outside
        document.getElementById('addCustomerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddCustomerModal();
            }
        });

        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('userMenu');
            const userButton = event.target.closest('button');
            
            if (!userButton || !userButton.onclick) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>

