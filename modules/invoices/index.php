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
    <title><?php echo APP_NAME; ?> - Invoice Management</title>
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

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Invoice Management
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Create, manage, and track invoices for your customers
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <button onclick="createNewInvoice()" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus -ml-1 mr-2 h-4 w-4"></i>
                    New Invoice
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-invoice text-blue-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Invoices</dt>
                                <dd class="text-lg font-medium text-gray-900">1,234</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Paid Invoices</dt>
                                <dd class="text-lg font-medium text-gray-900">1,156</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-yellow-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                                <dd class="text-lg font-medium text-gray-900">78</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-dollar-sign text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                                <dd class="text-lg font-medium text-gray-900">৳ 15,67,890</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Invoice Filters</h3>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option>All Status</option>
                            <option>Paid</option>
                            <option>Pending</option>
                            <option>Overdue</option>
                            <option>Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Customer</label>
                        <input type="text" placeholder="Search customer..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From Date</label>
                        <input type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">To Date</label>
                        <input type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="flex items-end">
                        <button class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Recent Invoices</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                <a href="#" onclick="viewInvoice('INV-2024-001')">#INV-2024-001</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">Beauty Palace</div>
                                <div class="text-sm text-gray-500">beauty@palace.com</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-08-28</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-09-12</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">৳ 15,420</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Paid</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="viewInvoice('INV-2024-001')" class="text-blue-600 hover:text-blue-900 mr-3" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="downloadInvoice('INV-2024-001')" class="text-green-600 hover:text-green-900 mr-3" title="Download PDF">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button onclick="sendInvoice('INV-2024-001')" class="text-purple-600 hover:text-purple-900 mr-3" title="Send Email">
                                    <i class="fas fa-envelope"></i>
                                </button>
                                <button onclick="editInvoice('INV-2024-001')" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                <a href="#" onclick="viewInvoice('INV-2024-002')">#INV-2024-002</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">Glamour Store</div>
                                <div class="text-sm text-gray-500">info@glamour.com</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-08-27</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-09-11</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">৳ 8,750</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="viewInvoice('INV-2024-002')" class="text-blue-600 hover:text-blue-900 mr-3" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="downloadInvoice('INV-2024-002')" class="text-green-600 hover:text-green-900 mr-3" title="Download PDF">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button onclick="sendInvoice('INV-2024-002')" class="text-purple-600 hover:text-purple-900 mr-3" title="Send Email">
                                    <i class="fas fa-envelope"></i>
                                </button>
                                <button onclick="editInvoice('INV-2024-002')" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                <a href="#" onclick="viewInvoice('INV-2024-003')">#INV-2024-003</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">Retail Customer</div>
                                <div class="text-sm text-gray-500">customer@email.com</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-08-26</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-09-10</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">৳ 2,100</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Overdue</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="viewInvoice('INV-2024-003')" class="text-blue-600 hover:text-blue-900 mr-3" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="downloadInvoice('INV-2024-003')" class="text-green-600 hover:text-green-900 mr-3" title="Download PDF">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button onclick="sendInvoice('INV-2024-003')" class="text-purple-600 hover:text-purple-900 mr-3" title="Send Email">
                                    <i class="fas fa-envelope"></i>
                                </button>
                                <button onclick="editInvoice('INV-2024-003')" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Invoice Creation Modal -->
    <div id="invoiceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Create New Invoice</h3>
                    <button onclick="closeInvoiceModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="invoiceForm" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Customer Name</label>
                            <input type="text" id="customerName" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Customer Email</label>
                            <input type="email" id="customerEmail" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Invoice Date</label>
                            <input type="date" id="invoiceDate" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Due Date</label>
                            <input type="date" id="dueDate" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Items</label>
                        <div class="border rounded-md">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="invoiceItems">
                                    <tr>
                                        <td class="px-4 py-2">
                                            <select class="w-full border-gray-300 rounded-md text-sm">
                                                <option>Select Product</option>
                                                <option>Lipstick Red Velvet</option>
                                                <option>Foundation Beige</option>
                                                <option>Mascara Black</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" class="w-full border-gray-300 rounded-md text-sm" min="1" value="1">
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" class="w-full border-gray-300 rounded-md text-sm" step="0.01" placeholder="0.00">
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="text-sm text-gray-900">৳ 0.00</span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <button type="button" onclick="removeInvoiceItem(this)" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" onclick="addInvoiceItem()" class="mt-2 text-blue-600 hover:text-blue-900 text-sm">
                            <i class="fas fa-plus mr-1"></i>Add Item
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea id="invoiceNotes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Subtotal:</span>
                                <span class="text-sm font-medium">৳ 0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Tax (15%):</span>
                                <span class="text-sm font-medium">৳ 0.00</span>
                            </div>
                            <div class="flex justify-between border-t pt-2">
                                <span class="text-base font-medium">Total:</span>
                                <span class="text-base font-bold">৳ 0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeInvoiceModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Create Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function createNewInvoice() {
            document.getElementById('invoiceModal').classList.remove('hidden');
            // Set today's date
            document.getElementById('invoiceDate').value = new Date().toISOString().split('T')[0];
            // Set due date to 15 days from now
            const dueDate = new Date();
            dueDate.setDate(dueDate.getDate() + 15);
            document.getElementById('dueDate').value = dueDate.toISOString().split('T')[0];
        }

        function closeInvoiceModal() {
            document.getElementById('invoiceModal').classList.add('hidden');
        }

        function viewInvoice(invoiceId) {
            alert('Viewing invoice: ' + invoiceId + ' - Feature coming soon!');
        }

        function downloadInvoice(invoiceId) {
            alert('Downloading PDF for invoice: ' + invoiceId + ' - Feature coming soon!');
        }

        function sendInvoice(invoiceId) {
            alert('Sending email for invoice: ' + invoiceId + ' - Feature coming soon!');
        }

        function editInvoice(invoiceId) {
            alert('Editing invoice: ' + invoiceId + ' - Feature coming soon!');
        }

        function addInvoiceItem() {
            const tbody = document.getElementById('invoiceItems');
            const newRow = tbody.rows[0].cloneNode(true);
            // Clear the values in the new row
            newRow.querySelectorAll('input').forEach(input => input.value = input.type === 'number' ? (input.min || '0') : '');
            newRow.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
            tbody.appendChild(newRow);
        }

        function removeInvoiceItem(button) {
            const tbody = document.getElementById('invoiceItems');
            if (tbody.rows.length > 1) {
                button.closest('tr').remove();
            }
        }

        document.getElementById('invoiceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const customerName = document.getElementById('customerName').value;
            const customerEmail = document.getElementById('customerEmail').value;
            const invoiceDate = document.getElementById('invoiceDate').value;
            const dueDate = document.getElementById('dueDate').value;
            
            if (!customerName || !invoiceDate || !dueDate) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Here you would normally send the data to the API
            alert('Invoice created successfully!');
            closeInvoiceModal();
            
            // Reset form
            document.getElementById('invoiceForm').reset();
        });
    </script>
</body>
</html>

