<?php
ob_start(); // Add this line at the very top
ob_clean();
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate unique SKU
 */
function generateSKU($prefix = 'TLB', $length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $sku = $prefix . '-';
    
    for ($i = 0; $i < $length; $i++) {
        $sku .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $sku;
}

/**
 * Generate EAN13 barcode
 */
function generateEAN13() {
    // Generate 12 digits
    $code = '';
    for ($i = 0; $i < 12; $i++) {
        $code .= rand(0, 9);
    }
    
    // Calculate check digit
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += $code[$i] * (($i % 2 == 0) ? 1 : 3);
    }
    
    $checkDigit = (10 - ($sum % 10)) % 10;
    
    return $code . $checkDigit;
}

/**
 * Format currency
 */
function formatCurrency($amount, $symbol = CURRENCY_SYMBOL) {
    return $symbol . ' ' . number_format($amount, DECIMAL_PLACES);
}

/**
 * Format date
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    return $date->format($format);
}

/**
 * Generate invoice number
 */
function generateInvoiceNumber($prefix = 'INV') {
    return $prefix . '-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Generate sale number
 */
function generateSaleNumber($prefix = 'SALE') {
    return $prefix . '-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

/**
 * Upload file
 */
function uploadFile($file, $uploadDir, $allowedTypes = ALLOWED_IMAGE_TYPES) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }

    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmpName = $file['tmp_name'];
    $fileError = $file['error'];

    if ($fileError !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }

    if ($fileSize > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size too large'];
    }

    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    $newFileName = uniqid() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFileName;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (move_uploaded_file($fileTmpName, $uploadPath)) {
        return ['success' => true, 'filename' => $newFileName, 'path' => $uploadPath];
    }

    return ['success' => false, 'message' => 'Failed to upload file'];
}

/**
 * Delete file
 */
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $tableName, $recordId = null, $oldValues = null, $newValues = null) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "INSERT INTO activity_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                 VALUES (:user_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':table_name', $tableName);
        $stmt->bindParam(':record_id', $recordId);
        $stmt->bindParam(':old_values', $oldValues ? json_encode($oldValues) : null);
        $stmt->bindParam(':new_values', $newValues ? json_encode($newValues) : null);
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'] ?? null);
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? null);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get request data (JSON or form data)
 */
function getRequestData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
    
    return $_REQUEST;
}

/**
 * Validate required fields
 */
function validateRequiredFields($data, $requiredFields) {
    $missing = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing[] = $field;
        }
    }
    
    return $missing;
}

/**
 * Calculate tax amount
 */
function calculateTax($amount, $taxRate = DEFAULT_TAX_RATE) {
    return ($amount * $taxRate) / 100;
}

/**
 * Get low stock products
 */
function getLowStockProducts() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT p.id, p.name, p.sku, i.quantity, p.min_stock_level
                 FROM products p
                 LEFT JOIN inventory i ON p.id = i.product_id
                 WHERE i.quantity <= p.min_stock_level AND p.is_active = 1
                 ORDER BY i.quantity ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Low stock query error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $stats = [];
        
        // Total products
        $query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['total_products'] = $stmt->fetch()['total'];
        
        // Low stock products
        $query = "SELECT COUNT(*) as total FROM products p 
                 LEFT JOIN inventory i ON p.id = i.product_id 
                 WHERE i.quantity <= p.min_stock_level AND p.is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['low_stock_products'] = $stmt->fetch()['total'];
        
        // Today's sales
        $query = "SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as amount 
                 FROM sales WHERE DATE(sale_date) = CURDATE()";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $todaySales = $stmt->fetch();
        $stats['today_sales_count'] = $todaySales['total'];
        $stats['today_sales_amount'] = $todaySales['amount'];
        
        // Total customers
        $query = "SELECT COUNT(*) as total FROM customers WHERE is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['total_customers'] = $stmt->fetch()['total'];
        
        return $stats;
    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
        return [];
    }
}

/**
 * Pagination helper
 */
function paginate($query, $params = [], $page = 1, $itemsPerPage = ITEMS_PER_PAGE) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Count total records
        $countQuery = "SELECT COUNT(*) as total FROM (" . $query . ") as count_table";
        $countStmt = $db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalRecords = $countStmt->fetch()['total'];
        
        // Calculate pagination
        $totalPages = ceil($totalRecords / $itemsPerPage);
        $offset = ($page - 1) * $itemsPerPage;
        
        // Get paginated results
        $paginatedQuery = $query . " LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($paginatedQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(),
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'items_per_page' => $itemsPerPage,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ];
    } catch (Exception $e) {
        error_log("Pagination error: " . $e->getMessage());
        return ['data' => [], 'pagination' => []];
    }
}
?>