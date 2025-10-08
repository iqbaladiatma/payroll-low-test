<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../src/controllers/EmployeeController.php';

checkAdmin();

header('Content-Type: application/json');

if ($_POST) {
    $employeeController = new EmployeeController();
    
    $id = sanitizeInput($_POST['id']);
    $name = sanitizeInput($_POST['name']);
    $position = sanitizeInput($_POST['position']);
    $salary = sanitizeInput($_POST['salary']);
    $department = sanitizeInput($_POST['department'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'active');
    
    $data = [
        'name' => $name,
        'position' => $position,
        'salary' => $salary,
        'department' => $department,
        'status' => $status
    ];
    
    if ($employeeController->updateEmployee($id, $data)) {
        logActivity('Employee updated via API', 'employees', $id, null, "name: $name, position: $position");
        echo json_encode([
            'status' => 'success', 
            'message' => 'Employee updated successfully',
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to update employee'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'No data provided'
    ]);
}
?>