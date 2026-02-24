<?php
/**
 * Authentication API Endpoint
 */

require_once '../config/config.php';

$auth = new Auth();
$method = $_SERVER['REQUEST_METHOD'];
$data = getRequestData();

switch ($method) {
    case 'POST':
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'login':
                $username = sanitizeInput($data['username'] ?? '');
                $password = $data['password'] ?? '';
                
                if (empty($username) || empty($password)) {
                    sendJsonResponse(['error' => 'Username and password are required'], 400);
                }
                
                $result = $auth->login($username, $password);
                
                if ($result['success']) {
                    sendJsonResponse($result);
                } else {
                    sendJsonResponse(['error' => $result['error']], 401);
                }
                break;
                
            case 'logout':
                $result = $auth->logout();
                sendJsonResponse($result);
                break;
                
            case 'change_password':
                $auth->requireAuth();
                
                $currentPassword = $data['current_password'] ?? '';
                $newPassword = $data['new_password'] ?? '';
                $confirmPassword = $data['confirm_password'] ?? '';
                
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    sendJsonResponse(['error' => 'All password fields are required'], 400);
                }
                
                if ($newPassword !== $confirmPassword) {
                    sendJsonResponse(['error' => 'New passwords do not match'], 400);
                }
                
                if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                    sendJsonResponse(['error' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'], 400);
                }
                
                $user = $auth->getCurrentUser();
                $result = $auth->changePassword($user['id'], $currentPassword, $newPassword);
                
                if ($result['success']) {
                    sendJsonResponse($result);
                } else {
                    sendJsonResponse(['error' => $result['error']], 400);
                }
                break;
                
            case 'create_user':
                $auth->requireAuth('admin');
                
                $requiredFields = ['username', 'email', 'password', 'full_name', 'role'];
                $missing = validateRequiredFields($data, $requiredFields);
                
                if (!empty($missing)) {
                    sendJsonResponse(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
                }
                
                if (!validateEmail($data['email'])) {
                    sendJsonResponse(['error' => 'Invalid email address'], 400);
                }
                
                if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
                    sendJsonResponse(['error' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'], 400);
                }
                
                $userData = [
                    'username' => sanitizeInput($data['username']),
                    'email' => sanitizeInput($data['email']),
                    'password' => $data['password'],
                    'full_name' => sanitizeInput($data['full_name']),
                    'role' => sanitizeInput($data['role']),
                    'phone' => sanitizeInput($data['phone'] ?? ''),
                    'address' => sanitizeInput($data['address'] ?? '')
                ];
                
                $result = $auth->createUser($userData);
                
                if ($result['success']) {
                    $currentUser = $auth->getCurrentUser();
                    logActivity($currentUser['id'], 'CREATE', 'users', null, null, $userData);
                    sendJsonResponse($result);
                } else {
                    sendJsonResponse(['error' => $result['error']], 400);
                }
                break;
                
            default:
                sendJsonResponse(['error' => 'Invalid action'], 400);
        }
        break;
        
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'current_user':
                $auth->requireAuth();
                $user = $auth->getCurrentUser();
                sendJsonResponse(['user' => $user]);
                break;
                
            case 'check_session':
                if ($auth->isLoggedIn()) {
                    $user = $auth->getCurrentUser();
                    sendJsonResponse(['authenticated' => true, 'user' => $user]);
                } else {
                    sendJsonResponse(['authenticated' => false]);
                }
                break;
                
            default:
                sendJsonResponse(['error' => 'Invalid action'], 400);
        }
        break;
        
    default:
        sendJsonResponse(['error' => 'Method not allowed'], 405);
}


