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
$appointment_date = sanitize($_GET['appointment_date'] ?? '');
$staff_id = isset($_GET['staff_id']) && !empty($_GET['staff_id']) ? intval($_GET['staff_id']) : null;

if (empty($service_ids) || empty($appointment_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

if (!isValidAppointmentDate($appointment_date)) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid appointment date.']);
    exit;
}

if ($appointment_date < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'Past dates are not allowed. Please choose today or a future date.']);
    exit;
}

// Get service details
$services = bookingServices($service_ids);
if (count($services) !== count($service_ids)) {
    echo json_encode(['success' => false, 'message' => 'Service not found']);
    exit;
}

$totals = bookingTotals($services);
$total_duration = $totals['duration'];
$buffer_minutes = bookingBufferMinutes();
$interval = 30;

// Generate all time slots
$time_slots = [];
$slot_start = strtotime("$appointment_date 09:00:00");
$slot_end = strtotime("$appointment_date 19:00:00");
for ($time = $slot_start; $time < $slot_end; $time += $interval * 60) {
    $slot = date('H:i:s', $time);
    $available_staff = bookableStaffForSlot($appointment_date, $slot, $total_duration, $staff_id, $buffer_minutes);

    $available_staff = array_values(array_filter($available_staff));
    $available_count = count($available_staff);
    $is_booked = slotHasBookingOverlap($appointment_date, $slot, $total_duration, $staff_id, $buffer_minutes);
    $status = $available_count > 1 ? 'Available' : ($available_count === 1 ? 'Almost Full' : ($is_booked ? 'Booked' : 'Unavailable'));
    $time_slots[] = [
        'time' => $slot,
        'label' => date('h:i A', strtotime($slot)),
        'status' => $status,
        'available' => $available_count > 0,
        'booked' => $is_booked,
        'available_staff_count' => $available_count,
        'staff' => $available_staff
    ];
}

$available_slots = array_values(array_filter($time_slots, function($slot) {
    return $slot['available'];
}));
$display_slots = array_values(array_filter($time_slots, function($slot) {
    return $slot['available'] || $slot['booked'];
}));

echo json_encode([
    'success' => true,
    'slots' => $display_slots,
    'all_slots' => $time_slots,
    'available_slots' => $available_slots,
    'duration' => $total_duration,
    'buffer' => $buffer_minutes,
    'count' => count($available_slots)
]);
?>
