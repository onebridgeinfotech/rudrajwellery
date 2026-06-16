# Commit -> push -> deploy to Hostinger FROM YOUR PC (GitHub cloud cannot reach Hostinger).
param(
    [Parameter(Mandatory = $true, Position = 0)]
    [string]$Message,

    [switch]$SkipLocal,
    [switch]$SkipDeploy
)

$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

if (-not $SkipLocal) {
    Write-Host ""
    Write-Host "=== Step 1: Sync to local WordPress (optional) ===" -ForegroundColor Cyan
    $syncScript = Join-Path $root "scripts\sync-local.ps1"
    $synced = & $syncScript
    if ($synced) {
        Write-Host ""
        Write-Host "Check the site in your browser." -ForegroundColor White
        $answer = Read-Host "Looks good? Continue to push and deploy? (y/N)"
        if ($answer -notmatch '^[yY]') {
            Write-Host "Stopped." -ForegroundColor Yellow
            exit 0
        }
    }
}

Write-Host ""
Write-Host "=== Step 2: Commit and push to GitHub ===" -ForegroundColor Cyan

git add .
$status = git status --porcelain
if (-not $status) {
    Write-Host "Nothing to commit." -ForegroundColor Yellow
    if (-not $SkipDeploy) {
        Write-Host "Deploying current files anyway..." -ForegroundColor Gray
    } else {
        exit 0
    }
} else {
    git commit -m $Message
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
    git push origin main
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Push failed (code may still be on GitHub from earlier)." -ForegroundColor Yellow
    }
}

if ($SkipDeploy) {
    Write-Host "Skipped deploy (-SkipDeploy)." -ForegroundColor Gray
    exit 0
}

Write-Host ""
Write-Host "=== Step 3: Deploy to live site from your PC ===" -ForegroundColor Cyan
Write-Host "Note: If GitHub Actions times out, deploy still runs from your PC via FTP." -ForegroundColor Gray

$testScript = Join-Path $root "scripts\test-ftp.ps1"
& $testScript
if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "Deploy aborted - fix FTP login first (password in hPanel and .env.deploy)." -ForegroundColor Red
    exit 1
}

$deployScript = Join-Path $root "scripts\deploy-hostinger.ps1"
& $deployScript
exit $LASTEXITCODE
