param(
    [string]$OutputPath = "C:\wamp64\www\unisex_salon_management\outputs\domestic_oil_ppt\Domestic_Oil_Business_Distributor_Management_System.pptx"
)

$ErrorActionPreference = "Stop"

$root = "C:\wamp64\www\unisex_salon_management"
$picDir = Join-Path $root "tmp\source_odg\Pictures"
$outDir = Split-Path -Parent $OutputPath
New-Item -ItemType Directory -Force -Path $outDir | Out-Null

$msoFalse = 0
$msoTrue = -1
$ppLayoutBlank = 12
$ppSaveAsOpenXMLPresentation = 24
$msoTextOrientationHorizontal = 1
$msoAnchorMiddle = 3
$msoAlignCenter = 2
$msoAlignLeft = 1
$msoBringToFront = 0

$W = 13.333333
$H = 7.5
function In($v) { return ([double]$v) * 72 }

$green = [int]"0x12372A"
$green2 = [int]"0x1F5B45"
$gold = [int]"0xD6A23A"
$gold2 = [int]"0xF4C76A"
$cream = [int]"0xF8F5EC"
$white = [int]"0xFFFFFF"
$ink = [int]"0x1B1B1B"
$muted = [int]"0x5F6B63"
$line = [int]"0xD8D2C2"
$red = [int]"0xB6402A"
$blue = [int]"0x2F5496"

$images = Get-ChildItem -LiteralPath $picDir -File -Include *.jpg,*.png | Sort-Object Length -Descending
$screenshotA = $images[0].FullName
$screenshotB = $images[1].FullName
$screenshotC = $images[2].FullName

function Invoke-ComRetry([scriptblock]$Action) {
    for ($attempt = 1; $attempt -le 12; $attempt++) {
        try {
            return & $Action
        } catch {
            if ($attempt -eq 12) { throw }
            Start-Sleep -Milliseconds (250 * $attempt)
        }
    }
}

function Add-Shape($slide, $type, $x, $y, $w, $h, $fill, $lineColor = $null) {
    $shape = Invoke-ComRetry { $slide.Shapes.AddShape($type, (In $x), (In $y), (In $w), (In $h)) }
    $shape.Fill.ForeColor.RGB = $fill
    if ($null -eq $lineColor) {
        $shape.Line.Visible = $msoFalse
    } else {
        $shape.Line.Visible = $msoTrue
        $shape.Line.ForeColor.RGB = $lineColor
    }
    return $shape
}

function Add-Text($slide, $text, $x, $y, $w, $h, $size, $color, $bold = $false, $align = $msoAlignLeft, $font = "Aptos") {
    $box = Invoke-ComRetry { $slide.Shapes.AddTextbox($msoTextOrientationHorizontal, (In $x), (In $y), (In $w), (In $h)) }
    $box.TextFrame.TextRange.Text = $text
    try { $box.TextFrame.MarginLeft = 0 } catch {}
    try { $box.TextFrame.MarginRight = 0 } catch {}
    try { $box.TextFrame.MarginTop = 0 } catch {}
    try { $box.TextFrame.MarginBottom = 0 } catch {}
    $box.TextFrame.TextRange.Font.Name = $font
    $box.TextFrame.TextRange.Font.Size = $size
    $box.TextFrame.TextRange.Font.Color.RGB = $color
    if ($bold) { $box.TextFrame.TextRange.Font.Bold = $msoTrue }
    $box.TextFrame.TextRange.ParagraphFormat.Alignment = $align
    return $box
}

function Add-Bullets($slide, $items, $x, $y, $w, $h, $size = 18, $color = $ink) {
    $text = ($items -join "`r")
    $box = Add-Text $slide $text $x $y $w $h $size $color $false $msoAlignLeft
    $range = $box.TextFrame.TextRange
    $range.ParagraphFormat.Bullet.Visible = $msoTrue
    $range.ParagraphFormat.Bullet.Character = 8226
    $range.ParagraphFormat.SpaceAfter = 8
    return $box
}

