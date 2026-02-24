<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once '../config/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'lookup':
            handleBarcodeLookup($db, $input['barcode']);
            break;
            
        case 'transaction':
            handleInventoryTransaction($db, $input, $user);
            break;
            
        case 'recent_transactions':
            handleRecentTransactions($db);
            break;
            
        case 'generate':
            handleBarcodeGeneration($db, $input);
            break;
            
        case 'print':
            handleBarcodePrint($db, $input);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Barcode API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'error' => $e->getMessage()
    ]);
}

function handleBarcodeLookup($db, $barcode) {
    try {
        // First check in barcodes table
        $stmt = $db->prepare("
            SELECT b.*, p.name, p.sku, p.cost_price, p.selling_price, p.wholesale_price,
                   p.brand_id, p.category_id, p.is_active,
                   br.name as brand_name, c.name as category_name,
                   pv.variant_name, pv.size, pv.color, pv.scent,
                   SUM(i.quantity) as current_stock
            FROM barcodes b
            JOIN products p ON b.product_id = p.id
            LEFT JOIN brands br ON p.brand_id = br.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_variants pv ON b.variant_id = pv.id
            LEFT JOIN inventory i ON p.id = i.product_id AND (b.variant_id IS NULL OR i.variant_id = b.variant_id)
            WHERE b.barcode = ? AND p.is_active = 1
            GROUP BY b.id, p.id, pv.id
        ");
        $stmt->execute([$barcode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'product' => $result
            ]);
            return;
        }
        
        // If not found in barcodes table, check products table barcode field
        $stmt = $db->prepare("
            SELECT p.*, p.id as product_id, NULL as variant_id, NULL as variant_name,
                   br.name as brand_name, c.name as category_name,
                   SUM(i.quantity) as current_stock
            FROM products p
            LEFT JOIN brands br ON p.brand_id = br.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN inventory i ON p.id = i.product_id
            WHERE p.barcode = ? AND p.is_active = 1
            GROUP BY p.id
        ");
        $stmt->execute([$barcode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'product' => $result
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Product not found for barcode: ' . $barcode
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error looking up barcode: ' . $e->getMessage()
        ]);
    }
}

function handleInventoryTransaction($db, $data, $user) {
    try {
        $db->beginTransaction();
        
        $product_id = $data['product_id'];
        $variant_id = $data['variant_id'] ?? null;
        $transaction_type = $data['transaction_type']; // in, out, adjustment, transfer
        $quantity = (int)$data['quantity'];
        $location = $data['location'] ?? 'Main Store';
        $batch_number = $data['batch_number'] ?? null;
        $expiry_date = $data['expiry_date'] ?? null;
        $reference_type = $data['reference_type'] ?? null;
        $reference_id = $data['reference_id'] ?? null;
        $notes = $data['notes'] ?? null;
        
        if ($quantity <= 0) {
            throw new Exception('Quantity must be greater than 0');
        }
        
        // Check if inventory record exists
        $stmt = $db->prepare("
            SELECT * FROM inventory 
            WHERE product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))
            AND location = ?
        ");
        $stmt->execute([$product_id, $variant_id, $variant_id, $location]);
        $inventory = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $current_quantity = $inventory ? $inventory['quantity'] : 0;
        $new_quantity = $current_quantity;
        
        // Calculate new quantity based on transaction type
        switch ($transaction_type) {
            case 'in':
                $new_quantity = $current_quantity + $quantity;
                break;
            case 'out':
                if ($current_quantity < $quantity) {
                    throw new Exception('Insufficient stock. Available: ' . $current_quantity);
                }
                $new_quantity = $current_quantity - $quantity;
                break;
            case 'adjustment':
                $new_quantity = $quantity; // Set to exact quantity
                break;
            case 'transfer':
                // For transfer, this would be the 'out' part
                if ($current_quantity < $quantity) {
                    throw new Exception('Insufficient stock for transfer. Available: ' . $current_quantity);
                }
                $new_quantity = $current_quantity - $quantity;
                break;
        }
        
        // Update or insert inventory record
        if ($inventory) {
            $stmt = $db->prepare("
                UPDATE inventory 
                SET quantity = ?, 
                    batch_number = COALESCE(?, batch_number),
                    expiry_date = COALESCE(?, expiry_date),
                    last_updated = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$new_quantity, $batch_number, $expiry_date, $inventory['id']]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO inventory (product_id, variant_id, quantity, location, batch_number, expiry_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$product_id, $variant_id, $new_quantity, $location, $batch_number, $expiry_date]);
        }
        
        // Record transaction in inventory_transactions table
        $stmt = $db->prepare("
            INSERT INTO inventory_transactions 
            (product_id, variant_id, transaction_type, quantity, reference_type, reference_id, notes, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $product_id, $variant_id, $transaction_type, 
            ($transaction_type === 'out' ? -$quantity : $quantity),
            $reference_type, $reference_id, $notes, $user['id']
        ]);
        
        // Log activity
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, table_name, record_id, new_values)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'], 
            'inventory_' . $transaction_type, 
            'inventory', 
            $product_id,
            json_encode([
                'product_id' => $product_id,
                'variant_id' => $variant_id,
                'transaction_type' => $transaction_type,
                'quantity' => $quantity,
                'new_stock' => $new_quantity,
                'location' => $location
            ])
        ]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Transaction processed successfully',
            'new_stock' => $new_quantity,
            'transaction_type' => $transaction_type,
            'quantity_changed' => ($transaction_type === 'out' ? -$quantity : $quantity)
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleRecentTransactions($db) {
    try {
        $stmt = $db->prepare("
            SELECT it.*, p.name as product_name, pv.variant_name, u.full_name as user_name
            FROM inventory_transactions it
            JOIN products p ON it.product_id = p.id
            LEFT JOIN product_variants pv ON it.variant_id = pv.id
            JOIN users u ON it.user_id = u.id
            ORDER BY it.created_at DESC
            LIMIT 20
        ");
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'transactions' => $transactions
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading transactions: ' . $e->getMessage()
        ]);
    }
}

