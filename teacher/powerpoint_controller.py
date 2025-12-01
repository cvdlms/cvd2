#!/usr/bin/env python3
"""
PowerPoint Remote Control Handler
Listens to remote control commands and sends keyboard inputs to PowerPoint
"""

import os
import json
import time
import sys
import threading
import logging
from pathlib import Path

# Setup logging to file
log_file = Path(__file__).parent / "controller.log"
logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(log_file),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

try:
    import pyautogui
    logger.info("pyautogui imported successfully")
except ImportError as e:
    logger.error(f"pyautogui not installed: {e}")
    print("ERROR: pyautogui not installed. Install with: pip install pyautogui")
    sys.exit(1)

# Configuration
DATA_DIR = Path(__file__).parent.parent / "data" / "remote_control"
POLL_INTERVAL = 0.5  # Check for commands every 500ms

# Key mapping
COMMAND_KEYS = {
    'start_slideshow': 'f5',
    'stop_slideshow': 'esc',
    'next_slide': ['space', 'right'],
    'prev_slide': 'left',
    'mouse_move': 'move',
    'mouse_click': 'click',
}

def send_key_command(command_type, payload=None):
    """Send keyboard command to PowerPoint"""
    try:
        if command_type == 'start_slideshow':
            logger.info(f"Sending: F5 (Start slideshow)")
            pyautogui.press('f5')
            time.sleep(0.5)
            
        elif command_type == 'stop_slideshow':
            logger.info(f"Sending: ESC (Stop slideshow)")
            pyautogui.press('esc')
            time.sleep(0.3)
            
        elif command_type == 'next_slide':
            logger.info(f"Sending: SPACE (Next slide)")
            pyautogui.press('space')
            time.sleep(0.3)
            
        elif command_type == 'prev_slide':
            logger.info(f"Sending: LEFT ARROW (Previous slide)")
            pyautogui.press('left')
            time.sleep(0.3)
            
        elif command_type == 'mouse_move' and payload:
            # Payload should be: {x: 0-1, y: 0-1} normalized coordinates
            x = payload.get('x', 0.5)
            y = payload.get('y', 0.5)
            # Convert normalized coordinates to screen coordinates
            screen_width, screen_height = pyautogui.size()
            screen_x = int(x * screen_width)
            screen_y = int(y * screen_height)
            logger.debug(f"Moving mouse to: ({screen_x}, {screen_y})")
            pyautogui.moveTo(screen_x, screen_y, duration=0.1)
            
        elif command_type == 'mouse_click' and payload:
            button = payload.get('button', 'left')
            logger.info(f"Mouse click: {button}")
            pyautogui.click(button=button)
            time.sleep(0.2)
        else:
            logger.warning(f"Unknown command type: {command_type}")
            
    except Exception as e:
        logger.error(f"Failed to send command '{command_type}': {e}", exc_info=True)

def process_commands(session_id):
    """Read and process commands for a session"""
    commands_file = DATA_DIR / f"{session_id}_commands.json"
    
    if not commands_file.exists():
        return
    
    try:
        with open(commands_file, 'r') as f:
            commands = json.load(f)
        
        if not commands:
            return
        
        logger.info(f"Processing {len(commands)} command(s) for session {session_id}")
        
        for cmd in commands:
            cmd_type = cmd.get('type')
            payload = cmd.get('payload')
            logger.debug(f"Command: {cmd_type}, Payload: {payload}")
            send_key_command(cmd_type, payload)
        
        # Clear commands after processing
        with open(commands_file, 'w') as f:
            json.dump([], f)
            
    except Exception as e:
        logger.error(f"Error processing commands for {session_id}: {e}", exc_info=True)

def find_active_sessions():
    """Find all active remote control sessions"""
    if not DATA_DIR.exists():
        return []
    
    sessions = []
    for f in DATA_DIR.glob("*_commands.json"):
        session_id = f.name.replace('_commands.json', '')
        sessions.append(session_id)
    
    return sessions

def monitor_commands():
    """Main loop to monitor and process commands"""
    logger.info("PowerPoint Remote Control Handler started")
    logger.info(f"Monitoring: {DATA_DIR}")
    logger.info("Listening for remote commands... (Ctrl+C to stop)")
    print("\n[+] PowerPoint Remote Control Handler started")
    print(f"[+] Monitoring: {DATA_DIR}")
    print("[+] Listening for remote commands... (Ctrl+C to stop)")
    print()
    
    # Give warning about focus
    time.sleep(1)
    logger.warning("IMPORTANT: Make sure PowerPoint window is in focus!")
    logger.warning("Commands will be sent to the active window")
    print("[!] IMPORTANT: Make sure PowerPoint window is in focus!")
    print("[!] Commands will be sent to the active window")
    print()
    
    last_sessions = set()
    
    try:
        while True:
            current_sessions = set(find_active_sessions())
            
            # Process commands for each session
            for session_id in current_sessions:
                process_commands(session_id)
            
            # Notify of new sessions
            new_sessions = current_sessions - last_sessions
            if new_sessions:
                logger.info(f"New sessions detected: {new_sessions}")
                print(f"[+] New sessions detected: {new_sessions}")
            
            last_sessions = current_sessions
            
            time.sleep(POLL_INTERVAL)
            
    except KeyboardInterrupt:
        logger.info("Shutting down...")
        print("\n[*] Shutting down...")
        sys.exit(0)
    except Exception as e:
        logger.critical(f"Unexpected error: {e}", exc_info=True)
        print(f"[ERROR] Unexpected error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    # Ensure data directory exists
    DATA_DIR.mkdir(parents=True, exist_ok=True)
    
    # Start monitoring
    monitor_commands()
