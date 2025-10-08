<?php
// MySQL Database initialization and seeding script for BullsCorp Payroll
// WARNING: This contains intentional vulnerabilities for penetration testing!

// Mengimpor autoloader Composer untuk library Faker
require_once __DIR__ . '/../vendor/autoload.php';

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bullscorp_payroll';

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("SET FOREIGN_KEY_CHECKS = 0"); // Disable foreign key checks for easier setup
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Inisialisasi Faker untuk data berbahasa Indonesia
$faker = Faker\Factory::create('id_ID');

try {
    echo "🚀 Membuat struktur tabel database BullsCorp Payroll...\n";

    // Drop existing tables if they exist (for clean setup)
    $db->exec("DROP TABLE IF EXISTS system_logs");
    $db->exec("DROP TABLE IF EXISTS attendance");
    $db->exec("DROP TABLE IF EXISTS submissions");
    $db->exec("DROP TABLE IF EXISTS salaries");
    $db->exec("DROP TABLE IF EXISTS users");
    $db->exec("DROP TABLE IF EXISTS employees");
    echo "🗑️  Existing tables dropped\n";

    // 1. Create employees table
    $employees_table = "CREATE TABLE employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_code VARCHAR(20) UNIQUE,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE,
        phone VARCHAR(20),
        department VARCHAR(100),
        position VARCHAR(100),
        salary DECIMAL(15,2) DEFAULT 0,
        hire_date DATE,
        status ENUM('active', 'inactive', 'terminated') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($employees_table);
    echo "✅ Tabel employees berhasil dibuat\n";

    // 2. Create users table (for authentication)
    $users_table = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE,
        role ENUM('admin', 'hr', 'employee') DEFAULT 'employee',
        employee_id INT NULL,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_employee_id (employee_id)
    )";
    $db->exec($users_table);
    echo "✅ Tabel users berhasil dibuat\n";

    // 3. Create salaries table
    $salaries_table = "CREATE TABLE salaries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT,
        pay_period DATE,
        basic_salary DECIMAL(15,2),
        allowances DECIMAL(15,2) DEFAULT 0,
        overtime_pay DECIMAL(15,2) DEFAULT 0,
        deductions DECIMAL(15,2) DEFAULT 0,
        total_amount DECIMAL(15,2),
        status ENUM('pending', 'processing', 'paid', 'cancelled') DEFAULT 'pending',
        processed_by INT,
        processed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_employee_id (employee_id),
        INDEX idx_pay_period (pay_period)
    )";
    $db->exec($salaries_table);
    echo "✅ Tabel salaries berhasil dibuat\n";

    // 4. Create submissions table
    $submissions_table = "CREATE TABLE submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT,
        submission_type ENUM('leave', 'overtime', 'expense', 'advance', 'other') NOT NULL,
        description TEXT,
        start_date DATE,
        end_date DATE,
        amount DECIMAL(15,2) DEFAULT 0,
        priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
        attachment_path VARCHAR(500),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        approved_by INT,
        approved_at TIMESTAMP NULL,
        rejected_by INT,
        rejected_at TIMESTAMP NULL,
        rejection_reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_employee_id (employee_id),
        INDEX idx_status (status)
    )";
    $db->exec($submissions_table);
    echo "✅ Tabel submissions berhasil dibuat\n";

    // 5. Create attendance table
    $attendance_table = "CREATE TABLE attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT,
        date DATE,
        time_in TIME,
        time_out TIME,
        status ENUM('present', 'late', 'absent', 'half_day') DEFAULT 'present',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_employee_id (employee_id),
        INDEX idx_date (date),
        UNIQUE KEY unique_employee_date (employee_id, date)
    )";
    $db->exec($attendance_table);
    echo "✅ Tabel attendance berhasil dibuat\n";

    // 6. Create system_logs table
    $logs_table = "CREATE TABLE system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100),
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action)
    )";
    $db->exec($logs_table);
    echo "✅ Tabel system_logs berhasil dibuat\n";

    echo "\n🌱 === SEEDING DATA DENGAN FAKER ===\n";

    // 1. Seed Employees Table (50 records)
    echo "🌱 Seeding tabel employees...\n";
    $stmt = $db->prepare("INSERT INTO employees (employee_code, name, email, phone, department, position, salary, hire_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $departments = ['IT', 'HR', 'Finance', 'Marketing', 'Operations', 'Sales', 'Legal', 'Admin'];
    $positions = ['Staff', 'Senior Staff', 'Supervisor', 'Manager', 'Senior Manager', 'Director'];
    
    for ($i = 1; $i <= 50; $i++) {
        $stmt->execute([
            'EMP' . str_pad($i, 4, '0', STR_PAD_LEFT),
            $faker->name,
            $faker->unique()->safeEmail,
            $faker->phoneNumber,
            $faker->randomElement($departments),
            $faker->randomElement($positions),
            $faker->numberBetween(3000000, 15000000),
            $faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            $faker->randomElement(['active', 'active', 'active', 'inactive']) // 75% active
        ]);
    }
    echo "✅ 50 employees berhasil dibuat\n";

    // 2. Seed Users Table (dengan link ke employees)
    echo "🌱 Seeding tabel users...\n";
    
    // Create admin users
    $admin_password = password_hash('admin5231', PASSWORD_BCRYPT);
    $hr_password = password_hash('hr2145', PASSWORD_BCRYPT);
    
    $db->exec("INSERT INTO users (username, password, email, role, employee_id) VALUES 
        ('admin', '$admin_password', 'admin@bullscorp.com', 'admin', 5),
        ('hr', '$hr_password', 'hr@bullscorp.com', 'hr', 7)");
    
    // Create employee users
    $stmt = $db->prepare("INSERT INTO users (username, password, email, role, employee_id) VALUES (?, ?, ?, ?, ?)");
    $employee_password = password_hash('halahgampang', PASSWORD_BCRYPT);
    
    for ($i = 3; $i <= 50; $i++) {
        $employee = $db->query("SELECT * FROM employees WHERE id = $i")->fetch(PDO::FETCH_ASSOC);
        if ($employee) {
            $username = strtolower(str_replace(' ', '.', $employee['name']));
            $username = preg_replace('/[^a-z0-9.]/', '', $username); // Clean username
            $stmt->execute([
                $username,
                $employee_password,
                $employee['email'],
                'employee',
                $i
            ]);
        }
    }
    echo "✅ Users berhasil dibuat (admin/admin123, hr/hr123, employees/password123)\n";

    // 3. Seed Salaries Table (200 records - multiple months per employee)
    echo "🌱 Seeding tabel salaries...\n";
    $stmt = $db->prepare("INSERT INTO salaries (employee_id, pay_period, basic_salary, allowances, overtime_pay, deductions, total_amount, status, processed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $employee_ids = $db->query("SELECT id FROM employees WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    $admin_ids = [1, 2]; // Admin and HR users
    
    foreach ($employee_ids as $emp_id) {
        $employee = $db->query("SELECT salary FROM employees WHERE id = $emp_id")->fetch(PDO::FETCH_ASSOC);
        $basic_salary = $employee['salary'];
        
        // Generate salary for last 4 months
        for ($month = 0; $month < 4; $month++) {
            $pay_period = date('Y-m-01', strtotime("-$month months"));
            $allowances = $faker->numberBetween(200000, 1000000);
            $overtime_pay = $faker->numberBetween(0, 2000000);
            $deductions = $faker->numberBetween(100000, 500000);
            $total = $basic_salary + $allowances + $overtime_pay - $deductions;
            
            $stmt->execute([
                $emp_id,
                $pay_period,
                $basic_salary,
                $allowances,
                $overtime_pay,
                $deductions,
                $total,
                $faker->randomElement(['paid', 'paid', 'paid', 'pending']), // 75% paid
                $faker->randomElement($admin_ids)
            ]);
        }
    }
    echo "✅ Salary records berhasil dibuat\n";

    // 4. Seed Submissions Table (100 records)
    echo "🌱 Seeding tabel submissions...\n";
    $stmt = $db->prepare("INSERT INTO submissions (employee_id, submission_type, description, start_date, end_date, amount, priority, status, approved_by, approved_at, rejected_by, rejection_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $employee_ids = $db->query("SELECT id FROM employees")->fetchAll(PDO::FETCH_COLUMN);
    $admin_ids = [1, 2];
    
    $submission_types = ['leave', 'overtime', 'expense', 'advance', 'other'];
    $priorities = ['low', 'normal', 'high', 'urgent'];
    $statuses = ['pending', 'approved', 'rejected'];
    
    for ($i = 0; $i < 100; $i++) {
        $status = $faker->randomElement($statuses);
        $start_date = $faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d');
        $end_date = date('Y-m-d', strtotime($start_date . ' +' . $faker->numberBetween(1, 14) . ' days'));
        
        $approved_by = null;
        $approved_at = null;
        $rejected_by = null;
        $rejection_reason = null;
        
        if ($status === 'approved') {
            $approved_by = $faker->randomElement($admin_ids);
            $approved_at = $faker->dateTimeBetween($start_date, 'now')->format('Y-m-d H:i:s');
        } elseif ($status === 'rejected') {
            $rejected_by = $faker->randomElement($admin_ids);
            $rejection_reason = $faker->sentence();
        }
        
        $stmt->execute([
            $faker->randomElement($employee_ids),
            $faker->randomElement($submission_types),
            $faker->paragraph(2),
            $start_date,
            $end_date,
            $faker->numberBetween(0, 5000000),
            $faker->randomElement($priorities),
            $status,
            $approved_by,
            $approved_at,
            $rejected_by,
            $rejection_reason
        ]);
    }
    echo "✅ Submissions berhasil dibuat\n";

    // 5. Seed Attendance Table (500 records)
    echo "🌱 Seeding tabel attendance...\n";
    $stmt = $db->prepare("INSERT IGNORE INTO attendance (employee_id, date, time_in, time_out, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
    
    $employee_ids = $db->query("SELECT id FROM employees WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    
    // Generate attendance for last 30 days
    for ($day = 0; $day < 30; $day++) {
        $date = date('Y-m-d', strtotime("-$day days"));
        
        foreach ($employee_ids as $emp_id) {
            // Skip weekends
            if (date('N', strtotime($date)) >= 6) continue;
            
            $status = $faker->randomElement(['present', 'present', 'present', 'late', 'absent']);
            $time_in = null;
            $time_out = null;
            $notes = null;
            
            if ($status !== 'absent') {
                $time_in = $faker->time('H:i:s', '09:30:00');
                $time_out = $faker->time('H:i:s', '18:30:00');
            }
            
            if ($status === 'late') {
                $notes = 'Terlambat karena macet';
            } elseif ($status === 'absent') {
                $notes = 'Sakit/Izin';
            }
            
            $stmt->execute([
                $emp_id,
                $date,
                $time_in,
                $time_out,
                $status,
                $notes
            ]);
        }
    }
    echo "✅ Attendance records berhasil dibuat\n";

    // 6. Seed System Logs (200 records)
    echo "🌱 Seeding tabel system_logs...\n";
    $stmt = $db->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    
    $user_ids = $db->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $actions = ['LOGIN_SUCCESS', 'LOGIN_FAILED', 'LOGOUT', 'CREATE_SUBMISSION', 'UPDATE_PROFILE', 'VIEW_SALARY', 'DOWNLOAD_PAYSLIP'];
    
    for ($i = 0; $i < 200; $i++) {
        $action = $faker->randomElement($actions);
        $description = "User performed action: $action";
        
        $stmt->execute([
            $faker->randomElement($user_ids),
            $action,
            $description,
            $faker->ipv4,
            $faker->userAgent
        ]);
    }
    echo "✅ System logs berhasil dibuat\n";

    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n🎉 Database initialization and seeding completed successfully!\n";
    echo "📊 Database: bullscorp_payroll\n";
    echo "🔗 Connection: localhost:3306\n\n";
    echo "👥 Default Login Credentials:\n";
    echo "   👑 Admin: admin / admin5231\n";
    echo "   👤 HR: hr / hr2145\n";
    echo "   👥 Employees: [employee.name] / halahgampang\n\n";
    echo "� Datab Summary:\n";
    echo "   • 50 Employees\n";
    echo "   • 52 Users (2 admin + 50 employees)\n";
    echo "   • 200+ Salary Records\n";
    echo "   • 100 Submissions\n";
    echo "   • 500+ Attendance Records\n";
    echo "   • 200 System Logs\n\n";
    echo "⚠️  WARNING: This database is intentionally vulnerable for penetration testing!\n";
    echo "🔒 Vulnerabilities included: SQL Injection, XSS, File Upload, Auth Bypass, etc.\n";

} catch (PDOException $e) {
    echo "❌ Error during database operation: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure MySQL server is running\n";
    echo "2. Check if database 'bullscorp_payroll' exists\n";
    echo "3. Verify MySQL connection settings\n";
    echo "4. Run: composer install (to install Faker)\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>