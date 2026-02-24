<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = $auth->getCurrentUser();

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_products':
            getProducts($db);
            break;
            
        case 'get_product':
            getProduct($db);
            break;
            
        case 'create_product':
            createProduct($db, $user);
            break;
            
        case 'update_product':
            updateProduct($db, $user);
            break;
            
        case 'delete_product':
            deleteProduct($db, $user);
            break;
            
        case 'get_filters':
            getFilters($db);
            break;
            
        case 'get_batches':
            getBatches($db);
            break;
            
        case 'create_batch':
            createBatch($db, $user);
            break;
            
        case 'update_batch':
            updateBatch($db, $user);
            break;
            
        case 'delete_batch':
            deleteBatch($db, $user);
            break;
            
        case 'generate_barcode':
            generateBarcode($db);
            break;
            
        case 'export_products':
            exportProducts($db);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log("Products API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

// Get all products with inventory and alerts
function getProducts($db) {
    try {
        // Get products with related data
        $query = "
            SELECT 
                p.*,
                c.name as category_name,
                b.name as brand_name,
                s.name as supplier_name,
                COALESCE(i.quantity, 0) as current_stock,
                CASE 
                    WHEN p.expiry_tracking = 1 THEN (
                        SELECT MIN(ib.expiry_date) 
                        FROM inventory_batches ib 
                        WHERE ib.product_id = p.id 
                        AND ib.quantity > 0 
                        AND ib.expiry_date IS NOT NULL
                        AND ib.expiry_date > CURDATE()
                    )
                    ELSE NULL
                END as nearest_expiry
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            LEFT JOIN inventory i ON p.id = i.product_id
            WHERE p.is_active = 1
            ORDER BY p.name ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate summary statistics
        $summary = [
            'total_products' => count($products),
            'low_stock' => 0,
            'out_of_stock' => 0,
            'expiring_soon' => 0,
            'total_value' => 0
        ];
        
        $alerts = [];
        $lowStockProducts = [];
        $expiringProducts = [];
        
        foreach ($products as &$product) {
            $currentStock = (int)$product['current_stock'];
            $minLevel = (int)$product['min_stock_level'];
            
            // Calculate inventory value
            $summary['total_value'] += $currentStock * (float)$product['cost_price'];
            
            // Check stock levels
            if ($currentStock === 0) {
                $summary['out_of_stock']++;
            } elseif ($currentStock <= $minLevel) {
                $summary['low_stock']++;
                $lowStockProducts[] = [
                    'name' => $product['name'],
                    'sku' => $product['sku'],
                    'current_stock' => $currentStock,
                    'min_level' => $minLevel
                ];
            }
            
            // Check expiry dates
            if ($product['expiry_tracking'] && $product['nearest_expiry']) {
                $daysToExpiry = (strtotime($product['nearest_expiry']) - time()) / (60 * 60 * 24);
                if ($daysToExpiry <= 30) {
                    $summary['expiring_soon']++;
                    $expiringProducts[] = [
                        'name' => $product['name'],
                        'sku' => $product['sku'],
                        'expiry_date' => $product['nearest_expiry'],
                        'days_left' => (int)$daysToExpiry
                    ];
                }
            }
        }
        
        // Generate alerts
        if (!empty($lowStockProducts)) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low Stock Alert',
                'message' => count($lowStockProducts) . ' products are running low on stock.',
                'products' => array_map(function($p) {
                    return [
                        'name' => $p['name'],
                        'sku' => $p['sku'],
                        'detail' => "Stock: {$p['current_stock']}, Min: {$p['min_level']}"
                    ];
                }, $lowStockProducts)
            ];
        }
        
        if (!empty($expiringProducts)) {
            $alerts[] = [
                'type' => 'error',
                'title' => 'Products Expiring Soon',
                'message' => count($expiringProducts) . ' products are expiring within 30 days.',
                'products' => array_map(function($p) {
                    return [
                        'name' => $p['name'],
                        'sku' => $p['sku'],
                        'detail' => "Expires in {$p['days_left']} days"
                    ];
                }, $expiringProducts)
            ];
        }
        
        echo json_encode([
            'success' => true,
            'products' => $products,
            'summary' => $summary,
            'alerts' => $alerts
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error fetching products: ' . $e->getMessage());
    }
}

// Get single product
function getProduct($db) {
    try {
        $productId = $_GET['id'] ?? null;
        if (!$productId) {
            throw new Exception('Product ID is required');
        }
        
        $query = "
            SELECT p.*, COALESCE(i.quantity, 0) as current_stock
            FROM products p
            LEFT JOIN inventory i ON p.id = i.product_id
            WHERE p.id = :id
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $productId);
        $stmt->execute();
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error fetching product: ' . $e->getMessage());
    }
}

