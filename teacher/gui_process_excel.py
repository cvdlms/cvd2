#!/usr/bin/env python3
"""
Simple Tkinter GUI for teachers to process Excel files locally.
Features:
- Select input .xlsx file
- Choose output path
- One-click: ensure venv + install requirements, then process via `process_excel_local.py`
- Live log area showing stdout/stderr
"""
import os
import sys
import json
import subprocess
import threading
import shutil
from pathlib import Path
try:
    import tkinter as tk
    from tkinter import ttk, filedialog, messagebox
except Exception:
    print(json.dumps({'success': False, 'message': 'Tkinter not available on this Python installation.'}))
    sys.exit(1)

HERE = Path(__file__).resolve().parent
VENV_DIR = HERE / 'venv'
REQ_FILE = HERE / 'requirements-local.txt'
PROCESSOR = HERE / 'process_excel_local.py'

def which_python_in_venv():
    if sys.platform.startswith('win'):
        candidate = VENV_DIR / 'Scripts' / 'python.exe'
    else:
        candidate = VENV_DIR / 'bin' / 'python'
    return str(candidate) if candidate.exists() else None

class App(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title('CVD Excel Comment - Local Processor')
        self.geometry('700x480')
        self.create_widgets()
        self.input_path = None
        self.output_path = None

    def create_widgets(self):
        frm = ttk.Frame(self, padding=12)
        frm.pack(fill='both', expand=True)

        row = 0
        ttk.Label(frm, text='Input Excel file:').grid(row=row, column=0, sticky='w')
        self.input_entry = ttk.Entry(frm)
        self.input_entry.grid(row=row, column=1, sticky='we', padx=6)
        ttk.Button(frm, text='Browse', command=self.browse_input).grid(row=row, column=2)

        row += 1
        ttk.Label(frm, text='Output file (optional):').grid(row=row, column=0, sticky='w')
        self.output_entry = ttk.Entry(frm)
        self.output_entry.grid(row=row, column=1, sticky='we', padx=6)
        ttk.Button(frm, text='Browse', command=self.browse_output).grid(row=row, column=2)

        row += 1
        btn_frame = ttk.Frame(frm)
        btn_frame.grid(row=row, column=0, columnspan=3, pady=10)
        ttk.Button(btn_frame, text='Setup Environment', command=self.setup_env).grid(row=0, column=0, padx=6)
        ttk.Button(btn_frame, text='Process File', command=self.process_file).grid(row=0, column=1, padx=6)
        ttk.Button(btn_frame, text='Open Logs', command=self.open_logs).grid(row=0, column=2, padx=6)

        row += 1
        ttk.Label(frm, text='Log:').grid(row=row, column=0, sticky='nw')
        self.log_text = tk.Text(frm, wrap='none', height=20)
        self.log_text.grid(row=row, column=1, columnspan=2, sticky='nsew')

        frm.columnconfigure(1, weight=1)
        frm.rowconfigure(row, weight=1)

    def append_log(self, text):
        def _append():
            self.log_text.insert('end', text)
            self.log_text.see('end')
        self.after(0, _append)

    def browse_input(self):
        p = filedialog.askopenfilename(title='Select input Excel file', filetypes=[('Excel files','*.xlsx;*.xlsm;*.xltx;*.xltm')])
        if p:
            self.input_entry.delete(0, 'end')
            self.input_entry.insert(0, p)

    def browse_output(self):
        p = filedialog.asksaveasfilename(title='Choose output file', defaultextension='.xlsx', filetypes=[('Excel files','*.xlsx')])
        if p:
            self.output_entry.delete(0, 'end')
            self.output_entry.insert(0, p)

    def run_subprocess(self, cmd, capture_json=False):
        # Run command in a thread-safe way and stream output
        proc = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, stdin=subprocess.PIPE, text=True)
        stdout, stderr = proc.communicate()
        if stdout:
            self.append_log(stdout + '\n')
        if stderr:
            self.append_log(stderr + '\n')
        return proc.returncode, stdout, stderr

    def setup_env(self):
        def _work():
            self.append_log('Setting up virtual environment and installing requirements...\n')
            py = shutil.which('python') or shutil.which('python3')
            if not py:
                self.append_log('Python executable not found in PATH.\n')
                messagebox.showerror('Error', 'Python not found. Please install Python 3.8+ and try again.')
                return

            # create venv if missing
            if not VENV_DIR.exists():
                self.append_log(f'Creating venv at {VENV_DIR}...\n')
                r = subprocess.call([py, '-m', 'venv', str(VENV_DIR)])
                if r != 0:
                    self.append_log('Failed to create virtualenv.\n')
                    return

            # pip install requirements using venv python
            vpy = which_python_in_venv() or py
            cmd = [vpy, '-m', 'pip', 'install', '--upgrade', 'pip']
            self.append_log('Upgrading pip...\n')
            subprocess.call(cmd)
            if REQ_FILE.exists():
                cmd = [vpy, '-m', 'pip', 'install', '-r', str(REQ_FILE)]
            else:
                cmd = [vpy, '-m', 'pip', 'install', 'pandas', 'openpyxl']
            self.append_log('Installing requirements... this may take a few minutes...\n')
            r = subprocess.call(cmd)
            if r == 0:
                self.append_log('Requirements installed successfully.\n')
                messagebox.showinfo('OK', 'Environment ready.')
            else:
                self.append_log('Failed to install requirements. Check the log.\n')
                messagebox.showerror('Error', 'Failed to install requirements. See log for details.')

        threading.Thread(target=_work, daemon=True).start()

    def process_file(self):
        inp = self.input_entry.get().strip()
        outp = self.output_entry.get().strip() or None
        if not inp or not Path(inp).exists():
            messagebox.showerror('Error', 'Please select a valid input Excel file.')
            return

        def _work():
            self.append_log(f'Starting processing: {inp}\n')
            vpy = which_python_in_venv() or shutil.which('python') or shutil.which('python3')
            if not vpy:
                self.append_log('No Python found.\n')
                messagebox.showerror('Error', 'Python not found. Please run Setup Environment first.')
                return

            cmd = [vpy, str(PROCESSOR), inp]
            if outp:
                cmd.append(outp)

            self.append_log('Running: ' + ' '.join(cmd) + '\n')
            rc, stdout, stderr = self.run_subprocess(cmd)

            # Try parse stdout for JSON
            try:
                data = json.loads(stdout.strip() or '{}')
            except Exception:
                data = None

            if data and data.get('success'):
                msg = f"Success. Output: {data.get('output_file','')}."
                self.append_log(msg + '\n')
                messagebox.showinfo('Done', msg)
            else:
                msg = data.get('message') if isinstance(data, dict) else (stderr or stdout)
                self.append_log('Processing failed: ' + str(msg) + '\n')
                messagebox.showerror('Error', 'Processing failed: ' + str(msg))

        threading.Thread(target=_work, daemon=True).start()

    def open_logs(self):
        log_file = (HERE.parent / 'logs' / 'excel_processor.log')
        if log_file.exists():
            try:
                if sys.platform.startswith('win'):
                    os.startfile(str(log_file))
                else:
                    subprocess.call(['xdg-open', str(log_file)])
            except Exception:
                messagebox.showinfo('Log path', str(log_file))
        else:
            messagebox.showinfo('Log', 'Log file not found: ' + str(log_file))

if __name__ == '__main__':
    app = App()
    app.mainloop()
