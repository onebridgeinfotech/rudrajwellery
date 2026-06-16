# Creates WordPress-compatible theme zip (forward slashes for Linux/Hostinger)
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$themeDir = Join-Path $root "wordpress-theme\jwellery-jewelry"
$zipPath = Join-Path $root "jwellery-jewelry.zip"

if (-not (Test-Path (Join-Path $themeDir "style.css"))) {
    Write-Error "Theme not found at $themeDir"
    exit 1
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$themeFolderName = "jwellery-jewelry"

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

$zip = [System.IO.Compression.ZipFile]::Open( $zipPath, [System.IO.Compression.ZipArchiveMode]::Create )

Get-ChildItem -Path $themeDir -Recurse -File | ForEach-Object {
    $relative = $_.FullName.Substring( $themeDir.Length + 1 ).Replace( '\', '/' )
    $entryName = "$themeFolderName/$relative"
    [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile( $zip, $_.FullName, $entryName )
}

$zip.Dispose()

# Verify style.css entry exists
$check = [System.IO.Compression.ZipFile]::OpenRead( $zipPath )
$found = $false
foreach ( $e in $check.Entries ) {
    if ( $e.FullName -eq "$themeFolderName/style.css" ) { $found = $true; break }
}
$check.Dispose()

if (-not $found) {
    Write-Error "ZIP verification failed: jwellery-jewelry/style.css not found"
    exit 1
}

Write-Host "Created: $zipPath"
Write-Host "Verified: jwellery-jewelry/style.css (Linux-compatible paths)"

# Optional child theme
$childDir = Join-Path $root "wordpress-theme\jwellery-jewelry-child"
$childZip = Join-Path $root "jwellery-jewelry-child.zip"
if (Test-Path (Join-Path $childDir "style.css")) {
    if (Test-Path $childZip) { Remove-Item $childZip -Force }
    $childZipArchive = [System.IO.Compression.ZipFile]::Open( $childZip, [System.IO.Compression.ZipArchiveMode]::Create )
    Get-ChildItem -Path $childDir -Recurse -File | ForEach-Object {
        $relative = $_.FullName.Substring( $childDir.Length + 1 ).Replace( '\', '/' )
        $entryName = "jwellery-jewelry-child/$relative"
        [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile( $childZipArchive, $_.FullName, $entryName )
    }
    $childZipArchive.Dispose()
    Write-Host "Created: $childZip"
}
