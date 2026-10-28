# Abre el Sistema de Bautismos. Lo usan los accesos directos del Escritorio y del
# inicio de Windows: el servidor corre oculto (sin ventana negra que se pueda
# cerrar por error) y el sistema se abre en su propia ventana, como un programa.
#   -SinNavegador: solo inicia el servidor (al encender la PC).
# Si el servidor no arranca, muestra un aviso con el error.
# Para ver el servidor con su ventana (soporte tecnico): iniciar_sistema.bat.
# Sin acentos: PowerShell 5.1 lo leeria con otra codificacion.

param([switch]$SinNavegador, [int]$Puerto = 8090)

$carpeta = $PSScriptRoot
$url = "http://127.0.0.1:$Puerto/"

function Responde {
    try {
        Invoke-WebRequest "${url}api/health" -UseBasicParsing -TimeoutSec 2 | Out-Null
        return $true
    } catch {
        return $false
    }
}

function Avisar($mensaje) {
    Add-Type -AssemblyName System.Windows.Forms
    [System.Windows.Forms.MessageBox]::Show($mensaje, "Sistema de Bautismos", "OK", "Warning") | Out-Null
}

if (-not (Responde)) {
    $datos = Join-Path $carpeta "pb_data"
    New-Item -ItemType Directory -Force $datos | Out-Null
    $errores = Join-Path $datos "servidor_errores.log"
    # A traves de cmd y sin heredar nada de quien lo abre: el servidor queda
    # independiente (sigue andando al cerrar este script) y oculto.
    $exe = Join-Path $carpeta "pocketbase.exe"
    $salida = Join-Path $datos "servidor.log"
    Start-Process cmd.exe -WorkingDirectory $carpeta -WindowStyle Hidden -ArgumentList `
        "/c `"`"$exe`" serve --http=0.0.0.0:$Puerto --dev=false > `"$salida`" 2> `"$errores`"`""
    $listo = $false
    for ($i = 0; $i -lt 80 -and -not $listo; $i++) {
        Start-Sleep -Milliseconds 250
        $listo = Responde
    }
    if (-not $listo) {
        $detalle = (Get-Content $errores -Tail 6 -ErrorAction SilentlyContinue) -join "`n"
        Avisar ("El sistema no pudo iniciarse.`n`n$detalle`n`n" +
            "Saque una foto de este mensaje y avise a soporte. Si otro programa usa el puerto $Puerto, reinicie la PC.")
        exit 1
    }
}
if ($SinNavegador) { exit 0 }

# En su propia ventana con Edge (viene con Windows 10 y 11); si no, el navegador predeterminado.
$edge = @("${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe",
          "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe") |
    Where-Object { Test-Path $_ } | Select-Object -First 1
if ($edge) {
    Start-Process $edge "--app=$url"
} else {
    Start-Process $url
}
