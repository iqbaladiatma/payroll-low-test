<?php
// BullsCorp Payroll System - Main Entry Point
// WARNING: This application contains intentional vulnerabilities for penetration testing!

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['username']);
$user_role = $_SESSION['role'] ?? 'guest';

// Redirect logic based on authentication and role
if ($is_logged_in) {
    switch ($user_role) {
        case 'admin':
        case 'hr':
            header('Location: admin/dashboard.php');
            exit;
        case 'employee':
            header('Location: user/dashboard.php');
            exit;
        default:
            // Invalid role, logout
            session_destroy();
            header('Location: public/login.php');
            exit;
    }
} else {
    // Not logged in, redirect to login
    header('Location: public/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BullsCorp Payroll System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-600 via-purple-600 to-red-600 min-h-screen flex items-center justify-center">
    <div class="text-center text-white">
        <div class="mb-8">
            <i class="fas fa-building text-8xl mb-4"></i>
            <h1 class="text-6xl font-bold mb-4">BullsCorp</h1>
            <h2 class="text-2xl font-semibold mb-4">Payroll Management System</h2>
            <p class="text-lg mb-8">Redirecting...</p>
        </div>
        
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-white mx-auto"></div>
        
        <div class="mt-8 text-sm opacity-75">
            <p>⚠️ This is a vulnerable application for penetration testing purposes only!</p>
        </div>
    </div>
    
    <script>
        // Auto redirect after 2 seconds if JavaScript is enabled
        setTimeout(function() {
            window.location.href = 'public/login.php';
        }, 2000);
    </script>
</body>
</html>