function Add-Header($slide, $section, $title) {
    Add-Shape $slide 1 0 0 13.333333 0.52 $green | Out-Null
    Add-Text $slide $section 0.55 0.15 3.5 0.24 9 $gold2 $true | Out-Null
    Add-Text $slide "Domestic Oil Business Distributor Management System" 7.0 0.15 5.75 0.24 9 $white $false $msoAlignRight | Out-Null
    Add-Text $slide $title 0.6 0.82 8.5 0.55 29 $green $true | Out-Null
    Add-Shape $slide 1 0.6 1.45 1.05 0.05 $gold | Out-Null
}

function Add-Card($slide, $x, $y, $w, $h, $title, $body, $accent = $gold) {
    Add-Shape $slide 5 $x $y $w $h $white $line | Out-Null
    Add-Shape $slide 1 $x $y 0.10 $h $accent | Out-Null
    Add-Text $slide $title ($x + 0.25) ($y + 0.22) ($w - 0.45) 0.28 15 $green $true | Out-Null
    Add-Text $slide $body ($x + 0.25) ($y + 0.62) ($w - 0.45) ($h - 0.78) 12 $muted | Out-Null
}

function Add-Footer($slide, $num) {
    Add-Text $slide "JSS SMI UG & PG Studies, Dharwad" 0.6 7.08 4.7 0.18 8 $muted | Out-Null
    Add-Text $slide ("{0:00}" -f $num) 12.25 7.08 0.48 0.18 8 $muted $false $msoAlignRight | Out-Null
}

function Add-PictureFrame($slide, $path, $x, $y, $w, $h, $caption) {
    Add-Shape $slide 5 $x $y $w $h $white $line | Out-Null
    $pic = Invoke-ComRetry { $slide.Shapes.AddPicture($path, $msoFalse, $msoTrue, (In ($x + 0.08)), (In ($y + 0.08)), (In ($w - 0.16)), (In ($h - 0.45))) }
    $pic.LockAspectRatio = $msoTrue
    if ($pic.Width -gt (In ($w - 0.16))) { $pic.Width = In ($w - 0.16) }
    if ($pic.Height -gt (In ($h - 0.45))) { $pic.Height = In ($h - 0.45) }
    $pic.Left = In ($x + (($w - ($pic.Width / 72)) / 2))
    $pic.Top = In ($y + 0.08)
    Add-Text $slide $caption ($x + 0.14) ($y + $h - 0.27) ($w - 0.28) 0.16 8 $muted $false $msoAlignCenter | Out-Null
}

function New-Slide($index) {
    if ($null -eq $slidesCollection) { throw "No PowerPoint Slides collection is available." }
    $slide = $null
    for ($attempt = 1; $attempt -le 12; $attempt++) {
        try {
            $slide = $slidesCollection.Add($index, $ppLayoutBlank)
            break
        } catch {
            if ($attempt -eq 12) { throw }
            Start-Sleep -Milliseconds (350 * $attempt)
        }
    }
    Start-Sleep -Milliseconds 150
    return $slide
}

$ppt = New-Object -ComObject PowerPoint.Application
try { $ppt.Visible = $msoTrue } catch {}
try { $ppt.WindowState = 2 } catch {}
$pres = $ppt.Presentations.Add($msoTrue)
if ($null -eq $pres) {
    Start-Sleep -Seconds 2
    $pres = $ppt.ActivePresentation
}
if ($null -eq $pres) {
    throw "PowerPoint did not return a presentation object."
}
$slidesCollection = $pres.Slides
if ($null -eq $slidesCollection) {
    throw "PowerPoint did not return a Slides collection."
}
# Keep PowerPoint's default widescreen canvas. In this local COM session,
# changing PageSetup or delaying before first slide can make Slides unavailable.

