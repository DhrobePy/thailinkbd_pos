<?php
/**
 * Reports API Endpoint
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
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'sales_summary':
                $startDate = sanitizeInput($_GET['start_date'] ?? date('Y-m-01'));
                $endDate = sanitizeInput($_GET['end_date'] ?? date('Y-m-d'));
                $groupBy = sanitizeInput($_GET['group_by'] ?? 'day'); // day, week, month
                
                try {
                    $dateFormat = match($groupBy) {
                        'week' => '%Y-%u',
                        'month' => '%Y-%m',
                        default => '%Y-%m-%d'
                    };
                    
                    $query = "SELECT 
                             DATE_FORMAT(sale_date, '$dateFormat') as period,
                             DATE(sale_date) as sale_date,
                             COUNT(*) as total_sales,
                             SUM(total_amount) as total_revenue,
                             SUM(subtotal) as total_subtotal,
                             SUM(tax_amount) as total_tax,
                             SUM(discount_amount) as total_discount,
                             AVG(total_amount) as average_sale
                             FROM sales 
                             WHERE DATE(sale_date) BETWEEN :start_date AND :end_date
                             GROUP BY period
                             ORDER BY sale_date ASC";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':start_date', $startDate);
                    $stmt->bindParam(':end_date', $endDate);
                    $stmt->execute();
                    
                    $salesData = $stmt->fetchAll();
                    
                    // Calculate totals
                    $totalSales = array_sum(array_column($salesData, 'total_sales'));
                    $totalRevenue = array_sum(array_column($salesData, 'total_revenue'));
                    $totalDiscount = array_sum(array_column($salesData, 'total_discount'));
                    $averageSale = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
                    
                    sendJsonResponse([
                        'sales_data' => $salesData,
                        'summary' => [
                            'total_sales' => $totalSales,
                            'total_revenue' => $totalRevenue,
                            'total_discount' => $totalDiscount,
                            'average_sale' => $averageSale,
                            'period' => $startDate . ' to ' . $endDate
                        ]
                    ]);
                } catch (Exception $e) {
                    error_log("Sales summary error: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to generate sales summary'], 500);
                }
                break;
                
            case 'top_products':
                $startDate = sanitizeInput($_GET['start_date'] ?? date('Y-m-01'));
                $endDate = sanitizeInput($_GET['end_date'] ?? date('Y-m-d'));
                $limit = intval($_GET['limit'] ?? 10);
                
                try {
                    $query = "SELECT 
                             p.id, p.name, p.sku, p.selling_price,
                             c.name as category_name,
                             b.name as brand_name,
                             SUM(si.quantity) as total_quantity_sold,
                             SUM(si.total_price) as total_revenue,
                             COUNT(DISTINCT s.id) as total_transactions,
                             AVG(si.unit_price) as average_price
                             FROM sale_items si
                             JOIN sales s ON si.sale_id = s.id
                             JOIN products p ON si.product_id = p.id
                             LEFT JOIN categories c ON p.category_id = c.id
                             LEFT JOIN brands b ON p.brand_id = b.id
                             WHERE DATE(s.sale_date) BETWEEN :start_date AND :end_date
                             GROUP BY p.id
                             ORDER BY total_quantity_sold DESC
                             LIMIT :limit";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':start_date', $startDate);
                    $stmt->bindParam(':end_date', $endDate);
                    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    sendJsonResponse(['top_products' => $stmt->fetchAll()]);
                } catch (Exception $e) {
                    error_log("Top products error: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to generate top products report'], 500);
                }
                break;
                
            case 'inventory_report':
                $categoryId = intval($_GET['category_id'] ?? 0);
                $lowStock = intval($_GET['low_stock'] ?? 0);
                
                try {
                    $query = "SELECT 
                             p.id, p.name, p.sku, p.barcode, p.cost_price, p.selling_price,
                             p.min_stock_level, p.reorder_point,
                             c.name as category_name,
                             b.name as brand_name,
                             s.name as supplier_name,
                             COALESCE(SUM(i.quantity), 0) as current_stock,
                             COALESCE(SUM(i.reserved_quantity), 0) as reserved_stock,
                             (COALESCE(SUM(i.quantity), 0) - COALESCE(SUM(i.reserved_quantity), 0)) as available_stock,
                             (COALESCE(SUM(i.quantity), 0) * p.cost_price) as stock_value
                             FROM products p
                             LEFT JOIN categories c ON p.category_id = c.id
                             LEFT JOIN brands b ON p.brand_id = b.id
                             LEFT JOIN suppliers s ON p.supplier_id = s.id
                             LEFT JOIN inventory i ON p.id = i.product_id
                             WHERE p.is_active = 1 AND p.track_inventory = 1";
                    
                    $params = [];
                    
                    if ($categoryId > 0) {
                        $query .= " AND p.category_id = :category_id";
                        $params[':category_id'] = $categoryId;
                    }
                    
                    $query .= " GROUP BY p.id";
                    
                    if ($lowStock) {
                        $query .= " HAVING current_stock <= p.min_stock_level";
                    }
                    
                    $query .= " ORDER BY p.name ASC";
                    
                    $stmt = $db->prepare($query);
                    foreach ($params as $key => $value) {
                        $stmt->bindValue($key, $value);
                    }
                    $stmt->execute();
                    
                    $inventoryData = $stmt->fetchAll();
                    
                    // Calculate summary
                    $totalProducts = count($inventoryData);
                    $totalStockValue = array_sum(array_column($inventoryData, 'stock_value'));
                    $lowStockCount = count(array_filter($inventoryData, function($item) {
                        return $item['current_stock'] <= $item['min_stock_level'];
                    }));
                    
                    sendJsonResponse([
                        'inventory_data' => $inventoryData,
                        'summary' => [
                            'total_products' => $totalProducts,
                            'total_stock_value' => $totalStockValue,
                            'low_stock_count' => $lowStockCount
                        ]
                    ]);
                } catch (Exception $e) {
                    error_log("Inventory report error: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to generate inventory report'], 500);
                }
                break;
                
            case 'customer_analysis':
                $startDate = sanitizeInput($_GET['start_date'] ?? date('Y-m-01'));
                $endDate = sanitizeInput($_GET['end_date'] ?? date('Y-m-d'));
                $limit = intval($_GET['limit'] ?? 10);
                
                try {
                    $query = "SELECT 
                             c.id, c.name, c.email, c.phone, c.customer_type,
                             COUNT(s.id) as total_orders,
                             SUM(s.total_amount) as total_spent,
                             AVG(s.total_amount) as average_order_value,
                             MAX(s.sale_date) as last_purchase_date,
                             MIN(s.sale_date) as first_purchase_date
                             FROM customers c
                             LEFT JOIN sales s ON c.id = s.customer_id
                             WHERE c.is_active = 1 
                             AND (s.sale_date IS NULL OR DATE(s.sale_date) BETWEEN :start_date AND :end_date)
                             GROUP BY c.id
                             HAVING total_orders > 0
                             ORDER BY total_spent DESC
                             LIMIT :limit";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':start_date', $startDate);
                    $stmt->bindParam(':end_date', $endDate);
                    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    sendJsonResponse(['customer_analysis' => $stmt->fetchAll()]);
                } catch (Exception $e) {
                    error_log("Customer analysis error: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to generate customer analysis'], 500);
                }
                break;
                
            case 'profit_loss':
                $startDate = sanitizeInput($_GET['start_date'] ?? date('Y-m-01'));
                $endDate = sanitizeInput($_GET['end_date'] ?? date('Y-m-d'));
                
                try {
                    // Calculate revenue
                    $revenueQuery = "SELECT 
                                    SUM(total_amount) as total_revenue,
                                    SUM(tax_amount) as total_tax,
                                    SUM(discount_amount) as total_discount
                                    FROM sales 
                                    WHERE DATE(sale_date) BETWEEN :start_date AND :end_date";
                    
                    $revenueStmt = $db->prepare($revenueQuery);
                    $revenueStmt->bindParam(':start_date', $startDate);
                    $revenueStmt->bindParam(':end_date', $endDate);
                    $revenueStmt->execute();
                    $revenueData = $revenueStmt->fetch();
                    
                    // Calculate cost of goods sold
                    $cogsQuery = "SELECT 
                                 SUM(si.quantity * p.cost_price) as total_cogs
                                 FROM sale_items si
                                 JOIN sales s ON si.sale_id = s.id
                                 JOIN products p ON si.product_id = p.id
                                 WHERE DATE(s.sale_date) BETWEEN :start_date AND :end_date";
                    
                    $cogsStmt = $db->prepare($cogsQuery);
                    $cogsStmt->bindParam(':start_date', $startDate);
                    $cogsStmt->bindParam(':end_date', $endDate);
                    $cogsStmt->execute();
                    $cogsData = $cogsStmt->fetch();
                    
                    $totalRevenue = floatval($revenueData['total_revenue'] ?? 0);
                    $totalTax = floatval($revenueData['total_tax'] ?? 0);
                    $totalDiscount = floatval($revenueData['total_discount'] ?? 0);
                    $totalCogs = floatval($cogsData['total_cogs'] ?? 0);
                    
                    $netRevenue = $totalRevenue - $totalTax;
                    $grossProfit = $netRevenue - $totalCogs;
                    $grossProfitMargin = $netRevenue > 0 ? ($grossProfit / $netRevenue) * 100 : 0;
                    
                    sendJsonResponse([
                        'profit_loss' => [
                            'total_revenue' => $totalRevenue,
                            'total_tax' => $totalTax,
                            'total_discount' => $totalDiscount,
                            'net_revenue' => $netRevenue,
                            'total_cogs' => $totalCogs,
                            'gross_profit' => $grossProfit,
                            'gross_profit_margin' => $grossProfitMargin,
                            'period' => $startDate . ' to ' . $endDate
                        ]
                    ]);
                } catch (Exception $e) {
                    error_log("Profit loss error: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to generate profit/loss report'], 500);
                }
                break;
                
            case 'category_performance':
                $startDate = sanitizeInput($_GET['start_date'] ?? date('Y-m-01'));
                $endDate = sanitizeInput($_GET['end_date'] ?? date('Y-m-d'));
                
                try {
                    $query = "SELECT 
                             c.id, c.name as category_name,
                             COUNT(DISTINCT p.id) as total_products,
                             SUM(si.quantity) as total_quantity_sold,
                             SUM(si.total_price) as total_revenue,
                             AVG(si.unit_price) as average_price,
                             COUNT(DISTINCT s.id) as total_transactions
                             FROM categories c
                             LEFT JOIN products p ON c.id = p.category_id
                             LEFT JOIN sale_items si ON p.id = si.product_id
                             LEFT JOIN sales s ON si.sale_id = s.id
                             WHERE c.is_active = 1 
                             AND (s.sale_date IS NULL OR DATE(s.sale_date) BETWEEN :start_date AND :end_date)
                             GROUP BY c.id
                             ORDER BY total_revenue DESC";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':start_date', $startDate);
                    $stmt->bindParam(':end_date', $endDate);
                    $stmt->execute();
                    
                    sendJsonResponse(['category_performance' => $stmt->fetchAll()]);
                } catch (Exception $e) {
                    error_log("Category performance error: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to generate category performance report'], 500);
                }
                break;
                
            case 'payment_methods':
                $startDate = sanitizeInput($_GET['start_date'] ?? date('Y-m-01'));
                $endDate = sanitizeInput($_GET['end_date'] ?? date('Y-m-d'));
                
                try {
                    $query = "SELECT 
                             payment_method,
                             COUNT(*) as transaction_count,
                             SUM(total_amount) as total_amount,
                             AVG(total_amount) as average_amount
                             FROM sales 
                             WHERE DATE(sale_date) BETWEEN :start_date AND :end_date
                             GROUP BY payment_method
                             ORDER BY total_amount DESC";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':start_date', $startDate);
                    $stmt->bindParam(':end_date', $endDate);
                    $stmt->execute();
                    
                    sendJsonResponse(['payment_methods' => $stmt->fetchAll()]);
                } catch (Exception $e) {
                    error_log("Payment methods error: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to generate payment methods report'], 500);
                }
                break;
                
            case 'daily_summary':
                $date = sanitizeInput($_GET['date'] ?? date('Y-m-d'));
                
                try {
                    // Sales summary
                    $salesQuery = "SELECT 
                                  COUNT(*) as total_sales,
                                  SUM(total_amount) as total_revenue,
                                  AVG(total_amount) as average_sale,
                                  SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END) as cash_sales,
                                  SUM(CASE WHEN payment_method = 'card' THEN total_amount ELSE 0 END) as card_sales,
                                  SUM(CASE WHEN payment_method = 'mobile' THEN total_amount ELSE 0 END) as mobile_sales
                                  FROM sales 
                                  WHERE DATE(sale_date) = :date";
                    
                    $salesStmt = $db->prepare($salesQuery);
                    $salesStmt->bindParam(':date', $date);
                    $salesStmt->execute();
                    $salesSummary = $salesStmt->fetch();
                    
                    // Top selling products
                    $topProductsQuery = "SELECT 
                                        p.name, p.sku,
                                        SUM(si.quantity) as quantity_sold,
                                        SUM(si.total_price) as revenue
                                        FROM sale_items si
                                        JOIN sales s ON si.sale_id = s.id
                                        JOIN products p ON si.product_id = p.id
                                        WHERE DATE(s.sale_date) = :date
                                        GROUP BY p.id
                                        ORDER BY quantity_sold DESC
                                        LIMIT 5";
                    
                    $topProductsStmt = $db->prepare($topProductsQuery);
                    $topProductsStmt->bindParam(':date', $date);
                    $topProductsStmt->execute();
                    $topProducts = $topProductsStmt->fetchAll();
                    
                    // Hourly sales
                    $hourlySalesQuery = "SELECT 
                                        HOUR(sale_date) as hour,
                                        COUNT(*) as sales_count,
                                        SUM(total_amount) as revenue
                                        FROM sales 
                                        WHERE DATE(sale_date) = :date
                                        GROUP BY HOUR(sale_date)
                                        ORDER BY hour ASC";
                    
                    $hourlySalesStmt = $db->prepare($hourlySalesQuery);
                    $hourlySalesStmt->bindParam(':date', $date);
                    $hourlySalesStmt->execute();
                    $hourlySales = $hourlySalesStmt->fetchAll();
                    
                    sendJsonResponse([
                        'daily_summary' => [
                            'date' => $date,
                            'sales_summary' => $salesSummary,
                            'top_products' => $topProducts,
                            'hourly_sales' => $hourlySales
                        ]
                    ]);
                } catch (Exception $e) {
                    error_log("Daily summary error: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to generate daily summary'], 500);
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

