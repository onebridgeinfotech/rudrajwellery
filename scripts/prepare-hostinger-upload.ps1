# Copies correctly named ZIPs into FOR-HOSTINGER-UPLOAD (do NOT zip that folder for WordPress)
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$outDir = Join-Path $root "FOR-HOSTINGER-UPLOAD"

# Python zip = reliable forward slashes on Hostinger/Linux
$py = Get-Command python -ErrorAction SilentlyContinue
if ($py) {
    & python (Join-Path $root "scripts\create-theme-zip.py")
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
} else {
    & (Join-Path $root "scripts\create-theme-zip.ps1")
}
& (Join-Path $root "scripts\create-plugin-zip.ps1")

if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }

# Clear old copies
Remove-Item (Join-Path $outDir "*.zip") -Force -ErrorAction SilentlyContinue

# Obvious filenames so user does not zip the folder
# Nested zip only — installs as wp-content/themes/jwellery-jewelry/ (correct folder name).
# Do NOT ship FLAT zip: WordPress names the theme folder after the zip filename and updates fail.
Copy-Item (Join-Path $root "jwellery-jewelry.zip") (Join-Path $outDir "1-THEME-UPLOAD-jwellery-jewelry.zip") -Force
$flat = Join-Path $outDir "1-THEME-UPLOAD-jwellery-jewelry-FLAT.zip"
if (Test-Path $flat) { Remove-Item $flat -Force }
Copy-Item (Join-Path $root "jewelry-upi-store.zip") (Join-Path $outDir "2-PLUGIN-UPLOAD-jewelry-upi-store.zip") -Force

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host " OPEN THIS FOLDER IN FILE EXPLORER:" -ForegroundColor Green
Write-Host " $outDir" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host " Upload THESE files separately (do NOT zip the folder):" -ForegroundColor Cyan
Write-Host ""
Write-Host "  1-THEME-UPLOAD-jwellery-jewelry.zip  (ONLY this file for themes)" -ForegroundColor White
Write-Host "    Installs as folder: jwellery-jewelry" -ForegroundColor Gray
Write-Host "  Do NOT use *-FLAT.zip (wrong folder name on server)" -ForegroundColor Red
Write-Host "    -> Appearance -> Themes -> Add New -> Upload Theme" -ForegroundColor Gray
Write-Host ""
Write-Host "  2-PLUGIN-UPLOAD-jewelry-upi-store.zip" -ForegroundColor White
Write-Host "    -> WordPress: Plugins -> Add New -> Upload Plugin" -ForegroundColor Gray
Write-Host ""
Write-Host "  3-VISIT-ONCE-FIX-CHECKOUT.php" -ForegroundColor White
Write-Host "    -> Hostinger File Manager: upload to public_html" -ForegroundColor Gray
Write-Host "    -> Log in to wp-admin, then open that URL in browser" -ForegroundColor Gray
Write-Host "    -> DELETE the file after fix shows Done" -ForegroundColor Gray
Write-Host ""
Write-Host " WRONG: FOR-HOSTINGER-UPLOAD.zip (folder zipped = error)" -ForegroundColor Red
Write-Host ""
