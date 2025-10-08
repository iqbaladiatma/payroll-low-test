<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vulnerable API endpoint - no proper authentication
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_GET['user_id'] ?? $_SESSION['user_id'];

// Vulnerable SQL query - allows access to any user's data
$query = "SELECT u.*, e.name as employee_name, e.position, e.salary, e.department 
          FROM users u 
          LEFT JOIN employees e ON u.id = e.user_id 
          WHERE u.id = $user_id";

$result = $db->query($query);
$user_data = $result ? $result->fetch(PDO::FETCH_ASSOC) : null;

if ($user_data) {
    // Expose sensitive data (vulnerable)
    echo json_encode([
        'status' => 'success',
        'user' => $user_data,
        'session_id' => session_id(),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
        ]
    ]);
} else {
    echo json_encode(['error' => 'User not found']);
}
?>