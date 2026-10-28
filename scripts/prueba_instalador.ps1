# Prueba del instalador con el paquete ya armado (dist\SistemaBautismos.zip):
# instalacion desde una carpeta con espacios, cuenta de mantenimiento generada,
# actualizacion sin perder datos y los casos de error. No toca el Firewall, el
# Escritorio ni C:\SistemaBautismos: todo queda en una carpeta temporal.
# Uso: powershell -ExecutionPolicy Bypass -File scripts\prueba_instalador.ps1

$ErrorActionPreference = "Stop"
$raiz = Split-Path $PSScriptRoot -Parent
$zip = Join-Path $raiz "dist\SistemaBautismos.zip"
if (-not (Test-Path $zip)) { throw "Falta ${zip}: ejecutar antes scripts\empaquetar.ps1" }

$base = Join-Path ([IO.Path]::GetTempPath()) ("prueba_instalador_" + [guid]::NewGuid().ToString("N").Substring(0, 6))
$descarga = Join-Path $base "Mis descargas (1)"
$destino = Join-Path $base "SistemaBautismos"
$puerto = 8097
$script:fallas = 0

function Check($nombre, $cond, $extra = "") {
    if ($cond) { Write-Host "OK    $nombre" } else { Write-Host "FALLA $nombre -> $extra" -ForegroundColor Red; $script:fallas++ }
}

function Instalar($carpeta, $dest, [switch]$Abierto) {
    $salida = & powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $carpeta "instalar.ps1") `
        -Destino $dest -Confirmar -SinFirewall -SinAccesos -SinAbrir -Puerto $puerto 2>&1 | Out-String
    return @{ codigo = $LASTEXITCODE; salida = $salida }
}

function Servidor($carpeta) {
    return @(Get-Process pocketbase -ErrorAction SilentlyContinue | Where-Object { $_.Path -like "$carpeta\*" })
}

# Igual que el acceso directo del inicio de Windows: abrir_sistema.ps1 sin ventana.
function Iniciar {
    & powershell -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "$destino\abrir_sistema.ps1" -SinNavegador -Puerto $puerto
    if ($LASTEXITCODE -ne 0) { throw "abrir_sistema.ps1 no pudo iniciar el servidor" }
    return (Servidor $destino)[0]
}

function Detener($proceso) {
    if ($proceso -and -not $proceso.HasExited) { Stop-Process -Id $proceso.Id -Force; $proceso.WaitForExit(5000) | Out-Null }
}

function Entrar($clave) {
    $cuerpo = @{ identity = "mantenimiento@parroquia.local"; password = $clave } | ConvertTo-Json
    return (Invoke-RestMethod "http://127.0.0.1:$puerto/api/collections/_superusers/auth-with-password" `
        -Method Post -ContentType "application/json" -Body $cuerpo).token
}

