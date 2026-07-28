<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once 'config/db.php';
require_once 'config/razorpay.php';
require_once 'includes/auth.php';
require_once 'includes/booking.php';

$error = '';
$success = '';
$booking_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $gender = sanitize($_POST['gender'] ?? 'Other');
    $service_ids = selectedServiceIds($_POST['service_id'] ?? []);
    $staff_id = isset($_POST['staff_id']) && $_POST['staff_id'] !== '' ? (int)$_POST['staff_id'] : null;
    $appointment_date = sanitize($_POST['appointment_date'] ?? '');
    $appointment_time = sanitize($_POST['appointment_time'] ?? '');
    $promo_code = sanitize($_POST['promo_code'] ?? '');
    $payment_method = sanitize($_POST['payment_method'] ?? 'pay_at_salon');
    $payment_method = in_array($payment_method, ['pay_at_salon', 'razorpay'], true) ? $payment_method : 'pay_at_salon';

    if (!$name || !$email || !$phone || !$service_ids || !$appointment_date || !$appointment_time) {
        $error = 'Please complete all required booking details.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $error = 'Please enter a valid phone number.';
    } elseif (!isValidAppointmentDate($appointment_date) || $appointment_date < date('Y-m-d')) {
        $error = 'Please choose today or a future date.';
    } elseif ($payment_method === 'razorpay') {
        $error = 'Please complete the Razorpay checkout to confirm online payment.';
    } else {
        $services = bookingServices($service_ids);
        $totals = bookingTotals($services, $promo_code);
        $available_staff = bookableStaffForSlot($appointment_date, $appointment_time, $totals['duration'], $staff_id);

        if (count($services) !== count($service_ids)) {
            $error = 'Please select valid services.';
        } elseif (!$available_staff) {
            $error = 'Selected staff or time is no longer available. Please choose another time or date.';
        } else {
            $customer = fetchPrepared("SELECT id FROM customers WHERE email = ? LIMIT 1", 's', [$email]);
            $assigned_staff_id = $staff_id ?: (int)$available_staff[0]['id'];
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
                     (booking_code, customer_id, staff_id, appointment_date, appointment_time, total_duration, subtotal, discount_amount, total_amount, promo_code, payment_method, payment_status, status, customer_name, customer_email, customer_phone)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)",
                    'siissidddssssss',
                    [$booking_code, $customer_id, $assigned_staff_id, $appointment_date, $appointment_time, $totals['duration'], $totals['subtotal'], $totals['discount'], $totals['total'], $totals['promo_code'], $payment_method, 'unpaid', $name, $email, $phone]
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
                $success = "Booking confirmed. Your Booking ID is $booking_code.";
            } catch (Exception $exception) {
                $conn->rollback();
                $error = 'Unable to complete booking. Please try again.';
            }
        }
    }
}

