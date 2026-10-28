# Instala o actualiza el Sistema de Bautismos. Se ejecuta con doble clic en
# instalar_sistema.bat, desde la carpeta descomprimida (en cualquier lugar).
#  1. Copia el sistema a C:\SistemaBautismos. Si ya estaba instalado, lo
#     actualiza sin tocar los datos (pb_data) ni los respaldos.
#  2. Prepara la base y, la primera vez, crea la cuenta de mantenimiento.
#  3. La primera vez, pide la clave de Gemini (opcional) y activa la IA.
#  4. Abre el puerto en el Firewall solo para la red local (para el telefono).
#  5. Crea los accesos directos y abre el sistema.
# Si el sistema esta abierto lo detiene antes de copiar y lo vuelve a abrir al final.
# Este archivo se mantiene sin acentos: PowerShell 5.1 lo leeria con otra codificacion.
#
# Para pruebas: instalar.ps1 -Destino C:\otra -Confirmar -SinFirewall -SinAccesos -SinAbrir
# (la clave de Gemini se puede pasar en la variable de entorno SISTEMA_CLAVE_GEMINI).

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

# No llamarla "Si": "si" es un alias de Set-Item y los alias ganan a las funciones.
function Confirma($texto) {
    return (Preguntar "$texto (S/N, Enter = S)" "S") -match "^[sS]"
}

function TextoDe($seguro) {
    return [Runtime.InteropServices.Marshal]::PtrToStringAuto(
        [Runtime.InteropServices.Marshal]::SecureStringToBSTR($seguro))
}

