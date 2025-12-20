#!/usr/bin/env bash
# Setup Python virtual environment and install required packages (Linux/macOS)
# Usage: run from the teacher folder: ./setup_env.sh
set -e
VENV_DIR="$(pwd)/venv"

command -v python3 >/dev/null 2>&1 || { echo "python3 not found. Please install Python 3.8+."; exit 1; }

python3 -m venv "$VENV_DIR"
source "$VENV_DIR/bin/activate"
python -m pip install --upgrade pip
pip install pandas openpyxl

echo "Setup complete. Activate virtualenv with: source venv/bin/activate"
echo "Then run: python process_excel_local.py input.xlsx"
