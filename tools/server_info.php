<?php
// Server Information Page
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Info - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-600 to-purple-600 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6 text-center">
                <i class="fas fa-server text-white text-4xl mb-4"></i>
                <h1 class="text-3xl font-bold text-white">BullsCorp Server Information</h1>
                <p class="text-blue-100 mt-2">Network Access Configuration</p>
            </div>
            
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Server Details -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>Server Details
                        </h2>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Server IP:</span>
                                <span class="font-mono bg-blue-100 px-2 py-1 rounded"><?php echo $_SERVER['SERVER_ADDR']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Server Port:</span>
                                <span class="font-mono bg-blue-100 px-2 py-1 rounded"><?php echo $_SERVER['SERVER_PORT']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">PHP Version:</span>
                                <span class="font-mono bg-green-100 px-2 py-1 rounded"><?php echo PHP_VERSION; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Server Software:</span>
                                <span class="font-mono bg-gray-100 px-2 py-1 rounded text-xs"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Network Access -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-network-wired text-green-600 mr-2"></i>Network Access
                        </h2>
                        <div class="space-y-3">
                            <div>
                                <span class="text-gray-600">Local Access:</span>
                                <div class="mt-1">
                                    <a href="http://localhost:<?php echo $_SERVER['SERVER_PORT']; ?>" 
                                       class="font-mono bg-blue-100 px-2 py-1 rounded text-blue-700 hover:bg-blue-200 transition-colors">
                                        http://localhost:<?php echo $_SERVER['SERVER_PORT']; ?>
                                    </a>
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-600">LAN Access:</span>
                                <div class="mt-1">
                                    <span class="font-mono bg-green-100 px-2 py-1 rounded text-green-700">
                                        http://<?php echo $_SERVER['SERVER_ADDR']; ?>:<?php echo $_SERVER['SERVER_PORT']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Client Information -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-desktop text-purple-600 mr-2"></i>Your Connection
                        </h2>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Your IP:</span>
                                <span class="font-mono bg-purple-100 px-2 py-1 rounded"><?php echo $_SERVER['REMOTE_ADDR']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">User Agent:</span>
                                <span class="font-mono bg-gray-100 px-2 py-1 rounded text-xs"><?php echo substr($_SERVER['HTTP_USER_AGENT'], 0, 30) . '...'; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Request Method:</span>
                                <span class="font-mono bg-yellow-100 px-2 py-1 rounded"><?php echo $_SERVER['REQUEST_METHOD']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-rocket text-red-600 mr-2"></i>Quick Actions
                        </h2>
                        <div class="space-y-3">
                            <a href="../public/login.php" class="block bg-blue-600 text-white text-center py-2 px-4 rounded hover:bg-blue-700 transition-colors">
                                <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                            </a>
                            <a href="../public/register.php" class="block bg-green-600 text-white text-center py-2 px-4 rounded hover:bg-green-700 transition-colors">
                                <i class="fas fa-user-plus mr-2"></i>Register Account
                            </a>
                            <button onclick="copyLANUrl()" class="block w-full bg-purple-600 text-white text-center py-2 px-4 rounded hover:bg-purple-700 transition-colors">
                                <i class="fas fa-copy mr-2"></i>Copy LAN URL
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="mt-8 bg-blue-50 border-l-4 border-blue-400 p-6">
                    <h3 class="text-lg font-bold text-blue-800 mb-3">
                        <i class="fas fa-lightbulb mr-2"></i>How to Access from Other Devices
                    </h3>
                    <ol class="list-decimal list-inside space-y-2 text-blue-700">
                        <li>Make sure all devices are connected to the same WiFi/LAN network</li>
                        <li>Copy the LAN URL: <code class="bg-white px-2 py-1 rounded">http://<?php echo $_SERVER['SERVER_ADDR']; ?>:<?php echo $_SERVER['SERVER_PORT']; ?></code></li>
                        <li>Open web browser on other device and paste the URL</li>
                        <li>Use demo credentials: <strong>admin/admin123</strong> or <strong>user/user123</strong></li>
                    </ol>
                </div>

                <!-- Security Warning -->
                <div class="mt-6 bg-red-50 border-l-4 border-red-400 p-6">
                    <h3 class="text-lg font-bold text-red-800 mb-2">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Security Warning
                    </h3>
                    <p class="text-red-700">This application is intentionally vulnerable for penetration testing purposes. Only use in isolated testing environments!</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyLANUrl() {
            const url = `http://<?php echo $_SERVER['SERVER_ADDR']; ?>:<?php echo $_SERVER['SERVER_PORT']; ?>`;
            navigator.clipboard.writeText(url).then(() => {
                alert('LAN URL copied to clipboard: ' + url);
            }).catch(() => {
                prompt('Copy this URL:', url);
            });
        }
    </script>
</body>
</html>