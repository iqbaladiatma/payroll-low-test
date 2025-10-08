<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../src/controllers/EmployeeController.php';

checkAdmin();

header('Content-Type: application/json');

if ($_GET && isset($_GET['id'])) {
    $employee_id = sanitizeInput($_GET['id']);
    $employeeController = new EmployeeController();
    
    // Get employee info before deletion for logging
    $employee = $employeeController->getEmployeeById($employee_id);
    
    if ($employee) {
        if ($employeeController->deleteEmployee($employee_id)) {
            logActivity('Employee deleted via API', 'employees', $employee_id, null, "name: " . $employee['name']);
            echo json_encode([
                'status' => 'success',
                'message' => 'Employee deleted successfully',
                'employee' => $employee['name']
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to delete employee'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Employee not found'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Employee ID required'
    ]);
}
?>