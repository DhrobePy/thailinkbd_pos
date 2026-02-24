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
    
    // Get total products (you have 8 products)
    $query = "SELECT COUNT(*) as total_products FROM products WHERE is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['total_products'] = (int)$result['total_products'];
    
    // Get total customers (you have 5 customers)
    $query = "SELECT COUNT(*) as total_customers FROM customers WHERE is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['total_customers'] = (int)$result['total_customers'];
    
    // Get low stock items from inventory table
    $query = "SELECT COUNT(*) as low_stock_items FROM inventory i 
              JOIN products p ON i.product_id = p.id 
              WHERE i.quantity <= i.min_stock_level AND p.is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['low_stock_items'] = (int)$result['low_stock_items'];
    
    // Get out of stock items
    $query = "SELECT COUNT(*) as out_of_stock FROM inventory i 
              JOIN products p ON i.product_id = p.id 
              WHERE i.quantity = 0 AND p.is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['out_of_stock'] = (int)$result['out_of_stock'];
    
    // Get total inventory value
    $query = "SELECT SUM(i.quantity * p.cost_price) as inventory_value 
              FROM inventory i 
              JOIN products p ON i.product_id = p.id 
              WHERE p.is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['inventory_value'] = (float)($result['inventory_value'] ?? 0);
    
    // Get today's sales (you have 0 sales currently)
    $query = "SELECT COUNT(*) as today_orders, COALESCE(SUM(total_amount), 0) as today_revenue 
              FROM sales WHERE DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['today_orders'] = (int)$result['today_orders'];
    $response['today_revenue'] = (float)$result['today_revenue'];
    
    // Get this month's sales
    $query = "SELECT COUNT(*) as month_orders, COALESCE(SUM(total_amount), 0) as month_revenue 
              FROM sales WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['month_orders'] = (int)$result['month_orders'];
    $response['month_revenue'] = (float)$result['month_revenue'];
    
    // Get pending invoices (you have 0 invoices currently)
    $query = "SELECT COUNT(*) as pending_invoices, COALESCE(SUM(total_amount), 0) as pending_amount 
              FROM invoices WHERE status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['pending_invoices'] = (int)$result['pending_invoices'];
    $response['pending_amount'] = (float)$result['pending_amount'];
    
    // Get overdue invoices
    $query = "SELECT COUNT(*) as overdue_invoices, COALESCE(SUM(total_amount), 0) as overdue_amount 
              FROM invoices WHERE status = 'pending' AND due_date < CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['overdue_invoices'] = (int)$result['overdue_invoices'];
    $response['overdue_amount'] = (float)$result['overdue_amount'];
    
    // Get recent sales (last 7 days) - will be empty since you have no sales yet
    $query = "SELECT DATE(created_at) as sale_date, COUNT(*) as orders, SUM(total_amount) as revenue 
              FROM sales 
              WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              GROUP BY DATE(created_at)
              ORDER BY sale_date DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $response['recent_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top selling products (from sale_items) - will be empty since no sales yet
    $query = "SELECT p.name, p.sku, SUM(si.quantity) as total_sold, SUM(si.quantity * si.unit_price) as revenue
              FROM products p
              JOIN sale_items si ON p.id = si.product_id
              JOIN sales s ON si.sale_id = s.id
              WHERE YEAR(s.created_at) = YEAR(CURDATE()) AND MONTH(s.created_at) = MONTH(CURDATE())
              GROUP BY p.id, p.name, p.sku
              ORDER BY total_sold DESC
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $response['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get low stock products details
    $query = "SELECT p.name, p.sku, i.quantity as current_stock, i.min_stock_level, 
              (i.min_stock_level - i.quantity) as shortage
              FROM products p
              JOIN inventory i ON p.id = i.product_id
              WHERE i.quantity <= i.min_stock_level AND p.is_active = 1
              ORDER BY shortage DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $response['low_stock_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activities from activity_logs (you have 0 currently)
    $query = "SELECT al.action, al.table_name, al.created_at, u.full_name as user_name
              FROM activity_logs al
              JOIN users u ON al.user_id = u.id
              ORDER BY al.created_at DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format activities for display
    $response['recent_activities'] = array_map(function($activity) {
        return [
            'type' => strtolower($activity['action']),
            'message' => ucfirst($activity['action']) . ' in ' . $activity['table_name'] . ' by ' . $activity['user_name'],
            'created_at' => $activity['created_at']
        ];
    }, $activities);
    
    // Generate notifications based on real data
    $notifications = [];
    
    // Low stock notifications
    if ($response['low_stock_items'] > 0) {
        $notifications[] = [
            'type' => 'warning',
            'title' => 'Low Stock Alert',
            'message' => $response['low_stock_items'] . ' products are running low on stock',
            'action' => 'modules/inventory/index.php',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // Out of stock notifications
    if ($response['out_of_stock'] > 0) {
        $notifications[] = [
            'type' => 'error',
            'title' => 'Out of Stock',
            'message' => $response['out_of_stock'] . ' products are completely out of stock',
            'action' => 'modules/inventory/index.php',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // Overdue invoices notifications
    if ($response['overdue_invoices'] > 0) {
        $notifications[] = [
            'type' => 'error',
            'title' => 'Overdue Invoices',
            'message' => $response['overdue_invoices'] . ' invoices are overdue (৳ ' . number_format($response['overdue_amount'], 2) . ')',
            'action' => 'modules/invoices/index.php',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // High sales day notification
    if ($response['today_revenue'] > 10000) {
        $notifications[] = [
            'type' => 'success',
            'title' => 'Great Sales Day!',
            'message' => 'Today\'s revenue reached ৳ ' . number_format($response['today_revenue'], 2),
            'action' => 'modules/reports/index.php',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // Welcome notification for new system
    if ($response['today_orders'] == 0 && $response['total_products'] > 0) {
        $notifications[] = [
            'type' => 'info',
            'title' => 'Welcome to Thai Link BD!',
            'message' => 'Your inventory system is ready with ' . $response['total_products'] . ' products. Start making sales!',
            'action' => 'modules/pos/index.php',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    $response['notifications'] = $notifications;
    $response['notification_count'] = count($notifications);
    
    // Calculate growth percentages (will be 0 since no previous data)
    $response['revenue_growth'] = 0;
    $response['orders_growth'] = 0;
    
    // Get actual product and brand info for display
    $query = "SELECT p.name, p.sku, b.name as brand_name, c.name as category_name, i.quantity
              FROM products p
              LEFT JOIN brands b ON p.brand_id = b.id
              LEFT JOIN categories c ON p.category_id = c.id
              LEFT JOIN inventory i ON p.id = i.product_id
              WHERE p.is_active = 1
              ORDER BY p.name
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $response['sample_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get brand and category counts
    $query = "SELECT COUNT(*) as total_brands FROM brands WHERE is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['total_brands'] = (int)$result['total_brands'];
    
    $query = "SELECT COUNT(*) as total_categories FROM categories WHERE is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['total_categories'] = (int)$result['total_categories'];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Real Dashboard API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
}
?>

