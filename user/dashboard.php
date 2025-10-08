<?php
// Vulnerable User Dashboard - Low Security for Penetration Testing
// WARNING: This code contains intentional vulnerabilities!

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Weak authentication check - allows user impersonation
$user_id = $_SESSION['user_id'] ?? $_GET['user_id'] ?? 1;
$user_role = $_SESSION['role'] ?? $_GET['role'] ?? 'employee';
$username = $_SESSION['username'] ?? 'Unknown User';

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

// Get user data first
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_data = $pdo->query($user_sql)->fetch(PDO::FETCH_ASSOC);

// Get employee data with SQL injection vulnerability
$employee_sql = "SELECT * FROM employees WHERE id = " . ($user_data['employee_id'] ?? $user_id);
$employee = $pdo->query($employee_sql)->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    // Create dummy employee if not found - use username from session
    $employee = [
        'id' => $user_id,
        'name' => $username, // Use username instead of "Test Employee"
        'email' => $user_data['email'] ?? 'user@bullscorp.com',
        'phone' => '081234567890',
        'department' => 'General',
        'position' => 'Employee',
        'hire_date' => date('Y-m-d'),
        'salary' => 5000000,
        'status' => 'active'
    ];
}

// Use correct employee_id for queries
$employee_id_for_queries = $user_data['employee_id'] ?? $user_id;

// Get salary information with potential data exposure
$salary_sql = "SELECT * FROM salaries WHERE employee_id = $employee_id_for_queries ORDER BY pay_period DESC LIMIT 6";
$salary_history = $pdo->query($salary_sql)->fetchAll(PDO::FETCH_ASSOC);

// Get recent submissions
$submissions_sql = "SELECT * FROM submissions WHERE employee_id = $employee_id_for_queries ORDER BY created_at DESC LIMIT 5";
$recent_submissions = $pdo->query($submissions_sql)->fetchAll(PDO::FETCH_ASSOC);

// Get attendance summary (if table exists)
$attendance_sql = "SELECT 
    COUNT(*) as total_days,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days
    FROM attendance WHERE employee_id = $employee_id_for_queries AND MONTH(date) = MONTH(CURRENT_DATE())";

try {
    $attendance = $pdo->query($attendance_sql)->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Default values if attendance table doesn't exist
    $attendance = [
        'total_days' => 20,
        'present_days' => 18,
        'late_days' => 2,
        'absent_days' => 0
    ];
}

