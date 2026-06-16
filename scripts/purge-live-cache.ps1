# Hit admin-ajax on the live site to purge LiteSpeed / page cache after FTP deploy.
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
$purgeKey = [guid]::NewGuid().ToString('N')
$keyName = '.deploy-purge-key'
$tempKey = Join-Path $env:TEMP "jwellery-deploy-purge-key-$purgeKey"
Set-Content -Path $tempKey -Value $purgeKey -Encoding ASCII -NoNewline

$server = $Config['FTP_SERVER']
$user = $Config['FTP_USERNAME']
$pass = $Config['FTP_PASSWORD']
$keyRemoteUrl = "ftp://${server}${remoteTheme}/${keyName}"

Write-Host "Purging live cache via admin-ajax ..." -ForegroundColor Cyan
& curl.exe --silent --show-error --ftp-create-dirs `
    --user "${user}:${pass}" `
    -T $tempKey $keyRemoteUrl
if ($LASTEXITCODE -ne 0) {
    Remove-Item $tempKey -Force -ErrorAction SilentlyContinue
    Write-Host "Cache purge key upload failed (FTP)." -ForegroundColor Yellow
    return
}

Start-Sleep -Seconds 2
$ajaxUrl = "${siteUrl}/wp-admin/admin-ajax.php?action=jwellery_deploy_purge&key=${purgeKey}"
$response = & curl.exe -sL -A "JwelleryDeploy/1.0" $ajaxUrl
Write-Host "Purge response: $response" -ForegroundColor Gray

& curl.exe --silent --user "${user}:${pass}" -Q "DELE ${remoteTheme}/${keyName}" "ftp://${server}/" 2>$null | Out-Null
Remove-Item $tempKey -Force -ErrorAction SilentlyContinue

if ($response -match '"success":true' -or $response -match 'purged') {
    Write-Host "Live cache purged." -ForegroundColor Green
} else {
    Write-Host "Cache purge may have failed - purge LiteSpeed manually in wp-admin." -ForegroundColor Yellow
}
