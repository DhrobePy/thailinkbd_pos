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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $response = [];
    $debug = [];
    
    // Test database connection
    $debug['connection_test'] = 'Connected successfully';
    $debug['database_name'] = $db->query('SELECT DATABASE()')->fetchColumn();
    
    // Check which tables exist
    $tableCheckQuery = "SHOW TABLES";
    $stmt = $db->prepare($tableCheckQuery);
    $stmt->execute();
    $existingTables = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $existingTables[] = $row[0];
    }
    $debug['existing_tables'] = $existingTables;
    
    // Check products table structure and data
    if (in_array('products', $existingTables)) {
        $debug['products_structure'] = $db->query("DESCRIBE products")->fetchAll(PDO::FETCH_ASSOC);
        $debug['products_count'] = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $debug['products_sample'] = $db->query("SELECT * FROM products LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get actual products data
        $query = "SELECT COUNT(*) as total_products FROM products";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['total_products'] = (int)$result['total_products'];
        $debug['total_products_query'] = $query;
        $debug['total_products_result'] = $result;
        
        // Check if is_active column exists
        $columns = array_column($debug['products_structure'], 'Field');
        if (in_array('is_active', $columns)) {
            $query = "SELECT COUNT(*) as active_products FROM products WHERE is_active = 1";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['total_products'] = (int)$result['active_products'];
            $debug['active_products_query'] = $query;
            $debug['active_products_result'] = $result;
        }
        
        // Check stock levels
        if (in_array('current_stock', $columns) && in_array('min_stock_level', $columns)) {
            $query = "SELECT COUNT(*) as low_stock FROM products WHERE current_stock <= min_stock_level";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['low_stock_items'] = (int)$result['low_stock'];
            $debug['low_stock_query'] = $query;
            $debug['low_stock_result'] = $result;
            
            $query = "SELECT COUNT(*) as out_of_stock FROM products WHERE current_stock = 0";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['out_of_stock'] = (int)$result['out_of_stock'];
            $debug['out_of_stock_query'] = $query;
            $debug['out_of_stock_result'] = $result;
        }
        
        // Check inventory value
        if (in_array('cost_price', $columns) && in_array('current_stock', $columns)) {
            $query = "SELECT SUM(current_stock * cost_price) as inventory_value FROM products";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['inventory_value'] = (float)($result['inventory_value'] ?? 0);
            $debug['inventory_value_query'] = $query;
            $debug['inventory_value_result'] = $result;
        }
        
        // Get low stock products
        if (in_array('current_stock', $columns) && in_array('min_stock_level', $columns)) {
            $query = "SELECT name, sku, current_stock, min_stock_level FROM products WHERE current_stock <= min_stock_level ORDER BY current_stock ASC LIMIT 10";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $response['low_stock_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $debug['low_stock_products_query'] = $query;
            $debug['low_stock_products_count'] = count($response['low_stock_products']);
        }
    } else {
        $debug['products_error'] = 'Products table does not exist';
        $response['total_products'] = 0;
        $response['low_stock_items'] = 0;
        $response['out_of_stock'] = 0;
        $response['inventory_value'] = 0;
        $response['low_stock_products'] = [];
    }
    
    // Check customers table
    if (in_array('customers', $existingTables)) {
        $debug['customers_structure'] = $db->query("DESCRIBE customers")->fetchAll(PDO::FETCH_ASSOC);
        $debug['customers_count'] = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        $debug['customers_sample'] = $db->query("SELECT * FROM customers LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        
        $query = "SELECT COUNT(*) as total_customers FROM customers";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['total_customers'] = (int)$result['total_customers'];
        $debug['total_customers_query'] = $query;
        $debug['total_customers_result'] = $result;
    } else {
        $debug['customers_error'] = 'Customers table does not exist';
        $response['total_customers'] = 0;
    }
    
    // Check sales table
    if (in_array('sales', $existingTables)) {
        $debug['sales_structure'] = $db->query("DESCRIBE sales")->fetchAll(PDO::FETCH_ASSOC);
        $debug['sales_count'] = $db->query("SELECT COUNT(*) FROM sales")->fetchColumn();
        $debug['sales_sample'] = $db->query("SELECT * FROM sales LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        
        // Today's sales
        $query = "SELECT COUNT(*) as today_orders, COALESCE(SUM(total_amount), 0) as today_revenue FROM sales WHERE DATE(created_at) = CURDATE()";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['today_orders'] = (int)$result['today_orders'];
        $response['today_revenue'] = (float)$result['today_revenue'];
        $debug['today_sales_query'] = $query;
        $debug['today_sales_result'] = $result;
        
        // This month's sales
        $query = "SELECT COUNT(*) as month_orders, COALESCE(SUM(total_amount), 0) as month_revenue FROM sales WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['month_orders'] = (int)$result['month_orders'];
        $response['month_revenue'] = (float)$result['month_revenue'];
        $debug['month_sales_query'] = $query;
        $debug['month_sales_result'] = $result;
        
        // Recent sales for chart
        $query = "SELECT DATE(created_at) as sale_date, COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue FROM sales WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY sale_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $response['recent_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $debug['recent_sales_query'] = $query;
        $debug['recent_sales_count'] = count($response['recent_sales']);
    } else {
        $debug['sales_error'] = 'Sales table does not exist';
        $response['today_orders'] = 0;
        $response['today_revenue'] = 0;
        $response['month_orders'] = 0;
        $response['month_revenue'] = 0;
        $response['recent_sales'] = [];
    }
    
    // Check invoices table
    if (in_array('invoices', $existingTables)) {
        $debug['invoices_structure'] = $db->query("DESCRIBE invoices")->fetchAll(PDO::FETCH_ASSOC);
        $debug['invoices_count'] = $db->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
        $debug['invoices_sample'] = $db->query("SELECT * FROM invoices LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        
        // Pending invoices
        $query = "SELECT COUNT(*) as pending_invoices, COALESCE(SUM(total_amount), 0) as pending_amount FROM invoices WHERE status = 'pending'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['pending_invoices'] = (int)$result['pending_invoices'];
        $response['pending_amount'] = (float)$result['pending_amount'];
        $debug['pending_invoices_query'] = $query;
        $debug['pending_invoices_result'] = $result;
        
        // Overdue invoices
        $query = "SELECT COUNT(*) as overdue_invoices, COALESCE(SUM(total_amount), 0) as overdue_amount FROM invoices WHERE status = 'pending' AND due_date < CURDATE()";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['overdue_invoices'] = (int)$result['overdue_invoices'];
        $response['overdue_amount'] = (float)$result['overdue_amount'];
        $debug['overdue_invoices_query'] = $query;
        $debug['overdue_invoices_result'] = $result;
    } else {
        $debug['invoices_error'] = 'Invoices table does not exist';
        $response['pending_invoices'] = 0;
        $response['pending_amount'] = 0;
        $response['overdue_invoices'] = 0;
        $response['overdue_amount'] = 0;
    }
    
    // Check users table
    if (in_array('users', $existingTables)) {
        $debug['users_structure'] = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
        $debug['users_count'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $debug['users_sample'] = $db->query("SELECT id, username, email, full_name, role FROM users LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Set defaults for missing data
    $response['top_products'] = [];
    $response['recent_activities'] = [];
    $response['notifications'] = [];
    $response['notification_count'] = 0;
    $response['revenue_growth'] = 0;
    $response['orders_growth'] = 0;
    
    // Generate notifications based on real data
    $notifications = [];
    
    if (($response['low_stock_items'] ?? 0) > 0) {
        $notifications[] = [
            'type' => 'warning',
            'title' => 'Low Stock Alert',
            'message' => $response['low_stock_items'] . ' products are running low on stock',
            'action' => 'modules/inventory/index.php',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    if (($response['out_of_stock'] ?? 0) > 0) {
        $notifications[] = [
            'type' => 'error',
            'title' => 'Out of Stock',
            'message' => $response['out_of_stock'] . ' products are completely out of stock',
            'action' => 'modules/inventory/index.php',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    if (($response['overdue_invoices'] ?? 0) > 0) {
        $notifications[] = [
            'type' => 'error',
            'title' => 'Overdue Invoices',
            'message' => $response['overdue_invoices'] . ' invoices are overdue (৳ ' . number_format($response['overdue_amount'], 2) . ')',
            'action' => 'modules/invoices/index.php',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    if (($response['today_revenue'] ?? 0) > 10000) {
        $notifications[] = [
            'type' => 'success',
            'title' => 'Great Sales Day!',
            'message' => 'Today\'s revenue reached ৳ ' . number_format($response['today_revenue'], 2),
            'action' => 'modules/reports/index.php',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    $response['notifications'] = $notifications;
    $response['notification_count'] = count($notifications);
    
    // Add comprehensive debug info
    $response['debug'] = $debug;
    $response['timestamp'] = date('Y-m-d H:i:s');
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Debug Dashboard API Error: " . $e->getMessage());
    echo json_encode([
        'error' => 'Database error occurred',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'debug' => [
            'error_occurred' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], JSON_PRETTY_PRINT);
}
?>

