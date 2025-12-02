@echo off
REM PowerPoint Remote Control - Teacher One-Click Launcher
REM Just double-click this file to start the local server!

echo.
echo ========================================
echo PowerPoint Remote Control - Teacher
echo ========================================
echo.

python run_local_server.py

if %errorlevel% neq 0 (
  echo.
  echo ERROR: Server failed to start.
  echo Make sure Python 3.8+ is installed and in PATH.
  echo.
  echo To fix, try:
  echo   1. Install Python from https://www.python.org
  echo   2. Make sure "Add Python to PATH" is checked during installation
  echo   3. Restart your computer
  echo   4. Try again
  echo.
)

pause
