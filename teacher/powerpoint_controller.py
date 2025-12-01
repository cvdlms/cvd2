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
from pathlib import Path

try:
    import pyautogui
except ImportError:
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
            print(f"[*] Sending: F5 (Start slideshow)")
            pyautogui.press('f5')
            time.sleep(0.5)
            
        elif command_type == 'stop_slideshow':
            print(f"[*] Sending: ESC (Stop slideshow)")
            pyautogui.press('esc')
            time.sleep(0.3)
            
        elif command_type == 'next_slide':
            print(f"[*] Sending: SPACE (Next slide)")
            pyautogui.press('space')
            time.sleep(0.3)
            
        elif command_type == 'prev_slide':
            print(f"[*] Sending: LEFT ARROW (Previous slide)")
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
            print(f"[*] Moving mouse to: ({screen_x}, {screen_y})")
            pyautogui.moveTo(screen_x, screen_y, duration=0.1)
            
        elif command_type == 'mouse_click' and payload:
            button = payload.get('button', 'left')
            print(f"[*] Mouse click: {button}")
            pyautogui.click(button=button)
            time.sleep(0.2)
            
    except Exception as e:
        print(f"[ERROR] Failed to send command '{command_type}': {e}")

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
        
        print(f"\n[+] Processing {len(commands)} command(s) for session {session_id}")
        
        for cmd in commands:
            cmd_type = cmd.get('type')
            payload = cmd.get('payload')
            print(f"    Command: {cmd_type}")
            send_key_command(cmd_type, payload)
        
        # Clear commands after processing
        with open(commands_file, 'w') as f:
            json.dump([], f)
            
    except Exception as e:
        print(f"[ERROR] Error processing commands: {e}")

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
    print("[+] PowerPoint Remote Control Handler started")
    print(f"[+] Monitoring: {DATA_DIR}")
    print("[+] Listening for remote commands... (Ctrl+C to stop)")
    print()
    
    # Give warning about focus
    time.sleep(1)
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
                print(f"[+] New sessions detected: {new_sessions}")
            
            last_sessions = current_sessions
            
            time.sleep(POLL_INTERVAL)
            
    except KeyboardInterrupt:
        print("\n[*] Shutting down...")
        sys.exit(0)

if __name__ == "__main__":
    # Ensure data directory exists
    DATA_DIR.mkdir(parents=True, exist_ok=True)
    
    # Start monitoring
    monitor_commands()
