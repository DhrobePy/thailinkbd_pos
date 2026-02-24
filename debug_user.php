<?php
session_start();
require_once 'config/config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $response = [];
    
    // Check users table structure
    $response['users_structure'] = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current user data
    if (isset($_SESSION['user_id'])) {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $response['current_user'] = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['session_user_id'] = $_SESSION['user_id'];
    } else {
        $response['session_error'] = 'No user_id in session';
    }
    
    // Check products count with different filters
    $response['total_products_all'] = $db->query("SELECT COUNT(*) as count FROM products")->fetch(PDO::FETCH_ASSOC);
    $response['total_products_active'] = $db->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1")->fetch(PDO::FETCH_ASSOC);
    
    // Check inventory value calculation
    $response['inventory_value_all'] = $db->query("SELECT SUM(i.quantity * p.cost_price) as total FROM inventory i JOIN products p ON i.product_id = p.id")->fetch(PDO::FETCH_ASSOC);
    $response['inventory_value_active'] = $db->query("SELECT SUM(i.quantity * p.cost_price) as total FROM inventory i JOIN products p ON i.product_id = p.id WHERE p.is_active = 1")->fetch(PDO::FETCH_ASSOC);
    
    // Sample products data
    $response['products_sample'] = $db->query("SELECT id, name, is_active, cost_price FROM products LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    // Sample inventory data
    $response['inventory_sample'] = $db->query("SELECT i.*, p.name, p.cost_price FROM inventory i JOIN products p ON i.product_id = p.id LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>

