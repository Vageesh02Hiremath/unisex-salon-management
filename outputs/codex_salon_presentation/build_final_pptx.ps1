$ErrorActionPreference = 'Stop'

$root = 'C:\wamp64\www\unisex_salon_management'
$sourceUnpack = Join-Path $root 'outputs\pptx_unpack'
$work = Join-Path $root 'tmp\slides\codex_salon_presentation\pptx_work'
$final = Join-Path $root 'outputs\codex_salon_presentation\unisex_salon_management_codex_presentation.pptx'

if (Test-Path $work) { Remove-Item $work -Recurse -Force }
Copy-Item $sourceUnpack $work -Recurse -Force

$mediaDir = Join-Path $work 'ppt\media'
$slideRelsDir = Join-Path $work 'ppt\slides\_rels'
New-Item -ItemType Directory -Force -Path $mediaDir | Out-Null
New-Item -ItemType Directory -Force -Path $slideRelsDir | Out-Null

Copy-Item (Join-Path $root 'outputs\screenshot_index.png') (Join-Path $mediaDir 'screenshot_index.png') -Force
Copy-Item (Join-Path $root 'outputs\screenshot_login.png') (Join-Path $mediaDir 'screenshot_login.png') -Force
Copy-Item (Join-Path $root 'outputs\screenshot_register.png') (Join-Path $mediaDir 'screenshot_register.png') -Force

$erImage = Join-Path $root 'outputs\codex_salon_presentation\er_diagram_high_res.png'
Add-Type -AssemblyName System.Drawing
$bmp = New-Object System.Drawing.Bitmap 1800,2000
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit
$g.Clear([System.Drawing.Color]::White)
$pen = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(25,34,49)), 3
$thinPen = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(25,34,49)), 2
$font = New-Object System.Drawing.Font 'Arial', 24
$small = New-Object System.Drawing.Font 'Arial', 20
$bold = New-Object System.Drawing.Font 'Arial', 26, ([System.Drawing.FontStyle]::Bold)
$brush = [System.Drawing.Brushes]::Black
$center = New-Object System.Drawing.StringFormat
$center.Alignment = [System.Drawing.StringAlignment]::Center
$center.LineAlignment = [System.Drawing.StringAlignment]::Center

function Draw-RectNode($g, $pen, $font, $text, $x, $y, $w, $h) {
    $g.DrawRectangle($pen, $x, $y, $w, $h)
    $rect = New-Object System.Drawing.RectangleF $x, $y, $w, $h
    $g.DrawString($text, $font, [System.Drawing.Brushes]::Black, $rect, $script:center)
}
function Draw-OvalNode($g, $pen, $font, $text, $x, $y, $w, $h) {
    $g.DrawEllipse($pen, $x, $y, $w, $h)
    $rect = New-Object System.Drawing.RectangleF $x, $y, $w, $h
    $g.DrawString($text, $font, [System.Drawing.Brushes]::Black, $rect, $script:center)
}
function Draw-DiamondNode($g, $pen, $font, $text, $cx, $cy, $w, $h) {
    $points = @(
        (New-Object System.Drawing.Point ($cx), ($cy - $h/2)),
        (New-Object System.Drawing.Point ($cx + $w/2), ($cy)),
        (New-Object System.Drawing.Point ($cx), ($cy + $h/2)),
        (New-Object System.Drawing.Point ($cx - $w/2), ($cy))
    )
    $g.DrawPolygon($pen, $points)
    $rect = New-Object System.Drawing.RectangleF ($cx - $w/2), ($cy - $h/2), $w, $h
    $g.DrawString($text, $font, [System.Drawing.Brushes]::Black, $rect, $script:center)
}
function Draw-Line($g, $pen, $x1, $y1, $x2, $y2) {
    $g.DrawLine($pen, $x1, $y1, $x2, $y2)
}

Draw-RectNode $g $pen $bold 'USERS' 760 320 280 90
Draw-OvalNode $g $thinPen $small 'id' 610 220 160 56
Draw-OvalNode $g $thinPen $small 'name' 770 190 180 60
Draw-OvalNode $g $thinPen $small 'role' 960 190 180 60
Draw-OvalNode $g $thinPen $small 'email' 1150 220 190 60
Draw-Line $g $thinPen 690 276 825 320
Draw-Line $g $thinPen 860 250 880 320
Draw-Line $g $thinPen 1030 250 940 320
Draw-Line $g $thinPen 1230 280 1000 320

