<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../src/controllers/EmployeeController.php';

checkAdmin();

$error = '';
$success = '';
$employeeController = new EmployeeController();

if ($_POST) {
    $name = sanitizeInput($_POST['name']);
    $position = sanitizeInput($_POST['position']);
    $salary = sanitizeInput($_POST['salary']);
    $department = sanitizeInput($_POST['department']);
    $user_id = $_POST['user_id'] ? $_POST['user_id'] : null;
    $employee_code = 'EMP' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $hire_date = date('Y-m-d');
    
    $data = [
        'employee_code' => $employee_code,
        'name' => $name,
        'position' => $position,
        'salary' => $salary,
        'department' => $department,
        'hire_date' => $hire_date
    ];
    
    if ($employeeController->addEmployee($data)) {
        $success = 'Employee added successfully!';
        logActivity('Employee added', 'employees', null, null, "name: $name, position: $position");
    } else {
        $error = 'Failed to add employee';
    }
}

// Get users for linking
$users_query = "SELECT * FROM users WHERE role = 'user'";
$users_result = $db->query($users_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b-4 border-blue-500">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div class="flex items-center">
                        <i class="fas fa-user-plus text-blue-600 text-2xl mr-2"></i>
                        <h1 class="text-2xl font-bold text-gray-800">Add New Employee</h1>
                    </div>
                </div>
                <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white">Employee Information</h2>
            </div>

            <div class="p-6">
                <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $success; ?>
                    <div class="mt-2">
                        <a href="dashboard.php" class="text-green-800 underline">Back to Dashboard</a>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2 text-gray-400"></i>Full Name
                            </label>
                            <input type="text" name="name" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Enter employee full name">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-briefcase mr-2 text-gray-400"></i>Position
                            </label>
                            <input type="text" name="position" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="e.g. Software Engineer">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-building mr-2 text-gray-400"></i>Department
                            </label>
                            <select name="department" required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Department</option>
                                <option value="IT">Information Technology</option>
                                <option value="Human Resources">Human Resources</option>
                                <option value="Finance">Finance</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Sales">Sales</option>
                                <option value="Operations">Operations</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-money-bill-wave mr-2 text-gray-400"></i>Monthly Salary (Rp)
                            </label>
                            <input type="number" name="salary" required min="0" step="100000"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="e.g. 8000000">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-gray-400"></i>Link to User Account (Optional)
                        </label>
                        <select name="user_id" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">No User Account</option>
                            <?php while ($user = $users_result->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">Link this employee to a user account to allow them to view their payroll</p>
                    </div>

                    <div class="flex justify-end space-x-4 pt-6 border-t">
                        <a href="dashboard.php" 
                           class="px-6 py-3 text-gray-600 hover:text-gray-800 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>Add Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview Card -->
        <div class="mt-6 bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-eye mr-2 text-blue-600"></i>Preview
            </h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-600">Employee information will be displayed here after adding to the system.</p>
            </div>
        </div>
    </div>
</body>
</html>