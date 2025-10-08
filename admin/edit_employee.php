<?php
// Vulnerable Edit Employee - Low Security for Penetration Testing
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
$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    $salary = $_POST['salary'] ?? 0;
    $status = $_POST['status'] ?? 'active';
    
    // Vulnerable SQL injection
    $sql = "UPDATE employees SET 
            name = '$name',
            email = '$email',
            phone = '$phone',
            department = '$department',
            position = '$position',
            salary = $salary,
            status = '$status'
            WHERE id = $employee_id";
    
    try {
        $pdo->exec($sql);
        $message = "Employee updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating employee: " . $e->getMessage();
    }
}

// Get employee data
$sql = "SELECT * FROM employees WHERE id = $employee_id";
$employee = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die("Employee not found");
}

// Get departments for dropdown
$departments = $pdo->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);

// Include sidebar
include './includes/admin_sidebar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - BullsCorp</title>
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
                            <i class="fas fa-user-edit mr-2 text-blue-600"></i>
                            Edit Employee
                        </h1>
                    </div>
                    <a href="manage_employees.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Employees
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
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-white text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($employee['name']); ?></h2>
                            <p class="text-gray-600"><?php echo htmlspecialchars($employee['employee_code']); ?></p>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user mr-1 text-blue-600"></i>
                                    Full Name *
                                </label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($employee['name']); ?>" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-envelope mr-1 text-green-600"></i>
                                    Email Address
                                </label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-phone mr-1 text-purple-600"></i>
                                    Phone Number
                                </label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-building mr-1 text-orange-600"></i>
                                    Department
                                </label>
                                <select name="department" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $employee['department'] === $dept ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <option value="IT" <?php echo $employee['department'] === 'IT' ? 'selected' : ''; ?>>IT</option>
                                    <option value="HR" <?php echo $employee['department'] === 'HR' ? 'selected' : ''; ?>>HR</option>
                                    <option value="Finance" <?php echo $employee['department'] === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                    <option value="Marketing" <?php echo $employee['department'] === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                    <option value="Operations" <?php echo $employee['department'] === 'Operations' ? 'selected' : ''; ?>>Operations</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-briefcase mr-1 text-indigo-600"></i>
                                    Position
                                </label>
                                <input type="text" name="position" value="<?php echo htmlspecialchars($employee['position']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-money-bill-wave mr-1 text-green-600"></i>
                                    Base Salary (IDR)
                                </label>
                                <input type="number" name="salary" value="<?php echo $employee['salary']; ?>" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-toggle-on mr-1 text-red-600"></i>
                                Employment Status
                            </label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="active" <?php echo $employee['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $employee['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="terminated" <?php echo $employee['status'] === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                            </select>
                        </div>

                        <!-- Additional Info -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-800 mb-3">Additional Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Employee Code:</span>
                                    <span class="ml-2 font-medium"><?php echo htmlspecialchars($employee['employee_code']); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Hire Date:</span>
                                    <span class="ml-2 font-medium"><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Created:</span>
                                    <span class="ml-2 font-medium"><?php echo date('M d, Y H:i', strtotime($employee['created_at'])); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Last Updated:</span>
                                    <span class="ml-2 font-medium"><?php echo date('M d, Y H:i', strtotime($employee['updated_at'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex space-x-4 pt-4">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                                <i class="fas fa-save mr-2"></i>Update Employee
                            </button>
                            <a href="manage_employees.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                            <button type="button" onclick="viewEmployee()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">
                                <i class="fas fa-eye mr-2"></i>View Details
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewEmployee() {
            window.open('view_employee.php?id=<?php echo $employee_id; ?>', '_blank', 'width=1000,height=700');
        }

        // Vulnerable: Expose employee data
        console.log('Editing Employee:', <?php echo json_encode($employee); ?>);
    </script>
</body>
</html>