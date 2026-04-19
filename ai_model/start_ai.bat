@echo off
echo === AgriSmart AI Model Launcher ===
echo Lancement du Serveur IA (Predict)...

set PYTHON_PATH="C:\Users\Shrek\AppData\Local\Programs\Python\Python310\python.exe"

%PYTHON_PATH% -m pip install fastapi uvicorn pandas scikit-learn --quiet

cd /d "%~dp0"
%PYTHON_PATH% -m uvicorn main:app --port 8000

pause
