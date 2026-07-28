param(
    [string]$PptxPath = "C:\wamp64\www\unisex_salon_management\outputs\domestic_oil_ppt\Domestic_Oil_Business_Distributor_Management_System.pptx"
)

$ErrorActionPreference = "Stop"
$root = "C:\wamp64\www\unisex_salon_management"
$work = Join-Path $root "tmp\oil_er_update_package"
$diagramPath = Join-Path $root "outputs\domestic_oil_ppt\professional_er_diagram.png"

Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.Drawing

function Color($hex) {
    return [System.Drawing.ColorTranslator]::FromHtml($hex)
}

function Pt($x, $y) {
    return New-Object System.Drawing.PointF([single]$x, [single]$y)
}

function Rect($x, $y, $w, $h) {
    return New-Object System.Drawing.RectangleF([single]$x, [single]$y, [single]$w, [single]$h)
}

function RoundedRectPath($rect, $radius) {
    $path = New-Object System.Drawing.Drawing2D.GraphicsPath
    $d = $radius * 2
    $path.AddArc($rect.X, $rect.Y, $d, $d, 180, 90)
    $path.AddArc($rect.Right - $d, $rect.Y, $d, $d, 270, 90)
    $path.AddArc($rect.Right - $d, $rect.Bottom - $d, $d, $d, 0, 90)
    $path.AddArc($rect.X, $rect.Bottom - $d, $d, $d, 90, 90)
    $path.CloseFigure()
    return $path
}

function Draw-CenteredText($g, $text, $font, $brush, $rect) {
    $sf = New-Object System.Drawing.StringFormat
    $sf.Alignment = [System.Drawing.StringAlignment]::Center
    $sf.LineAlignment = [System.Drawing.StringAlignment]::Center
    $g.DrawString($text, $font, $brush, $rect, $sf)
}

function Draw-Entity($g, $name, $x, $y, $w, $h, $attrs) {
    $rect = Rect $x $y $w $h
    $shadow = Rect ($x + 5) ($y + 6) $w $h
    $shadowPath = RoundedRectPath $shadow 18
    $path = RoundedRectPath $rect 18
    $g.FillPath((New-Object System.Drawing.SolidBrush (Color "#D9E5DD")), $shadowPath)
    $g.FillPath((New-Object System.Drawing.SolidBrush (Color "#FFFFFF")), $path)
    $g.DrawPath((New-Object System.Drawing.Pen (Color "#143D2B"), 3), $path)
    $head = Rect $x $y $w 46
    $headPath = RoundedRectPath $head 18
    $g.FillPath((New-Object System.Drawing.SolidBrush (Color "#143D2B")), $headPath)
    $g.FillRectangle((New-Object System.Drawing.SolidBrush (Color "#143D2B")), $x, ($y + 24), $w, 24)
    Draw-CenteredText $g $name (New-Object System.Drawing.Font -ArgumentList "Segoe UI",18,([System.Drawing.FontStyle]::Bold)) (New-Object System.Drawing.SolidBrush (Color "#FFFFFF")) $head
    $attrText = ($attrs -join "`n")
    $body = Rect ($x + 14) ($y + 58) ($w - 28) ($h - 68)
    $sf = New-Object System.Drawing.StringFormat
    $sf.Alignment = [System.Drawing.StringAlignment]::Near
    $sf.LineAlignment = [System.Drawing.StringAlignment]::Near
    $font = New-Object System.Drawing.Font -ArgumentList "Segoe UI",13
    $g.DrawString($attrText, $font, (New-Object System.Drawing.SolidBrush (Color "#26342E")), $body, $sf)
}

function Draw-Diamond($g, $label, $cx, $cy, $w, $h) {
    $points = @(
        (Pt $cx ($cy - $h / 2)),
        (Pt ($cx + $w / 2) $cy),
        (Pt $cx ($cy + $h / 2)),
        (Pt ($cx - $w / 2) $cy)
    )
    $g.FillPolygon((New-Object System.Drawing.SolidBrush (Color "#FFF7E3")), $points)
    $g.DrawPolygon((New-Object System.Drawing.Pen (Color "#D6A23A"), 3), $points)
    Draw-CenteredText $g $label (New-Object System.Drawing.Font -ArgumentList "Segoe UI",13,([System.Drawing.FontStyle]::Bold)) (New-Object System.Drawing.SolidBrush (Color "#143D2B")) (Rect ($cx - $w / 2) ($cy - $h / 2) $w $h)
}

function Draw-Line($g, $x1, $y1, $x2, $y2, $label1 = "", $label2 = "") {
    $pen = New-Object System.Drawing.Pen (Color "#53675C"), 3
    $pen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $pen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $g.DrawLine($pen, [single]$x1, [single]$y1, [single]$x2, [single]$y2)
    $font = New-Object System.Drawing.Font -ArgumentList "Segoe UI",13,([System.Drawing.FontStyle]::Bold)
    $brush = New-Object System.Drawing.SolidBrush (Color "#143D2B")
    if ($label1) { $g.DrawString($label1, $font, $brush, [single]($x1 + 8), [single]($y1 - 24)) }
    if ($label2) { $g.DrawString($label2, $font, $brush, [single]($x2 - 25), [single]($y2 - 24)) }
}

$bmp = New-Object System.Drawing.Bitmap 1800, 1012
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::ClearTypeGridFit
$g.Clear((Color "#F8F6EE"))

$bgBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush (Rect 0 0 1800 1012), (Color "#FBFAF5"), (Color "#EEF5F0"), 35
$g.FillRectangle($bgBrush, 0, 0, 1800, 1012)

Draw-CenteredText $g "ER DIAGRAM" (New-Object System.Drawing.Font -ArgumentList "Segoe UI",36,([System.Drawing.FontStyle]::Bold)) (New-Object System.Drawing.SolidBrush (Color "#143D2B")) (Rect 0 28 1800 55)
Draw-CenteredText $g "Domestic Oil Business Distributor Management System" (New-Object System.Drawing.Font -ArgumentList "Segoe UI",17) (New-Object System.Drawing.SolidBrush (Color "#6A756E")) (Rect 0 86 1800 34)

$entities = @{
    Admin = @{ x=70; y=180; w=245; h=150; attrs=@("PK  admin_id", "username", "password") }
    OilStock = @{ x=690; y=170; w=290; h=185; attrs=@("PK  oil_id", "name", "price", "stock_litres", "badge", "is_active") }
    Users = @{ x=1285; y=165; w=285; h=185; attrs=@("PK  users_id", "first_name", "last_name", "email", "phone") }
    StockHistory = @{ x=70; y=520; w=305; h=190; attrs=@("PK  stkhs_id", "change_qty", "reason", "admin_user", "created_at") }
    Orders = @{ x=760; y=500; w=280; h=210; attrs=@("PK  order_id", "order_no", "status", "quantity", "unit_price", "total_amount") }
    Payments = @{ x=1340; y=575; w=300; h=185; attrs=@("PK  pay_id", "transaction_id", "method", "amount", "status") }
    Inquiries = @{ x=705; y=790; w=350; h=155; attrs=@("PK  inq_id", "name", "email", "product", "message", "status") }
    PasswordResets = @{ x=80; y=790; w=330; h=155; attrs=@("PK  pass_id", "email", "token", "expires_at") }
}

foreach ($key in $entities.Keys) {
    $e = $entities[$key]
    Draw-Entity $g $key.ToUpper() $e.x $e.y $e.w $e.h $e.attrs
}

Draw-Diamond $g "adjusts" 225 430 145 90
Draw-Line $g 225 330 225 385 "1" ""
Draw-Line $g 225 475 225 520 "" "N"

Draw-Diamond $g "ordered in" 835 425 170 100
Draw-Line $g 835 355 835 375 "1" ""
Draw-Line $g 835 475 835 500 "" "N"

Draw-Diamond $g "places" 1165 420 140 90
Draw-Line $g 1285 255 1165 375 "1" ""
Draw-Line $g 1165 465 1040 565 "" "N"

Draw-Diamond $g "tracks" 565 615 135 85
Draw-Line $g 375 615 498 615 "N" ""
Draw-Line $g 632 615 760 615 "" "1"

Draw-Diamond $g "paid via" 1190 645 145 90
Draw-Line $g 1040 645 1118 645 "M" ""
Draw-Line $g 1262 645 1340 645 "" "N"

Draw-Line $g 900 710 900 790 "N" "N"
Draw-Line $g 250 790 250 710 "" ""

$noteRect = Rect 1220 835 470 95
$notePath = RoundedRectPath $noteRect 18
$g.FillPath((New-Object System.Drawing.SolidBrush (Color "#FFFFFF")), $notePath)
$g.DrawPath((New-Object System.Drawing.Pen (Color "#D6A23A"), 2), $notePath)
Draw-CenteredText $g "Cardinality markers show how customers place orders, orders connect to products and payments, and stock changes are tracked by admin activity." (New-Object System.Drawing.Font -ArgumentList "Segoe UI",14) (New-Object System.Drawing.SolidBrush (Color "#26342E")) $noteRect

$bmp.Save($diagramPath, [System.Drawing.Imaging.ImageFormat]::Png)
$g.Dispose()
$bmp.Dispose()

Remove-Item -LiteralPath $work -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force -Path $work | Out-Null
$tmpZip = Join-Path $work "_deck.zip"
Copy-Item -LiteralPath $PptxPath -Destination $tmpZip -Force
[System.IO.Compression.ZipFile]::ExtractToDirectory($tmpZip, $work)
Remove-Item -LiteralPath $tmpZip -Force

Copy-Item -LiteralPath $diagramPath -Destination (Join-Path $work "ppt\media\er_diagram_high_res.png") -Force

$slide14 = Join-Path $work "ppt\slides\slide14.xml"
$xml = New-Object xml
$xml.PreserveWhitespace = $true
$xml.Load($slide14)
$mgr = New-Object System.Xml.XmlNamespaceManager($xml.NameTable)
$mgr.AddNamespace("a", "http://schemas.openxmlformats.org/drawingml/2006/main")
$nodes = @($xml.SelectNodes("//a:t", $mgr))
if ($nodes.Count -ge 1) { $nodes[0].InnerText = "ER Diagram / Database Model" }
if ($nodes.Count -ge 2) { $nodes[1].InnerText = "Domestic Oil Business Distributor Management System" }
$xml.Save($slide14)

Remove-Item -LiteralPath $PptxPath -Force
[System.IO.Compression.ZipFile]::CreateFromDirectory($work, $PptxPath)
Write-Output $PptxPath
