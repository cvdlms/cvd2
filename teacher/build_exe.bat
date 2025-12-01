@echo off
REM Build a single-file executable for PowerPoint controller using PyInstaller

echo.
echo ============================================
echo Build ppt_controller.exe (PyInstaller)
echo ============================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python from https://www.python.org
    pause
    exit /b 1
)

REM Install build dependencies
echo Installing build dependencies...
python -m pip install --upgrade pip
python -m pip install pyinstaller pyautogui pywin32

if errorlevel 1 (
    echo ERROR: Failed to install dependencies
    pause
    exit /b 1
)

REM Build the executable
echo.
echo Building ppt_controller.exe...
echo (This may take a minute...)
echo.

REM Build with hidden imports for all dependencies
python -m PyInstaller ^
  --onefile ^
  --console ^
  --hidden-import=win32gui ^
  --hidden-import=win32con ^
  --hidden-import=pyautogui ^
  --hidden-import=PIL ^
  --hidden-import=logging ^
  --name ppt_controller ^
  powerpoint_controller.py

if errorlevel 1 (
    echo ERROR: PyInstaller build failed
    pause
    exit /b 1
)

if not exist dist\ppt_controller.exe (
    echo ERROR: Build completed but ppt_controller.exe was not created
    pause
    exit /b 1
)

echo.
echo ============================================
echo SUCCESS! File created: dist\ppt_controller.exe
echo ============================================
echo.
pause