function handleBarcodeGeneration($db, $data) {
    try {
        $product_id = $data['product_id'];
        $variant_id = $data['variant_id'] ?? null;
        $barcode_type = $data['barcode_type'] ?? 'EAN13';
        
        // Generate unique barcode
        $prefix = 'TLB'; // Thai Link BD prefix
        $timestamp = time();
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $barcode = $prefix . $timestamp . $random;
        
        // For EAN13, ensure it's 13 digits
        if ($barcode_type === 'EAN13') {
            $barcode = substr($barcode, 0, 12);
            $barcode = str_pad($barcode, 12, '0', STR_PAD_LEFT);
            // Calculate check digit for EAN13
            $checksum = 0;
            for ($i = 0; $i < 12; $i++) {
                $checksum += (int)$barcode[$i] * (($i % 2 === 0) ? 1 : 3);
            }
            $checkDigit = (10 - ($checksum % 10)) % 10;
            $barcode .= $checkDigit;
        }
        
        // Check if barcode already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM barcodes WHERE barcode = ?");
        $stmt->execute([$barcode]);
        if ($stmt->fetchColumn() > 0) {
            // Generate new one if exists
            $barcode = $prefix . (time() + rand(1, 100)) . $random;
            if ($barcode_type === 'EAN13') {
                $barcode = substr($barcode, 0, 12);
                $barcode = str_pad($barcode, 12, '0', STR_PAD_LEFT);
                $checksum = 0;
                for ($i = 0; $i < 12; $i++) {
                    $checksum += (int)$barcode[$i] * (($i % 2 === 0) ? 1 : 3);
                }
                $checkDigit = (10 - ($checksum % 10)) % 10;
                $barcode .= $checkDigit;
            }
        }
        
        // Insert barcode
        $stmt = $db->prepare("
            INSERT INTO barcodes (product_id, variant_id, barcode, barcode_type, is_primary)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([$product_id, $variant_id, $barcode, $barcode_type]);
        
        echo json_encode([
            'success' => true,
            'barcode' => $barcode,
            'barcode_type' => $barcode_type,
            'message' => 'Barcode generated successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error generating barcode: ' . $e->getMessage()
        ]);
    }
}

function handleBarcodePrint($db, $data) {
    try {
        $barcode_ids = $data['barcode_ids'] ?? [];
        
        if (empty($barcode_ids)) {
            throw new Exception('No barcodes selected for printing');
        }
        
        $placeholders = str_repeat('?,', count($barcode_ids) - 1) . '?';
        $stmt = $db->prepare("
            SELECT b.*, p.name as product_name, p.sku, pv.variant_name
            FROM barcodes b
            JOIN products p ON b.product_id = p.id
            LEFT JOIN product_variants pv ON b.variant_id = pv.id
            WHERE b.id IN ($placeholders)
        ");
        $stmt->execute($barcode_ids);
        $barcodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate print-ready HTML
        $html = generateBarcodePrintHTML($barcodes);
        
        echo json_encode([
            'success' => true,
            'print_html' => $html,
            'barcode_count' => count($barcodes)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error preparing barcodes for print: ' . $e->getMessage()
        ]);
    }
}

function generateBarcodePrintHTML($barcodes) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Barcode Print</title>
        <style>
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
            .barcode-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                padding: 20px;
            }
            .barcode-item {
                border: 1px solid #ccc;
                padding: 10px;
                text-align: center;
                page-break-inside: avoid;
            }
            .barcode-number {
                font-family: monospace;
                font-size: 12px;
                margin: 5px 0;
            }
            .product-info {
                font-size: 10px;
                margin: 2px 0;
            }
            .barcode-svg {
                margin: 5px 0;
            }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    </head>
    <body>
        <div class="no-print" style="padding: 20px; text-align: center;">
            <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px;">Print Barcodes</button>
        </div>
        <div class="barcode-grid">';
    
    foreach ($barcodes as $barcode) {
        $productName = htmlspecialchars($barcode['product_name']);
        $variantName = $barcode['variant_name'] ? ' - ' . htmlspecialchars($barcode['variant_name']) : '';
        $sku = htmlspecialchars($barcode['sku']);
        $barcodeValue = htmlspecialchars($barcode['barcode']);
        
        $html .= '
            <div class="barcode-item">
                <div class="product-info"><strong>' . $productName . $variantName . '</strong></div>
                <div class="product-info">SKU: ' . $sku . '</div>
                <svg class="barcode-svg" id="barcode-' . $barcode['id'] . '"></svg>
                <div class="barcode-number">' . $barcodeValue . '</div>
            </div>';
    }
    
    $html .= '
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {';
    
    foreach ($barcodes as $barcode) {
        $html .= '
                JsBarcode("#barcode-' . $barcode['id'] . '", "' . $barcode['barcode'] . '", {
                    format: "' . $barcode['barcode_type'] . '",
                    width: 1,
                    height: 40,
                    displayValue: false
                });';
    }
    
    $html .= '
            });
        </script>
    </body>
    </html>';
    
    return $html;
}
?>

