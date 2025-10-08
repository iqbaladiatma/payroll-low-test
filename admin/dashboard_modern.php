<?php
// Modern Admin Dashboard - Enhanced UI/UX with Vulnerabilities
// WARNING: This code contains intentional vulnerabilities!

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Weak authentication check
$user_id = $_SESSION['user_id'] ?? $_GET['user_id'] ?? 1;
$user_role = $_SESSION['role'] ?? $_GET['role'] ?? 'admin';
$username = $_SESSION['username'] ?? 'admin';

// Allow role escalation via URL parameter (vulnerability)
if (isset($_GET['role'])) {
    $_SESSION['role'] = $_GET['role'];
    $user_role = $_GET['role'];
}

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

// Get enhanced dashboard statistics
$stats = [];

// Total employees
$stats['total_employees'] = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$stats['active_employees'] = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn();
$stats['new_employees_month'] = $pdo->query("SELECT COUNT(*) FROM employees WHERE MONTH(created_at) = MONTH(CURRENT_DATE())")->fetchColumn();

// Submissions
$stats['pending_submissions'] = $pdo->query("SELECT COUNT(*) FROM submissions WHERE status = 'pending'")->fetchColumn();
$stats['total_submissions'] = $pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn();
$stats['approved_submissions'] = $pdo->query("SELECT COUNT(*) FROM submissions WHERE status = 'approved'")->fetchColumn();

// Payroll
$stats['monthly_payroll'] = $pdo->query("SELECT SUM(total_amount) FROM salaries WHERE MONTH(pay_period) = MONTH(CURRENT_DATE()) AND YEAR(pay_period) = YEAR(CURRENT_DATE())")->fetchColumn() ?? 0;
$stats['pending_payroll'] = $pdo->query("SELECT COUNT(*) FROM salaries WHERE status = 'pending'")->fetchColumn();

// Attendance today
$today = date('Y-m-d');
$stats['present_today'] = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = '$today' AND status IN ('present', 'late')")->fetchColumn();
$stats['absent_today'] = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = '$today' AND status = 'absent'")->fetchColumn();

