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
        case 'get_orders':
            getOrders($db);
            break;
            
        case 'get_order':
            getOrder($db);
            break;
            
        case 'create_order':
            createOrder($db, $user);
            break;
            
        case 'update_order':
            updateOrder($db, $user);
            break;
            
        case 'delete_order':
            deleteOrder($db, $user);
            break;
            
        case 'get_invoices':
            getInvoices($db);
            break;
            
        case 'get_invoice':
            getInvoice($db);
            break;
            
        case 'create_invoice':
            createInvoice($db, $user);
            break;
            
        case 'update_invoice':
            updateInvoice($db, $user);
            break;
            
        case 'delete_invoice':
            deleteInvoice($db, $user);
            break;
            
        case 'partial_delivery':
            createPartialDelivery($db, $user);
            break;
            
        case 'mark_delivered':
            markDelivered($db, $user);
            break;
            
        case 'generate_pdf':
            generateInvoicePDF($db);
            break;
            
        case 'send_email':
            sendInvoiceEmail($db, $user);
            break;
            
        case 'get_customers':
            getCustomers($db);
            break;
            
        case 'get_products_for_order':
            getProductsForOrder($db);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log("Orders/Invoices API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

// Get all orders with delivery status
function getOrders($db) {
    try {
        $query = "
            SELECT 
                o.*,
                c.name as customer_name,
                c.email as customer_email,
                c.phone as customer_phone,
                c.customer_type,
                COUNT(DISTINCT oi.id) as total_items,
                COUNT(DISTINCT i.id) as invoice_count,
                COALESCE(SUM(CASE WHEN i.status = 'delivered' THEN ii.quantity ELSE 0 END), 0) as delivered_quantity,
                COALESCE(SUM(oi.quantity), 0) as total_quantity,
                CASE 
                    WHEN COALESCE(SUM(CASE WHEN i.status = 'delivered' THEN ii.quantity ELSE 0 END), 0) = 0 THEN 'pending'
                    WHEN COALESCE(SUM(CASE WHEN i.status = 'delivered' THEN ii.quantity ELSE 0 END), 0) < COALESCE(SUM(oi.quantity), 0) THEN 'partial'
                    ELSE 'completed'
                END as delivery_status
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN invoices i ON o.id = i.order_id
            LEFT JOIN invoice_items ii ON i.id = ii.invoice_id AND oi.product_id = ii.product_id
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get summary statistics
        $summary = [
            'total_orders' => count($orders),
            'pending_orders' => 0,
            'partial_orders' => 0,
            'completed_orders' => 0,
            'total_value' => 0
        ];
        
        foreach ($orders as $order) {
            $summary['total_value'] += (float)$order['total_amount'];
            
            switch ($order['delivery_status']) {
                case 'pending':
                    $summary['pending_orders']++;
                    break;
                case 'partial':
                    $summary['partial_orders']++;
                    break;
                case 'completed':
                    $summary['completed_orders']++;
                    break;
            }
        }
        
        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'summary' => $summary
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error fetching orders: ' . $e->getMessage());
    }
}

// Get single order with items and invoices
function getOrder($db) {
    try {
        $orderId = $_GET['id'] ?? null;
        if (!$orderId) {
            throw new Exception('Order ID is required');
        }
        
        // Get order details
        $orderQuery = "
            SELECT 
                o.*,
                c.name as customer_name,
                c.email as customer_email,
                c.phone as customer_phone,
                c.address as customer_address,
                c.customer_type
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = :id
        ";
        
        $orderStmt = $db->prepare($orderQuery);
        $orderStmt->bindParam(':id', $orderId);
        $orderStmt->execute();
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new Exception('Order not found');
        }
        
        // Get order items
        $itemsQuery = "
            SELECT 
                oi.*,
                p.name as product_name,
                p.sku,
                p.selling_price,
                p.wholesale_price,
                COALESCE(SUM(CASE WHEN i.status = 'delivered' THEN ii.quantity ELSE 0 END), 0) as delivered_quantity,
                (oi.quantity - COALESCE(SUM(CASE WHEN i.status = 'delivered' THEN ii.quantity ELSE 0 END), 0)) as pending_quantity
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN invoice_items ii ON oi.product_id = ii.product_id
            LEFT JOIN invoices i ON ii.invoice_id = i.id AND i.order_id = oi.order_id
            WHERE oi.order_id = :order_id
            GROUP BY oi.id
            ORDER BY p.name
        ";
        
        $itemsStmt = $db->prepare($itemsQuery);
        $itemsStmt->bindParam(':order_id', $orderId);
        $itemsStmt->execute();
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get related invoices
        $invoicesQuery = "
            SELECT 
                i.*,
                COUNT(ii.id) as item_count,
                SUM(ii.quantity) as total_quantity
            FROM invoices i
            LEFT JOIN invoice_items ii ON i.id = ii.invoice_id
            WHERE i.order_id = :order_id
            GROUP BY i.id
            ORDER BY i.created_at DESC
        ";
        
        $invoicesStmt = $db->prepare($invoicesQuery);
        $invoicesStmt->bindParam(':order_id', $orderId);
        $invoicesStmt->execute();
        $invoices = $invoicesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'order' => $order,
            'items' => $items,
            'invoices' => $invoices
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error fetching order: ' . $e->getMessage());
    }
}

