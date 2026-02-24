<?php
/**
 * Products API Endpoint
 */

require_once '../config/config.php';

$auth = new Auth();
$auth->requireAuth();

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$data = getRequestData();

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                $page = intval($_GET['page'] ?? 1);
                $search = sanitizeInput($_GET['search'] ?? '');
                $category = intval($_GET['category'] ?? 0);
                $brand = intval($_GET['brand'] ?? 0);
                
                $query = "SELECT p.*, c.name as category_name, b.name as brand_name, s.name as supplier_name,
                         COALESCE(SUM(i.quantity), 0) as total_stock
                         FROM products p
                         LEFT JOIN categories c ON p.category_id = c.id
                         LEFT JOIN brands b ON p.brand_id = b.id
                         LEFT JOIN suppliers s ON p.supplier_id = s.id
                         LEFT JOIN inventory i ON p.id = i.product_id
                         WHERE p.is_active = 1";
                
                $params = [];
                
                if (!empty($search)) {
                    $query .= " AND (p.name LIKE :search OR p.sku LIKE :search OR p.barcode LIKE :search)";
                    $params[':search'] = "%$search%";
                }
                
                if ($category > 0) {
                    $query .= " AND p.category_id = :category";
                    $params[':category'] = $category;
                }
                
                if ($brand > 0) {
                    $query .= " AND p.brand_id = :brand";
                    $params[':brand'] = $brand;
                }
                
                $query .= " GROUP BY p.id ORDER BY p.name ASC";
                
                $result = paginate($query, $params, $page);
                sendJsonResponse($result);
                break;
                
            case 'get':
                $id = intval($_GET['id'] ?? 0);
                
                if ($id <= 0) {
                    sendJsonResponse(['error' => 'Invalid product ID'], 400);
                }
                
                $query = "SELECT p.*, c.name as category_name, b.name as brand_name, s.name as supplier_name
                         FROM products p
                         LEFT JOIN categories c ON p.category_id = c.id
                         LEFT JOIN brands b ON p.brand_id = b.id
                         LEFT JOIN suppliers s ON p.supplier_id = s.id
                         WHERE p.id = :id";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $product = $stmt->fetch();
                    
                    // Get variants if product has variants
                    if ($product['has_variants']) {
                        $variantQuery = "SELECT * FROM product_variants WHERE product_id = :product_id AND is_active = 1";
                        $variantStmt = $db->prepare($variantQuery);
                        $variantStmt->bindParam(':product_id', $id);
                        $variantStmt->execute();
                        $product['variants'] = $variantStmt->fetchAll();
                    }
                    
                    // Get inventory
                    $inventoryQuery = "SELECT * FROM inventory WHERE product_id = :product_id";
                    $inventoryStmt = $db->prepare($inventoryQuery);
                    $inventoryStmt->bindParam(':product_id', $id);
                    $inventoryStmt->execute();
                    $product['inventory'] = $inventoryStmt->fetchAll();
                    
                    sendJsonResponse(['product' => $product]);
                } else {
                    sendJsonResponse(['error' => 'Product not found'], 404);
                }
                break;
                
            case 'categories':
                $query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                sendJsonResponse(['categories' => $stmt->fetchAll()]);
                break;
                
            case 'brands':
                $query = "SELECT * FROM brands WHERE is_active = 1 ORDER BY name ASC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                sendJsonResponse(['brands' => $stmt->fetchAll()]);
                break;
                
            case 'suppliers':
                $query = "SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name ASC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                sendJsonResponse(['suppliers' => $stmt->fetchAll()]);
                break;
                
            case 'search':
                $term = sanitizeInput($_GET['term'] ?? '');
                
                if (strlen($term) < 2) {
                    sendJsonResponse(['products' => []]);
                }
                
                $query = "SELECT p.id, p.name, p.sku, p.barcode, p.selling_price, 
                         COALESCE(SUM(i.quantity), 0) as stock
                         FROM products p
                         LEFT JOIN inventory i ON p.id = i.product_id
                         WHERE p.is_active = 1 AND (p.name LIKE :term OR p.sku LIKE :term OR p.barcode LIKE :term)
                         GROUP BY p.id
                         ORDER BY p.name ASC
                         LIMIT 20";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':term', "%$term%");
                $stmt->execute();
                
                sendJsonResponse(['products' => $stmt->fetchAll()]);
                break;
                
            default:
                sendJsonResponse(['error' => 'Invalid action'], 400);
        }
        break;
        
    case 'POST':
        $auth->requireAuth('staff');
        
        $requiredFields = ['name', 'category_id', 'cost_price', 'selling_price'];
        $missing = validateRequiredFields($data, $requiredFields);
        
        if (!empty($missing)) {
            sendJsonResponse(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
        }
        
        try {
            $db->beginTransaction();
            
            // Generate SKU if not provided
            $sku = !empty($data['sku']) ? sanitizeInput($data['sku']) : generateSKU();
            
            // Check if SKU already exists
            $checkQuery = "SELECT id FROM products WHERE sku = :sku";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':sku', $sku);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $db->rollBack();
                sendJsonResponse(['error' => 'SKU already exists'], 400);
            }
            
            $query = "INSERT INTO products (name, description, sku, barcode, category_id, brand_id, supplier_id, 
                     cost_price, selling_price, wholesale_price, weight, dimensions, min_stock_level, 
                     max_stock_level, reorder_point, has_variants, track_inventory, expiry_tracking) 
                     VALUES (:name, :description, :sku, :barcode, :category_id, :brand_id, :supplier_id, 
                     :cost_price, :selling_price, :wholesale_price, :weight, :dimensions, :min_stock_level, 
                     :max_stock_level, :reorder_point, :has_variants, :track_inventory, :expiry_tracking)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', sanitizeInput($data['name']));
            $stmt->bindParam(':description', sanitizeInput($data['description'] ?? ''));
            $stmt->bindParam(':sku', $sku);
            $stmt->bindParam(':barcode', sanitizeInput($data['barcode'] ?? ''));
            $stmt->bindParam(':category_id', intval($data['category_id']));
            $stmt->bindParam(':brand_id', intval($data['brand_id'] ?? 0) ?: null);
            $stmt->bindParam(':supplier_id', intval($data['supplier_id'] ?? 0) ?: null);
            $stmt->bindParam(':cost_price', floatval($data['cost_price']));
            $stmt->bindParam(':selling_price', floatval($data['selling_price']));
            $stmt->bindParam(':wholesale_price', floatval($data['wholesale_price'] ?? 0) ?: null);
            $stmt->bindParam(':weight', floatval($data['weight'] ?? 0) ?: null);
            $stmt->bindParam(':dimensions', sanitizeInput($data['dimensions'] ?? ''));
            $stmt->bindParam(':min_stock_level', intval($data['min_stock_level'] ?? 0));
            $stmt->bindParam(':max_stock_level', intval($data['max_stock_level'] ?? 0) ?: null);
            $stmt->bindParam(':reorder_point', intval($data['reorder_point'] ?? 0));
            $stmt->bindParam(':has_variants', intval($data['has_variants'] ?? 0));
            $stmt->bindParam(':track_inventory', intval($data['track_inventory'] ?? 1));
            $stmt->bindParam(':expiry_tracking', intval($data['expiry_tracking'] ?? 0));
            
            if ($stmt->execute()) {
                $productId = $db->lastInsertId();
                
                // Create initial inventory record if tracking inventory
                if (intval($data['track_inventory'] ?? 1)) {
                    $inventoryQuery = "INSERT INTO inventory (product_id, quantity, location) VALUES (:product_id, 0, 'Main Store')";
                    $inventoryStmt = $db->prepare($inventoryQuery);
                    $inventoryStmt->bindParam(':product_id', $productId);
                    $inventoryStmt->execute();
                }
                
                // Generate barcode if not provided
                if (empty($data['barcode'])) {
                    $barcode = generateEAN13();
                    $barcodeQuery = "INSERT INTO barcodes (product_id, barcode) VALUES (:product_id, :barcode)";
                    $barcodeStmt = $db->prepare($barcodeQuery);
                    $barcodeStmt->bindParam(':product_id', $productId);
                    $barcodeStmt->bindParam(':barcode', $barcode);
                    $barcodeStmt->execute();
                    
                    // Update product with generated barcode
                    $updateQuery = "UPDATE products SET barcode = :barcode WHERE id = :id";
                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->bindParam(':barcode', $barcode);
                    $updateStmt->bindParam(':id', $productId);
                    $updateStmt->execute();
                }
                
                $db->commit();
                
                $user = $auth->getCurrentUser();
                logActivity($user['id'], 'CREATE', 'products', $productId, null, $data);
                
                sendJsonResponse(['success' => true, 'product_id' => $productId, 'message' => 'Product created successfully']);
            } else {
                $db->rollBack();
                sendJsonResponse(['error' => 'Failed to create product'], 500);
            }
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Create product error: " . $e->getMessage());
            sendJsonResponse(['error' => 'Failed to create product'], 500);
        }
        break;
        
    case 'PUT':
        $auth->requireAuth('staff');
        
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            sendJsonResponse(['error' => 'Invalid product ID'], 400);
        }
        
        try {
            // Get current product data for logging
            $currentQuery = "SELECT * FROM products WHERE id = :id";
            $currentStmt = $db->prepare($currentQuery);
            $currentStmt->bindParam(':id', $id);
            $currentStmt->execute();
            $currentData = $currentStmt->fetch();
            
            if (!$currentData) {
                sendJsonResponse(['error' => 'Product not found'], 404);
            }
            
            $query = "UPDATE products SET name = :name, description = :description, category_id = :category_id, 
                     brand_id = :brand_id, supplier_id = :supplier_id, cost_price = :cost_price, 
                     selling_price = :selling_price, wholesale_price = :wholesale_price, weight = :weight, 
                     dimensions = :dimensions, min_stock_level = :min_stock_level, max_stock_level = :max_stock_level, 
                     reorder_point = :reorder_point, has_variants = :has_variants, track_inventory = :track_inventory, 
                     expiry_tracking = :expiry_tracking, updated_at = NOW() WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', sanitizeInput($data['name'] ?? $currentData['name']));
            $stmt->bindParam(':description', sanitizeInput($data['description'] ?? $currentData['description']));
            $stmt->bindParam(':category_id', intval($data['category_id'] ?? $currentData['category_id']));
            $stmt->bindParam(':brand_id', intval($data['brand_id'] ?? $currentData['brand_id']) ?: null);
            $stmt->bindParam(':supplier_id', intval($data['supplier_id'] ?? $currentData['supplier_id']) ?: null);
            $stmt->bindParam(':cost_price', floatval($data['cost_price'] ?? $currentData['cost_price']));
            $stmt->bindParam(':selling_price', floatval($data['selling_price'] ?? $currentData['selling_price']));
            $stmt->bindParam(':wholesale_price', floatval($data['wholesale_price'] ?? $currentData['wholesale_price']) ?: null);
            $stmt->bindParam(':weight', floatval($data['weight'] ?? $currentData['weight']) ?: null);
            $stmt->bindParam(':dimensions', sanitizeInput($data['dimensions'] ?? $currentData['dimensions']));
            $stmt->bindParam(':min_stock_level', intval($data['min_stock_level'] ?? $currentData['min_stock_level']));
            $stmt->bindParam(':max_stock_level', intval($data['max_stock_level'] ?? $currentData['max_stock_level']) ?: null);
            $stmt->bindParam(':reorder_point', intval($data['reorder_point'] ?? $currentData['reorder_point']));
            $stmt->bindParam(':has_variants', intval($data['has_variants'] ?? $currentData['has_variants']));
            $stmt->bindParam(':track_inventory', intval($data['track_inventory'] ?? $currentData['track_inventory']));
            $stmt->bindParam(':expiry_tracking', intval($data['expiry_tracking'] ?? $currentData['expiry_tracking']));
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $user = $auth->getCurrentUser();
                logActivity($user['id'], 'UPDATE', 'products', $id, $currentData, $data);
                
                sendJsonResponse(['success' => true, 'message' => 'Product updated successfully']);
            } else {
                sendJsonResponse(['error' => 'Failed to update product'], 500);
            }
        } catch (Exception $e) {
            error_log("Update product error: " . $e->getMessage());
            sendJsonResponse(['error' => 'Failed to update product'], 500);
        }
        break;
        
    case 'DELETE':
        $auth->requireAuth('manager');
        
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            sendJsonResponse(['error' => 'Invalid product ID'], 400);
        }
        
        try {
            $query = "UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $user = $auth->getCurrentUser();
                logActivity($user['id'], 'DELETE', 'products', $id);
                
                sendJsonResponse(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                sendJsonResponse(['error' => 'Failed to delete product'], 500);
            }
        } catch (Exception $e) {
            error_log("Delete product error: " . $e->getMessage());
            sendJsonResponse(['error' => 'Failed to delete product'], 500);
        }
        break;
        
    default:
        sendJsonResponse(['error' => 'Method not allowed'], 405);
}
?>