// Create new product
function createProduct($db, $user) {
    try {
        $db->beginTransaction();
        
        // Validate required fields
        $requiredFields = ['name', 'sku', 'category_id', 'cost_price', 'selling_price', 'min_stock_level'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Check if SKU already exists
        $checkSku = $db->prepare("SELECT id FROM products WHERE sku = :sku");
        $checkSku->bindParam(':sku', $_POST['sku']);
        $checkSku->execute();
        
        if ($checkSku->rowCount() > 0) {
            throw new Exception('SKU already exists');
        }
        
        // Handle image upload
        $imageName = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imageName = handleImageUpload($_FILES['image']);
        }
        
        // Insert product
        $query = "
            INSERT INTO products (
                name, sku, description, category_id, brand_id, supplier_id,
                cost_price, selling_price, wholesale_price, min_stock_level,
                weight, dimensions, image, expiry_tracking, shelf_life, alert_days,
                is_active, created_by, created_at
            ) VALUES (
                :name, :sku, :description, :category_id, :brand_id, :supplier_id,
                :cost_price, :selling_price, :wholesale_price, :min_stock_level,
                :weight, :dimensions, :image, :expiry_tracking, :shelf_life, :alert_days,
                :is_active, :created_by, NOW()
            )
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $_POST['name']);
        $stmt->bindParam(':sku', $_POST['sku']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':category_id', $_POST['category_id']);
        $stmt->bindParam(':brand_id', $_POST['brand_id'] ?: null);
        $stmt->bindParam(':supplier_id', $_POST['supplier_id'] ?: null);
        $stmt->bindParam(':cost_price', $_POST['cost_price']);
        $stmt->bindParam(':selling_price', $_POST['selling_price']);
        $stmt->bindParam(':wholesale_price', $_POST['wholesale_price'] ?: null);
        $stmt->bindParam(':min_stock_level', $_POST['min_stock_level']);
        $stmt->bindParam(':weight', $_POST['weight'] ?: null);
        $stmt->bindParam(':dimensions', $_POST['dimensions'] ?: null);
        $stmt->bindParam(':image', $imageName);
        $stmt->bindParam(':expiry_tracking', $_POST['expiry_tracking'] ? 1 : 0);
        $stmt->bindParam(':shelf_life', $_POST['shelf_life'] ?: null);
        $stmt->bindParam(':alert_days', $_POST['alert_days'] ?: 30);
        $stmt->bindParam(':is_active', $_POST['is_active'] ? 1 : 0);
        $stmt->bindParam(':created_by', $user['id']);
        
        $stmt->execute();
        $productId = $db->lastInsertId();
        
        // Create initial inventory record
        $inventoryQuery = "
            INSERT INTO inventory (product_id, quantity, location, updated_by, updated_at)
            VALUES (:product_id, 0, 'Main Store', :updated_by, NOW())
        ";
        
        $inventoryStmt = $db->prepare($inventoryQuery);
        $inventoryStmt->bindParam(':product_id', $productId);
        $inventoryStmt->bindParam(':updated_by', $user['id']);
        $inventoryStmt->execute();
        
        // Generate barcode if not exists
        if (empty($_POST['barcode'])) {
            $barcode = generateProductBarcode($productId);
            $updateBarcode = $db->prepare("UPDATE products SET barcode = :barcode WHERE id = :id");
            $updateBarcode->bindParam(':barcode', $barcode);
            $updateBarcode->bindParam(':id', $productId);
            $updateBarcode->execute();
        }
        
        // Log activity
        logActivity($db, $user['id'], 'product_created', "Created product: {$_POST['name']} (SKU: {$_POST['sku']})");
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Product created successfully',
            'product_id' => $productId
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception('Error creating product: ' . $e->getMessage());
    }
}

