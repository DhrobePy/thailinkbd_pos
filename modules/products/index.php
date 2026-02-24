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
    <title><?php echo APP_NAME; ?> - Products</title>
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
                        <a href="index.php" class="text-primary border-b-2 border-primary px-1 pt-1 pb-4 text-sm font-medium">Products</a>
                        <a href="../inventory/index.php" class="text-gray-500 hover:text-gray-700 px-1 pt-1 pb-4 text-sm font-medium">Inventory</a>
                        <a href="../pos/index.php" class="text-gray-500 hover:text-gray-700 px-1 pt-1 pb-4 text-sm font-medium">POS</a>
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
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">Products</h2>
                <p class="mt-1 text-sm text-gray-500">Manage your product catalog and inventory</p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <button onclick="exportProducts()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary mr-3">
                    <i class="fas fa-download mr-2"></i>
                    Export
                </button>
                <a href="add.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="fas fa-plus mr-2"></i>
                    Add Product
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="searchInput" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="searchInput" placeholder="Search products..."
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                    <div>
                        <label for="categoryFilter" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select id="categoryFilter" class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    <div>
                        <label for="brandFilter" class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                        <select id="brandFilter" class="block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                            <option value="">All Brands</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button onclick="clearFilters()" class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Product List</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Products will be loaded here -->
                    </tbody>
                </table>
            </div>
            
            <!-- Loading State -->
            <div id="loadingState" class="flex items-center justify-center py-12">
                <div class="flex items-center space-x-2 text-gray-500">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Loading products...</span>
                </div>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="hidden text-center py-12">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    <i class="fas fa-box text-4xl"></i>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No products found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new product.</p>
                <div class="mt-6">
                    <a href="add.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Add Product
                    </a>
                </div>
            </div>

            <!-- Pagination -->
            <div id="pagination" class="hidden bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button id="prevPageMobile" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </button>
                    <button id="nextPageMobile" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span id="showingFrom" class="font-medium">1</span> to <span id="showingTo" class="font-medium">10</span> of <span id="totalRecords" class="font-medium">97</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" id="paginationNav">
                            <!-- Pagination buttons will be generated here -->
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Details Modal -->
    <div id="productModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Product Details</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="modalContent">
                    <!-- Product details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let currentFilters = {
            search: '',
            category: '',
            brand: ''
        };

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
            loadBrands();
            loadProducts();
            
            // Setup event listeners
            document.getElementById('searchInput').addEventListener('input', debounce(function() {
                currentFilters.search = this.value;
                currentPage = 1;
                loadProducts();
            }, 300));
            
            document.getElementById('categoryFilter').addEventListener('change', function() {
                currentFilters.category = this.value;
                currentPage = 1;
                loadProducts();
            });
            
            document.getElementById('brandFilter').addEventListener('change', function() {
                currentFilters.brand = this.value;
                currentPage = 1;
                loadProducts();
            });
        });

        function loadCategories() {
            fetch('../../api/products.php?action=categories')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('categoryFilter');
                    data.categories.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        select.appendChild(option);
                    });
                })
                .catch(error => console.error('Error loading categories:', error));
        }

        function loadBrands() {
            fetch('../../api/products.php?action=brands')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('brandFilter');
                    data.brands.forEach(brand => {
                        const option = document.createElement('option');
                        option.value = brand.id;
                        option.textContent = brand.name;
                        select.appendChild(option);
                    });
                })
                .catch(error => console.error('Error loading brands:', error));
        }

        function loadProducts() {
            const params = new URLSearchParams({
                action: 'list',
                page: currentPage,
                ...currentFilters
            });

            showLoading();

            fetch(`../../api/products.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    displayProducts(data.data);
                    displayPagination(data.pagination);
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error loading products:', error);
                    showError('Failed to load products');
                });
        }

        function displayProducts(products) {
            const tbody = document.getElementById('productsTableBody');
            const emptyState = document.getElementById('emptyState');
            
            if (products.length === 0) {
                tbody.innerHTML = '';
                emptyState.classList.remove('hidden');
                return;
            }
            
            emptyState.classList.add('hidden');
            
            tbody.innerHTML = products.map(product => `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-box text-gray-500"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">${product.name}</div>
                                <div class="text-sm text-gray-500">${product.description || ''}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${product.sku}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${product.category_name || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${product.brand_name || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">৳ ${parseFloat(product.selling_price).toFixed(2)}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStockBadgeClass(product.total_stock, product.min_stock_level)}">
                            ${product.total_stock || 0}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${product.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            ${product.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="viewProduct(${product.id})" class="text-primary hover:text-blue-700 mr-3">
                            <i class="fas fa-eye"></i>
                        </button>
                        <a href="edit.php?id=${product.id}" class="text-yellow-600 hover:text-yellow-700 mr-3">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="deleteProduct(${product.id})" class="text-red-600 hover:text-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function getStockBadgeClass(stock, minLevel) {
            if (stock <= minLevel) {
                return 'bg-red-100 text-red-800';
            } else if (stock <= minLevel * 2) {
                return 'bg-yellow-100 text-yellow-800';
            } else {
                return 'bg-green-100 text-green-800';
            }
        }

        function displayPagination(pagination) {
            const paginationDiv = document.getElementById('pagination');
            
            if (pagination.total_pages <= 1) {
                paginationDiv.classList.add('hidden');
                return;
            }
            
            paginationDiv.classList.remove('hidden');
            
            // Update showing text
            document.getElementById('showingFrom').textContent = ((pagination.current_page - 1) * pagination.items_per_page) + 1;
            document.getElementById('showingTo').textContent = Math.min(pagination.current_page * pagination.items_per_page, pagination.total_records);
            document.getElementById('totalRecords').textContent = pagination.total_records;
            
            // Generate pagination buttons
            const nav = document.getElementById('paginationNav');
            nav.innerHTML = '';
            
            // Previous button
            const prevButton = document.createElement('button');
            prevButton.className = `relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${!pagination.has_prev ? 'cursor-not-allowed opacity-50' : ''}`;
            prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
            prevButton.disabled = !pagination.has_prev;
            prevButton.onclick = () => {
                if (pagination.has_prev) {
                    currentPage--;
                    loadProducts();
                }
            };
            nav.appendChild(prevButton);
            
            // Page numbers
            for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.total_pages, pagination.current_page + 2); i++) {
                const pageButton = document.createElement('button');
                pageButton.className = `relative inline-flex items-center px-4 py-2 border text-sm font-medium ${i === pagination.current_page ? 'z-10 bg-primary border-primary text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'}`;
                pageButton.textContent = i;
                pageButton.onclick = () => {
                    currentPage = i;
                    loadProducts();
                };
                nav.appendChild(pageButton);
            }
            
            // Next button
            const nextButton = document.createElement('button');
            nextButton.className = `relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${!pagination.has_next ? 'cursor-not-allowed opacity-50' : ''}`;
            nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
            nextButton.disabled = !pagination.has_next;
            nextButton.onclick = () => {
                if (pagination.has_next) {
                    currentPage++;
                    loadProducts();
                }
            };
            nav.appendChild(nextButton);
        }

        function showLoading() {
            document.getElementById('loadingState').classList.remove('hidden');
            document.getElementById('productsTableBody').innerHTML = '';
            document.getElementById('emptyState').classList.add('hidden');
            document.getElementById('pagination').classList.add('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingState').classList.add('hidden');
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('brandFilter').value = '';
            
            currentFilters = {
                search: '',
                category: '',
                brand: ''
            };
            currentPage = 1;
            loadProducts();
        }

        function viewProduct(id) {
            fetch(`../../api/products.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.product) {
                        showProductModal(data.product);
                    }
                })
                .catch(error => {
                    console.error('Error loading product:', error);
                    showError('Failed to load product details');
                });
        }

        function showProductModal(product) {
            const modal = document.getElementById('productModal');
            const content = document.getElementById('modalContent');
            
            content.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-4">${product.name}</h4>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">SKU</dt>
                                <dd class="text-sm text-gray-900">${product.sku}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Barcode</dt>
                                <dd class="text-sm text-gray-900">${product.barcode || '-'}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Category</dt>
                                <dd class="text-sm text-gray-900">${product.category_name || '-'}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Brand</dt>
                                <dd class="text-sm text-gray-900">${product.brand_name || '-'}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Description</dt>
                                <dd class="text-sm text-gray-900">${product.description || '-'}</dd>
                            </div>
                        </dl>
                    </div>
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Pricing & Stock</h4>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Cost Price</dt>
                                <dd class="text-sm text-gray-900">৳ ${parseFloat(product.cost_price).toFixed(2)}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Selling Price</dt>
                                <dd class="text-sm text-gray-900">৳ ${parseFloat(product.selling_price).toFixed(2)}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Wholesale Price</dt>
                                <dd class="text-sm text-gray-900">৳ ${product.wholesale_price ? parseFloat(product.wholesale_price).toFixed(2) : '-'}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Min Stock Level</dt>
                                <dd class="text-sm text-gray-900">${product.min_stock_level}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Reorder Point</dt>
                                <dd class="text-sm text-gray-900">${product.reorder_point}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
                ${product.variants && product.variants.length > 0 ? `
                    <div class="mt-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Product Variants</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variant</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Color</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    ${product.variants.map(variant => `
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${variant.variant_name}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${variant.sku}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">৳ ${parseFloat(variant.selling_price).toFixed(2)}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${variant.size || '-'}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${variant.color || '-'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ` : ''}
            `;
            
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
        }

        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                fetch(`../../api/products.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('Product deleted successfully');
                        loadProducts();
                    } else {
                        showError(data.error || 'Failed to delete product');
                    }
                })
                .catch(error => {
                    console.error('Error deleting product:', error);
                    showError('Failed to delete product');
                });
            }
        }

        function exportProducts() {
            // Implementation for exporting products
            showInfo('Export functionality will be implemented');
        }

        // Utility functions
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function showSuccess(message) {
            // Implementation for success notification
            console.log('Success:', message);
        }

        function showError(message) {
            // Implementation for error notification
            console.error('Error:', message);
        }

        function showInfo(message) {
            // Implementation for info notification
            console.log('Info:', message);
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

        // Close modal when clicking outside
        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
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

