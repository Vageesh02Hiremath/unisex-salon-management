<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

function jsonAdminRegisterResponse($success, $message, $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
        'captcha' => captchaCode()
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
$password = $input['password'] ?? '';
$confirm = $input['confirm_password'] ?? '';
$captcha = $input['captcha'] ?? '';
$strong = preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password);

if (!$name || !$email || !$phone || !$password) {
    jsonAdminRegisterResponse(false, 'Please fill in all required admin details.');
}
if (!verifyCaptcha($captcha)) {
    generateCaptcha();
    jsonAdminRegisterResponse(false, 'CAPTCHA answer is incorrect.');
}
if (!isValidName($name)) {
    jsonAdminRegisterResponse(false, 'Please enter a valid name.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonAdminRegisterResponse(false, 'Please enter a valid email.');
}
if (!isValidPhone($phone)) {
    jsonAdminRegisterResponse(false, 'Please enter a valid phone number.');
}
if ($password !== $confirm) {
    jsonAdminRegisterResponse(false, 'Passwords do not match.');
}
if (!$strong) {
    jsonAdminRegisterResponse(false, 'Password must be strong before registration.');
}
if (emailExists($email) || userEmailExists($email)) {
    jsonAdminRegisterResponse(false, 'Email already exists.');
}
if (phoneExists($phone)) {
    jsonAdminRegisterResponse(false, 'Phone number already exists.');
}

try {
    preparedQuery(
        "INSERT INTO users (name, email, password, role, status, phone) VALUES (?, ?, ?, 'admin', 'active', ?)",
        'ssss',
        [$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone]
    );
    jsonAdminRegisterResponse(true, 'Admin registration successful. You can login now.', ['redirect' => BASE_URL . 'admin_login.php']);
} catch (Exception $exception) {
    jsonAdminRegisterResponse(false, 'Unable to register admin. Please check for duplicate details and try again.');
}
?>
