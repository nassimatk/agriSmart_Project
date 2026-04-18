@echo off
title AgriSmart - Demarrage Global
echo ==========================================
echo    AGRISMART : DEMARRAGE DU SYSTEME
echo ==========================================

:: 1. Lancer le serveur IA dans une nouvelle fenetre
echo [1/2] Lancement du Serveur IA...
start cmd /k "cd /d %~dp0ai_model && start_ai.bat"

:: 2. Attendre quelques secondes que le serveur demarre
timeout /t 5 /nobreak > nul

:: 3. Lancer l'envoyeur de donnees sensors
echo [2/2] Lancement du simulateur de capteurs...
start cmd /k "cd /d %~dp0 && python sensor_values_sender.py"

echo.
echo ==========================================
echo    SYSTEME PRET ! 
echo    Gardez les fenetres ouvertes pour le flux.
echo ==========================================
pause
