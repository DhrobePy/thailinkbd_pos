<?php
/**
 * Schema-Compliant Products Add API
 * This properly matches the database schema
 * Replace your api/products/add.php with this
 */

// Start output buffering and clean any previous output
ob_start();
ob_clean();

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

try {
    // Include required files properly
    require_once '../../config/database.php';
    require_once '../../includes/auth.php';

    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $currentUser = $auth->getCurrentUser();
    $database = new Database();
    $db = $database->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No data provided']);
        exit;
    }

    // Start transaction
    $db->beginTransaction();

    // Sanitize and prepare data according to schema
    $name = isset($data['name']) ? trim($data['name']) : '';
    $description = isset($data['description']) ? trim($data['description']) : null;
    $sku = isset($data['sku']) ? trim($data['sku']) : '';
    $barcode = isset($data['barcode']) ? trim($data['barcode']) : null;
    $category_id = isset($data['category_id']) ? (int)$data['category_id'] : 0;
    
    // Handle brand_id - check if it's numeric (existing brand) or text (new brand name)
    $brand_id = null;
    if (isset($data['brand_id']) && !empty($data['brand_id'])) {
        $brand_input = trim($data['brand_id']);
        if (is_numeric($brand_input)) {
            $brand_id = (int)$brand_input;
        } else {
            // Create new brand if it doesn't exist
            $brandCheck = $db->prepare("SELECT id FROM brands WHERE name = ?");
            $brandCheck->execute([$brand_input]);
            $existingBrand = $brandCheck->fetch();
            
            if ($existingBrand) {
                $brand_id = $existingBrand['id'];
            } else {
                $brandInsert = $db->prepare("INSERT INTO brands (name, is_active, created_at, updated_at) VALUES (?, 1, NOW(), NOW())");
                $brandInsert->execute([$brand_input]);
                $brand_id = $db->lastInsertId();
            }
        }
    }
    
    $supplier_id = isset($data['supplier_id']) ? (int)$data['supplier_id'] : null;
    $cost_price = isset($data['cost_price']) ? (float)$data['cost_price'] : 0.00;
    $selling_price = isset($data['selling_price']) ? (float)$data['selling_price'] : 0.00;
    $wholesale_price = isset($data['wholesale_price']) ? (float)$data['wholesale_price'] : null;
    $weight = isset($data['weight']) ? (float)$data['weight'] : null;
    $dimensions = isset($data['dimensions']) ? trim($data['dimensions']) : null;
    $image = isset($data['image']) ? trim($data['image']) : null;
    $min_stock_level = isset($data['min_stock_level']) ? (int)$data['min_stock_level'] : 0;
    $max_stock_level = isset($data['max_stock_level']) ? (int)$data['max_stock_level'] : null;
    $reorder_point = isset($data['reorder_point']) ? (int)$data['reorder_point'] : 0;
    $initial_stock = isset($data['initial_stock']) ? (int)$data['initial_stock'] : 0;
    $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
    $track_inventory = isset($data['track_inventory']) ? (int)$data['track_inventory'] : 1;
    $expiry_tracking = isset($data['expiry_tracking']) ? (int)$data['expiry_tracking'] : 0;

    // Validation
    if (empty($name) || empty($sku) || $category_id <= 0 || $cost_price <= 0 || $selling_price <= 0) {
        throw new Exception('Missing or invalid required fields: name, sku, category_id, cost_price, selling_price');
    }

    // Check if SKU already exists
    $checkQuery = "SELECT id FROM products WHERE sku = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$sku]);
    if ($checkStmt->fetch()) {
        throw new Exception('SKU already exists');
    }

    // Check if barcode already exists (if provided)
    if ($barcode) {
        $barcodeCheck = $db->prepare("SELECT id FROM products WHERE barcode = ?");
        $barcodeCheck->execute([$barcode]);
        if ($barcodeCheck->fetch()) {
            throw new Exception('Barcode already exists');
        }
    }

    // Insert into products table (matching exact schema)
    $query = "INSERT INTO products (
        name, description, sku, barcode, category_id, brand_id, supplier_id,
        cost_price, selling_price, wholesale_price, weight, dimensions, image,
        min_stock_level, max_stock_level, reorder_point, is_active, has_variants,
        track_inventory, expiry_tracking, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        $name, $description, $sku, $barcode, $category_id, $brand_id, $supplier_id,
        $cost_price, $selling_price, $wholesale_price, $weight, $dimensions, $image,
        $min_stock_level, $max_stock_level, $reorder_point, $is_active, 0, // has_variants starts as 0
        $track_inventory, $expiry_tracking
    ]);

    if (!$result) {
        throw new Exception('Failed to insert product');
    }

    $productId = $db->lastInsertId();

    // Handle variants if provided (colors, sizes, scents)
    $hasVariants = false;
    $variantData = [
        'colors' => isset($data['colors']) ? $data['colors'] : '',
        'sizes' => isset($data['sizes']) ? $data['sizes'] : '',
        'scents' => isset($data['scents']) ? $data['scents'] : ''
    ];

    foreach ($variantData as $variantType => $variantValues) {
        if (!empty($variantValues)) {
            $values = array_map('trim', explode(',', $variantValues));
            foreach ($values as $value) {
                if (!empty($value)) {
                    $variantSku = $sku . '-' . strtoupper(substr($value, 0, 3)) . rand(10, 99);
                    
                    // Insert variant (matching exact schema)
                    $variantQuery = "INSERT INTO product_variants (
                        product_id, variant_name, sku, barcode, size, color, scent,
                        cost_price, selling_price, wholesale_price, weight, image,
                        is_active, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    
                    $variantStmt = $db->prepare($variantQuery);
                    $result = $variantStmt->execute([
                        $productId,
                        $value,
                        $variantSku,
                        $barcode ? $barcode . '-' . strtoupper(substr($value, 0, 2)) : null,
                        $variantType === 'sizes' ? $value : null,
                        $variantType === 'colors' ? $value : null,
                        $variantType === 'scents' ? $value : null,
                        $cost_price,
                        $selling_price,
                        $wholesale_price,
                        $weight,
                        $image,
                        $is_active
                    ]);
                    
                    if (!$result) {
                        throw new Exception('Failed to insert variant: ' . $value);
                    }
                    $hasVariants = true;
                }
            }
        }
    }

    // Update has_variants flag if variants were added
    if ($hasVariants) {
        $updateQuery = "UPDATE products SET has_variants = 1 WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$productId]);
    }

    // Insert initial inventory if quantity provided
    if ($initial_stock > 0 && $track_inventory) {
        $inventoryQuery = "INSERT INTO inventory (
            product_id, variant_id, quantity, reserved_quantity, 
            location, batch_number, expiry_date, last_updated
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $batchNumber = 'BATCH-' . date('Ymd') . '-' . $productId;
        $location = 'Main Warehouse';
        $expiryDate = $expiry_tracking && isset($data['expiry_date']) ? $data['expiry_date'] : null;
        
        $inventoryStmt = $db->prepare($inventoryQuery);
        $result = $inventoryStmt->execute([
            $productId, null, $initial_stock, 0, 
            $location, $batchNumber, $expiryDate
        ]);
        
        if (!$result) {
            throw new Exception('Failed to initialize inventory');
        }

        // Record inventory transaction
        $transactionQuery = "INSERT INTO inventory_transactions (
            product_id, variant_id, transaction_type, quantity, 
            reference_type, reference_id, notes, user_id, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $transactionStmt = $db->prepare($transactionQuery);
        $transactionStmt->execute([
            $productId, null, 'in', $initial_stock,
            'adjustment', null, 'Initial stock entry', $currentUser['id']
        ]);
    }

    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Product "' . htmlspecialchars($name) . '" has been added successfully!',
        'product_id' => $productId,
        'has_variants' => $hasVariants,
        'initial_stock' => $initial_stock
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>

