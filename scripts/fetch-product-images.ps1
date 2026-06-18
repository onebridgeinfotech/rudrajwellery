$base = "https://ramyanagendra.com/products"
$outDir = Join-Path (Split-Path $PSScriptRoot -Parent) "sample-data\images\products"
$mapPath = Join-Path (Split-Path $PSScriptRoot -Parent) "sample-data\product-images.json"

if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir -Force | Out-Null }

$entries = @(
    @{ sku = 'ER-001'; handle = 'changeable-studs' }
    @{ sku = 'ER-002'; handle = 'flower-stud-with-string-earrings' }
    @{ sku = 'ER-003'; handle = 'panchaloham-ear-rings-butta' }
    @{ sku = 'ST-001'; handle = 'panchaloham-j-studs' }
    @{ sku = 'NK-001'; handle = '5-lines-chandraharam' }
    @{ sku = 'NK-002'; handle = 'pendant-with-chandraharam-black-beads' }
    @{ sku = 'NK-003'; handle = 'short-black-beads' }
    @{ sku = 'NK-004'; handle = '2lines-long-black-beads' }
    @{ sku = 'NK-005'; handle = 'laxmi-kasulu-short-necklace' }
    @{ sku = 'CK-001'; handle = 'mini-chocker' }
    @{ sku = 'BG-001'; handle = 'gold-kada-1' }
    @{ sku = 'LH-001'; handle = 'thali-chain-gj-1' }
    @{ sku = 'HM-001'; handle = 'offer-champaswaralu' }
    @{ sku = 'HM-002'; handle = '3lines-earchains' }
    @{ sku = 'HM-003'; handle = 'gold-matilu' }
    @{ sku = 'HM-004'; handle = 'pink-antique-pendant-chain' }
    @{ sku = 'HM-005'; handle = 'chain-with-pearls' }
    @{ sku = 'LC-001'; handle = '5-ball-black-beads-beads-design-will-be-different' }
    @{ sku = 'LC-002'; handle = 'pink-stone-laxmi-bb' }
    @{ sku = 'IG-001'; handle = 'nakshi-kada' }
    @{ sku = 'IG-002'; handle = 'green-butta' }
    @{ sku = 'CB-001'; handle = 'bb-combo' }
    @{ sku = 'CB-002'; handle = 'laxmi-kasulu-short-bottu-mala' }
)

$result = @{}
foreach ($e in $entries) {
    $url = "$base/$($e.handle).json"
    try {
        $resp = Invoke-RestMethod -Uri $url -TimeoutSec 30
        $p = $resp.product
        if (-not $p.images -or $p.images.Count -eq 0) {
            Write-Host "NO IMG $($e.sku) $($e.handle)"
            continue
        }
        $src = ($p.images[0].src -replace '\\/', '/')
        $result[$e.sku] = @{ handle = $e.handle; title = $p.title; image = $src }

        $ext = if ($src -match '\.(jpg|jpeg|png|webp)' ) { $matches[1] } else { 'jpg' }
        $file = Join-Path $outDir "$($e.sku).$ext"
        Invoke-WebRequest -Uri $src -OutFile $file -UseBasicParsing -TimeoutSec 60
        Write-Host "OK $($e.sku) -> $file"
    } catch {
        Write-Host "FAIL $($e.sku) $($e.handle): $($_.Exception.Message)"
    }
    Start-Sleep -Milliseconds 300
}

$result | ConvertTo-Json -Depth 5 | Set-Content $mapPath -Encoding UTF8
Write-Host "Map: $($result.Count) products -> $mapPath"
