@echo off
rem Inicia el servidor del sistema y abre el navegador.
rem Con el parametro /sinnavegador solo inicia el servidor (se usa al encender la PC).
setlocal
cd /d "%~dp0"
title Sistema de Bautismos

set PUERTO=8090

netstat -ano | findstr /r /c:":%PUERTO% .*LISTENING" >nul
if %errorlevel% equ 0 (
    rem El servidor ya esta corriendo: solo se abre el navegador.
    if /i not "%~1"=="/sinnavegador" start "" http://127.0.0.1:%PUERTO%/
    exit /b 0
)

if /i not "%~1"=="/sinnavegador" (
    start "" cmd /c "timeout /t 2 >nul & start "" http://127.0.0.1:%PUERTO%/"
)

echo =====================================================================
echo   SISTEMA DE BAUTISMOS - servidor en ejecucion
echo   No cierre esta ventana mientras se usa el sistema.
echo =====================================================================
"%~dp0pocketbase.exe" serve --http=0.0.0.0:%PUERTO%
