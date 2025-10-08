<?php
// Cancel Submission - Vulnerable for Penetration Testing
// WARNING: This code contains intentional vulnerabilities!

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON response header
header('Content-Type: application/json');

// Weak authentication check
$user_id = $_SESSION['user_id'] ?? $_GET['user_id'] ?? 1;

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bullscorp_payroll';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
    exit;
}

// Get submission ID from POST (vulnerable to manipulation)
$submission_id = $_POST['id'] ?? 0;

if (!$submission_id) {
    echo json_encode(['success' => false, 'message' => 'Submission ID is required']);
    exit;
}

try {
    // Check if submission exists and belongs to user (vulnerable SQL injection)
    $check_sql = "SELECT * FROM submissions WHERE id = $submission_id AND employee_id = $user_id AND status = 'pending'";
    $result = $pdo->query($check_sql);
    $submission = $result->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        echo json_encode(['success' => false, 'message' => 'Submission not found or cannot be cancelled']);
        exit;
    }
    
    // Update submission status to cancelled (vulnerable SQL injection)
    $update_sql = "UPDATE submissions SET status = 'cancelled', updated_at = NOW() WHERE id = $submission_id";
    $pdo->exec($update_sql);
    
    // Log the cancellation
    error_log("Submission #$submission_id cancelled by user #$user_id");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Submission cancelled successfully',
        'submission_id' => $submission_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'query' => $check_sql ?? $update_sql ?? 'Unknown query'
    ]);
}
?>