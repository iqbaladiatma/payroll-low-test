@echo off
title Client Side Network Test - BullsCorp
color 0B

echo.
echo ===============================================================================
echo                    CLIENT SIDE NETWORK TEST
echo ===============================================================================
echo.
echo Run this script on the device that CANNOT access the server
echo.

set /p server_ip="Enter the server IP address (e.g., 192.192.2.80): "
if "%server_ip%"=="" (
    echo Error: No IP address provided
    goto :end
)

echo.
echo Testing connection to server: %server_ip%
echo.

echo [TEST 1] Basic Network Information
echo.
echo Client Computer: %COMPUTERNAME%
echo Client User: %USERNAME%
echo.
ipconfig | findstr /C:"IPv4 Address" /C:"Subnet Mask" /C:"Default Gateway"
echo.

echo [TEST 2] Network Connectivity
echo.
echo Testing ping to server...
ping -n 4 %server_ip%
if %errorlevel% equ 0 (
    echo ✓ PING SUCCESS - Basic network connectivity works
    set ping_ok=1
) else (
    echo ✗ PING FAILED - Network connectivity problem
    set ping_ok=0
    echo.
    echo Possible causes:
    echo - Wrong IP address
    echo - Devices on different networks
    echo - Server firewall blocking ping
    echo - Network isolation enabled
)
echo.

echo [TEST 3] Port Connectivity Tests
echo.
for %%p in (8080 8081 8082 3000) do (
    echo Testing port %%p...
    timeout /t 1 >nul
    
    REM Try telnet if available
    echo quit | telnet %server_ip% %%p 2>nul | findstr "Connected" >nul
    if !errorlevel! equ 0 (
        echo ✓ Port %%p is OPEN and accessible
        set port_%%p_ok=1
    ) else (
        echo ✗ Port %%p is CLOSED or blocked
        set port_%%p_ok=0
    )
)
echo.

echo [TEST 4] HTTP Connection Tests
echo.
REM Check if curl is available
curl --version >nul 2>&1
if %errorlevel% equ 0 (
    echo Testing HTTP connections with curl...
    for %%p in (8080 8081 8082 3000) do (
        echo Testing HTTP on port %%p...
        curl -s -m 5 http://%server_ip%:%%p/ >nul 2>&1
        if !errorlevel! equ 0 (
            echo ✓ HTTP port %%p works
        ) else (
            echo ✗ HTTP port %%p failed
        )
    )
) else (
    echo Curl not available, skipping HTTP tests
)
echo.

echo [TEST 5] DNS and Network Route
echo.
echo Testing DNS resolution...
nslookup %server_ip% >nul 2>&1
echo.
echo Network route to server:
tracert -h 5 %server_ip%
echo.

echo [TEST 6] Local Firewall Check
echo.
echo Checking local Windows Firewall...
netsh advfirewall show allprofiles state
echo.

echo [TEST 7] Browser Test URLs
echo.
echo Try these URLs in your browser:
echo.
for %%p in (8080 8081 8082 3000) do (
    echo Port %%p: http://%server_ip%:%%p/public/login.php
)
echo.
echo Network diagnostic: http://%server_ip%:8080/tools/network_troubleshoot.php
echo.

echo [TEST 8] Alternative Connection Methods
echo.
echo If direct connection fails, try these:
echo.
echo 1. Mobile Hotspot Test:
echo    - Connect both devices to mobile hotspot
echo    - Try connection again
echo.
echo 2. Ethernet Connection:
echo    - Connect both devices via ethernet cable/switch
echo    - Try connection again
echo.
echo 3. Different Network:
echo    - Try on different WiFi network
echo    - Check if current network has device isolation
echo.

echo ===============================================================================
echo TEST RESULTS SUMMARY
echo ===============================================================================
echo.
echo Server IP: %server_ip%
echo Ping Result: %ping_ok%
echo.
if %ping_ok% equ 1 (
    echo ✓ Basic connectivity works
    echo The problem is likely:
    echo - Server not running on expected port
    echo - Firewall blocking specific ports
    echo - Server binding to wrong interface
    echo.
    echo SOLUTIONS TO TRY:
    echo 1. Make sure server is running: php -S 0.0.0.0:8080 server.php
    echo 2. Disable server firewall completely
    echo 3. Try different ports (8081, 8082, 3000)
    echo 4. Check antivirus on server
) else (
    echo ✗ Basic connectivity failed
    echo The problem is likely:
    echo - Devices on different networks
    echo - Network isolation/AP isolation enabled
    echo - Router blocking inter-device communication
    echo - Wrong IP address
    echo.
    echo SOLUTIONS TO TRY:
    echo 1. Verify both devices on same WiFi network
    echo 2. Check router settings for AP isolation
    echo 3. Try mobile hotspot instead
    echo 4. Use ethernet connection
    echo 5. Verify correct IP address
)
echo.
echo ===============================================================================

:end
pause