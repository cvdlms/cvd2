#!/usr/bin/env python3
"""
Local CLI wrapper for Excel comment processing.
Usage:
    python process_excel_local.py input.xlsx [output.xlsx]

This script calls the existing `process_excel.py` logic so teachers can process files
locally without relying on the web host.
"""
import sys
import os
import json
from pathlib import Path

SCRIPT_DIR = Path(__file__).resolve().parent
PROCESSOR = SCRIPT_DIR / 'process_excel.py'

DEFAULT_RULES = [
    {"min": 0,   "max": 3.5, "comment": "Chưa đạt, ý thức học kém, cần cố gắng nhiều hơn."},
    {"min": 3.5, "max": 5.0, "comment": "Chưa đạt, chưa cố gắng học, cần nghiêm túc hơn."},
    {"min": 5.0, "max": 6.5, "comment": "Có cố gắng, cần phát huy thêm."},
    {"min": 6.5, "max": 8.0, "comment": "Siêng học, cần phát huy thêm."},
    {"min": 8.0, "max": 10.0, "comment": "Chăm chỉ học tập, rất tích cực phát biểu, gương mẫu cho học sinh."}
]


def run_local(input_path, output_path=None, rules=None):
    if not Path(input_path).exists():
        print(f"Input file not found: {input_path}")
        return 2

    if output_path is None:
        p = Path(input_path)
        output_path = str(p.parent / (p.stem + '_processed' + p.suffix))

    if rules is None:
        rules = DEFAULT_RULES

    # Import the processor module
    sys.path.insert(0, str(SCRIPT_DIR))
    try:
        import process_excel as pe
    except Exception as e:
        print("Failed to import process_excel.py:", e)
        return 3

    try:
        ok = pe.process_excel_with_pandas(input_path, output_path, rules)
        if ok:
            print(json.dumps({"success": True, "output_file": output_path}))
            return 0
        else:
            print(json.dumps({"success": False, "message": "Processing failed"}))
            return 4
    except Exception as ex:
        print(json.dumps({"success": False, "message": str(ex)}))
        return 5


if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("Usage: python process_excel_local.py input.xlsx [output.xlsx]")
        sys.exit(1)

    input_file = sys.argv[1]
    output_file = sys.argv[2] if len(sys.argv) >= 3 else None
    sys.exit(run_local(input_file, output_file))
