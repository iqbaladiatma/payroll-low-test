@echo off
title Try All Solutions - BullsCorp LAN Access
color 0C

echo.
echo ===============================================================================
echo                    TRYING ALL POSSIBLE SOLUTIONS
echo ===============================================================================
echo.
echo This script will try multiple approaches to fix LAN access issues.
echo.

REM Check admin privileges
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ⚠ WARNING: Not running as Administrator!
    echo Some solutions may not work properly.
    echo.
    echo Please right-click and "Run as Administrator" for best results.
    echo.
    set /p continue="Continue anyway? (Y/N): "
    if /i not "%continue%"=="Y" exit /b 1
)

echo [SOLUTION 1] Complete Firewall Disable
echo.
echo Completely disabling Windows Firewall...
netsh advfirewall set allprofiles state off
if %errorlevel% equ 0 (
    echo ✓ Windows Firewall disabled
) else (
    echo ✗ Failed to disable firewall
)
echo.

echo [SOLUTION 2] Add Comprehensive Firewall Rules
echo.
echo Adding firewall rules for multiple ports...
for %%p in (8080 8081 8082 3000 80) do (
    netsh advfirewall firewall add rule name="BullsCorp-Port-%%p" dir=in action=allow protocol=TCP localport=%%p >nul 2>&1
    echo Added rule for port %%p
)
echo.

echo [SOLUTION 3] PHP Executable Permission
echo.
for /f "delims=" %%i in ('where php 2^>nul') do (
    netsh advfirewall firewall add rule name="BullsCorp-PHP-EXE" dir=in action=allow program="%%i" >nul 2>&1
    echo ✓ Added rule for PHP: %%i
    goto :php_done
)
echo ⚠ PHP executable not found in PATH
:php_done
echo.

echo [SOLUTION 4] Network Discovery Enable
echo.
echo Enabling network discovery...
netsh advfirewall firewall set rule group="Network Discovery" new enable=Yes >nul 2>&1
netsh advfirewall firewall set rule group="File and Printer Sharing" new enable=Yes >nul 2>&1
echo ✓ Network discovery enabled
echo.

echo [SOLUTION 5] Multiple Port Server Start
echo.
echo We'll try starting servers on multiple ports...
echo.

REM Get primary IP
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address" ^| findstr /v "127.0.0.1"') do (
    set "primary_ip=%%a"
    set primary_ip=!primary_ip: =!
    goto :ip_found
)
:ip_found

echo Primary IP detected: %primary_ip%
echo.

echo Starting servers on multiple ports...
echo.
echo Port 8080: http://%primary_ip%:8080/public/login.php
echo Port 8081: http://%primary_ip%:8081/public/login.php  
echo Port 8082: http://%primary_ip%:8082/public/login.php
echo Port 3000: http://%primary_ip%:3000/public/login.php
echo.

echo Choose which port to start:
echo 1. Port 8080 (default)
echo 2. Port 8081
echo 3. Port 8082  
echo 4. Port 3000
echo 5. Try all ports (will start multiple servers)
echo.
set /p port_choice="Enter choice (1-5): "

if "%port_choice%"=="1" (
    set server_port=8080
    goto :start_single
)
if "%port_choice%"=="2" (
    set server_port=8081
    goto :start_single
)
if "%port_choice%"=="3" (
    set server_port=8082
    goto :start_single
)
if "%port_choice%"=="4" (
    set server_port=3000
    goto :start_single
)
if "%port_choice%"=="5" (
    goto :start_multiple
)

REM Default to 8080
set server_port=8080

:start_single
echo.
echo ===============================================================================
echo Starting server on port %server_port%...
echo.
echo Access from other devices:
echo http://%primary_ip%:%server_port%/public/login.php
echo.
echo If this doesn't work, try these troubleshooting steps:
echo.
echo 1. On the OTHER device, try:
echo    ping %primary_ip%
echo    telnet %primary_ip% %server_port%
echo.
echo 2. Check if both devices are on the same network:
echo    - Same WiFi network
echo    - Not on guest network
echo    - Same subnet (first 3 numbers of IP should match)
echo.
echo 3. Try from other device browser:
echo    http://%primary_ip%:%server_port%/tools/network_troubleshoot.php
echo.
echo 4. If still fails, the issue might be:
echo    - Router blocking inter-device communication
echo    - Corporate network with device isolation
echo    - ISP blocking local server ports
echo    - Antivirus on either device
echo.
echo Press Ctrl+C to stop server
echo ===============================================================================
echo.

php -S 0.0.0.0:%server_port% server.php
goto :end

:start_multiple
echo.
echo ===============================================================================
echo Starting multiple servers...
echo.
echo This will open multiple command windows.
echo Try each URL from other devices:
echo.
echo http://%primary_ip%:8080/public/login.php
echo http://%primary_ip%:8081/public/login.php
echo http://%primary_ip%:8082/public/login.php
echo http://%primary_ip%:3000/public/login.php
echo.
echo Press any key to start...
pause >nul

start "BullsCorp-8080" cmd /k "php -S 0.0.0.0:8080 server.php"
timeout /t 2 >nul
start "BullsCorp-8081" cmd /k "php -S 0.0.0.0:8081 server.php"  
timeout /t 2 >nul
start "BullsCorp-8082" cmd /k "php -S 0.0.0.0:8082 server.php"
timeout /t 2 >nul
start "BullsCorp-3000" cmd /k "php -S 0.0.0.0:3000 server.php"

echo.
echo ✓ Multiple servers started in separate windows
echo.
echo Try accessing from other devices using the URLs shown above.
echo Close the individual server windows when done.

:end
echo.
echo ===============================================================================
echo.
echo FINAL TROUBLESHOOTING TIPS:
echo.
echo If NONE of the above worked, the issue is likely:
echo.
echo 1. NETWORK ISOLATION:
echo    - Router has AP isolation enabled
echo    - Corporate network blocks device-to-device communication
echo    - Guest network restrictions
echo.
echo 2. ISP/ROUTER BLOCKING:
echo    - Some ISPs block server ports
echo    - Router firewall blocking internal traffic
echo.
echo 3. ALTERNATIVE SOLUTIONS:
echo    - Use mobile hotspot instead of WiFi
echo    - Connect both devices via ethernet cable
echo    - Use VPN or tunneling software
echo    - Use cloud-based development server
echo.
echo 4. LAST RESORT - DIFFERENT APPROACH:
echo    - Install XAMPP/WAMP instead of PHP built-in server
echo    - Use Docker with port mapping
echo    - Set up proper Apache/Nginx server
echo.
echo ===============================================================================

setlocal enabledelayedexpansion
pause