<?php
// Admin Payslip Viewer - View employee payslips
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and has admin/hr access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'hr'])) {
    header('Location: ../public/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Admin';
$user_role = $_SESSION['role'] ?? 'admin';

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

// Get salary ID from URL
$salary_id = $_GET['id'] ?? 0;

if (!$salary_id) {
    header('Location: manage_payroll.php');
    exit;
}

// Get salary and employee data
$query = "SELECT s.*, e.name as employee_name, e.employee_code, e.department, e.position, e.email, e.phone
          FROM salaries s 
          LEFT JOIN employees e ON s.employee_id = e.id 
          WHERE s.id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$salary_id]);
$salary_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$salary_data) {
    header('Location: manage_payroll.php?error=Payslip not found');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?php echo htmlspecialchars($salary_data['employee_name'] ?? 'Employee'); ?> - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b no-print">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-file-invoice mr-2 text-blue-600"></i>
                        Employee Payslip
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($username ?? 'Admin'); ?></span>
                    <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                        <i class="fas fa-print mr-1"></i>Print
                    </button>
                    <a href="manage_payroll.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-arrow-left mr-1"></i>Back
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Payslip Content -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-8">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-3xl font-bold mb-2">BullsCorp</h1>
                            <p class="text-blue-100">Payroll Management System</p>
                            <p class="text-blue-200 text-sm">Jakarta, Indonesia</p>
                        </div>
                        <div class="text-right">
                            <h2 class="text-2xl font-bold mb-2">PAYSLIP</h2>
                            <p class="text-blue-100">Pay Period: <?php echo date('F Y', strtotime($salary_data['pay_period'])); ?></p>
                            <p class="text-blue-200 text-sm">Generated: <?php echo date('d M Y H:i'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Employee Information -->
                <div class="p-8 border-b">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-4">Employee Information</h3>
                            <div class="space-y-2 text-sm">
                                <div><span class="text-gray-600">Name:</span> <span class="font-medium"><?php echo htmlspecialchars($salary_data['employee_name'] ?? 'N/A'); ?></span></div>
                                <div><span class="text-gray-600">Employee ID:</span> <span class="font-medium"><?php echo htmlspecialchars($salary_data['employee_code'] ?? 'N/A'); ?></span></div>
                                <div><span class="text-gray-600">Department:</span> <span class="font-medium"><?php echo htmlspecialchars($salary_data['department'] ?? 'N/A'); ?></span></div>
                                <div><span class="text-gray-600">Position:</span> <span class="font-medium"><?php echo htmlspecialchars($salary_data['position'] ?? 'N/A'); ?></span></div>
                                <div><span class="text-gray-600">Email:</span> <span class="font-medium"><?php echo htmlspecialchars($salary_data['email'] ?? 'N/A'); ?></span></div>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-4">Payment Information</h3>
                            <div class="space-y-2 text-sm">
                                <div><span class="text-gray-600">Pay Period:</span> <span class="font-medium"><?php echo date('F Y', strtotime($salary_data['pay_period'])); ?></span></div>
                                <div><span class="text-gray-600">Payment Date:</span> <span class="font-medium"><?php echo $salary_data['processed_at'] ? date('d M Y', strtotime($salary_data['processed_at'])) : 'Pending'; ?></span></div>
                                <div><span class="text-gray-600">Status:</span> 
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php 
                                        switch($salary_data['status']) {
                                            case 'paid': echo 'bg-green-100 text-green-800'; break;
                                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'processing': echo 'bg-blue-100 text-blue-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($salary_data['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Salary Breakdown -->
                <div class="p-8">
                    <h3 class="font-semibold text-gray-800 mb-6">Salary Breakdown</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Earnings -->
                        <div>
                            <h4 class="font-medium text-green-700 mb-4 border-b border-green-200 pb-2">
                                <i class="fas fa-plus-circle mr-2"></i>Earnings
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Basic Salary</span>
                                    <span class="font-medium">Rp <?php echo number_format($salary_data['basic_salary'], 0, ',', '.'); ?></span>
                                </div>
                                <?php if ($salary_data['allowances'] > 0): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Allowances</span>
                                    <span class="font-medium">Rp <?php echo number_format($salary_data['allowances'], 0, ',', '.'); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($salary_data['overtime_pay'] > 0): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Overtime Pay</span>
                                    <span class="font-medium">Rp <?php echo number_format($salary_data['overtime_pay'], 0, ',', '.'); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="border-t pt-3">
                                    <div class="flex justify-between font-semibold text-green-700">
                                        <span>Total Earnings</span>
                                        <span>Rp <?php echo number_format($salary_data['basic_salary'] + $salary_data['allowances'] + $salary_data['overtime_pay'], 0, ',', '.'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Deductions -->
                        <div>
                            <h4 class="font-medium text-red-700 mb-4 border-b border-red-200 pb-2">
                                <i class="fas fa-minus-circle mr-2"></i>Deductions
                            </h4>
                            <div class="space-y-3">
                                <?php if ($salary_data['deductions'] > 0): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Deductions</span>
                                    <span class="font-medium text-red-600">-Rp <?php echo number_format($salary_data['deductions'], 0, ',', '.'); ?></span>
                                </div>
                                <?php else: ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">No deductions</span>
                                    <span class="font-medium">Rp 0</span>
                                </div>
                                <?php endif; ?>
                                <div class="border-t pt-3">
                                    <div class="flex justify-between font-semibold text-red-700">
                                        <span>Total Deductions</span>
                                        <span>-Rp <?php echo number_format($salary_data['deductions'], 0, ',', '.'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Net Pay -->
                    <div class="mt-8 p-6 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg border-2 border-blue-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-xl font-bold text-blue-800">Net Pay</h3>
                                <p class="text-blue-600 text-sm">Amount to be paid</p>
                            </div>
                            <div class="text-right">
                                <div class="text-3xl font-bold text-blue-800">
                                    Rp <?php echo number_format($salary_data['total_amount'], 0, ',', '.'); ?>
                                </div>
                                <p class="text-blue-600 text-sm"><?php echo date('F Y', strtotime($salary_data['pay_period'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 p-6 border-t">
                    <div class="text-center text-sm text-gray-600">
                        <p class="mb-2">This is a computer-generated payslip and does not require a signature.</p>
                        <p>For any queries regarding this payslip, please contact HR Department.</p>
                        <p class="mt-4 font-medium">BullsCorp - Human Resources Department</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>