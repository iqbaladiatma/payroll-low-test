<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../src/controllers/PayrollController.php';
require_once '../src/controllers/EmployeeController.php';

// Check if user is admin or HR
checkAuth();
if (!in_array($_SESSION['role'], ['admin', 'hr'])) {
    die('Access denied - Admin or HR only');
}

header('Content-Type: application/json');

if ($_POST) {
    $payrollController = new PayrollController();
    $employeeController = new EmployeeController();
    
    $employee_id = sanitizeInput($_POST['employee_id']);
    $amount = sanitizeInput($_POST['amount'] ?? 0);
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $processed_by = $_SESSION['user_id'];
    
    // Get employee data (vulnerable query)
    $employee = $employeeController->getEmployeeById($employee_id);
    
    if ($employee) {
        // Use provided amount or default to salary
        if (!$amount) {
            $amount = $employee['salary'];
        }
        
        if (!$notes) {
            $notes = "Monthly salary payment - " . date('F Y');
        }
        
        if ($payrollController->processPayroll($employee_id, $amount, $processed_by, $notes)) {
            logActivity('Payroll processed via API', 'payroll_history', null, null, "employee_id: $employee_id, amount: $amount");
            echo json_encode([
                'status' => 'success', 
                'message' => 'Payroll processed successfully',
                'amount' => $amount,
                'employee' => $employee['name']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to process payroll']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No data provided']);
}
?>