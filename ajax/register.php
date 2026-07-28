<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';

header('Content-Type: application/json');

function jsonRegisterResponse($success, $message, $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
        'captcha' => captchaCode()
    ], $extra));
    exit;
}

function resetAjaxPendingRegistration() {
    unset($_SESSION['pending_registration']);
}

function ajaxPendingRegistration() {
    return $_SESSION['pending_registration'] ?? null;
}

function createAjaxRegisteredAccount($pending) {
    global $conn;

    if ($pending['role'] === 'customer') {
        preparedQuery(
            "INSERT INTO customers (name, email, password, phone, gender, status) VALUES (?, ?, ?, ?, ?, 'active')",
            'sssss',
            [$pending['name'], $pending['email'], $pending['password_hash'], $pending['phone'], $pending['gender']]
        );
        return;
    }

    $conn->begin_transaction();
    try {
        preparedQuery(
            "INSERT INTO users (name, email, password, role, status, phone) VALUES (?, ?, ?, ?, 'active', ?)",
            'sssss',
            [$pending['name'], $pending['email'], $pending['password_hash'], $pending['role'], $pending['phone']]
        );
        $user_id = getLastId();
        if ($pending['role'] === 'staff') {
            preparedQuery(
                "INSERT INTO staff (user_id, specialization, availability_start, availability_end, days_working, commission_percentage) VALUES (?, ?, ?, ?, ?, ?)",
                'issssd',
                [
                    $user_id,
                    $pending['specialization'],
                    $pending['availability_start'] ?: null,
                    $pending['availability_end'] ?: null,
                    $pending['days_working'],
                    $pending['commission_percentage']
                ]
            );
        }
        $conn->commit();
    } catch (Exception $exception) {
        $conn->rollback();
        throw $exception;
    }
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$action = $input['action'] ?? 'request_otp';

if ($action === 'edit_registration') {
    resetAjaxPendingRegistration();
    generateCaptcha();
    jsonRegisterResponse(true, 'You can edit your registration details now.', ['reload' => true]);
}

if ($action === 'resend_otp') {
    $pending = ajaxPendingRegistration();
    if (!$pending) {
        jsonRegisterResponse(false, 'Please register again to receive a new OTP.', ['reload' => true]);
    }
    if (emailExists($pending['email']) || userEmailExists($pending['email'])) {
        resetAjaxPendingRegistration();
        jsonRegisterResponse(false, 'Email already exists.', ['reload' => true]);
    }
    if (phoneExists($pending['phone'])) {
        resetAjaxPendingRegistration();
        jsonRegisterResponse(false, 'Phone number already exists.', ['reload' => true]);
    }

    $otp = (string)random_int(100000, 999999);
    if (!sendRegistrationOtp($pending['email'], $pending['name'], $otp)) {
        jsonRegisterResponse(false, 'Could not resend OTP email. Please try again.');
    }

    $_SESSION['pending_registration']['otp_hash'] = password_hash($otp, PASSWORD_DEFAULT);
    $_SESSION['pending_registration']['expires_at'] = time() + 600;
    $_SESSION['pending_registration']['attempts'] = 0;
    jsonRegisterResponse(true, 'New OTP sent successfully. Please check your email.');
}

if ($action === 'verify_otp') {
    $pending = ajaxPendingRegistration();
    $otp = trim($input['otp'] ?? '');

    if (!$pending) {
        jsonRegisterResponse(false, 'Please request a new OTP to continue registration.', ['reload' => true]);
    }
    if (time() > (int)$pending['expires_at']) {
        resetAjaxPendingRegistration();
        jsonRegisterResponse(false, 'OTP expired. Please register again to receive a new OTP.', ['reload' => true]);
    }
    if (($pending['attempts'] ?? 0) >= 5) {
        resetAjaxPendingRegistration();
        jsonRegisterResponse(false, 'Too many incorrect OTP attempts. Please register again.', ['reload' => true]);
    }
    if (!preg_match('/^\d{6}$/', $otp) || !password_verify($otp, $pending['otp_hash'])) {
        $_SESSION['pending_registration']['attempts'] = ($pending['attempts'] ?? 0) + 1;
        jsonRegisterResponse(false, 'Invalid OTP. Please check your email and try again.');
    }
    if (emailExists($pending['email']) || userEmailExists($pending['email'])) {
        resetAjaxPendingRegistration();
        jsonRegisterResponse(false, 'Email already exists.', ['reload' => true]);
    }
    if (phoneExists($pending['phone'])) {
        resetAjaxPendingRegistration();
        jsonRegisterResponse(false, 'Phone number already exists.', ['reload' => true]);
    }

    try {
        createAjaxRegisteredAccount($pending);
        resetAjaxPendingRegistration();
        jsonRegisterResponse(true, ucfirst($pending['role']) . ' registration successful. You can login now.', ['redirect' => BASE_URL . 'login.php']);
    } catch (Exception $exception) {
        jsonRegisterResponse(false, 'Unable to create account. Please check for duplicate details.');
    }
}

$role = sanitize($input['role'] ?? 'customer');
$name = sanitize($input['name'] ?? '');
$email = sanitize($input['email'] ?? '');
$phone = sanitize($input['phone'] ?? '');
$gender = sanitize($input['gender'] ?? '');
$specialization = sanitize($input['specialization'] ?? '');
$availability_start = sanitize($input['availability_start'] ?? '');
$availability_end = sanitize($input['availability_end'] ?? '');
$days_working = sanitize($input['days_working'] ?? '');
$commission_percentage = sanitize($input['commission_percentage'] ?? '10');
$password = $input['password'] ?? '';
$confirm = $input['confirm_password'] ?? '';
$captcha = $input['captcha'] ?? '';

$strong = preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password);

