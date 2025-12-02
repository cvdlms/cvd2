"""
run_local_server.py

All-in-one launcher for teachers. This script:
1. Checks for required Python packages and installs them if needed
2. Starts the local Socket.IO server for PowerPoint remote control
3. Detects the local IP and generates a QR code for the phone to scan
4. Opens the browser on the PC automatically
5. Displays setup instructions for the teacher

Usage: python run_local_server.py
       or double-click run_local_server.bat
"""

import os
import sys
import subprocess
import time
import socket
import webbrowser
import logging
from pathlib import Path

logging.basicConfig(level=logging.INFO, format='[%(asctime)s] %(levelname)s: %(message)s')
logger = logging.getLogger(__name__)

REQUIRED_PACKAGES = [
    'flask',
    'flask-socketio',
    'python-socketio',
    'python-engineio',
    'eventlet',
    'pynput',
    'qrcode',
    'Pillow',
]

def check_and_install_packages():
    """Check if required packages are installed, install if needed."""
    logger.info('Checking Python packages...')
    
    missing = []
    for package in REQUIRED_PACKAGES:
        try:
            __import__(package.replace('-', '_'))
        except ImportError:
            missing.append(package)
    
    if missing:
        logger.info('Installing missing packages: %s', ', '.join(missing))
        try:
            subprocess.check_call([sys.executable, '-m', 'pip', 'install'] + missing)
            logger.info('Packages installed successfully')
        except subprocess.CalledProcessError as e:
            logger.error('Failed to install packages: %s', e)
            return False
    else:
        logger.info('All packages already installed')
    
    return True

def get_local_ip():
    """Get the local IP address reachable on LAN."""
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(('8.8.8.8', 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        try:
            return socket.gethostbyname(socket.gethostname())
        except Exception:
            return '127.0.0.1'

def generate_qr_code(url, filename='qr_code.png'):
    """Generate a QR code image for the URL."""
    try:
        import qrcode
        qr = qrcode.QRCode(version=1, box_size=10, border=5)
        qr.add_data(url)
        qr.make(fit=True)
        img = qr.make_image(fill_color='black', back_color='white')
        img.save(filename)
        logger.info('QR code saved to %s', filename)
        return True
    except Exception as e:
        logger.warning('Could not generate QR code: %s', e)
        return False

def main():
    logger.info('='*70)
    logger.info('PowerPoint Remote Control - Teacher Local Server')
    logger.info('='*70)
    
    # Step 1: Install packages
    if not check_and_install_packages():
        logger.error('Failed to install packages. Please try manually:')
        logger.error('pip install %s', ' '.join(REQUIRED_PACKAGES))
        return 1
    
    # Step 2: Get local IP
    local_ip = get_local_ip()
    logger.info('Local IP: %s', local_ip)
    
    # Step 3: Prepare URLs
    token = 'socketio123'
    port = 5000
    
    pc_url = f'http://localhost:{port}/?token={token}'
    phone_url = f'http://{local_ip}:{port}/?token={token}'
    
    logger.info('')
    logger.info('='*70)
    logger.info('URLS:')
    logger.info('  PC (this computer):  %s', pc_url)
    logger.info('  Phone (on same WiFi): %s', phone_url)
    logger.info('='*70)
    logger.info('')
    
    # Step 4: Generate QR code
    qr_path = generate_qr_code(phone_url)
    if qr_path:
        logger.info('QR code for phone: qr_code.png')
    
    # Step 5: Start server in background (non-blocking) by invoking socketio_server.py
    logger.info('Starting PowerPoint Remote Control server...')
    logger.info('The server will listen on http://0.0.0.0:%d', port)
    
    # Find socketio_server.py in the same directory
    socketio_script = Path(__file__).parent / 'socketio_server.py'
    if not socketio_script.exists():
        logger.error('socketio_server.py not found in %s', Path(__file__).parent)
        return 1
    
    # Open browser on PC (localhost)
    logger.info('Opening browser on localhost...')
    time.sleep(1)
    webbrowser.open(pc_url)
    
    logger.info('')
    logger.info('='*70)
    logger.info('SETUP COMPLETE! Now:')
    logger.info('  1. On your PHONE: Open the camera app or QR scanner')
    logger.info('  2. Scan the QR code printed by this program (qr_code.png)')
    logger.info('     OR open this URL: %s', phone_url)
    logger.info('  3. Allow notifications/permissions if asked')
    logger.info('  4. Use the touchpad and buttons to control PowerPoint')
    logger.info('='*70)
    logger.info('')
    
    logger.info('Server is running. Press Ctrl+C to stop.')
    logger.info('Logs will appear below:')
    logger.info('')
    
    # Step 6: Run the socketio server in the foreground
    import subprocess
    try:
        subprocess.run(
            [sys.executable, str(socketio_script), '--host', '0.0.0.0', '--port', str(port), '--token', token],
            check=False
        )
    except KeyboardInterrupt:
        logger.info('Server stopped by user')
        return 0
    except Exception as e:
        logger.error('Server error: %s', e)
        return 1

if __name__ == '__main__':
    sys.exit(main())
