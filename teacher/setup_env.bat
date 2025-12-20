@echo off
REM Setup Python virtual environment and install required packages (Windows)
REM Usage: double-click or run in PowerShell/CMD from the teacher folder

echo Setting up local Python virtual environment...
set VENV_DIR=%~dp0venv

REM Check Python
where python >nul 2>&1
if errorlevel 1 (
  echo Python not found in PATH. Please install Python 3.8+ and ensure it's added to PATH.
  pause
  exit /b 1
)

python -m venv "%VENV_DIR%"
if errorlevel 1 (
  echo Failed to create virtualenv.
  pause
  exit /b 1
)

echo Activating virtualenv and installing packages...
call "%VENV_DIR%\Scripts\activate.bat"
python -m pip install --upgrade pip
pip install pandas openpyxl

if errorlevel 1 (
  echo Some packages failed to install. See the output above.
  pause
  exit /b 1
)

echo Setup complete. To use the virtualenv, run:
	echo   call "%VENV_DIR%\Scripts\activate.bat"

echo Then run the local processor, e.g.:
	echo   python process_excel_local.py input.xlsx
pause
