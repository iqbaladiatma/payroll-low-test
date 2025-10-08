<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkAdmin();

$error = '';
$success = '';

if ($_POST) {
    $name = sanitizeInput($_POST['name']);
    $position = sanitizeInput($_POST['position']);
    $salary = sanitizeInput($_POST['salary']);
    $department = sanitizeInput($_POST['department']);
    $user_id = $_POST['user_id'] ? $_POST['user_id'] : 'NULL';
    $employee_code = sanitizeInput($_POST['employee_code']);
    
    // Vulnerable SQL injection
    $query = "INSERT INTO employees (user_id, employee_code, name, position, salary, department, hire_date) 
              VALUES ($user_id, '$employee_code', '$name', '$position', '$salary', '$department', CURDATE())";
    
    if ($db->exec($query)) {
        $success = 'Employee added successfully!';
        logActivity('Employee added', 'employees', null, null, "name: $name, position: $position");
    } else {
        $error = 'Failed to add employee';
    }
}

// Get users for linking
$users_query = "SELECT * FROM users WHERE role = 'user'";
$users_result = $db->query($users_query);

// Get departments
$dept_query = "SELECT * FROM departments ORDER BY name";
$dept_result = $db->query($dept_query);

$page_title = 'Add Employee - BullsCorp';
$navbar_title = 'Add New Employee';
$show_back_button = true;
$back_url = 'dashboard.php';
$body_class = 'bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen';
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
            <h2 class="text-xl font-bold text-white">Employee Information</h2>
        </div>

        <div class="p-6">
            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo displayData($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo displayData($success); ?>
                <div class="mt-2">
                    <a href="dashboard.php" class="text-green-800 underline">Back to Dashboard</a>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-id-card mr-2 text-gray-400"></i>Employee Code
                        </label>
                        <input type="text" name="employee_code" required 
                               value="EMP<?php echo str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g. EMP001">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-gray-400"></i>Full Name
                        </label>
                        <input type="text" name="name" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter employee full name">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-briefcase mr-2 text-gray-400"></i>Position
                        </label>
                        <input type="text" name="position" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g. Software Engineer">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-building mr-2 text-gray-400"></i>Department
                        </label>
                        <select name="department" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Department</option>
                            <?php while ($dept = $dept_result->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo displayData($dept['name']); ?>">
                                    <?php echo displayData($dept['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-money-bill-wave mr-2 text-gray-400"></i>Monthly Salary (Rp)
                        </label>
                        <input type="number" name="salary" required min="0" step="100000"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g. 8000000">
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
                                    <?php echo displayData($user['username']); ?> (<?php echo displayData($user['email']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">Link this employee to a user account to allow them to view their payroll</p>
                    </div>
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
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>