// Create new order
function createOrder($db, $user) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($input['customer_id']) || empty($input['items'])) {
            throw new Exception('Customer and items are required');
        }
        
        $db->beginTransaction();
        
        // Calculate totals
        $subtotal = 0;
        $taxAmount = 0;
        $discountAmount = (float)($input['discount_amount'] ?? 0);
        
        foreach ($input['items'] as $item) {
            $itemTotal = (float)$item['unit_price'] * (int)$item['quantity'];
            $subtotal += $itemTotal;
        }
        
        $taxRate = (float)($input['tax_rate'] ?? 0);
        $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);
        $totalAmount = $subtotal - $discountAmount + $taxAmount;
        
        // Create order
        $orderQuery = "
            INSERT INTO orders (
                customer_id, order_date, due_date, status, notes,
                subtotal, discount_amount, tax_rate, tax_amount, total_amount,
                created_by, created_at
            ) VALUES (
                :customer_id, :order_date, :due_date, :status, :notes,
                :subtotal, :discount_amount, :tax_rate, :tax_amount, :total_amount,
                :created_by, NOW()
            )
        ";
        
        $orderStmt = $db->prepare($orderQuery);
        $orderStmt->bindParam(':customer_id', $input['customer_id']);
        $orderStmt->bindParam(':order_date', $input['order_date'] ?? date('Y-m-d'));
        $orderStmt->bindParam(':due_date', $input['due_date']);
        $orderStmt->bindParam(':status', $input['status'] ?? 'pending');
        $orderStmt->bindParam(':notes', $input['notes'] ?? '');
        $orderStmt->bindParam(':subtotal', $subtotal);
        $orderStmt->bindParam(':discount_amount', $discountAmount);
        $orderStmt->bindParam(':tax_rate', $taxRate);
        $orderStmt->bindParam(':tax_amount', $taxAmount);
        $orderStmt->bindParam(':total_amount', $totalAmount);
        $orderStmt->bindParam(':created_by', $user['id']);
        
        $orderStmt->execute();
        $orderId = $db->lastInsertId();
        
        // Add order items
        $itemQuery = "
            INSERT INTO order_items (
                order_id, product_id, quantity, unit_price, total_price
            ) VALUES (
                :order_id, :product_id, :quantity, :unit_price, :total_price
            )
        ";
        
        $itemStmt = $db->prepare($itemQuery);
        
        foreach ($input['items'] as $item) {
            $itemTotal = (float)$item['unit_price'] * (int)$item['quantity'];
            
            $itemStmt->bindParam(':order_id', $orderId);
            $itemStmt->bindParam(':product_id', $item['product_id']);
            $itemStmt->bindParam(':quantity', $item['quantity']);
            $itemStmt->bindParam(':unit_price', $item['unit_price']);
            $itemStmt->bindParam(':total_price', $itemTotal);
            $itemStmt->execute();
        }
        
        // Log activity
        logActivity($db, $user['id'], 'order_created', "Created order #$orderId for customer ID: {$input['customer_id']}");
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order created successfully',
            'order_id' => $orderId
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception('Error creating order: ' . $e->getMessage());
    }
}