// Update product
function updateProduct($db, $user) {
    try {
        $db->beginTransaction();
        
        $productId = $_POST['id'] ?? null;
        if (!$productId) {
            throw new Exception('Product ID is required');
        }
        
        // Check if product exists
        $checkProduct = $db->prepare("SELECT * FROM products WHERE id = :id");
        $checkProduct->bindParam(':id', $productId);
        $checkProduct->execute();
        $existingProduct = $checkProduct->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingProduct) {
            throw new Exception('Product not found');
        }
        
        // Check if SKU already exists (excluding current product)
        $checkSku = $db->prepare("SELECT id FROM products WHERE sku = :sku AND id != :id");
        $checkSku->bindParam(':sku', $_POST['sku']);
        $checkSku->bindParam(':id', $productId);
        $checkSku->execute();
        
        if ($checkSku->rowCount() > 0) {
            throw new Exception('SKU already exists');
        }
        
        // Handle image upload
        $imageName = $existingProduct['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Delete old image
            if ($imageName && file_exists("../uploads/products/$imageName")) {
                unlink("../uploads/products/$imageName");
            }
            $imageName = handleImageUpload($_FILES['image']);
        }
        
        // Update product
        $query = "
            UPDATE products SET
                name = :name,
                sku = :sku,
                description = :description,
                category_id = :category_id,
                brand_id = :brand_id,
                supplier_id = :supplier_id,
                cost_price = :cost_price,
                selling_price = :selling_price,
                wholesale_price = :wholesale_price,
                min_stock_level = :min_stock_level,
                weight = :weight,
                dimensions = :dimensions,
                image = :image,
                expiry_tracking = :expiry_tracking,
                shelf_life = :shelf_life,
                alert_days = :alert_days,
                is_active = :is_active,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $productId);
        $stmt->bindParam(':name', $_POST['name']);
        $stmt->bindParam(':sku', $_POST['sku']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':category_id', $_POST['category_id']);
        $stmt->bindParam(':brand_id', $_POST['brand_id'] ?: null);
        $stmt->bindParam(':supplier_id', $_POST['supplier_id'] ?: null);
        $stmt->bindParam(':cost_price', $_POST['cost_price']);
        $stmt->bindParam(':selling_price', $_POST['selling_price']);
        $stmt->bindParam(':wholesale_price', $_POST['wholesale_price'] ?: null);
        $stmt->bindParam(':min_stock_level', $_POST['min_stock_level']);
        $stmt->bindParam(':weight', $_POST['weight'] ?: null);
        $stmt->bindParam(':dimensions', $_POST['dimensions'] ?: null);
        $stmt->bindParam(':image', $imageName);
        $stmt->bindParam(':expiry_tracking', $_POST['expiry_tracking'] ? 1 : 0);
        $stmt->bindParam(':shelf_life', $_POST['shelf_life'] ?: null);
        $stmt->bindParam(':alert_days', $_POST['alert_days'] ?: 30);
        $stmt->bindParam(':is_active', $_POST['is_active'] ? 1 : 0);
        $stmt->bindParam(':updated_by', $user['id']);
        
        $stmt->execute();
        
        // Log activity
        logActivity($db, $user['id'], 'product_updated', "Updated product: {$_POST['name']} (SKU: {$_POST['sku']})");
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception('Error updating product: ' . $e->getMessage());
    }
}

