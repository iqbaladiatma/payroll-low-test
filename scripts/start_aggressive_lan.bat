@echo off
title BullsCorp Payroll Server (Aggressive LAN Mode)
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
echo                   Aggressive LAN Mode
echo.
echo ===============================================================================
echo  WARNING: This mode will modify firewall settings for maximum compatibility!
echo  Only use in isolated testing environments!
echo ===============================================================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo ⚠ WARNING: Not running as Administrator!
    echo Some firewall configurations may fail.
    echo.
    echo For best results, right-click and "Run as Administrator"
    echo.
    set /p continue="Continue anyway? (Y/N): "
    if /i not "%continue%"=="Y" exit /b 1
)

REM Check if PHP is installed
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH!
    echo Please install PHP and add it to your system PATH.
    echo.
    pause
    exit /b 1
)

echo.
echo [1] Configuring Windows Firewall (Aggressive Mode)...
echo.

REM Disable Windows Firewall temporarily
echo Temporarily disabling Windows Firewall...
netsh advfirewall set allprofiles state off >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ Windows Firewall disabled
) else (
    echo ⚠ Could not disable firewall (may need admin rights)
)

REM Add comprehensive firewall rules
echo Adding firewall rules...
netsh advfirewall firewall delete rule name="BullsCorp" >nul 2>&1

REM Add rules for port 8080
netsh advfirewall firewall add rule name="BullsCorp-8080-In" dir=in action=allow protocol=TCP localport=8080 >nul 2>&1
netsh advfirewall firewall add rule name="BullsCorp-8080-Out" dir=out action=allow protocol=TCP localport=8080 >nul 2>&1

REM Add rules for common alternative ports
netsh advfirewall firewall add rule name="BullsCorp-8081-In" dir=in action=allow protocol=TCP localport=8081 >nul 2>&1
netsh advfirewall firewall add rule name="BullsCorp-8082-In" dir=in action=allow protocol=TCP localport=8082 >nul 2>&1

REM Add rule for PHP executable
for /f "delims=" %%i in ('where php 2^>nul') do (
    netsh advfirewall firewall add rule name="BullsCorp-PHP" dir=in action=allow program="%%i" >nul 2>&1
    goto :php_found
)
:php_found

echo ✓ Firewall rules configured

echo.
echo [2] Detecting network configuration...
echo.

REM Get all IP addresses
setlocal enabledelayedexpansion
set ip_count=0
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    set "ip=%%a"
    set ip=!ip: =!
    if not "!ip!"=="127.0.0.1" (
        set /a ip_count+=1
        set "ip_!ip_count!=!ip!"
        echo Found IP !ip_count!: !ip!
    )
)

if %ip_count% equ 0 (
    echo ERROR: No valid IP addresses found!
    pause
    exit /b 1
)

REM Use the first non-localhost IP
set main_ip=!ip_1!

echo.
echo Primary IP selected: %main_ip%

echo.
echo [3] Starting server with maximum compatibility...
echo.

echo Server Configuration:
echo - Binding to: 0.0.0.0:8080 (all interfaces)
echo - Primary IP: %main_ip%
echo - Firewall: Configured for maximum access
echo - Document Root: %cd%
echo.

echo Access URLs:
echo - Local:  http://localhost:8080/public/login.php
echo - LAN:    http://%main_ip%:8080/public/login.php
echo - Test:   http://%main_ip%:8080/tools/network_troubleshoot.php
echo.

echo Default Credentials:
echo - Admin: admin / admin123
echo - User:  user / user123
echo.

echo Network Test Commands (run from other device):
echo - ping %main_ip%
echo - telnet %main_ip% 8080
echo - Browser: http://%main_ip%:8080/public/login.php
echo.

echo ===============================================================================
echo Starting PHP Development Server...
echo.
echo If connection still fails from other devices:
echo 1. Check antivirus software
echo 2. Verify both devices on same network
echo 3. Try different port: php -S 0.0.0.0:8081 server.php
echo 4. Check router configuration
echo.
echo Press Ctrl+C to stop server
echo ===============================================================================
echo.

REM Start PHP server
php -S 0.0.0.0:8080 server.php

echo.
echo Server stopped.
echo.

REM Restore firewall (optional)
set /p restore="Restore Windows Firewall? (Y/N): "
if /i "%restore%"=="Y" (
    echo Restoring Windows Firewall...
    netsh advfirewall set allprofiles state on >nul 2>&1
    echo ✓ Windows Firewall restored
)

pause