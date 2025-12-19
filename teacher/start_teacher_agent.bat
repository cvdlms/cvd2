@echo off
REM Start Teacher Agent (connects to central server)













pausepython teacher_agent.py --server %SERVER% --token %TOKEN% --name "%NAME%"
necho Starting Teacher Agent...)  exit /b 1  echo Missing TOKEN. Usage: start_teacher_agent.bat  http://server:5000  SECRET_TOKEN "Teacher Name"if "%TOKEN%"=="" ()  exit /b 1  echo Usage: start_teacher_agent.bat  http://server:5000  SECRET_TOKEN "Teacher Name"
nif "%SERVER%"=="" (nSET SERVER=%1
nSET TOKEN=%2
nSET NAME=%3