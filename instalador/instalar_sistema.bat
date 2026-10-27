@echo off
rem Instala o actualiza el sistema (los datos se conservan). Doble clic desde la carpeta descomprimida.
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0instalar.ps1"
pause
