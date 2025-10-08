<?php
// Vulnerable Login System - Low Security for Penetration Testing
// WARNING: This code contains intentional vulnerabilities!

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? 'employee';
    if ($role === 'admin' || $role === 'hr') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../user/dashboard.php');
    }
    exit;
}

// Database connection with exposed credentials
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bullscorp_payroll';

$error_message = '';
$success_message = '';

// Handle logout message
if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $success_message = 'You have been successfully logged out.';
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $error_message = "Database connection failed: " . $e->getMessage();
}

// Handle login form submission
if ($_POST && !$error_message) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Log login attempt (vulnerable - logs sensitive data)
    error_log("Login attempt: $username from " . $_SERVER['REMOTE_ADDR']);
    
    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required";
    } else {
        // SQL injection vulnerability - direct string concatenation
        $sql = "SELECT u.*, e.name as employee_name, e.department, e.position 
                FROM users u 
                LEFT JOIN employees e ON u.employee_id = e.id 
                WHERE u.username = '$username' OR u.email = '$username'";
        
        try {
            $result = $pdo->query($sql);
            $user = $result->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Vulnerable password check - also accepts plain text passwords
                $password_valid = false;
                
                // Check hashed password
                if (password_verify($password, $user['password'])) {
                    $password_valid = true;
                }
                
                // Vulnerability: Also accept plain text passwords for testing
                if ($user['password'] === $password) {
                    $password_valid = true;
                }
                
                // Vulnerability: Hardcoded backdoor passwords
                if ($password === 'backdoor123' || $password === 'admin' || $password === 'password') {
                    $password_valid = true;
                }
                
                if ($password_valid) {
                    // Successful login
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['employee_id'] = $user['employee_id'];
                    $_SESSION['employee_name'] = $user['employee_name'];
                    $_SESSION['department'] = $user['department'];
                    $_SESSION['position'] = $user['position'];
                    $_SESSION['login_time'] = time();
                    
                    // Update last login (vulnerable - no prepared statement)
                    $pdo->exec("UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);
                    
                    // Log successful login
                    $pdo->exec("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES 
                               (" . $user['id'] . ", 'LOGIN_SUCCESS', 'User logged in successfully', '" . $_SERVER['REMOTE_ADDR'] . "', '" . addslashes($_SERVER['HTTP_USER_AGENT']) . "')");
                    
                    // Set remember me cookie (vulnerable - no encryption)
                    if ($remember_me) {
                        setcookie('remember_user', $username, time() + (86400 * 30), '/'); // 30 days
                        setcookie('remember_pass', $password, time() + (86400 * 30), '/'); // Extremely vulnerable!
                    }
                    
                    // Redirect based on role and profile completion status
                    if ($user['role'] === 'admin' || $user['role'] === 'hr') {
                        header('Location: ../admin/dashboard.php');
                    } else {
                        // Check if user needs to complete profile
                        if (!$user['employee_id']) {
                            header('Location: ../user/complete_profile.php');
                        } else {
                            header('Location: ../user/dashboard.php');
                        }
                    }
                    exit;
                } else {
                    $error_message = "Invalid username or password";
                    
                    // Log failed login
                    if ($user) {
                        $pdo->exec("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES 
                                   (" . $user['id'] . ", 'LOGIN_FAILED', 'Invalid password attempt', '" . $_SERVER['REMOTE_ADDR'] . "', '" . addslashes($_SERVER['HTTP_USER_AGENT']) . "')");
                    }
                }
            } else {
                $error_message = "Invalid username or password";
            }
        } catch (Exception $e) {
            $error_message = "Login error: " . $e->getMessage();
            // Expose SQL query in error (vulnerability)
            if (isset($_GET['debug'])) {
                $error_message .= "<br>Query: " . htmlspecialchars($sql);
            }
        }
    }
}

// Auto-fill from remember me cookies (vulnerable)
$remembered_username = $_COOKIE['remember_user'] ?? '';
$remembered_password = $_COOKIE['remember_pass'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BullsCorp Payroll</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-600 via-purple-600 to-red-600 min-h-screen flex items-center justify-center">
    <!-- Debug Panel (Vulnerable) -->
    <div class="fixed top-0 left-0 right-0 bg-red-100 border border-red-400 text-red-700 px-4 py-3 z-50" style="display: <?php echo isset($_GET['debug']) ? 'block' : 'none'; ?>">
        <strong>Debug Mode Active:</strong>
        <ul class="mt-2 text-sm">
            <li>Database: <?php echo $db_name; ?>@<?php echo $db_host; ?></li>
            <li>Session: <?php echo json_encode($_SESSION); ?></li>
            <li>Cookies: <?php echo json_encode($_COOKIE); ?></li>
            <li>POST Data: <?php echo json_encode($_POST); ?></li>
        </ul>
    </div>

    <div class="bg-white rounded-lg shadow-2xl p-8 max-w-md w-full mx-4">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-building text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">BullsCorp</h1>
            <p class="text-gray-600 mt-2">Payroll Management System</p>
        </div>

        <!-- Error/Success Messages -->
        <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user mr-1"></i>Username or Email
                </label>
                <input type="text" id="username" name="username" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Enter your username or email" value="" required>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-1"></i>Password
                </label>
                <div class="relative">
                    <input type="password" id="password" name="password" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10"
                           placeholder="Enter your password" value="" required>
                    <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <i class="fas fa-eye text-gray-400" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" id="remember_me" name="remember_me" 
                           <?php echo $remembered_username ? 'checked' : ''; ?>
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                        Remember me
                    </label>
                </div>
                <a href="forgot_password.php" class="text-sm text-blue-600 hover:text-blue-800">
                    Forgot password?
                </a>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-2 px-4 rounded-md hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                <i class="fas fa-sign-in-alt mr-2"></i>Sign In
            </button>
        </form>

        <!-- Footer -->
        <div class="mt-3 text-center text-xs text-gray-500">
            <p class="mt-1">
                <a href="register.php" class="text-purple-500 hover:text-purple-700">Register</a>
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash text-gray-400';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'fas fa-eye text-gray-400';
            }
        }

        function quickLogin(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            document.querySelector('form').submit();
        }

        // Auto-fill from remember me cookies only
        document.addEventListener('DOMContentLoaded', function() {
            // Only fill if there are remembered credentials
            <?php if ($remembered_username): ?>
            document.getElementById('username').value = '<?php echo htmlspecialchars($remembered_username); ?>';
            <?php endif; ?>
            <?php if ($remembered_password): ?>
            document.getElementById('password').value = '<?php echo htmlspecialchars($remembered_password); ?>';
            <?php endif; ?>
        });

        // Debug info removed for cleaner login experience
    </script>
</body>
</html>