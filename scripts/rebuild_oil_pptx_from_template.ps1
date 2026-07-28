param(
    [string]$TemplatePptx = "C:\wamp64\www\unisex_salon_management\outputs\codex_salon_presentation\unisex_salon_management_codex_presentation.pptx",
    [string]$OutputPptx = "C:\wamp64\www\unisex_salon_management\outputs\domestic_oil_ppt\Domestic_Oil_Business_Distributor_Management_System.pptx"
)

$ErrorActionPreference = "Stop"
$root = "C:\wamp64\www\unisex_salon_management"
$work = Join-Path $root "tmp\oil_pptx_package"
$picDir = Join-Path $root "tmp\source_odg\Pictures"

Remove-Item -LiteralPath $work -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force -Path $work | Out-Null
New-Item -ItemType Directory -Force -Path (Split-Path -Parent $OutputPptx) | Out-Null

Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.Drawing

$zip = Join-Path $work "_template.zip"
Copy-Item -LiteralPath $TemplatePptx -Destination $zip -Force
[System.IO.Compression.ZipFile]::ExtractToDirectory($zip, $work)
Remove-Item -LiteralPath $zip -Force

$slides = @{
    1 = @(
        "BCA PROJECT PRESENTATION",
        "Domestic Oil Business",
        "Distributor Management System",
        "A web-based PHP and MySQL platform for product listing, customer orders, payment flow, stock tracking, inquiries, and admin reporting.",
        "Presented by Basavaraj S Biradar",
        "OIL",
        "DMS",
        "Academic Year 2025-2026"
    )
    2 = @(
        "Agenda",
        "02",
        "01",
        "Problem statement and objectives",
        "02",
        "Proposed system and workflow",
        "03",
        "Modules, database, and technology stack",
        "04",
        "Dashboard, ordering, testing, and results",
        "05",
        "Limitations, future scope, and Q&A"
    )
    3 = @(
        "Problem Statement",
        "03",
        "Small oil distributors often manage orders and inventory manually.",
        "Orders are received through phone calls or WhatsApp messages.",
        "Paper records increase the chance of billing and stock mistakes.",
        "Customers cannot view live product availability or order status.",
        "Admin has no single view of revenue, customers, inquiries, or low stock.",
        "Current Pain Points",
        "Manual order records",
        "No live inventory view",
        "No centralized reporting",
        "HIGH MANUAL EFFORT"
    )
    4 = @(
        "Solution Overview",
        "04",
        "A role-based web platform for domestic oil distribution management.",
        "Customers can browse oil products, check stock, place orders, pay, and track order history.",
        "Admins can manage oil products, stock quantities, orders, inquiries, customers, and reports.",
        "The payment flow records transaction details and supports multiple payment methods including COD.",
        "Stock history is maintained for every addition and order-based deduction.",
        "Customer Portal",
        "Admin Portal",
        "Payment Module",
        "Digital workflow, cleaner operations"
    )
    5 = @(
        "How It Works",
        "05",
        "1",
        "Register / login",
        "2",
        "Browse oil products",
        "3",
        "Place order",
        "4",
        "Confirm payment",
        "5",
        "Update stock",
        "6",
        "Track and report",
        "The system reduces manual work by connecting customer orders, payment records, stock deduction, and admin reporting in one flow."
    )
    6 = @(
        "Key Features",
        "06",
        "Product Catalogue",
        "Oil cards show name, price, description, minimum order, badge, and live stock status.",
        "Secure Access",
        "Registration and login with validation and protected customer/admin areas.",
        "Ordering",
        "Customers select quantity, confirm order details, and receive an order number.",
        "Stock Control",
        "Admin can add stock, create products, and view stock history.",
        "Reports",
        "Revenue, orders, customers, inquiries, and oil sales summaries."
    )
    7 = @(
        "Technology Stack",
        "07",
        "Frontend",
        "HTML, CSS, JavaScript",
        "Backend",
        "PHP application pages and server-side logic",
        "Database",
        "MySQL database managed with phpMyAdmin",
        "Runtime",
        "WAMP / XAMPP localhost environment",
        "Payments",
        "UPI, card, net banking, wallet, and cash on delivery options"
    )
    8 = @(
        "Demo / Screenshots",
        "08",
        "Live application screens from the domestic oil distribution workflow",
        "Home page",
        "Customer dashboard",
        "Admin panel",
        "Demo can continue through product browsing, secure login, order creation, payment confirmation, stock update, inquiry handling, and revenue reports."
    )
    9 = @(
        "System Modules",
        "09",
        "Main modules included in the project",
        "Public website for product discovery, about section, testimonials, process steps, and contact form.",
        "Customer dashboard for product browsing, live inventory, order history, and inquiry form.",
        "Payment page for order summary, payment method selection, and transaction confirmation.",
        "Admin dashboard for revenue, orders, customer stats, stock, inquiries, and reports.",
        "MODULES",
        "Customer",
        "Payment",
        "Admin"
    )
    10 = @(
        "Challenges & Learnings",
        "10",
        "Challenges",
        "Keeping stock quantity accurate after every purchase and stock addition.",
        "Designing a simple flow for both retail customers and bulk inquiries.",
        "Maintaining payment, order status, and stock history consistently.",
        "Learnings",
        "Database transactions are important for reliable order and stock updates.",
        "Dashboards are useful only when data is organized and updated in real time.",
        "Validation and role-based access improve security and usability."
    )
    11 = @(
        "Results & Impact",
        "11",
        "2",
        "User roles",
        "5",
        "Core modules",
        "5+",
        "Database tables",
        "100%",
        "Local demo ready",
        "Improves order handling, stock monitoring, and customer communication.",
        "Reduces manual records and gives the admin a clearer business overview.",
        "Supports a practical real-world workflow for small and medium oil distributors.",
        "Useful foundation for a commercial distributor management system."
    )
    12 = @(
        "Future Roadmap",
        "12",
        "Deploy the system online with cloud hosting and automatic backup.",
        "Build a mobile application for customers and delivery support.",
        "Add barcode or QR integration for faster stock handling.",
        "Support multi-branch distributor management.",
        "Add advanced analytics dashboard for sales trends and forecasting."
    )
    13 = @(
        "Thank You",
        "Questions & Answers",
        "Domestic Oil Business Distributor Management System",
        "Presented by Basavaraj S Biradar"
    )
    14 = @(
        "Database Model",
        "06"
    )
}

