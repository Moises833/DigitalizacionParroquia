# Instala o actualiza el Sistema de Bautismos. Se ejecuta con doble clic en
# instalar_sistema.bat, desde la carpeta descomprimida (en cualquier lugar).
#  1. Copia el sistema a C:\SistemaBautismos. Si ya estaba instalado, lo
#     actualiza sin tocar los datos (pb_data) ni los respaldos.
#  2. Prepara la base y, la primera vez, crea la cuenta de mantenimiento.
#  3. Abre el puerto en el Firewall solo para la red local (para el telefono).
#  4. Crea los accesos directos y abre el sistema.
# Este archivo se mantiene sin acentos: PowerShell 5.1 lo leeria con otra codificacion.
#
# Para pruebas: instalar.ps1 -Destino C:\otra -Confirmar -SinFirewall -SinAccesos -SinAbrir

param(
    [string]$Destino = "C:\SistemaBautismos",
    [switch]$Confirmar,     # sin preguntas: usa las respuestas por defecto
    [switch]$SinFirewall,
    [switch]$SinAccesos,
    [switch]$SinAbrir,
    [int]$Puerto = 8090
)

$ErrorActionPreference = "Stop"
$origen = $PSScriptRoot

function Salir($mensaje) {
    Write-Host "`n[!] $mensaje" -ForegroundColor Red
    exit 1
}

# Pregunta con respuesta por defecto (Enter o -Confirmar la eligen).
function Preguntar($texto, $porDefecto) {
    if ($Confirmar) { return $porDefecto }
    $r = Read-Host $texto
    if ($r) { return $r } else { return $porDefecto }
}

function Si($texto) {
    return (Preguntar "$texto (S/N, Enter = S)" "S") -match "^[sS]"
}

