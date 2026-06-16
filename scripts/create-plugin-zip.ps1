# Creates jewelry-upi-store.zip (forward slashes for Linux/Hostinger)
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$pluginDir = Join-Path $root "wordpress-plugin\jewelry-upi-store"
$zipPath = Join-Path $root "jewelry-upi-store.zip"
$folderName = "jewelry-upi-store"

if (-not (Test-Path (Join-Path $pluginDir "jewelry-upi-store.php"))) {
    Write-Error "Plugin not found at $pluginDir"
    exit 1
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

$zip = [System.IO.Compression.ZipFile]::Open( $zipPath, [System.IO.Compression.ZipArchiveMode]::Create )

$exclude = @(
    'includes\class-jus-blocks.php'
    'assets\js\jus-blocks-checkout.js'
)

Get-ChildItem -Path $pluginDir -Recurse -File | ForEach-Object {
    $relative = $_.FullName.Substring( $pluginDir.Length + 1 ).Replace( '\', '/' )
    $relativeWin = $relative.Replace( '/', '\' )
    if ( $exclude -contains $relativeWin ) {
        return
    }
    $entryName = "$folderName/$relative"
    [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile( $zip, $_.FullName, $entryName )
}

$zip.Dispose()
Write-Host "Created: $zipPath (Linux-compatible paths)"