function Update-SlideText($slidePath, [string[]]$newText) {
    $xml = New-Object xml
    $xml.PreserveWhitespace = $true
    $xml.Load($slidePath)
    $mgr = New-Object System.Xml.XmlNamespaceManager($xml.NameTable)
    $mgr.AddNamespace("a", "http://schemas.openxmlformats.org/drawingml/2006/main")
    $nodes = @($xml.SelectNodes("//a:t", $mgr))
    for ($i = 0; $i -lt $nodes.Count; $i++) {
        if ($i -lt $newText.Count) {
            $nodes[$i].InnerText = $newText[$i]
        } else {
            $nodes[$i].InnerText = ""
        }
    }
    $xml.Save($slidePath)
}

foreach ($n in 1..14) {
    Update-SlideText (Join-Path $work "ppt\slides\slide$n.xml") $slides[$n]
}

$mediaTargets = @(
    "screenshot_index.png",
    "screenshot_login.png",
    "screenshot_register.png",
    "er_diagram_high_res.png"
)
$sourceImages = Get-ChildItem -LiteralPath $picDir -File -Include *.jpg,*.jpeg,*.png | Sort-Object Length -Descending | Select-Object -First $mediaTargets.Count

for ($i = 0; $i -lt $mediaTargets.Count; $i++) {
    $src = $sourceImages[$i].FullName
    $dest = Join-Path $work ("ppt\media\" + $mediaTargets[$i])
    $img = [System.Drawing.Image]::FromFile($src)
    try {
        $img.Save($dest, [System.Drawing.Imaging.ImageFormat]::Png)
    } finally {
        $img.Dispose()
    }
}

Remove-Item -LiteralPath $OutputPptx -Force -ErrorAction SilentlyContinue
[System.IO.Compression.ZipFile]::CreateFromDirectory($work, $OutputPptx)
Write-Output $OutputPptx