# Slide 1
$s = New-Slide 1
Add-Shape $s 1 0 0 13.333333 7.5 $green | Out-Null
Add-Shape $s 1 0 5.6 13.333333 1.9 $green2 | Out-Null
Add-Shape $s 9 9.15 -0.2 4.5 4.5 $gold | Out-Null
Add-Shape $s 9 10.05 0.65 2.75 2.75 $green | Out-Null
Add-Text $s "KARNATAK UNIVERSITY, DHARWAD" 0.85 0.55 5.6 0.28 13 $gold2 $true | Out-Null
Add-Text $s "A PROJECT PRESENTATION ON" 0.85 1.45 4.5 0.3 13 $white $true | Out-Null
Add-Text $s "Domestic Oil Business`rDistributor Management System" 0.85 1.88 8.1 1.35 34 $white $true | Out-Null
Add-Shape $s 1 0.85 3.45 1.25 0.06 $gold | Out-Null
Add-Text $s "Bachelor of Computer Applications (BCA) | Academic Year 2025-2026" 0.85 3.72 7.4 0.28 14 $cream | Out-Null
Add-Text $s "Submitted by: Basavaraj S Biradar`rBCA VI Semester | Reg No: U02BF23S0345" 0.85 5.95 5.4 0.55 14 $white $true | Out-Null
Add-Text $s "Project Guide: Prof. Vijay Hiremath`rDepartment of Computer Science" 7.0 5.95 5.4 0.55 14 $white $false $msoAlignRight | Out-Null

# Slide 2
$s = New-Slide 2; Add-Header $s "PROJECT CONTEXT" "Project Overview"
Add-Text $s "Sangameshwar Oil is a full-stack PHP web application built to digitize the sale and distribution of cold-pressed homemade Indian oils." 0.75 1.72 7.15 0.78 20 $ink $true | Out-Null
Add-Bullets $s @("Connects producer, retail customers, and bulk customers through one web platform.","Provides product discovery, customer ordering, payments, stock tracking, inquiries, and reporting.","Uses an e-commerce-style interface for a small or medium oil distribution business.") 0.9 2.82 6.45 1.55 16 $ink | Out-Null
Add-Card $s 8.1 1.55 4.35 1.05 "Project Type" "Web-based distributor management system" $gold
Add-Card $s 8.1 2.88 4.35 1.05 "Main Users" "Customer and Admin" $green2
Add-Card $s 8.1 4.20 4.35 1.05 "Core Value" "Automates ordering, stock, payment, and reporting" $blue
Add-Footer $s 2

# Slide 3
$s = New-Slide 3; Add-Header $s "PROBLEM STUDY" "Existing System Challenges"
Add-Card $s 0.75 1.65 3.65 1.55 "Manual Orders" "Orders were handled through phone calls or WhatsApp messages, creating dependency on informal communication." $red
Add-Card $s 4.85 1.65 3.65 1.55 "Paper Records" "Customer orders, stock movement, and payments were maintained manually in notebooks." $gold
Add-Card $s 8.95 1.65 3.65 1.55 "No Live Stock View" "Customers could not check product availability, prices, or order status before contacting the seller." $blue
Add-Card $s 2.0 4.0 4.15 1.45 "Weak Reporting" "The admin had no centralized view of revenue, pending orders, customer data, or bulk inquiries." $green2
Add-Card $s 7.15 4.0 4.15 1.45 "Low-Stock Risk" "Stock was updated manually and had no alert mechanism for low quantity items." $red
Add-Footer $s 3

# Slide 4
$s = New-Slide 4; Add-Header $s "PROJECT GOALS" "Objectives"
Add-Bullets $s @("Enable customers to browse oil products, check stock status, and place orders online.","Provide secure registration and login with validation and password protection.","Support payments through UPI, card, net banking, wallet, and cash on delivery.","Give the admin a dashboard to manage products, stock, orders, inquiries, customers, and reports.","Maintain stock history for every addition and order-based deduction.","Generate useful business summaries for sales and inventory monitoring.") 0.9 1.75 6.15 3.95 17 $ink | Out-Null
Add-Shape $s 5 7.7 1.65 4.55 3.95 $green $null | Out-Null
Add-Text $s "Objective Focus" 8.05 2.02 3.85 0.35 19 $gold2 $true $msoAlignCenter | Out-Null
Add-Text $s "Accuracy`rSpeed`rTransparency`rControl" 8.05 2.75 3.85 1.65 31 $white $true $msoAlignCenter | Out-Null
Add-Text $s "The system reduces manual workload while improving customer experience and admin decision-making." 8.15 5.0 3.65 0.45 13 $cream $false $msoAlignCenter | Out-Null
Add-Footer $s 4