// Create invoice from order (partial or full)
function createInvoice($db, $user) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['order_id']) || empty($input['items'])) {
            throw new Exception('Order ID and items are required');
        }
        
        $db->beginTransaction();
        
        // Get order details
        $orderQuery = "SELECT * FROM orders WHERE id = :id";
        $orderStmt = $db->prepare($orderQuery);
        $orderStmt->bindParam(':id', $input['order_id']);
        $orderStmt->execute();
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new Exception('Order not found');
        }
        
        // Generate invoice number
        $invoiceNumber = generateInvoiceNumber($db);
        
        // Calculate invoice totals
        $subtotal = 0;
        foreach ($input['items'] as $item) {
            $itemTotal = (float)$item['unit_price'] * (int)$item['quantity'];
            $subtotal += $itemTotal;
        }
        
        $discountAmount = (float)($input['discount_amount'] ?? 0);
        $taxRate = (float)($input['tax_rate'] ?? $order['tax_rate']);
        $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);
        $totalAmount = $subtotal - $discountAmount + $taxAmount;
        
        // Create invoice
        $invoiceQuery = "
            INSERT INTO invoices (
                invoice_number, order_id, customer_id, invoice_date, due_date,
                status, notes, subtotal, discount_amount, tax_rate, tax_amount, total_amount,
                created_by, created_at
            ) VALUES (
                :invoice_number, :order_id, :customer_id, :invoice_date, :due_date,
                :status, :notes, :subtotal, :discount_amount, :tax_rate, :tax_amount, :total_amount,
                :created_by, NOW()
            )
        ";
        
        $invoiceStmt = $db->prepare($invoiceQuery);
        $invoiceStmt->bindParam(':invoice_number', $invoiceNumber);
        $invoiceStmt->bindParam(':order_id', $input['order_id']);
        $invoiceStmt->bindParam(':customer_id', $order['customer_id']);
        $invoiceStmt->bindParam(':invoice_date', $input['invoice_date'] ?? date('Y-m-d'));
        $invoiceStmt->bindParam(':due_date', $input['due_date'] ?? $order['due_date']);
        $invoiceStmt->bindParam(':status', $input['status'] ?? 'pending');
        $invoiceStmt->bindParam(':notes', $input['notes'] ?? '');
        $invoiceStmt->bindParam(':subtotal', $subtotal);
        $invoiceStmt->bindParam(':discount_amount', $discountAmount);
        $invoiceStmt->bindParam(':tax_rate', $taxRate);
        $invoiceStmt->bindParam(':tax_amount', $taxAmount);
        $invoiceStmt->bindParam(':total_amount', $totalAmount);
        $invoiceStmt->bindParam(':created_by', $user['id']);
        
        $invoiceStmt->execute();
        $invoiceId = $db->lastInsertId();
        
        // Add invoice items
        $itemQuery = "
            INSERT INTO invoice_items (
                invoice_id, product_id, quantity, unit_price, total_price
            ) VALUES (
                :invoice_id, :product_id, :quantity, :unit_price, :total_price
            )
        ";
        
        $itemStmt = $db->prepare($itemQuery);
        
        foreach ($input['items'] as $item) {
            // Validate quantity doesn't exceed pending amount
            $pendingQuery = "
                SELECT 
                    oi.quantity - COALESCE(SUM(CASE WHEN i.status != 'cancelled' THEN ii.quantity ELSE 0 END), 0) as pending_quantity
                FROM order_items oi
                LEFT JOIN invoice_items ii ON oi.product_id = ii.product_id
                LEFT JOIN invoices i ON ii.invoice_id = i.id AND i.order_id = oi.order_id
                WHERE oi.order_id = :order_id AND oi.product_id = :product_id
                GROUP BY oi.id
            ";
            
            $pendingStmt = $db->prepare($pendingQuery);
            $pendingStmt->bindParam(':order_id', $input['order_id']);
            $pendingStmt->bindParam(':product_id', $item['product_id']);
            $pendingStmt->execute();
            $pendingData = $pendingStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$pendingData || $item['quantity'] > $pendingData['pending_quantity']) {
                throw new Exception("Quantity exceeds pending amount for product ID: {$item['product_id']}");
            }
            
            $itemTotal = (float)$item['unit_price'] * (int)$item['quantity'];
            
            $itemStmt->bindParam(':invoice_id', $invoiceId);
            $itemStmt->bindParam(':product_id', $item['product_id']);
            $itemStmt->bindParam(':quantity', $item['quantity']);
            $itemStmt->bindParam(':unit_price', $item['unit_price']);
            $itemStmt->bindParam(':total_price', $itemTotal);
            $itemStmt->execute();
        }
        
        // Log activity
        logActivity($db, $user['id'], 'invoice_created', "Created invoice #$invoiceNumber for order #{$input['order_id']}");
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Invoice created successfully',
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoiceNumber
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception('Error creating invoice: ' . $e->getMessage());
    }
}

