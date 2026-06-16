$outDir = Join-Path (Split-Path $PSScriptRoot -Parent) "sample-data\images\products"
$mapPath = Join-Path (Split-Path $PSScriptRoot -Parent) "sample-data\product-images.json"
$map = Get-Content $mapPath -Raw | ConvertFrom-Json

$missing = @(
    @{ sku = 'NK-003'; handle = 'short-black-beads-101' }
    @{ sku = 'LC-001'; handle = '5-balls-short-black-beads-1' }
)

foreach ($e in $missing) {
    $p = (Invoke-RestMethod "https://ramyanagendra.com/products/$($e.handle).json").product
    $src = ($p.images[0].src -replace '\\/', '/')
    $map | Add-Member -NotePropertyName $e.sku -NotePropertyValue @{ handle = $e.handle; title = $p.title; image = $src } -Force
    $file = Join-Path $outDir "$($e.sku).jpg"
    Invoke-WebRequest -Uri $src -OutFile $file -UseBasicParsing
    Write-Host "OK $($e.sku)"
}

$map | ConvertTo-Json -Depth 5 | Set-Content $mapPath -Encoding UTF8