Draw-DiamondNode $g $thinPen $small 'work as' 900 540 280 100
Draw-Line $g $thinPen 900 410 900 490

Draw-RectNode $g $pen $bold 'STAFF' 420 680 280 90
Draw-Line $g $thinPen 780 560 560 680
Draw-OvalNode $g $thinPen $small 'specialization' 150 610 250 60
Draw-OvalNode $g $thinPen $small 'id' 390 600 150 60
Draw-OvalNode $g $thinPen $small 'user_id' 550 600 190 60
Draw-Line $g $thinPen 275 670 430 700
Draw-Line $g $thinPen 465 660 500 680
Draw-Line $g $thinPen 625 660 620 680

Draw-RectNode $g $pen $bold 'CUSTOMERS' 1120 680 300 90
Draw-Line $g $thinPen 1020 560 1220 680
Draw-OvalNode $g $thinPen $small 'id' 1040 600 150 60
Draw-OvalNode $g $thinPen $small 'name' 1200 600 170 60
Draw-OvalNode $g $thinPen $small 'email' 1380 600 170 60
Draw-OvalNode $g $thinPen $small 'phone' 1560 610 180 60
Draw-Line $g $thinPen 1115 660 1160 680
Draw-Line $g $thinPen 1285 660 1270 680
Draw-Line $g $thinPen 1465 660 1340 680
Draw-Line $g $thinPen 1650 670 1400 700

Draw-RectNode $g $pen $bold 'SERVICES' 120 1040 300 100
Draw-OvalNode $g $thinPen $small 'name' 20 930 170 60
Draw-OvalNode $g $thinPen $small 'id' 220 920 170 60
Draw-OvalNode $g $thinPen $small 'price' 80 1240 180 60
Draw-OvalNode $g $thinPen $small 'duration' 300 1240 210 60
Draw-Line $g $thinPen 105 990 170 1040
Draw-Line $g $thinPen 305 980 280 1040
Draw-Line $g $thinPen 170 1240 210 1140
Draw-Line $g $thinPen 400 1240 350 1140

Draw-DiamondNode $g $thinPen $small 'offered in' 540 1240 240 110
Draw-Line $g $thinPen 420 1090 460 1210

Draw-RectNode $g $pen $bold 'Appointment' 790 1390 300 100
Draw-Line $g $thinPen 660 1240 790 1440
Draw-DiamondNode $g $thinPen $small 'takes' 680 940 240 110
Draw-Line $g $thinPen 560 770 680 885
Draw-Line $g $thinPen 680 995 900 1390
Draw-DiamondNode $g $thinPen $small 'books' 1230 940 240 110
Draw-Line $g $thinPen 1270 770 1230 885
Draw-Line $g $thinPen 1230 995 940 1390

Draw-OvalNode $g $thinPen $small 'id' 820 1280 160 60
Draw-OvalNode $g $thinPen $small 'customer_id' 520 1550 230 60
Draw-OvalNode $g $thinPen $small 'staff_id' 590 1710 190 60
Draw-OvalNode $g $thinPen $small 'service_id' 1090 1550 210 60
Draw-OvalNode $g $thinPen $small "appointment`ndate" 710 1840 240 80
Draw-OvalNode $g $thinPen $small "appointment`ntime" 980 1840 240 80
Draw-Line $g $thinPen 900 1340 910 1390
Draw-Line $g $thinPen 640 1550 820 1490
Draw-Line $g $thinPen 680 1710 860 1490
Draw-Line $g $thinPen 1110 1550 1030 1490
Draw-Line $g $thinPen 820 1840 880 1490
Draw-Line $g $thinPen 1100 1840 1000 1490

Draw-DiamondNode $g $thinPen $small 'will pay' 1420 1020 240 110
Draw-Line $g $thinPen 1270 770 1420 965