// Mark invoice as delivered and update inventory
function markDelivered($db, $user) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $invoiceId = $input['invoice_id'] ?? null;
        
        if (!$invoiceId) {
            throw new Exception('Invoice ID is required');
        }
        
        $db->beginTransaction();
        
        // Get invoice details
        $invoiceQuery = "SELECT * FROM invoices WHERE id = :id";
        $invoiceStmt = $db->prepare($invoiceQuery);
        $invoiceStmt->bindParam(':id', $invoiceId);
        $invoiceStmt->execute();
        $invoice = $invoiceStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            throw new Exception('Invoice not found');
        }
        
        if ($invoice['status'] === 'delivered') {
            throw new Exception('Invoice already marked as delivered');
        }
        
        // Get invoice items
        $itemsQuery = "
            SELECT ii.*, p.name as product_name
            FROM invoice_items ii
            JOIN products p ON ii.product_id = p.id
            WHERE ii.invoice_id = :invoice_id
        ";
        
        $itemsStmt = $db->prepare($itemsQuery);
        $itemsStmt->bindParam(':invoice_id', $invoiceId);
        $itemsStmt->execute();
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update inventory for each item
        foreach ($items as $item) {
            // Reduce inventory
            $updateInventory = "
                UPDATE inventory 
                SET quantity = quantity - :quantity,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE product_id = :product_id
            ";
            
            $inventoryStmt = $db->prepare($updateInventory);
            $inventoryStmt->bindParam(':quantity', $item['quantity']);
            $inventoryStmt->bindParam(':product_id', $item['product_id']);
            $inventoryStmt->bindParam(':updated_by', $user['id']);
            $inventoryStmt->execute();
            
            // Log inventory transaction
            $transactionQuery = "
                INSERT INTO inventory_transactions (
                    product_id, transaction_type, quantity, reference_type, reference_id,
                    notes, created_by, created_at
                ) VALUES (
                    :product_id, 'out', :quantity, 'invoice', :invoice_id,
                    :notes, :created_by, NOW()
                )
            ";
            
            $transactionStmt = $db->prepare($transactionQuery);
            $transactionStmt->bindParam(':product_id', $item['product_id']);
            $transactionStmt->bindParam(':quantity', $item['quantity']);
            $transactionStmt->bindParam(':invoice_id', $invoiceId);
            $transactionStmt->bindParam(':notes', "Delivered via invoice #{$invoice['invoice_number']}");
            $transactionStmt->bindParam(':created_by', $user['id']);
            $transactionStmt->execute();
        }
        
        // Update invoice status
        $updateInvoice = "
            UPDATE invoices 
            SET status = 'delivered',
                delivered_date = NOW(),
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id
        ";
        
        $updateStmt = $db->prepare($updateInvoice);
        $updateStmt->bindParam(':id', $invoiceId);
        $updateStmt->bindParam(':updated_by', $user['id']);
        $updateStmt->execute();
        
        // Create sale record
        createSaleFromInvoice($db, $invoice, $items, $user);
        
        // Log activity
        logActivity($db, $user['id'], 'invoice_delivered', "Marked invoice #{$invoice['invoice_number']} as delivered");
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Invoice marked as delivered successfully'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception('Error marking invoice as delivered: ' . $e->getMessage());
    }
}

