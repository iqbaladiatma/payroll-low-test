<?php
// Vulnerable Payroll Management - Low Security for Penetration Testing
// WARNING: This code contains intentional vulnerabilities!

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Weak authentication check
$user_id = $_SESSION['user_id'] ?? $_GET['user_id'] ?? 1;
$user_role = $_SESSION['role'] ?? $_GET['role'] ?? 'admin';
$username = $_SESSION['username'] ?? 'admin';

// Database connection with exposed credentials
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

$message = '';
$error = '';

// Handle payroll actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'process_payroll':
            $employee_id = $_POST['employee_id'];
            $pay_period_input = $_POST['pay_period'];
            // Convert YYYY-MM to YYYY-MM-01 for proper date format
            $pay_period = $pay_period_input . '-01';
            $basic_salary = $_POST['basic_salary'];
            $allowances = $_POST['allowances'] ?? 0;
            $overtime_pay = $_POST['overtime_pay'] ?? 0;
            $deductions = $_POST['deductions'] ?? 0;
            $total_amount = $basic_salary + $allowances + $overtime_pay - $deductions;
            
            // Vulnerable SQL injection
            $sql = "INSERT INTO salaries (employee_id, pay_period, basic_salary, allowances, overtime_pay, deductions, total_amount, status, processed_by) 
                    VALUES ($employee_id, '$pay_period', $basic_salary, $allowances, $overtime_pay, $deductions, $total_amount, 'pending', $user_id)";
            $pdo->exec($sql);
            $message = "Payroll processed successfully!";
            break;
            
        case 'approve_salary':
            $salary_id = $_POST['salary_id'];
            $sql = "UPDATE salaries SET status = 'paid', processed_at = NOW() WHERE id = $salary_id";
            $pdo->exec($sql);
            $message = "Salary approved and marked as paid!";
            break;
            
        case 'reject_salary':
            $salary_id = $_POST['salary_id'];
            $sql = "UPDATE salaries SET status = 'cancelled' WHERE id = $salary_id";
            $pdo->exec($sql);
            $message = "Salary rejected!";
            break;
    }
}

// Get payroll data
$filter_month = $_GET['month'] ?? date('Y-m');
$filter_status = $_GET['status'] ?? '';

$where_conditions = ["DATE_FORMAT(pay_period, '%Y-%m') = '$filter_month'"];
if ($filter_status) {
    $where_conditions[] = "s.status = '$filter_status'";
}
$where_clause = implode(' AND ', $where_conditions);

$payroll_sql = "
    SELECT s.*, e.name as employee_name, e.employee_code, e.department, e.position
    FROM salaries s
    LEFT JOIN employees e ON s.employee_id = e.id
    WHERE $where_clause
    ORDER BY s.created_at DESC
";

$payroll_records = $pdo->query($payroll_sql)->fetchAll(PDO::FETCH_ASSOC);

// Get employees for new payroll
$employees = $pdo->query("SELECT * FROM employees WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$stats = [
    'total_payroll' => 0,
    'pending_count' => 0,
    'paid_count' => 0,
    'cancelled_count' => 0
];

foreach ($payroll_records as $record) {
    $stats['total_payroll'] += $record['total_amount'];
    switch ($record['status']) {
        case 'pending': $stats['pending_count']++; break;
        case 'paid': $stats['paid_count']++; break;
        case 'cancelled': $stats['cancelled_count']++; break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payroll - BullsCorp Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-money-check-alt mr-2 text-green-600"></i>
                        Manage Payroll
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($username ?? ''); ?></span>
                    <a href="dashboard_modern.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-arrow-left mr-1"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Messages -->
        <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Payroll</p>
                        <p class="text-2xl font-bold text-gray-900">Rp <?php echo number_format($stats['total_payroll'], 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending_count']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-check text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Paid</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['paid_count']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <i class="fas fa-times text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Cancelled</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['cancelled_count']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Payroll List -->
            <div class="lg:col-span-2">
                <!-- Filters -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                            <input type="month" name="month" value="<?php echo htmlspecialchars($filter_month ?? ''); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo $filter_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-filter mr-1"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Payroll Records -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Payroll Records</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Basic</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($payroll_records as $record): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($record['employee_name'] ?? ''); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($record['employee_code'] ?? ''); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M Y', strtotime($record['pay_period'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        Rp <?php echo number_format($record['basic_salary'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        Rp <?php echo number_format($record['total_amount'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php 
                                            switch($record['status']) {
                                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'paid': echo 'bg-green-100 text-green-800'; break;
                                                case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <?php if ($record['status'] === 'pending'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="approve_salary">
                                                <input type="hidden" name="salary_id" value="<?php echo $record['id']; ?>">
                                                <button type="submit" class="text-green-600 hover:text-green-900" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="reject_salary">
                                                <input type="hidden" name="salary_id" value="<?php echo $record['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <button onclick="viewPayslip(<?php echo $record['id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-900" title="View Payslip">
                                                <i class="fas fa-file-invoice"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- New Payroll Form -->
            <div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-plus mr-2"></i>Process New Payroll
                    </h3>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="process_payroll">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                            <select name="employee_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>" data-salary="<?php echo $employee['salary']; ?>">
                                    <?php echo htmlspecialchars($employee['name'] ?? ''); ?> - <?php echo htmlspecialchars($employee['department'] ?? ''); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pay Period</label>
                            <input type="month" name="pay_period" value="<?php echo date('Y-m'); ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Basic Salary</label>
                            <input type="number" name="basic_salary" id="basic_salary" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Allowances</label>
                            <input type="number" name="allowances" value="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Overtime Pay</label>
                            <input type="number" name="overtime_pay" value="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deductions</label>
                            <input type="number" name="deductions" value="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-money-check-alt mr-2"></i>Process Payroll
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-fill basic salary when employee is selected
        document.querySelector('select[name="employee_id"]').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const salary = selectedOption.getAttribute('data-salary');
            if (salary) {
                document.getElementById('basic_salary').value = salary;
            }
        });

        function viewPayslip(salaryId) {
            window.open('payslip.php?id=' + salaryId, '_blank');
        }

        // Vulnerable: Expose payroll data
        console.log('Payroll Records:', <?php echo json_encode($payroll_records); ?>);
    </script>
</body>
</html>