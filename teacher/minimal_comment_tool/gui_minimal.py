#!/usr/bin/env python3
"""
Minimal GUI launcher for `comment_worker.py`.
This GUI avoids pandas and uses the openpyxl-only worker so teachers don't need heavy dependencies.
"""
import json
import subprocess
import threading
import shutil
import sys
from pathlib import Path
try:
    import tkinter as tk
    from tkinter import ttk, filedialog, messagebox
except Exception:
    print('Tkinter not available. Install a Python with tkinter support.')
    sys.exit(1)

HERE = Path(__file__).resolve().parent
WORKER = HERE / 'comment_worker.py'
REQ = HERE / 'requirements-minimal.txt'
VENV = HERE / 'venv'

def which_python_in_venv():
    if sys.platform.startswith('win'):
        p = VENV / 'Scripts' / 'python.exe'
    else:
        p = VENV / 'bin' / 'python'
    return str(p) if p.exists() else None

class App(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title('Vnedu Comment Tool - Tổ toán-tin')
        self.geometry('680x420')
        self.create_widgets()

    def create_widgets(self):
        f = ttk.Frame(self, padding=12)
        f.pack(fill='both', expand=True)

        ttk.Label(f, text='Input Excel (.xlsx):').grid(row=0, column=0, sticky='w')
        self.inp = ttk.Entry(f)
        self.inp.grid(row=0, column=1, sticky='we', padx=6)
        ttk.Button(f, text='Browse', command=self.browse_in).grid(row=0, column=2)

        ttk.Label(f, text='Output file (optional):').grid(row=1, column=0, sticky='w')
        self.out = ttk.Entry(f)
        self.out.grid(row=1, column=1, sticky='we', padx=6)
        ttk.Button(f, text='Browse', command=self.browse_out).grid(row=1, column=2)

        btnf = ttk.Frame(f)
        btnf.grid(row=2, column=0, columnspan=3, pady=8)
        ttk.Button(btnf, text='Create venv & Install', command=self.setup_env).grid(row=0, column=0, padx=6)
        ttk.Button(btnf, text='Run', command=self.run_worker).grid(row=0, column=1, padx=6)

        ttk.Label(f, text='Log:').grid(row=3, column=0, sticky='nw')
        self.log = tk.Text(f, height=15)
        self.log.grid(row=3, column=1, columnspan=2, sticky='nsew')
        f.columnconfigure(1, weight=1)
        f.rowconfigure(3, weight=1)

    def append(self, text):
        self.log.insert('end', text + '\n')
        self.log.see('end')

    def browse_in(self):
        p = filedialog.askopenfilename(filetypes=[('Excel','*.xlsx')])
        if p: self.inp.delete(0,'end'); self.inp.insert(0,p)

    def browse_out(self):
        p = filedialog.asksaveasfilename(defaultextension='.xlsx', filetypes=[('Excel','*.xlsx')])
        if p: self.out.delete(0,'end'); self.out.insert(0,p)

    def setup_env(self):
        def _work():
            self.append('Setting up venv...')
            py = shutil.which('python') or shutil.which('python3')
            if not py:
                messagebox.showerror('Error','Python not found in PATH')
                return
            if not VENV.exists():
                self.append('Creating virtual environment...')
                r = subprocess.call([py, '-m', 'venv', str(VENV)])
                if r != 0:
                    self.append('Failed to create venv')
                    return
            vpy = which_python_in_venv() or py
            self.append('Installing openpyxl...')
            cmd = [vpy, '-m', 'pip', 'install', '--upgrade', 'pip']
            subprocess.call(cmd)
            if REQ.exists():
                cmd = [vpy, '-m', 'pip', 'install', '-r', str(REQ)]
            else:
                cmd = [vpy, '-m', 'pip', 'install', 'openpyxl']
            subprocess.call(cmd)
            self.append('Done')
            messagebox.showinfo('OK','Environment ready')
        threading.Thread(target=_work, daemon=True).start()

    def run_worker(self):
        inp = self.inp.get().strip()
        outp = self.out.get().strip() or None
        if not inp:
            messagebox.showerror('Error','Select input file')
            return
        if not outp:
            p = Path(inp)
            outp = str(p.with_name(p.stem + '_processed' + p.suffix))

        def _work():
            self.append(f'Processing {inp} -> {outp}')
            vpy = which_python_in_venv() or shutil.which('python') or shutil.which('python3')
            if not vpy:
                self.append('Python not found. Run setup first.')
                messagebox.showerror('Error','Python not found')
                return
            payload = {'input_file': inp, 'output_file': outp, 'comment_rules': []}
            cmd = [vpy, str(WORKER)]
            try:
                proc = subprocess.Popen(cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
                stdout, stderr = proc.communicate(json.dumps(payload))
                if stdout:
                    self.append('STDOUT: ' + stdout.strip())
                if stderr:
                    self.append('STDERR: ' + stderr.strip())
                try:
                    data = json.loads(stdout.strip() or '{}')
                except Exception:
                    data = None
                if data and data.get('success'):
                    self.append('Success: ' + str(data.get('file_size')) + ' MB')
                    messagebox.showinfo('Done','File processed: ' + data.get('output_file',''))
                else:
                    self.append('Failed: ' + (data.get('message') if isinstance(data, dict) else (stderr or stdout)))
                    messagebox.showerror('Error','Processing failed')
            except Exception as e:
                self.append('Exception: ' + str(e))
                messagebox.showerror('Error', str(e))

        threading.Thread(target=_work, daemon=True).start()

if __name__ == '__main__':
    App().mainloop()
