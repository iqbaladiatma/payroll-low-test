<?php
// Simple test file
echo "<h1>🚀 BullsCorp Server Test</h1>";
echo "<p>✅ PHP Server is running!</p>";
echo "<p>📍 Current URL: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>🌐 Server: " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p>⏰ Time: " . date('Y-m-d H:i:s') . "</p>";

echo "<h2>🔗 Test Links:</h2>";
echo "<ul>";
echo "<li><a href='/'>Home</a></li>";
echo "<li><a href='/public/login.php'>Login</a></li>";
echo "<li><a href='/debug.php'>Debug</a></li>";
echo "<li><a href='/fix_paths.php'>Fix Paths</a></li>";
echo "</ul>";

echo "<h2>📊 Server Info:</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";
?>