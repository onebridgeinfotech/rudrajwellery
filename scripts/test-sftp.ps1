# Test Hostinger SFTP credentials from .env.deploy before deploy.
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
$port = if ($config['FTP_PORT']) { [int]$config['FTP_PORT'] } else { 65002 }
$keyPath = $config['SFTP_PRIVATE_KEY_PATH']

if (-not $keyPath) {
    $defaultKey = Join-Path $root "scripts\.deploy-keys\github_deploy"
    if (Test-Path $defaultKey) { $keyPath = $defaultKey }
}

Write-Host "Testing SFTP $user@${host_}:$port ..." -ForegroundColor Cyan

$py = @'
import paramiko, sys, os
host, port, user = sys.argv[1], int(sys.argv[2]), sys.argv[3]
password = sys.argv[4] if len(sys.argv) > 4 and sys.argv[4] else None
key_path = sys.argv[5] if len(sys.argv) > 5 and sys.argv[5] else None
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
try:
    kwargs = dict(hostname=host, port=port, username=user, timeout=25, allow_agent=False, look_for_keys=False)
    if key_path and os.path.isfile(key_path):
        kwargs['pkey'] = paramiko.Ed25519Key.from_private_key_file(key_path)
    elif password:
        kwargs['password'] = password
    else:
        print('FAIL: No password or SFTP_PRIVATE_KEY_PATH')
        sys.exit(1)
    client.connect(**kwargs)
    stdin, stdout, stderr = client.exec_command('pwd')
    print('OK SFTP login. Home:', stdout.read().decode().strip())
    client.close()
    sys.exit(0)
except Exception as e:
    print('FAIL SFTP:', e)
    sys.exit(1)
'@

pip install paramiko -q 2>$null
$keyArg = if ($keyPath) { $keyPath } else { '' }
python -c $py $host_ $port $user $pass $keyArg
if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "Fix: hPanel -> Remote Access -> add SSH public key OR reset FTP password" -ForegroundColor Yellow
    Write-Host "Update .env.deploy, then run test again." -ForegroundColor Yellow
    exit 1
}

Write-Host "Credentials OK. Safe to deploy." -ForegroundColor Green
