# Upload one-time key and trigger catalog sync on the live site.
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
$syncKey = [guid]::NewGuid().ToString('N')
$keyName = 'jwellery-catalog-sync.key'
$remoteKey = ($remoteTheme -replace 'themes/[^/]+/?$', '') + "uploads/$keyName"
$tempKey = Join-Path $env:TEMP "jwellery-catalog-sync-key-$syncKey"
Set-Content -Path $tempKey -Value $syncKey -Encoding ASCII -NoNewline

$server = $Config['FTP_SERVER']
$user = $Config['FTP_USERNAME']
$pass = $Config['FTP_PASSWORD']
$keyRemoteUrl = "ftp://${server}${remoteKey}"

Write-Host "Syncing live catalog ..." -ForegroundColor Cyan
& curl.exe --silent --show-error --ftp-create-dirs `
    --user "${user}:${pass}" `
    -T $tempKey $keyRemoteUrl
if ($LASTEXITCODE -ne 0) {
    Remove-Item $tempKey -Force -ErrorAction SilentlyContinue
    Write-Host "Catalog sync key upload failed (FTP)." -ForegroundColor Red
    exit 1
}

Start-Sleep -Seconds 2
$offset = 0
$maxRounds = 20
for ($round = 1; $round -le $maxRounds; $round++) {
    $syncUrl = "${siteUrl}/?jwellery_catalog_sync=${syncKey}&offset=${offset}"
    $response = & curl.exe -sL -A "JwelleryDeploy/1.0" --max-time 120 $syncUrl
    Write-Host "Batch $round : $response" -ForegroundColor Gray
    if ($response -notmatch '"success":true') {
        Write-Host "Catalog sync failed on batch $round." -ForegroundColor Red
        exit 1
    }
    if ($response -match '"done":true') {
        Write-Host "Live catalog synced." -ForegroundColor Green
        break
    }
    if ($response -match '"offset":(\d+)') {
        $offset = [int]$Matches[1]
    }
    $syncKey = [guid]::NewGuid().ToString('N')
    Set-Content -Path $tempKey -Value $syncKey -Encoding ASCII -NoNewline
    & curl.exe --silent --show-error --ftp-create-dirs --user "${user}:${pass}" -T $tempKey $keyRemoteUrl | Out-Null
    Start-Sleep -Seconds 1
}
if ($round -gt $maxRounds) {
    Write-Host "Catalog sync stopped after $maxRounds batches - re-run if needed." -ForegroundColor Yellow
    & curl.exe --silent --user "${user}:${pass}" -Q "DELE ${remoteKey}" "ftp://${server}/" 2>$null | Out-Null
    Remove-Item $tempKey -Force -ErrorAction SilentlyContinue
    exit 1
}

& curl.exe --silent --user "${user}:${pass}" -Q "DELE ${remoteKey}" "ftp://${server}/" 2>$null | Out-Null
Remove-Item $tempKey -Force -ErrorAction SilentlyContinue