Draw-RectNode $g $pen $bold 'BILL' 1360 1390 300 100
Draw-Line $g $thinPen 1420 1075 1510 1390
Draw-DiamondNode $g $thinPen $small 'generates' 1240 1450 240 110
Draw-Line $g $thinPen 1090 1440 1120 1450
Draw-Line $g $thinPen 1360 1440 1360 1450
Draw-OvalNode $g $thinPen $small 'appointment_id' 1600 1280 210 80
Draw-OvalNode $g $thinPen $small 'id' 1560 1530 170 60
Draw-OvalNode $g $thinPen $small 'customer_id' 1580 1710 210 70
Draw-Line $g $thinPen 1680 1360 1540 1390
Draw-Line $g $thinPen 1620 1530 1530 1490
Draw-Line $g $thinPen 1670 1710 1530 1490

$bmp.Save($erImage, [System.Drawing.Imaging.ImageFormat]::Png)
$g.Dispose()
$bmp.Dispose()
Copy-Item $erImage (Join-Path $mediaDir 'er_diagram_high_res.png') -Force

$contentTypesPath = Join-Path $work '[Content_Types].xml'
$content = [System.IO.File]::ReadAllText($contentTypesPath)
if ($content -notmatch 'Extension="png"') {
    $content = $content -replace '(<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">)', '$1<Default Extension="png" ContentType="image/png"/>'
    [System.IO.File]::WriteAllText($contentTypesPath, $content, [System.Text.UTF8Encoding]::new($false))
}

$slide1Path = Join-Path $work 'ppt\slides\slide1.xml'
$slide1 = [System.IO.File]::ReadAllText($slide1Path)
$dateShape = @'
<p:sp><p:nvSpPr><p:cNvPr id="18" name="Date"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="819150" y="6038850"/><a:ext cx="4095750" cy="361950"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></p:spPr><p:txBody><a:bodyPr wrap="square" lIns="76200" tIns="38100" rIns="76200" bIns="38100"><a:normAutofit fontScale="85000" lnSpcReduction="20000"/></a:bodyPr><a:lstStyle/><a:p><a:pPr algn="l"/><a:r><a:rPr lang="en-US" sz="1700"><a:solidFill><a:srgbClr val="DDE6F3"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>May 29, 2026</a:t></a:r></a:p></p:txBody></p:sp>
'@
if ($slide1 -notmatch 'May 29, 2026') {
    $slide1 = $slide1 -replace '</p:spTree>', ($dateShape + '</p:spTree>')
    [System.IO.File]::WriteAllText($slide1Path, $slide1, [System.Text.UTF8Encoding]::new($false))
}

$slide8Rels = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/screenshot_index.png"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/screenshot_login.png"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/screenshot_register.png"/>
</Relationships>
'@
[System.IO.File]::WriteAllText((Join-Path $slideRelsDir 'slide8.xml.rels'), $slide8Rels, [System.Text.UTF8Encoding]::new($false))