try {
    Expand-Archive $zip -DestinationPath $descarga
    $paquete = Join-Path $descarga "SistemaBautismos"
    # Marca de "descargado de internet", como la deja el Explorador al extraer un zip bajado.
    Get-ChildItem -LiteralPath $paquete -Recurse -File | ForEach-Object {
        Set-Content -LiteralPath $_.FullName -Stream Zone.Identifier -Value "[ZoneTransfer]`r`nZoneId=3"
    }

    # --- Instalacion nueva ----------------------------------------------------
    $r = Instalar $paquete $destino
    Check "instala desde una carpeta con espacios y parentesis" ($r.codigo -eq 0) $r.salida
    Check "copia el sistema a la carpeta definitiva" ((Test-Path "$destino\pocketbase.exe") -and (Test-Path "$destino\pb_hooks") -and (Test-Path "$destino\pb_data\data.db"))
    $marcados = @(Get-ChildItem -LiteralPath $destino -Recurse -File -Exclude *.db |
        Where-Object { Get-Item -LiteralPath $_.FullName -Stream Zone.Identifier -ErrorAction SilentlyContinue })
    Check "quita la marca de internet (Windows no pregunta al abrir)" ($marcados.Count -eq 0) ($marcados | Select-Object -First 3 -ExpandProperty Name)
    $clave = if ($r.salida -match "Contrasena de mantenimiento: (\S+)") { $Matches[1] } else { "" }
    Check "genera una contrasena de mantenimiento de 16 caracteres" ($clave.Length -eq 16) $r.salida

    $servidor = Iniciar
    try {
        Check "el acceso directo inicia el sistema sin ventana" ($servidor -and $servidor.MainWindowHandle -eq 0)
        & powershell -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "$destino\abrir_sistema.ps1" -SinNavegador -Puerto $puerto
        Check "abrirlo de nuevo no inicia otra copia" ((Servidor $destino).Count -eq 1)
        $token = Entrar $clave
        Check "la cuenta de mantenimiento entra con esa contrasena" ([bool]$token)
        $libro = Invoke-RestMethod "http://127.0.0.1:$puerto/api/collections/libros/records" -Method Post `
            -Headers @{ Authorization = $token } -ContentType "application/json" `
            -Body (@{ numero = "Tomo de prueba"; sacramento = "bautismo" } | ConvertTo-Json)
        Check "el sistema instalado guarda datos" ([bool]$libro.id)
        $r2 = Instalar $paquete $destino
        Check "si el sistema esta abierto lo cierra solo y actualiza" ($r2.codigo -eq 0 -and $r2.salida -match "Se cerro el sistema" -and
            (Servidor $destino).Count -eq 0) $r2.salida
    } finally {
        Detener $servidor
    }

    # Otro programa en el puerto (por ejemplo, una copia del sistema en otra carpeta).
    $ajeno = Start-Process "$paquete\pocketbase.exe" -ArgumentList "serve", "--http=127.0.0.1:$puerto" `
        -WorkingDirectory $paquete -WindowStyle Hidden -PassThru
    Start-Sleep -Seconds 2
    try {
        $r2 = Instalar $paquete $destino
        Check "avisa si otro programa usa el puerto" ($r2.codigo -ne 0 -and $r2.salida -match "Otro programa") $r2.salida
        Check "no detiene programas de otras carpetas" (-not $ajeno.HasExited)
    } finally {
        Detener $ajeno
        Remove-Item "$paquete\pb_data" -Recurse -Force -ErrorAction SilentlyContinue
    }

    # --- Actualizacion ---------------------------------------------------------
    New-Item -ItemType Directory -Force "$destino\respaldos" | Out-Null
    Set-Content "$destino\respaldos\copia.txt" "respaldo"
    Set-Content "$destino\pb_hooks\viejo.pb.js" "// archivo de una version anterior"
    $r = Instalar $paquete $destino
    Check "actualiza un sistema instalado" ($r.codigo -eq 0 -and $r.salida -match "actualizado") $r.salida
    Check "al actualizar no crea otra cuenta de mantenimiento" ($r.salida -notmatch "Contrasena de mantenimiento") $r.salida
    Check "conserva los respaldos" (Test-Path "$destino\respaldos\copia.txt")
    Check "borra los archivos viejos del programa" (-not (Test-Path "$destino\pb_hooks\viejo.pb.js"))

    $servidor = Iniciar
    try {
        $token = Entrar $clave
        $libros = Invoke-RestMethod "http://127.0.0.1:$puerto/api/collections/libros/records" -Headers @{ Authorization = $token }
        Check "conserva los datos y la cuenta de mantenimiento" ($libros.totalItems -eq 1 -and $libros.items[0].numero -eq "Tomo de prueba")
    } finally {
        Detener $servidor
    }

    # --- El .bat y las preguntas ------------------------------------------------
    $otro = Join-Path $base "Otra instalacion"
    $salida = "`r`n" | cmd /c "`"$paquete\instalar_sistema.bat`" -Destino `"$otro`" -Confirmar -SinFirewall -SinAccesos -SinAbrir -Puerto $puerto" 2>&1 | Out-String
    Check "instalar_sistema.bat funciona desde una carpeta con espacios y parentesis" ($LASTEXITCODE -eq 0 -and $salida -match "Instalacion completa" -and (Test-Path "$otro\pb_data\data.db")) $salida

    # Una persona que responde Enter a todo (Read-Host simulado: la consola real no se puede automatizar).
    $enter = Join-Path $base "enter.ps1"
    Set-Content $enter @'
param($Script, $Destino, $Puerto)
function global:Read-Host {
    param([Parameter(Position = 0)]$Prompt, [switch]$AsSecureString)
    Write-Host "[pregunta] $Prompt"
    if ($AsSecureString) { return (New-Object Security.SecureString) }
    return ""
}
& $Script -Destino $Destino -SinFirewall -SinAccesos -SinAbrir -Puerto $Puerto
'@
    $nuevo = Join-Path $base "Con preguntas"
    $salida = & powershell -NoProfile -ExecutionPolicy Bypass -File $enter -Script "$paquete\instalar.ps1" -Destino $nuevo -Puerto $puerto 2>&1 | Out-String
    $preguntas = ([regex]::Matches($salida, "\[pregunta\]")).Count
    Check "responder Enter a todo instala con las opciones recomendadas" ($LASTEXITCODE -eq 0 -and $preguntas -eq 4 -and $salida -match "Sin clave" -and
        $salida -match "mantenimiento@parroquia.local" -and $salida -match "Contrasena de mantenimiento: \S{16}") $salida

    $conIA = Join-Path $base "Con IA"
    $env:SISTEMA_CLAVE_GEMINI = "clave-de-prueba-instalador"
    $env:IA_URL_BASE = "http://127.0.0.1:9"   # sin conexion: no sale nada a internet
    try {
        $r = Instalar $paquete $conIA
    } finally {
        Remove-Item Env:\SISTEMA_CLAVE_GEMINI, Env:\IA_URL_BASE
    }
    Check "guarda la clave de Gemini y activa la IA al instalar" ($r.codigo -eq 0 -and $r.salida -match "Clave guardada e IA activada") $r.salida
    Check "no muestra la clave de Gemini" ($r.salida -notmatch "clave-de-prueba-instalador") $r.salida

    # --- Errores -----------------------------------------------------------------
    $ajena = Join-Path $base "Carpeta ajena"
    New-Item -ItemType Directory $ajena | Out-Null
    Set-Content "$ajena\documento.txt" "no tocar"
    $r = Instalar $paquete $ajena
    Check "no instala sobre una carpeta que no es del sistema" ($r.codigo -ne 0 -and (Get-Content "$ajena\documento.txt") -eq "no tocar" -and -not (Test-Path "$ajena\pocketbase.exe")) $r.salida

    $suelto = Join-Path $base "solo el instalador"
    New-Item -ItemType Directory $suelto | Out-Null
    Copy-Item "$paquete\instalar.ps1" $suelto
    $r = Instalar $suelto (Join-Path $base "otro")
    Check "avisa si se abrio sin descomprimir" ($r.codigo -ne 0 -and $r.salida -match "Extraer todo") $r.salida
} finally {
    Get-Process pocketbase -ErrorAction SilentlyContinue | Where-Object { $_.Path -like "$base*" } | Stop-Process -Force
    Start-Sleep -Milliseconds 300
    Remove-Item $base -Recurse -Force -ErrorAction SilentlyContinue
}

if ($script:fallas) { Write-Host "`n$($script:fallas) fallas" -ForegroundColor Red; exit 1 }
Write-Host "`nTodo bien" -ForegroundColor Green
