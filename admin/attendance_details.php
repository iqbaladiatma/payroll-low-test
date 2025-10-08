<?php
// Vulnerable Attendance Details - Low Security for Penetration Testing
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

$attendance_id = $_GET['id'] ?? 0;

// Vulnerable SQL injection
$sql = "SELECT a.*, e.name as employee_name, e.employee_code, e.department, e.position
        FROM attendance a
        LEFT JOIN employees e ON a.employee_id = e.id
        WHERE a.id = $attendance_id";

$attendance = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

if (!$attendance) {
    echo "<div class='text-red-600'>Attendance record not found</div>";
    exit;
}

// Calculate work hours
$work_hours = 0;
if ($attendance['time_in'] && $attendance['time_out']) {
    $work_hours = (strtotime($attendance['time_out']) - strtotime($attendance['time_in'])) / 3600;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Details - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-calendar-check mr-2 text-blue-600"></i>
                    Attendance Details
                </h2>
                <button onclick="window.close()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Employee Info -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">Employee Information</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Name:</span>
                        <span class="ml-2 font-medium"><?php echo htmlspecialchars($attendance['employee_name']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Employee Code:</span>
                        <span class="ml-2 font-medium"><?php echo htmlspecialchars($attendance['employee_code']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Department:</span>
                        <span class="ml-2 font-medium"><?php echo htmlspecialchars($attendance['department']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Position:</span>
                        <span class="ml-2 font-medium"><?php echo htmlspecialchars($attendance['position']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Attendance Details -->
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-calendar text-blue-600 text-xl"></i>
                            <div>
                                <p class="text-sm text-gray-600">Date</p>
                                <p class="font-semibold text-gray-900">
                                    <?php echo date('l, M d, Y', strtotime($attendance['date'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-clock text-green-600 text-xl"></i>
                            <div>
                                <p class="text-sm text-gray-600">Status</p>
                                <p class="font-semibold text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php 
                                        switch($attendance['status']) {
                                            case 'present': echo 'bg-green-100 text-green-800'; break;
                                            case 'late': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'absent': echo 'bg-red-100 text-red-800'; break;
                                            case 'half_day': echo 'bg-orange-100 text-orange-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $attendance['status'])); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <i class="fas fa-sign-in-alt text-blue-600 text-2xl mb-2"></i>
                        <p class="text-sm text-gray-600">Clock In</p>
                        <p class="font-bold text-lg text-gray-900">
                            <?php echo $attendance['time_in'] ? date('H:i', strtotime($attendance['time_in'])) : '-'; ?>
                        </p>
                    </div>

                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <i class="fas fa-sign-out-alt text-red-600 text-2xl mb-2"></i>
                        <p class="text-sm text-gray-600">Clock Out</p>
                        <p class="font-bold text-lg text-gray-900">
                            <?php echo $attendance['time_out'] ? date('H:i', strtotime($attendance['time_out'])) : '-'; ?>
                        </p>
                    </div>

                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <i class="fas fa-hourglass-half text-purple-600 text-2xl mb-2"></i>
                        <p class="text-sm text-gray-600">Work Hours</p>
                        <p class="font-bold text-lg text-gray-900">
                            <?php echo number_format($work_hours, 1); ?>h
                        </p>
                    </div>
                </div>

                <?php if ($attendance['notes']): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h4 class="font-semibold text-yellow-800 mb-2">
                        <i class="fas fa-sticky-note mr-2"></i>Notes
                    </h4>
                    <p class="text-yellow-700"><?php echo htmlspecialchars($attendance['notes']); ?></p>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="flex space-x-3 pt-4 border-t">
                    <button onclick="editAttendance()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </button>
                    <button onclick="printDetails()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    <button onclick="window.close()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-times mr-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editAttendance() {
            window.opener.location.href = 'edit_attendance.php?id=<?php echo $attendance_id; ?>';
            window.close();
        }

        function printDetails() {
            window.print();
        }

        // Vulnerable: Expose attendance data
        console.log('Attendance Details:', <?php echo json_encode($attendance); ?>);
    </script>
</body>
</html>