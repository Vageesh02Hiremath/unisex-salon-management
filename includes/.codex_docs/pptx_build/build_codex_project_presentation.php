<?php

$root = realpath(__DIR__ . '/../..');
$outDir = $root . DIRECTORY_SEPARATOR . 'outputs';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$pptxPath = $outDir . DIRECTORY_SEPARATOR . 'vageesh_hiremath_fabulous_unisex_salon_final_presentation.pptx';
$W = 12192000;
$H = 6858000;

function esc($text) {
    return htmlspecialchars((string)$text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function emu($px) {
    return (int)round($px * 9525);
}

function hexColor($hex) {
    return strtoupper(ltrim($hex, '#'));
}

function fillXml($hex) {
    return '<a:solidFill><a:srgbClr val="' . hexColor($hex) . '"/></a:solidFill>';
}

function shapeXml($id, $x, $y, $w, $h, $fill, $line = '#E2E8F0', $geom = 'rect', $round = false) {
    $lineXml = $line === null
        ? '<a:ln><a:noFill/></a:ln>'
        : '<a:ln w="9525"><a:solidFill><a:srgbClr val="' . hexColor($line) . '"/></a:solidFill></a:ln>';
    $geomXml = $round
        ? '<a:prstGeom prst="roundRect"><a:avLst><a:gd name="adj" fmla="val 11000"/></a:avLst></a:prstGeom>'
        : '<a:prstGeom prst="' . esc($geom) . '"><a:avLst/></a:prstGeom>';
    return '<p:sp><p:nvSpPr><p:cNvPr id="' . $id . '" name="Shape ' . $id . '"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="' . emu($x) . '" y="' . emu($y) . '"/><a:ext cx="' . emu($w) . '" cy="' . emu($h) . '"/></a:xfrm>' . $geomXml . fillXml($fill) . $lineXml . '</p:spPr><p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody></p:sp>';
}

function textXml($id, $x, $y, $w, $h, $text, $size = 24, $color = '#172033', $bold = false, $align = 'l', $fill = null) {
    $paragraphs = is_array($text) ? $text : [(string)$text];
    $boldXml = $bold ? ' b="1"' : '';
    $fillPart = $fill ? fillXml($fill) : '<a:noFill/>';
    $pXml = '';
    foreach ($paragraphs as $line) {
        $pXml .= '<a:p><a:pPr algn="' . esc($align) . '"/><a:r><a:rPr lang="en-US" sz="' . (int)($size * 100) . '"' . $boldXml . '><a:solidFill><a:srgbClr val="' . hexColor($color) . '"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>' . esc($line) . '</a:t></a:r><a:endParaRPr lang="en-US" sz="' . (int)($size * 100) . '"/></a:p>';
    }
    return '<p:sp><p:nvSpPr><p:cNvPr id="' . $id . '" name="Text ' . $id . '"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="' . emu($x) . '" y="' . emu($y) . '"/><a:ext cx="' . emu($w) . '" cy="' . emu($h) . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom>' . $fillPart . '<a:ln><a:noFill/></a:ln></p:spPr><p:txBody><a:bodyPr wrap="square" lIns="76200" tIns="38100" rIns="76200" bIns="38100"><a:normAutofit fontScale="85000" lnSpcReduction="20000"/></a:bodyPr><a:lstStyle/>' . $pXml . '</p:txBody></p:sp>';
}

function headerXml($title, $num) {
    return shapeXml(2, 0, 0, 1280, 78, '#111827', null)
        . textXml(3, 64, 18, 760, 42, $title, 25, '#FFFFFF', true)
        . textXml(4, 1110, 22, 95, 32, str_pad((string)$num, 2, '0', STR_PAD_LEFT), 16, '#FBCFE8', true, 'r');
}

function bulletRows($startId, $x, $y, $w, $items, $rowH = 58, $fontSize = 20) {
    $xml = '';
    foreach ($items as $i => $item) {
        $top = $y + ($i * $rowH);
        $xml .= shapeXml($startId + ($i * 3), $x, $top + 10, 11, 11, '#C24B8B', null, 'ellipse');
        $xml .= textXml($startId + ($i * 3) + 1, $x + 26, $top, $w - 26, $rowH, $item, $fontSize, '#334155');
    }
    return $xml;
}

function cardXml($id, $x, $y, $w, $h, $title, $body, $accent = '#C24B8B') {
    return shapeXml($id, $x, $y, $w, $h, '#FFFFFF', '#D8E0EA', 'rect', true)
        . shapeXml($id + 1, $x + 24, $y + 24, 48, 48, '#FCE7F3', null, 'ellipse')
        . textXml($id + 2, $x + 90, $y + 22, $w - 118, 34, $title, 21, '#172033', true)
        . textXml($id + 3, $x + 90, $y + 62, $w - 120, $h - 72, $body, 17, '#475569')
        . shapeXml($id + 4, $x + 24, $y + 86, 48, 5, $accent, null);
}

function slideXml($elements, $bg = '#F8FAFC') {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
        . '<p:cSld><p:bg><p:bgPr>' . fillXml($bg) . '<a:effectLst/></p:bgPr></p:bg><p:spTree>'
        . '<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>'
        . $elements
        . '</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sld>';
}

$slides = [];

// 1. Title
$s = shapeXml(10, 0, 0, 1280, 720, '#111827', null);
$s .= shapeXml(11, 835, 0, 445, 720, '#C24B8B', null);
$s .= shapeXml(12, 900, 110, 315, 315, '#FCE7F3', null, 'ellipse');
$s .= textXml(13, 86, 86, 620, 38, 'OPENAI CODEX ASSISTED PROJECT', 19, '#FBCFE8', true);
$s .= textXml(14, 82, 175, 670, 135, ['Fabulous Unisex Salon', 'Management System'], 43, '#FFFFFF', true);
$s .= textXml(15, 86, 342, 620, 92, 'A web-based salon management platform for appointments, services, staff workflow, billing, feedback, reporting, and Razorpay-ready payments.', 22, '#DDE6F3');
$s .= textXml(16, 86, 590, 430, 38, 'Presented by Vageesh Hiremath', 21, '#FFFFFF', true);
$s .= textXml(17, 928, 215, 260, 85, ['SALON', 'OPS'], 39, '#111827', true, 'c');
$slides[] = slideXml($s, '#111827');

// 2. Agenda
$s = headerXml('Agenda', 2);
$agenda = [
    'Problem and project objective',
    'Solution overview and workflow',
    'Features and technology stack',
    'Demo screens and Codex contribution',
    'Challenges, results, roadmap, and Q&A'
];
foreach ($agenda as $i => $item) {
    $y = 132 + ($i * 92);
    $s .= shapeXml(30 + $i, 120, $y, 1040, 66, '#FFFFFF', '#D8E0EA', 'rect', true);
    $s .= textXml(50 + $i, 145, $y + 12, 72, 32, '0' . ($i + 1), 20, '#C24B8B', true, 'c');
    $s .= textXml(70 + $i, 235, $y + 12, 820, 34, $item, 22, '#172033', true);
}
$slides[] = slideXml($s);

// 3. Problem
$s = headerXml('Problem Statement', 3);
$s .= textXml(30, 72, 128, 500, 72, 'Salon operations can become difficult to manage manually.', 30, '#172033', true);
$s .= bulletRows(40, 82, 235, 520, [
    'Manual appointment records increase double-booking risk.',
    'Billing and payment tracking require repeated admin effort.',
    'Staff schedules and service availability are not centralized.',
    'Customers need a simple way to book and track appointments.'
], 66, 19);
$s .= shapeXml(70, 720, 135, 390, 360, '#FFFFFF', '#D8E0EA', 'rect', true);
$s .= textXml(71, 765, 185, 300, 36, 'Current Pain Points', 25, '#991B1B', true, 'c');
$s .= bulletRows(80, 780, 260, 295, ['Registers and phone calls', 'Separate billing records', 'No instant slot visibility'], 64, 19);
$s .= shapeXml(95, 805, 438, 220, 44, '#FEE2E2', null, 'rect', true);
$s .= textXml(96, 820, 448, 190, 24, 'HIGH FRICTION', 15, '#991B1B', true, 'c');
$slides[] = slideXml($s);

// 4. Solution
$s = headerXml('Solution Overview', 4);
$s .= textXml(30, 72, 125, 600, 68, 'A single role-based platform for salon operations.', 31, '#172033', true);
$s .= bulletRows(40, 82, 225, 590, [
    'Customers can browse services, book slots, view bills, and submit feedback.',
    'Admins can manage services, staff, appointments, billing, reports, and payments.',
    'Staff can view assigned appointments and daily schedules.',
    'Razorpay can be used for online payment after account keys are configured.'
], 68, 19);
$roles = [['Customer Portal', '#DBEAFE'], ['Admin Portal', '#FCE7F3'], ['Staff Portal', '#DCFCE7']];
foreach ($roles as $i => $role) {
    $s .= shapeXml(80 + $i, 760, 150 + ($i * 115), 330, 72, $role[1], null, 'rect', true);
    $s .= textXml(90 + $i, 790, 170 + ($i * 115), 270, 30, $role[0], 22, '#172033', true, 'c');
}
$s .= textXml(105, 765, 530, 330, 34, 'Connected workflow, cleaner operations', 20, '#C24B8B', true, 'c');
$slides[] = slideXml($s);

// 5. Workflow
$s = headerXml('How It Works', 5);
$steps = [
    ['1', 'Select services'],
    ['2', 'Check slot'],
    ['3', 'Create booking'],
    ['4', 'Manage status'],
    ['5', 'Generate bill']
];
foreach ($steps as $i => $step) {
    $x = 82 + ($i * 225);
    $s .= shapeXml(30 + $i, $x, 185, 165, 95, '#FFFFFF', '#CBD5E1', 'rect', true);
    $s .= textXml(45 + $i, $x + 18, 205, 38, 28, $step[0], 19, '#C24B8B', true, 'c');
    $s .= textXml(60 + $i, $x + 58, 203, 88, 48, $step[1], 18, '#172033', true, 'c');
    if ($i < 4) {
        $s .= shapeXml(80 + $i, $x + 172, 229, 42, 3, '#C24B8B', null);
    }
}
$s .= shapeXml(100, 185, 390, 910, 130, '#111827', null, 'rect', true);
$s .= textXml(101, 225, 420, 830, 38, 'PHP + MySQL + AJAX + Razorpay + Role-Based Authentication', 25, '#FFFFFF', true, 'c');
$s .= textXml(102, 245, 470, 790, 32, 'Codex was used to inspect the codebase, update features, fix issues, and verify project readiness.', 17, '#CBD5E1', false, 'c');
$slides[] = slideXml($s);

// 6. Features
$s = headerXml('Key Features', 6);
$features = [
    ['Smart Booking', 'Service, date, time, staff, and slot availability checks.'],
    ['Role Dashboards', 'Separate workflows for admin, staff, and customers.'],
    ['Billing', 'Bills are generated after completed appointments.'],
    ['Payments', 'Supports pay-at-salon and Razorpay-ready payment flow.'],
    ['Reports', 'Revenue, feedback, appointment, and service insights.']
];
foreach ($features as $i => $f) {
    $x = 82 + (($i % 3) * 382);
    $y = 135 + (floor($i / 3) * 190);
    $s .= cardXml(30 + ($i * 10), $x, $y, 330, 145, $f[0], $f[1]);
}
$slides[] = slideXml($s);

// 7. Tech Stack
$s = headerXml('Technology Stack', 7);
$stack = [
    ['Frontend', 'HTML, CSS, JavaScript, AJAX'],
    ['Backend', 'PHP procedural application structure'],
    ['Database', 'MySQL database with phpMyAdmin'],
    ['Runtime', 'WAMP localhost environment'],
    ['Payments', 'Razorpay integration and INR payment tracking'],
    ['AI Assistance', 'OpenAI Codex for code changes and verification']
];
foreach ($stack as $i => $row) {
    $y = 125 + ($i * 72);
    $s .= shapeXml(30 + $i, 115, $y, 1050, 54, $i % 2 ? '#FFFFFF' : '#F1F5F9', '#D8E0EA', 'rect', true);
    $s .= textXml(50 + $i, 145, $y + 12, 210, 28, $row[0], 19, '#C24B8B', true);
    $s .= textXml(70 + $i, 380, $y + 12, 710, 28, $row[1], 19, '#172033');
}
$slides[] = slideXml($s);

// 8. Demo
$s = headerXml('Demo / Screenshots', 8);
$s .= textXml(30, 80, 112, 850, 44, 'Use this slide to place final screenshots during submission or demo.', 25, '#172033', true);
$shots = ['Landing page and contact section', 'Customer booking workflow', 'Admin dashboard and reports', 'Bill print / view page'];
foreach ($shots as $i => $shot) {
    $x = 90 + (($i % 2) * 550);
    $y = 205 + (floor($i / 2) * 170);
    $s .= shapeXml(40 + $i, $x, $y, 480, 120, '#E8EEF6', '#94A3B8', 'rect', true);
    $s .= textXml(55 + $i, $x + 38, $y + 40, 405, 35, $shot, 21, '#334155', true, 'c');
}
$slides[] = slideXml($s);

// 9. Codex
$s = headerXml('Codex Integration', 9);
$s .= textXml(30, 76, 124, 600, 60, 'How OpenAI Codex helped build and improve the project', 29, '#172033', true);
$s .= bulletRows(40, 88, 220, 585, [
    'Reviewed PHP files, SQL schema, and AJAX endpoints.',
    'Updated contact details, bill views, and Razorpay readiness.',
    'Fixed payment-state issues across booking and billing.',
    'Verified pages with syntax checks, database checks, and localhost responses.'
], 64, 19);
$s .= shapeXml(80, 765, 160, 340, 275, '#111827', null, 'rect', true);
$s .= textXml(81, 805, 205, 260, 42, 'CODEX', 36, '#FFFFFF', true, 'c');
$s .= textXml(82, 815, 275, 240, 90, ['Analyze', 'Improve', 'Verify'], 28, '#FBCFE8', true, 'c');
$slides[] = slideXml($s);

// 10. Challenges
$s = headerXml('Challenges & Learnings', 10);
$s .= shapeXml(30, 90, 145, 500, 395, '#FFFFFF', '#D8E0EA', 'rect', true);
$s .= textXml(31, 130, 185, 420, 34, 'Challenges', 27, '#B91C1C', true);
$s .= bulletRows(40, 135, 255, 390, [
    'Keeping database schema aligned with payment changes.',
    'Removing placeholder branding from project views.',
    'Avoiding duplicate or pending records after online payment.'
], 74, 18);
$s .= shapeXml(70, 690, 145, 500, 395, '#FFFFFF', '#D8E0EA', 'rect', true);
$s .= textXml(71, 730, 185, 420, 34, 'Learnings', 27, '#15803D', true);
$s .= bulletRows(80, 735, 255, 390, [
    'Payment status must be consistent across booking and billing.',
    'Small schema issues can affect real workflows.',
    'Regular linting and endpoint checks improve reliability.'
], 74, 18);
$slides[] = slideXml($s);

// 11. Results
$s = headerXml('Results & Impact', 11);
$metrics = [['3', 'User roles'], ['24+', 'Services'], ['13', 'Core tables'], ['100%', 'PHP lint pass']];
foreach ($metrics as $i => $m) {
    $x = 90 + ($i * 285);
    $s .= shapeXml(30 + $i, $x, 145, 220, 140, ['#DBEAFE', '#FCE7F3', '#DCFCE7', '#FEF3C7'][$i], null, 'rect', true);
    $s .= textXml(45 + $i, $x + 20, 175, 180, 48, $m[0], 38, '#111827', true, 'c');
    $s .= textXml(60 + $i, $x + 20, 235, 180, 28, $m[1], 18, '#334155', true, 'c');
}
$s .= bulletRows(80, 120, 370, 980, [
    'Improves appointment organization and staff visibility.',
    'Reduces manual billing and payment tracking effort.',
    'Provides a cleaner experience for customers and administrators.',
    'Makes the project ready for demonstration and future deployment.'
], 58, 20);
$slides[] = slideXml($s);

// 12. Roadmap
$s = headerXml('Future Roadmap', 12);
$roadmap = [
    'Perform a complete Razorpay test payment demo.',
    'Add email or SMS reminders for appointments.',
    'Improve staff leave management and calendar planning.',
    'Add loyalty points, coupons, and customer offers.',
    'Prepare hosting, backup, and deployment documentation.'
];
foreach ($roadmap as $i => $item) {
    $y = 128 + ($i * 82);
    $s .= shapeXml(30 + $i, 140, $y + 10, 34, 34, '#C24B8B', null, 'ellipse');
    $s .= shapeXml(45 + $i, 188, $y, 900, 56, '#FFFFFF', '#D8E0EA', 'rect', true);
    $s .= textXml(60 + $i, 215, $y + 13, 830, 28, $item, 20, '#172033');
}
$slides[] = slideXml($s);

// 13. Thank you
$s = shapeXml(10, 0, 0, 1280, 720, '#111827', null);
$s .= shapeXml(11, 0, 0, 1280, 18, '#C24B8B', null);
$s .= textXml(12, 160, 150, 960, 80, 'Thank You', 54, '#FFFFFF', true, 'c');
$s .= textXml(13, 215, 260, 850, 54, 'Questions & Answers', 32, '#FBCFE8', true, 'c');
$s .= shapeXml(14, 300, 420, 680, 110, '#FFFFFF', null, 'rect', true);
$s .= textXml(15, 340, 448, 600, 32, 'Fabulous Unisex Salon Management System', 23, '#172033', true, 'c');
$s .= textXml(16, 340, 488, 600, 26, 'Presented by Vageesh Hiremath', 18, '#475569', false, 'c');
$slides[] = slideXml($s, '#111827');

$zip = new ZipArchive();
if ($zip->open($pptxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Unable to create PPTX\n");
    exit(1);
}

$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/><Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>';
for ($i = 1; $i <= count($slides); $i++) {
    $contentTypes .= '<Override PartName="/ppt/slides/slide' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>';
}
$contentTypes .= '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');

$slideIds = '';
$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
for ($i = 1; $i <= count($slides); $i++) {
    $slideIds .= '<p:sldId id="' . (255 + $i) . '" r:id="rId' . $i . '"/>';
    $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide' . $i . '.xml"/>';
}
$rels .= '<Relationship Id="rIdTheme" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/></Relationships>';
$zip->addFromString('ppt/_rels/presentation.xml.rels', $rels);
$zip->addFromString('ppt/presentation.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"><p:sldIdLst>' . $slideIds . '</p:sldIdLst><p:sldSz cx="' . $W . '" cy="' . $H . '" type="wide"/><p:notesSz cx="6858000" cy="9144000"/><p:defaultTextStyle/></p:presentation>');

foreach ($slides as $i => $xml) {
    $zip->addFromString('ppt/slides/slide' . ($i + 1) . '.xml', $xml);
}

$zip->addFromString('ppt/theme/theme1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="SalonProfessional"><a:themeElements><a:clrScheme name="SalonProfessional"><a:dk1><a:srgbClr val="111827"/></a:dk1><a:lt1><a:srgbClr val="F8FAFC"/></a:lt1><a:dk2><a:srgbClr val="172033"/></a:dk2><a:lt2><a:srgbClr val="FFFFFF"/></a:lt2><a:accent1><a:srgbClr val="C24B8B"/></a:accent1><a:accent2><a:srgbClr val="2563EB"/></a:accent2><a:accent3><a:srgbClr val="16A34A"/></a:accent3><a:accent4><a:srgbClr val="F59E0B"/></a:accent4><a:accent5><a:srgbClr val="7C3AED"/></a:accent5><a:accent6><a:srgbClr val="0F766E"/></a:accent6><a:hlink><a:srgbClr val="2563EB"/></a:hlink><a:folHlink><a:srgbClr val="7C3AED"/></a:folHlink></a:clrScheme><a:fontScheme name="Aptos"><a:majorFont><a:latin typeface="Aptos Display"/></a:majorFont><a:minorFont><a:latin typeface="Aptos"/></a:minorFont></a:fontScheme><a:fmtScheme name="Clean"><a:fillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:fillStyleLst><a:lnStyleLst><a:ln w="9525"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln></a:lnStyleLst><a:effectStyleLst><a:effectStyle><a:effectLst/></a:effectStyle></a:effectStyleLst><a:bgFillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:bgFillStyleLst></a:fmtScheme></a:themeElements><a:objectDefaults/><a:extraClrSchemeLst/></a:theme>');
$zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>Fabulous Unisex Salon Management System</dc:title><dc:creator>Vageesh Hiremath</dc:creator><cp:lastModifiedBy>Vageesh Hiremath</cp:lastModifiedBy></cp:coreProperties>');
$zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Microsoft PowerPoint</Application><PresentationFormat>On-screen Show (16:9)</PresentationFormat><Slides>' . count($slides) . '</Slides></Properties>');
$zip->close();

echo $pptxPath . PHP_EOL;

