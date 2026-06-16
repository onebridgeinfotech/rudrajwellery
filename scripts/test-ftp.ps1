# Test Hostinger FTP/SFTP login from .env.deploy
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$envFile = Join-Path $root ".env.deploy"

if (-not (Test-Path $envFile)) {
    Write-Host "Missing .env.deploy" -ForegroundColor Red
    exit 1
}

$config = @{}
Get-Content $envFile | ForEach-Object {
    $line = $_.Trim()
    if ($line -and -not $line.StartsWith('#') -and $line -match '^([^=]+)=(.*)$') {
        $config[$matches[1].Trim()] = $matches[2].Trim()
    }
}

$host_ = $config['FTP_SERVER']
$user = $config['FTP_USERNAME']
$pass = $config['FTP_PASSWORD']
$port = if ($config['FTP_PORT']) { [int]$config['FTP_PORT'] } else { 21 }
$protocol = if ($config['FTP_PROTOCOL']) { $config['FTP_PROTOCOL'].ToLower() } else { 'ftp' }

Write-Host "Testing $protocol $user@${host_}:$port ..." -ForegroundColor Cyan

if ($protocol -eq 'ftp' -and $port -eq 21) {
    $env:FTP_TEST_PASS = $pass
    $py = @'
from ftplib import FTP
import os, sys
host, user = sys.argv[1], sys.argv[2]
password = os.environ['FTP_TEST_PASS']
try:
    ftp = FTP()
    ftp.connect(host, 21, timeout=25)
    ftp.login(user, password)
    print('OK FTP login. PWD:', ftp.pwd())
    ftp.quit()
except Exception as e:
    print('FAIL FTP:', e)
    raise SystemExit(1)
'@
    python -c $py $host_ $user
    Remove-Item Env:FTP_TEST_PASS -ErrorAction SilentlyContinue
} else {
    & (Join-Path $root "scripts\test-sftp.ps1")
    exit $LASTEXITCODE
}

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "Fix: hPanel -> FTP Accounts -> Change FTP password -> update .env.deploy" -ForegroundColor Yellow
    exit 1
}

Write-Host "Credentials OK. Safe to deploy." -ForegroundColor Green
