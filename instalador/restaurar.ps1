# Restaura un respaldo del Sistema de Bautismos: la base de datos y las imagenes.
# Los datos actuales no se borran: quedan en una carpeta pb_data_antes_<fecha>.
# Este archivo se mantiene sin acentos: PowerShell 5.1 lo leeria con otra codificacion.
#
# Uso normal: doble clic en restaurar_respaldo.bat y responder las preguntas.
# Sin preguntas: restaurar.ps1 -Origen E:\Respaldos [-Base datos_....db] -Confirmar
#                restaurar.ps1 -Nube -Confirmar   (usa los datos de la nube guardados)

param(
    [string]$Carpeta = $PSScriptRoot,  # carpeta del sistema (donde esta pocketbase.exe)
    [string]$Origen = "",              # carpeta del respaldo (con db\ y storage\)
    [string]$Base = "",                # copia de la base a usar (por defecto la mas reciente)
    [switch]$Nube,                     # bajar primero el respaldo desde la nube
    [switch]$Confirmar,                # no hacer preguntas
    [int]$Puerto = 8090
)

$ErrorActionPreference = "Stop"
$exe = Join-Path $Carpeta "pocketbase.exe"
$datos = Join-Path $Carpeta "pb_data"
Set-Location $Carpeta

function Salir($mensaje) {
    Write-Host "[!] $mensaje" -ForegroundColor Red
    exit 1
}

