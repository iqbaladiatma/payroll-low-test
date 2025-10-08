<?php
// Vulnerable Attendance System - Low Security for Penetration Testing
// WARNING: This code contains intentional vulnerabilities!

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Weak authentication check
$user_id = $_SESSION['user_id'] ?? $_GET['user_id'] ?? 1;
$employee_id = $_SESSION['employee_id'] ?? $_GET['employee_id'] ?? $user_id;
$username = $_SESSION['username'] ?? 'employee';

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
$user_data = $pdo->query("SELECT * FROM users WHERE id = $user_id")->fetch(PDO::FETCH_ASSOC);

// Get employee data - if employee_id exists in users table, use it, otherwise use user_id
$actual_employee_id = $user_data['employee_id'] ?? $user_id;
$employee = $pdo->query("SELECT * FROM employees WHERE id = $actual_employee_id")->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    // Create virtual employee record for new users
    $employee = [
        'id' => $user_id,
        'name' => $username,
        'email' => $user_data['email'] ?? 'user@bullscorp.com',
        'phone' => '081234567890',
        'department' => 'General',
        'position' => 'Employee',
        'hire_date' => date('Y-m-d'),
        'salary' => 5000000,
        'status' => 'active'
    ];
    // Use user_id as employee_id for attendance tracking
    $employee_id = $user_id;
} else {
    $employee_id = $actual_employee_id;
}

$message = '';
$error = '';
$today = date('Y-m-d');

// Check today's attendance
$today_attendance = $pdo->query("
    SELECT * FROM attendance 
    WHERE employee_id = $employee_id AND date = '$today'
")->fetch(PDO::FETCH_ASSOC);

// Handle attendance actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $current_time = date('H:i:s');
    
    switch ($action) {
        case 'clock_in':
            if ($today_attendance) {
                $error = "You have already clocked in today!";
            } else {
                // Vulnerable SQL injection
                $sql = "INSERT INTO attendance (employee_id, date, time_in, status) VALUES ($employee_id, '$today', '$current_time', 'present')";
                $pdo->exec($sql);
                $message = "Successfully clocked in at $current_time";
                
                // Log activity
                $pdo->exec("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES ($user_id, 'CLOCK_IN', 'Employee clocked in', '" . $_SERVER['REMOTE_ADDR'] . "')");
                
                // Refresh data
                $today_attendance = $pdo->query("SELECT * FROM attendance WHERE employee_id = $employee_id AND date = '$today'")->fetch(PDO::FETCH_ASSOC);
            }
            break;
            
        case 'clock_out':
            if (!$today_attendance) {
                $error = "You must clock in first!";
            } elseif ($today_attendance['time_out']) {
                $error = "You have already clocked out today!";
            } else {
                // Calculate work hours
                $time_in = $today_attendance['time_in'];
                $work_hours = (strtotime($current_time) - strtotime($time_in)) / 3600;
                
                // Determine status based on work hours
                $status = 'present';
                if ($work_hours < 4) {
                    $status = 'half_day';
                }
                
                // Vulnerable SQL injection
                $sql = "UPDATE attendance SET time_out = '$current_time', status = '$status' WHERE id = " . $today_attendance['id'];
                $pdo->exec($sql);
                $message = "Successfully clocked out at $current_time (Work hours: " . number_format($work_hours, 2) . ")";
                
                // Log activity
                $pdo->exec("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES ($user_id, 'CLOCK_OUT', 'Employee clocked out', '" . $_SERVER['REMOTE_ADDR'] . "')");
                
                // Refresh data
                $today_attendance = $pdo->query("SELECT * FROM attendance WHERE employee_id = $employee_id AND date = '$today'")->fetch(PDO::FETCH_ASSOC);
            }
            break;
            
        case 'break_start':
            // Simple break tracking (vulnerable)
            $pdo->exec("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES ($user_id, 'BREAK_START', 'Employee started break', '" . $_SERVER['REMOTE_ADDR'] . "')");
            $message = "Break started at $current_time";
            break;
            
        case 'break_end':
            // Simple break tracking (vulnerable)
            $pdo->exec("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES ($user_id, 'BREAK_END', 'Employee ended break', '" . $_SERVER['REMOTE_ADDR'] . "')");
            $message = "Break ended at $current_time";
            break;
    }
}

