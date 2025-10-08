<?php
// Test dashboard with full error capture
session_start();

// Set the session data like the working user
$_SESSION['user_id'] = 55;
$_SESSION['username'] = 'bull';
$_SESSION['email'] = 'bull@gmail.com';
$_SESSION['role'] = 'employee';
$_SESSION['employee_id'] = 51;

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "Testing dashboard with error capture...<br><br>";

// Capture all output and errors
ob_start();
$error_output = '';

// Custom error handler
set_error_handler(function($severity, $message, $file, $line) use (&$error_output) {
    $error_output .= "ERROR: $message in $file on line $line\n";
    return false; // Let PHP handle the error normally too
});

// Custom exception handler
set_exception_handler(function($exception) use (&$error_output) {
    $error_output .= "EXCEPTION: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
});

try {
    include 'user/dashboard.php';
    $dashboard_content = ob_get_contents();
    ob_end_clean();
    
    echo "<h2>Dashboard Output Status:</h2>";
    if (empty($dashboard_content)) {
        echo "<p style='color: red;'>❌ Dashboard produced NO output</p>";
    } else {
        echo "<p style='color: green;'>✅ Dashboard produced " . strlen($dashboard_content) . " characters</p>";
        
        // Check if it's just whitespace
        if (trim($dashboard_content) === '') {
            echo "<p style='color: red;'>❌ Output is only whitespace</p>";
        } else {
            echo "<p style='color: green;'>✅ Output contains actual content</p>";
        }
        
        // Show first part of output
        echo "<h3>First 500 characters:</h3>";
        echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 200px; overflow: auto;'>";
        echo htmlspecialchars(substr($dashboard_content, 0, 500));
        echo "</pre>";
    }
    
} catch (ParseError $e) {
    ob_end_clean();
    echo "<p style='color: red;'>❌ PARSE ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "</p>";
} catch (Error $e) {
    ob_end_clean();
    echo "<p style='color: red;'>❌ FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "</p>";
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color: red;'>❌ EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "</p>";
}

// Show any captured errors
if (!empty($error_output)) {
    echo "<h2>Captured Errors:</h2>";
    echo "<pre style='background: #ffe6e6; padding: 10px; color: red;'>";
    echo htmlspecialchars($error_output);
    echo "</pre>";
} else {
    echo "<h2>No Errors Captured</h2>";
}

// Restore error handlers
restore_error_handler();
restore_exception_handler();
?>