# El servidor de esta carpeta corre oculto (abrir_sistema.ps1).
function ProcesosDelSistema {
    return @(Get-Process pocketbase -ErrorAction SilentlyContinue |
        Where-Object { $_.Path -and $_.Path.StartsWith("$Carpeta\", [StringComparison]::OrdinalIgnoreCase) })
}

Write-Host "=====================================================================" -ForegroundColor Cyan
Write-Host "  RESTAURAR UN RESPALDO DEL SISTEMA DE BAUTISMOS" -ForegroundColor Cyan
Write-Host "=====================================================================" -ForegroundColor Cyan

if (-not (Test-Path $exe)) { Salir "No se encontro pocketbase.exe en $Carpeta" }
$Carpeta = (Resolve-Path $Carpeta).Path.TrimEnd("\")
if (-not (ProcesosDelSistema).Count -and (Get-NetTCPConnection -LocalPort $Puerto -State Listen -ErrorAction SilentlyContinue)) {
    Salir "Otro programa usa el puerto $Puerto. Reinicie la PC y vuelva a intentar."
}

# 1. De donde ------------------------------------------------------------------
if (-not $Origen -and -not $Nube -and -not $Confirmar) {
    Write-Host "`nDe donde se restaura?"
    Write-Host "  1. De una carpeta de respaldo (otro disco, pendrive o la carpeta 'respaldos')"
    Write-Host "  2. De la nube"
    if ((Read-Host "Opcion (1 o 2)") -eq "2") { $Nube = $true }
}

if ($Nube) {
    if (-not $Origen) { $Origen = Join-Path $Carpeta ("respaldo_nube_" + (Get-Date -Format "yyyyMMdd_HHmm")) }
    $guardados = Test-Path (Join-Path $datos "data.db")
    $usarGuardados = $guardados -and ($Confirmar -or (Read-Host "Usar los datos de la nube guardados en este equipo? (S/N)") -match "^[sS]")
    if (-not $usarGuardados) {
        Write-Host "Datos de la nube (los mismos que se cargaron en la pagina Respaldos):"
        $env:RESPALDO_S3_ENDPOINT = Read-Host "  Endpoint (por ejemplo https://s3.us-west-004.backblazeb2.com)"
        $env:RESPALDO_S3_REGION = Read-Host "  Region (por ejemplo us-west-004)"
        $env:RESPALDO_S3_BUCKET = Read-Host "  Bucket"
        $prefijo = Read-Host "  Prefijo (Enter = bautismos)"
        $env:RESPALDO_S3_PREFIJO = $(if ($prefijo) { $prefijo } else { "bautismos" })
        $env:RESPALDO_S3_CLAVE_ID = Read-Host "  Key ID"
        $secreto = Read-Host "  Clave secreta" -AsSecureString
        $env:RESPALDO_S3_SECRETO = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
            [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secreto))
    }
    Write-Host "`nBajando el respaldo a $Origen (la primera vez puede tardar horas)..."
    & $exe descargar-respaldo $Origen
    $codigo = $LASTEXITCODE
    $env:RESPALDO_S3_SECRETO = $null
    if ($codigo -ne 0) { Salir "No se pudo bajar el respaldo de la nube." }
}

if (-not $Origen) {
    $porDefecto = Join-Path $Carpeta "respaldos"
    $Origen = Read-Host "Carpeta del respaldo (Enter = $porDefecto)"
    if (-not $Origen) { $Origen = $porDefecto }
}

# 2. Que copia -------------------------------------------------------------------
$carpetaDb = Join-Path $Origen "db"
if (-not (Test-Path $carpetaDb)) { Salir "No hay copias de la base en $carpetaDb" }
$copias = @(Get-ChildItem $carpetaDb -Filter "datos_*.db" | Sort-Object Name -Descending)
if ($copias.Count -eq 0) { Salir "No hay copias de la base en $carpetaDb" }

if ($Base) {
    $elegida = $copias | Where-Object { $_.Name -eq $Base } | Select-Object -First 1
    if (-not $elegida) { Salir "No se encontro la copia $Base" }
} elseif ($Confirmar) {
    $elegida = $copias[0]
} else {
    Write-Host "`nCopias disponibles (la 1 es la mas reciente):"
    $maximo = [Math]::Min(10, $copias.Count)
    for ($i = 0; $i -lt $maximo; $i++) {
        Write-Host ("  {0}. {1}  ({2:N1} MB)" -f ($i + 1), $copias[$i].Name, ($copias[$i].Length / 1MB))
    }
    $numero = Read-Host "Numero (Enter = 1)"
    if (-not $numero) { $numero = 1 }
    $elegida = $copias[[int]$numero - 1]
    if (-not $elegida) { Salir "Numero invalido." }
}

$marca = Get-Date -Format "yyyyMMdd_HHmmss"
Write-Host "`nSe restaurara la copia $($elegida.Name) desde $Origen"
Write-Host "Los datos actuales no se borran: quedan en pb_data_antes_$marca"
if (-not $Confirmar) {
    if ((Read-Host "Escriba SI para continuar") -cne "SI") { Salir "Cancelado. No se cambio nada." }
}

# 3. Restaurar -------------------------------------------------------------------
$abiertos = ProcesosDelSistema
if ($abiertos.Count) {
    Write-Host "Cerrando el sistema..."
    $abiertos | Stop-Process -Force
    $abiertos | ForEach-Object { $_.WaitForExit(10000) | Out-Null }
}
if (Test-Path $datos) { Rename-Item -Path $datos -NewName "pb_data_antes_$marca" }
New-Item -ItemType Directory $datos | Out-Null
Copy-Item $elegida.FullName (Join-Path $datos "data.db")

$imagenes = Join-Path $Origen "storage"
if (Test-Path $imagenes) {
    Write-Host "Copiando las imagenes..."
    robocopy $imagenes (Join-Path $datos "storage") /E /NFL /NDL /NJH /NJS /NP /R:1 /W:1 | Out-Null
    if ($LASTEXITCODE -ge 8) {
        Salir "Fallo la copia de las imagenes (codigo $LASTEXITCODE). Los datos anteriores estan en pb_data_antes_$marca."
    }
}

# Por si el respaldo es de una version anterior del sistema.
& $exe migrate up | Out-Null
if ($LASTEXITCODE -ne 0) { Salir "La base restaurada no se pudo actualizar. Los datos anteriores estan en pb_data_antes_$marca." }

Write-Host "`n=====================================================================" -ForegroundColor Green
Write-Host "  Restauracion completa. Abra 'Sistema de Bautismos' en el Escritorio (o reinicie la PC)." -ForegroundColor Green
Write-Host "  Revise que esten los ultimos datos cargados antes de seguir trabajando." -ForegroundColor Green
Write-Host "=====================================================================" -ForegroundColor Green
