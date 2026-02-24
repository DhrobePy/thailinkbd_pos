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
    
    // 1. Total active products
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['total_products'] = (int)$result['count'];
    
    // 2. Total customers
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM customers WHERE is_active = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['total_customers'] = (int)$result['count'];
    
    // 3. Low stock items (using products.min_stock_level)
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT p.id) as count 
        FROM products p 
        JOIN inventory i ON p.id = i.product_id 
        WHERE p.is_active = 1 
        AND i.quantity <= p.min_stock_level 
        AND p.min_stock_level > 0
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['low_stock_items'] = (int)$result['count'];
    
    // 4. Out of stock items
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT p.id) as count 
        FROM products p 
        JOIN inventory i ON p.id = i.product_id 
        WHERE p.is_active = 1 
        AND i.quantity = 0
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['out_of_stock'] = (int)$result['count'];
    
    // 5. Active inventory value
    $stmt = $db->prepare("
        SELECT SUM(i.quantity * p.cost_price) as total 
        FROM inventory i 
        JOIN products p ON i.product_id = p.id 
        WHERE p.is_active = 1
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['inventory_value'] = (float)($result['total'] ?? 0);
    
    // 6. Today's sales
    $stmt = $db->prepare("
        SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue 
        FROM sales 
        WHERE DATE(sale_date) = CURDATE()
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['today_orders'] = (int)$result['orders'];
    $response['today_revenue'] = (float)$result['revenue'];
    
    // 7. This month's sales
    $stmt = $db->prepare("
        SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue 
        FROM sales 
        WHERE YEAR(sale_date) = YEAR(CURDATE()) 
        AND MONTH(sale_date) = MONTH(CURDATE())
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['month_orders'] = (int)$result['orders'];
    $response['month_revenue'] = (float)$result['revenue'];
    
    // 8. Pending invoices
    $stmt = $db->prepare("
        SELECT COUNT(*) as count, COALESCE(SUM(balance_due), 0) as amount 
        FROM invoices 
        WHERE status IN ('draft', 'sent') 
        AND balance_due > 0
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['pending_invoices'] = (int)$result['count'];
    $response['pending_amount'] = (float)$result['amount'];
    
    // 9. Overdue invoices
    $stmt = $db->prepare("
        SELECT COUNT(*) as count, COALESCE(SUM(balance_due), 0) as amount 
        FROM invoices 
        WHERE status = 'overdue' 
        OR (status IN ('draft', 'sent') AND due_date < CURDATE() AND balance_due > 0)
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['overdue_invoices'] = (int)$result['count'];
    $response['overdue_amount'] = (float)$result['amount'];
    
    // 10. Recent sales (last 7 days for chart)
    $stmt = $db->prepare("
        SELECT DATE(sale_date) as sale_date, 
               COUNT(*) as orders, 
               SUM(total_amount) as revenue 
        FROM sales 
        WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY DATE(sale_date) 
        ORDER BY sale_date DESC
    ");
    $stmt->execute();
    $response['recent_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 11. Top selling products this month
    $stmt = $db->prepare("
        SELECT p.name, p.sku, 
               SUM(si.quantity) as total_sold, 
               SUM(si.total_price) as revenue 
        FROM products p 
        JOIN sale_items si ON p.id = si.product_id 
        JOIN sales s ON si.sale_id = s.id 
        WHERE YEAR(s.sale_date) = YEAR(CURDATE()) 
        AND MONTH(s.sale_date) = MONTH(CURDATE()) 
        AND p.is_active = 1
        GROUP BY p.id, p.name, p.sku 
        ORDER BY total_sold DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $response['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 12. Low stock products details
    $stmt = $db->prepare("
        SELECT p.name, p.sku, 
               SUM(i.quantity) as current_stock, 
               p.min_stock_level,
               (p.min_stock_level - SUM(i.quantity)) as shortage 
        FROM products p 
        JOIN inventory i ON p.id = i.product_id 
        WHERE p.is_active = 1 
        AND p.min_stock_level > 0
        GROUP BY p.id, p.name, p.sku, p.min_stock_level
        HAVING current_stock <= p.min_stock_level
        ORDER BY shortage DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $response['low_stock_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 13. Recent activities
    $stmt = $db->prepare("
        SELECT al.action, al.table_name, al.created_at, 
               u.full_name as user_name 
        FROM activity_logs al 
        JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
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
    
    // 14. Expiring products (if expiry tracking enabled)
    $stmt = $db->prepare("
        SELECT p.name, p.sku, i.expiry_date, i.quantity,
               DATEDIFF(i.expiry_date, CURDATE()) as days_to_expiry
        FROM products p 
        JOIN inventory i ON p.id = i.product_id 
        WHERE p.is_active = 1 
        AND p.expiry_tracking = 1 
        AND i.expiry_date IS NOT NULL 
        AND i.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND i.quantity > 0
        ORDER BY i.expiry_date ASC 
        LIMIT 10
    ");
    $stmt->execute();
    $response['expiring_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    
    // Expiring products notifications
    if (count($response['expiring_products']) > 0) {
        $notifications[] = [
            'type' => 'warning',
            'title' => 'Products Expiring Soon',
            'message' => count($response['expiring_products']) . ' products will expire within 30 days',
            'action' => 'modules/inventory/expiry.php',
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
            'message' => 'Your inventory system is ready with ' . $response['total_products'] . ' active products. Start making sales!',
            'action' => 'modules/pos/index.php',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    $response['notifications'] = $notifications;
    $response['notification_count'] = count($notifications);
    
    // Calculate growth percentages (compare with previous month)
    $stmt = $db->prepare("
        SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue 
        FROM sales 
        WHERE YEAR(sale_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
        AND MONTH(sale_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
    ");
    $stmt->execute();
    $lastMonth = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastMonth['revenue'] > 0) {
        $response['revenue_growth'] = round((($response['month_revenue'] - $lastMonth['revenue']) / $lastMonth['revenue']) * 100, 1);
    } else {
        $response['revenue_growth'] = $response['month_revenue'] > 0 ? 100 : 0;
    }
    
    if ($lastMonth['orders'] > 0) {
        $response['orders_growth'] = round((($response['month_orders'] - $lastMonth['orders']) / $lastMonth['orders']) * 100, 1);
    } else {
        $response['orders_growth'] = $response['month_orders'] > 0 ? 100 : 0;
    }
    
    // Additional business metrics
    $response['total_brands'] = $db->query("SELECT COUNT(*) FROM brands WHERE is_active = 1")->fetchColumn();
    $response['total_categories'] = $db->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
    $response['total_suppliers'] = $db->query("SELECT COUNT(*) FROM suppliers WHERE is_active = 1")->fetchColumn();
    
    // Inventory turnover (simplified)
    $response['inventory_items'] = $db->query("SELECT COUNT(*) FROM inventory WHERE quantity > 0")->fetchColumn();
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>

