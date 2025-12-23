@echo off
REM Launch the minimal GUI (uses venv if present)
SETLOCAL
SET HERE=%~dp0
IF EXIST "%HERE%venv\Scripts\python.exe" (
  "%HERE%venv\Scripts\python.exe" "%HERE%gui_minimal.py"
) ELSE (
  python "%HERE%gui_minimal.py"
)
ENDLOCAL
pause