function ClaveAlAzar {
    $letras = "abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789"
    $bytes = New-Object byte[] 16
    [Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
    return -join ($bytes | ForEach-Object { $letras[$_ % $letras.Length] })
}

Write-Host "=====================================================================" -ForegroundColor Cyan
Write-Host "  INSTALACION DEL SISTEMA DE BAUTISMOS" -ForegroundColor Cyan
Write-Host "=====================================================================" -ForegroundColor Cyan

if (-not [Environment]::Is64BitOperatingSystem) {
    Salir "Este equipo es de 32 bits. El sistema requiere Windows de 64 bits."
}
if (-not (Test-Path (Join-Path $origen "pocketbase.exe"))) {
    Salir ("Faltan archivos del sistema. Seguramente se abrio el instalador sin descomprimir el zip.`n" +
           "    Cierre esta ventana, haga clic derecho sobre SistemaBautismos.zip -> 'Extraer todo...'`n" +
           "    y abra instalar_sistema.bat desde la carpeta que se crea.")
}

# 1. Copiar a la carpeta definitiva ---------------------------------------------
$origen = (Resolve-Path $origen).Path.TrimEnd("\")
$Destino = [IO.Path]::GetFullPath($Destino).TrimEnd("\")
$actualizacion = Test-Path (Join-Path $Destino "pb_data\data.db")

Write-Host "`n[1/4] Copiando el sistema a $Destino..."
if ($origen -ne $Destino) {
    if ((Test-Path $Destino) -and (Get-ChildItem $Destino -Force | Select-Object -First 1) -and
        -not (Test-Path (Join-Path $Destino "pocketbase.exe"))) {
        Salir "La carpeta $Destino ya existe y no es del sistema. Muevala o cambiele el nombre y vuelva a intentar."
    }
    while (Get-NetTCPConnection -LocalPort $Puerto -State Listen -ErrorAction SilentlyContinue) {
        if ($Confirmar) { Salir "El sistema esta abierto. Cierre la ventana 'Sistema de Bautismos' y vuelva a intentar." }
        Write-Host "  El sistema esta abierto. Cierre la ventana 'Sistema de Bautismos' (puede estar minimizada" -ForegroundColor Yellow
        Write-Host "  en la barra de tareas) y presione Enter." -ForegroundColor Yellow
        Read-Host | Out-Null
    }
    New-Item -ItemType Directory -Force $Destino | Out-Null
    # /MIR deja la carpeta igual a la nueva version (borra archivos viejos del
    # programa), pero nunca toca los datos, los respaldos ni las copias previas.
    robocopy $origen $Destino /MIR /XD pb_data respaldos "pb_data_antes_*" "respaldo_nube_*" /R:2 /W:2 /NFL /NDL /NJH /NJS /NP | Out-Null
    if ($LASTEXITCODE -ge 8) { Salir "No se pudieron copiar los archivos a $Destino (codigo $LASTEXITCODE)." }
    # Los archivos vienen de un zip descargado: se desbloquean para que Windows
    # no pregunte cada vez que se abre el sistema.
    Get-ChildItem $origen -Recurse -File |
        ForEach-Object { Join-Path $Destino $_.FullName.Substring($origen.Length) } |
        Unblock-File -ErrorAction SilentlyContinue
}
if ($actualizacion) {
    Write-Host "  Sistema actualizado. Los datos y los respaldos se conservaron." -ForegroundColor Green
} else {
    Write-Host "  Listo." -ForegroundColor Green
}

Set-Location $Destino
$exe = Join-Path $Destino "pocketbase.exe"

# 2. Base de datos y cuenta de mantenimiento ----------------------------------
Write-Host "`n[2/4] Preparando la base de datos..."
& $exe migrate up | Out-Null
if ($LASTEXITCODE -ne 0) { Salir "No se pudo preparar la base de datos." }

if ($actualizacion) {
    Write-Host "  Se conserva la cuenta de mantenimiento existente." -ForegroundColor Green
} else {
    Write-Host "  Cuenta de mantenimiento (solo para soporte tecnico, no para el uso diario)."
    $correo = Preguntar "  Correo (Enter = mantenimiento@parroquia.local)" "mantenimiento@parroquia.local"
    $claveTexto = ""
    if (-not $Confirmar) {
        $clave = Read-Host "  Contrasena (minimo 10 caracteres; Enter = generar una)" -AsSecureString
        $claveTexto = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
            [Runtime.InteropServices.Marshal]::SecureStringToBSTR($clave))
    }
    $generada = -not $claveTexto
    if ($generada) { $claveTexto = ClaveAlAzar }
    if ($claveTexto.Length -lt 10) { Salir "La contrasena es demasiado corta. Vuelva a ejecutar el instalador." }
    & $exe superuser upsert $correo $claveTexto | Out-Null
    if ($LASTEXITCODE -ne 0) { Salir "No se pudo crear la cuenta de mantenimiento." }
    Write-Host "  Cuenta de mantenimiento: $correo" -ForegroundColor Green
    if ($generada) {
        Write-Host "  Contrasena de mantenimiento: $claveTexto" -ForegroundColor Yellow
        Write-Host "  Anotela en la hoja de datos de la instalacion y guardela fuera de la PC." -ForegroundColor Yellow
        if (-not $Confirmar) { Read-Host "  Presione Enter cuando la haya anotado" | Out-Null }
    }
    $claveTexto = $null
}

# 3. Firewall (requiere permisos de administrador) ---------------------------
Write-Host "`n[3/4] Permitiendo la conexion del telefono (red local)..."
$regla = "Sistema de Bautismos (puerto $Puerto)"
if ($SinFirewall) {
    Write-Host "  Omitido."
} elseif (Get-NetFirewallRule -DisplayName $regla -ErrorAction SilentlyContinue) {
    Write-Host "  Ya estaba permitido." -ForegroundColor Green
} else {
    Write-Host "  Windows va a pedir permiso de administrador: responda Si."
    $comando = "New-NetFirewallRule -DisplayName '$regla' -Direction Inbound -Protocol TCP " +
        "-LocalPort $Puerto -Action Allow -RemoteAddress LocalSubnet -Profile Any | Out-Null"
    try {
        Start-Process powershell -Verb RunAs -Wait -ArgumentList "-NoProfile", "-Command", $comando
        Write-Host "  Listo (solo equipos de la misma red)." -ForegroundColor Green
    } catch {
        Write-Host "  [!] No se dio el permiso. El telefono no podra conectarse hasta volver a ejecutar el instalador." -ForegroundColor Yellow
    }
}

# 4. Accesos directos ---------------------------------------------------------
Write-Host "`n[4/4] Creando accesos directos..."
$bat = Join-Path $Destino "iniciar_sistema.bat"
if ($SinAccesos) {
    Write-Host "  Omitido."
} else {
    $shell = New-Object -ComObject WScript.Shell
    $escritorio = $shell.CreateShortcut((Join-Path ([Environment]::GetFolderPath("Desktop")) "Sistema de Bautismos.lnk"))
    $escritorio.TargetPath = $bat
    $escritorio.WorkingDirectory = $Destino
    $escritorio.Save()
    Write-Host "  'Sistema de Bautismos' en el Escritorio." -ForegroundColor Green

    $rutaInicio = Join-Path ([Environment]::GetFolderPath("Startup")) "Servidor de Bautismos.lnk"
    if ((Test-Path $rutaInicio) -or (Si "  Iniciar el sistema automaticamente al encender la PC?")) {
        $inicio = $shell.CreateShortcut($rutaInicio)
        $inicio.TargetPath = $bat
        $inicio.Arguments = "/sinnavegador"
        $inicio.WorkingDirectory = $Destino
        $inicio.WindowStyle = 7  # minimizado
        $inicio.Save()
        Write-Host "  El sistema se iniciara con Windows." -ForegroundColor Green
    }
}

Write-Host "`n=====================================================================" -ForegroundColor Cyan
if ($actualizacion) {
    Write-Host "  Actualizacion completa." -ForegroundColor Cyan
} else {
    Write-Host "  Instalacion completa. La primera vez se registra la parroquia y el administrador." -ForegroundColor Cyan
}
Write-Host "  El sistema quedo en $Destino. Para abrirlo: 'Sistema de Bautismos' en el Escritorio." -ForegroundColor Cyan
if ($origen -ne $Destino) {
    Write-Host "  La carpeta descargada y el zip ya se pueden borrar." -ForegroundColor Cyan
}
Write-Host "=====================================================================" -ForegroundColor Cyan

if (-not $SinAbrir -and (Si "`nAbrir el sistema ahora?")) {
    Start-Process -FilePath $bat -WorkingDirectory $Destino
}