// Calculate current month salary (if available)
$current_salary = 0;
$salary_status = 'Not Available';
if (!empty($salary_history)) {
    $latest_salary = $salary_history[0];
    $current_salary = $latest_salary['total_amount'] ?? $employee['salary'];
    $salary_status = $latest_salary['status'] ?? 'Pending';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Debug Panel (Vulnerable) -->
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 mb-4" style="display: <?php echo isset($_GET['debug']) ? 'block' : 'none'; ?>">
        <strong>Debug Info:</strong>
        <ul class="mt-2 text-sm">
            <li>User ID: <?php echo $user_id; ?></li>
            <li>User Role: <?php echo $user_role; ?></li>
            <li>Employee Query: <?php echo htmlspecialchars($employee_sql); ?></li>
            <li>Session Data: <?php echo json_encode($_SESSION); ?></li>
            <li>GET Parameters: <?php echo json_encode($_GET); ?></li>
        </ul>
    </div>

    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-tachometer-alt mr-2 text-blue-600"></i>
                        Employee Dashboard
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($username); ?></span>
                    <a href="?debug=1" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-bug"></i>
                    </a>
                    <a href="../public/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-sign-out-alt mr-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Employee Profile Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center space-x-6">
                <div class="flex-shrink-0">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-3xl"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($employee['name']); ?></h2>
                    <p class="text-lg text-gray-600"><?php echo htmlspecialchars($employee['position']); ?></p>
                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($employee['department']); ?> Department</p>
                    <div class="mt-2 flex items-center space-x-4 text-sm text-gray-600">
                        <span><i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($employee['email']); ?></span>
                        <span><i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($employee['phone'] ?? 'Not provided'); ?></span>
                        <span><i class="fas fa-calendar mr-1"></i>Joined: <?php echo date('M Y', strtotime($employee['hire_date'])); ?></span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Employee ID</div>
                    <div class="text-xl font-bold text-gray-900">#<?php echo str_pad($employee['id'], 4, '0', STR_PAD_LEFT); ?></div>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            <?php echo $employee['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <i class="fas fa-circle mr-1 text-xs"></i>
                            <?php echo ucfirst($employee['status'] ?? 'active'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Current Salary -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Current Salary</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php if ($current_salary > 0): ?>
                                Rp <?php echo number_format($current_salary, 0, ',', '.'); ?>
                            <?php else: ?>
                                <span class="text-gray-400">Not Set</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-xs text-gray-500"><?php echo $salary_status; ?></p>
                    </div>
                </div>
            </div>

            <!-- Attendance This Month -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Attendance</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $attendance['present_days']; ?>/<?php echo $attendance['total_days']; ?></p>
                        <p class="text-xs text-gray-500">This Month</p>
                    </div>
                </div>
            </div>

            <!-- Pending Submissions -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending Requests</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php 
                            $pending_count = 0;
                            foreach ($recent_submissions as $sub) {
                                if ($sub['status'] === 'pending') $pending_count++;
                            }
                            echo $pending_count;
                            ?>
                        </p>
                        <p class="text-xs text-gray-500">Awaiting Approval</p>
                    </div>
                </div>
            </div>

            <!-- Late Days -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Late Days</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $attendance['late_days']; ?></p>
                        <p class="text-xs text-gray-500">This Month</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Salary History -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-chart-line mr-2"></i>Salary History
                        </h3>
                        <a href="salary_details.php" class="text-blue-600 hover:text-blue-800 text-sm">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    
                    <?php if (!empty($salary_history)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Basic Salary</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach (array_slice($salary_history, 0, 3) as $salary): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M Y', strtotime($salary['pay_period'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        Rp <?php echo number_format($salary['basic_salary'] ?? $employee['salary'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
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
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-money-bill-wave text-4xl mb-4"></i>
                        <p>No salary information available</p>
                        <p class="text-sm">Contact HR department for salary details</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Submissions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-clipboard-list mr-2"></i>Recent Submissions
                        </h3>
                        <a href="submit_request.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-plus mr-1"></i>New Request
                        </a>
                    </div>
                    
                    <?php if (!empty($recent_submissions)): ?>
                    <div class="space-y-3">
                        <?php foreach ($recent_submissions as $submission): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php 
                                            switch($submission['submission_type']) {
                                                case 'leave': echo 'bg-blue-100 text-blue-800'; break;
                                                case 'overtime': echo 'bg-purple-100 text-purple-800'; break;
                                                case 'expense': echo 'bg-green-100 text-green-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo ucfirst($submission['submission_type']); ?>
                                        </span>
                                        <span class="text-xs text-gray-500">#<?php echo $submission['id']; ?></span>
                                    </div>
                                    <p class="text-sm text-gray-900 mb-1">
                                        <?php echo htmlspecialchars(substr($submission['description'], 0, 100)) . (strlen($submission['description']) > 100 ? '...' : ''); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?php echo date('M d, Y H:i', strtotime($submission['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="ml-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php 
                                        switch($submission['status']) {
                                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'approved': echo 'bg-green-100 text-green-800'; break;
                                            case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($submission['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-clipboard-list text-4xl mb-4"></i>
                        <p>No submissions yet</p>
                        <a href="submit_request.php" class="text-blue-600 hover:text-blue-800 text-sm">
                            Submit your first request
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>      
      <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-bolt mr-2"></i>Quick Actions
                    </h3>
                    <div class="space-y-3">
                        <a href="submit_request.php" class="block w-full text-left px-4 py-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                            <i class="fas fa-paper-plane text-blue-600 mr-3"></i>
                            <span class="font-medium">Submit Request</span>
                        </a>
                        <a href="attendance.php" class="block w-full text-left px-4 py-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                            <i class="fas fa-calendar-check text-green-600 mr-3"></i>
                            <span class="font-medium">Clock In/Out</span>
                        </a>
                        <a href="payslip.php" class="block w-full text-left px-4 py-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                            <i class="fas fa-file-invoice text-purple-600 mr-3"></i>
                            <span class="font-medium">Download Payslip</span>
                        </a>
                        <a href="profile.php" class="block w-full text-left px-4 py-3 bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors">
                            <i class="fas fa-user-edit text-orange-600 mr-3"></i>
                            <span class="font-medium">Edit Profile</span>
                        </a>
                    </div>
                </div>

                <!-- Employee Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-id-card mr-2"></i>Employee Details
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Employee ID:</span>
                            <span class="font-medium">#<?php echo str_pad($employee['id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Department:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($employee['department']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Position:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($employee['position']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Hire Date:</span>
                            <span class="font-medium"><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status:</span>
                            <span class="font-medium capitalize <?php echo $employee['status'] === 'active' ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $employee['status'] ?? 'active'; ?>
                            </span>
                        </div>
                        <?php if (isset($employee['salary']) && $employee['salary'] > 0): ?>
                        <div class="flex justify-between border-t pt-3">
                            <span class="text-gray-600">Base Salary:</span>
                            <span class="font-medium">Rp <?php echo number_format($employee['salary'], 0, ',', '.'); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Attendance Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-pie mr-2"></i>Attendance Overview
                    </h3>
                    <div class="relative">
                        <canvas id="attendanceChart" width="200" height="200"></canvas>
                    </div>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                                <span>Present</span>
                            </div>
                            <span class="font-medium"><?php echo $attendance['present_days']; ?> days</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                                <span>Late</span>
                            </div>
                            <span class="font-medium"><?php echo $attendance['late_days']; ?> days</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                                <span>Absent</span>
                            </div>
                            <span class="font-medium"><?php echo $attendance['absent_days']; ?> days</span>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-bell mr-2"></i>Notifications
                    </h3>
                    <div class="space-y-3">
                        <?php if ($pending_count > 0): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <i class="fas fa-clock text-yellow-600 mr-2"></i>
                                <div class="text-sm">
                                    <p class="font-medium text-yellow-800">Pending Requests</p>
                                    <p class="text-yellow-600">You have <?php echo $pending_count; ?> pending request(s)</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($attendance['late_days'] > 3): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                                <div class="text-sm">
                                    <p class="font-medium text-red-800">Attendance Warning</p>
                                    <p class="text-red-600">Too many late days this month</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (empty($salary_history)): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                <div class="text-sm">
                                    <p class="font-medium text-blue-800">Salary Information</p>
                                    <p class="text-blue-600">Contact HR for salary setup</p>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                <div class="text-sm">
                                    <p class="font-medium text-green-800">All Good!</p>
                                    <p class="text-green-600">No urgent notifications</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Vulnerable Admin Access Panel -->
                <?php if (isset($_GET['admin_mode']) || $user_role === 'admin'): ?>
                <div class="bg-red-100 border border-red-400 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-red-800 mb-2">
                        <i class="fas fa-user-shield mr-2"></i>Admin Panel
                    </h3>
                    <div class="space-y-2 text-sm">
                        <a href="../admin/dashboard.php" class="block text-red-700 hover:underline">
                            <i class="fas fa-tachometer-alt mr-1"></i>Admin Dashboard
                        </a>
                        <a href="../admin/manage_submissions.php" class="block text-red-700 hover:underline">
                            <i class="fas fa-clipboard-list mr-1"></i>Manage Submissions
                        </a>
                        <a href="?user_id=<?php echo $user_id + 1; ?>" class="block text-red-700 hover:underline">
                            <i class="fas fa-user-friends mr-1"></i>View Other Employee
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Attendance Chart
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Late', 'Absent'],
                datasets: [{
                    data: [
                        <?php echo $attendance['present_days']; ?>,
                        <?php echo $attendance['late_days']; ?>,
                        <?php echo $attendance['absent_days']; ?>
                    ],
                    backgroundColor: [
                        '#10B981',
                        '#F59E0B',
                        '#EF4444'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Vulnerable JavaScript functions
        function switchUser(userId) {
            window.location = '?user_id=' + userId;
        }

        function enableAdminMode() {
            window.location = '?admin_mode=1&user_id=<?php echo $user_id; ?>';
        }

        // Auto-refresh dashboard every 5 minutes
        setTimeout(function() {
            if (!document.hidden) {
                location.reload();
            }
        }, 300000);

        // Expose sensitive data to console (vulnerability)
        console.log('Employee Data:', <?php echo json_encode($employee); ?>);
        console.log('Salary History:', <?php echo json_encode($salary_history); ?>);
        console.log('User Session:', <?php echo json_encode($_SESSION); ?>);

        // Vulnerable AJAX functions
        function quickAction(action) {
            $.post('quick_actions.php', {
                action: action,
                user_id: <?php echo $user_id; ?>
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json');
        }

        // Add vulnerable event listeners
        document.addEventListener('keydown', function(e) {
            // Ctrl+Shift+D for debug mode
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                window.location = '?debug=1&user_id=<?php echo $user_id; ?>';
            }
            // Ctrl+Shift+A for admin mode
            if (e.ctrlKey && e.shiftKey && e.key === 'A') {
                enableAdminMode();
            }
        });

        // Store user data in localStorage (vulnerability)
        localStorage.setItem('currentUser', JSON.stringify({
            id: <?php echo $user_id; ?>,
            name: '<?php echo addslashes($employee['name']); ?>',
            role: '<?php echo $user_role; ?>',
            department: '<?php echo addslashes($employee['department']); ?>'
        }));
    </script>
</body>
</html>