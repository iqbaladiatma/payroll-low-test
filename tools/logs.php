<?php
session_start();
require_once './config/database.php';

// Vulnerable log viewer - minimal access control
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get system logs from database
$logs_query = "SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 50";
$logs_result = $db->query($logs_query);
$system_logs = $logs_result ? $logs_result->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-file-alt mr-2"></i>System Logs
            </h1>
            <a href="../public/index.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-database mr-2 text-green-600"></i>Database Activity Logs
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Time</th>
                            <th class="px-4 py-2 text-left">User</th>
                            <th class="px-4 py-2 text-left">Action</th>
                            <th class="px-4 py-2 text-left">Table</th>
                            <th class="px-4 py-2 text-left">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($system_logs)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                No logs available. System logging may not be configured.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($system_logs as $log): ?>
                        <tr class="border-t">
                            <td class="px-4 py-2 font-mono text-xs"><?php echo $log['created_at']; ?></td>
                            <td class="px-4 py-2"><?php echo $log['user_id'] ?? 'System'; ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($log['action']); ?></td>
                            <td class="px-4 py-2"><?php echo $log['table_name'] ?? '-'; ?></td>
                            <td class="px-4 py-2 font-mono text-xs"><?php echo $log['ip_address'] ?? '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-terminal mr-2 text-blue-600"></i>Simulated Access Logs
            </h2>
            <div class="bg-black text-green-400 p-4 rounded font-mono text-sm overflow-auto h-96">
                <?php
                // Simulate access logs with sensitive information
                $logs = [
                    date('Y-m-d H:i:s') . " - User 'admin' logged in from IP: " . $_SERVER['REMOTE_ADDR'],
                    date('Y-m-d H:i:s', strtotime('-1 minute')) . " - SQL Query: SELECT * FROM users WHERE username = 'admin'",
                    date('Y-m-d H:i:s', strtotime('-2 minutes')) . " - Payroll processed for employee ID: 1, Amount: 8000000",
                    date('Y-m-d H:i:s', strtotime('-3 minutes')) . " - File uploaded: document.pdf",
                    date('Y-m-d H:i:s', strtotime('-4 minutes')) . " - Failed login attempt: username='test', password='password123'",
                    date('Y-m-d H:i:s', strtotime('-5 minutes')) . " - Database backup created: payroll_backup_" . date('Ymd') . ".sql",
                    date('Y-m-d H:i:s', strtotime('-6 minutes')) . " - User '" . $_SESSION['username'] . "' accessed employee data",
                    date('Y-m-d H:i:s', strtotime('-7 minutes')) . " - Search performed: query='john'",
                    date('Y-m-d H:i:s', strtotime('-8 minutes')) . " - Employee record updated: ID=3",
                    date('Y-m-d H:i:s', strtotime('-9 minutes')) . " - Payroll report generated",
                    date('Y-m-d H:i:s', strtotime('-10 minutes')) . " - System maintenance completed",
                    date('Y-m-d H:i:s', strtotime('-11 minutes')) . " - Database connection established",
                    date('Y-m-d H:i:s', strtotime('-12 minutes')) . " - User session started: " . session_id(),
                    date('Y-m-d H:i:s', strtotime('-13 minutes')) . " - Configuration loaded successfully",
                    date('Y-m-d H:i:s', strtotime('-14 minutes')) . " - Server started on port " . $_SERVER['SERVER_PORT']
                ];
                
                foreach ($logs as $log) {
                    echo $log . "\n";
                }
                ?>
            </div>
        </div>
        
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="mt-6 bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">
                <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i>Error Logs (Admin Only)
            </h2>
            <div class="bg-red-900 text-red-100 p-4 rounded font-mono text-sm overflow-auto h-64">
                <?php
                $error_logs = [
                    "ERROR: Potential SQL injection attempt detected",
                    "WARNING: Unvalidated input detected in search parameter",
                    "ERROR: File upload validation bypassed",
                    "CRITICAL: Sensitive data exposed in API response",
                    "ERROR: Session management vulnerability detected",
                    "WARNING: Weak password policy - password 'admin123' accepted",
                    "ERROR: CSRF token validation failed",
                    "WARNING: XSS payload detected in user input",
                    "ERROR: Directory traversal attempt blocked",
                    "CRITICAL: Database credentials exposed in error message"
                ];
                
                foreach ($error_logs as $i => $error) {
                    echo date('Y-m-d H:i:s', strtotime("-$i minutes")) . " - " . $error . "\n";
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>