<?php
/**
 * Invoices API Endpoint
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
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                $page = intval($_GET['page'] ?? 1);
                $search = sanitizeInput($_GET['search'] ?? '');
                $status = sanitizeInput($_GET['status'] ?? '');
                $customerId = intval($_GET['customer_id'] ?? 0);
                
                $query = "SELECT i.*, c.name as customer_name, c.email as customer_email, 
                         c.phone as customer_phone, u.full_name as created_by
                         FROM invoices i
                         LEFT JOIN customers c ON i.customer_id = c.id
                         LEFT JOIN users u ON i.user_id = u.id
                         WHERE 1=1";
                
                $params = [];
                
                if (!empty($search)) {
                    $query .= " AND (i.invoice_number LIKE :search OR c.name LIKE :search)";
                    $params[':search'] = "%$search%";
                }
                
                if (!empty($status)) {
                    $query .= " AND i.status = :status";
                    $params[':status'] = $status;
                }
                
                if ($customerId > 0) {
                    $query .= " AND i.customer_id = :customer_id";
                    $params[':customer_id'] = $customerId;
                }
                
                $query .= " ORDER BY i.created_at DESC";
                
                $result = paginate($query, $params, $page);
                sendJsonResponse($result);
                break;
                
            case 'get':
                $id = intval($_GET['id'] ?? 0);
                
                if ($id <= 0) {
                    sendJsonResponse(['error' => 'Invalid invoice ID'], 400);
                }
                
                try {
                    // Get invoice details
                    $invoiceQuery = "SELECT i.*, c.name as customer_name, c.email as customer_email, 
                                    c.phone as customer_phone, c.address as customer_address,
                                    c.tax_number as customer_tax_number, u.full_name as created_by
                                    FROM invoices i
                                    LEFT JOIN customers c ON i.customer_id = c.id
                                    LEFT JOIN users u ON i.user_id = u.id
                                    WHERE i.id = :id";
                    
                    $invoiceStmt = $db->prepare($invoiceQuery);
                    $invoiceStmt->bindParam(':id', $id);
                    $invoiceStmt->execute();
                    
                    if ($invoiceStmt->rowCount() === 0) {
                        sendJsonResponse(['error' => 'Invoice not found'], 404);
                    }
                    
                    $invoice = $invoiceStmt->fetch();
                    
                    // Get invoice items
                    $itemsQuery = "SELECT ii.*, p.name as product_name, p.sku, pv.variant_name
                                  FROM invoice_items ii
                                  LEFT JOIN products p ON ii.product_id = p.id
                                  LEFT JOIN product_variants pv ON ii.variant_id = pv.id
                                  WHERE ii.invoice_id = :invoice_id
                                  ORDER BY ii.id ASC";
                    
                    $itemsStmt = $db->prepare($itemsQuery);
                    $itemsStmt->bindParam(':invoice_id', $id);
                    $itemsStmt->execute();
                    $invoice['items'] = $itemsStmt->fetchAll();
                    
                    // Get payments
                    $paymentsQuery = "SELECT p.*, u.full_name as recorded_by
                                     FROM payments p
                                     LEFT JOIN users u ON p.user_id = u.id
                                     WHERE p.reference_type = 'invoice' AND p.reference_id = :invoice_id
                                     ORDER BY p.payment_date DESC";
                    
                    $paymentsStmt = $db->prepare($paymentsQuery);
                    $paymentsStmt->bindParam(':invoice_id', $id);
                    $paymentsStmt->execute();
                    $invoice['payments'] = $paymentsStmt->fetchAll();
                    
                    sendJsonResponse(['invoice' => $invoice]);
                } catch (Exception $e) {
                    error_log("Get invoice error: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to retrieve invoice'], 500);
                }
                break;
                
            case 'generate_pdf':
                $id = intval($_GET['id'] ?? 0);
                
                if ($id <= 0) {
                    sendJsonResponse(['error' => 'Invalid invoice ID'], 400);
                }
                
                try {
                    // Get invoice data (reuse the get logic)
                    $invoiceQuery = "SELECT i.*, c.name as customer_name, c.email as customer_email, 
                                    c.phone as customer_phone, c.address as customer_address,
                                    c.tax_number as customer_tax_number
                                    FROM invoices i
                                    LEFT JOIN customers c ON i.customer_id = c.id
                                    WHERE i.id = :id";
                    
                    $invoiceStmt = $db->prepare($invoiceQuery);
                    $invoiceStmt->bindParam(':id', $id);
                    $invoiceStmt->execute();
                    
                    if ($invoiceStmt->rowCount() === 0) {
                        sendJsonResponse(['error' => 'Invoice not found'], 404);
                    }
                    
                    $invoice = $invoiceStmt->fetch();
                    
                    // Get invoice items
                    $itemsQuery = "SELECT ii.*, p.name as product_name, p.sku, pv.variant_name
                                  FROM invoice_items ii
                                  LEFT JOIN products p ON ii.product_id = p.id
                                  LEFT JOIN product_variants pv ON ii.variant_id = pv.id
                                  WHERE ii.invoice_id = :invoice_id
                                  ORDER BY ii.id ASC";
                    
                    $itemsStmt = $db->prepare($itemsQuery);
                    $itemsStmt->bindParam(':invoice_id', $id);
                    $itemsStmt->execute();
                    $invoice['items'] = $itemsStmt->fetchAll();
                    
                    // Generate PDF
                    $pdfPath = generateInvoicePDF($invoice);
                    
                    if ($pdfPath) {
                        // Return PDF file
                        header('Content-Type: application/pdf');
                        header('Content-Disposition: attachment; filename="invoice-' . $invoice['invoice_number'] . '.pdf"');
                        header('Content-Length: ' . filesize($pdfPath));
                        readfile($pdfPath);
                        
                        // Clean up temporary file
                        unlink($pdfPath);
                        exit;
                    } else {
                        sendJsonResponse(['error' => 'Failed to generate PDF'], 500);
                    }
                } catch (Exception $e) {
                    error_log("Generate PDF error: " . $e->getMessage());
                    sendJsonResponse(['error' => 'Failed to generate PDF'], 500);
                }
                break;
                
            default:
                sendJsonResponse(['error' => 'Invalid action'], 400);
        }
        break;
        
    case 'POST':
        $auth->requireAuth('staff');
        
        $requiredFields = ['customer_id', 'items'];
        $missing = validateRequiredFields($data, $requiredFields);
        
        if (!empty($missing)) {
            sendJsonResponse(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
        }
        
        $customerId = intval($data['customer_id']);
        $items = $data['items'];
        $invoiceDate = !empty($data['invoice_date']) ? $data['invoice_date'] : date('Y-m-d');
        $dueDate = !empty($data['due_date']) ? $data['due_date'] : date('Y-m-d', strtotime('+30 days'));
        $subtotal = floatval($data['subtotal'] ?? 0);
        $taxAmount = floatval($data['tax_amount'] ?? 0);
        $discountAmount = floatval($data['discount_amount'] ?? 0);
        $totalAmount = floatval($data['total_amount']);
        $notes = sanitizeInput($data['notes'] ?? '');
        $termsConditions = sanitizeInput($data['terms_conditions'] ?? '');
        
        if (empty($items) || !is_array($items)) {
            sendJsonResponse(['error' => 'No items in invoice'], 400);
        }
        
        if ($totalAmount <= 0) {
            sendJsonResponse(['error' => 'Invalid total amount'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            // Generate invoice number
            $invoiceNumber = generateInvoiceNumber();
            
            // Check if invoice number already exists
            $checkQuery = "SELECT id FROM invoices WHERE invoice_number = :invoice_number";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':invoice_number', $invoiceNumber);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $invoiceNumber = generateInvoiceNumber() . '-' . rand(100, 999);
            }
            
            // Insert invoice record
            $invoiceQuery = "INSERT INTO invoices (invoice_number, customer_id, user_id, invoice_date, 
                           due_date, subtotal, tax_amount, discount_amount, total_amount, balance_due, 
                           notes, terms_conditions) 
                           VALUES (:invoice_number, :customer_id, :user_id, :invoice_date, :due_date, 
                           :subtotal, :tax_amount, :discount_amount, :total_amount, :balance_due, 
                           :notes, :terms_conditions)";
            
            $invoiceStmt = $db->prepare($invoiceQuery);
            $user = $auth->getCurrentUser();
            $invoiceStmt->bindParam(':invoice_number', $invoiceNumber);
            $invoiceStmt->bindParam(':customer_id', $customerId);
            $invoiceStmt->bindParam(':user_id', $user['id']);
            $invoiceStmt->bindParam(':invoice_date', $invoiceDate);
            $invoiceStmt->bindParam(':due_date', $dueDate);
            $invoiceStmt->bindParam(':subtotal', $subtotal);
            $invoiceStmt->bindParam(':tax_amount', $taxAmount);
            $invoiceStmt->bindParam(':discount_amount', $discountAmount);
            $invoiceStmt->bindParam(':total_amount', $totalAmount);
            $invoiceStmt->bindParam(':balance_due', $totalAmount);
            $invoiceStmt->bindParam(':notes', $notes);
            $invoiceStmt->bindParam(':terms_conditions', $termsConditions);
            
            if (!$invoiceStmt->execute()) {
                throw new Exception('Failed to create invoice record');
            }
            
            $invoiceId = $db->lastInsertId();
            
            // Process each item
            foreach ($items as $item) {
                $productId = intval($item['product_id']);
                $variantId = intval($item['variant_id'] ?? 0) ?: null;
                $description = sanitizeInput($item['description']);
                $quantity = intval($item['quantity']);
                $unitPrice = floatval($item['unit_price']);
                $itemDiscountAmount = floatval($item['discount_amount'] ?? 0);
                $totalPrice = floatval($item['total_price']);
                
                if ($quantity <= 0) {
                    throw new Exception('Invalid quantity for item');
                }
                
                // Insert invoice item
                $itemQuery = "INSERT INTO invoice_items (invoice_id, product_id, variant_id, description, 
                             quantity, unit_price, discount_amount, total_price) 
                             VALUES (:invoice_id, :product_id, :variant_id, :description, :quantity, 
                             :unit_price, :discount_amount, :total_price)";
                
                $itemStmt = $db->prepare($itemQuery);
                $itemStmt->bindParam(':invoice_id', $invoiceId);
                $itemStmt->bindParam(':product_id', $productId);
                $itemStmt->bindParam(':variant_id', $variantId);
                $itemStmt->bindParam(':description', $description);
                $itemStmt->bindParam(':quantity', $quantity);
                $itemStmt->bindParam(':unit_price', $unitPrice);
                $itemStmt->bindParam(':discount_amount', $itemDiscountAmount);
                $itemStmt->bindParam(':total_price', $totalPrice);
                
                if (!$itemStmt->execute()) {
                    throw new Exception('Failed to create invoice item');
                }
            }
            
            $db->commit();
            
            logActivity($user['id'], 'CREATE', 'invoices', $invoiceId, null, $data);
            
            sendJsonResponse([
                'success' => true, 
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'message' => 'Invoice created successfully'
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Create invoice error: " . $e->getMessage());
            sendJsonResponse(['error' => $e->getMessage()], 500);
        }
        break;
        
    case 'PUT':
        $auth->requireAuth('staff');
        
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            sendJsonResponse(['error' => 'Invalid invoice ID'], 400);
        }
        
        try {
            // Get current invoice data for logging
            $currentQuery = "SELECT * FROM invoices WHERE id = :id";
            $currentStmt = $db->prepare($currentQuery);
            $currentStmt->bindParam(':id', $id);
            $currentStmt->execute();
            $currentData = $currentStmt->fetch();
            
            if (!$currentData) {
                sendJsonResponse(['error' => 'Invoice not found'], 404);
            }
            
            // Only allow updating draft invoices
            if ($currentData['status'] !== 'draft') {
                sendJsonResponse(['error' => 'Only draft invoices can be updated'], 400);
            }
            
            $query = "UPDATE invoices SET status = :status, notes = :notes, terms_conditions = :terms_conditions, 
                     updated_at = NOW() WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', sanitizeInput($data['status'] ?? $currentData['status']));
            $stmt->bindParam(':notes', sanitizeInput($data['notes'] ?? $currentData['notes']));
            $stmt->bindParam(':terms_conditions', sanitizeInput($data['terms_conditions'] ?? $currentData['terms_conditions']));
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $user = $auth->getCurrentUser();
                logActivity($user['id'], 'UPDATE', 'invoices', $id, $currentData, $data);
                
                sendJsonResponse(['success' => true, 'message' => 'Invoice updated successfully']);
            } else {
                sendJsonResponse(['error' => 'Failed to update invoice'], 500);
            }
        } catch (Exception $e) {
            error_log("Update invoice error: " . $e->getMessage());
            sendJsonResponse(['error' => 'Failed to update invoice'], 500);
        }
        break;
        
    case 'DELETE':
        $auth->requireAuth('manager');
        
        $id = intval($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            sendJsonResponse(['error' => 'Invalid invoice ID'], 400);
        }
        
        try {
            // Check if invoice can be deleted (only draft invoices)
            $checkQuery = "SELECT status FROM invoices WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            $invoice = $checkStmt->fetch();
            
            if (!$invoice) {
                sendJsonResponse(['error' => 'Invoice not found'], 404);
            }
            
            if ($invoice['status'] !== 'draft') {
                sendJsonResponse(['error' => 'Only draft invoices can be deleted'], 400);
            }
            
            $query = "UPDATE invoices SET status = 'cancelled', updated_at = NOW() WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $user = $auth->getCurrentUser();
                logActivity($user['id'], 'DELETE', 'invoices', $id);
                
                sendJsonResponse(['success' => true, 'message' => 'Invoice cancelled successfully']);
            } else {
                sendJsonResponse(['error' => 'Failed to cancel invoice'], 500);
            }
        } catch (Exception $e) {
            error_log("Delete invoice error: " . $e->getMessage());
            sendJsonResponse(['error' => 'Failed to cancel invoice'], 500);
        }
        break;
        
    default:
        sendJsonResponse(['error' => 'Method not allowed'], 405);
}

/**
 * Generate Invoice PDF
 */
