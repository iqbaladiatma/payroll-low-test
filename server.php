<?php
// PHP Built-in Server Router for BullsCorp Payroll
// This file handles all routing for the development server

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$uri = strtok($uri, '?'); // Remove query string

// Serve static files directly
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|pdf|txt)$/', $uri)) {
    $file = __DIR__ . $uri;
    if (file_exists($file)) {
        return false; // Let PHP serve the file
    }
}

// Route handling
switch (true) {
    // Root - redirect to main app
    case $uri === '/' || $uri === '':
        require __DIR__ . '/index.php';
        return true;
        
    // Debug page
    case $uri === '/debug.php':
        require __DIR__ . '/debug.php';
        return true;
        
    // Test routing page
    case $uri === '/test_routing.php':
        require __DIR__ . '/test_routing.php';
        return true;
        
    // Public routes
    case preg_match('/^\/public\/(.+)$/', $uri, $matches):
        $file = __DIR__ . '/public/' . $matches[1];
        if (file_exists($file)) {
            require $file;
            return true;
        }
        break;
        
    // Admin routes
    case preg_match('/^\/admin\/(.+)$/', $uri, $matches):
        $file = __DIR__ . '/admin/' . $matches[1];
        if (file_exists($file)) {
            require $file;
            return true;
        }
        break;
        
    // User routes
    case preg_match('/^\/user\/(.+)$/', $uri, $matches):
        $file = __DIR__ . '/user/' . $matches[1];
        if (file_exists($file)) {
            require $file;
            return true;
        }
        break;
        
    // Tools routes
    case preg_match('/^\/tools\/(.+)$/', $uri, $matches):
        $file = __DIR__ . '/tools/' . $matches[1];
        if (file_exists($file)) {
            require $file;
            return true;
        }
        break;
        
    // API routes
    case preg_match('/^\/api\/(.+)$/', $uri, $matches):
        $file = __DIR__ . '/api/' . $matches[1];
        if (file_exists($file)) {
            require $file;
            return true;
        }
        break;
        
    // Assets routes
    case preg_match('/^\/assets\/(.+)$/', $uri, $matches):
        $file = __DIR__ . '/assets/' . $matches[1];
        if (file_exists($file)) {
            return false; // Let PHP serve the file
        }
        break;
        
    // Config routes (for debugging only)
    case preg_match('/^\/config\/(.+)$/', $uri, $matches):
        $file = __DIR__ . '/config/' . $matches[1];
        if (file_exists($file)) {
            require $file;
            return true;
        }
        break;
}

// 404 - File not found
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Not Found - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-red-600 via-orange-600 to-yellow-600 min-h-screen flex items-center justify-center">
    <div class="text-center text-white px-6 max-w-2xl">
        <div class="mb-8">
            <i class="fas fa-exclamation-triangle text-8xl mb-4 text-yellow-300"></i>
            <h1 class="text-6xl font-bold mb-4">404</h1>
            <h2 class="text-2xl font-semibold mb-4">Page Not Found</h2>
            <p class="text-lg mb-8">The requested resource was not found on this server.</p>
        </div>
        
        <div class="bg-white bg-opacity-20 rounded-lg p-6 mb-8 text-left">
            <h3 class="text-xl font-semibold mb-4 text-center">Debug Information:</h3>
            <div class="space-y-2 text-sm">
                <p><strong>Requested URI:</strong> <?php echo htmlspecialchars($uri); ?></p>
                <p><strong>Full URL:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></p>
                <p><strong>Server:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?></p>
                <p><strong>Method:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_METHOD']); ?></p>
                <p><strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div class="bg-white bg-opacity-20 rounded-lg p-4">
                <h4 class="font-semibold mb-2">Available Routes:</h4>
                <ul class="text-sm space-y-1">
                    <li><a href="/" class="hover:underline">/ - Home</a></li>
                    <li><a href="/public/login.php" class="hover:underline">/public/login.php - Login</a></li>
                    <li><a href="/admin/dashboard.php" class="hover:underline">/admin/dashboard.php - Admin</a></li>
                    <li><a href="/user/dashboard.php" class="hover:underline">/user/dashboard.php - User</a></li>
                    <li><a href="/tools/logs.php" class="hover:underline">/tools/logs.php - Logs</a></li>
                </ul>
            </div>
            <div class="bg-white bg-opacity-20 rounded-lg p-4">
                <h4 class="font-semibold mb-2">Debug Tools:</h4>
                <ul class="text-sm space-y-1">
                    <li><a href="/debug.php" class="hover:underline">/debug.php - Debug Info</a></li>
                    <li><a href="/test_routing.php" class="hover:underline">/test_routing.php - Test Routing</a></li>
                    <li><a href="/tools/server_info.php" class="hover:underline">/tools/server_info.php - Server Info</a></li>
                </ul>
            </div>
        </div>
        
        <div class="space-x-4">
            <a href="/" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <i class="fas fa-home mr-2"></i>Go Home
            </a>
            <a href="/debug.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <i class="fas fa-bug mr-2"></i>Debug Info
            </a>
        </div>
        
        <div class="mt-8 text-sm opacity-75">
            <p>⚠️ This is a vulnerable application for penetration testing purposes only!</p>
        </div>
    </div>
</body>
</html>
<?php
return true;
?>