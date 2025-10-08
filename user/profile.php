<?php
// User Profile Management - Edit Employee Profile
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

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

// Get user and employee data
$user_query = "SELECT u.*, e.* FROM users u LEFT JOIN employees e ON u.employee_id = e.id WHERE u.id = $user_id";
$user_data = $pdo->query($user_query)->fetch(PDO::FETCH_ASSOC);

if (!$user_data || !$user_data['employee_id']) {
    header('Location: complete_profile.php');
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
    
    if (empty($name) || empty($email) || empty($department) || empty($position)) {
        $error = "Please fill in all required fields";
    } else {
        try {
            // Update employee data
            $update_employee = $pdo->prepare("UPDATE employees SET name = ?, email = ?, phone = ?, address = ?, department = ?, position = ?, salary = ?, updated_at = NOW() WHERE id = ?");
            $update_employee->execute([$name, $email, $phone, $address, $department, $position, $salary, $user_data['employee_id']]);
            
            // Update user email if changed
            if ($email !== $user_data['email']) {
                $update_user = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $update_user->execute([$email, $user_id]);
            }
            
            $message = "Profile updated successfully!";
            
            // Refresh data
            $user_data = $pdo->query($user_query)->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}
    } else {
        // Insert new profile
        $insert_query = "INSERT INTO user_profiles 
            (user_id, full_name, phone, address, birth_date, gender, id_number, 
             emergency_contact, emergency_phone, bank_account, bank_name, is_complete) 
            VALUES 
            ($user_id, '$full_name', '$phone', '$address', '$birth_date', '$gender', 
             '$id_number', '$emergency_contact', '$emergency_phone', '$bank_account', '$bank_name', TRUE)";
        $db->exec($insert_query);
    }
    
    $_SESSION['success'] = 'Biodata berhasil disimpan!';
    header('Location: dashboard.php');
    exit();
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-user-edit"></i> Lengkapi Biodata Anda</h4>
                    <?php if (!$is_complete): ?>
                        <div class="alert alert-warning mt-2">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Anda harus melengkapi biodata untuk dapat melihat informasi gaji.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Nama Lengkap *</label>
                                    <input type="text" name="full_name" class="form-control" 
                                           value="<?= $profile['full_name'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>No. Telepon *</label>
                                    <input type="text" name="phone" class="form-control" 
                                           value="<?= $profile['phone'] ?? '' ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Alamat *</label>
                            <textarea name="address" class="form-control" rows="3" required><?= $profile['address'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Tanggal Lahir *</label>
                                    <input type="date" name="birth_date" class="form-control" 
                                           value="<?= $profile['birth_date'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Jenis Kelamin *</label>
                                    <select name="gender" class="form-control" required>
                                        <option value="male" <?= ($profile['gender'] ?? '') == 'male' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="female" <?= ($profile['gender'] ?? '') == 'female' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>No. KTP/ID *</label>
                            <input type="text" name="id_number" class="form-control" 
                                   value="<?= $profile['id_number'] ?? '' ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Kontak Darurat *</label>
                                    <input type="text" name="emergency_contact" class="form-control" 
                                           value="<?= $profile['emergency_contact'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>No. Telepon Darurat *</label>
                                    <input type="text" name="emergency_phone" class="form-control" 
                                           value="<?= $profile['emergency_phone'] ?? '' ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>No. Rekening Bank</label>
                                    <input type="text" name="bank_account" class="form-control" 
                                           value="<?= $profile['bank_account'] ?? '' ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Nama Bank</label>
                                    <input type="text" name="bank_name" class="form-control" 
                                           value="<?= $profile['bank_name'] ?? '' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Biodata
                            </button>
                            <?php if ($is_complete): ?>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - BullsCorp Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-user-circle mr-2"></i>My Profile
                </h1>
                <div class="flex space-x-2">
                    <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                    </a>
                </div>
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Profile Summary Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-center">
                            <div class="bg-blue-100 p-4 rounded-full w-20 h-20 mx-auto mb-4">
                                <i class="fas fa-user text-blue-600 text-3xl mt-2"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($user_data['name']); ?></h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($user_data['position']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user_data['department']); ?></p>
                        </div>
                        
                        <div class="mt-6 space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Employee ID:</span>
                                <span class="font-medium">#<?php echo $user_data['employee_id']; ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Username:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($user_data['username']); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Hire Date:</span>
                                <span class="font-medium"><?php echo date('M d, Y', strtotime($user_data['hire_date'])); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Monthly Salary:</span>
                                <span class="font-medium">Rp <?php echo number_format($user_data['salary'], 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Edit Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-6">
                            <i class="fas fa-edit mr-2"></i>Edit Profile Information
                        </h2>
                        
                        <form method="POST" class="space-y-6">
                            <!-- Personal Information -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                                    <i class="fas fa-user mr-2"></i>Personal Information
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                        <input type="text" name="name" required value="<?php echo htmlspecialchars($user_data['name']); ?>"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                        <input type="email" name="email" required value="<?php echo htmlspecialchars($user_data['email']); ?>"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_data['phone']); ?>"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                    <textarea name="address" rows="3"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($user_data['address']); ?></textarea>
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
                                            <option value="IT" <?php echo $user_data['department'] === 'IT' ? 'selected' : ''; ?>>Information Technology</option>
                                            <option value="HR" <?php echo $user_data['department'] === 'HR' ? 'selected' : ''; ?>>Human Resources</option>
                                            <option value="Finance" <?php echo $user_data['department'] === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                            <option value="Marketing" <?php echo $user_data['department'] === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                            <option value="Sales" <?php echo $user_data['department'] === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                            <option value="Operations" <?php echo $user_data['department'] === 'Operations' ? 'selected' : ''; ?>>Operations</option>
                                            <option value="Customer Service" <?php echo $user_data['department'] === 'Customer Service' ? 'selected' : ''; ?>>Customer Service</option>
                                            <option value="General" <?php echo $user_data['department'] === 'General' ? 'selected' : ''; ?>>General</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
                                        <input type="text" name="position" required value="<?php echo htmlspecialchars($user_data['position']); ?>"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Salary (IDR)</label>
                                        <input type="number" name="salary" min="0" step="1000" value="<?php echo $user_data['salary']; ?>"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <p class="text-xs text-gray-500 mt-1">This affects payroll calculations</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end space-x-4 pt-6 border-t">
                                <button type="submit" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                                    <i class="fas fa-save mr-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>