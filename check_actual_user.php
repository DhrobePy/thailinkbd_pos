<?php
/**
 * Check Actual Users in Database
 * This will show you the real usernames and test passwords
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Actual Users in Your Database</h1>";

try {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all users
    $stmt = $db->prepare("SELECT id, username, email, full_name, role, is_active FROM users ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "<h2>Found " . count($users) . " users:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th><th>Active</th><th>Test Password</th></tr>";
    
    foreach ($users as $user) {
        $status = $user['is_active'] ? 'Yes' : 'No';
        
        // Test common passwords
        $stmt2 = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt2->execute([$user['id']]);
        $userWithPassword = $stmt2->fetch();
        
        $passwordTest = '';
        $testPasswords = ['admin123', 'password', '123456', 'admin', $user['username']];
        
        foreach ($testPasswords as $testPass) {
            if (password_verify($testPass, $userWithPassword['password'])) {
                $passwordTest = $testPass;
                break;
            }
        }
        
        if (!$passwordTest) {
            $passwordTest = 'Unknown - try admin123';
        }
        
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$user['username']}</strong></td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$status}</td>";
        echo "<td><strong style='color: green;'>{$passwordTest}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Try These Credentials:</h2>";
    foreach ($users as $user) {
        if ($user['is_active']) {
            // Test password
            $stmt2 = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt2->execute([$user['id']]);
            $userWithPassword = $stmt2->fetch();
            
            $passwordTest = '';
            $testPasswords = ['admin123', 'password', '123456', 'admin', $user['username']];
            
            foreach ($testPasswords as $testPass) {
                if (password_verify($testPass, $userWithPassword['password'])) {
                    $passwordTest = $testPass;
                    break;
                }
            }
            
            if (!$passwordTest) {
                $passwordTest = 'admin123';
            }
            
            echo "<div style='background: #f0f0f0; padding: 10px; margin: 5px; border-radius: 5px;'>";
            echo "<strong>Username:</strong> {$user['username']}<br>";
            echo "<strong>Password:</strong> {$passwordTest}<br>";
            echo "<strong>Role:</strong> {$user['role']}<br>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

