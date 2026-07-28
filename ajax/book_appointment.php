<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../config/razorpay.php';
require_once '../includes/auth.php';
require_once '../includes/booking.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$customer_id = intval($input['customer_id'] ?? 0);
if ($customer_id <= 0 && isLoggedIn() && hasRole('customer')) {
    $customer_id = (int)$_SESSION['user_id'];
}
$service_ids = selectedServiceIds($input['service_ids'] ?? ($input['service_id'] ?? []));
$appointment_date = sanitize($input['appointment_date'] ?? '');
$appointment_time = sanitize($input['appointment_time'] ?? '');
$staff_id = isset($input['staff_id']) && !empty($input['staff_id']) ? intval($input['staff_id']) : null;
$promo_code = sanitize($input['promo_code'] ?? '');
$payment_method = sanitize($input['payment_method'] ?? 'pay_at_salon');
$payment_method = in_array($payment_method, ['pay_at_salon', 'razorpay'], true) ? $payment_method : 'pay_at_salon';
$razorpay_order_id = sanitize($input['razorpay_order_id'] ?? '');
$razorpay_payment_id = sanitize($input['razorpay_payment_id'] ?? '');
$razorpay_signature = sanitize($input['razorpay_signature'] ?? '');

if ($customer_id <= 0 || empty($service_ids) || empty($appointment_date) || empty($appointment_time)) {
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

if ($appointment_date === date('Y-m-d') && strtotime("$appointment_date $appointment_time") <= time()) {
    echo json_encode(['success' => false, 'message' => 'This time has already passed today. Please choose another time or date.']);
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
    echo json_encode(['success' => false, 'message' => 'Selected staff or time is no longer available. Please choose another time or date.']);
    exit;
}

$assigned_staff_id = $staff_id ?: (int)$available_staff[0]['id'];
$customer = fetchPrepared("SELECT name, email, phone FROM customers WHERE id = ? LIMIT 1", 'i', [$customer_id]);
if (!$customer) {
    echo json_encode(['success' => false, 'message' => 'Customer not found.']);
    exit;
}

if ($payment_method === 'razorpay') {
    if (!razorpayIsConfigured()) {
        echo json_encode(['success' => false, 'message' => 'Razorpay is not configured.']);
        exit;
    }

    if (!$razorpay_order_id || !$razorpay_payment_id || !$razorpay_signature) {
        echo json_encode(['success' => false, 'message' => 'Missing Razorpay payment details.']);
        exit;
    }

    if (!razorpayVerifySignature($razorpay_order_id, $razorpay_payment_id, $razorpay_signature)) {
        echo json_encode(['success' => false, 'message' => 'Razorpay payment verification failed.']);
        exit;
    }

    try {
        if (!razorpayPaymentIsSettledForAmount($razorpay_payment_id, $totals['total'])) {
            echo json_encode(['success' => false, 'message' => 'Razorpay payment amount or status could not be verified.']);
            exit;
        }
    } catch (Exception $exception) {
        echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
        exit;
    }

    $stored_order = $_SESSION['razorpay_orders'][$razorpay_order_id] ?? null;
    $submitted_ids = $service_ids;
    $stored_ids = $stored_order['service_ids'] ?? [];
    sort($submitted_ids);
    sort($stored_ids);

    if (
        !$stored_order ||
        (int)$stored_order['amount'] !== razorpayAmountInPaise($totals['total']) ||
        (int)$stored_order['customer_id'] !== $customer_id ||
        $submitted_ids !== $stored_ids ||
        $stored_order['appointment_date'] !== $appointment_date ||
        $stored_order['appointment_time'] !== $appointment_time ||
        (int)$stored_order['staff_id'] !== $assigned_staff_id ||
        $stored_order['promo_code'] !== $totals['promo_code']
    ) {
        echo json_encode(['success' => false, 'message' => 'Payment order does not match the booking details.']);
        exit;
    }
}

$booking_code = bookingCode();
$appointment_ids = [];
ensureBookingGroupsTable();

$conn->begin_transaction();
try {
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
            $customer['name'],
            $customer['email'],
            $customer['phone']
        ]
    );

    foreach ($service_ids as $selected_service_id) {
        preparedQuery(
            "INSERT INTO appointments (customer_id, staff_id, service_id, appointment_date, appointment_time, status, notes, created_at)
             VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())",
            'iiisss',
            [$customer_id, $assigned_staff_id, $selected_service_id, $appointment_date, $appointment_time, 'Booking Code: ' . $booking_code]
        );
        $appointment_ids[] = getLastId();
    }

    $conn->commit();
    if ($payment_method === 'razorpay') {
        unset($_SESSION['razorpay_orders'][$razorpay_order_id]);
    }
    echo json_encode([
        'success' => true,
        'message' => count($appointment_ids) . ' service appointment' . (count($appointment_ids) > 1 ? 's' : '') . ' booked successfully',
        'booking_id' => $booking_code,
        'appointment_id' => $appointment_ids[0],
        'appointment_ids' => $appointment_ids,
        'assigned_staff_id' => $assigned_staff_id,
        'total_amount' => $totals['total'],
        'payment_status' => $payment_method === 'razorpay' ? 'paid' : 'unpaid'
    ]);
} catch (Exception $exception) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to book appointment: ' . $exception->getMessage()
    ]);
}
?>
