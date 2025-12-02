# socketio_server.py
# Flask + SocketIO server for PowerPoint remote control
# Usage: python socketio_server.py --host 0.0.0.0 --port 5000 --token secret123

import argparse
import os
import logging
import socket
from pathlib import Path
from flask import Flask, send_from_directory, request, abort
from flask_socketio import SocketIO, emit, disconnect
from pynput.mouse import Controller as MouseController, Button
from pynput.keyboard import Controller as KeyboardController, Key
import time

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] %(levelname)s: %(message)s',
    handlers=[
        logging.FileHandler('socketio_server.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Suppress socket.io and engine.io logs
logging.getLogger('socketio').setLevel(logging.WARNING)
logging.getLogger('engineio').setLevel(logging.WARNING)

# Get absolute path to this script's directory
SCRIPT_DIR = Path(__file__).resolve().parent
STATIC_DIR = SCRIPT_DIR / 'static'

app = Flask(__name__, static_folder=str(STATIC_DIR))
app.config['SECRET_KEY'] = 'socketio-secret-key'
socketio = SocketIO(app, cors_allowed_origins="*", ping_timeout=60, ping_interval=25)

mouse = MouseController()
keyboard = KeyboardController()

def get_local_ip():
    """Get the local LAN IP address"""
    try:
        # Connect to a non-routable address to determine local IP
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        return '127.0.0.1'

# Get local IP for phone access
LOCAL_IP = get_local_ip()

# Command line arguments
parser = argparse.ArgumentParser()
parser.add_argument('--host', default='0.0.0.0', help='Server host')
parser.add_argument('--port', default=5000, type=int, help='Server port')
parser.add_argument('--token', default=os.environ.get('RC_TOKEN', 'socketio123'), help='Secret token for authentication')
args = parser.parse_args()

SECRET_TOKEN = args.token
logger.info(f"Server configured: host={args.host}, port={args.port}, token=***")
logger.info(f"🔗 Access from phone: http://{LOCAL_IP}:{args.port}/?token={SECRET_TOKEN}")

# Routes
@app.route('/')
def index():
    token = request.args.get('token', '')
    if SECRET_TOKEN and token != SECRET_TOKEN:
        logger.warning(f"Unauthorized access attempt from {request.remote_addr}")
        abort(401)
    logger.info(f"Serving socketio_client.html to {request.remote_addr}")
    return send_from_directory(str(STATIC_DIR), 'socketio_client.html')

@app.route('/api/server-info')
def server_info():
    """Return server info for QR code generation"""
    return {
        'host': LOCAL_IP,
        'port': args.port,
        'token': SECRET_TOKEN,
        'url': f"http://{LOCAL_IP}:{args.port}/?token={SECRET_TOKEN}"
    }
def static_files(p):
    return send_from_directory(str(STATIC_DIR), p)

# WebSocket events
@socketio.on('connect')
def handle_connect(auth):
    """Handle client connection with token auth"""
    token = None
    if isinstance(auth, dict):
        token = auth.get('token')
    
    if SECRET_TOKEN and token != SECRET_TOKEN:
        logger.warning(f"Unauthorized socket connection from {request.sid}")
        disconnect()
        return
    
    logger.info(f"Client connected: {request.sid}")
    emit('status', {'msg': 'Connected to PowerPoint remote control'})

@socketio.on('disconnect')
def handle_disconnect():
    """Handle client disconnection"""
    logger.info(f"Client disconnected: {request.sid}")

@socketio.on('move')
def on_move(data):
    """Handle relative mouse movement"""
    try:
        dx = int(data.get('dx', 0))
        dy = int(data.get('dy', 0))
        if dx != 0 or dy != 0:
            mouse.move(dx, dy)
            logger.debug(f"Mouse move: dx={dx}, dy={dy}")
    except Exception as e:
        logger.error(f"Mouse move error: {e}")
        emit('error', {'msg': str(e)})

@socketio.on('click')
def on_click(data):
    """Handle mouse click"""
    try:
        btn = data.get('button', 'left')
        times = int(data.get('times', 1))
        btnobj = Button.left if btn == 'left' else Button.right
        
        for _ in range(times):
            mouse.click(btnobj)
        
        logger.debug(f"Mouse click: button={btn}, times={times}")
    except Exception as e:
        logger.error(f"Mouse click error: {e}")
        emit('error', {'msg': str(e)})

@socketio.on('scroll')
def on_scroll(data):
    """Handle mouse scroll"""
    try:
        dx = int(data.get('dx', 0))
        dy = int(data.get('dy', 0))
        mouse.scroll(dx, dy)
        logger.debug(f"Mouse scroll: dx={dx}, dy={dy}")
    except Exception as e:
        logger.error(f"Mouse scroll error: {e}")
        emit('error', {'msg': str(e)})

@socketio.on('key')
def on_key(data):
    """Handle keyboard commands"""
    try:
        k = data.get('key', '').upper()
        if not k:
            return
        
        special_keys = {
            'F5': Key.f5,
            'ESC': Key.esc,
            'ESCAPE': Key.esc,
            'LEFT': Key.left,
            'RIGHT': Key.right,
            'UP': Key.up,
            'DOWN': Key.down,
            'SPACE': Key.space,
            'ENTER': Key.enter,
            'TAB': Key.tab,
            'BACKSPACE': Key.backspace,
            'CTRL': Key.ctrl,
            'ALT': Key.alt,
            'SHIFT': Key.shift,
        }
        
        if k in special_keys:
            key_obj = special_keys[k]
            keyboard.press(key_obj)
            time.sleep(0.1)
            keyboard.release(key_obj)
            logger.info(f"Key pressed: {k}")
        else:
            keyboard.press(k)
            time.sleep(0.05)
            keyboard.release(k)
            logger.debug(f"Key pressed: {k}")
        
        emit('status', {'msg': f'Key {k} sent'})
    except Exception as e:
        logger.error(f"Keyboard command error: {e}")
        emit('error', {'msg': str(e)})

@socketio.on('powerpoint_command')
def on_powerpoint_command(data):
    """Handle PowerPoint-specific commands"""
    try:
        cmd = data.get('cmd', '').upper()
        
        command_map = {
            'START': 'F5',           # Start presentation (Trình chiếu)
            'STOP': 'ESC',           # Stop presentation (Dừng chiếu)
            'NEXT': 'SPACE',         # Next slide (Slide sau)
            'PREV': 'LEFT',          # Previous slide (Slide trước)
        }
        
        key_to_send = command_map.get(cmd)
        if key_to_send:
            special_keys = {
                'F5': Key.f5,
                'ESC': Key.esc,
                'SPACE': Key.space,
                'LEFT': Key.left,
            }
            
            key_obj = special_keys[key_to_send]
            keyboard.press(key_obj)
            time.sleep(0.15)
            keyboard.release(key_obj)
            logger.info(f"PowerPoint command: {cmd} -> {key_to_send}")
            emit('status', {'msg': f'PowerPoint: {cmd}'})
        else:
            logger.warning(f"Unknown PowerPoint command: {cmd}")
            emit('error', {'msg': f'Unknown command: {cmd}'})
    
    except Exception as e:
        logger.error(f"PowerPoint command error: {e}")
        emit('error', {'msg': str(e)})

if __name__ == '__main__':
    # Get local IP address
    try:
        # Get the IP address that can reach external network
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        local_ip = s.getsockname()[0]
        s.close()
    except Exception:
        try:
            local_ip = socket.gethostbyname(socket.gethostname())
        except Exception:
            local_ip = "127.0.0.1"
    
    logger.info("=" * 60)
    logger.info("PowerPoint Remote Control Server (Socket.IO)")
    logger.info("=" * 60)
    logger.info(f"PC Access:    http://localhost:{args.port}/?token={SECRET_TOKEN}")
    logger.info(f"Phone Access: http://{local_ip}:{args.port}/?token={SECRET_TOKEN}")
    logger.info("=" * 60)
    
    try:
        socketio.run(app, host=args.host, port=args.port, debug=False)
    except Exception as e:
        logger.error(f"Server error: {e}")
