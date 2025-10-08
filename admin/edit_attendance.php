<?php
// Vulnerable Edit Attendance - Low Security for Penetration Testing
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
$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    $time_in = $_POST['time_in'] ?? null;
    $time_out = $_POST['time_out'] ?? null;
    $status = $_POST['status'] ?? 'present';
    $notes = $_POST['notes'] ?? '';
    
    // Vulnerable SQL injection
    $sql = "UPDATE attendance SET 
            time_in = " . ($time_in ? "'$time_in'" : "NULL") . ",
            time_out = " . ($time_out ? "'$time_out'" : "NULL") . ",
            status = '$status',
            notes = '$notes'
            WHERE id = $attendance_id";
    
    try {
        $pdo->exec($sql);
        $message = "Attendance updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating attendance: " . $e->getMessage();
    }
}

// Get attendance data
$sql = "SELECT a.*, e.name as employee_name, e.employee_code, e.department
        FROM attendance a
        LEFT JOIN employees e ON a.employee_id = e.id
        WHERE a.id = $attendance_id";

$attendance = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

if (!$attendance) {
    die("Attendance record not found");
}

// Include sidebar
include './includes/admin_sidebar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attendance - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
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
                            <i class="fas fa-edit mr-2 text-blue-600"></i>
                            Edit Attendance
                        </h1>
                    </div>
                    <a href="attendance_report.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Report
                    </a>
                </div>
            </div>
        </header>

        <div class="p-6">
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

            <div class="max-w-2xl mx-auto">
                <!-- Employee Info Card -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Employee Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Name:</span>
                            <span class="ml-2 font-medium"><?php echo htmlspecialchars($attendance['employee_name']); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Employee Code:</span>
                            <span class="ml-2 font-medium"><?php echo htmlspecialchars($attendance['employee_code']); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Date:</span>
                            <span class="ml-2 font-medium"><?php echo date('M d, Y', strtotime($attendance['date'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Edit Attendance Details</h3>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-sign-in-alt mr-1 text-blue-600"></i>
                                    Clock In Time
                                </label>
                                <input type="time" name="time_in" 
                                       value="<?php echo $attendance['time_in'] ? date('H:i', strtotime($attendance['time_in'])) : ''; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-sign-out-alt mr-1 text-red-600"></i>
                                    Clock Out Time
                                </label>
                                <input type="time" name="time_out" 
                                       value="<?php echo $attendance['time_out'] ? date('H:i', strtotime($attendance['time_out'])) : ''; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-flag mr-1 text-purple-600"></i>
                                Status
                            </label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="present" <?php echo $attendance['status'] === 'present' ? 'selected' : ''; ?>>Present</option>
                                <option value="late" <?php echo $attendance['status'] === 'late' ? 'selected' : ''; ?>>Late</option>
                                <option value="absent" <?php echo $attendance['status'] === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                <option value="half_day" <?php echo $attendance['status'] === 'half_day' ? 'selected' : ''; ?>>Half Day</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-sticky-note mr-1 text-yellow-600"></i>
                                Notes
                            </label>
                            <textarea name="notes" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Add any notes about this attendance record..."><?php echo htmlspecialchars($attendance['notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="flex space-x-4 pt-4">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                                <i class="fas fa-save mr-2"></i>Update Attendance
                            </button>
                            <a href="attendance_report.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Vulnerable: Expose attendance data
        console.log('Editing Attendance:', <?php echo json_encode($attendance); ?>);
    </script>
</body>
</html>