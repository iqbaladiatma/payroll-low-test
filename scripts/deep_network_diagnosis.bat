@echo off
title Deep Network Diagnosis - BullsCorp
color 0E

echo.
echo ===============================================================================
echo                    DEEP NETWORK DIAGNOSIS
echo ===============================================================================
echo.

echo [STEP 1] Basic System Information
echo.
echo Computer Name: %COMPUTERNAME%
echo User: %USERNAME%
echo Date/Time: %DATE% %TIME%
echo.

echo [STEP 2] Network Adapter Information
echo.
ipconfig /all | findstr /C:"Ethernet adapter" /C:"Wireless LAN adapter" /C:"IPv4 Address" /C:"Subnet Mask" /C:"Default Gateway" /C:"DHCP Enabled"
echo.

echo [STEP 3] All IP Addresses on this machine
echo.
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    set "ip=%%a"
    set ip=!ip: =!
    echo IP Found: !ip!
)
echo.

echo [STEP 4] Routing Table
echo.
route print | findstr /C:"0.0.0.0" /C:"192.168" /C:"10.0" /C:"172.16"
echo.

echo [STEP 5] Active Network Connections
echo.
netstat -an | findstr :8080
echo.
echo If no results above, port 8080 is not in use (good for server)
echo If results show LISTENING, server is running
echo.

echo [STEP 6] Windows Firewall Status
echo.
netsh advfirewall show allprofiles state
echo.

echo [STEP 7] Testing localhost connectivity
echo.
echo Testing if server can connect to itself...
timeout /t 2 >nul
curl -s -m 5 http://localhost:8080 >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ Localhost works - Server is running correctly
) else (
    echo ✗ Localhost failed - Server may not be running
    echo.
    echo Make sure to start server first:
    echo php -S 0.0.0.0:8080 server.php
)
echo.

echo [STEP 8] Network Connectivity Test
echo.
set /p target_ip="Enter the IP you want other devices to use (press Enter for auto-detect): "

if "%target_ip%"=="" (
    REM Auto-detect primary IP
    for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address" ^| findstr /v "127.0.0.1"') do (
        set "target_ip=%%a"
        set target_ip=!target_ip: =!
        goto :ip_found
    )
    :ip_found
)

echo.
echo Testing IP: %target_ip%
echo.

REM Test if we can connect to our own IP
ping -n 2 %target_ip% >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ Can ping own IP: %target_ip%
) else (
    echo ✗ Cannot ping own IP: %target_ip%
    echo This indicates a network configuration problem
)

echo.
echo [STEP 9] Firewall Rules Check
echo.
netsh advfirewall firewall show rule name=all | findstr /C:"BullsCorp" /C:"PHP" /C:"8080"
echo.

echo [STEP 10] Alternative Ports Test
echo.
echo Testing if other ports are accessible...
netstat -an | findstr /C:":8081" /C:":8082" /C:":3000" /C:":80"
echo.

echo [STEP 11] Network Isolation Check
echo.
echo Checking for network isolation (common in corporate/guest networks)...
arp -a | findstr %target_ip%
echo.

echo ===============================================================================
echo DIAGNOSIS COMPLETE
echo ===============================================================================
echo.
echo SUMMARY:
echo - Your primary IP appears to be: %target_ip%
echo - Other devices should try: http://%target_ip%:8080/public/login.php
echo.
echo NEXT STEPS TO TRY:
echo.
echo 1. COMPLETE FIREWALL DISABLE (most likely fix):
echo    netsh advfirewall set allprofiles state off
echo.
echo 2. TRY DIFFERENT PORT:
echo    php -S 0.0.0.0:8081 server.php
echo    Then access: http://%target_ip%:8081/public/login.php
echo.
echo 3. CHECK ANTIVIRUS:
echo    Temporarily disable antivirus software
echo.
echo 4. NETWORK TROUBLESHOOTING:
echo    - Ensure both devices on same WiFi/network
echo    - Check if guest network isolation is enabled
echo    - Try ethernet cable instead of WiFi
echo    - Restart router/switch
echo.
echo 5. ALTERNATIVE SOLUTION - Use different IP:
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    set "alt_ip=%%a"
    set alt_ip=!alt_ip: =!
    if not "!alt_ip!"=="%target_ip%" if not "!alt_ip!"=="127.0.0.1" (
        echo    Try: http://!alt_ip!:8080/public/login.php
    )
)
echo.
echo ===============================================================================

setlocal enabledelayedexpansion
pause