function generateInvoicePDF($invoice) {
    try {
        require_once '../vendor/autoload.php'; // Assuming TCPDF or similar is installed
        
        // For now, return a simple HTML-based PDF generation
        // In production, you would use a proper PDF library like TCPDF or FPDF
        
        $html = generateInvoiceHTML($invoice);
        
        // Create temporary HTML file
        $tempHtmlFile = tempnam(sys_get_temp_dir(), 'invoice_') . '.html';
        file_put_contents($tempHtmlFile, $html);
        
        // Convert to PDF using wkhtmltopdf or similar
        $tempPdfFile = tempnam(sys_get_temp_dir(), 'invoice_') . '.pdf';
        
        // For demonstration, we'll just return the HTML file path
        // In production, implement proper PDF conversion
        return $tempHtmlFile;
        
    } catch (Exception $e) {
        error_log("PDF generation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate Invoice HTML
 */
function generateInvoiceHTML($invoice) {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Invoice ' . htmlspecialchars($invoice['invoice_number']) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .company-name { font-size: 24px; font-weight: bold; color: #333; }
            .invoice-title { font-size: 20px; margin: 20px 0; }
            .invoice-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
            .customer-info, .invoice-details { width: 45%; }
            .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .table th { background-color: #f2f2f2; }
            .totals { text-align: right; margin-top: 20px; }
            .total-row { margin: 5px 0; }
            .grand-total { font-weight: bold; font-size: 18px; }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="company-name">Thai Link BD</div>
            <div>House 123, Road 15, Dhanmondi, Dhaka-1205, Bangladesh</div>
            <div>Phone: +880-2-123456789 | Email: info@thailinkbd.com</div>
        </div>
        
        <div class="invoice-title">INVOICE</div>
        
        <div class="invoice-info">
            <div class="customer-info">
                <h3>Bill To:</h3>
                <div><strong>' . htmlspecialchars($invoice['customer_name']) . '</strong></div>
                <div>' . htmlspecialchars($invoice['customer_address'] ?? '') . '</div>
                <div>' . htmlspecialchars($invoice['customer_phone'] ?? '') . '</div>
                <div>' . htmlspecialchars($invoice['customer_email'] ?? '') . '</div>
            </div>
            
            <div class="invoice-details">
                <div><strong>Invoice #:</strong> ' . htmlspecialchars($invoice['invoice_number']) . '</div>
                <div><strong>Invoice Date:</strong> ' . date('M d, Y', strtotime($invoice['invoice_date'])) . '</div>
                <div><strong>Due Date:</strong> ' . date('M d, Y', strtotime($invoice['due_date'])) . '</div>
                <div><strong>Status:</strong> ' . ucfirst($invoice['status']) . '</div>
            </div>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Discount</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($invoice['items'] as $item) {
        $productName = $item['product_name'];
        if (!empty($item['variant_name'])) {
            $productName .= ' - ' . $item['variant_name'];
        }
        
        $html .= '<tr>
                    <td>' . htmlspecialchars($productName) . '<br><small>SKU: ' . htmlspecialchars($item['sku']) . '</small></td>
                    <td>' . $item['quantity'] . '</td>
                    <td>৳ ' . number_format($item['unit_price'], 2) . '</td>
                    <td>৳ ' . number_format($item['discount_amount'], 2) . '</td>
                    <td>৳ ' . number_format($item['total_price'], 2) . '</td>
                  </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <div class="totals">
            <div class="total-row">Subtotal: ৳ ' . number_format($invoice['subtotal'], 2) . '</div>
            <div class="total-row">Discount: ৳ ' . number_format($invoice['discount_amount'], 2) . '</div>
            <div class="total-row">Tax: ৳ ' . number_format($invoice['tax_amount'], 2) . '</div>
            <div class="total-row grand-total">Total: ৳ ' . number_format($invoice['total_amount'], 2) . '</div>
            <div class="total-row">Balance Due: ৳ ' . number_format($invoice['balance_due'], 2) . '</div>
        </div>';
    
    if (!empty($invoice['notes'])) {
        $html .= '<div style="margin-top: 30px;">
                    <h4>Notes:</h4>
                    <p>' . nl2br(htmlspecialchars($invoice['notes'])) . '</p>
                  </div>';
    }
    
    if (!empty($invoice['terms_conditions'])) {
        $html .= '<div style="margin-top: 20px;">
                    <h4>Terms & Conditions:</h4>
                    <p>' . nl2br(htmlspecialchars($invoice['terms_conditions'])) . '</p>
                  </div>';
    }
    
    $html .= '</body></html>';
    
    return $html;
}
?>

