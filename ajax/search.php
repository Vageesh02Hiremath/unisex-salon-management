<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$query_str = sanitize($_GET['q'] ?? '');

if (strlen($query_str) < 2) {
    echo json_encode(['success' => false, 'message' => 'Search term too short']);
    exit;
}

$results = [
    'customers' => [],
    'services' => [],
    'appointments' => []
];

// Search customers
$customers = fetchAll("
    SELECT id, name, email, phone FROM customers 
    WHERE name LIKE '%$query_str%' OR email LIKE '%$query_str%' OR phone LIKE '%$query_str%'
    LIMIT 5
");

foreach ($customers as $c) {
    $results['customers'][] = [
        'id' => $c['id'],
        'name' => $c['name'],
        'email' => $c['email'],
        'phone' => $c['phone'],
        'type' => 'customer'
    ];
}

// Search services
$services = fetchAll("
    SELECT id, name, category, price FROM services 
    WHERE name LIKE '%$query_str%' OR category LIKE '%$query_str%'
    LIMIT 5
");

foreach ($services as $s) {
    $results['services'][] = [
        'id' => $s['id'],
        'name' => $s['name'],
        'category' => $s['category'],
        'price' => $s['price'],
        'type' => 'service'
    ];
}

// Search appointments
$appointments = fetchAll("
    SELECT a.id, a.appointment_date, a.appointment_time, c.name as customer_name, s.name as service_name, a.status
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    WHERE c.name LIKE '%$query_str%' OR s.name LIKE '%$query_str%'
    LIMIT 5
");

foreach ($appointments as $a) {
    $results['appointments'][] = [
        'id' => $a['id'],
        'customer' => $a['customer_name'],
        'service' => $a['service_name'],
        'date' => $a['appointment_date'],
        'time' => $a['appointment_time'],
        'status' => $a['status'],
        'type' => 'appointment'
    ];
}

echo json_encode([
    'success' => true,
    'results' => $results,
    'total' => count($customers) + count($services) + count($appointments)
]);
?>
