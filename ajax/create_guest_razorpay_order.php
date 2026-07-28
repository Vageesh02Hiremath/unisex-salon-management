<?php
require_once '../config/db.php';
require_once '../config/razorpay.php';
require_once '../includes/auth.php';
require_once '../includes/booking.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$name = sanitize($input['name'] ?? '');
$email = sanitize($input['email'] ?? '');
$phone = sanitize($input['phone'] ?? '');
$service_ids = selectedServiceIds($input['service_id'] ?? ($input['service_ids'] ?? []));
$staff_id = isset($input['staff_id']) && $input['staff_id'] !== '' ? (int)$input['staff_id'] : null;
$appointment_date = sanitize($input['appointment_date'] ?? '');
$appointment_time = sanitize($input['appointment_time'] ?? '');
$promo_code = sanitize($input['promo_code'] ?? '');

if (!$name || !$email || !$phone || !$service_ids || !$appointment_date || !$appointment_time) {
    echo json_encode(['success' => false, 'message' => 'Please complete all required booking details.']);
    exit;
}

if (!isValidName($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !isValidPhone($phone)) {
    echo json_encode(['success' => false, 'message' => 'Please enter valid guest details.']);
    exit;
}

if (!razorpayIsConfigured()) {
    echo json_encode(['success' => false, 'message' => 'Razorpay is not configured.']);
    exit;
}

if (!isValidAppointmentDate($appointment_date) || $appointment_date < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid appointment date.']);
    exit;
}

if ($appointment_date === date('Y-m-d') && strtotime("$appointment_date $appointment_time") <= time()) {
    echo json_encode(['success' => false, 'message' => 'This time has already passed today.']);
    exit;
}

$services = bookingServices($service_ids);
if (count($services) !== count($service_ids)) {
    echo json_encode(['success' => false, 'message' => 'Please select valid active services.']);
    exit;
}

$totals = bookingTotals($services, $promo_code);
$available_staff = bookableStaffForSlot($appointment_date, $appointment_time, $totals['duration'], $staff_id);
if (!$available_staff) {
    echo json_encode(['success' => false, 'message' => 'Selected staff or time is no longer available.']);
    exit;
}

$assigned_staff_id = $staff_id ?: (int)$available_staff[0]['id'];
$receipt = bookingCode();

try {
    $order = razorpayCreateOrder($totals['total'], $receipt, [
        'booking_code' => $receipt,
        'guest_email' => $email
    ]);

    $_SESSION['razorpay_orders'][$order['id']] = [
        'amount' => razorpayAmountInPaise($totals['total']),
        'customer_id' => 0,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'service_ids' => $service_ids,
        'appointment_date' => $appointment_date,
        'appointment_time' => $appointment_time,
        'staff_id' => $assigned_staff_id,
        'promo_code' => $totals['promo_code']
    ];

    echo json_encode([
        'success' => true,
        'key_id' => RAZORPAY_KEY_ID,
        'order_id' => $order['id'],
        'amount' => $order['amount'],
        'currency' => $order['currency'],
        'name' => 'Fabulous Unisex Salon',
        'description' => count($service_ids) . ' salon service' . (count($service_ids) > 1 ? 's' : ''),
        'prefill' => [
            'name' => $name,
            'email' => $email,
            'contact' => $phone
        ]
    ]);
} catch (Exception $exception) {
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
}

?>
