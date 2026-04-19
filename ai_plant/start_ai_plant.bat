@echo off
echo Lancement du Serveur IA pour la Detection de Maladies (AI Plant)...

set PYTHON_PATH="C:\Users\Shrek\AppData\Local\Programs\Python\Python310\python.exe"

%PYTHON_PATH% -m pip install fastapi uvicorn tensorflow pillow numpy python-multipart --quiet

cd /d "%~dp0"
%PYTHON_PATH% -m uvicorn app:app --port 8001
