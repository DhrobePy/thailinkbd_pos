<?php
header('Content-Type: application/json');

// Simple database debug without authentication
require_once 'config/config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $response = [];
    
    // Test database connection
    $response['connection'] = 'Connected successfully';
    $response['database_name'] = $db->query('SELECT DATABASE()')->fetchColumn();
    
    // Check which tables exist
    $tableCheckQuery = "SHOW TABLES";
    $stmt = $db->prepare($tableCheckQuery);
    $stmt->execute();
    $existingTables = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $existingTables[] = $row[0];
    }
    $response['existing_tables'] = $existingTables;
    
    // Check each table
    foreach ($existingTables as $table) {
        $response['tables'][$table] = [];
        
        // Get table structure
        $response['tables'][$table]['structure'] = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        
        // Get row count
        $response['tables'][$table]['count'] = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        
        // Get sample data (first 5 rows)
        $response['tables'][$table]['sample_data'] = $db->query("SELECT * FROM $table LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Specific queries for dashboard data
    $response['dashboard_queries'] = [];
    
    if (in_array('products', $existingTables)) {
        // Total products
        $query = "SELECT COUNT(*) as total FROM products";
        $result = $db->query($query)->fetch(PDO::FETCH_ASSOC);
        $response['dashboard_queries']['total_products'] = [
            'query' => $query,
            'result' => $result
        ];
        
        // Check if is_active column exists
        $columns = $db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('is_active', $columns)) {
            $query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
            $result = $db->query($query)->fetch(PDO::FETCH_ASSOC);
            $response['dashboard_queries']['active_products'] = [
                'query' => $query,
                'result' => $result
            ];
        }
        
        // Low stock check
        if (in_array('current_stock', $columns) && in_array('min_stock_level', $columns)) {
            $query = "SELECT COUNT(*) as total FROM products WHERE current_stock <= min_stock_level";
            $result = $db->query($query)->fetch(PDO::FETCH_ASSOC);
            $response['dashboard_queries']['low_stock'] = [
                'query' => $query,
                'result' => $result
            ];
        }
        
        // Inventory value
        if (in_array('cost_price', $columns) && in_array('current_stock', $columns)) {
            $query = "SELECT SUM(current_stock * cost_price) as total FROM products";
            $result = $db->query($query)->fetch(PDO::FETCH_ASSOC);
            $response['dashboard_queries']['inventory_value'] = [
                'query' => $query,
                'result' => $result
            ];
        }
    }
    
    if (in_array('customers', $existingTables)) {
        $query = "SELECT COUNT(*) as total FROM customers";
        $result = $db->query($query)->fetch(PDO::FETCH_ASSOC);
        $response['dashboard_queries']['total_customers'] = [
            'query' => $query,
            'result' => $result
        ];
    }
    
    if (in_array('sales', $existingTables)) {
        // Today's sales
        $query = "SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue FROM sales WHERE DATE(created_at) = CURDATE()";
        $result = $db->query($query)->fetch(PDO::FETCH_ASSOC);
        $response['dashboard_queries']['today_sales'] = [
            'query' => $query,
            'result' => $result
        ];
        
        // All sales
        $query = "SELECT COUNT(*) as total FROM sales";
        $result = $db->query($query)->fetch(PDO::FETCH_ASSOC);
        $response['dashboard_queries']['all_sales'] = [
            'query' => $query,
            'result' => $result
        ];
    }
    
    if (in_array('invoices', $existingTables)) {
        $query = "SELECT COUNT(*) as total FROM invoices";
        $result = $db->query($query)->fetch(PDO::FETCH_ASSOC);
        $response['dashboard_queries']['total_invoices'] = [
            'query' => $query,
            'result' => $result
        ];
        
        // Check status column
        $columns = $db->query("SHOW COLUMNS FROM invoices")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('status', $columns)) {
            $query = "SELECT status, COUNT(*) as count FROM invoices GROUP BY status";
            $result = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
            $response['dashboard_queries']['invoice_status'] = [
                'query' => $query,
                'result' => $result
            ];
        }
    }
    
    $response['timestamp'] = date('Y-m-d H:i:s');
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error occurred',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>

