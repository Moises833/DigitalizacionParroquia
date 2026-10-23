# Genera la carpeta portable que se copia al equipo de la parroquia:
#   dist\SistemaBautismos\  (y dist\SistemaBautismos.zip)
# Requiere Node.js solo en el equipo de desarrollo; la parroquia no necesita instalar nada.
# Uso:  powershell -ExecutionPolicy Bypass -File scripts\empaquetar.ps1

$ErrorActionPreference = "Stop"
$raiz = Split-Path $PSScriptRoot -Parent
. (Join-Path $PSScriptRoot "pocketbase.ps1")

$destino = Join-Path $raiz "dist\SistemaBautismos"

Write-Host "[1/4] Compilando la interfaz..."
Push-Location (Join-Path $raiz "web")
try {
    npm ci --no-audit --no-fund
    if ($LASTEXITCODE -ne 0) { throw "npm ci fallo" }
    npm run build
    if ($LASTEXITCODE -ne 0) { throw "npm run build fallo" }
} finally {
    Pop-Location
}

Write-Host "[2/4] Verificando PocketBase $PocketBaseVersion..."
$exe = Obtener-PocketBase (Join-Path $raiz "servidor")

Write-Host "[3/4] Armando $destino..."
if (Test-Path $destino) { Remove-Item $destino -Recurse -Force }
New-Item -ItemType Directory -Force $destino | Out-Null
Copy-Item $exe $destino
foreach ($dir in "pb_migrations", "pb_hooks", "pb_public") {
    Copy-Item (Join-Path $raiz "servidor\$dir") (Join-Path $destino $dir) -Recurse
}
Copy-Item (Join-Path $raiz "instalador\*") $destino

Write-Host "[4/4] Comprimiendo..."
$zip = "$destino.zip"
if (Test-Path $zip) { Remove-Item $zip -Force }
Compress-Archive -Path $destino -DestinationPath $zip

Write-Host "Listo: $zip" -ForegroundColor Green