# Detiene el servidor de esta carpeta (corre oculto). Devuelve si estaba abierto.
function DetenerSistema($carpeta) {
    $procesos = @(Get-Process pocketbase -ErrorAction SilentlyContinue |
        Where-Object { $_.Path -and $_.Path.StartsWith("$carpeta\", [StringComparison]::OrdinalIgnoreCase) })
    if ($procesos.Count) {
        $procesos | Stop-Process -Force
        $procesos | ForEach-Object { $_.WaitForExit(10000) | Out-Null }
    }
    return [bool]$procesos.Count
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

Write-Host "`n[1/5] Copiando el sistema a $Destino..."
if ($origen -ne $Destino) {
    if ((Test-Path $Destino) -and (Get-ChildItem $Destino -Force | Select-Object -First 1) -and
        -not (Test-Path (Join-Path $Destino "pocketbase.exe"))) {
        Salir "La carpeta $Destino ya existe y no es del sistema. Muevala o cambiele el nombre y vuelva a intentar."
    }
    if (DetenerSistema $Destino) { Write-Host "  Se cerro el sistema para actualizarlo." }
    if (Get-NetTCPConnection -LocalPort $Puerto -State Listen -ErrorAction SilentlyContinue) {
        Salir "Otro programa usa el puerto $Puerto (puede ser una copia del sistema en otra carpeta). Reinicie la PC y vuelva a intentar."
    }
    New-Item -ItemType Directory -Force $Destino | Out-Null
    # /MIR deja la carpeta igual a la nueva version (borra archivos viejos del
    # programa), pero nunca toca los datos, los respaldos ni las copias previas.
    robocopy $origen $Destino /MIR /XD pb_data respaldos "pb_data_antes_*" "respaldo_nube_*" /R:2 /W:2 /NFL /NDL /NJH /NJS /NP | Out-Null
    if ($LASTEXITCODE -ge 8) { Salir "No se pudieron copiar los archivos a $Destino (codigo $LASTEXITCODE)." }
    # Los archivos vienen de un zip descargado: se desbloquean para que Windows
    # no pregunte cada vez que se abre el sistema.
    Get-ChildItem -LiteralPath $origen -Recurse -File | ForEach-Object {
        Unblock-File -LiteralPath (Join-Path $Destino $_.FullName.Substring($origen.Length)) -ErrorAction SilentlyContinue
    }
}
if ($actualizacion) {
    Write-Host "  Sistema actualizado. Los datos y los respaldos se conservaron." -ForegroundColor Green
} else {
    Write-Host "  Listo." -ForegroundColor Green
}

Set-Location $Destino
$exe = Join-Path $Destino "pocketbase.exe"

# 2. Base de datos y cuenta de mantenimiento ----------------------------------
Write-Host "`n[2/5] Preparando la base de datos..."
& $exe migrate up | Out-Null
if ($LASTEXITCODE -ne 0) { Salir "No se pudo preparar la base de datos." }

if ($actualizacion) {
    Write-Host "  Se conserva la cuenta de mantenimiento existente." -ForegroundColor Green
} else {
    Write-Host "  Cuenta de mantenimiento (solo para soporte tecnico, no para el uso diario)."
    $correo = Preguntar "  Correo (Enter = mantenimiento@parroquia.local)" "mantenimiento@parroquia.local"
    $claveTexto = ""
    if (-not $Confirmar) {
        $claveTexto = TextoDe (Read-Host "  Contrasena (minimo 10 caracteres; Enter = generar una)" -AsSecureString)
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

# 3. Inteligencia artificial (opcional) ---------------------------------------
Write-Host "`n[3/5] Lectura de actas con inteligencia artificial (Gemini)..."
if ($actualizacion) {
    Write-Host "  Se conserva la configuracion de la IA." -ForegroundColor Green
} else {
    $claveIA = [string]$env:SISTEMA_CLAVE_GEMINI
    if (-not $claveIA -and -not $Confirmar) {
        Write-Host "  Pegue la clave de Gemini (clic derecho para pegar; se ve con asteriscos)."
        Write-Host "  Se crea en https://aistudio.google.com/apikey con la cuenta de Google de la parroquia."
        $claveIA = TextoDe (Read-Host "  Clave de Gemini (Enter = cargarla despues desde el sistema)" -AsSecureString)
    }
    $claveIA = $claveIA.Trim()
    if (-not $claveIA) {
        Write-Host "  Sin clave: se puede cargar despues en el menu 'Inteligencia artificial'."
    } else {
        $env:IA_CLAVE_NUEVA = $claveIA
        $codificacion = [Console]::OutputEncoding
        try {
            [Console]::OutputEncoding = [Text.Encoding]::UTF8   # el mensaje trae acentos
            # --dev=false: nunca mostrar las consultas (llevan la clave), este donde este instalado.
            $lineas = @(& $exe configurar-ia --dev=false 2>&1 | ForEach-Object { "$_" } | Where-Object { $_.Trim() })
        } finally {
            [Console]::OutputEncoding = $codificacion
        }
        $codigo = $LASTEXITCODE
        Remove-Item Env:\IA_CLAVE_NUEVA
        $resultado = if ($lineas.Count) { $lineas[-1] -replace "^\d{4}/\d\d/\d\d \d\d:\d\d:\d\d ", "" } else { "" }
        $claveIA = $null
        $color = if ($codigo -eq 0) { "Green" } else { "Yellow" }
        Write-Host "  $resultado" -ForegroundColor $color
    }
}

# 4. Firewall (requiere permisos de administrador) ---------------------------
Write-Host "`n[4/5] Permitiendo la conexion del telefono (red local)..."
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

# 5. Accesos directos ---------------------------------------------------------
# Abren abrir_sistema.ps1 sin ventana: el servidor queda oculto y el sistema se
# abre en su propia ventana.
Write-Host "`n[5/5] Creando accesos directos..."
$powershell = Join-Path $env:SystemRoot "System32\WindowsPowerShell\v1.0\powershell.exe"
$abrir = Join-Path $Destino "abrir_sistema.ps1"
$argumentos = "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$abrir`""
function AccesoDirecto($ruta, $extra) {
    $acceso = (New-Object -ComObject WScript.Shell).CreateShortcut($ruta)
    $acceso.TargetPath = $powershell
    $acceso.Arguments = "$argumentos $extra".Trim()
    $acceso.WorkingDirectory = $Destino
    $acceso.IconLocation = Join-Path $Destino "sistema.ico"
    $acceso.WindowStyle = 7  # minimizado: casi no se ve la consola al abrir
    $acceso.Description = "Sistema de Bautismos"
    $acceso.Save()
}
if ($SinAccesos) {
    Write-Host "  Omitido."
} else {
    AccesoDirecto (Join-Path ([Environment]::GetFolderPath("Desktop")) "Sistema de Bautismos.lnk") ""
    Write-Host "  'Sistema de Bautismos' en el Escritorio." -ForegroundColor Green

    $rutaInicio = Join-Path ([Environment]::GetFolderPath("Startup")) "Servidor de Bautismos.lnk"
    if ((Test-Path $rutaInicio) -or (Confirma "  Iniciar el sistema automaticamente al encender la PC?")) {
        AccesoDirecto $rutaInicio "-SinNavegador"
        Write-Host "  El sistema se iniciara con Windows, sin ventanas." -ForegroundColor Green
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

if (-not $SinAbrir -and (Confirma "`nAbrir el sistema ahora?")) {
    Start-Process $powershell -ArgumentList $argumentos -WorkingDirectory $Destino -WindowStyle Hidden
}
