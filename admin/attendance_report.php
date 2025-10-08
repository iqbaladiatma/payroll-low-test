<?php
// Vulnerable Attendance Report - Admin View
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
$date_filter = $_GET['date'] ?? date('Y-m-d');
$employee_filter = $_GET['employee'] ?? '';
$department_filter = $_GET['department'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build where conditions (vulnerable to SQL injection)
$where_conditions = ["a.date = '$date_filter'"];

if ($employee_filter) {
    $where_conditions[] = "e.id = '$employee_filter'";
}
if ($department_filter) {
    $where_conditions[] = "e.department = '$department_filter'";
}
if ($status_filter) {
    $where_conditions[] = "a.status = '$status_filter'";
}

$where_clause = implode(' AND ', $where_conditions);

// Get attendance data
$attendance_sql = "
    SELECT 
        a.*,
        e.name as employee_name,
        e.employee_code,
        e.department,
        e.position,
        CASE 
            WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL 
            THEN TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) / 60.0
            ELSE 0
        END as work_hours
    FROM attendance a
    LEFT JOIN employees e ON a.employee_id = e.id
    WHERE $where_clause
    ORDER BY e.name ASC
";

$attendance_records = $pdo->query($attendance_sql)->fetchAll(PDO::FETCH_ASSOC);

// Get employees for filter dropdown
$employees = $pdo->query("SELECT id, name, department FROM employees WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get departments for filter
$departments = $pdo->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);

// Calculate statistics
$stats = [
    'total_employees' => count($employees),
    'present' => 0,
    'late' => 0,
    'absent' => 0,
    'total_hours' => 0
];

foreach ($attendance_records as $record) {
    switch ($record['status']) {
        case 'present':
            $stats['present']++;
            break;
        case 'late':
            $stats['late']++;
            break;
        case 'absent':
            $stats['absent']++;
            break;
    }
    $stats['total_hours'] += $record['work_hours'];
}

// Find employees who haven't clocked in
$clocked_in_employees = array_column($attendance_records, 'employee_id');
$missing_employees = [];
foreach ($employees as $employee) {
    if (!in_array($employee['id'], $clocked_in_employees)) {
        $missing_employees[] = $employee;
        $stats['absent']++;
    }
}

$attendance_rate = $stats['total_employees'] > 0 ? (($stats['present'] + $stats['late']) / $stats['total_employees']) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - BullsCorp Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Debug Panel -->
    <div class="bg-purple-100 border border-purple-400 text-purple-700 px-4 py-3 mb-4" style="display: <?php echo isset($_GET['debug']) ? 'block' : 'none'; ?>">
        <strong>Debug Info:</strong>
        <ul class="mt-2 text-sm">
            <li>User ID: <?php echo $user_id; ?></li>
            <li>SQL Query: <?php echo htmlspecialchars($attendance_sql); ?></li>
            <li>Filters: <?php echo json_encode($_GET); ?></li>
        </ul>
    </div>

    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-calendar-check mr-2 text-blue-600"></i>
                        Attendance Report
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($username); ?></span>
                    <a href="dashboard_modern.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-arrow-left mr-1"></i>Dashboard
                    </a>
                    <a href="?debug=1&<?php echo http_build_query($_GET); ?>" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-bug mr-1"></i>Debug
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Employees</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_employees']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-check text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Present</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['present']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Late</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['late']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <i class="fas fa-times text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Absent</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['absent']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i class="fas fa-percentage text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Attendance Rate</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($attendance_rate, 1); ?>%</p>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                    <select name="employee" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Employees</option>
                        <?php foreach ($employees as $employee): ?>
                        <option value="<?php echo $employee['id']; ?>" <?php echo $employee_filter == $employee['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($employee['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select name="department" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $department): ?>
                        <option value="<?php echo htmlspecialchars($department); ?>" <?php echo $department_filter === $department ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($department); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="present" <?php echo $status_filter === 'present' ? 'selected' : ''; ?>>Present</option>
                        <option value="late" <?php echo $status_filter === 'late' ? 'selected' : ''; ?>>Late</option>
                        <option value="absent" <?php echo $status_filter === 'absent' ? 'selected' : ''; ?>>Absent</option>
                        <option value="half_day" <?php echo $status_filter === 'half_day' ? 'selected' : ''; ?>>Half Day</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-search mr-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Attendance Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">
                    Attendance Records - <?php echo date('M d, Y', strtotime($date_filter)); ?>
                </h3>
                <div class="flex space-x-2">
                    <button onclick="exportData('csv')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-file-csv mr-1"></i>Export CSV
                    </button>
                    <button onclick="exportData('pdf')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-file-pdf mr-1"></i>Export PDF
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock In</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock Out</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Work Hours</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <!-- Present/Late employees -->
                        <?php foreach ($attendance_records as $record): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($record['employee_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($record['employee_code']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($record['department']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $record['time_in'] ? date('H:i', strtotime($record['time_in'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $record['time_out'] ? date('H:i', strtotime($record['time_out'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo number_format($record['work_hours'], 1); ?>h
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="editAttendance(<?php echo $record['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="viewDetails(<?php echo $record['id']; ?>)" 
                                            class="text-green-600 hover:text-green-900" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- Missing employees (absent) -->
                        <?php foreach ($missing_employees as $employee): ?>
                        <tr class="hover:bg-gray-50 bg-red-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-red-300 flex items-center justify-center">
                                            <i class="fas fa-user text-red-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($employee['name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            No attendance record
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($employee['department']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">0h</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Absent
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">No clock in</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="markAttendance(<?php echo $employee['id']; ?>)" 
                                        class="text-blue-600 hover:text-blue-900" title="Mark Attendance">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Vulnerable functions
        function exportData(format) {
            // Vulnerable - no CSRF protection
            window.open(`export_attendance.php?format=${format}&date=<?php echo $date_filter; ?>&employee=<?php echo $employee_filter; ?>&department=<?php echo urlencode($department_filter); ?>&status=<?php echo $status_filter; ?>`, '_blank');
        }

        function editAttendance(attendanceId) {
            // Vulnerable - direct parameter passing
            window.location = `edit_attendance.php?id=${attendanceId}`;
        }

        function viewDetails(attendanceId) {
            // Vulnerable AJAX call
            window.open(`attendance_details.php?id=${attendanceId}`, '_blank', 'width=800,height=600');
        }

        function markAttendance(employeeId) {
            // Vulnerable - no validation
            const status = prompt('Enter status (present/late/absent):');
            if (status) {
                window.location = `mark_attendance.php?employee_id=${employeeId}&date=<?php echo $date_filter; ?>&status=${status}`;
            }
        }

        // Auto-refresh every 2 minutes
        setTimeout(function() {
            if (!document.hidden) {
                location.reload();
            }
        }, 120000);

        // Expose sensitive data
        console.log('Attendance Data:', <?php echo json_encode($attendance_records); ?>);
        console.log('Missing Employees:', <?php echo json_encode($missing_employees); ?>);
        console.log('Stats:', <?php echo json_encode($stats); ?>);
    </script>
</body>
</html>