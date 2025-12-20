@echo off
REM Local Excel processor - Windows helper
REM Usage: double-click and follow prompts, or run from command line:
REM    process_excel_local.bat input.xlsx [output.xlsx]

set PY=python
if exist "%~dp0\venv\Scripts\python.exe" (
  set PY=%~dp0\venv\Scripts\python.exe
)

if "%~1"=="" (
  echo Usage: %~nx0 input.xlsx [output.xlsx]
  goto :eof
)

%PY% "%~dp0process_excel_local.py" "%~1" "%~2"
pause
