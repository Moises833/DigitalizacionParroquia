@echo off
title Permitir Acceso Red Local (Puerto 8000)
color 0A
echo =========================================================================
echo       CONFIGURANDO CORTAFUEGOS DE WINDOWS PARA ACCESO MOVIL
echo =========================================================================
echo.
echo [+] Añadiendo regla en el Firewall de Windows para el puerto 8000...
powershell -Command "Start-Process netsh -ArgumentList 'advfirewall firewall add rule name=\"Sistema Bautizos Laravel (Puerto 8000)\" dir=in action=allow protocol=TCP localport=8000' -Verb RunAs"

echo.
echo [✓] Regla del Firewall agregada exitosamente.
echo [i] Ahora tu teléfono móvil por Wi-Fi podrá conectarse con la PC por Ethernet.
echo.
pause