// Delete product
function deleteProduct($db, $user) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = $input['id'] ?? null;
        
        if (!$productId) {
            throw new Exception('Product ID is required');
        }
        
        $db->beginTransaction();
        
        // Check if product exists
        $checkProduct = $db->prepare("SELECT * FROM products WHERE id = :id");
        $checkProduct->bindParam(':id', $productId);
        $checkProduct->execute();
        $product = $checkProduct->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        // Check if product has sales
        $checkSales = $db->prepare("SELECT COUNT(*) as count FROM sale_items WHERE product_id = :id");
        $checkSales->bindParam(':id', $productId);
        $checkSales->execute();
        $salesCount = $checkSales->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($salesCount > 0) {
            // Don't delete, just deactivate
            $deactivate = $db->prepare("UPDATE products SET is_active = 0, updated_by = :updated_by, updated_at = NOW() WHERE id = :id");
            $deactivate->bindParam(':id', $productId);
            $deactivate->bindParam(':updated_by', $user['id']);
            $deactivate->execute();
            
            logActivity($db, $user['id'], 'product_deactivated', "Deactivated product: {$product['name']} (has sales history)");
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Product deactivated (has sales history)'
            ]);
            return;
        }
        
        // Delete related records
        $db->prepare("DELETE FROM inventory_batches WHERE product_id = :id")->execute([':id' => $productId]);
        $db->prepare("DELETE FROM inventory WHERE product_id = :id")->execute([':id' => $productId]);
        $db->prepare("DELETE FROM barcodes WHERE product_id = :id")->execute([':id' => $productId]);
        
        // Delete product image
        if ($product['image'] && file_exists("../uploads/products/{$product['image']}")) {
            unlink("../uploads/products/{$product['image']}");
        }
        
        // Delete product
        $deleteProduct = $db->prepare("DELETE FROM products WHERE id = :id");
        $deleteProduct->bindParam(':id', $productId);
        $deleteProduct->execute();
        
        // Log activity
        logActivity($db, $user['id'], 'product_deleted', "Deleted product: {$product['name']} (SKU: {$product['sku']})");
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception('Error deleting product: ' . $e->getMessage());
    }
}

// Get filter options
function getFilters($db) {
    try {
        // Get categories
        $categoriesQuery = "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name";
        $categoriesStmt = $db->prepare($categoriesQuery);
        $categoriesStmt->execute();
        $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get brands
        $brandsQuery = "SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name";
        $brandsStmt = $db->prepare($brandsQuery);
        $brandsStmt->execute();
        $brands = $brandsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get suppliers
        $suppliersQuery = "SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name";
        $suppliersStmt = $db->prepare($suppliersQuery);
        $suppliersStmt->execute();
        $suppliers = $suppliersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'categories' => $categories,
            'brands' => $brands,
            'suppliers' => $suppliers
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error fetching filters: ' . $e->getMessage());
    }
}

// Get product batches
function getBatches($db) {
    try {
        $productId = $_GET['product_id'] ?? null;
        if (!$productId) {
            throw new Exception('Product ID is required');
        }
        
        $query = "
            SELECT 
                ib.*,
                p.name as product_name,
                pv.variant_name
            FROM inventory_batches ib
            JOIN products p ON ib.product_id = p.id
            LEFT JOIN product_variants pv ON ib.variant_id = pv.id
            WHERE ib.product_id = :product_id
            ORDER BY ib.expiry_date ASC, ib.created_at ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'batches' => $batches
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error fetching batches: ' . $e->getMessage());
    }
}

// Generate barcode
function generateBarcode($db) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = $input['product_id'] ?? null;
        
        if (!$productId) {
            throw new Exception('Product ID is required');
        }
        
        // Get product details
        $productQuery = "SELECT * FROM products WHERE id = :id";
        $productStmt = $db->prepare($productQuery);
        $productStmt->bindParam(':id', $productId);
        $productStmt->execute();
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        // Generate barcode if not exists
        $barcode = $product['barcode'];
        if (!$barcode) {
            $barcode = generateProductBarcode($productId);
            
            // Update product with barcode
            $updateQuery = "UPDATE products SET barcode = :barcode WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':barcode', $barcode);
            $updateStmt->bindParam(':id', $productId);
            $updateStmt->execute();
        }
        
        // Generate barcode image (using a simple implementation)
        $barcodeImage = generateBarcodeImage($barcode);
        
        echo json_encode([
            'success' => true,
            'barcode' => $barcode,
            'barcode_image' => $barcodeImage,
            'product' => $product
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error generating barcode: ' . $e->getMessage());
    }
}

