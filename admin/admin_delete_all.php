<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

checkAdmin();

// Dangerous function with minimal protection (for testing purposes)
if ($_SESSION['role'] == 'admin') {
    // Get count before deletion for logging
    $count_query = "SELECT COUNT(*) as count FROM employees";
    $count_result = $db->query($count_query);
    $count = $count_result->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Delete all employees - no confirmation, no backup (vulnerable by design)
    $db->exec("DELETE FROM employees");
    $db->exec("DELETE FROM payroll_history");
    
    logActivity('DANGER: All employees deleted', 'employees', null, null, "deleted_count: $count");
    
    redirect('dashboard.php', "All $count employees deleted successfully");
} else {
    redirect('dashboard.php', 'Access denied');
}
?>