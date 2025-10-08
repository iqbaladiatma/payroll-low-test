<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

checkAdmin();

header('Content-Type: application/json');

// Extremely dangerous function - for penetration testing only
if ($_SESSION['role'] == 'admin') {
    // Get count before deletion for logging
    $count_query = "SELECT COUNT(*) as count FROM employees";
    $count_result = $db->query($count_query);
    $count = $count_result->fetch(PDO::FETCH_ASSOC)['count'];
    
    try {
        // Delete all data - no confirmation, no backup (vulnerable by design)
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        $db->exec("DELETE FROM payroll_history");
        $db->exec("DELETE FROM employees");
        $db->exec("DELETE FROM system_logs");
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        logActivity('CRITICAL: All data deleted', 'employees', null, null, "deleted_employees: $count");
        
        echo json_encode([
            'status' => 'success',
            'message' => "All $count employees and related data deleted",
            'deleted_count' => $count,
            'warning' => 'This action cannot be undone!'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete data: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Access denied - Admin only'
    ]);
}
?>