$services = fetchAll("SELECT * FROM services WHERE status = 'active' ORDER BY gender_category, name");
$staff_list = activeStaffList();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Booking - Unisex Salon</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>assets/images/salon-logo.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
</head>
<body class="auth-page booking-auth-page" data-base-url="<?php echo BASE_URL; ?>">
    <main class="auth-layout auth-layout-register">
        <section class="auth-showcase">
            <a href="<?php echo BASE_URL; ?>" class="auth-brand"><img class="auth-brand-logo" src="<?php echo BASE_URL; ?>assets/images/salon-logo.svg" alt=""> Unisex Salon</a>
            <div class="showcase-copy"><span>Online booking</span><h1>Book as a guest in a few clear steps.</h1><p>Select services, choose a slot, and pay at salon or online with Razorpay.</p></div>
        </section>
        <section class="auth-panel">
            <div class="auth-heading"><span class="auth-kicker">Guest Booking</span><h2>Reserve your appointment</h2><p class="muted">You can create an account after booking from the registration page.</p></div>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="<?php echo BASE_URL; ?>register.php">Create account</a></div><?php endif; ?>
            <form method="POST" id="bookingForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="guest_name">Name *</label>
                        <div class="icon-field"><i class="fa-solid fa-user"></i><input type="text" id="guest_name" name="name" required autocomplete="name"></div>
                    </div>
                    <div class="form-group">
                        <label for="guest_email">Email *</label>
                        <div class="icon-field"><i class="fa-solid fa-envelope"></i><input type="email" id="guest_email" name="email" required autocomplete="email"></div>
                    </div>
                    <div class="form-group">
                        <label for="guest_phone">Phone *</label>
                        <div class="icon-field"><i class="fa-solid fa-phone"></i><input type="tel" id="guest_phone" name="phone" required autocomplete="tel"></div>
                    </div>
                    <div class="form-group">
                        <label for="guest_gender">Gender</label>
                        <div class="icon-field"><i class="fa-solid fa-venus-mars"></i><select id="guest_gender" name="gender"><option>Other</option><option>Male</option><option>Female</option></select></div>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="service_id">Services *</label>
                        <select class="booking-service-select" id="service_id" name="service_id[]" multiple size="6" required onchange="loadTimeSlots()"><?php foreach ($services as $service): ?><option value="<?php echo (int)$service['id']; ?>" data-price="<?php echo $service['price']; ?>" data-duration="<?php echo $service['duration']; ?>"><?php echo htmlspecialchars($service['name']); ?> - <?php echo formatCurrency($service['price']); ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="form-group">
                        <label for="staff_id">Staff</label>
                        <div class="icon-field"><i class="fa-solid fa-user-tie"></i><select id="staff_id" name="staff_id" onchange="loadTimeSlots()"><option value="">Auto-assign available staff</option><?php foreach ($staff_list as $staff): ?><option value="<?php echo (int)$staff['id']; ?>"><?php echo htmlspecialchars($staff['name']); ?></option><?php endforeach; ?></select></div>
                    </div>
                    <div class="form-group">
                        <label for="appointment_date">Date *</label>
                        <div class="icon-field"><i class="fa-solid fa-calendar-days"></i><input type="date" id="appointment_date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required onchange="loadTimeSlots()"></div>
                    </div>
                    <div class="form-group">
                        <label for="appointment_time">Time *</label>
                        <div class="icon-field"><i class="fa-solid fa-clock"></i><select id="appointment_time" name="appointment_time" required disabled><option value="">Select services and date first</option></select></div>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="promo_code">Promo Code</label>
                        <div class="icon-field"><i class="fa-solid fa-ticket"></i><input type="text" id="promo_code" name="promo_code" placeholder="WELCOME10"></div>
                    </div>
                    <div class="form-group">
                        <label for="payment_method">Payment</label>
                        <div class="icon-field"><i class="fa-solid fa-credit-card"></i><select id="payment_method" name="payment_method"><option value="pay_at_salon">Pay at Salon</option><option value="razorpay">Pay Online with Razorpay</option></select></div>
                    </div>
                </div>
                <div class="booking-form-actions">
                    <a href="<?php echo BASE_URL; ?>" class="btn booking-secondary-btn"><i class="fa-solid fa-arrow-left"></i> Back</a>
                    <button type="submit" class="btn booking-primary-btn"><i class="fa-solid fa-calendar-check"></i> Confirm Booking</button>
                </div>
            </form>
            <div class="auth-link">Already registered? <a href="<?php echo BASE_URL; ?>login.php">Login here</a></div>
        </section>
    </main>
    <?php if (razorpayIsConfigured()): ?>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <?php endif; ?>
    <script>window.RAZORPAY_ENABLED = <?php echo razorpayIsConfigured() ? 'true' : 'false'; ?>;</script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js?v=<?php echo filemtime(__DIR__ . '/assets/js/main.js'); ?>"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/ajax.js?v=<?php echo filemtime(__DIR__ . '/assets/js/ajax.js'); ?>"></script>
</body>
</html>
