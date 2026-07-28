<?php
require_once '../config/db.php';
require_once '../config/razorpay.php';
require_once '../includes/auth.php';
require_once '../includes/booking.php';

header('Content-Type: application/json');

function jsonGuestBookingResponse($success, $message, $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$name = sanitize($input['name'] ?? '');
$email = sanitize($input['email'] ?? '');
$phone = sanitize($input['phone'] ?? '');
$gender = sanitize($input['gender'] ?? 'Other');
$service_ids = selectedServiceIds($input['service_id'] ?? ($input['service_ids'] ?? []));
$staff_id = isset($input['staff_id']) && $input['staff_id'] !== '' ? (int)$input['staff_id'] : null;
$appointment_date = sanitize($input['appointment_date'] ?? '');
$appointment_time = sanitize($input['appointment_time'] ?? '');
$promo_code = sanitize($input['promo_code'] ?? '');
$payment_method = sanitize($input['payment_method'] ?? 'pay_at_salon');
$payment_method = in_array($payment_method, ['pay_at_salon', 'razorpay'], true) ? $payment_method : 'pay_at_salon';
$razorpay_order_id = sanitize($input['razorpay_order_id'] ?? '');
$razorpay_payment_id = sanitize($input['razorpay_payment_id'] ?? '');
$razorpay_signature = sanitize($input['razorpay_signature'] ?? '');

if (!$name || !$email || !$phone || !$service_ids || !$appointment_date || !$appointment_time) {
    jsonGuestBookingResponse(false, 'Please complete all required booking details.');
}
if (!isValidName($name)) {
    jsonGuestBookingResponse(false, 'Please enter a valid name.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonGuestBookingResponse(false, 'Please enter a valid email address.');
}
if (!isValidPhone($phone)) {
    jsonGuestBookingResponse(false, 'Please enter a valid phone number.');
}
if (!isValidAppointmentDate($appointment_date) || $appointment_date < date('Y-m-d')) {
    jsonGuestBookingResponse(false, 'Please choose today or a future date.');
}
if ($appointment_date === date('Y-m-d') && strtotime("$appointment_date $appointment_time") <= time()) {
    jsonGuestBookingResponse(false, 'This time has already passed today. Please choose another time or date.');
}

$services = bookingServices($service_ids);
if (count($services) !== count($service_ids)) {
    jsonGuestBookingResponse(false, 'Please select valid services.');
}

$totals = bookingTotals($services, $promo_code);
$available_staff = bookableStaffForSlot($appointment_date, $appointment_time, $totals['duration'], $staff_id);
if (!$available_staff) {
    jsonGuestBookingResponse(false, 'Selected staff or time is no longer available. Please choose another time or date.');
}

$customer = fetchPrepared("SELECT id FROM customers WHERE email = ? LIMIT 1", 's', [$email]);
$assigned_staff_id = $staff_id ?: (int)$available_staff[0]['id'];

if ($payment_method === 'razorpay') {
    if (!razorpayIsConfigured()) {
        jsonGuestBookingResponse(false, 'Razorpay is not configured.');
    }
    if (!$razorpay_order_id || !$razorpay_payment_id || !$razorpay_signature) {
        jsonGuestBookingResponse(false, 'Missing Razorpay payment details.');
    }
    if (!razorpayVerifySignature($razorpay_order_id, $razorpay_payment_id, $razorpay_signature)) {
        jsonGuestBookingResponse(false, 'Razorpay payment verification failed.');
    }

    try {
        if (!razorpayPaymentIsSettledForAmount($razorpay_payment_id, $totals['total'])) {
            jsonGuestBookingResponse(false, 'Razorpay payment amount or status could not be verified.');
        }
    } catch (Exception $exception) {
        jsonGuestBookingResponse(false, $exception->getMessage());
    }

    $stored_order = $_SESSION['razorpay_orders'][$razorpay_order_id] ?? null;
    $submitted_ids = $service_ids;
    $stored_ids = $stored_order['service_ids'] ?? [];
    sort($submitted_ids);
    sort($stored_ids);

    if (
        !$stored_order ||
        (int)$stored_order['amount'] !== razorpayAmountInPaise($totals['total']) ||
        $stored_order['name'] !== $name ||
        $stored_order['email'] !== $email ||
        $stored_order['phone'] !== $phone ||
        $submitted_ids !== $stored_ids ||
        $stored_order['appointment_date'] !== $appointment_date ||
        $stored_order['appointment_time'] !== $appointment_time ||
        (int)$stored_order['staff_id'] !== $assigned_staff_id ||
        $stored_order['promo_code'] !== $totals['promo_code']
    ) {
        jsonGuestBookingResponse(false, 'Payment order does not match the booking details.');
    }
}

ensureBookingGroupsTable();
$conn->begin_transaction();

try {
    if ($customer) {
        $customer_id = (int)$customer['id'];
        preparedQuery("UPDATE customers SET name = ?, phone = ?, gender = ? WHERE id = ?", 'sssi', [$name, $phone, $gender, $customer_id]);
    } else {
        $guest_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        preparedQuery(
            "INSERT INTO customers (name, email, password, phone, gender, status) VALUES (?, ?, ?, ?, ?, 'active')",
            'sssss',
            [$name, $email, $guest_password, $phone, $gender]
        );
        $customer_id = getLastId();
    }

    $booking_code = bookingCode();
    preparedQuery(
        "INSERT INTO booking_groups
         (booking_code, customer_id, staff_id, appointment_date, appointment_time, total_duration, subtotal, discount_amount, total_amount, promo_code, payment_method, payment_status, razorpay_order_id, razorpay_payment_id, razorpay_signature, status, customer_name, customer_email, customer_phone)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)",
        'siissidddsssssssss',
        [
            $booking_code,
            $customer_id,
            $assigned_staff_id,
            $appointment_date,
            $appointment_time,
            $totals['duration'],
            $totals['subtotal'],
            $totals['discount'],
            $totals['total'],
            $totals['promo_code'],
            $payment_method,
            $payment_method === 'razorpay' ? 'paid' : 'unpaid',
            $payment_method === 'razorpay' ? $razorpay_order_id : null,
            $payment_method === 'razorpay' ? $razorpay_payment_id : null,
            $payment_method === 'razorpay' ? $razorpay_signature : null,
            $name,
            $email,
            $phone
        ]
    );

    foreach ($service_ids as $service_id) {
        preparedQuery(
            "INSERT INTO appointments (customer_id, staff_id, service_id, appointment_date, appointment_time, status, notes, created_at)
             VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())",
            'iiisss',
            [$customer_id, $assigned_staff_id, $service_id, $appointment_date, $appointment_time, 'Guest Booking Code: ' . $booking_code]
        );
    }
    $conn->commit();
    if ($payment_method === 'razorpay') {
        unset($_SESSION['razorpay_orders'][$razorpay_order_id]);
    }
    jsonGuestBookingResponse(true, "Booking confirmed. Your Booking ID is $booking_code.", ['booking_id' => $booking_code]);
} catch (Exception $exception) {
    $conn->rollback();
    jsonGuestBookingResponse(false, 'Unable to complete booking. Please try again.');
}
?>
