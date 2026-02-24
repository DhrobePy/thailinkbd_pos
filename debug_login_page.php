<?php
// Include configuration
require_once __DIR__ . '/config/config.php';

// If already logged in, redirect to dashboard
$auth = new Auth();
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thai Link BD Inventory System - Debug Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#64748B',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Thai Link BD
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Debug Login - Detailed Error Messages
            </p>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-8">
            <form id="loginForm" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">
                        Username
                    </label>
                    <div class="mt-1 relative">
                        <input id="username" name="username" type="text" required 
                               class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                               placeholder="Enter your username" value="admin">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <div class="mt-1 relative">
                        <input id="password" name="password" type="password" required 
                               class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                               placeholder="Enter your password" value="admin123">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <div id="errorContainer" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    Login Error
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p id="errorMessage"></p>
                                    <div id="debugInfo" class="mt-2 p-2 bg-red-100 rounded text-xs font-mono"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="successContainer" class="hidden">
                    <div class="bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">
                                    Login Successful
                                </h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>Redirecting to dashboard...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" id="loginButton"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition duration-150 ease-in-out">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt text-blue-300 group-hover:text-blue-200"></i>
                        </span>
                        <span id="buttonText">Sign In</span>
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">
                    Demo Credentials: admin/admin123, manager/admin123, cashier/admin123
                </p>
            </div>
        </div>
    </div>

    <script>
        function showError(message, debugInfo = null) {
            const errorContainer = document.getElementById('errorContainer');
            const successContainer = document.getElementById('successContainer');
            const errorMessage = document.getElementById('errorMessage');
            const debugInfoDiv = document.getElementById('debugInfo');
            
            errorMessage.textContent = message;
            
            if (debugInfo) {
                debugInfoDiv.innerHTML = '<strong>Debug Info:</strong><br>' + JSON.stringify(debugInfo, null, 2);
                debugInfoDiv.classList.remove('hidden');
            } else {
                debugInfoDiv.classList.add('hidden');
            }
            
            errorContainer.classList.remove('hidden');
            successContainer.classList.add('hidden');
        }

        function showSuccess(message) {
            const errorContainer = document.getElementById('errorContainer');
            const successContainer = document.getElementById('successContainer');
            
            successContainer.classList.remove('hidden');
            errorContainer.classList.add('hidden');
        }

        function hideError() {
            const errorContainer = document.getElementById('errorContainer');
            errorContainer.classList.add('hidden');
        }

        function setLoading(loading) {
            const button = document.getElementById('loginButton');
            const buttonText = document.getElementById('buttonText');
            
            if (loading) {
                button.disabled = true;
                button.classList.add('opacity-50', 'cursor-not-allowed');
                buttonText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing In...';
            } else {
                button.disabled = false;
                button.classList.remove('opacity-50', 'cursor-not-allowed');
                buttonText.textContent = 'Sign In';
            }
        }

        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            hideError();
            setLoading(true);
            
            try {
                const response = await fetch('debug_auth_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'login',
                        username: username,
                        password: password
                    })
                });

                const responseText = await response.text();
                console.log('Raw response:', responseText);

                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    showError('Invalid response from server', {
                        'response_text': responseText,
                        'parse_error': parseError.message
                    });
                    setLoading(false);
                    return;
                }
                
                setLoading(false);
                
                if (data.success) {
                    showSuccess('Login successful! Redirecting...');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    showError(data.error || 'Login failed', data.debug_info);
                }
            } catch (error) {
                setLoading(false);
                showError('Network error: ' + error.message, {
                    'error_type': 'network_error',
                    'error_message': error.message
                });
            }
        });
    </script>
</body>
</html>
