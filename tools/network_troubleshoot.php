<?php
// Network Troubleshooting Tool for BullsCorp Payroll
// This tool helps diagnose network connectivity issues

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Troubleshooting - BullsCorp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .code-block {
            background: #1a1a1a;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg p-6 mb-8">
                <h1 class="text-3xl font-bold mb-2">
                    <i class="fas fa-network-wired mr-3"></i>
                    Network Troubleshooting Tool
                </h1>
                <p class="text-blue-100">Diagnose network connectivity issues for LAN access</p>
            </div>

            <!-- Server Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-server mr-2 text-blue-600"></i>
                    Server Information
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-700 mb-2">Current Request</h3>
                        <div class="space-y-1 text-sm">
                            <p><strong>Host:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'N/A'); ?></p>
                            <p><strong>Server IP:</strong> <?php echo htmlspecialchars($_SERVER['SERVER_ADDR'] ?? 'N/A'); ?></p>
                            <p><strong>Client IP:</strong> <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'N/A'); ?></p>
                            <p><strong>Port:</strong> <?php echo htmlspecialchars($_SERVER['SERVER_PORT'] ?? 'N/A'); ?></p>
                            <p><strong>Protocol:</strong> <?php echo htmlspecialchars($_SERVER['SERVER_PROTOCOL'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-700 mb-2">PHP Configuration</h3>
                        <div class="space-y-1 text-sm">
                            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                            <p><strong>Server Software:</strong> <?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'N/A'); ?></p>
                            <p><strong>Document Root:</strong> <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A'); ?></p>
                            <p><strong>Script Name:</strong> <?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Network Diagnostics -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-stethoscope mr-2 text-green-600"></i>
                    Network Diagnostics
                </h2>
                
                <?php
                // Get network information
                $networkInfo = [];
                
                // Try to get IP configuration (Windows)
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $ipconfig = shell_exec('ipconfig 2>nul');
                    if ($ipconfig) {
                        $networkInfo['ipconfig'] = $ipconfig;
                    }
                    
                    // Get firewall status
                    $firewallStatus = shell_exec('netsh advfirewall show allprofiles state 2>nul');
                    if ($firewallStatus) {
                        $networkInfo['firewall'] = $firewallStatus;
                    }
                } else {
                    // Linux/Mac
                    $ifconfig = shell_exec('ifconfig 2>/dev/null || ip addr show 2>/dev/null');
                    if ($ifconfig) {
                        $networkInfo['ifconfig'] = $ifconfig;
                    }
                }
                
                // Check if we can determine the local IP
                $localIPs = [];
                if (isset($networkInfo['ipconfig'])) {
                    preg_match_all('/IPv4 Address[.\s]*:\s*([0-9.]+)/', $networkInfo['ipconfig'], $matches);
                    $localIPs = array_unique($matches[1]);
                } elseif (isset($networkInfo['ifconfig'])) {
                    preg_match_all('/inet (?:addr:)?([0-9.]+)/', $networkInfo['ifconfig'], $matches);
                    $localIPs = array_unique($matches[1]);
                }
                
                // Remove localhost
                $localIPs = array_filter($localIPs, function($ip) {
                    return $ip !== '127.0.0.1';
                });
                ?>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Local IP Addresses -->
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-blue-800 mb-3">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            Detected IP Addresses
                        </h3>
                        <?php if (!empty($localIPs)): ?>
                            <div class="space-y-2">
                                <?php foreach ($localIPs as $ip): ?>
                                    <div class="bg-white p-3 rounded border">
                                        <p class="font-mono text-sm"><?php echo htmlspecialchars($ip); ?></p>
                                        <div class="mt-2 space-y-1">
                                            <a href="http://<?php echo $ip; ?>:8080/public/login.php" 
                                               class="text-blue-600 hover:underline text-xs block" 
                                               target="_blank">
                                                http://<?php echo $ip; ?>:8080/public/login.php
                                            </a>
                                            <a href="http://<?php echo $ip; ?>:8080/tools/network_troubleshoot.php" 
                                               class="text-green-600 hover:underline text-xs block" 
                                               target="_blank">
                                                Test this IP
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-red-600">Could not detect local IP addresses</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Connection Test -->
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-green-800 mb-3">
                            <i class="fas fa-plug mr-2"></i>
                            Connection Test
                        </h3>
                        <div class="space-y-3">
                            <div class="bg-white p-3 rounded border">
                                <p class="text-sm font-medium">Current Connection:</p>
                                <p class="font-mono text-xs text-green-600">
                                    ✓ Successfully connected from <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?>
                                </p>
                            </div>
                            
                            <div class="bg-white p-3 rounded border">
                                <p class="text-sm font-medium">Server Status:</p>
                                <p class="font-mono text-xs text-green-600">
                                    ✓ PHP server is running on port <?php echo htmlspecialchars($_SERVER['SERVER_PORT']); ?>
                                </p>
                            </div>
                            
                            <div class="bg-white p-3 rounded border">
                                <p class="text-sm font-medium">Router Status:</p>
                                <p class="font-mono text-xs text-green-600">
                                    ✓ Router is working (you can see this page)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Troubleshooting Steps -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-tools mr-2 text-orange-600"></i>
                    Troubleshooting Steps
                </h2>
                
                <div class="space-y-4">
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="font-semibold text-blue-800">1. Use the LAN Server Script</h3>
                        <p class="text-gray-600 mb-2">Use the new LAN-optimized server scripts:</p>
                        <div class="code-block text-xs">
