@echo off
rem Restaura un respaldo (base de datos e imagenes). Ver README, seccion Respaldos.
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0restaurar.ps1"
pause
