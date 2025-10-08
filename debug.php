<?php
// Debug routing and server information
echo "<h1>🔧 BullsCorp Payroll - Debug Information</h1>";

echo "<h2>📍 Server Information</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Variable</th><th>Value</th></tr>";
echo "<tr><td>REQUEST_URI</td><td>" . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>SCRIPT_NAME</td><td>" . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>DOCUMENT_ROOT</td><td>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>SERVER_NAME</td><td>" . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>SERVER_PORT</td><td>" . ($_SERVER['SERVER_PORT'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>HTTP_HOST</td><td>" . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>QUERY_STRING</td><td>" . ($_SERVER['QUERY_STRING'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>Current Directory</td><td>" . __DIR__ . "</td></tr>";
echo "<tr><td>Current File</td><td>" . __FILE__ . "</td></tr>";
echo "</table>";

echo "<h2>📁 File Structure Check</h2>";
$files_to_check = [
    'public/login.php',
    'public/register.php',
    'public/index.php',
    'admin/dashboard.php',
    'user/dashboard.php',
    'tools/logs.php',
    'config/database.php',
    'includes/functions.php',
    'server.php',
    '.htaccess'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>File Path</th><th>Status</th><th>Full Path</th></tr>";
foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    $status = file_exists($full_path) ? "✅ EXISTS" : "❌ MISSING";
    echo "<tr><td>$file</td><td>$status</td><td>$full_path</td></tr>";
}
echo "</table>";

echo "<h2>🔐 Session Information</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "<p>✅ User logged in:</p>";
    echo "<ul>";
    echo "<li><strong>User ID:</strong> " . $_SESSION['user_id'] . "</li>";
    echo "<li><strong>Username:</strong> " . $_SESSION['username'] . "</li>";
    echo "<li><strong>Role:</strong> " . $_SESSION['role'] . "</li>";
    echo "</ul>";
} else {
    echo "<p>❌ No user logged in</p>";
}

echo "<h2>🗄️ Database Connection Test</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✅ Database connection successful<br>";
        
        // Test query
        $result = $db->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $count = $result->fetch(PDO::FETCH_ASSOC);
            echo "✅ Users table accessible - " . $count['count'] . " users found<br>";
        }
        
        $result = $db->query("SELECT COUNT(*) as count FROM employees");
        if ($result) {
            $count = $result->fetch(PDO::FETCH_ASSOC);
            echo "✅ Employees table accessible - " . $count['count'] . " employees found<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

echo "<h2>🔗 Test Links</h2>";
echo "<ul>";
echo "<li><a href='/'>Root Index</a></li>";
echo "<li><a href='/public/login.php'>Login Page</a></li>";
echo "<li><a href='/public/register.php'>Register Page</a></li>";
echo "<li><a href='/admin/dashboard.php'>Admin Dashboard</a></li>";
echo "<li><a href='/user/dashboard.php'>User Dashboard</a></li>";
echo "<li><a href='/tools/logs.php'>System Logs</a></li>";
echo "<li><a href='/tools/server_info.php'>Server Info</a></li>";
echo "</ul>";

echo "<h2>📋 Routing Test</h2>";
echo "<p>Current URL: <strong>" . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</strong></p>";

if (isset($_GET['test'])) {
    echo "<p>✅ GET parameter received: " . htmlspecialchars($_GET['test']) . "</p>";
}

echo "<p><a href='?test=routing_works'>Test GET Parameter</a></p>";

echo "<h2>🚀 Quick Actions</h2>";
echo "<ul>";
if (isset($_SESSION['user_id'])) {
    echo "<li><a href='/public/logout.php'>Logout</a></li>";
    if ($_SESSION['role'] == 'admin') {
        echo "<li><a href='/admin/dashboard.php'>Go to Admin Dashboard</a></li>";
    } else {
        echo "<li><a href='/user/dashboard.php'>Go to User Dashboard</a></li>";
    }
} else {
    echo "<li><a href='/public/login.php'>Login</a></li>";
}
echo "</ul>";

echo "<hr>";
echo "<p><small>Debug file: " . __FILE__ . "</small></p>";
echo "<p><small>Generated at: " . date('Y-m-d H:i:s') . "</small></p>";
?>