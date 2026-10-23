# Configuracion inicial del equipo de la parroquia (se ejecuta una sola vez).
#  1. Crea la cuenta de mantenimiento (panel /_/ de PocketBase).
#  2. Abre el puerto en el Firewall solo para la red local (para el telefono).
#  3. Crea accesos directos en el Escritorio y en el Inicio de Windows.
# Este archivo se mantiene sin acentos: PowerShell 5.1 lo leeria con otra codificacion.

$ErrorActionPreference = "Stop"
$carpeta = $PSScriptRoot
$puerto = 8090
$exe = Join-Path $carpeta "pocketbase.exe"

Write-Host "=====================================================================" -ForegroundColor Cyan
Write-Host "  INSTALACION DEL SISTEMA DE BAUTISMOS" -ForegroundColor Cyan
Write-Host "=====================================================================" -ForegroundColor Cyan

if (-not [Environment]::Is64BitOperatingSystem) {
    Write-Host "[!] Este equipo es de 32 bits. Esta version del sistema requiere Windows de 64 bits." -ForegroundColor Red
    exit 1
}
if (-not (Test-Path $exe)) {
    Write-Host "[!] No se encontro pocketbase.exe en $carpeta" -ForegroundColor Red
    exit 1
}

# 1. Base de datos y cuenta de mantenimiento ---------------------------------
Write-Host "`n[1/3] Preparando la base de datos..."
& $exe migrate up | Out-Null

Write-Host "Cuenta de mantenimiento (solo para soporte tecnico, no para el uso diario)."
$correo = Read-Host "  Correo"
$clave = Read-Host "  Contrasena (minimo 10 caracteres)" -AsSecureString
$claveTexto = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR($clave))
if ($claveTexto.Length -lt 10) {
    Write-Host "[!] La contrasena es demasiado corta." -ForegroundColor Red
    exit 1
}
& $exe superuser upsert $correo $claveTexto | Out-Null
$claveTexto = $null
Write-Host "  Cuenta de mantenimiento lista." -ForegroundColor Green

# 2. Firewall (requiere permisos de administrador) ---------------------------
Write-Host "`n[2/3] Abriendo el puerto $puerto para la red local..."
$regla = "Sistema de Bautismos (puerto $puerto)"
$comando = "Remove-NetFirewallRule -DisplayName '$regla' -ErrorAction SilentlyContinue; " +
    "New-NetFirewallRule -DisplayName '$regla' -Direction Inbound -Protocol TCP " +
    "-LocalPort $puerto -Action Allow -RemoteAddress LocalSubnet -Profile Any | Out-Null"
try {
    Start-Process powershell -Verb RunAs -Wait -ArgumentList "-NoProfile", "-Command", $comando
    Write-Host "  Puerto abierto (solo equipos de la misma red)." -ForegroundColor Green
} catch {
    Write-Host "  [!] No se abrio el puerto. El telefono no podra conectarse hasta repetir este paso." -ForegroundColor Yellow
}

# 3. Accesos directos ---------------------------------------------------------
Write-Host "`n[3/3] Creando accesos directos..."
$shell = New-Object -ComObject WScript.Shell
$bat = Join-Path $carpeta "iniciar_sistema.bat"

$escritorio = $shell.CreateShortcut((Join-Path ([Environment]::GetFolderPath("Desktop")) "Sistema de Bautismos.lnk"))
$escritorio.TargetPath = $bat
$escritorio.WorkingDirectory = $carpeta
$escritorio.Save()

$respuesta = Read-Host "  Iniciar el servidor automaticamente al encender la PC? (S/N)"
if ($respuesta -match "^[sS]") {
    $inicio = $shell.CreateShortcut((Join-Path ([Environment]::GetFolderPath("Startup")) "Servidor de Bautismos.lnk"))
    $inicio.TargetPath = $bat
    $inicio.Arguments = "/sinnavegador"
    $inicio.WorkingDirectory = $carpeta
    $inicio.WindowStyle = 7  # minimizado
    $inicio.Save()
    Write-Host "  El servidor se iniciara con Windows." -ForegroundColor Green
}

Write-Host "`n=====================================================================" -ForegroundColor Cyan
Write-Host "  Instalacion completa. Abra 'Sistema de Bautismos' en el Escritorio." -ForegroundColor Cyan
Write-Host "  La primera vez se pedira registrar la parroquia y el administrador." -ForegroundColor Cyan
Write-Host "=====================================================================" -ForegroundColor Cyan
