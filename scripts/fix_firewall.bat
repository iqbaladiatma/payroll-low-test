@echo off
title Fix Windows Firewall for LAN Access - BullsCorp
color 0C

echo.
echo ===============================================================================
echo                    WINDOWS FIREWALL FIX TOOL
echo ===============================================================================
echo.
echo This tool will configure Windows Firewall to allow LAN access to the server.
echo.
echo WARNING: This will modify your firewall settings!
echo.
set /p confirm="Continue? (Y/N): "
if /i not "%confirm%"=="Y" goto :end

echo.
echo [1] Checking current firewall status...
echo.
netsh advfirewall show allprofiles state

echo.
echo [2] Removing old firewall rules...
echo.
netsh advfirewall firewall delete rule name="BullsCorp PHP Server" >nul 2>&1
netsh advfirewall firewall delete rule name="BullsCorp PHP Server Test" >nul 2>&1
netsh advfirewall firewall delete rule name="PHP Server" >nul 2>&1
netsh advfirewall firewall delete rule name="PHP Development Server" >nul 2>&1

echo [3] Adding new firewall rules...
echo.

REM Add inbound rule for port 8080
netsh advfirewall firewall add rule name="BullsCorp PHP Server - Port 8080" dir=in action=allow protocol=TCP localport=8080
if %errorlevel% equ 0 (
    echo ✓ Inbound rule for port 8080 added
) else (
    echo ✗ Failed to add inbound rule
)

REM Add outbound rule for port 8080
netsh advfirewall firewall add rule name="BullsCorp PHP Server - Port 8080 Out" dir=out action=allow protocol=TCP localport=8080
if %errorlevel% equ 0 (
    echo ✓ Outbound rule for port 8080 added
) else (
    echo ✗ Failed to add outbound rule
)

REM Add rule for PHP executable
for /f "delims=" %%i in ('where php 2^>nul') do (
    set "php_path=%%i"
    goto :found_php
)
:found_php

if defined php_path (
    netsh advfirewall firewall add rule name="BullsCorp PHP Executable" dir=in action=allow program="%php_path%"
    if %errorlevel% equ 0 (
        echo ✓ PHP executable rule added: %php_path%
    ) else (
        echo ✗ Failed to add PHP executable rule
    )
) else (
    echo ⚠ PHP executable not found in PATH
)

echo.
echo [4] Alternative: Temporarily disable firewall (NOT RECOMMENDED for production)
echo.
set /p disable_fw="Temporarily disable Windows Firewall? (Y/N): "
if /i "%disable_fw%"=="Y" (
    echo Disabling Windows Firewall...
    netsh advfirewall set allprofiles state off
    echo ✓ Windows Firewall disabled
    echo.
    echo IMPORTANT: Remember to re-enable firewall later:
    echo netsh advfirewall set allprofiles state on
)

echo.
echo [5] Testing firewall rules...
echo.
netsh advfirewall firewall show rule name="BullsCorp PHP Server - Port 8080"

echo.
echo ===============================================================================
echo Firewall configuration completed!
echo.
echo Next steps:
echo 1. Start the server: scripts\start_simple.bat
echo 2. Test from another device: http://YOUR_IP:8080/public/login.php
echo 3. If still not working, check antivirus software
echo.
echo To restore firewall rules later:
echo netsh advfirewall set allprofiles state on
echo ===============================================================================
echo.

:end
pause