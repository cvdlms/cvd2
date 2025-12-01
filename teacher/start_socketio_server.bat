@echo off
REM PowerPoint Remote Control Server - Socket.IO
REM This script starts the Socket.IO server for remote PowerPoint control

echo.
echo ============================================================
echo   PowerPoint Remote Control Server (Socket.IO)
echo ============================================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.8+ from https://www.python.org
    pause
    exit /b 1
)

REM Check if required packages are installed
echo Checking required packages...
python -c "import flask, flask_socketio, pynput" 2>nul
if %errorlevel% neq 0 (
    echo.
    echo Installing required packages...
    echo This may take a minute...
    echo.
    pip install -r requirements_socketio.txt
    if %errorlevel% neq 0 (
        echo ERROR: Failed to install packages
        echo Make sure pip is available and you have internet connection
        pause
        exit /b 1
    )
)

REM Run the server
echo.
echo Starting PowerPoint Remote Control Server...
echo.
echo Server will run on:
echo   Local:  http://localhost:5000/?token=socketio123
echo   Remote: http://^<PC_IP^>:5000/?token=socketio123
echo.
echo Press Ctrl+C to stop the server
echo.

python socketio_server.py --host 0.0.0.0 --port 5000 --token socketio123

if %errorlevel% neq 0 (
    echo.
    echo ERROR: Server failed to start
    pause
)

pause
