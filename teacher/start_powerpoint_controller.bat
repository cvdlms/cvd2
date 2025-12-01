@echo off
REM PowerPoint Remote Control Handler - Batch Script
REM This script runs the Python PowerPoint controller

echo.
echo ============================================
echo PowerPoint Remote Control Handler
echo ============================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python from https://www.python.org
    echo Make sure to check "Add Python to PATH" during installation
    pause
    exit /b 1
)

REM Check if pyautogui is installed
python -c "import pyautogui" >nul 2>&1
if errorlevel 1 (
    echo Installing required package: pyautogui
    python -m pip install pyautogui
    if errorlevel 1 (
        echo ERROR: Failed to install pyautogui
        pause
        exit /b 1
    )
)

echo.
echo [+] Starting PowerPoint Remote Control Handler...
echo [+] Make sure PowerPoint is open and in focus!
echo [+] Press Ctrl+C to stop
echo.
timeout /t 2

REM If a bundled executable exists, run it (easier for teachers)
if exist "%~dp0dist\ppt_controller.exe" (
    echo Found bundled executable, running ppt_controller.exe...
    start "PowerPoint Controller" "%~dp0dist\ppt_controller.exe"
) else (
    REM Run the Python script (fallback)
    python "%~dp0powerpoint_controller.py"
)

pause
