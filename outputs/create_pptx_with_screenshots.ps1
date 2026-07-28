$original = 'C:\wamp64\www\unisex_salon_management\outputs\vageesh_hiremath_fabulous_unisex_salon_final_presentation.pptx'
$final = 'C:\wamp64\www\unisex_salon_management\outputs\vageesh_hiremath_fabulous_unisex_salon_final_presentation_with_screenshots.pptx'
$tmp = 'C:\wamp64\www\unisex_salon_management\outputs\pptx_unpack2'
$images = @(
    @{Source='C:\wamp64\www\unisex_salon_management\outputs\screenshot_index.png'; File='screenshot_index.png'; Title='Landing Page Screenshot'}
    @{Source='C:\wamp64\www\unisex_salon_management\outputs\screenshot_login.png'; File='screenshot_login.png'; Title='Login Page Screenshot'}
    @{Source='C:\wamp64\www\unisex_salon_management\outputs\screenshot_register.png'; File='screenshot_register.png'; Title='Registration Page Screenshot'}
)
if (Test-Path $tmp) { Remove-Item $tmp -Recurse -Force }
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::ExtractToDirectory($original, $tmp)
$mediaDir = Join-Path $tmp 'ppt\media'
if (!(Test-Path $mediaDir)) { New-Item -ItemType Directory -Path $mediaDir | Out-Null }
foreach ($img in $images) { Copy-Item $img.Source -Destination (Join-Path $mediaDir $img.File) -Force }
$contentTypesPath = Join-Path $tmp '[Content_Types].xml'
$content = Get-Content $contentTypesPath | Out-String
if ($content -notmatch 'Extension="png"') {
    $content = $content -replace '(?=<Override PartName="/ppt/presentation.xml" ContentType=)', '<Default Extension="png" ContentType="image/png"/>'
    Set-Content -Path $contentTypesPath -Value $content -Encoding UTF8
}
$presentationPath = Join-Path $tmp 'ppt\presentation.xml'
$presentation = Get-Content $presentationPath | Out-String
$newEntries = '<p:sldId id="269" r:id="rId14"/><p:sldId id="270" r:id="rId15"/><p:sldId id="271" r:id="rId16"/>'
$presentation = $presentation -replace '(</p:sldIdLst>)', "$newEntries`$1"
Set-Content -Path $presentationPath -Value $presentation -Encoding UTF8
$relsPath = Join-Path $tmp 'ppt\_rels\presentation.xml.rels'
$rels = Get-Content $relsPath | Out-String
$newRelEntries = '<Relationship Id="rId14" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide14.xml"/><Relationship Id="rId15" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide15.xml"/><Relationship Id="rId16" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide16.xml"/>'
$rels = $rels -replace '(</Relationships>)', "$newRelEntries`$1"
Set-Content -Path $relsPath -Value $rels -Encoding UTF8
$slidesDir = Join-Path $tmp 'ppt\slides'
$slidesRelsDir = Join-Path $slidesDir '_rels'
if (!(Test-Path $slidesRelsDir)) { New-Item -ItemType Directory -Path $slidesRelsDir | Out-Null }
function New-SlideFile($number, $imgFile, $title) {
    $slidePath = Join-Path $slidesDir "slide$number.xml"
    $relPath = Join-Path $slidesRelsDir "slide$number.xml.rels"
    $slideXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:bg>
      <p:bgPr>
        <a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill>
        <a:effectLst/>
      </p:bgPr>
    </p:bg>
    <p:spTree>
      <p:nvGrpSpPr>
        <p:cNvPr id="1" name=""/>
        <p:cNvGrpSpPr/>
        <p:nvPr/>
      </p:nvGrpSpPr>
      <p:grpSpPr>
        <a:xfrm>
          <a:off x="0" y="0"/>
          <a:ext cx="0" cy="0"/>
          <a:chOff x="0" y="0"/>
          <a:chExt cx="0" cy="0"/>
        </a:xfrm>
      </p:grpSpPr>
      <p:sp>
        <p:nvSpPr>
          <p:cNvPr id="2" name="Title 1"/>
          <p:cNvSpPr txBox="1"/>
          <p:nvPr/>
        </p:nvSpPr>
        <p:spPr>
          <a:xfrm>
            <a:off x="914400" y="457200"/>
            <a:ext cx="10454400" cy="914400"/>
          </a:xfrm>
          <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
          <a:noFill/>
          <a:ln><a:noFill/></a:ln>
        </p:spPr>
        <p:txBody>
          <a:bodyPr wrap="square" lIns="0" tIns="0" rIns="0" bIns="0"/>
          <a:lstStyle/>
          <a:p>
            <a:pPr algn="l"/>
            <a:r>
              <a:rPr lang="en-US" sz="3600" b="1">
                <a:solidFill><a:srgbClr val="111827"/></a:solidFill>
                <a:latin typeface="Aptos"/>
              </a:rPr>
              <a:t>$title</a:t>
            </a:r>
            <a:endParaRPr lang="en-US" sz="3600"/>
          </a:p>
        </p:txBody>
      </p:sp>
      <p:pic>
        <p:nvPicPr>
          <p:cNvPr id="3" name="Picture 1"/>
          <p:cNvPicPr/>
          <p:nvPr/>
        </p:nvPicPr>
        <p:blipFill>
          <a:blip r:embed="rId1"/>
          <a:stretch><a:fillRect/></a:stretch>
        </p:blipFill>
        <p:spPr>
          <a:xfrm>
            <a:off x="914400" y="1371600"/>
            <a:ext cx="10454400" cy="5029200"/>
          </a:xfrm>
          <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
          <a:ln><a:noFill/></a:ln>
        </p:spPr>
      </p:pic>
    </p:spTree>
  </p:cSld>
  <p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>
</p:sld>
"@
    $slideXml | Set-Content -Path $slidePath -Encoding UTF8
    $relXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/$imgFile"/>
</Relationships>
"@
    $relXml | Set-Content -Path $relPath -Encoding UTF8
}
New-SlideFile 14 'screenshot_index.png' 'Landing Page Screenshot'
New-SlideFile 15 'screenshot_login.png' 'Login Page Screenshot'
New-SlideFile 16 'screenshot_register.png' 'Registration Page Screenshot'
if (Test-Path $final) { Remove-Item $final -Force }
[System.IO.Compression.ZipFile]::CreateFromDirectory($tmp, $final)
Remove-Item $tmp -Recurse -Force
Write-Output "Created final PPTX: $final"
