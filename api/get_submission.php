<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$submission_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Get submission details
$query = "SELECT s.*, u.username as processed_by_name 
          FROM submissions s 
          LEFT JOIN users u ON s.processed_by = u.id 
          WHERE s.id = $submission_id";

// If not admin, only show user's own submissions
if ($_SESSION['role'] !== 'admin') {
    $query .= " AND s.user_id = $user_id";
}

$result = $db->query($query);
$submission = $result->fetch(PDO::FETCH_ASSOC);

if ($submission) {
    echo json_encode(['success' => true, 'submission' => $submission]);
} else {
    echo json_encode(['success' => false, 'message' => 'Submission not found']);
}
?>