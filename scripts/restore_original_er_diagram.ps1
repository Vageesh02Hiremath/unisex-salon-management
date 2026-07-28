param(
    [string]$PptxPath = "C:\wamp64\www\unisex_salon_management\outputs\domestic_oil_ppt\Domestic_Oil_Business_Distributor_Management_System.pptx"
)

$ErrorActionPreference = "Stop"
$root = "C:\wamp64\www\unisex_salon_management"
$work = Join-Path $root "tmp\oil_er_restore_package"
$originalEr = Join-Path $root "tmp\source_odg\Pictures\100000000000039F000001AD862052A4.jpg"
$outputImage = Join-Path $root "outputs\domestic_oil_ppt\original_er_diagram_as_given.png"

Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.Drawing

New-Item -ItemType Directory -Force -Path (Split-Path -Parent $outputImage) | Out-Null

# Convert only the file format needed by the existing PPT media slot.
# The diagram pixels/content are not redrawn or redesigned.
$img = [System.Drawing.Image]::FromFile($originalEr)
try {
    $img.Save($outputImage, [System.Drawing.Imaging.ImageFormat]::Png)
} finally {
    $img.Dispose()
}

Remove-Item -LiteralPath $work -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force -Path $work | Out-Null

$tmpZip = Join-Path $work "_deck.zip"
Copy-Item -LiteralPath $PptxPath -Destination $tmpZip -Force
[System.IO.Compression.ZipFile]::ExtractToDirectory($tmpZip, $work)
Remove-Item -LiteralPath $tmpZip -Force

Copy-Item -LiteralPath $outputImage -Destination (Join-Path $work "ppt\media\er_diagram_high_res.png") -Force

$slide14 = Join-Path $work "ppt\slides\slide14.xml"
$xml = New-Object xml
$xml.PreserveWhitespace = $true
$xml.Load($slide14)
$mgr = New-Object System.Xml.XmlNamespaceManager($xml.NameTable)
$mgr.AddNamespace("a", "http://schemas.openxmlformats.org/drawingml/2006/main")
$nodes = @($xml.SelectNodes("//a:t", $mgr))
if ($nodes.Count -ge 1) { $nodes[0].InnerText = "ER DIAGRAM" }
if ($nodes.Count -ge 2) { $nodes[1].InnerText = "Domestic Oil Business Distributor Management System" }
$xml.Save($slide14)

Remove-Item -LiteralPath $PptxPath -Force
[System.IO.Compression.ZipFile]::CreateFromDirectory($work, $PptxPath)
Write-Output $PptxPath
