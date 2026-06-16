# Maps demo SKUs to ramyanagendra.com product image URLs
$jsonPath = "C:\Users\HP\.cursor\projects\d-jwellery-ecommerce\agent-tools\b0b4ea04-80a2-44ba-9395-c74d04238aac.txt"
$outPath = Join-Path (Split-Path $PSScriptRoot -Parent) "sample-data\product-images.json"

$map = @{
    'ER-001' = 'changeable-studs'
    'ER-002' = 'flower-stud-with-string-earrings'
    'NK-001' = '5-lines-chandraharam'
    'NK-002' = 'pendant-with-chandraharam-black-beads'
    'NK-003' = 'short-black-beads'
    'NK-004' = '2lines-long-black-beads'
    'CK-001' = 'mini-chocker'
    'BG-001' = 'gold-kada-1'
    'LH-001' = 'thali-chain-gj-1'
    'HM-001' = 'offer-champaswaralu'
    'HM-002' = '3lines-earchains'
    'HM-003' = 'gold-matilu'
    'HM-004' = 'pink-antique-pendant-chain'
    'HM-005' = 'chain-with-pearls'
    'LC-001' = '5-ball-black-beads-beads-design-will-be-different'
    'LC-002' = 'pink-stone-laxmi-bb'
    'IG-001' = 'nakshi-kada'
    'IG-002' = 'green-butta'
    'CB-001' = 'bb-combo'
}

$json = Get-Content $jsonPath -Raw | ConvertFrom-Json
$result = @{}

foreach ($sku in $map.Keys) {
    $handle = $map[$sku]
    $p = $json.products | Where-Object { $_.handle -eq $handle } | Select-Object -First 1
    if (-not $p) {
        $p = $json.products | Where-Object { $_.title -like "*$(($handle -replace '-',' '))*" } | Select-Object -First 1
    }
    if ($p -and $p.images -and $p.images.Count -gt 0) {
        $src = ($p.images[0].src -replace '\\/', '/')
        $result[$sku] = @{ handle = $handle; title = $p.title; image = $src }
    }
}

$result | ConvertTo-Json -Depth 5 | Set-Content $outPath -Encoding UTF8
Write-Host "Mapped $($result.Count) products -> $outPath"
