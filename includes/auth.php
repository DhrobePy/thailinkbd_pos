<?php
/**
 * Authentication System for Thai Link BD Inventory System
 */

// Include database configuration
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;

    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
        } catch (Exception $e) {
            error_log("Auth initialization error: " . $e->getMessage());
            throw new Exception("Authentication system initialization failed");
        }
    }

    /**
     * Login user with username/email and password
     */
    public function login($username, $password) {
        try {
            $query = "SELECT id, username, email, password, full_name, role, is_active 
                     FROM users 
                     WHERE (username = :username OR email = :email) 
                     AND is_active = 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $username); // Using username for email search too
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $user['password'])) {
                    // Update last login
                    $this->updateLastLogin($user['id']);
                    
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Log activity
                    $this->logActivity($user['id'], 'login', 'User logged in');
                    
                    return [
                        'success' => true,
                        'user' => [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'full_name' => $user['full_name'],
                            'role' => $user['role'],
                            'email' => $user['email']
                        ]
                    ];
                } else {
                    error_log("Login failed: Invalid password for user: " . $username);
                    return ['success' => false, 'error' => 'Invalid credentials'];
                }
            } else {
                error_log("Login failed: User not found: " . $username);
                return ['success' => false, 'error' => 'Invalid credentials'];
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Login failed'];
        }
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Get current logged in user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'role' => $_SESSION['role'] ?? '',
            'email' => $_SESSION['email'] ?? ''
        ];
    }

    /**
     * Logout user
     */
    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        // Destroy session
        session_destroy();
        
        return ['success' => true];
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId) {
        try {
            $query = "UPDATE users SET last_login = NOW() WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }

    /**
     * Log user activity
     */
    private function logActivity($userId, $action, $description) {
        try {
            $query = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at) 
                     VALUES (:user_id, :action, :description, :ip_address, :user_agent, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'] ?? '');
            $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Log activity error: " . $e->getMessage());
        }
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['role'] === $role;
    }

    /**
     * Check if user has permission for action
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $role = $_SESSION['role'];
        
        // Admin has all permissions
        if ($role === 'admin') {
            return true;
        }
        
        // Manager permissions
        if ($role === 'manager') {
            $managerPermissions = [
                'view_products', 'add_products', 'edit_products',
                'view_inventory', 'adjust_inventory',
                'view_sales', 'create_sales',
                'view_customers', 'add_customers', 'edit_customers',
                'view_reports'
            ];
            return in_array($permission, $managerPermissions);
        }
        
        // Staff permissions
        if ($role === 'staff') {
            $staffPermissions = [
                'view_products',
                'view_inventory',
                'create_sales',
                'view_customers'
            ];
            return in_array($permission, $staffPermissions);
        }
        
        return false;
    }

    /**
     * Require login (redirect if not logged in)
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /modules/auth/login.php');
            exit;
        }
    }

    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireLogin();
        
        if (!$this->hasRole($role)) {
            http_response_code(403);
            die('Access denied. Insufficient permissions.');
        }
    }

    /**
     * Require specific permission
     */
    public function requirePermission($permission) {
        $this->requireLogin();
        
        if (!$this->hasPermission($permission)) {
            http_response_code(403);
            die('Access denied. Insufficient permissions.');
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        try {
            $query = "SELECT id, username, email, full_name, role, phone, is_active, created_at, last_login 
                     FROM users WHERE id = :user_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $user = $this->getUserById($userId);
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'error' => 'Current password is incorrect'];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = :password WHERE id = :user_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            // Log activity
            $this->logActivity($userId, 'password_change', 'User changed password');
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to change password'];
        }
    }

    /**
     * Create new user
     */
    public function createUser($userData) {
        try {
            // Check if username or email already exists
            $checkQuery = "SELECT id FROM users WHERE username = :username OR email = :email";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':username', $userData['username']);
            $checkStmt->bindParam(':email', $userData['email']);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                return ['success' => false, 'error' => 'Username or email already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert new user
            $query = "INSERT INTO users (username, email, password, full_name, role, phone, address, is_active, created_at) 
                     VALUES (:username, :email, :password, :full_name, :role, :phone, :address, 1, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $userData['username']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':full_name', $userData['full_name']);
            $stmt->bindParam(':role', $userData['role']);
            $stmt->bindParam(':phone', $userData['phone'] ?? '');
            $stmt->bindParam(':address', $userData['address'] ?? '');
            $stmt->execute();
            
            $userId = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($userId, 'user_created', 'User account created');
            
            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'User created successfully'
            ];
        } catch (Exception $e) {
            error_log("Create user error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to create user'];
        }
    }

    /**
     * Require authentication (for API endpoints)
     */
    public function requireAuth($role = null) {
        if (!$this->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        
        if ($role && !$this->hasRole($role)) {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            exit;
        }
    }
}


