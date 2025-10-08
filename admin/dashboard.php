<?php
// Vulnerable Admin Dashboard - Low Security for Penetration Testing
// WARNING: This code contains intentional vulnerabilities for educational purposes!
// DO NOT USE IN A PRODUCTION ENVIRONMENT.

session_start();

// Mengaktifkan tampilan error untuk mempermudah debugging saat pentest
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- VULNERABILITY 1: Broken Access Control ---
// User ID dan Role diambil langsung dari URL ($_GET) jika tidak ada di session.
// Siapapun bisa menjadi user lain atau menjadi admin.
// Contoh: /dashboard.php?user_id=1&role=admin
$user_id = $_SESSION['user_id'] ?? $_GET['user_id'] ?? 1;
$user_role = $_SESSION['role'] ?? $_GET['role'] ?? 'admin';
$username = $_SESSION['username'] ?? 'admin';

// Fitur ini secara eksplisit mengizinkan eskalasi peran melalui URL.
if (isset($_GET['role'])) {
    $_SESSION['role'] = $_GET['role'];
    $user_role = $_GET['role'];
}

// --- VULNERABILITY 2: Hardcoded Credentials ---
// Kredensial database ditulis langsung di kode. Sangat berbahaya jika kode bocor.
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

// Get dashboard statistics
$stats = [];
$stats['total_employees'] = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$stats['active_employees'] = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn();
$stats['pending_submissions'] = $pdo->query("SELECT COUNT(*) FROM submissions WHERE status = 'pending'")->fetchColumn();
$stats['monthly_payroll'] = $pdo->query("SELECT SUM(total_amount) FROM salaries WHERE MONTH(pay_period) = MONTH(CURRENT_DATE()) AND YEAR(pay_period) = YEAR(CURRENT_DATE())")->fetchColumn() ?? 0;

// Recent submissions
$recent_submissions = $pdo->query("
    SELECT s.*, e.name as employee_name 
    FROM submissions s 
    LEFT JOIN employees e ON s.employee_id = e.id 
    ORDER BY s.created_at DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Recent employees
$recent_employees = $pdo->query("
    SELECT * FROM employees 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// System alerts
$alerts = [];
if ($stats['pending_submissions'] > 10) {
    $alerts[] = [
        'type' => 'warning',
        'message' => 'High number of pending submissions (' . $stats['pending_submissions'] . ')',
        'action' => 'manage_submissions.php'
    ];
}

$no_salary_count = $pdo->query("
    SELECT COUNT(*) FROM employees e 
    LEFT JOIN salaries s ON e.id = s.employee_id 
    WHERE s.id IS NULL AND e.status = 'active'
")->fetchColumn();

if ($no_salary_count > 0) {
    $alerts[] = [
        'type' => 'error',
        'message' => $no_salary_count . ' active employees without salary records',
        'action' => 'manage_employees.php'
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
</head>
<body class="bg-gray-100">
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mb-4" style="display: <?php echo isset($_GET['debug']) ? 'block' : 'none'; ?>">
        <strong>Debug Info:</strong>
        <ul class="mt-2 text-sm">
            <li>User ID: <?php echo $user_id; ?></li>
            <li>User Role: <?php echo $user_role; ?></li>
            <li>Username: <?php echo $username; ?></li>
            <li>Session Data: <?php print_r($_SESSION); ?></li>
            <li>Database: <?php echo $db_name; ?>@<?php echo $db_host; ?></li>
        </ul>
    </div>

    <nav class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-tachometer-alt mr-2 text-blue-600"></i>
                        Admin Dashboard
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <?php echo $username; ?></span>
                    <a href="?debug=1" class="text-blue-600 hover:text-blue-800" title="Enable Debug Mode">
                        <i class="fas fa-bug"></i>
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-sign-out-alt mr-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <script>
        // Redirect to modern dashboard
        window.location.href = 'dashboard_modern.php';
    </script>
    
    <div class="container mx-auto px-4 py-8">
        <?php if (!empty($alerts)): ?>
        <div class="mb-6">
            <?php foreach ($alerts as $alert): ?>
            <div class="bg-<?php echo $alert['type'] === 'error' ? 'red' : 'yellow'; ?>-100 border border-<?php echo $alert['type'] === 'error' ? 'red' : 'yellow'; ?>-400 text-<?php echo $alert['type'] === 'error' ? 'red' : 'yellow'; ?>-700 px-4 py-3 rounded mb-2">
                <div class="flex justify-between items-center">
                    <span><?php echo $alert['message']; // No escaping for potential XSS practice ?></span>
                    <a href="<?php echo $alert['action']; ?>" class="text-sm underline">Take Action</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6"><p>Total Employees: <?php echo $stats['total_employees']; ?></p></div>
            <div class="bg-white rounded-lg shadow p-6"><p>Active Employees: <?php echo $stats['active_employees']; ?></p></div>
            <div class="bg-white rounded-lg shadow p-6"><p>Pending Submissions: <?php echo $stats['pending_submissions']; ?></p></div>
            <div class="bg-white rounded-lg shadow p-6"><p>Monthly Payroll: Rp <?php echo number_format($stats['monthly_payroll'], 0, ',', '.'); ?></p></div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Actions</h2>
            </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800">Recent Submissions</h3>
                <?php if (!empty($recent_submissions)): ?>
                <div class="space-y-3 mt-4">
                    <?php foreach ($recent_submissions as $submission): ?>
                    <div class="border p-3">
                        <p><strong><?php echo $submission['employee_name'] ?? 'Unknown'; ?></strong> - <?php echo $submission['submission_type']; ?></p>
                        <p><?php echo $submission['description']; ?></p>
                        <small><?php echo $submission['status']; ?> on <?php echo $submission['created_at']; ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p>No recent submissions</p>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800">Recent Employees</h3>
                <?php if (!empty($recent_employees)): ?>
                <div class="space-y-3 mt-4">
                    <?php foreach ($recent_employees as $employee): ?>
                    <div class="border p-3">
                        <p><strong><?php echo $employee['name']; ?></strong> (<?php echo $employee['status']; ?>)</p>
                        <small><?php echo $employee['position']; ?> - <?php echo $employee['department']; ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p>No employees found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Fungsi untuk switch user via URL parameter
        function switchUser(userId) {
            window.location = '?user_id=' + userId;
        }

        // Fungsi untuk eskalasi role via URL parameter
        function escalateRole(role) {
            window.location = '?role=' + role;
        }

        // --- VULNERABILITY 5: Sensitive Data Exposure in Console ---
        // Membocorkan data internal ke console log browser
        console.log('Admin Dashboard Data (INTENTIONALLY EXPOSED):');
        console.log('Stats:', <?php echo json_encode($stats); ?>);
        console.log('User ID:', <?php echo json_encode($user_id); ?>);
        console.log('User Role:', <?php echo json_encode($user_role); ?>);
        console.log('Session:', <?php echo json_encode($_SESSION); ?>);

        // Shortcut keyboard untuk memudahkan eksploitasi
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'A') { // Ctrl+Shift+A
                console.log('Escalating to admin...');
                escalateRole('admin');
            }
            if (e.ctrlKey && e.shiftKey && e.key === 'U') { // Ctrl+Shift+U
                const newUserId = prompt('Enter User ID to switch to:');
                if (newUserId) {
                    switchUser(newUserId);
                }
            }
        });
    </script>
</body>
</html>