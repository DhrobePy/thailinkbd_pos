<?php
/**
 * Debug Authentication API - Shows detailed error messages
 * Replace your api/auth.php with this temporarily to see actual errors
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

try {
    // Include required files
    require_once '../config/database.php';
    require_once '../includes/auth.php';

    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON data received',
            'debug_info' => [
                'raw_input' => $input,
                'json_error' => json_last_error_msg()
            ]
        ]);
        exit;
    }

    $action = $data['action'] ?? '';

    switch ($action) {
        case 'login':
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            if (empty($username) || empty($password)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Username and password are required',
                    'debug_info' => [
                        'username_provided' => !empty($username),
                        'password_provided' => !empty($password)
                    ]
                ]);
                exit;
            }

            try {
                // Test database connection first
                $database = new Database();
                $db = $database->getConnection();
                
                if (!$db) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Database connection failed',
                        'debug_info' => [
                            'step' => 'database_connection',
                            'message' => 'Could not establish database connection'
                        ]
                    ]);
                    exit;
                }

                // Test if users table exists
                $stmt = $db->prepare("SHOW TABLES LIKE 'users'");
                $stmt->execute();
                $tableExists = $stmt->fetch();
                
                if (!$tableExists) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Users table does not exist',
                        'debug_info' => [
                            'step' => 'table_check',
                            'message' => 'The users table was not found in the database'
                        ]
                    ]);
                    exit;
                }

                // Try to find the user
                $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if (!$user) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'User not found or inactive',
                        'debug_info' => [
                            'step' => 'user_lookup',
                            'username' => $username,
                            'message' => 'No active user found with this username'
                        ]
                    ]);
                    exit;
                }

                // Check password
                if (!password_verify($password, $user['password'])) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Invalid password',
                        'debug_info' => [
                            'step' => 'password_verification',
                            'username' => $username,
                            'password_hash_exists' => !empty($user['password']),
                            'message' => 'Password verification failed'
                        ]
                    ]);
                    exit;
                }

                // Login successful - set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;

                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'full_name' => $user['full_name'],
                        'role' => $user['role'],
                        'email' => $user['email']
                    ],
                    'debug_info' => [
                        'step' => 'login_success',
                        'session_set' => true
                    ]
                ]);

            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Authentication error: ' . $e->getMessage(),
                    'debug_info' => [
                        'step' => 'exception_caught',
                        'exception_type' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]
                ]);
            }
            break;

        case 'logout':
            session_destroy();
            echo json_encode([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action',
                'debug_info' => [
                    'action_received' => $action,
                    'valid_actions' => ['login', 'logout']
                ]
            ]);
            break;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'System error: ' . $e->getMessage(),
        'debug_info' => [
            'step' => 'system_exception',
            'exception_type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>