# Slide 5
$s = New-Slide 5; Add-Header $s "SOLUTION" "Proposed System"
Add-Card $s 0.85 1.65 3.7 1.65 "Public Website" "Displays oil products, pricing, stock status, about section, testimonials, process steps, and contact form." $green2
Add-Card $s 4.85 1.65 3.7 1.65 "Customer Dashboard" "Allows authenticated customers to browse inventory, select quantity, place orders, pay, and view order history." $gold
Add-Card $s 8.85 1.65 3.7 1.65 "Admin Panel" "Centralizes orders, stock, inquiries, customers, revenue metrics, and sales reports for business control." $blue
Add-Text $s "Key automation: stock is deducted after successful order flow, payment details are recorded, and order numbers are generated for tracking." 1.2 4.2 10.9 0.62 18 $ink $true $msoAlignCenter | Out-Null
Add-Shape $s 1 1.35 5.25 10.65 0.58 $cream $line | Out-Null
Add-Text $s "Discover products  ->  Register/Login  ->  Add quantity  ->  Payment confirmation  ->  Track order status" 1.55 5.43 10.25 0.18 13 $green $true $msoAlignCenter | Out-Null
Add-Footer $s 5

# Slide 6
$s = New-Slide 6; Add-Header $s "SYSTEM MODULES" "User Roles and Modules"
Add-Text $s "Customer Side" 1.0 1.72 4.7 0.35 20 $green $true | Out-Null
Add-Bullets $s @("Registration and secure login","Product catalogue with live stock","Order placement and payment selection","Order history and status tracking","Bulk order inquiry form") 1.15 2.22 4.7 2.5 16 $ink | Out-Null
Add-Text $s "Admin Side" 7.0 1.72 4.7 0.35 20 $green $true | Out-Null
Add-Bullets $s @("Dashboard overview and revenue stats","Product and stock management","Order status update flow","Customer and inquiry management","Reports and stock history") 7.15 2.22 4.7 2.5 16 $ink | Out-Null
Add-Shape $s 9 5.65 2.55 2.05 2.05 $gold | Out-Null
Add-Text $s "SYSTEM`rCORE" 5.98 3.16 1.4 0.55 20 $green $true $msoAlignCenter | Out-Null
Add-Footer $s 6

# Slide 7
$s = New-Slide 7; Add-Header $s "PROCESS LOGIC" "System Workflow"
$steps = @("Login","Browse Oils","Place Order","Choose Payment","Update Stock","Track & Report")
$x = 0.75
for ($i=0; $i -lt $steps.Count; $i++) {
    Add-Shape $s 5 $x 2.2 1.55 0.9 $white $line | Out-Null
    Add-Text $s ($steps[$i]) ($x + 0.12) 2.48 1.3 0.2 12 $green $true $msoAlignCenter | Out-Null
    if ($i -lt $steps.Count - 1) {
        Add-Shape $s 13 ($x + 1.62) 2.49 0.55 0.25 $gold | Out-Null
    }
    $x += 2.05
}
Add-Bullets $s @("Admin adds products and stock before customer ordering starts.","Order transactions reduce stock quantity in real time.","Dashboard summaries help monitor sales, customers, inquiries, and low stock.","Reports support business review and future planning.") 1.25 4.25 10.8 1.35 17 $ink | Out-Null
Add-Footer $s 7

