@echo off
title Servidor de Bautizos Parroquial
color 0B
echo =========================================================================
echo       INICIANDO SISTEMA DE DIGITALIZACION DE BAUTIZOS
echo =========================================================================
echo.
echo [+] Abriendo navegador web en http://127.0.0.1:8000 ...
start http://127.0.0.1:8000

echo [+] Servidor local activo. Para detener el sistema, cierra esta ventana.
echo.
php artisan serve
