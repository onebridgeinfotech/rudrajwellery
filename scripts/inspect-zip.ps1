param([string]$ZipPath)
Add-Type -AssemblyName System.IO.Compression.FileSystem
$z = [System.IO.Compression.ZipFile]::OpenRead($ZipPath)
Write-Host "Zip: $ZipPath"
Write-Host "Entry count: $($z.Entries.Count)"
$z.Entries | Select-Object -First 20 | ForEach-Object { Write-Host $_.FullName }
Write-Host "--- style.css entries ---"
$z.Entries | Where-Object { $_.FullName -match 'style\.css' } | ForEach-Object { Write-Host $_.FullName }
$z.Dispose()
