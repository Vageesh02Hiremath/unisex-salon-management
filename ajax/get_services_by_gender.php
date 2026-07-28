<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';

header('Content-Type: application/json');

$gender = sanitize($_GET['gender'] ?? 'Unisex');
$allowed = ['Male', 'Female', 'Kids', 'Unisex'];
if (!in_array($gender, $allowed, true)) {
    echo json_encode(['success' => false, 'services' => [], 'message' => 'Invalid category']);
    exit;
}

$services = fetchAllPrepared(
    "SELECT id, name, description, price, duration, category, gender_category, image
     FROM services
     WHERE status = 'active' AND gender_category = ?
     ORDER BY name",
    's',
    [$gender]
);

echo json_encode(['success' => true, 'services' => $services]);
?>
