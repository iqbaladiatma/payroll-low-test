<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

checkAdmin();

// Get user info
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = $user_id"; // Vulnerable to SQL injection
$user_result = $db->query($user_query);
$user = $user_result->fetch(PDO::FETCH_ASSOC);

// Get all employees for admin
$employees_query = "SELECT e.*, u.username FROM employees e LEFT JOIN users u ON e.user_id = u.id ORDER BY e.created_at DESC";
$employees_result = $db->query($employees_query);

// Get statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM employees) as total_employees,
    (SELECT SUM(salary) FROM employees WHERE status = 'active') as total_payroll,
    (SELECT COUNT(*) FROM employees WHERE status = 'active') as active_employees,
    (SELECT COUNT(*) FROM payroll_history WHERE DATE(pay_date) = CURDATE()) as today_payments";
$stats_result = $db->query($stats_query);
$stats = $stats_result->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-red-50 to-orange-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b-4 border-red-500">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <i class="fas fa-bull text-red-600 text-2xl mr-2"></i>
                        <h1 class="text-2xl font-bold text-gray-800">BullsCorp Admin Panel</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                        <i class="fas fa-crown mr-1"></i>Administrator
                    </span>
                    <a href="../public/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Admin Dashboard Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" x-data="{ animateCards: false }" x-init="setTimeout(() => animateCards = true, 100)">
            <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-500" 
                 :class="animateCards ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Employees</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $stats['total_employees']; ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-500 delay-100" 
                 :class="animateCards ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Payroll</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo formatCurrency($stats['total_payroll']); ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-500 delay-200" 
                 :class="animateCards ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Active Employees</p>
                        <p class="text-3xl font-bold text-orange-600"><?php echo $stats['active_employees']; ?></p>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i class="fas fa-user-check text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-500 delay-300" 
                 :class="animateCards ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Today's Payments</p>
                        <p class="text-3xl font-bold text-red-600"><?php echo $stats['today_payments']; ?></p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-shield-alt text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <a href="add_employee_page.php" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6 rounded-xl shadow-lg hover:from-blue-600 hover:to-blue-700 transition-all transform hover:scale-105">
                <div class="flex items-center">
                    <i class="fas fa-user-plus text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-xl font-bold">Add Employee</h3>
                        <p class="text-blue-100">Add new employee to system</p>
                    </div>
                </div>
            </a>

            <a href="../tools/logs.php" class="bg-gradient-to-r from-green-500 to-green-600 text-white p-6 rounded-xl shadow-lg hover:from-green-600 hover:to-green-700 transition-all transform hover:scale-105">
                <div class="flex items-center">
                    <i class="fas fa-history text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-xl font-bold">System Logs</h3>
                        <p class="text-green-100">View system activities</p>
                    </div>
                </div>
            </a>

            <a href="../tools/debug_sql.php" class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-6 rounded-xl shadow-lg hover:from-purple-600 hover:to-purple-700 transition-all transform hover:scale-105">
                <div class="flex items-center">
                    <i class="fas fa-chart-bar text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-xl font-bold">SQL Debug</h3>
                        <p class="text-purple-100">Debug database queries</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Employee Management Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-red-600 to-orange-600 px-6 py-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-white">
                        <i class="fas fa-users-cog mr-2"></i>Employee Management
                    </h2>
                    <div class="flex space-x-2">
                        <a href="add_employee_page.php" class="bg-white text-red-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Add Employee
                        </a>
                        <a href="../tools/upload.php" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors">
                            <i class="fas fa-upload mr-2"></i>File Upload
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">ID</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Code</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Name</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Position</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Department</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Salary</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">User Account</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($employee = $employees_result->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo $employee['id']; ?></td>
                                <td class="px-4 py-3 text-sm font-mono text-blue-600"><?php echo displayData($employee['employee_code']); ?></td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo displayData($employee['name']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo displayData($employee['position']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo displayData($employee['department']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-semibold"><?php echo formatCurrency($employee['salary']); ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if ($employee['username']): ?>
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                            <i class="fas fa-user mr-1"></i><?php echo displayData($employee['username']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">No Account</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $employee['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <i class="fas fa-<?php echo $employee['status'] == 'active' ? 'check-circle' : 'times-circle'; ?> mr-1"></i>
                                        <?php echo ucfirst($employee['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex space-x-2">
                                        <a href="process_payroll_page.php?id=<?php echo $employee['id']; ?>" 
                                           class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors">
                                            <i class="fas fa-money-check-alt mr-1"></i>Pay
                                        </a>
                                        <a href="edit_employee_page.php?id=<?php echo $employee['id']; ?>" 
                                           class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs transition-colors">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </a>
                                        <a href="delete_employee.php?id=<?php echo $employee['id']; ?>" 
                                           onclick="return confirm('Are you sure you want to delete this employee?')"
                                           class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition-colors">
                                            <i class="fas fa-trash mr-1"></i>Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
</body>
</html>