$slide8Xml = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:bg><p:bgPr><a:solidFill><a:srgbClr val="F8FAFC"/></a:solidFill><a:effectLst/></p:bgPr></p:bg>
    <p:spTree>
      <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
      <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="2" name="Header"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="12192000" cy="742950"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:solidFill><a:srgbClr val="111827"/></a:solidFill><a:ln><a:noFill/></a:ln></p:spPr>
        <p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody>
      </p:sp>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="3" name="Title"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="609600" y="171450"/><a:ext cx="7239000" cy="400050"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></p:spPr>
        <p:txBody><a:bodyPr wrap="square" lIns="76200" tIns="38100" rIns="76200" bIns="38100"/><a:lstStyle/><a:p><a:pPr algn="l"/><a:r><a:rPr lang="en-US" sz="2500" b="1"><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>Demo / Screenshots</a:t></a:r></a:p></p:txBody>
      </p:sp>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="4" name="Slide Number"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="10572750" y="209550"/><a:ext cx="904875" cy="304800"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></p:spPr>
        <p:txBody><a:bodyPr wrap="square" lIns="76200" tIns="38100" rIns="76200" bIns="38100"/><a:lstStyle/><a:p><a:pPr algn="r"/><a:r><a:rPr lang="en-US" sz="1600" b="1"><a:solidFill><a:srgbClr val="FBCFE8"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>08</a:t></a:r></a:p></p:txBody>
      </p:sp>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="10" name="Subtitle"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="762000" y="990600"/><a:ext cx="8382000" cy="381000"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></p:spPr>
        <p:txBody><a:bodyPr wrap="square" lIns="76200" tIns="38100" rIns="76200" bIns="38100"/><a:lstStyle/><a:p><a:pPr algn="l"/><a:r><a:rPr lang="en-US" sz="2200" b="1"><a:solidFill><a:srgbClr val="172033"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>Live application screens from the salon management workflow</a:t></a:r></a:p></p:txBody>
      </p:sp>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="20" name="Frame 1"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="685800" y="1638300"/><a:ext cx="3429000" cy="2857500"/></a:xfrm><a:prstGeom prst="roundRect"><a:avLst><a:gd name="adj" fmla="val 9000"/></a:avLst></a:prstGeom><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:ln w="12700"><a:solidFill><a:srgbClr val="CBD5E1"/></a:solidFill></a:ln></p:spPr>
        <p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody>
      </p:sp>
      <p:pic>
        <p:nvPicPr><p:cNvPr id="21" name="Landing Page Screenshot"/><p:cNvPicPr/><p:nvPr/></p:nvPicPr>
        <p:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>
        <p:spPr><a:xfrm><a:off x="838200" y="1790700"/><a:ext cx="3124200" cy="2219325"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:ln><a:noFill/></a:ln></p:spPr>
      </p:pic>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="22" name="Caption 1"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="914400" y="4143375"/><a:ext cx="2971800" cy="304800"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></p:spPr>
        <p:txBody><a:bodyPr wrap="square" lIns="38100" tIns="38100" rIns="38100" bIns="38100"/><a:lstStyle/><a:p><a:pPr algn="c"/><a:r><a:rPr lang="en-US" sz="1700" b="1"><a:solidFill><a:srgbClr val="334155"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>Landing page</a:t></a:r></a:p></p:txBody>
      </p:sp>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="30" name="Frame 2"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="4267200" y="1638300"/><a:ext cx="3429000" cy="2857500"/></a:xfrm><a:prstGeom prst="roundRect"><a:avLst><a:gd name="adj" fmla="val 9000"/></a:avLst></a:prstGeom><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:ln w="12700"><a:solidFill><a:srgbClr val="CBD5E1"/></a:solidFill></a:ln></p:spPr>
        <p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody>
      </p:sp>
      <p:pic>
        <p:nvPicPr><p:cNvPr id="31" name="Login Screenshot"/><p:cNvPicPr/><p:nvPr/></p:nvPicPr>
        <p:blipFill><a:blip r:embed="rId2"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>
        <p:spPr><a:xfrm><a:off x="4419600" y="1790700"/><a:ext cx="3124200" cy="2219325"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:ln><a:noFill/></a:ln></p:spPr>
      </p:pic>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="32" name="Caption 2"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="4495800" y="4143375"/><a:ext cx="2971800" cy="304800"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></p:spPr>
        <p:txBody><a:bodyPr wrap="square" lIns="38100" tIns="38100" rIns="38100" bIns="38100"/><a:lstStyle/><a:p><a:pPr algn="c"/><a:r><a:rPr lang="en-US" sz="1700" b="1"><a:solidFill><a:srgbClr val="334155"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>Secure login</a:t></a:r></a:p></p:txBody>
      </p:sp>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="40" name="Frame 3"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="7848600" y="1638300"/><a:ext cx="3429000" cy="2857500"/></a:xfrm><a:prstGeom prst="roundRect"><a:avLst><a:gd name="adj" fmla="val 9000"/></a:avLst></a:prstGeom><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:ln w="12700"><a:solidFill><a:srgbClr val="CBD5E1"/></a:solidFill></a:ln></p:spPr>
        <p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody>
      </p:sp>
      <p:pic>
        <p:nvPicPr><p:cNvPr id="41" name="Registration Screenshot"/><p:cNvPicPr/><p:nvPr/></p:nvPicPr>
        <p:blipFill><a:blip r:embed="rId3"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>
        <p:spPr><a:xfrm><a:off x="8001000" y="1790700"/><a:ext cx="3124200" cy="2219325"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:ln><a:noFill/></a:ln></p:spPr>
      </p:pic>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="42" name="Caption 3"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="8077200" y="4143375"/><a:ext cx="2971800" cy="304800"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></p:spPr>
        <p:txBody><a:bodyPr wrap="square" lIns="38100" tIns="38100" rIns="38100" bIns="38100"/><a:lstStyle/><a:p><a:pPr algn="c"/><a:r><a:rPr lang="en-US" sz="1700" b="1"><a:solidFill><a:srgbClr val="334155"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>Customer registration</a:t></a:r></a:p></p:txBody>
      </p:sp>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="50" name="Note"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="990600" y="5162550"/><a:ext cx="9906000" cy="495300"/></a:xfrm><a:prstGeom prst="roundRect"><a:avLst><a:gd name="adj" fmla="val 12000"/></a:avLst></a:prstGeom><a:solidFill><a:srgbClr val="ECFEFF"/></a:solidFill><a:ln><a:noFill/></a:ln></p:spPr>
        <p:txBody><a:bodyPr wrap="square" lIns="152400" tIns="76200" rIns="152400" bIns="76200"/><a:lstStyle/><a:p><a:pPr algn="c"/><a:r><a:rPr lang="en-US" sz="1800"><a:solidFill><a:srgbClr val="155E75"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>Demo can continue through customer booking, staff status updates, generated bill, payment record, feedback, and admin revenue chart.</a:t></a:r></a:p></p:txBody>
      </p:sp>
    </p:spTree>
  </p:cSld>
  <p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>
