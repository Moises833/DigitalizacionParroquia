# Version fijada de PocketBase y descarga verificada por checksum.
# Para actualizar: cambiar la version y el SHA-256 (del checksums.txt de la release).
# Sin acentos: PowerShell 5.1 lee los .ps1 sin BOM con otra codificacion.

$PocketBaseVersion = "0.40.5"
$PocketBaseSha256 = "d0980d1f3a8097ff4c03a876e8c98f3008a83c0d42cc03b636bc92b2d4788d1c"

# Devuelve la ruta a <carpeta>\pocketbase.exe, descargandolo si falta o si es otra version.
function Obtener-PocketBase([string]$carpeta) {
    $exe = Join-Path $carpeta "pocketbase.exe"
    if (Test-Path $exe) {
        $actual = (& $exe --version) -replace "^.*version\s+", ""
        if ($actual -eq $PocketBaseVersion) { return $exe }
    }

    $nombre = "pocketbase_${PocketBaseVersion}_windows_amd64.zip"
    $url = "https://github.com/pocketbase/pocketbase/releases/download/v$PocketBaseVersion/$nombre"
    $temporal = Join-Path ([IO.Path]::GetTempPath()) ("pb_" + [guid]::NewGuid())
    New-Item -ItemType Directory $temporal | Out-Null
    try {
        $zip = Join-Path $temporal $nombre
        Invoke-WebRequest $url -OutFile $zip -UseBasicParsing
        $hash = (Get-FileHash $zip -Algorithm SHA256).Hash.ToLower()
        if ($hash -ne $PocketBaseSha256) { throw "Checksum incorrecto para $nombre ($hash)" }
        Expand-Archive $zip -DestinationPath $temporal
        Copy-Item (Join-Path $temporal "pocketbase.exe") $exe -Force
    } finally {
        Remove-Item $temporal -Recurse -Force
    }
    return $exe
}
