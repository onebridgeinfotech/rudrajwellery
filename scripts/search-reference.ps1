$json = Get-Content (Join-Path (Split-Path $PSScriptRoot -Parent) "sample-data\reference-products.json") -Raw | ConvertFrom-Json
$terms = @('changeable','short black','2lines','mini chock','champaswaralu','3lines','gold matilu','pink antique','chain with pearl','5 ball','laxmi bb','nakshi')
foreach ($t in $terms) {
    Write-Host "`n=== $t ==="
    $json.products | Where-Object { $_.title -match $t -or $_.handle -match ($t -replace ' ','-') } | ForEach-Object {
        Write-Host "  $($_.handle) | $($_.title) | sku=$($_.variants[0].sku)"
    }
}