// Export products
function exportProducts($db) {
    try {
        $query = "
            SELECT 
                p.name,
                p.sku,
                p.description,
                c.name as category,
                b.name as brand,
                s.name as supplier,
                p.cost_price,
                p.selling_price,
                p.wholesale_price,
                p.min_stock_level,
                COALESCE(i.quantity, 0) as current_stock,
                p.weight,
                p.dimensions,
                p.barcode,
                CASE WHEN p.expiry_tracking = 1 THEN 'Yes' ELSE 'No' END as expiry_tracking,
                p.shelf_life,
                p.alert_days,
                CASE WHEN p.is_active = 1 THEN 'Active' ELSE 'Inactive' END as status,
                p.created_at
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            LEFT JOIN inventory i ON p.id = i.product_id
            ORDER BY p.name ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate CSV
        $csv = "Name,SKU,Description,Category,Brand,Supplier,Cost Price,Selling Price,Wholesale Price,Min Stock Level,Current Stock,Weight,Dimensions,Barcode,Expiry Tracking,Shelf Life,Alert Days,Status,Created Date\n";
        
        foreach ($products as $product) {
            $csv .= '"' . implode('","', array_map(function($value) {
                return str_replace('"', '""', $value ?? '');
            }, array_values($product))) . '"' . "\n";
        }
        
        echo json_encode([
            'success' => true,
            'csv_data' => $csv,
            'filename' => 'products_export_' . date('Y-m-d') . '.csv'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error exporting products: ' . $e->getMessage());
    }
}

// Helper functions
function handleImageUpload($file) {
    $uploadDir = '../uploads/products/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid image type. Only JPG, PNG, and GIF are allowed.');
    }
    
    if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
        throw new Exception('Image size must be less than 2MB');
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to upload image');
    }
    
    return $filename;
}

function generateProductBarcode($productId) {
    // Generate EAN-13 compatible barcode
    $prefix = '299'; // Internal use prefix
    $productCode = str_pad($productId, 9, '0', STR_PAD_LEFT);
    $barcode = $prefix . $productCode;
    
    // Calculate check digit
    $checkDigit = calculateEAN13CheckDigit($barcode);
    
    return $barcode . $checkDigit;
}

function calculateEAN13CheckDigit($barcode) {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $digit = (int)$barcode[$i];
        $sum += ($i % 2 === 0) ? $digit : $digit * 3;
    }
    return (10 - ($sum % 10)) % 10;
}

function generateBarcodeImage($barcode) {
    // Simple barcode image generation (base64 encoded)
    // In production, use a proper barcode library like picqer/php-barcode-generator
    
    $width = 200;
    $height = 50;
    $image = imagecreate($width, $height);
    
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    
    imagefill($image, 0, 0, $white);
    
    // Simple bar pattern (not a real barcode, just for demo)
    $barWidth = 2;
    $x = 10;
    
    for ($i = 0; $i < strlen($barcode); $i++) {
        $digit = (int)$barcode[$i];
        if ($digit % 2 === 0) {
            imagefilledrectangle($image, $x, 5, $x + $barWidth, $height - 15, $black);
        }
        $x += $barWidth + 1;
    }
    
    // Add barcode text
    imagestring($image, 2, 10, $height - 15, $barcode, $black);
    
    ob_start();
    imagepng($image);
    $imageData = ob_get_contents();
    ob_end_clean();
    
    imagedestroy($image);
    
    return base64_encode($imageData);
}

function logActivity($db, $userId, $action, $description) {
    try {
        $query = "INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (:user_id, :action, :description, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':description', $description);
        $stmt->execute();
    } catch (Exception $e) {
        // Log error but don't throw exception
        error_log("Activity log error: " . $e->getMessage());
    }
}
?>