</p:sld>
'@
[System.IO.File]::WriteAllText((Join-Path $work 'ppt\slides\slide8.xml'), $slide8Xml, [System.Text.UTF8Encoding]::new($false))

function New-TextShapeXml($id, $name, $geom, $x, $y, $w, $h, $text, $size, $fill, $line, $color, $bold) {
    $boldAttr = ''
    if ($bold) { $boldAttr = ' b="1"' }
    return "<p:sp><p:nvSpPr><p:cNvPr id=`"$id`" name=`"$name`"/><p:cNvSpPr txBox=`"1`"/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x=`"$x`" y=`"$y`"/><a:ext cx=`"$w`" cy=`"$h`"/></a:xfrm><a:prstGeom prst=`"$geom`"><a:avLst/></a:prstGeom><a:solidFill><a:srgbClr val=`"$fill`"/></a:solidFill><a:ln w=`"9525`"><a:solidFill><a:srgbClr val=`"$line`"/></a:solidFill></a:ln></p:spPr><p:txBody><a:bodyPr wrap=`"square`" anchor=`"ctr`" lIns=`"38100`" tIns=`"19050`" rIns=`"38100`" bIns=`"19050`"/><a:lstStyle/><a:p><a:pPr algn=`"c`"/><a:r><a:rPr lang=`"en-US`" sz=`"$size`"$boldAttr><a:solidFill><a:srgbClr val=`"$color`"/></a:solidFill><a:latin typeface=`"Aptos`"/></a:rPr><a:t>$text</a:t></a:r></a:p></p:txBody></p:sp>"
}

function New-LineXml($id, $x1, $y1, $x2, $y2) {
    $x = [Math]::Min($x1, $x2)
    $y = [Math]::Min($y1, $y2)
    $w = [Math]::Abs($x2 - $x1)
    $h = [Math]::Abs($y2 - $y1)
    $flipH = ''
    if ($x2 -lt $x1) { $flipH = ' flipH="1"' }
    return "<p:cxnSp><p:nvCxnSpPr><p:cNvPr id=`"$id`" name=`"Connector $id`"/><p:cNvCxnSpPr/><p:nvPr/></p:nvCxnSpPr><p:spPr><a:xfrm$flipH><a:off x=`"$x`" y=`"$y`"/><a:ext cx=`"$w`" cy=`"$h`"/></a:xfrm><a:prstGeom prst=`"line`"><a:avLst/></a:prstGeom><a:ln w=`"9525`"><a:solidFill><a:srgbClr val=`"334155`"/></a:solidFill></a:ln></p:spPr></p:cxnSp>"
}

function New-CardXml($id, $title, $attrs, $x, $y, $w, $h, $accent) {
    $attrText = [System.Security.SecurityElement]::Escape(($attrs -join "`n"))
    return @"
<p:sp><p:nvSpPr><p:cNvPr id="$id" name="$title Card"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="$x" y="$y"/><a:ext cx="$w" cy="$h"/></a:xfrm><a:prstGeom prst="roundRect"><a:avLst><a:gd name="adj" fmla="val 9000"/></a:avLst></a:prstGeom><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:ln w="12700"><a:solidFill><a:srgbClr val="CBD5E1"/></a:solidFill></a:ln></p:spPr><p:txBody><a:bodyPr wrap="square" lIns="152400" tIns="114300" rIns="152400" bIns="114300"/><a:lstStyle/><a:p><a:pPr algn="l"/><a:r><a:rPr lang="en-US" sz="1600" b="1"><a:solidFill><a:srgbClr val="$accent"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>$title</a:t></a:r></a:p><a:p><a:pPr algn="l"/><a:r><a:rPr lang="en-US" sz="1000"><a:solidFill><a:srgbClr val="334155"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>$attrText</a:t></a:r></a:p></p:txBody></p:sp>
"@
}

