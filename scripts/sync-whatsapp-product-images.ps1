# Copy curated WhatsApp product photos into theme demo-products bundle.
param(
    [string]$ManifestPath = (Join-Path (Split-Path $PSScriptRoot -Parent) "sample-data\whatsapp-product-images.json"),
    [string]$OutDir = (Join-Path (Split-Path $PSScriptRoot -Parent) "wordpress-theme\jwellery-jewelry\assets\demo-products")
)

$ErrorActionPreference = "Stop"
$manifest = Get-Content $ManifestPath -Raw | ConvertFrom-Json
$sourceDir = $manifest.sourceDir

if (-not (Test-Path $sourceDir)) {
    throw "Source image folder not found: $sourceDir"
}
if (-not (Test-Path $OutDir)) {
    New-Item -ItemType Directory -Path $OutDir -Force | Out-Null
}

function Find-WhatsAppSourceFile {
    param([string]$Stamp)
    $matches = Get-ChildItem $sourceDir -File | Where-Object { $_.Name -like "*WhatsApp_Image_${Stamp}*" }
    if (-not $matches) {
        throw "No source file for stamp: $Stamp"
    }
    if ($matches.Count -gt 1) {
        return ($matches | Sort-Object Length -Descending | Select-Object -First 1)
    }
    return $matches[0]
}

# Remove old bundled product images (keep images-map.json).
Get-ChildItem $OutDir -File | Where-Object { $_.Name -match '^[A-Z]{2}-\d{3}(-\d+)?\.(jpg|jpeg|png|webp)$' } | Remove-Item -Force

$copied = 0
foreach ($sku in $manifest.products.PSObject.Properties.Name) {
    $entry = $manifest.products.$sku
    $index = 1
    foreach ($stamp in $entry.images) {
        $src = Find-WhatsAppSourceFile -Stamp $stamp
        $suffix = if ($index -eq 1) { "" } else { "-$index" }
        $destName = "$sku$suffix$($src.Extension.ToLower())"
        $dest = Join-Path $OutDir $destName
        Copy-Item -LiteralPath $src.FullName -Destination $dest -Force
        Write-Host "  $sku -> $destName  ($stamp)"
        $index++
        $copied++
    }
}

Write-Host ""
Write-Host "Copied $copied image(s) to $OutDir"
Write-Host "Skipped exact duplicates: $($manifest.skippedDuplicates.Count)"
Write-Host "Skipped near-duplicate shots: $($manifest.skippedNearDuplicates.Count)"
