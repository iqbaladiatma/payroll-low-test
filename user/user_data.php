<?php
session_start();

// Vulnerable - exposes user data without proper validation
$user_id = $_GET['user_id'];

$db = new SQLite3('payroll.db');

// Vulnerable SQL injection
$query = "SELECT * FROM users WHERE id = $user_id";
$result = $db->query($query);
$user = $result->fetchArray();

if ($user) {
    // Expose sensitive information
    echo json_encode([
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'password' => $user['password'], // Extremely vulnerable - exposing password
        'created_at' => $user['created_at']
    ]);
} else {
    echo json_encode(['error' => 'User not found']);
}

$db->close();
?>