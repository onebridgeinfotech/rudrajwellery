$jsonPath = Join-Path (Split-Path $PSScriptRoot -Parent) "sample-data\reference-products.json"
$outPath  = Join-Path (Split-Path $PSScriptRoot -Parent) "sample-data\product-images.json"

$json = Get-Content $jsonPath -Raw | ConvertFrom-Json

$rows = @(
    @{ sku = 'ER-001'; search = 'Changeable studs' }
    @{ sku = 'ER-002'; search = 'Flower Stud' }
    @{ sku = 'NK-001'; search = '5 lines Chandraharam' }
    @{ sku = 'NK-002'; search = 'Pendant with Chandraharam' }
    @{ sku = 'NK-003'; search = 'Short black beads' }
    @{ sku = 'NK-004'; search = '2lines long black beads' }
    @{ sku = 'CK-001'; search = 'Mini chocker' }
    @{ sku = 'BG-001'; search = 'Gold kada' }
    @{ sku = 'LH-001'; search = 'Thali chain (GJ-1)' }
    @{ sku = 'HM-001'; search = 'champaswaralu' }
    @{ sku = 'HM-002'; search = '3lines earchains' }
    @{ sku = 'HM-003'; search = 'Gold matilu' }
    @{ sku = 'HM-004'; search = 'Pink antique pendant' }
    @{ sku = 'HM-005'; search = 'Chain with pearls' }
    @{ sku = 'LC-001'; search = '5 ball black beads' }
    @{ sku = 'LC-002'; search = 'Pink stone Laxmi' }
    @{ sku = 'IG-001'; search = 'Nakshi kada' }
    @{ sku = 'IG-002'; search = 'Green butta' }
    @{ sku = 'CB-001'; search = 'BB COMBO' }
)

$result = @{}
foreach ($row in $rows) {
    $p = $json.products | Where-Object { $_.variants[0].sku -eq $row.sku } | Select-Object -First 1
    if (-not $p) {
        $p = $json.products | Where-Object { $_.title -like "*$($row.search)*" } | Select-Object -First 1
    }
    if (-not $p) {
        $p = $json.products | Where-Object { $_.handle -like "*$($row.search -replace ' ','-')*" } | Select-Object -First 1
    }
    if ($p -and $p.images -and $p.images.Count -gt 0) {
        $src = ($p.images[0].src -replace '\\/', '/')
        $result[$row.sku] = @{
            handle = $p.handle
            title  = $p.title
            image  = $src
        }
        Write-Host "OK $($row.sku) -> $($p.handle)"
    } else {
        Write-Host "MISS $($row.sku) -> $($row.search)"
    }
}

$result | ConvertTo-Json -Depth 5 | Set-Content $outPath -Encoding UTF8
Write-Host "Saved $($result.Count) entries to $outPath"
