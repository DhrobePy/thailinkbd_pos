<?php
/**
 * Test Database Connection and User Table
 * Upload this to your server and run it to see what's wrong
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

// Test 1: Check if config files exist
echo "<h2>1. File Check</h2>";
if (file_exists('config/database.php')) {
    echo "✅ config/database.php exists<br>";
} else {
    echo "❌ config/database.php NOT found<br>";
}

if (file_exists('includes/auth.php')) {
    echo "✅ includes/auth.php exists<br>";
} else {
    echo "❌ includes/auth.php NOT found<br>";
}

// Test 2: Include files and test database connection
echo "<h2>2. Database Connection Test</h2>";
try {
    require_once 'config/database.php';
    echo "✅ Database config loaded<br>";
    
    $database = new Database();
    echo "✅ Database class instantiated<br>";
    
    $db = $database->getConnection();
    echo "✅ Database connection established<br>";
    
    // Test 3: Check if users table exists
    echo "<h2>3. Users Table Check</h2>";
    $stmt = $db->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✅ Users table exists<br>";
        
        // Test 4: Check users in table
        echo "<h2>4. Users Data Check</h2>";
        $stmt = $db->prepare("SELECT username, email, role, is_active FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        echo "Found " . count($users) . " users:<br>";
        foreach ($users as $user) {
            $status = $user['is_active'] ? 'Active' : 'Inactive';
            echo "- {$user['username']} ({$user['email']}) - Role: {$user['role']} - Status: $status<br>";
        }
        
        // Test 5: Check specific admin user
        echo "<h2>5. Admin User Check</h2>";
        $stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "✅ Admin user found<br>";
            echo "- ID: {$admin['id']}<br>";
            echo "- Username: {$admin['username']}<br>";
            echo "- Email: {$admin['email']}<br>";
            echo "- Role: {$admin['role']}<br>";
            echo "- Active: " . ($admin['is_active'] ? 'Yes' : 'No') . "<br>";
            echo "- Password Hash: " . (strlen($admin['password']) > 10 ? 'Present (' . strlen($admin['password']) . ' chars)' : 'Missing or too short') . "<br>";
            
            // Test password verification
            echo "<h2>6. Password Test</h2>";
            if (password_verify('admin123', $admin['password'])) {
                echo "✅ Password 'admin123' is correct<br>";
            } else {
                echo "❌ Password 'admin123' does NOT match<br>";
                echo "Trying to create new hash for 'admin123':<br>";
                $newHash = password_hash('admin123', PASSWORD_DEFAULT);
                echo "New hash: $newHash<br>";
            }
        } else {
            echo "❌ Admin user NOT found<br>";
            echo "Creating admin user...<br>";
            
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute(['admin', 'admin@thailinkbd.com', $hashedPassword, 'System Administrator', 'admin', 1]);
            
            if ($result) {
                echo "✅ Admin user created successfully<br>";
            } else {
                echo "❌ Failed to create admin user<br>";
            }
        }
        
    } else {
        echo "❌ Users table does NOT exist<br>";
        echo "You need to import the database schema first!<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<h2>7. PHP Info</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "PDO Available: " . (extension_loaded('pdo') ? 'Yes' : 'No') . "<br>";
echo "PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "<br>";

echo "<h2>Test Complete</h2>";
echo "If you see errors above, that's what's preventing login from working!";
?>
