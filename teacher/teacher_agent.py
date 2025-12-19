"""
teacher_agent.py

A small Socket.IO client that connects to a central Socket.IO server and
executes local keyboard/mouse actions using pynput. This allows teachers to
run this agent on their PC and be reachable from any phone via the central server
(without port forwarding).

Usage:
  python teacher_agent.py --server http://your-server.example.com:5000 --token YOUR_TOKEN --name "Teacher Name"

Events listened:
 - 'powerpoint_command' with { cmd: 'START'|'STOP'|'NEXT'|'PREV' }
 - 'move' with { dx, dy }
 - 'click' with { button:'left'|'right', times }

When connected the agent will emit 'register_teacher' with a small metadata object
so the server or web UI can list available teachers.

Security: use a strong secret token for the central server and run the agent only
on trusted machines.
"""

import argparse
import logging
import time
import socketio
from pynput.mouse import Controller as MouseController, Button
from pynput.keyboard import Controller as KeyboardController, Key

logging.basicConfig(level=logging.INFO, format='[%(asctime)s] %(levelname)s: %(message)s')
logger = logging.getLogger('teacher_agent')

sio = socketio.Client(reconnection=True, reconnection_attempts=5, logger=False, engineio_logger=False)

mouse = MouseController()
keyboard = KeyboardController()

parser = argparse.ArgumentParser()
parser.add_argument('--server', required=True, help='Central server URL, e.g. http://example.com:5000')
parser.add_argument('--token', required=True, help='Secret token to authenticate with the server')
parser.add_argument('--name', default=None, help='Human-friendly name for this teacher machine')
args = parser.parse_args()

SERVER = args.server.rstrip('/')
TOKEN = args.token
NAME = args.name or socket.gethostname()

@ sio.event
def connect():
    logger.info('Connected to central server')
    # Register this teacher so UI can route commands here
    metadata = {
        'name': NAME,
        'host': socket.gethostname(),
    }
    try:
        sio.emit('register_teacher', metadata)
    except Exception as e:
        logger.warning('Failed to emit register_teacher: %s', e)

@ sio.event
def disconnect():
    logger.info('Disconnected from central server')

@ sio.on('powerpoint_command')
def on_powerpoint_command(data):
    cmd = (data.get('cmd') or '').upper()
    logger.info('Received PPT command: %s', cmd)
    mapping = {
        'START': Key.f5,
        'STOP': Key.esc,
        'NEXT': Key.space,
        'PREV': Key.left,
    }

    key = mapping.get(cmd)
    if key:
        try:
            keyboard.press(key)
            time.sleep(0.08)
            keyboard.release(key)
            logger.info('Sent key %s', key)
        except Exception as e:
            logger.error('Keyboard error: %s', e)

@ sio.on('move')
def on_move(data):
    try:
        dx = int(data.get('dx', 0))
        dy = int(data.get('dy', 0))
        mouse.move(dx, dy)
    except Exception as e:
        logger.error('Mouse move error: %s', e)

@ sio.on('click')
def on_click(data):
    try:
        btn = data.get('button', 'left')
        times = int(data.get('times', 1))
        btnobj = Button.left if btn == 'left' else Button.right
        for _ in range(times):
            mouse.click(btnobj)
    except Exception as e:
        logger.error('Mouse click error: %s', e)

# Optional: allow remote invocation of simple text typing (careful)
@ sio.on('type')
def on_type(data):
    text = data.get('text', '')
    if not text:
        return
    try:
        keyboard.type(text)
        logger.info('Typed text of length %d', len(text))
    except Exception as e:
        logger.error('Type error: %s', e)


def main():
    logger.info('Starting teacher agent (connecting to %s)', SERVER)
    try:
        sio.connect(SERVER, auth={'token': TOKEN})
    except Exception as e:
        logger.error('Connection failed: %s', e)
        return

    try:
        sio.wait()
    except KeyboardInterrupt:
        logger.info('Interrupted by user')
    finally:
        try:
            sio.disconnect()
        except Exception:
            pass

if __name__ == '__main__':
    main()
