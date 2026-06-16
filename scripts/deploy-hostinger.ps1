# Upload theme + plugin to Hostinger via FTP/SFTP (reads .env.deploy).
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$envFile = Join-Path $root ".env.deploy"

if (-not (Test-Path $envFile)) {
    Write-Host "Missing .env.deploy - copy deploy.env.example to .env.deploy" -ForegroundColor Red
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

if (-not $config['FTP_PASSWORD']) {
    Write-Host "Missing FTP_PASSWORD in .env.deploy" -ForegroundColor Red
    exit 1
}

$port = if ($config['FTP_PORT']) { [int]$config['FTP_PORT'] } else { 21 }
$protocol = if ($config['FTP_PROTOCOL']) { $config['FTP_PROTOCOL'].ToLower() } else { 'ftp' }

$winScp = Get-Command winscp.com -ErrorAction SilentlyContinue

function Deploy-Folder-Curl {
    param(
        [string]$LocalDir,
        [string]$RemoteDir,
        [string]$Label
    )

    $curl = Get-Command curl.exe -ErrorAction SilentlyContinue
    if (-not $curl) {
        Write-Host "Install WinSCP (https://winscp.net/) or ensure curl.exe is available." -ForegroundColor Red
        exit 1
    }

    Write-Host "Deploying $Label via curl FTP..." -ForegroundColor Cyan
    Get-ChildItem -Path $LocalDir -Recurse -File | ForEach-Object {
        $relative = $_.FullName.Substring($LocalDir.Length).TrimStart('\').Replace('\', '/')
        $remoteUrl = "ftp://$($config['FTP_SERVER'])$($RemoteDir.TrimEnd('/'))/$relative"
        & curl.exe --silent --show-error --ftp-create-dirs `
            --user "$($config['FTP_USERNAME']):$($config['FTP_PASSWORD'])" `
            -T $_.FullName $remoteUrl
        if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
    }
}

function Deploy-Folder {
    param(
        [string]$LocalDir,
        [string]$RemoteDir,
        [string]$Label
    )

    if (-not (Test-Path $LocalDir)) {
        Write-Host "Skip $Label - folder not found: $LocalDir" -ForegroundColor Yellow
        return
    }

    Write-Host "Deploying $Label..." -ForegroundColor Cyan
    Write-Host "  Local:  $LocalDir" -ForegroundColor Gray
    Write-Host "  Remote: $RemoteDir" -ForegroundColor Gray

    if (-not $winScp) {
        Deploy-Folder-Curl -LocalDir $LocalDir -RemoteDir $RemoteDir -Label $Label
        return
    }

    $escapedPass = [Uri]::EscapeDataString($config['FTP_PASSWORD'])
    $openLine = "open ${protocol}://$($config['FTP_USERNAME']):${escapedPass}@$($config['FTP_SERVER']):$port/ -hostkey=*"

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
