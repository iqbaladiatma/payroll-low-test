<?php
// Vulnerable System Logs - Low Security for Penetration Testing
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

// Get filter parameters
$action_filter = $_GET['action'] ?? '';
$user_filter = $_GET['user'] ?? '';
$date_filter = $_GET['date'] ?? '';
$limit = $_GET['limit'] ?? 50;

// Build where conditions (vulnerable to SQL injection)
$where_conditions = [];

if ($action_filter) {
    $where_conditions[] = "sl.action LIKE '%$action_filter%'";
}
if ($user_filter) {
    $where_conditions[] = "u.username LIKE '%$user_filter%'";
}
if ($date_filter) {
    $where_conditions[] = "DATE(sl.created_at) = '$date_filter'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get system logs
$logs_sql = "
    SELECT sl.*, u.username, e.name as employee_name
    FROM system_logs sl
    LEFT JOIN users u ON sl.user_id = u.id
    LEFT JOIN employees e ON u.employee_id = e.id
    $where_clause
    ORDER BY sl.created_at DESC
    LIMIT $limit
";

$logs = $pdo->query($logs_sql)->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total_logs,
        COUNT(CASE WHEN action = 'LOGIN_SUCCESS' THEN 1 END) as login_success,
        COUNT(CASE WHEN action = 'LOGIN_FAILED' THEN 1 END) as login_failed,
        COUNT(CASE WHEN DATE(created_at) = CURRENT_DATE() THEN 1 END) as today_logs
    FROM system_logs
";
$stats = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);

// Get unique actions for filter
$actions = $pdo->query("SELECT DISTINCT action FROM system_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

// Get users for filter
$users = $pdo->query("SELECT DISTINCT username FROM users ORDER BY username")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - BullsCorp Admin</title>
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
                        <i class="fas fa-list-alt mr-2 text-red-600"></i>
                        System Logs
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($username); ?></span>
                    <a href="dashboard_modern.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-arrow-left mr-1"></i>Dashboard
                    </a>
                    <button onclick="clearLogs()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-trash mr-1"></i>Clear Logs
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-list text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Logs</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_logs']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-sign-in-alt text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Successful Logins</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['login_success']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Failed Logins</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['login_failed']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i class="fas fa-calendar-day text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Today's Logs</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['today_logs']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-filter mr-2"></i>Filters
            </h3>
            
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                    <select name="action" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $action): ?>
                        <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($action); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                    <select name="user" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo htmlspecialchars($user); ?>" <?php echo $user_filter === $user ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Limit</label>
                    <select name="limit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                        <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>500</option>
                        <option value="1000" <?php echo $limit == 1000 ? 'selected' : ''; ?>>1000</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-search mr-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">System Activity Logs</h3>
                <div class="flex space-x-2">
                    <button onclick="exportLogs('csv')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-file-csv mr-1"></i>Export CSV
                    </button>
                    <button onclick="refreshLogs()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-sync-alt mr-1"></i>Refresh
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User Agent</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($log['employee_name'] ?? $log['username'] ?? 'System'); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    ID: <?php echo $log['user_id'] ?? 'N/A'; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php 
                                    switch($log['action']) {
                                        case 'LOGIN_SUCCESS': echo 'bg-green-100 text-green-800'; break;
                                        case 'LOGIN_FAILED': echo 'bg-red-100 text-red-800'; break;
                                        case 'LOGOUT': echo 'bg-gray-100 text-gray-800'; break;
                                        case 'CREATE_SUBMISSION': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'CLOCK_IN': echo 'bg-purple-100 text-purple-800'; break;
                                        case 'CLOCK_OUT': echo 'bg-orange-100 text-orange-800'; break;
                                        default: echo 'bg-yellow-100 text-yellow-800';
                                    }
                                    ?>">
                                    <i class="fas fa-<?php 
                                        switch($log['action']) {
                                            case 'LOGIN_SUCCESS': echo 'sign-in-alt'; break;
                                            case 'LOGIN_FAILED': echo 'exclamation-triangle'; break;
                                            case 'LOGOUT': echo 'sign-out-alt'; break;
                                            case 'CREATE_SUBMISSION': echo 'plus'; break;
                                            case 'CLOCK_IN': echo 'clock'; break;
                                            case 'CLOCK_OUT': echo 'clock'; break;
                                            default: echo 'info';
                                        }
                                    ?> mr-1"></i>
                                    <?php echo str_replace('_', ' ', $log['action']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                <?php echo htmlspecialchars($log['description'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>">
                                <?php echo htmlspecialchars(substr($log['user_agent'] ?? '', 0, 50)) . (strlen($log['user_agent'] ?? '') > 50 ? '...' : ''); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($logs)): ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-list-alt text-6xl mb-4 text-gray-300"></i>
                <h3 class="text-lg font-medium mb-2">No Logs Found</h3>
                <p>No system logs match your current filters.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function clearLogs() {
            if (confirm('Are you sure you want to clear all system logs? This action cannot be undone.')) {
                // Vulnerable - no CSRF protection
                window.location = 'clear_logs.php';
            }
        }

        function exportLogs(format) {
            // Vulnerable - direct parameter passing
            const params = new URLSearchParams(window.location.search);
            params.set('format', format);
            window.open('export_logs.php?' + params.toString(), '_blank');
        }

        function refreshLogs() {
            location.reload();
        }

        // Auto-refresh every 30 seconds
        setInterval(function() {
            if (!document.hidden) {
                location.reload();
            }
        }, 30000);

        // Vulnerable: Expose logs data
        console.log('System Logs:', <?php echo json_encode($logs); ?>);
        console.log('Stats:', <?php echo json_encode($stats); ?>);
        console.log('SQL Query:', '<?php echo addslashes($logs_sql); ?>');
    </script>
</body>
</html>