// Delete undelivered invoice
function deleteInvoice($db, $user) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $invoiceId = $input['invoice_id'] ?? null;
        
        if (!$invoiceId) {
            throw new Exception('Invoice ID is required');
        }
        
        $db->beginTransaction();
        
        // Get invoice details
        $invoiceQuery = "SELECT * FROM invoices WHERE id = :id";
        $invoiceStmt = $db->prepare($invoiceQuery);
        $invoiceStmt->bindParam(':id', $invoiceId);
        $invoiceStmt->execute();
        $invoice = $invoiceStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            throw new Exception('Invoice not found');
        }
        
        if ($invoice['status'] === 'delivered') {
            throw new Exception('Cannot delete delivered invoice');
        }
        
        // Delete invoice items
        $deleteItems = "DELETE FROM invoice_items WHERE invoice_id = :invoice_id";
        $deleteItemsStmt = $db->prepare($deleteItems);
        $deleteItemsStmt->bindParam(':invoice_id', $invoiceId);
        $deleteItemsStmt->execute();
        
        // Delete invoice
        $deleteInvoice = "DELETE FROM invoices WHERE id = :id";
        $deleteInvoiceStmt = $db->prepare($deleteInvoice);
        $deleteInvoiceStmt->bindParam(':id', $invoiceId);
        $deleteInvoiceStmt->execute();
        
        // Log activity
        logActivity($db, $user['id'], 'invoice_deleted', "Deleted undelivered invoice #{$invoice['invoice_number']}");
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Invoice deleted successfully'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception('Error deleting invoice: ' . $e->getMessage());
    }
}

// Get customers for dropdown
function getCustomers($db) {
    try {
        $query = "
            SELECT id, name, email, phone, customer_type, address
            FROM customers 
            WHERE is_active = 1 
            ORDER BY name ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'customers' => $customers
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error fetching customers: ' . $e->getMessage());
    }
}

// Get products for order creation
function getProductsForOrder($db) {
    try {
        $query = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.selling_price,
                p.wholesale_price,
                p.cost_price,
                COALESCE(i.quantity, 0) as stock_quantity,
                c.name as category_name,
                b.name as brand_name
            FROM products p
            LEFT JOIN inventory i ON p.id = i.product_id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.is_active = 1
            ORDER BY p.name ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'products' => $products
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error fetching products: ' . $e->getMessage());
    }
}

// Helper functions
function generateInvoiceNumber($db) {
    $prefix = 'INV';
    $year = date('Y');
    $month = date('m');
    
    // Get last invoice number for this month
    $query = "
        SELECT invoice_number 
        FROM invoices 
        WHERE invoice_number LIKE :pattern 
        ORDER BY id DESC 
        LIMIT 1
    ";
    
    $pattern = "$prefix-$year$month-%";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':pattern', $pattern);
    $stmt->execute();
    
    $lastInvoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastInvoice) {
        $lastNumber = (int)substr($lastInvoice['invoice_number'], -4);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . '-' . $year . $month . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

function createSaleFromInvoice($db, $invoice, $items, $user) {
    // Create sale record
    $saleQuery = "
        INSERT INTO sales (
            customer_id, sale_date, total_amount, payment_method, status,
            invoice_id, created_by, created_at
        ) VALUES (
            :customer_id, NOW(), :total_amount, 'pending', 'completed',
            :invoice_id, :created_by, NOW()
        )
    ";
    
    $saleStmt = $db->prepare($saleQuery);
    $saleStmt->bindParam(':customer_id', $invoice['customer_id']);
    $saleStmt->bindParam(':total_amount', $invoice['total_amount']);
    $saleStmt->bindParam(':invoice_id', $invoice['id']);
    $saleStmt->bindParam(':created_by', $user['id']);
    $saleStmt->execute();
    
    $saleId = $db->lastInsertId();
    
    // Create sale items
    $itemQuery = "
        INSERT INTO sale_items (
            sale_id, product_id, quantity, unit_price, total_price
        ) VALUES (
            :sale_id, :product_id, :quantity, :unit_price, :total_price
        )
    ";
    
    $itemStmt = $db->prepare($itemQuery);
    
    foreach ($items as $item) {
        $itemStmt->bindParam(':sale_id', $saleId);
        $itemStmt->bindParam(':product_id', $item['product_id']);
        $itemStmt->bindParam(':quantity', $item['quantity']);
        $itemStmt->bindParam(':unit_price', $item['unit_price']);
        $itemStmt->bindParam(':total_price', $item['total_price']);
        $itemStmt->execute();
    }
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
        error_log("Activity log error: " . $e->getMessage());
    }
}
?>

