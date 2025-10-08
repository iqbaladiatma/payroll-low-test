<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /user/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';
$authController = new AuthController();

if ($_POST) {
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);
    $email = sanitizeInput($_POST['email']);
    
    // Validasi input
    if (empty($username) || empty($password) || empty($email)) {
        $error = 'All fields are required';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        if ($authController->register($username, $password, $email)) {
            // Auto-login the user after successful registration
            if ($authController->login($username, $password)) {
                // Log activity if function exists
                if (function_exists('logActivity')) {
                    logActivity('User registration', 'users', null, null, "username: $username, email: $email");
                }
                // Redirect to profile completion
                header('Location: ../user/complete_profile.php');
                exit;
            } else {
                $success = 'Registration successful! You can now login.';
            }
        } else {
            $error = 'Registration failed - username or email may already exist';
        }
    }
}

$page_title = 'Register - BullsCorp Payroll';
$body_class = 'bg-gradient-to-br from-purple-600 via-blue-600 to-indigo-800 min-h-screen flex items-center justify-center';
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="absolute inset-0 bg-black opacity-20"></div>

<!-- Animated Background Elements -->
<div class="absolute inset-0 overflow-hidden">
    <div class="absolute -top-40 -right-40 w-80 h-80 bg-white opacity-10 rounded-full animate-bounce"></div>
    <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-white opacity-10 rounded-full animate-bounce delay-1000"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-white opacity-5 rounded-full animate-pulse"></div>
</div>

<div class="relative z-10 w-full max-w-md px-6">
    <!-- Register Card -->
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-500" 
         x-data="{ show: false }" x-init="setTimeout(() => show = true, 300)" 
         :class="show ? 'translate-y-0 opacity-100 scale-100' : 'translate-y-8 opacity-0 scale-95'">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 px-8 py-6 text-center">
            <div class="flex justify-center mb-4">
                <div class="bg-white p-3 rounded-full">
                    <i class="fas fa-user-plus text-purple-600 text-3xl"></i>
                </div>
            </div>
            <h1 class="text-2xl font-bold text-white">Join BullsCorp</h1>
            <p class="text-purple-100 text-sm mt-1">Create your payroll account</p>
        </div>

        <!-- Register Form -->
        <div class="px-8 py-6">
            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 animate-shake">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo displayData($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo displayData($success); ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-gray-400"></i>Username
                    </label>
                    <input type="text" id="username" name="username" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 hover:border-purple-300"
                           placeholder="Choose a username">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-gray-400"></i>Email
                    </label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 hover:border-purple-300"
                           placeholder="Enter your email">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-gray-400"></i>Password
                    </label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 hover:border-purple-300"
                           placeholder="Create a password">
                </div>

                <button type="submit" 
                        class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:from-purple-700 hover:to-blue-700 transform transition-all duration-200 hover:scale-105 focus:ring-4 focus:ring-purple-200">
                    <i class="fas fa-user-plus mr-2"></i>
                    Create Account
                </button>
            </form>

            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="text-purple-600 hover:text-purple-800 font-medium transition-colors">
                        Sign in here
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>