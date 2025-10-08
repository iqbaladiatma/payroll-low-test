<?php
// Create departments table for BullsCorp Payroll

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bullscorp_payroll';

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating departments table...\n";
    
    // Create departments table
    $departments_table = "CREATE TABLE IF NOT EXISTS departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        manager_id INT NULL,
        budget DECIMAL(15,2) DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($departments_table);
    echo "✅ Departments table created\n";
    
    // Insert default departments
    $departments = [
        ['IT', 'Information Technology Department', 50000000],
        ['HR', 'Human Resources Department', 30000000],
        ['Finance', 'Finance and Accounting Department', 40000000],
        ['Marketing', 'Marketing and Communications Department', 35000000],
        ['Operations', 'Operations and Production Department', 60000000],
        ['Sales', 'Sales and Business Development Department', 45000000],
        ['Legal', 'Legal and Compliance Department', 25000000],
        ['Admin', 'Administration and General Affairs Department', 20000000],
        ['General', 'General Department', 15000000]
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO departments (name, description, budget) VALUES (?, ?, ?)");
    
    foreach ($departments as $dept) {
        $stmt->execute($dept);
    }
    
    echo "✅ Default departments inserted\n";
    echo "🎉 Departments table setup completed!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>