// Recent activities
$recent_activities = $pdo->query("
    SELECT sl.*, u.username, e.name as employee_name
    FROM system_logs sl
    LEFT JOIN users u ON sl.user_id = u.id
    LEFT JOIN employees e ON u.employee_id = e.id
    ORDER BY sl.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Recent submissions
$recent_submissions = $pdo->query("
    SELECT s.*, e.name as employee_name 
    FROM submissions s 
    LEFT JOIN employees e ON s.employee_id = e.id 
    ORDER BY s.created_at DESC 
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Attendance overview for chart
$attendance_data = $pdo->query("
    SELECT 
        DATE(date) as date,
        COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
        COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent
    FROM attendance 
    WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
    GROUP BY DATE(date)
    ORDER BY date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Department statistics
$dept_stats = $pdo->query("
    SELECT 
        department,
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active
    FROM employees 
    GROUP BY department
    ORDER BY total DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// System alerts
$alerts = [];

// Check for high pending submissions
if ($stats['pending_submissions'] > 5) {
    $alerts[] = [
        'type' => 'warning',
        'icon' => 'fas fa-exclamation-triangle',
        'title' => 'Pending Submissions',
        'message' => $stats['pending_submissions'] . ' submissions awaiting approval',
        'action' => 'manage_submissions.php',
        'action_text' => 'Review Now'
    ];
}

// Check for employees without salary records
$no_salary_count = $pdo->query("
    SELECT COUNT(*) FROM employees e 
    LEFT JOIN salaries s ON e.id = s.employee_id 
    WHERE s.id IS NULL AND e.status = 'active'
")->fetchColumn();

if ($no_salary_count > 0) {
    $alerts[] = [
        'type' => 'error',
        'icon' => 'fas fa-money-bill-wave',
        'title' => 'Missing Salary Records',
        'message' => $no_salary_count . ' active employees without salary setup',
        'action' => 'manage_payroll.php',
        'action_text' => 'Setup Now'
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card-2 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-card-3 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .stat-card-4 {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <?php include './includes/admin_sidebar.php'; ?>

    <!-- Debug Panel -->
    <div class="fixed top-0 left-0 right-0 bg-red-500 text-white px-4 py-2 z-50 text-sm" style="display: <?php echo isset($_GET['debug']) ? 'block' : 'none'; ?>">
        <div class="container mx-auto flex justify-between items-center">
            <div>
                <strong>🐛 DEBUG MODE:</strong> 
                User: <?php echo $username; ?> (ID: <?php echo $user_id; ?>) | 
                Role: <?php echo $user_role; ?> | 
                DB: <?php echo $db_name; ?>@<?php echo $db_host; ?>
            </div>
            <button onclick="this.parentElement.parentElement.style.display='none'" class="text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div id="mainContent" class="transition-all duration-300">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-gray-700">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-tachometer-alt mr-2 text-blue-600"></i>
                            Dashboard Overview
                        </h1>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Real-time clock -->
                        <div id="currentTime" class="text-sm text-gray-600"></div>
                        
                        <!-- Notifications -->
                        <div class="relative">
                            <button onclick="toggleNotifications()" class="relative p-2 text-gray-500 hover:text-gray-700">
                                <i class="fas fa-bell text-xl"></i>
                                <?php if (count($alerts) > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                    <?php echo count($alerts); ?>
                                </span>
                                <?php endif; ?>
                            </button>
                            
                            <div id="notificationsDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                                <div class="p-4 border-b border-gray-200">
                                    <h3 class="font-semibold text-gray-800">Notifications</h3>
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    <?php if (!empty($alerts)): ?>
                                    <?php foreach ($alerts as $alert): ?>
                                    <div class="p-4 border-b border-gray-100 hover:bg-gray-50">
                                        <div class="flex items-start space-x-3">
                                            <i class="<?php echo $alert['icon']; ?> text-<?php echo $alert['type'] === 'error' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue'); ?>-500 mt-1"></i>
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-800"><?php echo $alert['title']; ?></h4>
                                                <p class="text-sm text-gray-600"><?php echo $alert['message']; ?></p>
                                                <a href="<?php echo $alert['action']; ?>" class="text-sm text-blue-600 hover:text-blue-800 mt-1 inline-block">
                                                    <?php echo $alert['action_text']; ?> →
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <div class="p-4 text-center text-gray-500">
                                        <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                                        <p>All good! No alerts.</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- User Profile & Logout -->
                        <div class="relative">
                            <button onclick="toggleUserMenu()" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <span class="text-sm font-medium"><?php echo htmlspecialchars($username); ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            
                            <div id="userMenuDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                                <div class="p-3 border-b border-gray-200">
                                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($username); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo ucfirst($user_role); ?> Account</p>
                                </div>
                                <a href="profile.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user-edit mr-2"></i>Edit Profile
                                </a>
                                <a href="settings.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-cog mr-2"></i>Settings
                                </a>
                                <div class="border-t border-gray-200"></div>
                                <a href="../public/logout.php" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </a>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="relative">
                            <button onclick="toggleQuickActions()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                <i class="fas fa-plus mr-2"></i>Quick Actions
                            </button>
                            
                            <div id="quickActionsDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                                <a href="add_employee.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user-plus mr-2"></i>Add Employee
                                </a>
                                <a href="manage_submissions.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-clipboard-list mr-2"></i>Review Submissions
                                </a>
                                <a href="manage_payroll.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-money-check-alt mr-2"></i>Process Payroll
                                </a>
                                <a href="?debug=1" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-bug mr-2"></i>Debug Mode
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Employees -->
                <div class="stat-card rounded-xl p-6 text-white card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white text-opacity-80 text-sm">Total Employees</p>
                            <p class="text-3xl font-bold"><?php echo $stats['total_employees']; ?></p>
                            <p class="text-white text-opacity-80 text-xs mt-1">
                                +<?php echo $stats['new_employees_month']; ?> this month
                            </p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-lg p-3">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Pending Submissions -->
                <div class="stat-card-2 rounded-xl p-6 text-white card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white text-opacity-80 text-sm">Pending Submissions</p>
                            <p class="text-3xl font-bold"><?php echo $stats['pending_submissions']; ?></p>
                            <p class="text-white text-opacity-80 text-xs mt-1">
                                <?php echo $stats['total_submissions']; ?> total submissions
                            </p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-lg p-3">
                            <i class="fas fa-clock text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Monthly Payroll -->
                <div class="stat-card-3 rounded-xl p-6 text-white card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white text-opacity-80 text-sm">Monthly Payroll</p>
                            <p class="text-2xl font-bold">Rp <?php echo number_format($stats['monthly_payroll'] / 1000000, 1); ?>M</p>
                            <p class="text-white text-opacity-80 text-xs mt-1">
                                <?php echo $stats['pending_payroll']; ?> pending payments
                            </p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-lg p-3">
                            <i class="fas fa-money-bill-wave text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Today's Attendance -->
                <div class="stat-card-4 rounded-xl p-6 text-white card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white text-opacity-80 text-sm">Today's Attendance</p>
                            <p class="text-3xl font-bold"><?php echo $stats['present_today']; ?></p>
                            <p class="text-white text-opacity-80 text-xs mt-1">
                                <?php echo number_format(($stats['present_today'] / max($stats['active_employees'], 1)) * 100, 1); ?>% attendance rate
                            </p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-lg p-3">
                            <i class="fas fa-calendar-check text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>       
     <!-- Charts and Analytics Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Attendance Chart -->
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                            Weekly Attendance Trend
                        </h3>
                        <div class="flex space-x-2">
                            <button class="text-xs bg-blue-100 text-blue-600 px-3 py-1 rounded-full">7 Days</button>
                            <button class="text-xs text-gray-500 px-3 py-1 rounded-full hover:bg-gray-100">30 Days</button>
                        </div>
                    </div>
                    <div class="relative h-64">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>

                <!-- Department Distribution -->
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-chart-pie mr-2 text-purple-600"></i>
                            Department Distribution
                        </h3>
                        <button class="text-xs text-gray-500 hover:text-gray-700">
                            <i class="fas fa-expand-alt"></i>
                        </button>
                    </div>
                    <div class="relative h-64">
                        <canvas id="departmentChart"></canvas>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <?php foreach (array_slice($dept_stats, 0, 4) as $index => $dept): ?>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 rounded-full" style="background-color: <?php echo ['#3B82F6', '#8B5CF6', '#10B981', '#F59E0B'][$index % 4]; ?>"></div>
                            <span class="text-sm text-gray-600"><?php echo $dept['department']; ?></span>
                            <span class="text-sm font-semibold text-gray-800"><?php echo $dept['total']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activities and Submissions -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Recent Submissions -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6 card-hover">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-clipboard-list mr-2 text-green-600"></i>
                            Recent Submissions
                        </h3>
                        <a href="manage_submissions.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    
                    <div class="space-y-3">
                        <?php foreach (array_slice($recent_submissions, 0, 5) as $submission): ?>
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors border border-gray-200">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                                    <i class="fas fa-<?php 
                                        switch($submission['submission_type']) {
                                            case 'leave': echo 'calendar-alt'; break;
                                            case 'overtime': echo 'clock'; break;
                                            case 'expense': echo 'dollar-sign'; break;
                                            default: echo 'file';
                                        }
                                    ?> text-white text-xs"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        <?php echo htmlspecialchars($submission['employee_name'] ?? 'Unknown'); ?>
                                    </p>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ml-2
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
                                </div>
                                <p class="text-xs text-gray-500 truncate mb-1">
                                    <?php echo htmlspecialchars(substr($submission['description'], 0, 50)) . (strlen($submission['description']) > 50 ? '...' : ''); ?>
                                </p>
                                <div class="flex items-center justify-between">
                                    <p class="text-xs text-gray-400">
                                        <?php echo date('M d, H:i', strtotime($submission['created_at'])); ?>
                                    </p>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        <?php 
                                        switch($submission['status']) {
                                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'approved': echo 'bg-green-100 text-green-800'; break;
                                            case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <i class="fas fa-<?php 
                                            switch($submission['status']) {
                                                case 'pending': echo 'clock'; break;
                                                case 'approved': echo 'check'; break;
                                                case 'rejected': echo 'times'; break;
                                                default: echo 'question';
                                            }
                                        ?> mr-1"></i>
                                        <?php echo ucfirst($submission['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (empty($recent_submissions)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-clipboard-list text-4xl mb-4 text-gray-300"></i>
                        <p>No recent submissions</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- System Activities -->
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-activity mr-2 text-orange-600"></i>
                            System Activity
                        </h3>
                        <button class="text-xs text-gray-500 hover:text-gray-700">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-2 h-2 rounded-full bg-<?php 
                                    switch($activity['action']) {
                                        case 'LOGIN_SUCCESS': echo 'green'; break;
                                        case 'LOGIN_FAILED': echo 'red'; break;
                                        case 'LOGOUT': echo 'gray'; break;
                                        case 'CREATE_SUBMISSION': echo 'blue'; break;
                                        default: echo 'purple';
                                    }
                                ?>-500"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">
                                    <span class="font-medium"><?php echo htmlspecialchars($activity['employee_name'] ?? $activity['username'] ?? 'System'); ?></span>
                                    <?php 
                                    switch($activity['action']) {
                                        case 'LOGIN_SUCCESS': echo 'logged in successfully'; break;
                                        case 'LOGIN_FAILED': echo 'failed to login'; break;
                                        case 'LOGOUT': echo 'logged out'; break;
                                        case 'CREATE_SUBMISSION': echo 'created a new submission'; break;
                                        case 'CLOCK_IN': echo 'clocked in'; break;
                                        case 'CLOCK_OUT': echo 'clocked out'; break;
                                        default: echo strtolower(str_replace('_', ' ', $activity['action']));
                                    }
                                    ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php echo date('M d, H:i', strtotime($activity['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <a href="system_logs.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            View All Logs <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Action Center -->
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">
                    <i class="fas fa-bolt mr-2 text-yellow-600"></i>
                    Quick Action Center
                </h3>
                
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <a href="manage_submissions.php" class="group flex flex-col items-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg hover:from-blue-100 hover:to-blue-200 transition-all duration-300">
                        <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <i class="fas fa-clipboard-list text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 text-center">Manage Submissions</span>
                        <?php if ($stats['pending_submissions'] > 0): ?>
                        <span class="text-xs bg-red-500 text-white px-2 py-1 rounded-full mt-1">
                            <?php echo $stats['pending_submissions']; ?>
                        </span>
                        <?php endif; ?>
                    </a>

                    <a href="add_employee.php" class="group flex flex-col items-center p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-lg hover:from-green-100 hover:to-green-200 transition-all duration-300">
                        <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <i class="fas fa-user-plus text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 text-center">Add Employee</span>
                    </a>

                    <a href="manage_payroll.php" class="group flex flex-col items-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg hover:from-purple-100 hover:to-purple-200 transition-all duration-300">
                        <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <i class="fas fa-money-check-alt text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 text-center">Manage Payroll</span>
                    </a>

                    <a href="attendance_report.php" class="group flex flex-col items-center p-4 bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg hover:from-orange-100 hover:to-orange-200 transition-all duration-300">
                        <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <i class="fas fa-calendar-check text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 text-center">Attendance Report</span>
                    </a>

                    <a href="reports.php" class="group flex flex-col items-center p-4 bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg hover:from-indigo-100 hover:to-indigo-200 transition-all duration-300">
                        <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 text-center">Generate Reports</span>
                    </a>

                    <a href="system_logs.php" class="group flex flex-col items-center p-4 bg-gradient-to-br from-red-50 to-red-100 rounded-lg hover:from-red-100 hover:to-red-200 transition-all duration-300">
                        <div class="w-12 h-12 bg-red-600 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <i class="fas fa-list-alt text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 text-center">System Logs</span>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Real-time clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const clockElement = document.getElementById('currentTime');
            if (clockElement) {
                clockElement.textContent = timeString;
            }
        }
        
        // Dropdown functions
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationsDropdown');
            dropdown.classList.toggle('hidden');
            // Close other dropdowns
            document.getElementById('quickActionsDropdown').classList.add('hidden');
        }
        
        function toggleQuickActions() {
            const dropdown = document.getElementById('quickActionsDropdown');
            dropdown.classList.toggle('hidden');
            // Close other dropdowns
            document.getElementById('notificationsDropdown').classList.add('hidden');
            document.getElementById('userMenuDropdown').classList.add('hidden');
        }
        
        function toggleUserMenu() {
            const dropdown = document.getElementById('userMenuDropdown');
            dropdown.classList.toggle('hidden');
            // Close other dropdowns
            document.getElementById('notificationsDropdown').classList.add('hidden');
            document.getElementById('quickActionsDropdown').classList.add('hidden');
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.relative')) {
                document.getElementById('notificationsDropdown').classList.add('hidden');
                document.getElementById('quickActionsDropdown').classList.add('hidden');
                document.getElementById('userMenuDropdown').classList.add('hidden');
            }
        });
        
        // Initialize clock
        setInterval(updateClock, 1000);
        updateClock();

        // Chart.js configurations
        document.addEventListener('DOMContentLoaded', function() {
            // Attendance Chart
            const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
            new Chart(attendanceCtx, {
                type: 'line',
                data: {
                    labels: [<?php 
                        $labels = [];
                        foreach (array_reverse($attendance_data) as $data) {
                            $labels[] = "'" . date('M d', strtotime($data['date'])) . "'";
                        }
                        echo implode(',', $labels);
                    ?>],
                    datasets: [{
                        label: 'Present',
                        data: [<?php 
                            $present_data = [];
                            foreach (array_reverse($attendance_data) as $data) {
                                $present_data[] = $data['present'];
                            }
                            echo implode(',', $present_data);
                        ?>],
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Late',
                        data: [<?php 
                            $late_data = [];
                            foreach (array_reverse($attendance_data) as $data) {
                                $late_data[] = $data['late'];
                            }
                            echo implode(',', $late_data);
                        ?>],
                        borderColor: 'rgb(251, 191, 36)',
                        backgroundColor: 'rgba(251, 191, 36, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Absent',
                        data: [<?php 
                            $absent_data = [];
                            foreach (array_reverse($attendance_data) as $data) {
                                $absent_data[] = $data['absent'];
                            }
                            echo implode(',', $absent_data);
                        ?>],
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Department Chart
            const departmentCtx = document.getElementById('departmentChart').getContext('2d');
            new Chart(departmentCtx, {
                type: 'doughnut',
                data: {
                    labels: [<?php 
                        $dept_labels = [];
                        foreach ($dept_stats as $dept) {
                            $dept_labels[] = "'" . $dept['department'] . "'";
                        }
                        echo implode(',', $dept_labels);
                    ?>],
                    datasets: [{
                        data: [<?php 
                            $dept_data = [];
                            foreach ($dept_stats as $dept) {
                                $dept_data[] = $dept['total'];
                            }
                            echo implode(',', $dept_data);
                        ?>],
                        backgroundColor: [
                            '#3B82F6', '#8B5CF6', '#10B981', '#F59E0B',
                            '#EF4444', '#6366F1', '#EC4899', '#14B8A6'
                        ]
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
        });

        // Auto-refresh dashboard every 5 minutes
        setTimeout(function() {
            if (!document.hidden) {
                location.reload();
            }
        }, 300000);

        // Vulnerable: Expose sensitive data
        console.log('🔓 Admin Dashboard Data:');
        console.log('Stats:', <?php echo json_encode($stats); ?>);
        console.log('User ID:', <?php echo $user_id; ?>);
        console.log('User Role:', '<?php echo $user_role; ?>');
        console.log('Recent Activities:', <?php echo json_encode($recent_activities); ?>);

        // Keyboard shortcuts for vulnerabilities
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'A') {
                window.location = '?role=admin';
            }
            if (e.ctrlKey && e.shiftKey && e.key === 'U') {
                const newUserId = prompt('Enter User ID to switch to:');
                if (newUserId) {
                    window.location = '?user_id=' + newUserId;
                }
            }
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                window.location = '?debug=1';
            }
        });
    </script>
</body>
</html>