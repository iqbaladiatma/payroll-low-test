<?php
session_start();
require_once './config/database.php';
require_once './includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_SESSION['role'];
$username = $_SESSION['username'];

include './includes/header.php';
include './includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            Welcome, <?php echo htmlspecialchars($username); ?>!
        </h1>
        
        <?php if ($user_role === 'admin'): ?>
            <!-- Admin Dashboard -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-blue-100 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold text-blue-800 mb-2">Employee Management</h3>
                    <p class="text-blue-600 mb-4">Manage employee records</p>
                    <a href="/admin/dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Go to Admin Panel
                    </a>
                </div>
                
                <div class="bg-green-100 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold text-green-800 mb-2">Payroll Processing</h3>
                    <p class="text-green-600 mb-4">Process employee payroll</p>
                    <a href="/admin/process_payroll_page.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        Process Payroll
                    </a>
                </div>
                
                <div class="bg-purple-100 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold text-purple-800 mb-2">System Logs</h3>
                    <p class="text-purple-600 mb-4">View system activities</p>
                    <a href="/tools/logs.php" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                        View Logs
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- User Dashboard -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-blue-100 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold text-blue-800 mb-2">My Profile</h3>
                    <p class="text-blue-600 mb-4">View and update your profile</p>
                    <a href="/user/dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        View Profile
                    </a>
                </div>
                
                <div class="bg-green-100 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold text-green-800 mb-2">Payroll History</h3>
                    <p class="text-green-600 mb-4">View your payroll history</p>
                    <a href="/user/payroll_history.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        View History
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include './includes/footer.php'; ?>