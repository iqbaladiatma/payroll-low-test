<?php
// Vulnerable Reports System - Low Security for Penetration Testing
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

// Get report data based on type
$report_type = $_GET['type'] ?? 'overview';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');

$report_data = [];

switch ($report_type) {
    case 'payroll':
        $report_data = $pdo->query("
            SELECT 
                e.name as employee_name,
                e.department,
                s.pay_period,
                s.basic_salary,
                s.allowances,
                s.overtime_pay,
                s.deductions,
                s.total_amount,
                s.status
            FROM salaries s
            LEFT JOIN employees e ON s.employee_id = e.id
            WHERE s.pay_period BETWEEN '$date_from' AND '$date_to'
            ORDER BY s.pay_period DESC, e.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'attendance':
        $report_data = $pdo->query("
            SELECT 
                e.name as employee_name,
                e.department,
                a.date,
                a.time_in,
                a.time_out,
                a.status,
                CASE 
                    WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL 
                    THEN TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) / 60.0
                    ELSE 0
                END as work_hours
            FROM attendance a
            LEFT JOIN employees e ON a.employee_id = e.id
            WHERE a.date BETWEEN '$date_from' AND '$date_to'
            ORDER BY a.date DESC, e.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'submissions':
        $report_data = $pdo->query("
            SELECT 
                e.name as employee_name,
                e.department,
                s.submission_type,
                s.description,
                s.amount,
                s.status,
                s.created_at,
                approver.name as approved_by_name
            FROM submissions s
            LEFT JOIN employees e ON s.employee_id = e.id
            LEFT JOIN employees approver ON s.approved_by = approver.id
            WHERE s.created_at BETWEEN '$date_from' AND '$date_to'
            ORDER BY s.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    default: // overview
        $report_data = [
            'employees' => $pdo->query("SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'active' THEN 1 END) as active FROM employees")->fetch(PDO::FETCH_ASSOC),
            'payroll' => $pdo->query("SELECT COUNT(*) as total, SUM(total_amount) as total_amount FROM salaries WHERE pay_period BETWEEN '$date_from' AND '$date_to'")->fetch(PDO::FETCH_ASSOC),
            'attendance' => $pdo->query("SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'present' THEN 1 END) as present FROM attendance WHERE date BETWEEN '$date_from' AND '$date_to'")->fetch(PDO::FETCH_ASSOC),
            'submissions' => $pdo->query("SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending FROM submissions WHERE created_at BETWEEN '$date_from' AND '$date_to'")->fetch(PDO::FETCH_ASSOC)
        ];
        break;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - BullsCorp Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-chart-line mr-2 text-purple-600"></i>
                        Reports & Analytics
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($username); ?></span>
                    <a href="dashboard_modern.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-arrow-left mr-1"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Report Type Selection -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-filter mr-2"></i>Report Configuration
            </h3>
            
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                        <option value="payroll" <?php echo $report_type === 'payroll' ? 'selected' : ''; ?>>Payroll Report</option>
                        <option value="attendance" <?php echo $report_type === 'attendance' ? 'selected' : ''; ?>>Attendance Report</option>
                        <option value="submissions" <?php echo $report_type === 'submissions' ? 'selected' : ''; ?>>Submissions Report</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-chart-bar mr-1"></i>Generate Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Report Content -->
        <?php if ($report_type === 'overview'): ?>
        <!-- Overview Report -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Employees</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $report_data['employees']['total']; ?></p>
                        <p class="text-xs text-green-600"><?php echo $report_data['employees']['active']; ?> active</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Payroll</p>
                        <p class="text-2xl font-bold text-gray-900">Rp <?php echo number_format($report_data['payroll']['total_amount'] ?? 0, 0, ',', '.'); ?></p>
                        <p class="text-xs text-gray-600"><?php echo $report_data['payroll']['total']; ?> records</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i class="fas fa-calendar-check text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Attendance</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $report_data['attendance']['present']; ?></p>
                        <p class="text-xs text-gray-600">of <?php echo $report_data['attendance']['total']; ?> records</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clipboard-list text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Submissions</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $report_data['submissions']['total']; ?></p>
                        <p class="text-xs text-yellow-600"><?php echo $report_data['submissions']['pending']; ?> pending</p>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Detailed Reports -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">
                    <?php echo ucfirst($report_type); ?> Report 
                    (<?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>)
                </h3>
                <div class="flex space-x-2">
                    <button onclick="exportReport('csv')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-file-csv mr-1"></i>Export CSV
                    </button>
                    <button onclick="exportReport('pdf')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-file-pdf mr-1"></i>Export PDF
                    </button>
                    <button onclick="printReport()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-print mr-1"></i>Print
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <?php if ($report_type === 'payroll'): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Basic Salary</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Allowances</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Overtime</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deductions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <?php elseif ($report_type === 'attendance'): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time In</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time Out</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Work Hours</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <?php elseif ($report_type === 'submissions'): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved By</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($report_data as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <?php if ($report_type === 'payroll'): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($row['employee_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($row['department']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M Y', strtotime($row['pay_period'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                Rp <?php echo number_format($row['basic_salary'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                Rp <?php echo number_format($row['allowances'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                Rp <?php echo number_format($row['overtime_pay'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                -Rp <?php echo number_format($row['deductions'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                Rp <?php echo number_format($row['total_amount'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php 
                                    switch($row['status']) {
                                        case 'paid': echo 'bg-green-100 text-green-800'; break;
                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            
                            <?php elseif ($report_type === 'attendance'): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($row['employee_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($row['department']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M d, Y', strtotime($row['date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $row['time_in'] ? date('H:i', strtotime($row['time_in'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $row['time_out'] ? date('H:i', strtotime($row['time_out'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo number_format($row['work_hours'], 1); ?>h
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php 
                                    switch($row['status']) {
                                        case 'present': echo 'bg-green-100 text-green-800'; break;
                                        case 'late': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'absent': echo 'bg-red-100 text-red-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            
                            <?php elseif ($report_type === 'submissions'): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($row['employee_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($row['department']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo ucfirst($row['submission_type']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                <?php echo htmlspecialchars(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : ''); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $row['amount'] > 0 ? 'Rp ' . number_format($row['amount'], 0, ',', '.') : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php 
                                    switch($row['status']) {
                                        case 'approved': echo 'bg-green-100 text-green-800'; break;
                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($row['approved_by_name'] ?? '-'); ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function exportReport(format) {
            const params = new URLSearchParams(window.location.search);
            params.set('export', format);
            window.open('export_report.php?' + params.toString(), '_blank');
        }

        function printReport() {
            window.print();
        }

        // Vulnerable: Expose report data
        console.log('Report Data:', <?php echo json_encode($report_data); ?>);
        console.log('Report Type:', '<?php echo $report_type; ?>');
    </script>
</body>
</html>