<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../src/controllers/EmployeeController.php';

checkAdmin();

// Get employee ID from URL (vulnerable to manipulation)
$employee_id = $_GET['id'] ?? 0;
$employeeController = new EmployeeController();

if ($employee_id) {
    // Get employee info before deletion for logging
    $employee = $employeeController->getEmployeeById($employee_id);
    
    // Vulnerable SQL injection for testing
    $query = "DELETE FROM employees WHERE id = $employee_id";
    
    if ($db->exec($query)) {
        if ($employee) {
            logActivity('Employee deleted', 'employees', $employee_id, null, "name: " . $employee['name']);
        }
        redirect('dashboard.php', 'Employee deleted successfully');
    } else {
        redirect('dashboard.php', 'Failed to delete employee');
    }
} else {
    redirect('dashboard.php', 'Invalid employee ID');
}
?>