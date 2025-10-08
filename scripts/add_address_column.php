<?php
// Add address column to employees table
// Run this script to fix the missing address column issue

echo "🔧 Adding address column to employees table...\n";

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=bullscorp_payroll", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if address column already exists
    $result = $pdo->query("SHOW COLUMNS FROM employees LIKE 'address'");
    
    if ($result->rowCount() == 0) {
        // Add address column after phone column
        $pdo->exec("ALTER TABLE employees ADD COLUMN address TEXT AFTER phone");
        echo "✅ Address column added successfully!\n";
    } else {
        echo "ℹ️  Address column already exists.\n";
    }
    
    echo "🎉 Migration completed!\n";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure MySQL server is running\n";
    echo "2. Check if database 'bullscorp_payroll' exists\n";
    echo "3. Verify MySQL connection settings\n";
}
?>