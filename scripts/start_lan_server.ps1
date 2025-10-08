# BullsCorp Payroll Server - LAN Access Mode (PowerShell)
# This script configures the server for LAN access with proper firewall rules

param(
    [int]$Port = 8080,
    [switch]$SkipFirewall
)

# Set console title and colors
$Host.UI.RawUI.WindowTitle = "BullsCorp Payroll Server (LAN Access Mode)"

Write-Host ""
Write-Host "  ██████╗ ██╗   ██╗██╗     ██╗     ███████╗ ██████╗ ██████╗ ██████╗ ██████╗ " -ForegroundColor Green
Write-Host "  ██╔══██╗██║   ██║██║     ██║     ██╔════╝██╔════╝██╔═══██╗██╔══██╗██╔══██╗" -ForegroundColor Green
Write-Host "  ██████╔╝██║   ██║██║     ██║     ███████╗██║     ██║   ██║██████╔╝██████╔╝" -ForegroundColor Green
Write-Host "  ██╔══██╗██║   ██║██║     ██║     ╚════██║██║     ██║   ██║██╔══██╗██╔═══╝ " -ForegroundColor Green
Write-Host "  ██████╔╝╚██████╔╝███████╗███████╗███████║╚██████╗╚██████╔╝██║  ██║██║     " -ForegroundColor Green
Write-Host "  ╚═════╝  ╚═════╝ ╚══════╝╚══════╝╚══════╝ ╚═════╝ ╚═════╝ ╚═╝  ╚═╝╚═╝     " -ForegroundColor Green
Write-Host ""
Write-Host "                    PAYROLL MANAGEMENT SYSTEM" -ForegroundColor Yellow
Write-Host "                   LAN Access Mode (PowerShell)" -ForegroundColor Yellow
Write-Host ""
Write-Host "===============================================================================" -ForegroundColor Red
Write-Host "  WARNING: This application is intentionally vulnerable for penetration testing!" -ForegroundColor Red
Write-Host "  Only use in isolated testing environments!" -ForegroundColor Red
Write-Host "===============================================================================" -ForegroundColor Red
Write-Host ""

# Check if PHP is installed
try {
    $phpVersion = php --version 2>$null
    if ($LASTEXITCODE -ne 0) {
        throw "PHP not found"
    }
    Write-Host "✓ PHP is installed" -ForegroundColor Green
} catch {
    Write-Host "✗ ERROR: PHP is not installed or not in PATH!" -ForegroundColor Red
    Write-Host "Please install PHP and add it to your system PATH." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Get network information
Write-Host "Detecting network configuration..." -ForegroundColor Cyan

$networkAdapters = Get-NetIPAddress -AddressFamily IPv4 | Where-Object { 
    $_.IPAddress -ne "127.0.0.1" -and $_.PrefixOrigin -eq "Dhcp" -or $_.PrefixOrigin -eq "Manual" 
}

$mainIP = $null
foreach ($adapter in $networkAdapters) {
    if ($adapter.IPAddress -match "^192\.168\.|^10\.|^172\.(1[6-9]|2[0-9]|3[0-1])\.") {
        $mainIP = $adapter.IPAddress
        break
    }
}

if (-not $mainIP) {
    $mainIP = $networkAdapters[0].IPAddress
}

Write-Host "Network interfaces found:" -ForegroundColor Yellow
foreach ($adapter in $networkAdapters) {
    $status = if ($adapter.IPAddress -eq $mainIP) { " (Primary)" } else { "" }
    Write-Host "  - $($adapter.IPAddress)$status" -ForegroundColor White
}

# Configure Windows Firewall
if (-not $SkipFirewall) {
    Write-Host ""
    Write-Host "Configuring Windows Firewall..." -ForegroundColor Cyan
    
    try {
        # Check if running as administrator
        $isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")
        
        if ($isAdmin) {
            # Remove existing rule
            Remove-NetFirewallRule -DisplayName "BullsCorp PHP Server" -ErrorAction SilentlyContinue
            
            # Add new rule
            New-NetFirewallRule -DisplayName "BullsCorp PHP Server" -Direction Inbound -Protocol TCP -LocalPort $Port -Action Allow | Out-Null
            Write-Host "✓ Firewall rule added successfully" -ForegroundColor Green
        } else {
            Write-Host "⚠ Warning: Not running as administrator" -ForegroundColor Yellow
            Write-Host "  Firewall rule cannot be added automatically" -ForegroundColor Yellow
            Write-Host "  You may need to manually allow port $Port in Windows Firewall" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "⚠ Warning: Could not configure firewall: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

# Display server information
Write-Host ""
Write-Host "Starting BullsCorp Payroll Server (LAN Access Mode)..." -ForegroundColor Green
Write-Host ""
Write-Host "Server Configuration:" -ForegroundColor Yellow
Write-Host "  - Binding to: 0.0.0.0:$Port (all interfaces)" -ForegroundColor White
Write-Host "  - Local Access:  http://localhost:$Port" -ForegroundColor White
Write-Host "  - LAN Access:    http://$mainIP`:$Port" -ForegroundColor White
Write-Host "  - Document Root: $(Get-Location)" -ForegroundColor White
Write-Host ""
Write-Host "Default Credentials:" -ForegroundColor Yellow
Write-Host "  - Admin: admin / admin123" -ForegroundColor White
Write-Host "  - User:  user / user123" -ForegroundColor White
Write-Host ""
Write-Host "Available URLs for LAN access:" -ForegroundColor Yellow
Write-Host "  - Main App: http://$mainIP`:$Port/" -ForegroundColor White
Write-Host "  - Login: http://$mainIP`:$Port/public/login.php" -ForegroundColor White
Write-Host "  - Admin: http://$mainIP`:$Port/admin/dashboard.php" -ForegroundColor White
Write-Host "  - User: http://$mainIP`:$Port/user/dashboard.php" -ForegroundColor White
Write-Host ""
Write-Host "Network Troubleshooting Tips:" -ForegroundColor Yellow
Write-Host "  1. Make sure other devices are on the same network/subnet" -ForegroundColor White
Write-Host "  2. Try disabling Windows Firewall temporarily if still not working" -ForegroundColor White
Write-Host "  3. Check if antivirus is blocking connections" -ForegroundColor White
Write-Host "  4. Verify the IP address is correct: $mainIP" -ForegroundColor White
Write-Host "  5. Try accessing from another device: http://$mainIP`:$Port" -ForegroundColor White
Write-Host ""
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Red
Write-Host "===============================================================================" -ForegroundColor Cyan
Write-Host ""

# Start the PHP server
Write-Host "Starting PHP development server..." -ForegroundColor Green
Write-Host "Binding to all network interfaces (0.0.0.0:$Port)" -ForegroundColor White
Write-Host "Using router: server.php" -ForegroundColor White
Write-Host ""

try {
    # Start PHP server
    php -S "0.0.0.0:$Port" server.php
} catch {
    Write-Host "Error starting server: $($_.Exception.Message)" -ForegroundColor Red
} finally {
    Write-Host ""
    Write-Host "Server stopped." -ForegroundColor Yellow
    
    # Clean up firewall rule
    if (-not $SkipFirewall) {
        try {
            $isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")
            if ($isAdmin) {
                Remove-NetFirewallRule -DisplayName "BullsCorp PHP Server" -ErrorAction SilentlyContinue
                Write-Host "✓ Firewall rule cleaned up" -ForegroundColor Green
            }
        } catch {
            Write-Host "Warning: Could not clean up firewall rule" -ForegroundColor Yellow
        }
    }
    
    Write-Host ""
    Read-Host "Press Enter to exit"
}