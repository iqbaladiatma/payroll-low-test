<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../src/controllers/PayrollController.php';

// Check if user is admin or HR
checkAuth();
if (!in_array($_SESSION['role'], ['admin', 'hr'])) {
    die('Access denied - Admin or HR only');
}

$error = '';
$success = '';
$employee_id = $_GET['id'] ?? 0;
$payrollController = new PayrollController();

// Get employee data (vulnerable query)
$employee_query = "SELECT e.*, u.username FROM employees e LEFT JOIN users u ON e.user_id = u.id WHERE e.id = $employee_id";
$employee_result = $db->query($employee_query);
$employee = $employee_result ? $employee_result->fetch(PDO::FETCH_ASSOC) : null;

if (!$employee) {
    header('Location: dashboard.php?error=Employee not found');
    exit();
}

if ($_POST) {
    $amount = $_POST['amount'];
    $notes = $_POST['notes'];
    $processed_by = $_SESSION['user_id'];
    
    if ($payrollController->processPayroll($employee_id, $amount, $processed_by, $notes)) {
        $success = 'Payroll processed successfully!';
        logActivity('Payroll processed', 'payroll_history', null, null, "employee_id: $employee_id, amount: $amount");
    } else {
        $error = 'Failed to process payroll';
    }
}

// Get recent payroll history for this employee
$history_query = "SELECT * FROM payroll_history WHERE employee_id = $employee_id ORDER BY pay_date DESC LIMIT 5";
$history_result = $db->query($history_query);
$history_data = $history_result ? $history_result->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payroll - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b-4 border-green-500">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-green-600 hover:text-green-800">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div class="flex items-center">
                        <i class="fas fa-money-check-alt text-green-600 text-2xl mr-2"></i>
                        <h1 class="text-2xl font-bold text-gray-800">Process Payroll</h1>
                    </div>
                </div>
                <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Employee Info Card -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-green-600 to-blue-600 px-6 py-6">
                <div class="flex items-center text-white">
                    <div class="bg-white bg-opacity-20 p-4 rounded-full mr-6">
                        <i class="fas fa-user text-3xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($employee['name']); ?></h2>
                        <p class="text-green-100"><?php echo htmlspecialchars($employee['position']); ?></p>
                        <p class="text-green-200"><?php echo htmlspecialchars($employee['department']); ?> Department</p>
                        <p class="text-green-200 text-sm">Employee ID: #<?php echo $employee['id']; ?></p>
                        <?php if ($employee['username']): ?>
                            <p class="text-green-200 text-sm">
                                <i class="fas fa-link mr-1"></i>Account: <?php echo htmlspecialchars($employee['username']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Process Payroll Form -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-blue-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white">Process Payment</h3>
                </div>

                <div class="p-6">
                    <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $success; ?>
                        <div class="mt-2">
                            <a href="dashboard.php" class="text-green-800 underline">Back to Dashboard</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-money-bill-wave mr-2 text-gray-400"></i>Payment Amount (Rp)
                            </label>
                            <input type="number" name="amount" required min="0" step="1000"
                                   value="<?php echo $employee['salary']; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-lg font-semibold">
                            <p class="text-sm text-gray-500 mt-1">Default: Monthly salary (Rp <?php echo number_format($employee['salary'], 0, ',', '.'); ?>)</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-sticky-note mr-2 text-gray-400"></i>Payment Notes
                            </label>
                            <textarea name="notes" rows="4" required
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                      placeholder="e.g. Monthly salary for January 2024, Bonus payment, etc.">Monthly salary payment for <?php echo date('F Y'); ?></textarea>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-800 mb-2">
                                <i class="fas fa-info-circle mr-2"></i>Payment Summary
                            </h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-blue-700">Employee:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($employee['name']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-blue-700">Position:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($employee['position']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-blue-700">Base Salary:</span>
                                    <span class="font-medium">Rp <?php echo number_format($employee['salary'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-blue-700">Processed By:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-blue-700">Date:</span>
                                    <span class="font-medium"><?php echo date('d M Y H:i'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 pt-6 border-t">
                            <a href="dashboard.php" 
                               class="px-6 py-3 text-gray-600 hover:text-gray-800 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                                <i class="fas fa-check mr-2"></i>Process Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payment History -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white">
                        <i class="fas fa-history mr-2"></i>Recent Payments
                    </h3>
                </div>

                <div class="p-6">
                    <?php if (!empty($history_data)): ?>
                        <div class="space-y-4">
                            <?php foreach ($history_data as $payment): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-semibold text-green-600">
                                            Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?>
                                        </p>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <?php echo htmlspecialchars($payment['notes']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-2">
                                            <i class="fas fa-calendar mr-1"></i>
                                            <?php echo date('d M Y H:i', strtotime($payment['pay_date'])); ?>
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                        <i class="fas fa-check-circle mr-1"></i>Paid
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="../tools/logs.php" 
                               class="text-purple-600 hover:text-purple-800 font-medium">
                                View All History <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No payment history</p>
                            <p class="text-gray-400 text-sm">This will be the first payment for this employee</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>