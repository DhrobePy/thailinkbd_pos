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

$debug = [];
$response = [];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $debug['connection'] = 'SUCCESS - Connected to database';
    $debug['database_name'] = $db->query('SELECT DATABASE()')->fetchColumn();
    
    // Test each query individually and log results
    
    // 1. Total products
    try {
        $query = "SELECT COUNT(*) as count FROM products WHERE is_active = 1";
        $debug['query_1'] = $query;
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['total_products'] = (int)$result['count'];
        $debug['query_1_result'] = $result;
        $debug['query_1_status'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug['query_1_error'] = $e->getMessage();
        $debug['query_1_status'] = 'FAILED';
        $response['total_products'] = 0;
    }
    
    // 2. Total customers
    try {
        $query = "SELECT COUNT(*) as count FROM customers WHERE is_active = 1";
        $debug['query_2'] = $query;
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['total_customers'] = (int)$result['count'];
        $debug['query_2_result'] = $result;
        $debug['query_2_status'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug['query_2_error'] = $e->getMessage();
        $debug['query_2_status'] = 'FAILED';
        $response['total_customers'] = 0;
    }
    
    // 3. Check if inventory table exists and has data
    try {
        $query = "SELECT COUNT(*) as count FROM inventory";
        $debug['query_3'] = $query;
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug['inventory_table_count'] = $result['count'];
        $debug['query_3_status'] = 'SUCCESS - Inventory table exists';
    } catch (Exception $e) {
        $debug['query_3_error'] = $e->getMessage();
        $debug['query_3_status'] = 'FAILED - Inventory table issue';
    }
    
    // 4. Low stock items (if inventory table works)
    try {
        $query = "SELECT COUNT(*) as count FROM inventory i JOIN products p ON i.product_id = p.id WHERE i.quantity <= i.min_stock_level AND p.is_active = 1";
        $debug['query_4'] = $query;
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['low_stock_items'] = (int)$result['count'];
        $debug['query_4_result'] = $result;
        $debug['query_4_status'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug['query_4_error'] = $e->getMessage();
        $debug['query_4_status'] = 'FAILED';
        $response['low_stock_items'] = 0;
    }
    
    // 5. Out of stock
    try {
        $query = "SELECT COUNT(*) as count FROM inventory i JOIN products p ON i.product_id = p.id WHERE i.quantity = 0 AND p.is_active = 1";
        $debug['query_5'] = $query;
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['out_of_stock'] = (int)$result['count'];
        $debug['query_5_result'] = $result;
        $debug['query_5_status'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug['query_5_error'] = $e->getMessage();
        $debug['query_5_status'] = 'FAILED';
        $response['out_of_stock'] = 0;
    }
    
    // 6. Inventory value
    try {
        $query = "SELECT SUM(i.quantity * p.cost_price) as total FROM inventory i JOIN products p ON i.product_id = p.id WHERE p.is_active = 1";
        $debug['query_6'] = $query;
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['inventory_value'] = (float)($result['total'] ?? 0);
        $debug['query_6_result'] = $result;
        $debug['query_6_status'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug['query_6_error'] = $e->getMessage();
        $debug['query_6_status'] = 'FAILED';
        $response['inventory_value'] = 0;
    }
    
    // 7. Today's sales
    try {
        $query = "SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue FROM sales WHERE DATE(created_at) = CURDATE()";
        $debug['query_7'] = $query;
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['today_orders'] = (int)$result['orders'];
        $response['today_revenue'] = (float)$result['revenue'];
        $debug['query_7_result'] = $result;
        $debug['query_7_status'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug['query_7_error'] = $e->getMessage();
        $debug['query_7_status'] = 'FAILED';
        $response['today_orders'] = 0;
        $response['today_revenue'] = 0;
    }
    
    // 8. This month's sales
    try {
        $query = "SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue FROM sales WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
        $debug['query_8'] = $query;
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['month_orders'] = (int)$result['orders'];
        $response['month_revenue'] = (float)$result['revenue'];
        $debug['query_8_result'] = $result;
        $debug['query_8_status'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug['query_8_error'] = $e->getMessage();
        $debug['query_8_status'] = 'FAILED';
        $response['month_orders'] = 0;
        $response['month_revenue'] = 0;
    }
    
    // 9. Pending invoices
    try {
        $query = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as amount FROM invoices WHERE status = 'pending'";
        $debug['query_9'] = $query;
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['pending_invoices'] = (int)$result['count'];
        $response['pending_amount'] = (float)$result['amount'];
        $debug['query_9_result'] = $result;
        $debug['query_9_status'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug['query_9_error'] = $e->getMessage();
        $debug['query_9_status'] = 'FAILED';
        $response['pending_invoices'] = 0;
        $response['pending_amount'] = 0;
    }
    
    // 10. Overdue invoices
    try {
        $query = "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as amount FROM invoices WHERE status = 'pending' AND due_date < CURDATE()";
        $debug['query_10'] = $query;
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['overdue_invoices'] = (int)$result['count'];
        $response['overdue_amount'] = (float)$result['amount'];
        $debug['query_10_result'] = $result;
        $debug['query_10_status'] = 'SUCCESS';
    } catch (Exception $e) {
        $debug['query_10_error'] = $e->getMessage();
        $debug['query_10_status'] = 'FAILED';
        $response['overdue_invoices'] = 0;
        $response['overdue_amount'] = 0;
    }
    
    // Check table structures
    try {
        $debug['products_structure'] = $db->query("DESCRIBE products")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $debug['products_structure_error'] = $e->getMessage();
    }
    
    try {
        $debug['inventory_structure'] = $db->query("DESCRIBE inventory")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $debug['inventory_structure_error'] = $e->getMessage();
    }
    
    try {
        $debug['sales_structure'] = $db->query("DESCRIBE sales")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $debug['sales_structure_error'] = $e->getMessage();
    }
    
    // Sample data from each table
    try {
        $debug['products_sample'] = $db->query("SELECT * FROM products LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $debug['products_sample_error'] = $e->getMessage();
    }
    
    try {
        $debug['inventory_sample'] = $db->query("SELECT * FROM inventory LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $debug['inventory_sample_error'] = $e->getMessage();
    }
    
    // Empty arrays for now
    $response['recent_sales'] = [];
    $response['top_products'] = [];
    $response['recent_activities'] = [];
    $response['low_stock_products'] = [];
    $response['notifications'] = [];
    $response['notification_count'] = 0;
    $response['revenue_growth'] = 0;
    $response['orders_growth'] = 0;
    
    // Add debug info to response
    $response['debug'] = $debug;
    $response['timestamp'] = date('Y-m-d H:i:s');
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database connection failed',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'debug' => $debug,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>

