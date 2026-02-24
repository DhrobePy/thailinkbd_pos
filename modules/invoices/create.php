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
    <title><?php echo APP_NAME; ?> - Create Invoice</title>
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
                        <a href="../inventory/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Inventory</a>
                        <a href="../pos/index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">POS</a>
                        <a href="../invoices/index.php" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2 text-sm font-medium">Invoices</a>
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
                    Create New Invoice
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Generate a professional invoice for your customer
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                <a href="index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left -ml-1 mr-2 h-4 w-4"></i>
                    Back to Invoices
                </a>
                <button onclick="previewInvoice()" class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100">
                    <i class="fas fa-eye -ml-1 mr-2 h-4 w-4"></i>
                    Preview
                </button>
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

        <!-- Invoice Form -->
        <div class="bg-white shadow rounded-lg">
            <form id="invoiceForm" class="space-y-6 p-6">
                <!-- Invoice Header -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Company Info -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">From (Your Company)</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Company Name</label>
                                <input type="text" id="companyName" value="Thai Link BD" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Address</label>
                                <textarea id="companyAddress" rows="3" 
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">Dhaka, Bangladesh
Phone: +880-123-456-789
Email: info@thailinkbd.com</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Bill To (Customer)</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Customer Name *</label>
                                <input type="text" id="customerName" name="customer_name" required 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter customer name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Customer Address</label>
                                <textarea id="customerAddress" name="customer_address" rows="3" 
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Customer address, phone, email"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pt-6 border-t">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Invoice Number *</label>
                        <input type="text" id="invoiceNumber" name="invoice_number" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="INV-2024-001">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Invoice Date *</label>
                        <input type="date" id="invoiceDate" name="invoice_date" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Due Date *</label>
                        <input type="date" id="dueDate" name="due_date" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Customer Type</label>
                        <select id="customerType" name="customer_type" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="retail">Retail</option>
                            <option value="wholesale">Wholesale</option>
                        </select>
                    </div>
                </div>

                <!-- Invoice Items -->
                <div class="pt-6 border-t">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Invoice Items</h3>
                        <button type="button" onclick="addInvoiceItem()" 
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                            <i class="fas fa-plus mr-2"></i>Add Item
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200 rounded-lg">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price (৳)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total (৳)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody id="invoiceItems" class="bg-white divide-y divide-gray-200">
                                <tr class="invoice-item">
                                    <td class="px-4 py-3">
                                        <select class="w-full border-gray-300 rounded-md text-sm product-select" onchange="updateProductDetails(this)">
                                            <option value="">Select Product</option>
                                            <option value="1" data-price-retail="850" data-price-wholesale="750" data-description="Premium Collection">Lipstick Red Velvet</option>
                                            <option value="2" data-price-retail="1200" data-price-wholesale="1000" data-description="Natural Glow">Foundation Beige</option>
                                            <option value="3" data-price-retail="650" data-price-wholesale="550" data-description="Long Lasting">Mascara Black</option>
                                            <option value="4" data-price-retail="1500" data-price-wholesale="1200" data-description="12 Shades">Eyeshadow Palette</option>
                                            <option value="5" data-price-retail="450" data-price-wholesale="350" data-description="Natural Pink">Blush Pink</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" class="w-full border-gray-300 rounded-md text-sm item-description" placeholder="Product description">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" class="w-full border-gray-300 rounded-md text-sm item-quantity" min="1" value="1" onchange="calculateItemTotal(this)">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" class="w-full border-gray-300 rounded-md text-sm item-price" step="0.01" min="0" onchange="calculateItemTotal(this)">
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm font-medium item-total">৳ 0.00</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button type="button" onclick="removeInvoiceItem(this)" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Invoice Totals -->
                <div class="pt-6 border-t">
                    <div class="flex justify-end">
                        <div class="w-full max-w-md space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Subtotal:</span>
                                <span class="text-sm font-medium" id="subtotal">৳ 0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Discount:</span>
                                <div class="flex items-center space-x-2">
                                    <input type="number" id="discountAmount" step="0.01" min="0" value="0" 
                                           class="w-20 border-gray-300 rounded-md text-sm" onchange="calculateTotals()">
                                    <span class="text-sm text-gray-600">৳</span>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Tax (15%):</span>
                                <span class="text-sm font-medium" id="taxAmount">৳ 0.00</span>
                            </div>
                            <div class="flex justify-between border-t pt-3">
                                <span class="text-base font-bold">Total:</span>
                                <span class="text-base font-bold" id="grandTotal">৳ 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Terms & Notes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Payment Terms</label>
                        <select id="paymentTerms" name="payment_terms" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="net_15">Net 15 days</option>
                            <option value="net_30">Net 30 days</option>
                            <option value="due_on_receipt">Due on receipt</option>
                            <option value="cash_on_delivery">Cash on delivery</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="invoiceStatus" name="status" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea id="invoiceNotes" name="notes" rows="3" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Thank you for your business! Payment is due within 15 days."></textarea>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <a href="index.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </a>
                    <button type="button" onclick="saveAsDraft()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-yellow-100 border border-yellow-300 rounded-md hover:bg-yellow-200">
                        <i class="fas fa-save mr-2"></i>Save as Draft
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        <i class="fas fa-paper-plane mr-2"></i>Create & Send Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Set default dates
        document.getElementById('invoiceDate').value = new Date().toISOString().split('T')[0];
        const dueDate = new Date();
        dueDate.setDate(dueDate.getDate() + 15);
        document.getElementById('dueDate').value = dueDate.toISOString().split('T')[0];

        // Generate invoice number
        document.getElementById('invoiceNumber').value = 'INV-' + new Date().getFullYear() + '-' + String(Math.floor(Math.random() * 1000)).padStart(3, '0');

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

        function updateProductDetails(selectElement) {
            const row = selectElement.closest('tr');
            const selectedOption = selectElement.selectedOptions[0];
            
            if (selectedOption.value) {
                const customerType = document.getElementById('customerType').value;
                const price = customerType === 'wholesale' ? 
                    selectedOption.dataset.priceWholesale : 
                    selectedOption.dataset.priceRetail;
                const description = selectedOption.dataset.description;
                
                row.querySelector('.item-description').value = description;
                row.querySelector('.item-price').value = price;
                
                calculateItemTotal(row.querySelector('.item-quantity'));
            }
        }

        function calculateItemTotal(element) {
            const row = element.closest('tr');
            const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const total = quantity * price;
            
            row.querySelector('.item-total').textContent = '৳ ' + total.toFixed(2);
            calculateTotals();
        }

        function calculateTotals() {
            let subtotal = 0;
            
            document.querySelectorAll('.invoice-item').forEach(row => {
                const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                subtotal += quantity * price;
            });
            
            const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
            const discountedSubtotal = subtotal - discount;
            const tax = discountedSubtotal * 0.15; // 15% tax
            const grandTotal = discountedSubtotal + tax;
            
            document.getElementById('subtotal').textContent = '৳ ' + subtotal.toFixed(2);
            document.getElementById('taxAmount').textContent = '৳ ' + tax.toFixed(2);
            document.getElementById('grandTotal').textContent = '৳ ' + grandTotal.toFixed(2);
        }

        function addInvoiceItem() {
            const tbody = document.getElementById('invoiceItems');
            const newRow = tbody.rows[0].cloneNode(true);
            
            // Clear the values in the new row
            newRow.querySelectorAll('input').forEach(input => {
                if (input.type === 'number') {
                    input.value = input.classList.contains('item-quantity') ? '1' : '0';
                } else {
                    input.value = '';
                }
            });
            newRow.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
            newRow.querySelector('.item-total').textContent = '৳ 0.00';
            
            tbody.appendChild(newRow);
        }

        function removeInvoiceItem(button) {
            const tbody = document.getElementById('invoiceItems');
            if (tbody.rows.length > 1) {
                button.closest('tr').remove();
                calculateTotals();
            }
        }

        function previewInvoice() {
            alert('Invoice preview feature coming soon!');
        }

        function saveAsDraft() {
            document.getElementById('invoiceStatus').value = 'draft';
            document.getElementById('invoiceForm').dispatchEvent(new Event('submit'));
        }

        // Update prices when customer type changes
        document.getElementById('customerType').addEventListener('change', function() {
            document.querySelectorAll('.product-select').forEach(select => {
                if (select.value) {
                    updateProductDetails(select);
                }
            });
        });

        // Form submission
        document.getElementById('invoiceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const customerName = document.getElementById('customerName').value;
            const invoiceNumber = document.getElementById('invoiceNumber').value;
            const invoiceDate = document.getElementById('invoiceDate').value;
            const dueDate = document.getElementById('dueDate').value;
            
            // Validation
            if (!customerName || !invoiceNumber || !invoiceDate || !dueDate) {
                showMessage('error', 'Please fill in all required fields');
                return;
            }
            
            // Check if at least one item is added
            const hasItems = Array.from(document.querySelectorAll('.product-select')).some(select => select.value);
            if (!hasItems) {
                showMessage('error', 'Please add at least one item to the invoice');
                return;
            }
            
            const status = document.getElementById('invoiceStatus').value;
            const statusText = status === 'draft' ? 'saved as draft' : 'created and sent';
            
            // Simulate API call
            setTimeout(() => {
                showMessage('success', `Invoice ${invoiceNumber} has been ${statusText} successfully!`);
                
                // Reset form after successful submission
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 2000);
            }, 1000);
        });

        // Initial calculation
        calculateTotals();
    </script>
</body>
</html>

