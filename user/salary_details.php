<?php
// Vulnerable Salary Details Page - Low Security for Penetration Testing
// WARNING: This code contains intentional vulnerabilities!

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Weak authentication - allows viewing other employees' salary
$user_id = $_SESSION['user_id'] ?? $_GET['user_id'] ?? $_GET['employee_id'] ?? 1;
$view_user_id = $_GET['view_user'] ?? $user_id;

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

// Get employee data with SQL injection vulnerability
$employee_sql = "SELECT * FROM employees WHERE id = $view_user_id";
$employee = $pdo->query($employee_sql)->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die("Employee not found");
}

// Get salary history with potential data exposure
$year_filter = $_GET['year'] ?? date('Y');
$month_filter = $_GET['month'] ?? '';

$where_conditions = ["employee_id = $view_user_id"];
if ($year_filter) {
    $where_conditions[] = "YEAR(pay_period) = '$year_filter'";
}
if ($month_filter) {
    $where_conditions[] = "MONTH(pay_period) = '$month_filter'";
}

$where_clause = implode(' AND ', $where_conditions);
$salary_sql = "SELECT * FROM salaries WHERE $where_clause ORDER BY pay_period DESC";
$salary_history = $pdo->query($salary_sql)->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_earned = 0;
$total_deductions = 0;
foreach ($salary_history as $salary) {
    $total_earned += $salary['total_amount'];
    $total_deductions += ($salary['deductions'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Details - <?php echo htmlspecialchars($employee['name']); ?> - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Debug Panel -->
    <div class="bg-purple-100 border border-purple-400 text-purple-700 px-4 py-3 mb-4" style="display: <?php echo isset($_GET['debug']) ? 'block' : 'none'; ?>">
        <strong>Debug Info:</strong>
        <ul class="mt-2 text-sm">
            <li>Viewing User ID: <?php echo $view_user_id; ?></li>
            <li>Current User ID: <?php echo $user_id; ?></li>
            <li>Employee Query: <?php echo htmlspecialchars($employee_sql); ?></li>
            <li>Salary Query: <?php echo htmlspecialchars($salary_sql); ?></li>
            <li>Total Records: <?php echo count($salary_history); ?></li>
        </ul>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-money-bill-wave mr-2"></i>Salary Details
                </h1>
                <p class="text-gray-600 mt-1">Employee: <?php echo htmlspecialchars($employee['name']); ?></p>
            </div>
            <div class="flex space-x-2">
                <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-arrow-left mr-1"></i>Back
                </a>
                <a href="?debug=1&view_user=<?php echo $view_user_id; ?>" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-bug mr-1"></i>Debug
                </a>
            </div>
        </div>

        <!-- Employee Info Card -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Employee ID</h3>
                    <p class="text-lg font-semibold">#<?php echo str_pad($employee['id'], 4, '0', STR_PAD_LEFT); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Department</h3>
                    <p class="text-lg font-semibold"><?php echo htmlspecialchars($employee['department']); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Position</h3>
                    <p class="text-lg font-semibold"><?php echo htmlspecialchars($employee['position']); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Base Salary</h3>
                    <p class="text-lg font-semibold">Rp <?php echo number_format($employee['salary'], 0, ',', '.'); ?></p>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-coins text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Earned</p>
                        <p class="text-2xl font-bold text-gray-900">Rp <?php echo number_format($total_earned, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <i class="fas fa-minus-circle text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Deductions</p>
                        <p class="text-2xl font-bold text-gray-900">Rp <?php echo number_format($total_deductions, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Net Income</p>
                        <p class="text-2xl font-bold text-gray-900">Rp <?php echo number_format($total_earned - $total_deductions, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="hidden" name="view_user" value="<?php echo $view_user_id; ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                    <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year_filter == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                    <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">All Months</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $month_filter == $m ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">View Employee</label>
                    <input type="number" name="view_user" value="<?php echo $view_user_id; ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md"
                           placeholder="Employee ID">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-filter mr-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Salary History Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-history mr-2"></i>Salary History
                </h3>
            </div>
            
            <?php if (!empty($salary_history)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Basic Salary</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Allowances</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Overtime</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deductions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($salary_history as $salary): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo date('M Y', strtotime($salary['pay_period'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                Rp <?php echo number_format($salary['basic_salary'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                Rp <?php echo number_format($salary['allowances'] ?? 0, 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                Rp <?php echo number_format($salary['overtime_pay'] ?? 0, 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                -Rp <?php echo number_format($salary['deductions'] ?? 0, 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                Rp <?php echo number_format($salary['total_amount'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php 
                                    switch($salary['status']) {
                                        case 'paid': echo 'bg-green-100 text-green-800'; break;
                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'processing': echo 'bg-blue-100 text-blue-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($salary['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="payslip.php?id=<?php echo $salary['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900" title="View Payslip">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    <a href="download_payslip.php?id=<?php echo $salary['id']; ?>" 
                                       class="text-green-600 hover:text-green-900" title="Download PDF">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <!-- Vulnerable direct access -->
                                    <a href="?view_salary_details=<?php echo $salary['id']; ?>&debug=1" 
                                       class="text-purple-600 hover:text-purple-900" title="Debug Details">
                                        <i class="fas fa-bug"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-money-bill-wave text-6xl mb-4"></i>
                <h3 class="text-lg font-medium mb-2">No Salary Records Found</h3>
                <p>No salary information available for the selected period.</p>
                <p class="text-sm mt-2">Contact HR department for assistance.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Vulnerable Debug Section -->
        <?php if (isset($_GET['view_salary_details'])): ?>
        <div class="mt-6 bg-red-50 border border-red-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-red-800 mb-4">Debug: Salary Details</h3>
            <?php
            $debug_id = $_GET['view_salary_details'];
            $debug_sql = "SELECT * FROM salaries WHERE id = $debug_id";
            $debug_salary = $pdo->query($debug_sql)->fetch(PDO::FETCH_ASSOC);
            ?>
            <pre class="bg-red-100 p-4 rounded text-sm overflow-x-auto"><?php print_r($debug_salary); ?></pre>
            <div class="mt-4 text-sm text-red-700">
                <p><strong>Query:</strong> <?php echo htmlspecialchars($debug_sql); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Vulnerable JavaScript functions
        function viewOtherEmployee(employeeId) {
            window.location = '?view_user=' + employeeId;
        }

        function exportSalaryData() {
            // Vulnerable - exports all data without authorization
            window.open('export_salary.php?employee_id=<?php echo $view_user_id; ?>&format=csv', '_blank');
        }

        // Expose sensitive data
        console.log('Employee Data:', <?php echo json_encode($employee); ?>);
        console.log('Salary History:', <?php echo json_encode($salary_history); ?>);
        console.log('Viewing User ID:', <?php echo $view_user_id; ?>);

        // Add keyboard shortcuts for vulnerabilities
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'E') {
                exportSalaryData();
            }
            if (e.ctrlKey && e.shiftKey && e.key === 'U') {
                const newUserId = prompt('Enter Employee ID to view:');
                if (newUserId) {
                    viewOtherEmployee(newUserId);
                }
            }
        });
    </script>
</body>
</html>