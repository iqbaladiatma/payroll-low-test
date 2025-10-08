<?php
// Vulnerable View Employee - Low Security for Penetration Testing
// WARNING: This code contains intentional vulnerabilities!

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
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

$employee_id = $_GET['id'] ?? 0;

// Vulnerable SQL injection
$sql = "SELECT * FROM employees WHERE id = $employee_id";
$employee = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    echo "<div class='text-red-600'>Employee not found</div>";
    exit;
}

// Get employee statistics
$stats = [];

// Recent attendance
$stats['attendance'] = $pdo->query("
    SELECT COUNT(*) as total,
           COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
           COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
           COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent
    FROM attendance 
    WHERE employee_id = $employee_id 
    AND date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
")->fetch(PDO::FETCH_ASSOC);

// Recent submissions
$stats['submissions'] = $pdo->query("
    SELECT COUNT(*) as total,
           COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
           COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
           COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
    FROM submissions 
    WHERE employee_id = $employee_id
")->fetch(PDO::FETCH_ASSOC);

// Salary info
$stats['salary'] = $pdo->query("
    SELECT COUNT(*) as total_payments,
           SUM(total_amount) as total_earned,
           AVG(total_amount) as avg_salary
    FROM salaries 
    WHERE employee_id = $employee_id
")->fetch(PDO::FETCH_ASSOC);

// Recent activities
$recent_activities = $pdo->query("
    SELECT 'attendance' as type, date as activity_date, status as activity_status, 'Attendance' as activity_type
    FROM attendance 
    WHERE employee_id = $employee_id
    UNION ALL
    SELECT 'submission' as type, created_at as activity_date, status as activity_status, submission_type as activity_type
    FROM submissions 
    WHERE employee_id = $employee_id
    ORDER BY activity_date DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Details - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6">
                <div class="flex justify-between items-start">
                    <div class="flex items-center space-x-4">
                        <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($employee['name']); ?></h1>
                            <p class="text-xl opacity-90"><?php echo htmlspecialchars($employee['position']); ?></p>
                            <p class="opacity-75"><?php echo htmlspecialchars($employee['department']); ?> Department</p>
                        </div>
                    </div>
                    <button onclick="window.close()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <!-- Employee Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="text-center">
                        <div class="bg-blue-100 rounded-lg p-4 mb-2">
                            <i class="fas fa-id-card text-blue-600 text-2xl"></i>
                        </div>
                        <p class="text-sm text-gray-600">Employee ID</p>
                        <p class="font-bold text-lg"><?php echo htmlspecialchars($employee['employee_code']); ?></p>
                    </div>

                    <div class="text-center">
                        <div class="bg-green-100 rounded-lg p-4 mb-2">
                            <i class="fas fa-money-bill-wave text-green-600 text-2xl"></i>
                        </div>
                        <p class="text-sm text-gray-600">Base Salary</p>
                        <p class="font-bold text-lg">Rp <?php echo number_format($employee['salary'], 0, ',', '.'); ?></p>
                    </div>

                    <div class="text-center">
                        <div class="bg-purple-100 rounded-lg p-4 mb-2">
                            <i class="fas fa-calendar text-purple-600 text-2xl"></i>
                        </div>
                        <p class="text-sm text-gray-600">Hire Date</p>
                        <p class="font-bold text-lg"><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></p>
                    </div>

                    <div class="text-center">
                        <div class="bg-<?php echo $employee['status'] === 'active' ? 'green' : 'red'; ?>-100 rounded-lg p-4 mb-2">
                            <i class="fas fa-circle text-<?php echo $employee['status'] === 'active' ? 'green' : 'red'; ?>-600 text-2xl"></i>
                        </div>
                        <p class="text-sm text-gray-600">Status</p>
                        <p class="font-bold text-lg capitalize"><?php echo $employee['status']; ?></p>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-address-card mr-2"></i>Contact Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Email Address</p>
                            <p class="font-medium"><?php echo htmlspecialchars($employee['email'] ?? 'Not provided'); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Phone Number</p>
                            <p class="font-medium"><?php echo htmlspecialchars($employee['phone'] ?? 'Not provided'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Attendance Stats -->
                    <div class="bg-white border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-3">
                            <i class="fas fa-calendar-check mr-2 text-blue-600"></i>
                            Attendance (30 days)
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Present:</span>
                                <span class="font-medium text-green-600"><?php echo $stats['attendance']['present']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Late:</span>
                                <span class="font-medium text-yellow-600"><?php echo $stats['attendance']['late']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Absent:</span>
                                <span class="font-medium text-red-600"><?php echo $stats['attendance']['absent']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Submissions Stats -->
                    <div class="bg-white border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-3">
                            <i class="fas fa-clipboard-list mr-2 text-purple-600"></i>
                            Submissions
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total:</span>
                                <span class="font-medium"><?php echo $stats['submissions']['total']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Pending:</span>
                                <span class="font-medium text-yellow-600"><?php echo $stats['submissions']['pending']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Approved:</span>
                                <span class="font-medium text-green-600"><?php echo $stats['submissions']['approved']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Salary Stats -->
                    <div class="bg-white border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-3">
                            <i class="fas fa-money-bill-wave mr-2 text-green-600"></i>
                            Salary Info
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payments:</span>
                                <span class="font-medium"><?php echo $stats['salary']['total_payments']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Earned:</span>
                                <span class="font-medium text-green-600">Rp <?php echo number_format($stats['salary']['total_earned'] ?? 0, 0, ',', '.'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Avg Salary:</span>
                                <span class="font-medium">Rp <?php echo number_format($stats['salary']['avg_salary'] ?? 0, 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-history mr-2"></i>Recent Activities
                    </h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="flex items-center space-x-3 p-3 bg-white rounded border">
                            <div class="flex-shrink-0">
                                <i class="fas fa-<?php echo $activity['type'] === 'attendance' ? 'calendar-check' : 'clipboard-list'; ?> text-<?php echo $activity['type'] === 'attendance' ? 'blue' : 'purple'; ?>-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">
                                    <?php echo ucfirst($activity['activity_type']); ?>
                                    <?php if ($activity['type'] === 'submission'): ?>
                                    - <?php echo ucfirst($activity['activity_status']); ?>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?php echo date('M d, Y H:i', strtotime($activity['activity_date'])); ?>
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    <?php 
                                    switch($activity['activity_status']) {
                                        case 'present': echo 'bg-green-100 text-green-800'; break;
                                        case 'late': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'absent': echo 'bg-red-100 text-red-800'; break;
                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'approved': echo 'bg-green-100 text-green-800'; break;
                                        case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($activity['activity_status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex space-x-3">
                    <button onclick="editEmployee()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-edit mr-2"></i>Edit Employee
                    </button>
                    <button onclick="viewPayroll()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-money-check-alt mr-2"></i>View Payroll
                    </button>
                    <button onclick="printProfile()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-print mr-2"></i>Print Profile
                    </button>
                    <button onclick="window.close()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-times mr-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editEmployee() {
            window.opener.location.href = 'edit_employee.php?id=<?php echo $employee_id; ?>';
            window.close();
        }

        function viewPayroll() {
            window.open('manage_payroll.php?employee=<?php echo $employee_id; ?>', '_blank');
        }

        function printProfile() {
            window.print();
        }

        // Vulnerable: Expose employee data
        console.log('Employee Details:', <?php echo json_encode($employee); ?>);
        console.log('Employee Stats:', <?php echo json_encode($stats); ?>);
    </script>
</body>
</html>