# Slide 8
$s = New-Slide 8; Add-Header $s "TECHNICAL DESIGN" "Technology Stack and Architecture"
Add-Card $s 0.85 1.72 2.75 1.15 "Front End" "HTML, CSS, JavaScript" $blue
Add-Card $s 3.95 1.72 2.75 1.15 "Back End" "PHP" $green2
Add-Card $s 7.05 1.72 2.75 1.15 "Database" "MySQL" $gold
Add-Card $s 10.15 1.72 2.35 1.15 "Server" "WAMP / XAMPP" $red
Add-Shape $s 1 1.15 4.05 2.1 0.68 $cream $line | Out-Null
Add-Text $s "Browser UI" 1.38 4.28 1.65 0.18 13 $green $true $msoAlignCenter | Out-Null
Add-Shape $s 13 3.55 4.26 0.75 0.22 $gold | Out-Null
Add-Shape $s 1 4.55 4.05 2.1 0.68 $cream $line | Out-Null
Add-Text $s "PHP Logic" 4.78 4.28 1.65 0.18 13 $green $true $msoAlignCenter | Out-Null
Add-Shape $s 13 6.95 4.26 0.75 0.22 $gold | Out-Null
Add-Shape $s 1 7.95 4.05 2.1 0.68 $cream $line | Out-Null
Add-Text $s "MySQL DB" 8.18 4.28 1.65 0.18 13 $green $true $msoAlignCenter | Out-Null
Add-Shape $s 13 10.35 4.26 0.75 0.22 $gold | Out-Null
Add-Shape $s 1 11.15 4.05 1.25 0.68 $cream $line | Out-Null
Add-Text $s "Reports" 11.28 4.28 0.95 0.18 13 $green $true $msoAlignCenter | Out-Null
Add-Footer $s 8

# Slide 9
$s = New-Slide 9; Add-Header $s "DATA MODEL" "Database Design"
$tables = @(
    @("users", "Customer profile, login details, contact information"),
    @("oil_stock", "Oil product name, price, stock litres, badge, active status"),
    @("orders", "Order number, customer, oil, quantity, amount, payment, status"),
    @("inquiries", "Bulk inquiry product, quantity, message, response status"),
    @("stock_history", "Stock addition/deduction log with reason and date")
)
$y = 1.65
foreach ($row in $tables) {
    Add-Shape $s 1 0.9 $y 2.25 0.58 $green2 $null | Out-Null
    Add-Text $s $row[0] 1.08 ($y + 0.18) 1.85 0.16 12 $white $true $msoAlignCenter | Out-Null
    Add-Shape $s 1 3.15 $y 8.95 0.58 $white $line | Out-Null
    Add-Text $s $row[1] 3.38 ($y + 0.18) 8.35 0.16 12 $ink | Out-Null
    $y += 0.82
}
Add-Text $s "Relationships connect users to orders and inquiries, while products connect to orders and stock history for accurate inventory control." 1.0 6.15 11.0 0.35 14 $muted $false $msoAlignCenter | Out-Null
Add-Footer $s 9

# Slide 10
$s = New-Slide 10; Add-Header $s "ADMIN FEATURES" "Admin Dashboard"
Add-Bullets $s @("Shows revenue, total orders, customers, inquiries, and low stock indicators.","Allows admin to add new oil products and update stock quantities.","Supports order status updates such as paid, processing, shipped, delivered, and cancelled.","Includes inquiry handling, customer records, reports, and stock history.") 0.85 1.75 5.2 2.2 15 $ink | Out-Null
Add-PictureFrame $s $screenshotA 6.5 1.55 5.85 4.45 "Source project screenshot / interface visual"
Add-Footer $s 10

# Slide 11
$s = New-Slide 11; Add-Header $s "CUSTOMER FLOW" "Customer Ordering and Payment"
Add-PictureFrame $s $screenshotB 0.85 1.55 5.5 4.25 "Customer-facing screen visual"
Add-Card $s 6.85 1.7 2.55 1.25 "Browse" "Customers view product cards with price and live stock." $green2
Add-Card $s 9.75 1.7 2.55 1.25 "Order" "Quantity is selected and total bill is calculated." $gold
Add-Card $s 6.85 3.35 2.55 1.25 "Pay" "UPI, card, wallet, net banking, or COD options." $blue
Add-Card $s 9.75 3.35 2.55 1.25 "Track" "Order number and order status are available later." $red
Add-Footer $s 11

