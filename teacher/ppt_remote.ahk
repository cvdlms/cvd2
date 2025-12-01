; PowerPoint Remote Control Handler - AutoHotkey
; This script monitors for commands and sends them to PowerPoint
; Compile to .exe using: AutoHotkey_Compiler.exe ppt_remote.ahk

#NoEnv
SendMode Input
SetWorkingDir %A_ScriptDir%

; Configuration
DATA_DIR := A_ScriptDir . "\..\..\data\remote_control"
POLL_INTERVAL := 500  ; milliseconds

; Global variables
active_sessions := {}
output_file := A_ScriptDir . "\controller.log"

; Initialize
FileAppend, [%A_Now%] PowerPoint Remote Control Handler started`n, %output_file%
FileAppend, Monitoring: %DATA_DIR%`n, %output_file%
FileAppend, Press Ctrl+Alt+Z to stop`n`n, %output_file%

; Main loop - check for commands every 500ms
SetTimer, CheckCommands, %POLL_INTERVAL%

; Hotkey to exit
^!z::ExitApp

CheckCommands:
{
    ; Find all command files in the data directory
    Loop, Files, %DATA_DIR%\*_commands.json
    {
        session_id := SubStr(A_LoopFileName, 1, -14)  ; Remove "_commands.json"
        filepath := A_LoopFileFullPath
        
        ; Read the file
        FileRead, content, %filepath%
        
        ; Parse JSON (simple parsing - look for command types)
        if (InStr(content, "start_slideshow"))
        {
            SendCommandToPowePoint("start_slideshow")
            FileDelete, %filepath%
            FileAppend, [%A_Now%] Executed: start_slideshow`n, %output_file%
        }
        else if (InStr(content, "stop_slideshow"))
        {
            SendCommandToPowePoint("stop_slideshow")
            FileDelete, %filepath%
            FileAppend, [%A_Now%] Executed: stop_slideshow`n, %output_file%
        }
        else if (InStr(content, "next_slide"))
        {
            SendCommandToPowePoint("next_slide")
            FileDelete, %filepath%
            FileAppend, [%A_Now%] Executed: next_slide`n, %output_file%
        }
        else if (InStr(content, "prev_slide"))
        {
            SendCommandToPowePoint("prev_slide")
            FileDelete, %filepath%
            FileAppend, [%A_Now%] Executed: prev_slide`n, %output_file%
        }
    }
    return
}

SendCommandToPowePoint(command)
{
    ; Focus PowerPoint window if exists
    WinActivate, ahk_class screenClass  ; PowerPoint slideshow window
    if (!WinExist("ahk_class screenClass"))
    {
        WinActivate, ahk_exe powerpnt.exe  ; PowerPoint main window
    }
    Sleep, 100
    
    ; Send the command
    if (command == "start_slideshow")
    {
        Send, {F5}
        Sleep, 1000
    }
    else if (command == "stop_slideshow")
    {
        Send, {Escape}
        Sleep, 300
    }
    else if (command == "next_slide")
    {
        Send, {Space}
        Sleep, 300
    }
    else if (command == "prev_slide")
    {
        Send, {Left}
        Sleep, 300
    }
}
