<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth();

$user_id = $_SESSION['user_id'];

// Get employee data for this user
$employee = getEmployeeByUserId($user_id);

if (!$employee) {
    redirect('dashboard.php', 'No employee profile found for your account.');
}

// Get all payroll history for this user
$payroll_query = "SELECT ph.*, u.username as processed_by_name FROM payroll_history ph 
                  JOIN employees e ON ph.employee_id = e.id 
                  LEFT JOIN users u ON ph.processed_by = u.id
                  WHERE e.user_id = $user_id 
                  ORDER BY ph.pay_date DESC";
$payroll_result = $db->query($payroll_query);

// Calculate statistics
$stats_query = "SELECT COUNT(*) as count, SUM(amount) as total, AVG(amount) as average FROM payroll_history ph 
                JOIN employees e ON ph.employee_id = e.id 
                WHERE e.user_id = $user_id";
$stats_result = $db->query($stats_query);
$stats = $stats_result->fetch(PDO::FETCH_ASSOC);

$page_title = 'My Payroll History - BullsCorp';
$navbar_title = 'My Payroll History';
$show_back_button = true;
$back_url = 'dashboard.php';
$body_class = 'bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen';
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <!-- Employee Info -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-6 text-white">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-20 p-3 rounded-full mr-4">
                    <i class="fas fa-user text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold"><?php echo displayData($employee['name']); ?></h2>
                    <p class="text-blue-100"><?php echo displayData($employee['position']); ?> - <?php echo displayData($employee['department']); ?></p>
                    <p class="text-blue-200 text-sm">Employee Code: <?php echo displayData($employee['employee_code']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Payments</p>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $stats['count'] ?? 0; ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-receipt text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Earned</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo formatCurrency($stats['total'] ?? 0); ?></p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Average Payment</p>
                    <p class="text-2xl font-bold text-purple-600"><?php echo formatCurrency($stats['average'] ?? 0); ?></p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-chart-bar text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Monthly Salary</p>
                    <p class="text-2xl font-bold text-orange-600"><?php echo formatCurrency($employee['salary']); ?></p>
                </div>
                <div class="bg-orange-100 p-3 rounded-full">
                    <i class="fas fa-calendar-alt text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Payroll History Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
            <h2 class="text-xl font-bold text-white">
                <i class="fas fa-list mr-2"></i>Payment History
            </h2>
        </div>

        <div class="p-6">
            <?php if ($payroll_result && $payroll_result->fetch(PDO::FETCH_ASSOC)): ?>
                <?php 
                // Reset result pointer
                $payroll_result = $db->query($payroll_query);
                ?>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Payment Date</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Amount</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Notes</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Processed By</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($payment = $payroll_result->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-4 text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar text-blue-500 mr-2"></i>
                                        <div>
                                            <p class="font-medium"><?php echo formatDate($payment['pay_date'], 'd M Y'); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo formatDate($payment['pay_date'], 'H:i'); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <span class="text-lg font-bold text-green-600">
                                        <?php echo formatCurrency($payment['amount']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600">
                                    <div class="max-w-xs">
                                        <p class="truncate"><?php echo displayData($payment['notes']); ?></p>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <i class="fas fa-user-shield text-gray-400 mr-2"></i>
                                        <?php echo displayData($payment['processed_by_name'] ?? 'System'); ?>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800 font-medium">
                                        <i class="fas fa-check-circle mr-1"></i>Paid
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-gray-400 text-6xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No Payment History</h3>
                    <p class="text-gray-500">You haven't received any payments yet.</p>
                    <p class="text-gray-400 text-sm mt-2">Your payment history will appear here once processed by HR.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Export Options -->
    <div class="mt-6 bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-download mr-2 text-blue-600"></i>Export Options
        </h3>
        <div class="flex flex-wrap gap-4">
            <a href="../api/export_payroll.php?user_id=<?php echo $user_id; ?>&format=pdf" 
               class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-file-pdf mr-2"></i>Export as PDF
            </a>
            <a href="../api/export_payroll.php?user_id=<?php echo $user_id; ?>&format=excel" 
               class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-file-excel mr-2"></i>Export as Excel
            </a>
            <a href="../api/export_payroll.php?user_id=<?php echo $user_id; ?>&format=csv" 
               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-file-csv mr-2"></i>Export as CSV
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>