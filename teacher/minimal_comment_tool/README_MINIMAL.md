# Minimal Excel Comment Worker

This minimal package provides a tiny Python worker that adds comments to an Excel file using only `openpyxl`.

Files:
- `comment_worker.py`: Reads JSON from stdin with `input_file`, `output_file`, `comment_rules` and writes JSON to stdout with the result.
- `requirements-minimal.txt`: `openpyxl` only.

Usage (CLI - stdin JSON):

Example JSON (save as `input.json`):
```json
{
  "input_file": "C:/path/to/input.xlsx",
  "output_file": "C:/path/to/output_processed.xlsx",
  "comment_rules": [
    {"min": 0, "max": 3.5, "comment": "Chưa đạt..."},
    {"min": 3.5, "max": 5.0, "comment": "Chưa đạt..."},
    {"min": 5.0, "max": 6.5, "comment": "Có cố gắng..."}
  ]
}
```

Run (Windows PowerShell):
```powershell
Get-Content input.json | python comment_worker.py
```

Run (Linux/macOS):
```bash
cat input.json | python3 comment_worker.py
```

Output is JSON to stdout:
```json
{"success": true, "output_file": "...", "file_size": 0.17}
```

Install dependency (recommended in a venv):
```bash
python -m venv venv
source venv/bin/activate
pip install -r requirements-minimal.txt
```

This worker is suitable to embed into remote invocation flows where a small footprint is required.
