# Sync theme + plugin to local WordPress for preview before live deploy.
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$envFile = Join-Path $root "local.env"

function Read-EnvFile {
    param([string]$Path)
    $config = @{}
    if (-not (Test-Path $Path)) { return $config }
    Get-Content $Path | ForEach-Object {
        $line = $_.Trim()
        if ($line -and -not $line.StartsWith('#') -and $line -match '^([^=]+)=(.*)$') {
            $config[$matches[1].Trim()] = $matches[2].Trim()
        }
    }
    return $config
}

$config = Read-EnvFile $envFile

if (-not $config['LOCAL_WP_THEME_PATH'] -or -not $config['LOCAL_WP_PLUGIN_PATH']) {
    Write-Host ""
    Write-Host "Local preview not configured." -ForegroundColor Yellow
    Write-Host "1. Copy local.env.example to local.env" -ForegroundColor Gray
    Write-Host "2. Set LOCAL_SITE_URL, LOCAL_WP_THEME_PATH, LOCAL_WP_PLUGIN_PATH" -ForegroundColor Gray
    Write-Host ""
    return $false
}

$themeSrc = Join-Path $root "wordpress-theme\jwellery-jewelry"
$pluginSrc = Join-Path $root "wordpress-plugin\jewelry-upi-store"
$themeDst = $config['LOCAL_WP_THEME_PATH']
$pluginDst = $config['LOCAL_WP_PLUGIN_PATH']

foreach ($pair in @(
    @{ Label = "theme"; Src = $themeSrc; Dst = $themeDst }
    @{ Label = "plugin"; Src = $pluginSrc; Dst = $pluginDst }
)) {
    if (-not (Test-Path $pair.Src)) {
        Write-Host "Missing source: $($pair.Src)" -ForegroundColor Red
        return $false
    }
    if (-not (Test-Path $pair.Dst)) {
        New-Item -ItemType Directory -Path $pair.Dst -Force | Out-Null
    }
    Write-Host "Syncing $($pair.Label) -> $($pair.Dst)" -ForegroundColor Cyan
    & robocopy $pair.Src $pair.Dst /E /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null
    if ($LASTEXITCODE -ge 8) {
        Write-Host "Robocopy failed for $($pair.Label) (exit $LASTEXITCODE)" -ForegroundColor Red
        return $false
    }
}

$siteUrl = $config['LOCAL_SITE_URL']
if ($siteUrl) {
    Write-Host ""
    Write-Host "Local preview: $siteUrl" -ForegroundColor Green
    Start-Process $siteUrl
}

return $true
