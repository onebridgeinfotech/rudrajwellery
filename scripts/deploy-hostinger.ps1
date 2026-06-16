# Upload theme + plugin folders to Hostinger via FTP (reads .env.deploy)
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$envFile = Join-Path $root ".env.deploy"

if (-not (Test-Path $envFile)) {
    Write-Host "Missing .env.deploy — copy deploy.env.example to .env.deploy and add FTP details." -ForegroundColor Red
    exit 1
}

$config = @{}
Get-Content $envFile | ForEach-Object {
    $line = $_.Trim()
    if ($line -and -not $line.StartsWith('#') -and $line -match '^([^=]+)=(.*)$') {
        $config[$matches[1].Trim()] = $matches[2].Trim()
    }
}

$required = @('FTP_SERVER', 'FTP_USERNAME', 'FTP_PASSWORD', 'FTP_REMOTE_THEME', 'FTP_REMOTE_PLUGIN')
foreach ($key in $required) {
    if (-not $config[$key]) {
        Write-Host "Missing $key in .env.deploy" -ForegroundColor Red
        exit 1
    }
}

$port = if ($config['FTP_PORT']) { [int]$config['FTP_PORT'] } else { 21 }

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
    if ($winScp) {
        $script = @"
option batch abort
option confirm off
open ftp://${config['FTP_USERNAME']}:$([Uri]::EscapeDataString($config['FTP_PASSWORD']))@${config['FTP_SERVER']}:$port/
synchronize remote -mirror -criteria=time "$LocalDir" "$RemoteDir"
exit
"@
        $tempScript = Join-Path $env:TEMP "jwellery-deploy-$(Get-Random).txt"
        Set-Content -Path $tempScript -Value $script -Encoding UTF8
        & winscp.com /script=$tempScript
        Remove-Item $tempScript -Force -ErrorAction SilentlyContinue
        if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
        return
    }

    $curl = Get-Command curl.exe -ErrorAction SilentlyContinue
    if (-not $curl) {
        Write-Host "Install WinSCP (winscp.com in PATH) or use GitHub Actions deploy." -ForegroundColor Red
        Write-Host "GitHub: add secrets from deploy.env.example, then push to main." -ForegroundColor Yellow
        exit 1
    }

    Get-ChildItem -Path $LocalDir -Recurse -File | ForEach-Object {
        $relative = $_.FullName.Substring($LocalDir.Length).TrimStart('\').Replace('\', '/')
        $remoteUrl = "ftp://$($config['FTP_SERVER'])$($RemoteDir.TrimEnd('/'))/$relative"
        & curl.exe --silent --show-error --ftp-create-dirs `
            --user "$($config['FTP_USERNAME']):$($config['FTP_PASSWORD'])" `
            -T $_.FullName $remoteUrl
        if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
    }
}

$themeLocal = Join-Path $root "wordpress-theme\jwellery-jewelry"
$pluginLocal = Join-Path $root "wordpress-plugin\jewelry-upi-store"

Deploy-Folder -LocalDir $themeLocal -RemoteDir $config['FTP_REMOTE_THEME'] -Label "theme"
Deploy-Folder -LocalDir $pluginLocal -RemoteDir $config['FTP_REMOTE_PLUGIN'] -Label "plugin"

Write-Host ""
Write-Host "Deploy complete. Purge LiteSpeed cache in wp-admin if changes do not show." -ForegroundColor Green