function New-LabelXml($id, $text, $x, $y, $w) {
    return "<p:sp><p:nvSpPr><p:cNvPr id=`"$id`" name=`"$text Label`"/><p:cNvSpPr txBox=`"1`"/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x=`"$x`" y=`"$y`"/><a:ext cx=`"$w`" cy=`"247650`"/></a:xfrm><a:prstGeom prst=`"roundRect`"><a:avLst><a:gd name=`"adj`" fmla=`"val 18000`"/></a:avLst></a:prstGeom><a:solidFill><a:srgbClr val=`"ECFEFF`"/></a:solidFill><a:ln w=`"6350`"><a:solidFill><a:srgbClr val=`"67E8F9`"/></a:solidFill></a:ln></p:spPr><p:txBody><a:bodyPr wrap=`"square`" anchor=`"ctr`" lIns=`"57150`" tIns=`"19050`" rIns=`"57150`" bIns=`"19050`"/><a:lstStyle/><a:p><a:pPr algn=`"c`"/><a:r><a:rPr lang=`"en-US`" sz=`"950`" b=`"1`"><a:solidFill><a:srgbClr val=`"155E75`"/></a:solidFill><a:latin typeface=`"Aptos`"/></a:rPr><a:t>$text</a:t></a:r></a:p></p:txBody></p:sp>"
}

$slide14Rels = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/er_diagram_high_res.png"/>
</Relationships>
'@
[System.IO.File]::WriteAllText((Join-Path $slideRelsDir 'slide14.xml.rels'), $slide14Rels, [System.Text.UTF8Encoding]::new($false))

$slide14Xml = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:bg><p:bgPr><a:solidFill><a:srgbClr val="F8FAFC"/></a:solidFill><a:effectLst/></p:bgPr></p:bg>
    <p:spTree>
      <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
      <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="2" name="Header"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="12192000" cy="742950"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:solidFill><a:srgbClr val="111827"/></a:solidFill><a:ln><a:noFill/></a:ln></p:spPr>
        <p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody>
      </p:sp>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="3" name="Title"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="609600" y="171450"/><a:ext cx="7239000" cy="400050"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></p:spPr>
        <p:txBody><a:bodyPr wrap="square" lIns="76200" tIns="38100" rIns="76200" bIns="38100"/><a:lstStyle/><a:p><a:pPr algn="l"/><a:r><a:rPr lang="en-US" sz="2500" b="1"><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>ER Diagram / Database Model</a:t></a:r></a:p></p:txBody>
      </p:sp>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="4" name="Slide Number"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="10572750" y="209550"/><a:ext cx="904875" cy="304800"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></p:spPr>
        <p:txBody><a:bodyPr wrap="square" lIns="76200" tIns="38100" rIns="76200" bIns="38100"/><a:lstStyle/><a:p><a:pPr algn="r"/><a:r><a:rPr lang="en-US" sz="1600" b="1"><a:solidFill><a:srgbClr val="FBCFE8"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>06</a:t></a:r></a:p></p:txBody>
      </p:sp>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="6" name="Image Frame"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="3314700" y="990600"/><a:ext cx="5295900" cy="5486400"/></a:xfrm><a:prstGeom prst="roundRect"><a:avLst><a:gd name="adj" fmla="val 2500"/></a:avLst></a:prstGeom><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:ln w="12700"><a:solidFill><a:srgbClr val="CBD5E1"/></a:solidFill></a:ln></p:spPr>
        <p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody>
      </p:sp>
      <p:pic>
        <p:nvPicPr><p:cNvPr id="7" name="ER Diagram Screenshot"/><p:cNvPicPr/><p:nvPr/></p:nvPicPr>
        <p:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>
        <p:spPr><a:xfrm><a:off x="3524250" y="1104900"/><a:ext cx="4876800" cy="5419725"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:ln><a:noFill/></a:ln></p:spPr>
      </p:pic>
    </p:spTree>
  </p:cSld>
  <p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>
</p:sld>
'@
[System.IO.File]::WriteAllText((Join-Path $work 'ppt\slides\slide14.xml'), $slide14Xml, [System.Text.UTF8Encoding]::new($false))

$content = [System.IO.File]::ReadAllText($contentTypesPath)
if ($content -notmatch '/ppt/slides/slide14.xml') {
    $content = $content -replace '(<Override PartName="/docProps/core.xml")', '<Override PartName="/ppt/slides/slide14.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>$1'
    [System.IO.File]::WriteAllText($contentTypesPath, $content, [System.Text.UTF8Encoding]::new($false))
}

$presentationPath = Join-Path $work 'ppt\presentation.xml'
$presentation = [System.IO.File]::ReadAllText($presentationPath)
if ($presentation -notmatch 'r:id="rId14"') {
    $presentation = $presentation -replace '(<p:sldId id="260" r:id="rId5"/>)', '$1<p:sldId id="269" r:id="rId14"/>'
    [System.IO.File]::WriteAllText($presentationPath, $presentation, [System.Text.UTF8Encoding]::new($false))
}

$presentationRelsPath = Join-Path $work 'ppt\_rels\presentation.xml.rels'
$presentationRels = [System.IO.File]::ReadAllText($presentationRelsPath)
if ($presentationRels -notmatch 'slides/slide14.xml') {
    $presentationRels = $presentationRels -replace '(<Relationship Id="rIdTheme")', '<Relationship Id="rId14" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide14.xml"/>$1'
    [System.IO.File]::WriteAllText($presentationRelsPath, $presentationRels, [System.Text.UTF8Encoding]::new($false))
}

$renumber = @{
    'slide6.xml' = @('06','07'); 'slide7.xml' = @('07','08'); 'slide8.xml' = @('08','09'); 'slide9.xml' = @('09','10');
    'slide10.xml' = @('10','11'); 'slide11.xml' = @('11','12'); 'slide12.xml' = @('12','13'); 'slide13.xml' = @('13','14')
}
foreach ($item in $renumber.GetEnumerator()) {
    $path = Join-Path (Join-Path $work 'ppt\slides') $item.Key
    $xml = [System.IO.File]::ReadAllText($path)
    $xml = $xml.Replace("<a:t>$($item.Value[0])</a:t>", "<a:t>$($item.Value[1])</a:t>")
    [System.IO.File]::WriteAllText($path, $xml, [System.Text.UTF8Encoding]::new($false))
}

$presentation = [System.IO.File]::ReadAllText($presentationPath)
$presentation = $presentation -replace '<p:sldId id="264" r:id="rId9"/>', ''
[System.IO.File]::WriteAllText($presentationPath, $presentation, [System.Text.UTF8Encoding]::new($false))

$content = [System.IO.File]::ReadAllText($contentTypesPath)
$content = $content -replace '<Override PartName="/ppt/slides/slide9.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide\+xml"/>', ''
[System.IO.File]::WriteAllText($contentTypesPath, $content, [System.Text.UTF8Encoding]::new($false))

$afterCodexRenumber = @{
    'slide10.xml' = @('11','10'); 'slide11.xml' = @('12','11'); 'slide12.xml' = @('13','12'); 'slide13.xml' = @('14','13')
}
foreach ($item in $afterCodexRenumber.GetEnumerator()) {
    $path = Join-Path (Join-Path $work 'ppt\slides') $item.Key
    $xml = [System.IO.File]::ReadAllText($path)
    $xml = $xml.Replace("<a:t>$($item.Value[0])</a:t>", "<a:t>$($item.Value[1])</a:t>")
    [System.IO.File]::WriteAllText($path, $xml, [System.Text.UTF8Encoding]::new($false))
}

if (Test-Path $final) { Remove-Item $final -Force }
Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression
$zip = [System.IO.Compression.ZipFile]::Open($final, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($file in [System.IO.Directory]::GetFiles($work, '*', [System.IO.SearchOption]::AllDirectories)) {
        $relative = $file.Substring($work.Length).TrimStart('\')
        $entryName = $relative.Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file, $entryName) | Out-Null
    }
}
finally {
    $zip.Dispose()
}

Write-Output "Created $final"
