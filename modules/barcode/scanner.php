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
    <title>Barcode Scanner - Thai Link BD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="../../index.php" class="text-xl font-bold">Thai Link BD</a>
                <span class="text-blue-200">Barcode Scanner</span>
            </div>
            <div class="flex items-center space-x-4">
                <span>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></span>
                <a href="../../modules/auth/logout.php" class="bg-blue-700 px-3 py-1 rounded">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Scanner Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <i class="fas fa-qrcode mr-2 text-blue-600"></i>
                    Barcode Scanner
                </h2>
                
                <!-- Scanner Controls -->
                <div class="mb-4 flex space-x-2">
                    <button id="startScan" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        <i class="fas fa-play mr-2"></i>Start Scanner
                    </button>
                    <button id="stopScan" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700" disabled>
                        <i class="fas fa-stop mr-2"></i>Stop Scanner
                    </button>
                    <button id="switchCamera" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" disabled>
                        <i class="fas fa-camera mr-2"></i>Switch Camera
                    </button>
                </div>

                <!-- Scanner Display -->
                <div id="reader" class="w-full h-64 bg-gray-100 rounded border-2 border-dashed border-gray-300 flex items-center justify-center">
                    <div class="text-center text-gray-500">
                        <i class="fas fa-qrcode text-4xl mb-2"></i>
                        <p>Click "Start Scanner" to begin</p>
                    </div>
                </div>

                <!-- Manual Barcode Input -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Manual Barcode Entry:</label>
                    <div class="flex space-x-2">
                        <input type="text" id="manualBarcode" placeholder="Enter barcode manually" 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button onclick="processBarcode(document.getElementById('manualBarcode').value)" 
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Transaction Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <i class="fas fa-exchange-alt mr-2 text-green-600"></i>
                    Inventory Transaction
                </h2>

                <!-- Transaction Type -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Type:</label>
                    <select id="transactionType" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="in">Stock In (Receiving)</option>
                        <option value="out">Stock Out (Issue/Sale)</option>
                        <option value="adjustment">Stock Adjustment</option>
                        <option value="transfer">Stock Transfer</option>
                    </select>
                </div>

                <!-- Product Information Display -->
                <div id="productInfo" class="hidden mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <h3 class="font-semibold text-blue-800 mb-2">Product Found:</h3>
                    <div id="productDetails"></div>
                </div>

                <!-- Transaction Form -->
                <div id="transactionForm" class="hidden space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity:</label>
                        <input type="number" id="quantity" min="1" value="1" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Location:</label>
                        <input type="text" id="location" value="Main Store" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Batch Number (Optional):</label>
                        <input type="text" id="batchNumber" placeholder="Enter batch number" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date (Optional):</label>
                        <input type="date" id="expiryDate" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reference Type:</label>
                        <select id="referenceType" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="purchase">Purchase Order</option>
                            <option value="sale">Sale</option>
                            <option value="adjustment">Manual Adjustment</option>
                            <option value="transfer">Transfer</option>
                            <option value="return">Return</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reference ID (Optional):</label>
                        <input type="text" id="referenceId" placeholder="PO/Sale/Invoice number" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes:</label>
                        <textarea id="notes" rows="3" placeholder="Transaction notes..." 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <button onclick="processTransaction()" 
                            class="w-full bg-green-600 text-white py-3 rounded-md hover:bg-green-700 font-semibold">
                        <i class="fas fa-check mr-2"></i>Process Transaction
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="mt-6 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-history mr-2 text-purple-600"></i>
                Recent Transactions
            </h2>
            <div id="recentTransactions" class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsList" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                No recent transactions. Start scanning to see activity here.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="messageContainer" class="fixed top-4 right-4 z-50"></div>

    <script>
        let html5QrcodeScanner = null;
        let currentProduct = null;
        let cameras = [];
        let currentCameraIndex = 0;

        // Initialize scanner
        function initScanner() {
            html5QrcodeScanner = new Html5Qrcode("reader");
        }

        // Start scanning
        document.getElementById('startScan').addEventListener('click', async function() {
            try {
                const cameras = await Html5Qrcode.getCameras();
                if (cameras && cameras.length) {
                    const cameraId = cameras[currentCameraIndex].id;
                    
                    await html5QrcodeScanner.start(
                        cameraId,
                        {
                            fps: 10,
                            qrbox: { width: 250, height: 250 }
                        },
                        (decodedText, decodedResult) => {
                            processBarcode(decodedText);
                        },
                        (errorMessage) => {
                            // Handle scan errors silently
                        }
                    );
                    
                    document.getElementById('startScan').disabled = true;
                    document.getElementById('stopScan').disabled = false;
                    document.getElementById('switchCamera').disabled = cameras.length <= 1;
                    
                    showMessage('Scanner started successfully', 'success');
                } else {
                    showMessage('No cameras found', 'error');
                }
            } catch (err) {
                showMessage('Error starting scanner: ' + err, 'error');
            }
        });

        // Stop scanning
        document.getElementById('stopScan').addEventListener('click', async function() {
            try {
                await html5QrcodeScanner.stop();
                document.getElementById('startScan').disabled = false;
                document.getElementById('stopScan').disabled = true;
                document.getElementById('switchCamera').disabled = true;
                showMessage('Scanner stopped', 'info');
            } catch (err) {
                showMessage('Error stopping scanner: ' + err, 'error');
            }
        });

        // Switch camera
        document.getElementById('switchCamera').addEventListener('click', async function() {
            try {
                await html5QrcodeScanner.stop();
                
                const cameras = await Html5Qrcode.getCameras();
                currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
                
                const cameraId = cameras[currentCameraIndex].id;
                await html5QrcodeScanner.start(
                    cameraId,
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 }
                    },
                    (decodedText, decodedResult) => {
                        processBarcode(decodedText);
                    },
                    (errorMessage) => {
                        // Handle scan errors silently
                    }
                );
                
                showMessage('Switched to camera ' + (currentCameraIndex + 1), 'info');
            } catch (err) {
                showMessage('Error switching camera: ' + err, 'error');
            }
        });

        // Process barcode
        async function processBarcode(barcode) {
            if (!barcode || barcode.trim() === '') {
                showMessage('Invalid barcode', 'error');
                return;
            }

            try {
                const response = await fetch('../../api/barcode.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'lookup',
                        barcode: barcode.trim()
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    currentProduct = data.product;
                    displayProductInfo(data.product);
                    document.getElementById('transactionForm').classList.remove('hidden');
                    showMessage('Product found: ' + data.product.name, 'success');
                } else {
                    showMessage(data.message || 'Product not found', 'error');
                    currentProduct = null;
                    document.getElementById('productInfo').classList.add('hidden');
                    document.getElementById('transactionForm').classList.add('hidden');
                }
            } catch (error) {
                showMessage('Error looking up barcode: ' + error.message, 'error');
            }

            // Clear manual input
            document.getElementById('manualBarcode').value = '';
        }

        // Display product information
        function displayProductInfo(product) {
            const productDetails = document.getElementById('productDetails');
            const variantInfo = product.variant_name ? ` (${product.variant_name})` : '';
            
            productDetails.innerHTML = `
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><strong>Name:</strong> ${product.name}${variantInfo}</div>
                    <div><strong>SKU:</strong> ${product.sku}</div>
                    <div><strong>Brand:</strong> ${product.brand_name || 'N/A'}</div>
                    <div><strong>Category:</strong> ${product.category_name || 'N/A'}</div>
                    <div><strong>Current Stock:</strong> ${product.current_stock || 0}</div>
                    <div><strong>Cost Price:</strong> ৳${parseFloat(product.cost_price || 0).toFixed(2)}</div>
                </div>
            `;
            
            document.getElementById('productInfo').classList.remove('hidden');
        }

        // Process transaction
        async function processTransaction() {
            if (!currentProduct) {
                showMessage('No product selected', 'error');
                return;
            }

            const transactionData = {
                action: 'transaction',
                product_id: currentProduct.product_id,
                variant_id: currentProduct.variant_id || null,
                transaction_type: document.getElementById('transactionType').value,
                quantity: parseInt(document.getElementById('quantity').value),
                location: document.getElementById('location').value,
                batch_number: document.getElementById('batchNumber').value || null,
                expiry_date: document.getElementById('expiryDate').value || null,
                reference_type: document.getElementById('referenceType').value,
                reference_id: document.getElementById('referenceId').value || null,
                notes: document.getElementById('notes').value || null
            };

            if (transactionData.quantity <= 0) {
                showMessage('Quantity must be greater than 0', 'error');
                return;
            }

            try {
                const response = await fetch('../../api/barcode.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(transactionData)
                });

                const data = await response.json();
                
                if (data.success) {
                    showMessage('Transaction processed successfully', 'success');
                    
                    // Reset form
                    document.getElementById('quantity').value = 1;
                    document.getElementById('batchNumber').value = '';
                    document.getElementById('expiryDate').value = '';
                    document.getElementById('referenceId').value = '';
                    document.getElementById('notes').value = '';
                    
                    // Update product info with new stock
                    if (data.new_stock !== undefined) {
                        currentProduct.current_stock = data.new_stock;
                        displayProductInfo(currentProduct);
                    }
                    
                    // Refresh recent transactions
                    loadRecentTransactions();
                } else {
                    showMessage(data.message || 'Transaction failed', 'error');
                }
            } catch (error) {
                showMessage('Error processing transaction: ' + error.message, 'error');
            }
        }

        // Load recent transactions
        async function loadRecentTransactions() {
            try {
                const response = await fetch('../../api/barcode.php?action=recent_transactions');
                const data = await response.json();
                
                if (data.success) {
                    const tbody = document.getElementById('transactionsList');
                    
                    if (data.transactions.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    No recent transactions found.
                                </td>
                            </tr>
                        `;
                    } else {
                        tbody.innerHTML = data.transactions.map(transaction => `
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">
                                    ${new Date(transaction.created_at).toLocaleString()}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-900">
                                    ${transaction.product_name}
                                    ${transaction.variant_name ? `<br><small class="text-gray-500">${transaction.variant_name}</small>` : ''}
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full ${getTransactionTypeClass(transaction.transaction_type)}">
                                        ${transaction.transaction_type.toUpperCase()}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-900">
                                    ${transaction.transaction_type === 'out' ? '-' : '+'}${transaction.quantity}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    ${transaction.reference_type || 'N/A'}
                                    ${transaction.reference_id ? `<br><small>#${transaction.reference_id}</small>` : ''}
                                </td>
                            </tr>
                        `).join('');
                    }
                }
            } catch (error) {
                console.error('Error loading recent transactions:', error);
            }
        }

        // Get transaction type CSS class
        function getTransactionTypeClass(type) {
            switch (type) {
                case 'in': return 'bg-green-100 text-green-800';
                case 'out': return 'bg-red-100 text-red-800';
                case 'adjustment': return 'bg-yellow-100 text-yellow-800';
                case 'transfer': return 'bg-blue-100 text-blue-800';
                default: return 'bg-gray-100 text-gray-800';
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

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initScanner();
            loadRecentTransactions();
            
            // Auto-focus manual barcode input
            document.getElementById('manualBarcode').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    processBarcode(this.value);
                }
            });
        });
    </script>
</body>
</html>

