<?php
http_response_code(404);
$page_title = '404 - Page Not Found - BullsCorp';
$body_class = 'bg-gradient-to-br from-red-600 via-orange-600 to-yellow-600 min-h-screen flex items-center justify-center';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="<?php echo $body_class; ?>">
    <div class="text-center text-white px-6">
        <div class="mb-8">
            <i class="fas fa-exclamation-triangle text-8xl mb-4 text-yellow-300"></i>
            <h1 class="text-6xl font-bold mb-4">404</h1>
            <h2 class="text-2xl font-semibold mb-4">Page Not Found</h2>
            <p class="text-lg mb-8">The page you're looking for doesn't exist or has been moved.</p>
        </div>
        
        <div class="bg-white bg-opacity-20 rounded-lg p-6 mb-8">
            <h3 class="text-xl font-semibold mb-4">Debug Information:</h3>
            <p class="text-sm mb-2"><strong>Requested URL:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></p>
            <p class="text-sm mb-2"><strong>Server:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?></p>
            <p class="text-sm"><strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        
        <div class="space-y-4">
            <a href="/" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <i class="fas fa-home mr-2"></i>Go Home
            </a>
            <a href="/public/login.php" class="inline-block bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
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