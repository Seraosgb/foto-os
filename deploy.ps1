param (
    [string]$msg = "feat: atualizacao automatica do projeto"
)

$ErrorActionPreference = "Continue"

# ============================================================
# SCALLE ERP - DEPLOY WINDOWS -> LINUX / HOSTOO
# ============================================================

$ProjetoLocal = $PSScriptRoot

$SSH_Host = "ssh.samtooweb.com"
$SSH_Port = 44886
$SSH_User = "vtfsiqb3"
$SSH_Password = "hOjTaC9E%k-o&vJ_"
$ProjetoRemoto = "/home/vtfsiqb3/public_html/foto-os"

# ============================================================
# FUNÇÕES
# ============================================================

function Write-Step ([string]$Text) {
    Write-Host "`n============================================================" -ForegroundColor Cyan
    Write-Host ">>> $Text" -ForegroundColor Cyan
    Write-Host "============================================================" -ForegroundColor Cyan
}

function Stop-Deploy ([string]$Text) {
    Write-Host "`n============================================================" -ForegroundColor Red
    Write-Host "ERRO: $Text" -ForegroundColor Red
    Write-Host "============================================================" -ForegroundColor Red
    Set-Location $ProjetoLocal
    exit 1
}

# ============================================================
# EXECUÇÃO DO DEPLOY
# ============================================================

Write-Step "0. Localizando plink.exe"
$PlinkLocal = Join-Path $ProjetoLocal "plink.exe"
if (Test-Path $PlinkLocal) { $PlinkPath = $PlinkLocal }
else {
    $PlinkCommand = Get-Command plink.exe -ErrorAction SilentlyContinue
    if ($null -eq $PlinkCommand) { Stop-Deploy "plink.exe não encontrado no PATH nem na raiz." }
    $PlinkPath = $PlinkCommand.Source
}
Write-Host "Usando: $PlinkPath" -ForegroundColor Gray

Write-Step "1. Compilando o Frontend"
$FrontendPath = Join-Path $ProjetoLocal "frontend"
Set-Location $FrontendPath
npm run build
if ($LASTEXITCODE -ne 0) { Stop-Deploy "O build do frontend falhou." }

Write-Step "2. Gerando public\dist.zip"
Set-Location $ProjetoLocal
$DistPath = Join-Path $ProjetoLocal "frontend\dist"
$ZipPath  = Join-Path $ProjetoLocal "public\dist.zip"

Remove-Item $ZipPath -Force -ErrorAction SilentlyContinue
Add-Type -AssemblyName System.IO.Compression.FileSystem

$ZipArchive = [System.IO.Compression.ZipFile]::Open($ZipPath, [System.IO.Compression.ZipArchiveMode]::Create)
Get-ChildItem -Path $DistPath -Recurse -File | ForEach-Object {
    $RelativePath = $_.FullName.Substring($DistPath.Length + 1)
    $EntryName = $RelativePath -replace '\\', '/'
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($ZipArchive, $_.FullName, $EntryName) | Out-Null
}
$ZipArchive.Dispose()
if (-not (Test-Path $ZipPath)) { Stop-Deploy "Falha ao criar o ZIP." }

Write-Step "3. Sincronizando com GitHub"
Set-Location $ProjetoLocal

# Verifica se há algo para commitar antes de tentar o commit
$GitStatus = git status --porcelain

if ([string]::IsNullOrWhiteSpace($GitStatus)) {
    Write-Host ">>> Nenhuma alteracao local. Pulando o git commit..." -ForegroundColor Yellow
} else {
    git add -A
    git commit -m "$msg"

    if ($LASTEXITCODE -ne 0) {
        Stop-Deploy "Falha critica no git commit."
    } else {
        Write-Host ">>> Commit criado com sucesso." -ForegroundColor Green
    }
}

Write-Host ">>> Sincronizando com origin main..." -ForegroundColor Cyan
git push origin main

if ($LASTEXITCODE -ne 0) {
    Stop-Deploy "Falha ao enviar para o repositório remoto."
}
Write-Host ">>> Repositório sincronizado!" -ForegroundColor Green


Write-Step "4. Preparando Rotina Linux Hostoo"
$RawScript = @"
#!/bin/bash
set -e

echo ">>> [1/7] Entrando no projeto..."
cd "$ProjetoRemoto"
git reset --hard HEAD
git pull origin main

echo ">>> [2/7] Atualizando Pacotes Laravel..."
composer install --no-dev --optimize-autoloader 2>/dev/null || true

echo ">>> [3/7] Manipulando Frontend..."
rm -rf public/assets public/app/assets public/index.html public/app/index.html
mkdir -p public/app
unzip -o public/dist.zip -d public/
unzip -o public/dist.zip -d public/app/

echo ">>> [4/7] Sincronizando Assets..."
[ -d "public/app/assets" ] && cp -rf public/app/assets public/ || true
[ -d "public/assets" ] && cp -rf public/assets public/app/ || true

echo ">>> [5/7] Permissoes e Limpeza..."
chmod -R 755 public/assets public/app 2>/dev/null || true
rm -f public/dist.zip

echo ">>> [6/7] Resetando OPcache (LiteSpeed)..."
killall -9 lsphp 2>/dev/null || true

echo ">>> [7/7] Otimizando Laravel e Banco de Dados..."
php artisan migrate --force
php artisan optimize:clear
php artisan optimize

echo ">>> DEPLOY LINUX CONCLUIDO COM SUCESSO!"
"@

$RawScriptUnix = $RawScript -replace "`r`n", "`n"
$Base64Script = [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($RawScriptUnix))

Write-Step "5. Conectando e Executando na Hostoo"
$RemoteCommand = "echo $Base64Script | base64 -d | bash"

$ProcessInfo = New-Object System.Diagnostics.ProcessStartInfo
$ProcessInfo.FileName = $PlinkPath
$ProcessInfo.Arguments = "-ssh -P $SSH_Port -l `"$SSH_User`" -pw `"$SSH_Password`" -batch `"$SSH_Host`" `"$RemoteCommand`""
$ProcessInfo.UseShellExecute = $false
$ProcessInfo.CreateNoWindow = $true
$ProcessInfo.RedirectStandardOutput = $true
$ProcessInfo.RedirectStandardError = $true

$Process = New-Object System.Diagnostics.Process
$Process.StartInfo = $ProcessInfo

try {
    $Process.Start() | Out-Null
    $StdOut = $Process.StandardOutput.ReadToEnd()
    $Process.WaitForExit()

    if ($StdOut) { Write-Host "`n$StdOut" }

    if ($Process.ExitCode -ne 0) {
        Stop-Deploy "Código de saída do Plink: $($Process.ExitCode). Verifique a conexão SSH."
    }
}
catch { Stop-Deploy $_.Exception.Message }
finally { if ($Process) { $Process.Dispose() } }

Write-Host "`n============================================================" -ForegroundColor Green
Write-Host "          DEPLOY CONCLUIDO COM SUCESSO!" -ForegroundColor Green
Write-Host "============================================================`n" -ForegroundColor Green
