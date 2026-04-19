@echo off
title AgriSmart - Demarrage Global
echo ==========================================
echo    AGRISMART : DEMARRAGE DU SYSTEME
echo ==========================================

:: 1. Lancer le serveur IA (Predict) dans une nouvelle fenetre
echo [1/3] Lancement du Serveur IA (Predict)...
start cmd /k "cd /d %~dp0ai_model && start_ai.bat"

:: 2. Lancer le serveur IA (Plant) dans une nouvelle fenetre
echo [2/3] Lancement du Serveur IA (Plant)...
start cmd /k "cd /d %~dp0ai_plant && start_ai_plant.bat"

:: 3. Attendre quelques secondes que les serveurs demarrent
timeout /t 5 /nobreak > nul

:: 4. Lancer l'envoyeur de donnees sensors
echo [3/3] Lancement du simulateur de capteurs...
start cmd /k "cd /d %~dp0 && python sensor_values_sender.py"

echo.
echo ==========================================
echo    SYSTEME PRET ! 
echo    Gardez les fenetres ouvertes pour le flux.
echo ==========================================
pause
