# Generate SSH key for GitHub Actions -> Hostinger SFTP (passwordless deploy).
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$keyDir = Join-Path $root "scripts\.deploy-keys"
$privateKey = Join-Path $keyDir "github_deploy"
$publicKey = "$privateKey.pub"

if (-not (Test-Path $keyDir)) {
    New-Item -ItemType Directory -Path $keyDir -Force | Out-Null
}

if (Test-Path $privateKey) {
    Write-Host "Key already exists: $privateKey" -ForegroundColor Yellow
} else {
    ssh-keygen -t ed25519 -f $privateKey -N '""' -C "github-deploy-rudrajewellery"
    Write-Host "Created new deploy key." -ForegroundColor Green
}

Write-Host ""
Write-Host "=== STEP 1: Add PUBLIC key to Hostinger ===" -ForegroundColor Cyan
Write-Host "hPanel -> Advanced -> Remote Access -> SSH/SFTP Keys -> Add key" -ForegroundColor Gray
Write-Host ""
Get-Content $publicKey
Write-Host ""

Write-Host "=== STEP 2: Add PRIVATE key to GitHub ===" -ForegroundColor Cyan
Write-Host "Repo -> Settings -> Secrets -> Actions -> New secret" -ForegroundColor Gray
Write-Host "Name: SSH_PRIVATE_KEY" -ForegroundColor Gray
Write-Host "Value: entire contents of:" -ForegroundColor Gray
Write-Host $privateKey -ForegroundColor Yellow
Write-Host ""

Write-Host "=== STEP 3: Update deploy.yml on GitHub to use ssh_private_key ===" -ForegroundColor Cyan
Write-Host "Replace password: line with ssh_private_key: `${{ secrets.SSH_PRIVATE_KEY }}" -ForegroundColor Gray
Write-Host ""
Write-Host "Private key file (do NOT commit): $privateKey" -ForegroundColor Red
