# Upload theme + plugin to Hostinger via SFTP (reads .env.deploy). Runs from YOUR PC network.
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$envFile = Join-Path $root ".env.deploy"

if (-not (Test-Path $envFile)) {
    Write-Host "Missing .env.deploy — copy deploy.env.example to .env.deploy" -ForegroundColor Red
    exit 1
}

$config = @{}
Get-Content $envFile | ForEach-Object {
    $line = $_.Trim()
    if ($line -and -not $line.StartsWith('#') -and $line -match '^([^=]+)=(.*)$') {
        $config[$matches[1].Trim()] = $matches[2].Trim()
    }
}

$required = @('FTP_SERVER', 'FTP_USERNAME', 'FTP_REMOTE_THEME', 'FTP_REMOTE_PLUGIN')
foreach ($key in $required) {
    if (-not $config[$key]) {
        Write-Host "Missing $key in .env.deploy" -ForegroundColor Red
        exit 1
    }
}

$keyPath = $config['SFTP_PRIVATE_KEY_PATH']
if (-not $keyPath) {
    $defaultKey = Join-Path $root "scripts\.deploy-keys\github_deploy"
    if (Test-Path $defaultKey) { $keyPath = $defaultKey }
}

if (-not $keyPath -and -not $config['FTP_PASSWORD']) {
    Write-Host "Missing FTP_PASSWORD or SFTP_PRIVATE_KEY_PATH in .env.deploy" -ForegroundColor Red
    exit 1
}

$port = if ($config['FTP_PORT']) { [int]$config['FTP_PORT'] } else { 65002 }
$protocol = if ($config['FTP_PROTOCOL']) { $config['FTP_PROTOCOL'].ToLower() } elseif ($port -eq 65002) { 'sftp' } else { 'ftp' }

function Deploy-Folder {
    param(
        [string]$LocalDir,
        [string]$RemoteDir,
        [string]$Label
    )

    if (-not (Test-Path $LocalDir)) {
        Write-Host "Skip $Label — folder not found: $LocalDir" -ForegroundColor Yellow
        return
    }

    Write-Host "Deploying $Label..." -ForegroundColor Cyan
    Write-Host "  Local:  $LocalDir" -ForegroundColor Gray
    Write-Host "  Remote: $RemoteDir" -ForegroundColor Gray

    $winScp = Get-Command winscp.com -ErrorAction SilentlyContinue
    if (-not $winScp) {
        Write-Host "Install WinSCP and add winscp.com to PATH: https://winscp.net/" -ForegroundColor Red
        exit 1
    }

    if ($keyPath -and (Test-Path $keyPath)) {
        $openLine = "open ${protocol}://${config['FTP_USERNAME']}@${config['FTP_SERVER']}:$port/ -privatekey=`"$keyPath`" -hostkey=*"
    } else {
        $openLine = "open ${protocol}://${config['FTP_USERNAME']}:$([Uri]::EscapeDataString($config['FTP_PASSWORD']))@${config['FTP_SERVER']}:$port/ -hostkey=*"
    }

    $script = @"
option batch abort
option confirm off
$openLine
synchronize remote -mirror -criteria=time "$LocalDir" "$RemoteDir"
exit
"@
    $tempScript = Join-Path $env:TEMP "jwellery-deploy-$(Get-Random).txt"
    Set-Content -Path $tempScript -Value $script -Encoding UTF8
    & winscp.com /script=$tempScript
    $code = $LASTEXITCODE
    Remove-Item $tempScript -Force -ErrorAction SilentlyContinue
    if ($code -ne 0) { exit $code }
}

$themeLocal = Join-Path $root "wordpress-theme\jwellery-jewelry"
$pluginLocal = Join-Path $root "wordpress-plugin\jewelry-upi-store"

Deploy-Folder -LocalDir $themeLocal -RemoteDir $config['FTP_REMOTE_THEME'] -Label "theme"
Deploy-Folder -LocalDir $pluginLocal -RemoteDir $config['FTP_REMOTE_PLUGIN'] -Label "plugin"

Write-Host ""
Write-Host "Deploy complete. Purge LiteSpeed cache in wp-admin if changes do not show." -ForegroundColor Green
