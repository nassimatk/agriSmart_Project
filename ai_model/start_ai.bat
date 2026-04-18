@echo off
echo === AgriSmart AI Model Launcher ===
echo Recherche de l'environnement Anaconda/Conda...

:: Tester différents emplacements courants de conda
SET CONDA_PYTHON=
IF EXIST "%USERPROFILE%\Anaconda3\python.exe" SET CONDA_PYTHON=%USERPROFILE%\Anaconda3\python.exe
IF EXIST "%USERPROFILE%\miniconda3\python.exe" SET CONDA_PYTHON=%USERPROFILE%\miniconda3\python.exe
IF EXIST "C:\ProgramData\Anaconda3\python.exe" SET CONDA_PYTHON=C:\ProgramData\Anaconda3\python.exe
IF EXIST "C:\ProgramData\miniconda3\python.exe" SET CONDA_PYTHON=C:\ProgramData\miniconda3\python.exe

IF NOT "%CONDA_PYTHON%"=="" (
    echo OK - Anaconda Python trouve: %CONDA_PYTHON%
    echo Installation des dependances si necessaire...
    "%CONDA_PYTHON%" -m pip install fastapi uvicorn --quiet
    echo Lancement du serveur IA sur le port 8000...
    cd /d "%~dp0"
    "%CONDA_PYTHON%" -m uvicorn main:app --port 8000
    goto :end
)

:: Fallback: essayer pip install avec une version Python disponible (Python 3.10+)
echo Anaconda non trouve. Tentative avec Python standard...
python -m pip install fastapi uvicorn pandas scikit-learn --quiet
echo Lancement du serveur IA...
cd /d "%~dp0"
python -m uvicorn main:app --port 8000

:end
pause
