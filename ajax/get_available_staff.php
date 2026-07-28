<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/booking.php';

header('Content-Type: application/json');

$service_ids = selectedServiceIds($_GET['service_ids'] ?? ($_GET['service_id'] ?? []));
$appointment_date = sanitize($_GET['appointment_date'] ?? date('Y-m-d'));
$appointment_time = sanitize($_GET['appointment_time'] ?? '09:00:00');

if (!$service_ids || !isValidAppointmentDate($appointment_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid service or date.']);
    exit;
}

$services = bookingServices($service_ids);
if (count($services) !== count($service_ids)) {
    echo json_encode(['success' => false, 'message' => 'Service not found.']);
    exit;
}

$totals = bookingTotals($services);
$staff = bookableStaffForSlot($appointment_date, $appointment_time, $totals['duration']);

echo json_encode([
    'success' => true,
    'staff' => $staff,
    'count' => count($staff)
]);
?>
