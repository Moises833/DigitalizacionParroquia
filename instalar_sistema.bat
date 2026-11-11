@echo off
rem Instala o actualiza el Sistema de Bautismos. Doble clic para ejecutar.
cd /d "%~dp0"
title Instalacion del Sistema de Bautismos

if exist "%~dp0instalar.ps1" (
    powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0instalar.ps1" %*
) else if exist "%~dp0instalador\instalar.ps1" (
    powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0instalador\instalar.ps1" %*
) else (
    echo [!] No se encontro instalar.ps1
    pause
    exit /b 1
)

if %ERRORLEVEL% neq 0 (
    echo.
    echo [!] Ocurrio un error durante la instalacion.
    pause
)
