@echo off
rem Configuracion inicial del equipo. Se ejecuta una sola vez.
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0instalar.ps1"
pause
