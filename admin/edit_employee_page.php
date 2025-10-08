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
$employee_id = $_GET['id'] ?? 0;
$employeeController = new EmployeeController();

// Get employee data (vulnerable query)
$employee_query = "SELECT e.*, u.username FROM employees e LEFT JOIN users u ON e.user_id = u.id WHERE e.id = $employee_id";
$employee_result = $db->query($employee_query);
$employee = $employee_result ? $employee_result->fetch(PDO::FETCH_ASSOC) : null;

if (!$employee) {
    header('Location: dashboard.php?error=Employee not found');
    exit();
}

if ($_POST) {
    $name = sanitizeInput($_POST['name']);
    $position = sanitizeInput($_POST['position']);
    $salary = sanitizeInput($_POST['salary']);
    $department = sanitizeInput($_POST['department']);
    $status = sanitizeInput($_POST['status'] ?? 'active');
    
    $data = [
        'name' => $name,
        'position' => $position,
        'salary' => $salary,
        'department' => $department,
        'status' => $status
    ];
    
    if ($employeeController->updateEmployee($employee_id, $data)) {
        $success = 'Employee updated successfully!';
        logActivity('Employee updated', 'employees', $employee_id, null, "name: $name, position: $position");
        // Refresh employee data
        $employee_result = $db->query($employee_query);
        $employee = $employee_result->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = 'Failed to update employee';
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
    <title>Edit Employee - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-yellow-50 to-orange-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b-4 border-yellow-500">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-yellow-600 hover:text-yellow-800">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div class="flex items-center">
                        <i class="fas fa-user-edit text-yellow-600 text-2xl mr-2"></i>
                        <h1 class="text-2xl font-bold text-gray-800">Edit Employee</h1>
                    </div>
                </div>
                <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto px-4 py-8">
        <!-- Current Employee Info -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-yellow-600 to-orange-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white">Current Employee Information</h2>
            </div>
            <div class="p-6">
                <div class="flex items-center">
                    <div class="bg-yellow-100 p-3 rounded-full mr-4">
                        <i class="fas fa-user text-yellow-600 text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($employee['name']); ?></h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($employee['position']); ?> - <?php echo htmlspecialchars($employee['department']); ?></p>
                        <p class="text-green-600 font-semibold">Rp <?php echo number_format($employee['salary'], 0, ',', '.'); ?>/month</p>
                        <?php if ($employee['username']): ?>
                            <p class="text-blue-600 text-sm">
                                <i class="fas fa-link mr-1"></i>Linked to: <?php echo htmlspecialchars($employee['username']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-yellow-600 to-orange-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white">Update Employee Information</h2>
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
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2 text-gray-400"></i>Full Name
                            </label>
                            <input type="text" name="name" required 
                                   value="<?php echo htmlspecialchars($employee['name']); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-briefcase mr-2 text-gray-400"></i>Position
                            </label>
                            <input type="text" name="position" required 
                                   value="<?php echo htmlspecialchars($employee['position']); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-building mr-2 text-gray-400"></i>Department
                            </label>
                            <select name="department" required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                                <option value="">Select Department</option>
                                <option value="IT" <?php echo $employee['department'] == 'IT' ? 'selected' : ''; ?>>Information Technology</option>
                                <option value="Human Resources" <?php echo $employee['department'] == 'Human Resources' ? 'selected' : ''; ?>>Human Resources</option>
                                <option value="Finance" <?php echo $employee['department'] == 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                <option value="Marketing" <?php echo $employee['department'] == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                <option value="Sales" <?php echo $employee['department'] == 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                <option value="Operations" <?php echo $employee['department'] == 'Operations' ? 'selected' : ''; ?>>Operations</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-money-bill-wave mr-2 text-gray-400"></i>Monthly Salary (Rp)
                            </label>
                            <input type="number" name="salary" required min="0" step="100000"
                                   value="<?php echo $employee['salary']; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-link mr-2 text-gray-400"></i>Link to User Account
                        </label>
                        <select name="user_id" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                            <option value="">No User Account</option>
                            <?php while ($user = $users_result->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $employee['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-4 pt-6 border-t">
                        <a href="dashboard.php" 
                           class="px-6 py-3 text-gray-600 hover:text-gray-800 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="bg-yellow-600 hover:bg-yellow-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                            <i class="fas fa-save mr-2"></i>Update Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="mt-6 bg-white rounded-xl shadow-lg overflow-hidden border-2 border-red-200">
            <div class="bg-red-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone
                </h2>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">Permanently delete this employee. This action cannot be undone.</p>
                <a href="delete_employee.php?id=<?php echo $employee['id']; ?>" 
                   onclick="return confirm('Are you sure you want to delete this employee? This action cannot be undone!')"
                   class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    <i class="fas fa-trash mr-2"></i>Delete Employee
                </a>
            </div>
        </div>
    </div>
</body>
</html>