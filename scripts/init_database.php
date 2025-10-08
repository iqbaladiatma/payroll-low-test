<?php
// MySQL Database initialization script for BullsCorp Payroll App
// Run this script to create the database and tables with Faker data

echo "🚀 BullsCorp Payroll Database Initialization\n";
echo "============================================\n\n";

try {
    // Connect to MySQL server (without database)
    echo "🔌 Connecting to MySQL server...\n";
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to MySQL server\n";
    
    // Create database
    echo "📊 Creating database 'bullscorp_payroll'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `bullscorp_payroll` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database 'bullscorp_payroll' created successfully!\n\n";
    
    // Include the table creation and seeding script
    echo "🏗️  Running table creation and data seeding...\n";
    require_once __DIR__ . '/../config/init_mysql.php';
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure MySQL server is running\n";
    echo "2. Check if root user has proper permissions\n";
    echo "3. Verify MySQL connection settings\n";
    echo "4. Run 'composer install' to install Faker PHP\n";
}
?>