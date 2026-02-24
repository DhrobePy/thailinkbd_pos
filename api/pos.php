<?php
/**
 * POS (Point of Sale) API Endpoint
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
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'search_products':
                $term = sanitizeInput($_GET['term'] ?? '');
                
                if (strlen($term) < 2) {
                    sendJsonResponse(['products' => []]);
                }
                
                $query = "SELECT p.id, p.name, p.sku, p.barcode, p.selling_price, p.wholesale_price, p.has_variants,
                         c.name as category_name, b.name as brand_name,
                         COALESCE(SUM(i.quantity - i.reserved_quantity), 0) as available_stock
                         FROM products p
                         LEFT JOIN categories c ON p.category_id = c.id
                         LEFT JOIN brands b ON p.brand_id = b.id
                         LEFT JOIN inventory i ON p.id = i.product_id
                         WHERE p.is_active = 1 AND p.track_inventory = 1 
                         AND (p.name LIKE :term OR p.sku LIKE :term OR p.barcode LIKE :term)
                         GROUP BY p.id
                         HAVING available_stock > 0
                         ORDER BY p.name ASC
                         LIMIT 20";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':term', "%$term%");
                $stmt->execute();
                
                $products = $stmt->fetchAll();
                
                // Get variants for products that have them
                foreach ($products as &$product) {
                    if ($product['has_variants']) {
                        $variantQuery = "SELECT pv.*, COALESCE(SUM(i.quantity - i.reserved_quantity), 0) as available_stock
                                        FROM product_variants pv
                                        LEFT JOIN inventory i ON pv.id = i.variant_id
                                        WHERE pv.product_id = :product_id AND pv.is_active = 1
                                        GROUP BY pv.id
                                        HAVING available_stock > 0";
                        $variantStmt = $db->prepare($variantQuery);
                        $variantStmt->bindParam(':product_id', $product['id']);
                        $variantStmt->execute();
                        $product['variants'] = $variantStmt->fetchAll();
                    }
                }
                
                sendJsonResponse(['products' => $products]);
                break;
                
            case 'get_product':
                $productId = intval($_GET['product_id'] ?? 0);
                $variantId = intval($_GET['variant_id'] ?? 0) ?: null;
                
                if ($productId <= 0) {
                    sendJsonResponse(['error' => 'Invalid product ID'], 400);
                }
                
                $query = "SELECT p.*, c.name as category_name, b.name as brand_name,
                         COALESCE(SUM(i.quantity - i.reserved_quantity), 0) as available_stock
                         FROM products p
                         LEFT JOIN categories c ON p.category_id = c.id
                         LEFT JOIN brands b ON p.brand_id = b.id
                         LEFT JOIN inventory i ON p.id = i.product_id AND (i.variant_id = :variant_id OR (i.variant_id IS NULL AND :variant_id IS NULL))
                         WHERE p.id = :product_id AND p.is_active = 1
                         GROUP BY p.id";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':product_id', $productId);
                $stmt->bindParam(':variant_id', $variantId);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $product = $stmt->fetch();
                    
                    // Get variant details if specified
                    if ($variantId) {
                        $variantQuery = "SELECT * FROM product_variants WHERE id = :variant_id";
                        $variantStmt = $db->prepare($variantQuery);
                        $variantStmt->bindParam(':variant_id', $variantId);
                        $variantStmt->execute();
                        $product['variant'] = $variantStmt->fetch();
                    }
                    
                    sendJsonResponse(['product' => $product]);
                } else {
                    sendJsonResponse(['error' => 'Product not found or out of stock'], 404);
                }
                break;
                
            case 'customers':
                $search = sanitizeInput($_GET['search'] ?? '');
                
                $query = "SELECT * FROM customers WHERE is_active = 1";
                $params = [];
                
                if (!empty($search)) {
                    $query .= " AND (name LIKE :search OR phone LIKE :search OR email LIKE :search)";
                    $params[':search'] = "%$search%";
                }
                
                $query .= " ORDER BY name ASC LIMIT 20";
                
                $stmt = $db->prepare($query);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                
                sendJsonResponse(['customers' => $stmt->fetchAll()]);
                break;
                
            default:
                sendJsonResponse(['error' => 'Invalid action'], 400);
        }
        break;
        
    case 'POST':
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'process_sale':
                $requiredFields = ['items', 'payment_method', 'total_amount'];
                $missing = validateRequiredFields($data, $requiredFields);
                
                if (!empty($missing)) {
                    sendJsonResponse(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
                }
                
                $items = $data['items'];
                $customerId = intval($data['customer_id'] ?? 0) ?: null;
                $paymentMethod = sanitizeInput($data['payment_method']);
                $subtotal = floatval($data['subtotal'] ?? 0);
                $taxAmount = floatval($data['tax_amount'] ?? 0);
                $discountAmount = floatval($data['discount_amount'] ?? 0);
                $totalAmount = floatval($data['total_amount']);
                $paidAmount = floatval($data['paid_amount'] ?? $totalAmount);
                $changeAmount = floatval($data['change_amount'] ?? 0);
                $notes = sanitizeInput($data['notes'] ?? '');
                
                if (empty($items) || !is_array($items)) {
                    sendJsonResponse(['error' => 'No items in sale'], 400);
                }
                
                if ($totalAmount <= 0) {
                    sendJsonResponse(['error' => 'Invalid total amount'], 400);
                }
                
                try {
                    $db->beginTransaction();
                    
                    // Generate sale number
                    $saleNumber = generateSaleNumber();
                    
                    // Check if sale number already exists
                    $checkQuery = "SELECT id FROM sales WHERE sale_number = :sale_number";
                    $checkStmt = $db->prepare($checkQuery);
                    $checkStmt->bindParam(':sale_number', $saleNumber);
                    $checkStmt->execute();
                    
                    if ($checkStmt->rowCount() > 0) {
                        $saleNumber = generateSaleNumber() . '-' . rand(100, 999);
                    }
                    
                    // Determine payment status
                    $paymentStatus = 'paid';
                    if ($paidAmount < $totalAmount) {
                        $paymentStatus = $paidAmount > 0 ? 'partial' : 'pending';
                    }
                    
                    // Insert sale record
                    $saleQuery = "INSERT INTO sales (sale_number, customer_id, user_id, subtotal, tax_amount, 
                                 discount_amount, total_amount, paid_amount, change_amount, payment_method, 
                                 payment_status, notes) 
                                 VALUES (:sale_number, :customer_id, :user_id, :subtotal, :tax_amount, 
                                 :discount_amount, :total_amount, :paid_amount, :change_amount, :payment_method, 
                                 :payment_status, :notes)";
                    
                    $saleStmt = $db->prepare($saleQuery);
                    $user = $auth->getCurrentUser();
                    $saleStmt->bindParam(':sale_number', $saleNumber);
                    $saleStmt->bindParam(':customer_id', $customerId);
                    $saleStmt->bindParam(':user_id', $user['id']);
                    $saleStmt->bindParam(':subtotal', $subtotal);
                    $saleStmt->bindParam(':tax_amount', $taxAmount);
                    $saleStmt->bindParam(':discount_amount', $discountAmount);
                    $saleStmt->bindParam(':total_amount', $totalAmount);
                    $saleStmt->bindParam(':paid_amount', $paidAmount);
                    $saleStmt->bindParam(':change_amount', $changeAmount);
                    $saleStmt->bindParam(':payment_method', $paymentMethod);
                    $saleStmt->bindParam(':payment_status', $paymentStatus);
                    $saleStmt->bindParam(':notes', $notes);
                    
                    if (!$saleStmt->execute()) {
                        throw new Exception('Failed to create sale record');
                    }
                    
                    $saleId = $db->lastInsertId();
                    
                    // Process each item
                    foreach ($items as $item) {
                        $productId = intval($item['product_id']);
                        $variantId = intval($item['variant_id'] ?? 0) ?: null;
                        $quantity = intval($item['quantity']);
                        $unitPrice = floatval($item['unit_price']);
                        $itemDiscountAmount = floatval($item['discount_amount'] ?? 0);
                        $totalPrice = floatval($item['total_price']);
                        
                        if ($quantity <= 0) {
                            throw new Exception('Invalid quantity for item');
                        }
                        
                        // Check stock availability
                        $stockQuery = "SELECT COALESCE(SUM(quantity - reserved_quantity), 0) as available_stock
                                      FROM inventory 
                                      WHERE product_id = :product_id AND 
                                      (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL))";
                        $stockStmt = $db->prepare($stockQuery);
                        $stockStmt->bindParam(':product_id', $productId);
                        $stockStmt->bindParam(':variant_id', $variantId);
                        $stockStmt->execute();
                        $stockResult = $stockStmt->fetch();
                        
                        if ($stockResult['available_stock'] < $quantity) {
                            throw new Exception('Insufficient stock for product');
                        }
                        
                        // Insert sale item
                        $itemQuery = "INSERT INTO sale_items (sale_id, product_id, variant_id, quantity, 
                                     unit_price, discount_amount, total_price) 
                                     VALUES (:sale_id, :product_id, :variant_id, :quantity, :unit_price, 
                                     :discount_amount, :total_price)";
                        
                        $itemStmt = $db->prepare($itemQuery);
                        $itemStmt->bindParam(':sale_id', $saleId);
                        $itemStmt->bindParam(':product_id', $productId);
                        $itemStmt->bindParam(':variant_id', $variantId);
                        $itemStmt->bindParam(':quantity', $quantity);
                        $itemStmt->bindParam(':unit_price', $unitPrice);
                        $itemStmt->bindParam(':discount_amount', $itemDiscountAmount);
                        $itemStmt->bindParam(':total_price', $totalPrice);
                        
                        if (!$itemStmt->execute()) {
                            throw new Exception('Failed to create sale item');
                        }
                        
                        // Update inventory - reduce stock
                        $updateInventoryQuery = "UPDATE inventory 
                                               SET quantity = quantity - :quantity, last_updated = NOW()
                                               WHERE product_id = :product_id AND 
                                               (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL))
                                               AND quantity >= :quantity";
                        
                        $updateInventoryStmt = $db->prepare($updateInventoryQuery);
                        $updateInventoryStmt->bindParam(':quantity', $quantity);
                        $updateInventoryStmt->bindParam(':product_id', $productId);
                        $updateInventoryStmt->bindParam(':variant_id', $variantId);
                        
                        if (!$updateInventoryStmt->execute() || $updateInventoryStmt->rowCount() === 0) {
                            throw new Exception('Failed to update inventory');
                        }
                        
                        // Record inventory transaction
                        $transactionQuery = "INSERT INTO inventory_transactions (product_id, variant_id, 
                                           transaction_type, quantity, reference_type, reference_id, user_id) 
                                           VALUES (:product_id, :variant_id, 'out', :quantity, 'sale', :sale_id, :user_id)";
                        
                        $transactionStmt = $db->prepare($transactionQuery);
                        $transactionStmt->bindParam(':product_id', $productId);
                        $transactionStmt->bindParam(':variant_id', $variantId);
                        $transactionStmt->bindParam(':quantity', -$quantity);
                        $transactionStmt->bindParam(':sale_id', $saleId);
                        $transactionStmt->bindParam(':user_id', $user['id']);
                        $transactionStmt->execute();
                    }
                    
                    // Record payment if amount paid
                    if ($paidAmount > 0) {
                        $paymentQuery = "INSERT INTO payments (reference_type, reference_id, customer_id, 
                                        payment_method, amount, user_id) 
                                        VALUES ('sale', :sale_id, :customer_id, :payment_method, :amount, :user_id)";
                        
                        $paymentStmt = $db->prepare($paymentQuery);
                        $paymentStmt->bindParam(':sale_id', $saleId);
                        $paymentStmt->bindParam(':customer_id', $customerId);
                        $paymentStmt->bindParam(':payment_method', $paymentMethod);
                        $paymentStmt->bindParam(':amount', $paidAmount);
                        $paymentStmt->bindParam(':user_id', $user['id']);
                        $paymentStmt->execute();
                    }
                    
                    $db->commit();
                    
                    logActivity($user['id'], 'CREATE', 'sales', $saleId, null, $data);
                    
                    sendJsonResponse([
                        'success' => true, 
                        'sale_id' => $saleId,
                        'sale_number' => $saleNumber,
                        'message' => 'Sale processed successfully'
                    ]);
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    error_log("Process sale error: " . $e->getMessage());
                    sendJsonResponse(['error' => $e->getMessage()], 500);
                }
                break;
                
            case 'add_customer':
                $auth->requireAuth('staff');
                
                $requiredFields = ['name'];
                $missing = validateRequiredFields($data, $requiredFields);
                
                if (!empty($missing)) {
                    sendJsonResponse(['error' => 'Customer name is required'], 400);
                }
                
                try {
                    $query = "INSERT INTO customers (name, email, phone, address, customer_type) 
                             VALUES (:name, :email, :phone, :address, :customer_type)";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':name', sanitizeInput($data['name']));
                    $stmt->bindParam(':email', sanitizeInput($data['email'] ?? ''));
                    $stmt->bindParam(':phone', sanitizeInput($data['phone'] ?? ''));
                    $stmt->bindParam(':address', sanitizeInput($data['address'] ?? ''));
                    $stmt->bindParam(':customer_type', sanitizeInput($data['customer_type'] ?? 'retail'));
                    
                    if ($stmt->execute()) {
                        $customerId = $db->lastInsertId();
                        
                        $user = $auth->getCurrentUser();
                        logActivity($user['id'], 'CREATE', 'customers', $customerId, null, $data);
                        
                        sendJsonResponse([
                            'success' => true, 
                            'customer_id' => $customerId,
                            'message' => 'Customer added successfully'
                        ]);
                    } else {
                        sendJsonResponse(['error' => 'Failed to add customer'], 500);
                    }
                } catch (Exception $e) {
                    error_log("Add customer error: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to add customer'], 500);
                }
                break;
                
            default:
                sendJsonResponse(['error' => 'Invalid action'], 400);
        }
        break;
        
    default:
        sendJsonResponse(['error' => 'Method not allowed'], 405);
}
?>

