<?php
/**
 * Inventory API Endpoint
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
                $lowStock = intval($_GET['low_stock'] ?? 0);
                
                $query = "SELECT p.id, p.name, p.sku, p.barcode, p.min_stock_level, p.reorder_point,
                         c.name as category_name, b.name as brand_name,
                         COALESCE(SUM(i.quantity), 0) as total_stock,
                         COALESCE(SUM(i.reserved_quantity), 0) as reserved_stock,
                         (COALESCE(SUM(i.quantity), 0) - COALESCE(SUM(i.reserved_quantity), 0)) as available_stock
                         FROM products p
                         LEFT JOIN categories c ON p.category_id = c.id
                         LEFT JOIN brands b ON p.brand_id = b.id
                         LEFT JOIN inventory i ON p.id = i.product_id
                         WHERE p.is_active = 1 AND p.track_inventory = 1";
                
                $params = [];
                
                if (!empty($search)) {
                    $query .= " AND (p.name LIKE :search OR p.sku LIKE :search OR p.barcode LIKE :search)";
                    $params[':search'] = "%$search%";
                }
                
                $query .= " GROUP BY p.id";
                
                if ($lowStock) {
                    $query .= " HAVING total_stock <= p.min_stock_level";
                }
                
                $query .= " ORDER BY p.name ASC";
                
                $result = paginate($query, $params, $page);
                sendJsonResponse($result);
                break;
                
            case 'product':
                $productId = intval($_GET['product_id'] ?? 0);
                
                if ($productId <= 0) {
                    sendJsonResponse(['error' => 'Invalid product ID'], 400);
                }
                
                $query = "SELECT i.*, p.name as product_name, p.sku, pv.variant_name
                         FROM inventory i
                         LEFT JOIN products p ON i.product_id = p.id
                         LEFT JOIN product_variants pv ON i.variant_id = pv.id
                         WHERE i.product_id = :product_id
                         ORDER BY i.location, pv.variant_name";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':product_id', $productId);
                $stmt->execute();
                
                sendJsonResponse(['inventory' => $stmt->fetchAll()]);
                break;
                
            case 'transactions':
                $page = intval($_GET['page'] ?? 1);
                $productId = intval($_GET['product_id'] ?? 0);
                $type = sanitizeInput($_GET['type'] ?? '');
                
                $query = "SELECT it.*, p.name as product_name, p.sku, pv.variant_name, u.full_name as user_name
                         FROM inventory_transactions it
                         LEFT JOIN products p ON it.product_id = p.id
                         LEFT JOIN product_variants pv ON it.variant_id = pv.id
                         LEFT JOIN users u ON it.user_id = u.id
                         WHERE 1=1";
                
                $params = [];
                
                if ($productId > 0) {
                    $query .= " AND it.product_id = :product_id";
                    $params[':product_id'] = $productId;
                }
                
                if (!empty($type)) {
                    $query .= " AND it.transaction_type = :type";
                    $params[':type'] = $type;
                }
                
                $query .= " ORDER BY it.created_at DESC";
                
                $result = paginate($query, $params, $page);
                sendJsonResponse($result);
                break;
                
            case 'low_stock':
                $query = "SELECT p.id, p.name, p.sku, p.min_stock_level, p.reorder_point,
                         COALESCE(SUM(i.quantity), 0) as current_stock
                         FROM products p
                         LEFT JOIN inventory i ON p.id = i.product_id
                         WHERE p.is_active = 1 AND p.track_inventory = 1
                         GROUP BY p.id
                         HAVING current_stock <= p.min_stock_level
                         ORDER BY current_stock ASC";
                
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                sendJsonResponse(['products' => $stmt->fetchAll()]);
                break;
                
            default:
                sendJsonResponse(['error' => 'Invalid action'], 400);
        }
        break;
        
    case 'POST':
        $auth->requireAuth('staff');
        
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'adjust':
                $requiredFields = ['product_id', 'adjustment_type', 'quantity'];
                $missing = validateRequiredFields($data, $requiredFields);
                
                if (!empty($missing)) {
                    sendJsonResponse(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
                }
                
                $productId = intval($data['product_id']);
                $variantId = intval($data['variant_id'] ?? 0) ?: null;
                $adjustmentType = sanitizeInput($data['adjustment_type']); // 'in', 'out', 'adjustment'
                $quantity = intval($data['quantity']);
                $location = sanitizeInput($data['location'] ?? 'Main Store');
                $notes = sanitizeInput($data['notes'] ?? '');
                $batchNumber = sanitizeInput($data['batch_number'] ?? '');
                $expiryDate = !empty($data['expiry_date']) ? $data['expiry_date'] : null;
                
                if ($quantity <= 0) {
                    sendJsonResponse(['error' => 'Quantity must be greater than 0'], 400);
                }
                
                try {
                    $db->beginTransaction();
                    
                    // Get or create inventory record
                    $inventoryQuery = "SELECT * FROM inventory WHERE product_id = :product_id AND 
                                      (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL)) AND 
                                      location = :location";
                    $inventoryStmt = $db->prepare($inventoryQuery);
                    $inventoryStmt->bindParam(':product_id', $productId);
                    $inventoryStmt->bindParam(':variant_id', $variantId);
                    $inventoryStmt->bindParam(':location', $location);
                    $inventoryStmt->execute();
                    
                    if ($inventoryStmt->rowCount() > 0) {
                        $inventory = $inventoryStmt->fetch();
                        $currentQuantity = $inventory['quantity'];
                        
                        // Calculate new quantity
                        if ($adjustmentType === 'in') {
                            $newQuantity = $currentQuantity + $quantity;
                        } elseif ($adjustmentType === 'out') {
                            $newQuantity = $currentQuantity - $quantity;
                            if ($newQuantity < 0) {
                                $db->rollBack();
                                sendJsonResponse(['error' => 'Insufficient stock'], 400);
                            }
                        } else { // adjustment
                            $newQuantity = $quantity;
                        }
                        
                        // Update inventory
                        $updateQuery = "UPDATE inventory SET quantity = :quantity, batch_number = :batch_number, 
                                       expiry_date = :expiry_date, last_updated = NOW() WHERE id = :id";
                        $updateStmt = $db->prepare($updateQuery);
                        $updateStmt->bindParam(':quantity', $newQuantity);
                        $updateStmt->bindParam(':batch_number', $batchNumber);
                        $updateStmt->bindParam(':expiry_date', $expiryDate);
                        $updateStmt->bindParam(':id', $inventory['id']);
                        $updateStmt->execute();
                    } else {
                        // Create new inventory record
                        if ($adjustmentType === 'out') {
                            $db->rollBack();
                            sendJsonResponse(['error' => 'No stock available'], 400);
                        }
                        
                        $newQuantity = ($adjustmentType === 'in') ? $quantity : $quantity;
                        
                        $insertQuery = "INSERT INTO inventory (product_id, variant_id, quantity, location, batch_number, expiry_date) 
                                       VALUES (:product_id, :variant_id, :quantity, :location, :batch_number, :expiry_date)";
                        $insertStmt = $db->prepare($insertQuery);
                        $insertStmt->bindParam(':product_id', $productId);
                        $insertStmt->bindParam(':variant_id', $variantId);
                        $insertStmt->bindParam(':quantity', $newQuantity);
                        $insertStmt->bindParam(':location', $location);
                        $insertStmt->bindParam(':batch_number', $batchNumber);
                        $insertStmt->bindParam(':expiry_date', $expiryDate);
                        $insertStmt->execute();
                    }
                    
                    // Record transaction
                    $transactionQuantity = ($adjustmentType === 'out') ? -$quantity : $quantity;
                    
                    $transactionQuery = "INSERT INTO inventory_transactions (product_id, variant_id, transaction_type, 
                                        quantity, reference_type, notes, user_id) 
                                        VALUES (:product_id, :variant_id, :transaction_type, :quantity, :reference_type, :notes, :user_id)";
                    $transactionStmt = $db->prepare($transactionQuery);
                    $transactionStmt->bindParam(':product_id', $productId);
                    $transactionStmt->bindParam(':variant_id', $variantId);
                    $transactionStmt->bindParam(':transaction_type', $adjustmentType);
                    $transactionStmt->bindParam(':quantity', $transactionQuantity);
                    $transactionStmt->bindParam(':reference_type', $adjustmentType);
                    $transactionStmt->bindParam(':notes', $notes);
                    
                    $user = $auth->getCurrentUser();
                    $transactionStmt->bindParam(':user_id', $user['id']);
                    $transactionStmt->execute();
                    
                    $db->commit();
                    
                    logActivity($user['id'], 'INVENTORY_ADJUST', 'inventory', $productId, null, $data);
                    
                    sendJsonResponse(['success' => true, 'message' => 'Inventory adjusted successfully']);
                } catch (Exception $e) {
                    $db->rollBack();
                    error_log("Inventory adjustment error: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to adjust inventory'], 500);
                }
                break;
                
            case 'transfer':
                $auth->requireAuth('manager');
                
                $requiredFields = ['product_id', 'from_location', 'to_location', 'quantity'];
                $missing = validateRequiredFields($data, $requiredFields);
                
                if (!empty($missing)) {
                    sendJsonResponse(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
                }
                
                $productId = intval($data['product_id']);
                $variantId = intval($data['variant_id'] ?? 0) ?: null;
                $fromLocation = sanitizeInput($data['from_location']);
                $toLocation = sanitizeInput($data['to_location']);
                $quantity = intval($data['quantity']);
                $notes = sanitizeInput($data['notes'] ?? '');
                
                if ($quantity <= 0) {
                    sendJsonResponse(['error' => 'Quantity must be greater than 0'], 400);
                }
                
                if ($fromLocation === $toLocation) {
                    sendJsonResponse(['error' => 'Source and destination locations cannot be the same'], 400);
                }
                
                try {
                    $db->beginTransaction();
                    
                    // Check source inventory
                    $sourceQuery = "SELECT * FROM inventory WHERE product_id = :product_id AND 
                                   (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL)) AND 
                                   location = :location";
                    $sourceStmt = $db->prepare($sourceQuery);
                    $sourceStmt->bindParam(':product_id', $productId);
                    $sourceStmt->bindParam(':variant_id', $variantId);
                    $sourceStmt->bindParam(':location', $fromLocation);
                    $sourceStmt->execute();
                    
                    if ($sourceStmt->rowCount() === 0) {
                        $db->rollBack();
                        sendJsonResponse(['error' => 'No stock found at source location'], 400);
                    }
                    
                    $sourceInventory = $sourceStmt->fetch();
                    
                    if ($sourceInventory['quantity'] < $quantity) {
                        $db->rollBack();
                        sendJsonResponse(['error' => 'Insufficient stock at source location'], 400);
                    }
                    
                    // Update source inventory
                    $newSourceQuantity = $sourceInventory['quantity'] - $quantity;
                    $updateSourceQuery = "UPDATE inventory SET quantity = :quantity, last_updated = NOW() WHERE id = :id";
                    $updateSourceStmt = $db->prepare($updateSourceQuery);
                    $updateSourceStmt->bindParam(':quantity', $newSourceQuantity);
                    $updateSourceStmt->bindParam(':id', $sourceInventory['id']);
                    $updateSourceStmt->execute();
                    
                    // Check destination inventory
                    $destQuery = "SELECT * FROM inventory WHERE product_id = :product_id AND 
                                 (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL)) AND 
                                 location = :location";
                    $destStmt = $db->prepare($destQuery);
                    $destStmt->bindParam(':product_id', $productId);
                    $destStmt->bindParam(':variant_id', $variantId);
                    $destStmt->bindParam(':location', $toLocation);
                    $destStmt->execute();
                    
                    if ($destStmt->rowCount() > 0) {
                        // Update existing destination inventory
                        $destInventory = $destStmt->fetch();
                        $newDestQuantity = $destInventory['quantity'] + $quantity;
                        
                        $updateDestQuery = "UPDATE inventory SET quantity = :quantity, last_updated = NOW() WHERE id = :id";
                        $updateDestStmt = $db->prepare($updateDestQuery);
                        $updateDestStmt->bindParam(':quantity', $newDestQuantity);
                        $updateDestStmt->bindParam(':id', $destInventory['id']);
                        $updateDestStmt->execute();
                    } else {
                        // Create new destination inventory
                        $insertDestQuery = "INSERT INTO inventory (product_id, variant_id, quantity, location) 
                                           VALUES (:product_id, :variant_id, :quantity, :location)";
                        $insertDestStmt = $db->prepare($insertDestQuery);
                        $insertDestStmt->bindParam(':product_id', $productId);
                        $insertDestStmt->bindParam(':variant_id', $variantId);
                        $insertDestStmt->bindParam(':quantity', $quantity);
                        $insertDestStmt->bindParam(':location', $toLocation);
                        $insertDestStmt->execute();
                    }
                    
                    // Record transactions
                    $user = $auth->getCurrentUser();
                    
                    // Out transaction for source
                    $outTransactionQuery = "INSERT INTO inventory_transactions (product_id, variant_id, transaction_type, 
                                           quantity, reference_type, notes, user_id) 
                                           VALUES (:product_id, :variant_id, 'out', :quantity, 'transfer', :notes, :user_id)";
                    $outTransactionStmt = $db->prepare($outTransactionQuery);
                    $outTransactionStmt->bindParam(':product_id', $productId);
                    $outTransactionStmt->bindParam(':variant_id', $variantId);
                    $outTransactionStmt->bindParam(':quantity', -$quantity);
                    $outTransactionStmt->bindParam(':notes', "Transfer to $toLocation: $notes");
                    $outTransactionStmt->bindParam(':user_id', $user['id']);
                    $outTransactionStmt->execute();
                    
                    // In transaction for destination
                    $inTransactionQuery = "INSERT INTO inventory_transactions (product_id, variant_id, transaction_type, 
                                          quantity, reference_type, notes, user_id) 
                                          VALUES (:product_id, :variant_id, 'in', :quantity, 'transfer', :notes, :user_id)";
                    $inTransactionStmt = $db->prepare($inTransactionQuery);
                    $inTransactionStmt->bindParam(':product_id', $productId);
                    $inTransactionStmt->bindParam(':variant_id', $variantId);
                    $inTransactionStmt->bindParam(':quantity', $quantity);
                    $inTransactionStmt->bindParam(':notes', "Transfer from $fromLocation: $notes");
                    $inTransactionStmt->bindParam(':user_id', $user['id']);
                    $inTransactionStmt->execute();
                    
                    $db->commit();
                    
                    logActivity($user['id'], 'INVENTORY_TRANSFER', 'inventory', $productId, null, $data);
                    
                    sendJsonResponse(['success' => true, 'message' => 'Inventory transferred successfully']);
                } catch (Exception $e) {
                    $db->rollBack();
                    error_log("Inventory transfer error: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to transfer inventory'], 500);
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