// Get attendance history (last 30 days)
$attendance_history = $pdo->query("
    SELECT * FROM attendance 
    WHERE employee_id = $employee_id 
    ORDER BY date DESC 
    LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$stats = [];
$stats['total_days'] = count($attendance_history);
$stats['present_days'] = 0;
$stats['late_days'] = 0;
$stats['absent_days'] = 0;
$stats['total_hours'] = 0;

foreach ($attendance_history as $record) {
    switch ($record['status']) {
        case 'present':
            $stats['present_days']++;
            break;
        case 'late':
            $stats['late_days']++;
            break;
        case 'absent':
            $stats['absent_days']++;
            break;
    }
    
    if ($record['time_in'] && $record['time_out']) {
        $hours = (strtotime($record['time_out']) - strtotime($record['time_in'])) / 3600;
        $stats['total_hours'] += $hours;
    }
}

$stats['avg_hours'] = $stats['total_days'] > 0 ? $stats['total_hours'] / $stats['total_days'] : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - BullsCorp Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        // Real-time clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID');
            const dateString = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('current-time').textContent = timeString;
            document.getElementById('current-date').textContent = dateString;
        }
        
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</head>
<body class="bg-gray-100">
    <!-- Debug Panel -->
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 mb-4" style="display: <?php echo isset($_GET['debug']) ? 'block' : 'none'; ?>">
        <strong>Debug Info:</strong>
        <ul class="mt-2 text-sm">
            <li>User ID: <?php echo $user_id; ?></li>
            <li>Employee ID: <?php echo $employee_id; ?></li>
            <li>Today's Attendance: <?php echo json_encode($today_attendance); ?></li>
        </ul>
    </div>

    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-clock mr-2 text-blue-600"></i>
                        Attendance System
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($employee['name']); ?></span>
                    <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Clock In/Out Panel -->
            <div class="lg:col-span-2">
                <!-- Current Time Display -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-8 text-white mb-6">
                    <div class="text-center">
                        <div class="text-6xl font-bold mb-2" id="current-time">--:--:--</div>
                        <div class="text-xl opacity-90" id="current-date">Loading...</div>
                        <div class="mt-4 text-sm opacity-75">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            BullsCorp Office - Jakarta, Indonesia
                        </div>
                    </div>
                </div>

                <!-- Today's Status -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-calendar-day mr-2"></i>Today's Status
                    </h3>
                    
                    <?php if ($today_attendance): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <i class="fas fa-sign-in-alt text-green-600 text-2xl mb-2"></i>
                            <div class="text-sm text-gray-600">Clock In</div>
                            <div class="text-lg font-bold text-green-600">
                                <?php echo $today_attendance['time_in'] ? date('H:i', strtotime($today_attendance['time_in'])) : 'Not yet'; ?>
                            </div>
                        </div>
                        
                        <div class="text-center p-4 bg-red-50 rounded-lg">
                            <i class="fas fa-sign-out-alt text-red-600 text-2xl mb-2"></i>
                            <div class="text-sm text-gray-600">Clock Out</div>
                            <div class="text-lg font-bold text-red-600">
                                <?php echo $today_attendance['time_out'] ? date('H:i', strtotime($today_attendance['time_out'])) : 'Not yet'; ?>
                            </div>
                        </div>
                        
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <i class="fas fa-hourglass-half text-blue-600 text-2xl mb-2"></i>
                            <div class="text-sm text-gray-600">Work Hours</div>
                            <div class="text-lg font-bold text-blue-600">
                                <?php 
                                if ($today_attendance['time_in'] && $today_attendance['time_out']) {
                                    $hours = (strtotime($today_attendance['time_out']) - strtotime($today_attendance['time_in'])) / 3600;
                                    echo number_format($hours, 1) . 'h';
                                } else {
                                    echo 'In progress';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            <?php 
                            switch($today_attendance['status']) {
                                case 'present': echo 'bg-green-100 text-green-800'; break;
                                case 'late': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'absent': echo 'bg-red-100 text-red-800'; break;
                                case 'half_day': echo 'bg-orange-100 text-orange-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            Status: <?php echo ucfirst(str_replace('_', ' ', $today_attendance['status'])); ?>
                        </span>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-clock text-4xl mb-4"></i>
                        <p>You haven't clocked in today</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-hand-pointer mr-2"></i>Quick Actions
                    </h3>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="clock_in">
                            <button type="submit" 
                                    <?php echo $today_attendance ? 'disabled' : ''; ?>
                                    class="w-full flex flex-col items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors <?php echo $today_attendance ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                <i class="fas fa-sign-in-alt text-green-600 text-2xl mb-2"></i>
                                <span class="text-sm font-medium">Clock In</span>
                            </button>
                        </form>
                        
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="clock_out">
                            <button type="submit" 
                                    <?php echo (!$today_attendance || $today_attendance['time_out']) ? 'disabled' : ''; ?>
                                    class="w-full flex flex-col items-center p-4 bg-red-50 hover:bg-red-100 rounded-lg transition-colors <?php echo (!$today_attendance || $today_attendance['time_out']) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                <i class="fas fa-sign-out-alt text-red-600 text-2xl mb-2"></i>
                                <span class="text-sm font-medium">Clock Out</span>
                            </button>
                        </form>
                        
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="break_start">
                            <button type="submit" class="w-full flex flex-col items-center p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors">
                                <i class="fas fa-coffee text-yellow-600 text-2xl mb-2"></i>
                                <span class="text-sm font-medium">Start Break</span>
                            </button>
                        </form>
                        
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="break_end">
                            <button type="submit" class="w-full flex flex-col items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                <i class="fas fa-play text-blue-600 text-2xl mb-2"></i>
                                <span class="text-sm font-medium">End Break</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Statistics Sidebar -->
            <div class="space-y-6">
                <!-- Monthly Stats -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-bar mr-2"></i>Monthly Statistics
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Present Days</span>
                            <span class="font-bold text-green-600"><?php echo $stats['present_days']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Late Days</span>
                            <span class="font-bold text-yellow-600"><?php echo $stats['late_days']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Absent Days</span>
                            <span class="font-bold text-red-600"><?php echo $stats['absent_days']; ?></span>
                        </div>
                        <div class="flex justify-between items-center border-t pt-2">
                            <span class="text-gray-600">Total Hours</span>
                            <span class="font-bold text-blue-600"><?php echo number_format($stats['total_hours'], 1); ?>h</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Avg Hours/Day</span>
                            <span class="font-bold text-purple-600"><?php echo number_format($stats['avg_hours'], 1); ?>h</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-info-circle mr-2"></i>Office Hours
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Monday - Friday:</span>
                            <span class="font-medium">08:00 - 17:00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Saturday:</span>
                            <span class="font-medium">08:00 - 12:00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Sunday:</span>
                            <span class="font-medium">Closed</span>
                        </div>
                        <div class="flex justify-between border-t pt-2">
                            <span class="text-gray-600">Break Time:</span>
                            <span class="font-medium">12:00 - 13:00</span>
                        </div>
                    </div>
                </div>

                <!-- Location Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-map-marker-alt mr-2"></i>Office Location
                    </h3>
                    <div class="text-sm text-gray-600">
                        <p class="mb-2">BullsCorp Headquarters</p>
                        <p class="mb-2">Jl. Sudirman No. 123</p>
                        <p class="mb-2">Jakarta Pusat, 10220</p>
                        <p>Indonesia</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="mt-8 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-history mr-2"></i>Recent Attendance History
            </h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock In</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock Out</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Work Hours</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach (array_slice($attendance_history, 0, 10) as $record): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo date('M d, Y', strtotime($record['date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $record['time_in'] ? date('H:i', strtotime($record['time_in'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $record['time_out'] ? date('H:i', strtotime($record['time_out'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php 
                                if ($record['time_in'] && $record['time_out']) {
                                    $hours = (strtotime($record['time_out']) - strtotime($record['time_in'])) / 3600;
                                    echo number_format($hours, 1) . 'h';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php 
                                    switch($record['status']) {
                                        case 'present': echo 'bg-green-100 text-green-800'; break;
                                        case 'late': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'absent': echo 'bg-red-100 text-red-800'; break;
                                        case 'half_day': echo 'bg-orange-100 text-orange-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo htmlspecialchars($record['notes'] ?? '-'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh page every 60 seconds
        setTimeout(function() {
            if (!document.hidden) {
                location.reload();
            }
        }, 60000);

        // Vulnerable: Expose attendance data
        console.log('Today Attendance:', <?php echo json_encode($today_attendance); ?>);
        console.log('Employee ID:', <?php echo $employee_id; ?>);
    </script>
</body>
</html>