# Slide 12
$s = New-Slide 12; Add-Header $s "QUALITY CHECK" "Testing Strategy"
Add-Card $s 0.9 1.72 3.55 1.35 "Unit Testing" "Individual PHP pages, form validations, calculations, and database operations are checked separately." $blue
Add-Card $s 4.9 1.72 3.55 1.35 "Integration Testing" "Login, ordering, payment, stock deduction, order history, and reports are verified together." $green2
Add-Card $s 8.9 1.72 3.55 1.35 "System Testing" "The complete application is tested on the local WAMP/XAMPP server environment." $gold
Add-Bullets $s @("Validate required fields, email format, quantity limits, and payment selection.","Verify stock updates after order placement and admin stock addition.","Check role-based access for customer dashboard and admin dashboard.","Confirm order status filters, reports, and inquiry status updates.") 1.2 4.0 10.6 1.45 16 $ink | Out-Null
Add-Footer $s 12

# Slide 13
$s = New-Slide 13; Add-Header $s "EVALUATION" "Benefits and Limitations"
Add-Text $s "Benefits" 1.0 1.7 4.5 0.3 20 $green $true | Out-Null
Add-Bullets $s @("Reduces manual workload and record-keeping errors.","Improves customer convenience with online product and order access.","Gives admin faster control over stock, customers, orders, and inquiries.","Creates organized business data for reports and decision-making.") 1.1 2.2 5.1 2.1 15 $ink | Out-Null
Add-Text $s "Limitations" 7.1 1.7 4.5 0.3 20 $green $true | Out-Null
Add-Bullets $s @("Designed mainly for small and medium businesses.","Runs on local server deployment in the current version.","No dedicated mobile application yet.","Scalability and advanced security can be improved in future versions.") 7.2 2.2 5.1 2.1 15 $ink | Out-Null
Add-Footer $s 13

# Slide 14
$s = New-Slide 14
Add-Shape $s 1 0 0 13.333333 7.5 $green | Out-Null
Add-Shape $s 1 0 0 13.333333 0.55 $green2 | Out-Null
Add-Text $s "Future Scope" 0.9 0.95 4.6 0.45 28 $gold2 $true | Out-Null
Add-Bullets $s @("Cloud-based deployment for online access and backup.","Mobile application support for customers and delivery staff.","Barcode or QR integration for stock handling.","Multi-branch management for larger distributors.","Advanced analytics dashboard for trends and forecasting.") 1.1 1.65 5.5 2.8 16 $white | Out-Null
Add-Shape $s 1 7.3 1.1 4.7 3.5 $cream $null | Out-Null
Add-Text $s "Thank You" 7.75 1.85 3.8 0.62 36 $green $true $msoAlignCenter | Out-Null
Add-Text $s "Questions & Discussion" 7.85 2.65 3.6 0.35 18 $muted $false $msoAlignCenter | Out-Null
Add-Text $s "Domestic Oil Business Distributor Management System" 7.85 3.45 3.6 0.5 13 $green $true $msoAlignCenter | Out-Null
Add-Text $s "Presented by Basavaraj S Biradar" 7.85 4.02 3.6 0.24 11 $muted $false $msoAlignCenter | Out-Null

$finalPres = $ppt.ActivePresentation
if ($null -eq $finalPres) { $finalPres = $pres }
$finalPres.SaveAs($OutputPath, $ppSaveAsOpenXMLPresentation)
try { $finalPres.Close() } catch {}
try { $ppt.Quit() } catch {}

[System.Runtime.InteropServices.Marshal]::ReleaseComObject($pres) | Out-Null
[System.Runtime.InteropServices.Marshal]::ReleaseComObject($ppt) | Out-Null

Write-Output $OutputPath
