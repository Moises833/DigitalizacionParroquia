@echo off
title Instalador del Sistema de Digitalizacion de Bautizos
color 0A
echo =========================================================================
echo       INSTALADOR AUTOMATICO - DIGITALIZACION DE BAUTIZOS
echo =========================================================================
echo.

:: 1. Verificar instalacion de PHP
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo [!] PHP no esta instalado o no se encuentra en el PATH.
    echo [i] Intentando instalar PHP y Composer automaticamente con Winget...
    winget install --id PHP.PHP -e --source winget --accept-source-agreements --accept-package-agreements
    winget install --id Composer.Composer -e --source winget --accept-source-agreements --accept-package-agreements
    echo.
    echo [!] Por favor, cierra esta ventana y vuelve a ejecutar instalar_sistema.bat
    pause
    exit /b
)

:: 2. Crear archivo .env si no existe
if not exist ".env" (
    echo [+] Creando archivo de configuracion .env...
    copy .env.example .env
)

:: 3. Crear base de datos SQLite si no existe
if not exist "database\database.sqlite" (
    echo [+] Creando archivo de base de datos local SQLite...
    type nul > database\database.sqlite
)

:: 4. Ajustar ruta de SQLite en .env
echo [+] Configurando base de datos local SQLite...
powershell -Command "(Get-Content .env) -replace 'DB_CONNECTION=.*', 'DB_CONNECTION=sqlite' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'DB_DATABASE=.*', ('DB_DATABASE=' + (Get-Location).Path + '\database\database.sqlite') | Set-Content .env"

:: 5. Instalar dependencias con Composer
echo [+] Instalando dependencias de PHP con Composer...
where composer >nul 2>&1
if %errorlevel% equ 0 (
    call composer install --no-dev --no-interaction --prefer-dist
) else (
    echo [!] Composer no encontrado en PATH. Saltando composer install.
)

:: 6. Generar clave de encriptacion
echo [+] Generando clave unica de encriptacion (APP_KEY)...
call php artisan key:generate --force

:: 7. Migrar y poblar base de datos
echo [+] Estructurando e instalando la base de datos...
call php artisan migrate --force
call php artisan db:seed --force

:: 8. Crear enlace simbolico de imagenes
echo [+] Vinculando almacenamiento de imagenes escaneadas...
call php artisan storage:link --force >nul 2>&1

echo.
echo =========================================================================
echo    ¡INSTALACION COMPLETADA EXITOSAMENTE!
echo =========================================================================
echo  Para iniciar el sistema en cualquier momento, ejecuta: iniciar_sistema.bat
echo.
pause
