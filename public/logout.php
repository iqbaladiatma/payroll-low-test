<?php
// Vulnerable Logout System - Low Security for Penetration Testing
// WARNING: This code contains intentional vulnerabilities!

session_start();

// Log logout activity (if user is logged in)
if (isset($_SESSION['user_id'])) {
    // Database connection
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'bullscorp_payroll';
    
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Log logout (vulnerable - no prepared statement)
        $user_id = $_SESSION['user_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = addslashes($_SERVER['HTTP_USER_AGENT']);
        
        $pdo->exec("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES 
                   ($user_id, 'LOGOUT', 'User logged out', '$ip_address', '$user_agent')");
        
    } catch (Exception $e) {
        // Ignore database errors during logout
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Clear remember me cookies (vulnerable - doesn't clear properly)
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}
if (isset($_COOKIE['remember_pass'])) {
    setcookie('remember_pass', '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect to login with message
header('Location: login.php?message=logged_out');
exit;
?>