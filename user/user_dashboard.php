<?php
session_start();

// Check user access
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Simple database connection (intentionally insecure)
$db = new SQLite3('payroll.db');

// Get user info
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = $user_id"; // Vulnerable to SQL injection
$user_result = $db->query($user_query);
$user = $user_result->fetchArray();

// Get employee data for this user only (vulnerable query)
$employee_query = "SELECT * FROM employees WHERE user_id = $user_id";
$employee_result = $db->query($employee_query);
$employee = $employee_result->fetchArray();

// Get payroll history for this user
$payroll_query = "SELECT ph.*, e.name FROM payroll_history ph 
                  JOIN employees e ON ph.employee_id = e.id 
                  WHERE e.user_id = $user_id 
                  ORDER BY ph.pay_date DESC LIMIT 5";
$payroll_result = $db->query($payroll_query);

// Calculate statistics
$total_earned = 0;
$payment_count = 0;
if ($employee) {
    $stats_query = "SELECT COUNT(*) as count, SUM(amount) as total FROM payroll_history ph 
                    JOIN employees e ON ph.employee_id = e.id 
                    WHERE e.user_id = $user_id";
    $stats_result = $db->query($stats_query);
    $stats = $stats_result->fetchArray();
    $total_earned = $stats['total'] ?? 0;
    $payment_count = $stats['count'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payroll - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b-4 border-blue-500">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <i class="fas fa-bull text-blue-600 text-2xl mr-2"></i>
                        <h1 class="text-2xl font-bold text-gray-800">My BullsCorp Payroll</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                        <i class="fas fa-user mr-1"></i>Employee
                    </span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <?php if (!$employee): ?>
        <!-- No Employee Profile -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 mb-8">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>No Employee Profile Found</strong><br>
                        Your account is not linked to an employee profile. Please contact HR to set up your employee record.
                    </p>
                </div>
            </div>
        </div>
        <?php else: ?>

        <!-- Employee Profile Card -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8" x-data="{ show: false }" x-init="setTimeout(() => show = true, 200)">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-8 text-white" 
                 :class="show ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'" 
                 class="transform transition-all duration-500">
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 p-4 rounded-full mr-6">
                        <i class="fas fa-user text-4xl"></i>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold"><?php echo htmlspecialchars($employee['name']); ?></h2>
                        <p class="text-blue-100 text-lg"><?php echo htmlspecialchars($employee['position']); ?></p>
                        <p class="text-blue-200"><?php echo htmlspecialchars($employee['department']); ?> Department</p>
                        <p class="text-blue-200 text-sm">Employee ID: #<?php echo $employee['id']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Salary Information Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" x-data="{ animateCards: false }" x-init="setTimeout(() => animateCards = true, 400)">
            <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-500" 
                 :class="animateCards ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Monthly Salary</p>
                        <p class="text-3xl font-bold text-green-600">Rp <?php echo number_format($employee['salary'], 0, ',', '.'); ?></p>
                        <p class="text-green-500 text-sm mt-1">
                            <i class="fas fa-calendar-alt mr-1"></i>Per Month
                        </p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-500 delay-100" 
                 :class="animateCards ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Earned</p>
                        <p class="text-3xl font-bold text-blue-600">Rp <?php echo number_format($total_earned, 0, ',', '.'); ?></p>
                        <p class="text-blue-500 text-sm mt-1">
                            <i class="fas fa-chart-line mr-1"></i>All Time
                        </p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-piggy-bank text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-500 delay-200" 
                 :class="animateCards ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Payments Received</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo $payment_count; ?></p>
                        <p class="text-purple-500 text-sm mt-1">
                            <i class="fas fa-receipt mr-1"></i>Transactions
                        </p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-history text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <a href="my_payroll_history.php" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6 rounded-xl shadow-lg hover:from-blue-600 hover:to-blue-700 transition-all transform hover:scale-105">
                <div class="flex items-center">
                    <i class="fas fa-history text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-xl font-bold">View Payroll History</h3>
                        <p class="text-blue-100">See all your payment records</p>
                    </div>
                </div>
            </a>

            <a href="my_profile.php" class="bg-gradient-to-r from-green-500 to-green-600 text-white p-6 rounded-xl shadow-lg hover:from-green-600 hover:to-green-700 transition-all transform hover:scale-105">
                <div class="flex items-center">
                    <i class="fas fa-user-edit text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-xl font-bold">Update Profile</h3>
                        <p class="text-green-100">Edit your personal information</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Recent Payroll History -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white">
                    <i class="fas fa-clock mr-2"></i>Recent Payroll History
                </h2>
            </div>

            <div class="p-6">
                <?php if ($payroll_result && $payroll_result->fetchArray()): ?>
                    <?php 
                    // Reset result pointer
                    $payroll_result = $db->query($payroll_query);
                    ?>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Date</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Amount</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Notes</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($payroll = $payroll_result->fetchArray()): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?php echo date('d M Y H:i', strtotime($payroll['pay_date'])); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-bold text-green-600">
                                        Rp <?php echo number_format($payroll['amount'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?php echo htmlspecialchars($payroll['notes']); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>Paid
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <a href="my_payroll_history.php" class="text-blue-600 hover:text-blue-800 font-medium">
                            View All History <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No payroll history found</p>
                        <p class="text-gray-400 text-sm">Your payment history will appear here once processed</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="app.js"></script>
</body>
</html>