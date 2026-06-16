# Commit + push to main → GitHub Actions deploys to Hostinger automatically.
param(
    [Parameter(Mandatory = $true, Position = 0)]
    [string]$Message
)

$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $root

git add .
$status = git status --porcelain
if (-not $status) {
    Write-Host "Nothing to commit." -ForegroundColor Yellow
    exit 0
}

git commit -m $Message
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

git push origin main
if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "Push failed. If workflow file changed, update deploy.yml on GitHub.com or use a PAT with workflow scope." -ForegroundColor Red
    exit $LASTEXITCODE
}

Write-Host ""
Write-Host "Pushed to main. Deploy starts automatically in GitHub Actions." -ForegroundColor Green
Write-Host "Track: https://github.com/onebridgeinfotech/rudrajwellery/actions" -ForegroundColor Cyan
Write-Host "Live site updates in ~1-2 min. Purge LiteSpeed cache if CSS/JS looks stale." -ForegroundColor Gray
