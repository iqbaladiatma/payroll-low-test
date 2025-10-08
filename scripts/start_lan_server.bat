@echo off
title BullsCorp Payroll Server (LAN Access Mode)
color 0A

echo.
echo  ██████╗ ██╗   ██╗██╗     ██╗     ███████╗ ██████╗ ██████╗ ██████╗ ██████╗ 
echo  ██╔══██╗██║   ██║██║     ██║     ██╔════╝██╔════╝██╔═══██╗██╔══██╗██╔══██╗
echo  ██████╔╝██║   ██║██║     ██║     ███████╗██║     ██║   ██║██████╔╝██████╔╝
echo  ██╔══██╗██║   ██║██║     ██║     ╚════██║██║     ██║   ██║██╔══██╗██╔═══╝ 
echo  ██████╔╝╚██████╔╝███████╗███████╗███████║╚██████╗╚██████╔╝██║  ██║██║     
echo  ╚═════╝  ╚═════╝ ╚══════╝╚══════╝╚══════╝ ╚═════╝ ╚═════╝ ╚═╝  ╚═╝╚═╝     
echo.
echo                    PAYROLL MANAGEMENT SYSTEM
echo                   LAN Access Mode
echo.
echo ===============================================================================
echo  WARNING: This application is intentionally vulnerable for penetration testing!
echo  Only use in isolated testing environments!
echo ===============================================================================
echo.

REM Check if PHP is installed
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH!
    echo Please install PHP and add it to your system PATH.
    echo.
    pause
    exit /b 1
)

echo Configuring Windows Firewall for LAN access...
echo.

REM Add firewall rule for PHP server (requires admin privileges)
echo Adding Windows Firewall rule for port 8080...
netsh advfirewall firewall delete rule name="BullsCorp PHP Server" >nul 2>&1
netsh advfirewall firewall add rule name="BullsCorp PHP Server" dir=in action=allow protocol=TCP localport=8080 >nul 2>&1

if %errorlevel% equ 0 (
    echo ✓ Firewall rule added successfully
) else (
    echo ⚠ Warning: Could not add firewall rule (may need admin privileges)
    echo   You may need to manually allow port 8080 in Windows Firewall
)

echo.

REM Get all IP addresses
echo Detecting network interfaces...
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    set "ip=%%a"
    set ip=!ip: =!
    echo Found IP: !ip!
)

REM Get the main IP address (usually the first one that's not 127.0.0.1)
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address" ^| findstr /v "127.0.0.1"') do (
    set "main_ip=%%a"
    set main_ip=!main_ip: =!
    goto :found_main_ip
)
:found_main_ip

if "%main_ip%"=="" (
    echo Warning: Could not detect main IP address, using 0.0.0.0
    set main_ip=0.0.0.0
)

echo.
echo Starting BullsCorp Payroll Server (LAN Access Mode)...
echo.
echo Server Information:
echo - Binding to: 0.0.0.0:8080 (all interfaces)
echo - Local Access:  http://localhost:8080
echo - LAN Access:    http://%main_ip%:8080
echo - Document Root: %cd%
echo.
echo Default Credentials:
echo - Admin: admin / admin123
echo - User:  user / user123
echo.
echo Available URLs for LAN access:
echo - Main App: http://%main_ip%:8080/
echo - Login: http://%main_ip%:8080/public/login.php
echo - Admin: http://%main_ip%:8080/admin/dashboard.php
echo - User: http://%main_ip%:8080/user/dashboard.php
echo.
echo Network Troubleshooting:
echo 1. Make sure other devices are on the same network
echo 2. Try disabling Windows Firewall temporarily if still not working
echo 3. Check if antivirus is blocking connections
echo 4. Verify IP address is correct: %main_ip%
echo.
echo Press Ctrl+C to stop the server
echo ===============================================================================
echo.

REM Start PHP development server binding to all interfaces
echo Starting server on all network interfaces (0.0.0.0:8080)...
echo Router: server.php
echo.

setlocal enabledelayedexpansion

php -S 0.0.0.0:8080 server.php

echo.
echo Server stopped.
echo.

REM Clean up firewall rule on exit
echo Cleaning up firewall rule...
netsh advfirewall firewall delete rule name="BullsCorp PHP Server" >nul 2>&1

pause