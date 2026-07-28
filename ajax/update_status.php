<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/generate_bill.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$appointment_id = intval($input['appointment_id'] ?? 0);
$status = sanitize($input['status'] ?? '');

$valid_statuses = ['pending', 'approved', 'rejected', 'confirmed', 'in-progress', 'completed', 'cancelled'];

if ($appointment_id <= 0 || !in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$query = "UPDATE appointments SET status = '$status' WHERE id = $appointment_id";

if ($conn->query($query)) {
    $bill = ['success' => true, 'message' => ''];
    if ($status === 'completed') {
        $bill = generateBillForAppointment($appointment_id);
    }
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully' . ($status === 'completed' ? '. ' . $bill['message'] : ''),
        'status' => $status
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update status: ' . $conn->error
    ]);
}
?>