if (!in_array($role, ['customer', 'staff'], true)) {
    jsonRegisterResponse(false, 'Please select a valid account type.');
}
if (!verifyCaptcha($captcha)) {
    generateCaptcha();
    jsonRegisterResponse(false, 'CAPTCHA answer is incorrect.');
}
if (!$name || !$email || !$phone || !$password || ($role === 'customer' && !$gender)) {
    jsonRegisterResponse(false, 'Please fill in all required fields.');
}
if (!isValidName($name)) {
    jsonRegisterResponse(false, 'Please enter a valid name.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonRegisterResponse(false, 'Please enter a valid email.');
}
if (!isValidPhone($phone)) {
    jsonRegisterResponse(false, 'Please enter a valid phone number.');
}
if ($password !== $confirm) {
    jsonRegisterResponse(false, 'Passwords do not match.');
}
if (!$strong) {
    jsonRegisterResponse(false, 'Password must be strong before registration.');
}
if (emailExists($email) || userEmailExists($email)) {
    jsonRegisterResponse(false, 'Email already exists.');
}
if (phoneExists($phone)) {
    jsonRegisterResponse(false, 'Phone number already exists.');
}

$otp = (string)random_int(100000, 999999);
if (!sendRegistrationOtp($email, $name, $otp)) {
    generateCaptcha();
    jsonRegisterResponse(false, 'Could not send OTP email. Please check SMTP settings and try again.');
}

$_SESSION['pending_registration'] = [
    'role' => $role,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'gender' => $gender,
    'specialization' => $specialization,
    'availability_start' => $availability_start,
    'availability_end' => $availability_end,
    'days_working' => $days_working,
    'commission_percentage' => is_numeric($commission_percentage) ? (float)$commission_percentage : 10.0,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'otp_hash' => password_hash($otp, PASSWORD_DEFAULT),
    'expires_at' => time() + 600,
    'attempts' => 0,
    'created_at' => time()
];

jsonRegisterResponse(true, 'OTP sent successfully. Please check your email to complete registration.', ['reload' => true]);
?>
