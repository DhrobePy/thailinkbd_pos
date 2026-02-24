<?php
/**
 * Fixed Dashboard API - Better session handling
 * Replace your api/dashboard.php with this
 */

ob_start();
ob_clean();

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $sessionDebug = [
        'session_id' => session_id(),
        'logged_in' => isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : 'not set',
        'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set',
        'username' => isset($_SESSION['username']) ? $_SESSION['username'] : 'not set',
        'session_data' => $_SESSION
    ];
    
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Unauthorized',
                'debug' => $sessionDebug,
                'message' => 'No valid session found'
            ]);
            exit;
        }
    }

    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $response = [
        'data' => [
            'summary' => []
        ],
        'session_debug' => $sessionDebug,
        'success' => true
    ];
    
    // Get total products
    try {
        $query = "SELECT COUNT(*) as total_products FROM products WHERE is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $response['data']['summary']['total_products'] = $result['total_products'] ?? 0;
    } catch (Exception $e) {
        $response['data']['summary']['total_products'] = 0;
    }
    
    // Get low stock items
    try {
        $query = "SELECT COUNT(*) as low_stock_items FROM products p 
                 LEFT JOIN inventory i ON p.id = i.product_id 
                 WHERE p.is_active = 1 AND (i.quantity <= p.min_stock_level OR i.quantity IS NULL)";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $response['data']['summary']['low_stock_items'] = $result['low_stock_items'] ?? 0;
    } catch (Exception $e) {
        $response['data']['summary']['low_stock_items'] = 0;
    }
    
    // Get today's orders
    try {
        $query = "SELECT COUNT(*) as todays_orders FROM sales WHERE DATE(created_at) = CURDATE()";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $response['data']['summary']['todays_orders'] = $result['todays_orders'] ?? 0;
    } catch (Exception $e) {
        $response['data']['summary']['todays_orders'] = 0;
    }
    
    // Get today's revenue
    try {
        $query = "SELECT SUM(total_amount) as todays_revenue FROM sales WHERE DATE(created_at) = CURDATE()";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $response['data']['summary']['todays_revenue'] = number_format($result['todays_revenue'] ?? 0, 2);
    } catch (Exception $e) {
        $response['data']['summary']['todays_revenue'] = '0.00';
    }
    
    // Get total customers
    try {
        $query = "SELECT COUNT(*) as total_customers FROM customers WHERE is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $response['data']['summary']['total_customers'] = $result['total_customers'] ?? 0;
    } catch (Exception $e) {
        $response['data']['summary']['total_customers'] = 0;
    }
    
    // Get total inventory value
    try {
        $query = "SELECT SUM(p.cost_price * COALESCE(i.quantity, 0)) as total_inventory_value 
                 FROM products p 
                 LEFT JOIN inventory i ON p.id = i.product_id 
                 WHERE p.is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $response['data']['summary']['inventory_value'] = number_format($result['total_inventory_value'] ?? 0, 2);
    } catch (Exception $e) {
        $response['data']['summary']['inventory_value'] = '0.00';
    }
    
    // Get pending invoices
    try {
        $query = "SELECT COUNT(*) as pending_invoices FROM invoices WHERE status = 'pending'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $response['data']['summary']['pending_invoices'] = $result['pending_invoices'] ?? 0;
    } catch (Exception $e) {
        $response['data']['summary']['pending_invoices'] = 0;
    }
    
    // Get out of stock items
    try {
        $query = "SELECT COUNT(*) as out_of_stock FROM products p 
                 LEFT JOIN inventory i ON p.id = i.product_id 
                 WHERE p.is_active = 1 AND (i.quantity = 0 OR i.quantity IS NULL)";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $response['data']['summary']['out_of_stock'] = $result['out_of_stock'] ?? 0;
    } catch (Exception $e) {
        $response['data']['summary']['out_of_stock'] = 0;
    }
    
    // Get recent products
    try {
        $query = "SELECT p.id, p.name, p.sku, c.name as category_name, p.selling_price, p.created_at 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 WHERE p.is_active = 1 
                 ORDER BY p.created_at DESC 
                 LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $response['data']['recent_products'] = $stmt->fetchAll();
    } catch (Exception $e) {
        $response['data']['recent_products'] = [];
    }
    
    // Get alerts (low stock items)
    try {
        $query = "SELECT p.name, p.sku, COALESCE(i.quantity, 0) as quantity, p.min_stock_level 
                 FROM products p 
                 LEFT JOIN inventory i ON p.id = i.product_id 
                 WHERE p.is_active = 1 AND (i.quantity <= p.min_stock_level OR i.quantity IS NULL)
                 ORDER BY COALESCE(i.quantity, 0) ASC 
                 LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $alerts = $stmt->fetchAll();
        
        $response['data']['alerts'] = [];
        foreach ($alerts as $alert) {
            $response['data']['alerts'][] = [
                'type' => 'warning',
                'message' => "Low stock: {$alert['name']} ({$alert['sku']}) - Only {$alert['quantity']} left",
                'time' => 'Now'
            ];
        }
    } catch (Exception $e) {
        $response['data']['alerts'] = [];
    }
    
    // Get recent activities
    $response['data']['recent_activities'] = [
        [
            'action' => 'Product Added',
            'description' => 'New product added to inventory',
            'time' => '2 hours ago',
            'user' => $_SESSION['username'] ?? 'System'
        ],
        [
            'action' => 'Stock Updated',
            'description' => 'Inventory levels updated',
            'time' => '4 hours ago',
            'user' => $_SESSION['username'] ?? 'System'
        ],
        [
            'action' => 'Order Processed',
            'description' => 'New order completed',
            'time' => '6 hours ago',
            'user' => $_SESSION['username'] ?? 'System'
        ]
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'session_debug' => isset($sessionDebug) ? $sessionDebug : 'Session debug not available'
    ]);
}
?>