# Windows Command Prompt:
scripts\start_lan_server.bat

# PowerShell (Recommended):
PowerShell -ExecutionPolicy Bypass -File scripts\start_lan_server.ps1
                        </div>
                    </div>
                    
                    <div class="border-l-4 border-green-500 pl-4">
                        <h3 class="font-semibold text-green-800">2. Check Windows Firewall</h3>
                        <p class="text-gray-600 mb-2">Temporarily disable Windows Firewall or add an exception:</p>
                        <div class="code-block text-xs">
# Add firewall rule (Run as Administrator):
netsh advfirewall firewall add rule name="PHP Server" dir=in action=allow protocol=TCP localport=8080

# Or temporarily disable firewall:
netsh advfirewall set allprofiles state off
                        </div>
                    </div>
                    
                    <div class="border-l-4 border-yellow-500 pl-4">
                        <h3 class="font-semibold text-yellow-800">3. Verify Network Configuration</h3>
                        <p class="text-gray-600 mb-2">Make sure devices are on the same network:</p>
                        <div class="code-block text-xs">
# Check IP configuration:
ipconfig

# Test connectivity from other device:
ping <?php echo !empty($localIPs) ? reset($localIPs) : 'YOUR_SERVER_IP'; ?>

# Test port connectivity:
telnet <?php echo !empty($localIPs) ? reset($localIPs) : 'YOUR_SERVER_IP'; ?> 8080
                        </div>
                    </div>
                    
                    <div class="border-l-4 border-red-500 pl-4">
                        <h3 class="font-semibold text-red-800">4. Check Antivirus/Security Software</h3>
                        <p class="text-gray-600">Some antivirus software may block incoming connections. Temporarily disable or add an exception for PHP.exe and port 8080.</p>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <?php if (isset($networkInfo['ipconfig']) || isset($networkInfo['ifconfig'])): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-info-circle mr-2 text-purple-600"></i>
                    System Network Information
                </h2>
                
                <?php if (isset($networkInfo['ipconfig'])): ?>
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-700 mb-2">IP Configuration:</h3>
                    <div class="code-block text-xs">
<?php echo htmlspecialchars($networkInfo['ipconfig']); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($networkInfo['firewall'])): ?>
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Firewall Status:</h3>
                    <div class="code-block text-xs">
<?php echo htmlspecialchars($networkInfo['firewall']); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($networkInfo['ifconfig'])): ?>
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Network Interfaces:</h3>
                    <div class="code-block text-xs">
<?php echo htmlspecialchars($networkInfo['ifconfig']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // Add click-to-copy functionality
        document.querySelectorAll('.code-block').forEach(function(block) {
            block.style.cursor = 'pointer';
            block.title = 'Click to copy';
            block.addEventListener('click', function() {
                navigator.clipboard.writeText(this.textContent).then(function() {
                    // Show feedback
                    const original = block.style.backgroundColor;
                    block.style.backgroundColor = '#22c55e';
                    setTimeout(function() {
                        block.style.backgroundColor = original;
                    }, 200);
                });
            });
        });
    </script>
</body>
</html>