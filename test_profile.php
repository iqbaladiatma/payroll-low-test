<?php
// Test profile page with error capture
session_start();

// Set the session data like a logged in user
$_SESSION['user_id'] = 55;
$_SESSION['username'] = 'bull';
$_SESSION['email'] = 'bull@gmail.com';
$_SESSION['role'] = 'employee';
$_SESSION['employee_id'] = 51;

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing profile page with error capture...<br><br>";

// Capture all output and errors
ob_start();
$error_output = '';

// Custom error handler
set_error_handler(function($severity, $message, $file, $line) use (&$error_output) {
    $error_output .= "ERROR: $message in $file on line $line\n";
    return false; // Let PHP handle the error normally too
});

try {
    include 'user/profile.php';
    $profile_content = ob_get_contents();
    ob_end_clean();
    
    echo "<h2>Profile Page Output Status:</h2>";
    if (empty($profile_content)) {
        echo "<p style='color: red;'>❌ Profile page produced NO output</p>";
    } else {
        echo "<p style='color: green;'>✅ Profile page produced " . strlen($profile_content) . " characters</p>";
        
        // Check if it's just whitespace
        if (trim($profile_content) === '') {
            echo "<p style='color: red;'>❌ Output is only whitespace</p>";
        } else {
            echo "<p style='color: green;'>✅ Output contains actual content</p>";
        }
        
        // Show first part of output
        echo "<h3>First 500 characters:</h3>";
        echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 200px; overflow: auto;'>";
        echo htmlspecialchars(substr($profile_content, 0, 500));
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
    echo "<h2>No Errors Captured ✅</h2>";
}

// Restore error handlers
restore_error_handler();
?>