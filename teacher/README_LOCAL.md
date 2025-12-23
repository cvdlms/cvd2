# Local Excel Comment Tool

This folder provides a simple local tool for teachers to process Excel files and add comments without requiring Python on the hosting server.

Files:
- `process_excel_local.py`: CLI wrapper that calls `process_excel.py` to process a local Excel file and outputs a processed file.
- `process_excel.py`: processor (uses `pandas` + `openpyxl`).
- `process_excel_local.bat`: Windows helper script to run the local tool (will use `venv` python if present).
- `requirements-local.txt`: Python packages required for local execution.

Quick start (Windows PowerShell):

1. Install Python 3.8+ if not already installed: https://www.python.org/downloads/
2. Open PowerShell in this folder (`cvd2\teacher`).
3. Create and activate a virtual environment (recommended):
```powershell
python -m venv venv
.\venv\Scripts\Activate.ps1
pip install --upgrade pip
pip install -r requirements-local.txt
```
4. Run the processor:
```powershell
python process_excel_local.py "C:\path\to\input.xlsx" "C:\path\to\output_processed.xlsx"
```
Or double-click `process_excel_local.bat` and provide the file name when prompted.

Quick start (Linux/macOS):
```bash
python3 -m venv venv
source venv/bin/activate
pip install --upgrade pip
pip install -r requirements-local.txt
python process_excel_local.py /path/to/input.xlsx /path/to/output_processed.xlsx
```

Notes:
- The script expects column L (12) to contain the score and writes comments to column M (13).
- If you run into import errors, ensure the active Python interpreter is the one with the installed packages (check `which python` / `where python`).
- Log output is written to `../logs/excel_processor.log` relative to the script.

A small GUI (Tkinter) wrapper is included: `gui_process_excel.py`.

GUI usage (after environment setup):

Windows PowerShell:
```powershell
.
python gui_process_excel.py
```

The GUI provides buttons to create the virtual environment, install requirements, select input/output files, and run processing. Logs appear in the interface and the detailed processor log is in `../logs/excel_processor.log`.
