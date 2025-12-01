@echo off
REM PowerPoint Remote Control - AutoHotkey Version
REM This script is easier than Python and works better with PowerPoint on Windows

echo.
echo ============================================
echo PowerPoint Remote Control Handler (AutoHotkey)
echo ============================================
echo.

REM Check if AutoHotkey is installed
where /q AutoHotkey.exe
if errorlevel 1 (
    echo ERROR: AutoHotkey is not installed or not in PATH
    echo.
    echo Please install AutoHotkey from: https://www.autohotkey.com/
    echo 1. Download and install AutoHotkey v1.1
    echo 2. AutoHotkey should be added to PATH during installation
    echo 3. Run this script again
    echo.
    pause
    exit /b 1
)

echo [+] AutoHotkey found
echo [+] Starting PowerPoint Remote Control Handler...
echo [+] Make sure PowerPoint is open (or will be opened)
echo [+] Press Ctrl+Alt+Z in PowerPoint to stop
echo.

REM Run the AutoHotkey script
AutoHotkey.exe "%~dp0ppt_remote.ahk"

REM If we get here, the script was stopped
echo [*] PowerPoint Remote Control Handler stopped
pause
