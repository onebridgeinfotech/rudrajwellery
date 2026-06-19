# Restore trashed WooCommerce products and resolve duplicate SKUs on the live site.
param(
    [hashtable]$Config
)

if (-not $Config) {
    $root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
    $envFile = Join-Path $root ".env.deploy"
    if (-not (Test-Path $envFile)) { return }
    $Config = @{}
    Get-Content $envFile | ForEach-Object {
        $line = $_.Trim()
        if ($line -and -not $line.StartsWith('#') -and $line -match '^([^=]+)=(.*)$') {
            $Config[$matches[1].Trim()] = $matches[2].Trim()
        }
    }
}

$siteUrl = if ($Config['SITE_URL']) { $Config['SITE_URL'].TrimEnd('/') } else { 'https://www.rudrajewellery.co.in' }
$remoteTheme = $Config['FTP_REMOTE_THEME'].TrimEnd('/')
$restoreKey = [guid]::NewGuid().ToString('N')
$keyName = 'jwellery-catalog-sync.key'
$remoteKey = ($remoteTheme -replace 'themes/[^/]+/?$', '') + "uploads/$keyName"
$tempKey = Join-Path $env:TEMP "jwellery-catalog-restore-key-$restoreKey"
Set-Content -Path $tempKey -Value $restoreKey -Encoding ASCII -NoNewline

$server = $Config['FTP_SERVER']
$user = $Config['FTP_USERNAME']
$pass = $Config['FTP_PASSWORD']
$keyRemoteUrl = "ftp://${server}${remoteKey}"

Write-Host "Restoring live catalog from trash ..." -ForegroundColor Cyan
& curl.exe --silent --show-error --ftp-create-dirs `
    --user "${user}:${pass}" `
    -T $tempKey $keyRemoteUrl
if ($LASTEXITCODE -ne 0) {
    Remove-Item $tempKey -Force -ErrorAction SilentlyContinue
    Write-Host "Catalog restore key upload failed (FTP)." -ForegroundColor Red
    exit 1
}

Start-Sleep -Seconds 2
$restoreUrl = "${siteUrl}/?jwellery_catalog_restore=${restoreKey}"
$response = & curl.exe -sL -A "JwelleryDeploy/1.0" --max-time 180 $restoreUrl
Write-Host $response -ForegroundColor Gray

& curl.exe --silent --user "${user}:${pass}" -Q "DELE ${remoteKey}" "ftp://${server}/" 2>$null | Out-Null
Remove-Item $tempKey -Force -ErrorAction SilentlyContinue

if ($response -notmatch '"success":true') {
    Write-Host "Catalog restore failed." -ForegroundColor Red
    exit 1
}

Write-Host "Live catalog restore complete." -ForegroundColor Green
