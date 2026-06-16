$src = Join-Path (Split-Path $PSScriptRoot -Parent) "sample-data\images\products"
$dst = Join-Path (Split-Path $PSScriptRoot -Parent) "wordpress-theme\jwellery-jewelry\assets\demo-products"
$mapSrc = Join-Path (Split-Path $PSScriptRoot -Parent) "sample-data\product-images.json"

if (-not (Test-Path $dst)) { New-Item -ItemType Directory -Path $dst -Force | Out-Null }
Copy-Item "$src\*.jpg" $dst -Force
Copy-Item $mapSrc (Join-Path $dst "images-map.json") -Force
Write-Host "Copied $(@(Get-ChildItem $dst -Filter *.jpg).Count) images to theme"
