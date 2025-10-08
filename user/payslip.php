<?php
// User Payslip - View Monthly Payslips
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bullscorp_payroll';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get user and employee data
$user_query = "SELECT u.*, e.* FROM users u LEFT JOIN employees e ON u.employee_id = e.id WHERE u.id = $user_id";
$user_data = $pdo->query($user_query)->fetch(PDO::FETCH_ASSOC);

if (!$user_data || !$user_data['employee_id']) {
    header('Location: complete_profile.php');
    exit;
}

// Get payroll history
$payroll_query = "SELECT * FROM salaries WHERE employee_id = {$user_data['employee_id']} ORDER BY pay_period DESC";
$payrolls = $pdo->query($payroll_query)->fetchAll(PDO::FETCH_ASSOC);

// Get specific payslip if requested
$selected_payslip = null;
if (isset($_GET['id'])) {
    $payslip_id = $_GET['id'];
    $payslip_query = "SELECT * FROM salaries WHERE id = $payslip_id AND employee_id = {$user_data['employee_id']}";
    $selected_payslip = $pdo->query($payslip_query)->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payslips - BullsCorp Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>My Payslips
                </h1>
                <div class="flex space-x-2">
                    <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>

            <?php if ($selected_payslip): ?>
            <!-- Detailed Payslip View -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Payslip</h2>
                        <p class="text-gray-600">Pay Period: <?php echo date('F Y', strtotime($selected_payslip['pay_period'])); ?></p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600">BullsCorp Payroll System</div>
                        <div class="text-sm text-gray-600">Generated: <?php echo date('M d, Y'); ?></div>
                    </div>
                </div>

                <!-- Employee Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 p-4 bg-gray-50 rounded-lg">
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-2">Employee Information</h3>
                        <div class="space-y-1 text-sm">
                            <div><span class="text-gray-600">Name:</span> <span class="font-medium"><?php echo htmlspecialchars($user_data['name'] ?? ''); ?></span></div>
                            <div><span class="text-gray-600">Employee ID:</span> <span class="font-medium">#<?php echo $user_data['employee_id']; ?></span></div>
                            <div><span class="text-gray-600">Department:</span> <span class="font-medium"><?php echo htmlspecialchars($user_data['department'] ?? ''); ?></span></div>
                            <div><span class="text-gray-600">Position:</span> <span class="font-medium"><?php echo htmlspecialchars($user_data['position'] ?? ''); ?></span></div>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-2">Payment Information</h3>
                        <div class="space-y-1 text-sm">
                            <div><span class="text-gray-600">Pay Period:</span> <span class="font-medium"><?php echo date('F Y', strtotime($selected_payslip['pay_period'])); ?></span></div>
                            <div><span class="text-gray-600">Status:</span> 
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php 
                                    switch($selected_payslip['status']) {
                                        case 'paid': echo 'bg-green-100 text-green-800'; break;
                                        case 'processing': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($selected_payslip['status']); ?>
                                </span>
                            </div>
                            <div><span class="text-gray-600">Processed:</span> <span class="font-medium"><?php echo $selected_payslip['processed_at'] ? date('M d, Y', strtotime($selected_payslip['processed_at'])) : 'Not yet'; ?></span></div>
                        </div>
                    </div>
                </div>

                <!-- Salary Breakdown -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Earnings -->
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-4 pb-2 border-b">Earnings</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Basic Salary</span>
                                <span class="font-medium">Rp <?php echo number_format($selected_payslip['basic_salary'], 0, ',', '.'); ?></span>
                            </div>
                            <?php if ($selected_payslip['allowances'] > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Allowances</span>
                                <span class="font-medium">Rp <?php echo number_format($selected_payslip['allowances'], 0, ',', '.'); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($selected_payslip['overtime_pay'] > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Overtime Pay</span>
                                <span class="font-medium">Rp <?php echo number_format($selected_payslip['overtime_pay'], 0, ',', '.'); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between font-semibold text-green-600 pt-2 border-t">
                                <span>Total Earnings</span>
                                <span>Rp <?php echo number_format($selected_payslip['basic_salary'] + $selected_payslip['allowances'] + $selected_payslip['overtime_pay'], 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-4 pb-2 border-b">Deductions</h3>
                        <div class="space-y-3">
                            <?php if ($selected_payslip['deductions'] > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Deductions</span>
                                <span class="font-medium text-red-600">Rp <?php echo number_format($selected_payslip['deductions'], 0, ',', '.'); ?></span>
                            </div>
                            <?php else: ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">No deductions</span>
                                <span class="font-medium">Rp 0</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Net Pay -->
                <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-xl font-bold text-gray-800">Net Pay</span>
                        <span class="text-2xl font-bold text-blue-600">Rp <?php echo number_format($selected_payslip['total_amount'], 0, ',', '.'); ?></span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="payslip.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-arrow-left mr-1"></i>Back to List
                    </a>
                    <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                        <i class="fas fa-print mr-1"></i>Print Payslip
                    </button>
                </div>
            </div>

            <?php else: ?>
            <!-- Payslip List -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-semibold text-gray-800">Payroll History</h2>
                    <p class="text-gray-600 text-sm mt-1">View and download your monthly payslips</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Basic Salary</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($payrolls)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>No payslips found</p>
                                    <p class="text-sm">Your payslips will appear here once processed</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($payrolls as $payroll): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo date('F Y', strtotime($payroll['pay_period'])); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($payroll['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    Rp <?php echo number_format($payroll['basic_salary'], 0, ',', '.'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    Rp <?php echo number_format($payroll['total_amount'], 0, ',', '.'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php 
                                        switch($payroll['status']) {
                                            case 'paid': echo 'bg-green-100 text-green-800'; break;
                                            case 'processing': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <i class="fas fa-<?php 
                                            switch($payroll['status']) {
                                                case 'paid': echo 'check'; break;
                                                case 'processing': echo 'clock'; break;
                                                case 'pending': echo 'hourglass-half'; break;
                                                default: echo 'question';
                                            }
                                        ?> mr-1"></i>
                                        <?php echo ucfirst($payroll['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="payslip.php?id=<?php echo $payroll['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .bg-white, .bg-white * {
                visibility: visible;
            }
            .bg-white {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</body>
</html>