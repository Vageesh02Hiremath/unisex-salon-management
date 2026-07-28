<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

function jsonLoginResponse($success, $message, $extra = []) {
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

$email = sanitize($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = sanitize($input['role'] ?? 'customer');
$captcha = $input['captcha'] ?? '';
$adminOnly = !empty($input['admin_only']);

if ($adminOnly) {
    $role = 'admin';
}

if (isLoggedIn()) {
    $redirect = hasRole('admin') ? 'admin/dashboard.php' : (hasRole('staff') ? 'staff/dashboard.php' : 'customer/dashboard.php');
    jsonLoginResponse(true, 'Already logged in.', ['redirect' => BASE_URL . $redirect]);
}

if (tooManyLoginAttempts($email)) {
    jsonLoginResponse(false, 'Too many failed attempts. Please wait 15 minutes and try again.');
}

if (!verifyCaptcha($captcha)) {
    recordLoginAttempt($email, false);
    generateCaptcha();
    jsonLoginResponse(false, 'CAPTCHA answer is incorrect.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || !in_array($role, ['admin', 'staff', 'customer'], true)) {
    generateCaptcha();
    jsonLoginResponse(false, 'Please enter valid login details.');
}

$table = $role === 'customer' ? 'customers' : 'users';
$user = $role === 'customer'
    ? fetchPrepared("SELECT * FROM customers WHERE email = ? LIMIT 1", 's', [$email])
    : fetchPrepared("SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1", 'ss', [$email, $role]);

if ($user && password_verify($password, $user['password']) && $user['status'] === 'active') {
    recordLoginAttempt($email, true);
    preparedQuery("UPDATE $table SET last_login = NOW() WHERE id = ?", 'i', [(int)$user['id']]);
    loginUser($user['id'], $user['email'], $role, $user['name']);
    $redirect = $role === 'admin' ? 'admin/dashboard.php' : ($role === 'staff' ? 'staff/dashboard.php' : 'customer/dashboard.php');
    jsonLoginResponse(true, 'Login successful.', ['redirect' => BASE_URL . $redirect]);
}

recordLoginAttempt($email, false);
generateCaptcha();
jsonLoginResponse(false, $adminOnly ? 'Invalid admin email or password.' : 'Invalid email, password, or role.');
?>
