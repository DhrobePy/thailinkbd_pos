<?php
/**
 * Thai Link BD Inventory Management System
 * Installation Script
 */

// Check if already installed
if (file_exists('.env') && file_exists('config/installed.lock')) {
    die('System is already installed. Delete config/installed.lock to reinstall.');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Handle form submissions
if ($_POST) {
    switch ($step) {
        case 2:
            // Database configuration
            $dbHost = $_POST['db_host'] ?? 'localhost';
            $dbName = $_POST['db_name'] ?? 'thai_link_inventory';
            $dbUser = $_POST['db_user'] ?? '';
            $dbPass = $_POST['db_pass'] ?? '';
            
            if (empty($dbUser)) {
                $error = 'Database username is required';
                break;
            }
            
            // Test database connection
            try {
                $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Create database if it doesn't exist
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Create .env file
                $envContent = "DB_HOST=$dbHost\n";
                $envContent .= "DB_NAME=$dbName\n";
                $envContent .= "DB_USER=$dbUser\n";
                $envContent .= "DB_PASS=$dbPass\n";
                
                file_put_contents('.env', $envContent);
                
                $step = 3;
                $success = 'Database connection successful!';
            } catch (Exception $e) {
                $error = 'Database connection failed: ' . $e->getMessage();
            }
            break;
            
        case 3:
            // Import database schema
            try {
                require_once 'config/database.php';
                $database = new Database();
                $db = $database->getConnection();
                
                // Read and execute schema
                $schema = file_get_contents('database/schema.sql');
                $db->exec($schema);
                
                // Read and execute seed data
                $seedData = file_get_contents('database/seed.sql');
                $db->exec($seedData);
                
                $step = 4;
                $success = 'Database tables created successfully!';
            } catch (Exception $e) {
                $error = 'Database setup failed: ' . $e->getMessage();
            }
            break;
            
        case 4:
            // Admin user setup
            $username = $_POST['admin_username'] ?? '';
            $email = $_POST['admin_email'] ?? '';
            $password = $_POST['admin_password'] ?? '';
            $fullName = $_POST['admin_name'] ?? '';
            
            if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
                $error = 'All fields are required';
                break;
            }
            
            if (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters';
                break;
            }
            
            try {
                require_once 'config/database.php';
                $database = new Database();
                $db = $database->getConnection();
                
                // Update admin user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET username = :username, email = :email, password = :password, 
                         full_name = :full_name WHERE role = 'admin' LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':full_name', $fullName);
                $stmt->execute();
                
                // Create installation lock file
                if (!is_dir('config')) {
                    mkdir('config', 0755, true);
                }
                file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
                
                $step = 5;
                $success = 'Installation completed successfully!';
            } catch (Exception $e) {
                $error = 'Admin setup failed: ' . $e->getMessage();
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thai Link BD - Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto h-16 w-16 bg-blue-500 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-store text-white text-2xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-900">Thai Link BD</h2>
                <p class="text-gray-600 mt-2">Inventory Management System</p>
                <p class="text-sm text-gray-500 mt-1">Installation Wizard</p>
            </div>

            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                    <span>Step <?php echo $step; ?> of 5</span>
                    <span><?php echo round(($step / 5) * 100); ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo ($step / 5) * 100; ?>%"></div>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
            <!-- Step 1: Welcome -->
            <div class="text-center">
                <h3 class="text-xl font-semibold mb-4">Welcome to Thai Link BD</h3>
                <p class="text-gray-600 mb-6">This wizard will help you set up your inventory management system.</p>
                
                <div class="text-left bg-gray-50 p-4 rounded-lg mb-6">
                    <h4 class="font-medium mb-2">System Requirements:</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>PHP 7.4 or higher</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>MySQL 5.7 or higher</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Web server (Apache/Nginx)</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>PDO MySQL extension</li>
                    </ul>
                </div>
                
                <a href="?step=2" class="w-full bg-blue-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-600 inline-block">
                    Start Installation
                </a>
            </div>

            <?php elseif ($step == 2): ?>
            <!-- Step 2: Database Configuration -->
            <form method="POST">
                <h3 class="text-xl font-semibold mb-4">Database Configuration</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Database Host</label>
                        <input type="text" name="db_host" value="localhost" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Database Name</label>
                        <input type="text" name="db_name" value="thai_link_inventory" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Database Username</label>
                        <input type="text" name="db_user" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Database Password</label>
                        <input type="password" name="db_pass"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-blue-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-600 mt-6">
                    Test Connection & Continue
                </button>
            </form>

            <?php elseif ($step == 3): ?>
            <!-- Step 3: Database Setup -->
            <form method="POST">
                <h3 class="text-xl font-semibold mb-4">Database Setup</h3>
                <p class="text-gray-600 mb-6">Click the button below to create the database tables and import sample data.</p>
                
                <button type="submit" class="w-full bg-blue-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-600">
                    Create Database Tables
                </button>
            </form>

            <?php elseif ($step == 4): ?>
            <!-- Step 4: Admin User Setup -->
            <form method="POST">
                <h3 class="text-xl font-semibold mb-4">Admin User Setup</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" name="admin_name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" name="admin_username" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="admin_email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" name="admin_password" required minlength="6"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-blue-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-600 mt-6">
                    Create Admin User
                </button>
            </form>

            <?php elseif ($step == 5): ?>
            <!-- Step 5: Installation Complete -->
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-green-500 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-check text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">Installation Complete!</h3>
                <p class="text-gray-600 mb-6">Your Thai Link BD Inventory Management System has been successfully installed.</p>
                
                <div class="bg-blue-50 p-4 rounded-lg mb-6 text-left">
                    <h4 class="font-medium mb-2">Next Steps:</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>1. Delete the install.php file for security</li>
                        <li>2. Configure your .env file if needed</li>
                        <li>3. Set up SSL certificate (recommended)</li>
                        <li>4. Configure backups</li>
                    </ul>
                </div>
                
                <a href="index.php" class="w-full bg-green-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-600 inline-block">
                    Access Your System
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

