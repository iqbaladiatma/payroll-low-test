<?php
// Simple PHP Router for BullsCorp Payroll App
session_start();

// Get the requested URI
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove leading slash
$path = ltrim($path, '/');

// Define routes
$routes = [
    '' => 'index.php',
    'login' => 'public/login.php',
    'register' => 'public/register.php',
    'logout' => 'public/logout.php',
    'dashboard' => 'public/index.php',
    'admin' => 'admin/dashboard.php',
    'admin/dashboard' => 'admin/dashboard.php',
    'user' => 'user/dashboard.php',
    'user/dashboard' => 'user/dashboard.php',
    'search' => 'public/search.php',
    'test' => 'test_routing.php'
];

// Check if route exists
if (array_key_exists($path, $routes)) {
    $file = $routes[$path];
    
    // Check if file exists
    if (file_exists($file)) {
        include $file;
    } else {
        // File not found
        http_response_code(404);
        echo "<h1>404 - File Not Found</h1>";
        echo "<p>The requested file '$file' was not found.</p>";
        echo "<p>Requested path: '$path'</p>";
        echo "<a href='/'>Go to Home</a>";
    }
} else {
    // Check if it's a direct file request
    if (file_exists($path)) {
        include $path;
    } else {
        // Route not found
        http_response_code(404);
        echo "<h1>404 - Page Not Found</h1>";
        echo "<p>The requested page '$path' was not found.</p>";
        echo "<p>Available routes:</p>";
        echo "<ul>";
        foreach ($routes as $route => $file) {
            $display_route = $route ?: 'home';
            echo "<li><a href='/$route'>/$display_route</a></li>";
        }
        echo "</ul>";
    }
}
?>