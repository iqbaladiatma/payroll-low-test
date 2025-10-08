<?php
// Complete Profile - User Profile Completion for New Users
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

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

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Check if user already has employee record
$check_employee = $pdo->prepare("SELECT e.*, u.employee_id FROM users u LEFT JOIN employees e ON u.employee_id = e.id WHERE u.id = ?");
$check_employee->execute([$user_id]);
$existing_data = $check_employee->fetch(PDO::FETCH_ASSOC);

// If user already has complete profile, redirect to dashboard
if ($existing_data && $existing_data['employee_id'] && $existing_data['name']) {
    header('Location: dashboard.php');
    exit;
}

// Handle form submission
if ($_POST) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    $salary = $_POST['salary'] ?? 0;
    $hire_date = $_POST['hire_date'] ?? date('Y-m-d');
    
    // Validation
    if (empty($name) || empty($email) || empty($department) || empty($position)) {
        $error = "Please fill in all required fields";
    } else {
        try {
            // Insert into employees table
            $insert_employee = $pdo->prepare("INSERT INTO employees (name, email, phone, address, department, position, salary, hire_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $insert_employee->execute([$name, $email, $phone, $address, $department, $position, $salary, $hire_date]);
            
            $employee_id = $pdo->lastInsertId();
            
            // Update users table with employee_id
            $update_user = $pdo->prepare("UPDATE users SET employee_id = ? WHERE id = ?");
            $update_user->execute([$employee_id, $user_id]);
            
            // Update session
            $_SESSION['employee_id'] = $employee_id;
            $_SESSION['user_name'] = $name;
            
            // Force session save
            session_write_close();
            session_start();
            
            $message = "Profile completed successfully! Redirecting to dashboard...";
            
            // Immediate redirect instead of JavaScript delay
            header("Refresh: 2; url=dashboard.php");
            
            // Also add JavaScript as backup
            echo "<script>setTimeout(function(){ window.location.href = 'dashboard.php'; }, 2000);</script>";
            
        } catch (Exception $e) {
            $error = "Error saving profile: " . $e->getMessage();
        }
    }
}

// Get user info
$user_info = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_info->execute([$user_id]);
$user = $user_info->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Profile - BullsCorp Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="bg-white p-4 rounded-full w-20 h-20 mx-auto mb-4 shadow-lg">
                    <i class="fas fa-user-edit text-blue-600 text-3xl mt-2"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Complete Your Profile</h1>
                <p class="text-gray-600">Please fill in your employee information to access all features</p>
            </div>

            <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- Profile Form -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <form method="POST" class="space-y-6">
                    <!-- Personal Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                            <i class="fas fa-user mr-2"></i>Personal Information
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input type="text" name="name" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter your full name">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter your email">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" name="phone"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter your phone number">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hire Date</label>
                                <input type="date" name="hire_date" value="<?php echo date('Y-m-d'); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                            <textarea name="address" rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="Enter your address"></textarea>
                        </div>
                    </div>

                    <!-- Employment Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                            <i class="fas fa-briefcase mr-2"></i>Employment Information
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                                <select name="department" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Select Department</option>
                                    <option value="IT">Information Technology</option>
                                    <option value="HR">Human Resources</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Operations">Operations</option>
                                    <option value="Customer Service">Customer Service</option>
                                    <option value="General">General</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
                                <input type="text" name="position" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="e.g. Software Developer, Manager, etc.">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Salary (IDR)</label>
                                <input type="number" name="salary" min="0" step="1000" value="5000000"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter monthly salary">
                                <p class="text-xs text-gray-500 mt-1">This will be used for payroll calculations</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-4 pt-6 border-t">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                            <i class="fas fa-save mr-2"></i>Complete Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Info Card -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div>
                        <h4 class="font-semibold text-blue-800">Why do we need this information?</h4>
                        <p class="text-blue-700 text-sm mt-1">
                            This information is required to set up your employee profile, calculate payroll, 
                            generate payslips, and process your submissions (leave requests, overtime, etc.).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>