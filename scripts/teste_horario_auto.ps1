param(
  [string]$ProjectPath = "c:\laragon\www\NA_virtual_clone",
  [string]$Url = "http://na_virtual_clone.local/reunioes-virtuais",
  [switch]$RunSync
)

$ErrorActionPreference = "Stop"

function Step($msg) { Write-Host "`n=== $msg ===" -ForegroundColor Cyan }

function Invoke-LaravelPhp([string]$Code) {
  $cmd = "require 'vendor/autoload.php'; `$app = require 'bootstrap/app.php'; `$kernel = `$app->make('Illuminate\\Contracts\\Console\\Kernel'); `$kernel->bootstrap(); " + $Code
  php -r $cmd
}

Set-Location $ProjectPath
if (!(Test-Path ".\artisan")) { throw "artisan não encontrado em $ProjectPath" }

Step "1) Timezone e hora atual do Laravel"
$tz = Invoke-LaravelPhp "echo config('app.timezone'), PHP_EOL; echo now()->format('c'), PHP_EOL;"
$tz

Step "2) data-server-time no HTML (2 refreshes)"
$h1 = curl.exe -s $Url
$d1 = [regex]::Match($h1, 'data-server-time="([^"]+)"').Groups[1].Value
Write-Host "HTML #1: $d1"

Start-Sleep -Seconds 3

$h2 = curl.exe -s $Url
$d2 = [regex]::Match($h2, 'data-server-time="([^"]+)"').Groups[1].Value
Write-Host "HTML #2: $d2"

if ($d1 -and $d2 -and ([datetimeoffset]$d2 -gt [datetimeoffset]$d1)) {
  Write-Host "OK: refresh está trazendo hora nova no HTML." -ForegroundColor Green
} else {
  Write-Host "ALERTA: refresh não trouxe hora nova no HTML." -ForegroundColor Yellow
}

Step "3) Verificação de fallback nos logs"
$logFiles = Get-ChildItem ".\storage\logs\laravel*.log" -ErrorAction SilentlyContinue
if ($logFiles) {
  Select-String -Path $logFiles.FullName -Pattern "Fallback da homepage acionado" | Select-Object -Last 5
} else {
  Write-Host "Nenhum log encontrado."
}

if ($RunSync) {
  Step "4) Sync manual"
  php artisan na:sync-virtual-meetings
}

Step "Fim"
