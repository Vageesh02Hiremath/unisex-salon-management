<?php
session_start();

function clientIpAddress() {
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Login user
function loginUser($id, $email, $role, $name) {
    $_SESSION['user_id'] = $id;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['name'] = $name;
}

// Logout user
function logoutUser() {
    $_SESSION = [];
    session_destroy();
    header("Location: " . BASE_URL . "login.php");
    exit();
}

function ensureLoginAttemptsTable() {
    global $conn;
    $conn->query("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(100) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        success TINYINT(1) DEFAULT 0,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email_time (email, attempted_at),
        INDEX idx_ip_time (ip_address, attempted_at)
    )");
}

function tooManyLoginAttempts($email) {
    ensureLoginAttemptsTable();
    $email = sanitize($email);
    $ip = sanitize(clientIpAddress());
    $row = fetchOne("SELECT COUNT(*) AS count FROM login_attempts
        WHERE success = 0 AND attempted_at > (NOW() - INTERVAL 15 MINUTE)
        AND (email = '$email' OR ip_address = '$ip')");
    return (int)($row['count'] ?? 0) >= 5;
}

function recordLoginAttempt($email, $success) {
    ensureLoginAttemptsTable();
    $email = sanitize($email);
    $ip = sanitize(clientIpAddress());
    $flag = $success ? 1 : 0;
    executeQuery("INSERT INTO login_attempts (email, ip_address, success) VALUES ('$email', '$ip', $flag)");
}

function generateCaptcha() {
    $_SESSION['captcha_answer'] = (string)random_int(100000, 999999);
}

function verifyCaptcha($answer) {
    return isset($_SESSION['captcha_answer']) && trim((string)$answer) === $_SESSION['captcha_answer'];
}

function captchaCode() {
    if (!isset($_SESSION['captcha_answer'])) {
        generateCaptcha();
    }
    return $_SESSION['captcha_answer'];
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!hasRole('admin')) {
        header("Location: " . BASE_URL . "index.php");
        exit();
    }
}

// Redirect if not staff
function requireStaff() {
    requireLogin();
    if (!hasRole('staff')) {
        header("Location: " . BASE_URL . "index.php");
        exit();
    }
}

// Redirect if not customer
function requireCustomer() {
    requireLogin();
    if (!hasRole('customer')) {
        header("Location: " . BASE_URL . "index.php");
        exit();
    }
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Get user by email and role
function getUserByEmail($email, $role = null) {
    $email = sanitize($email);
    
    if ($role && $role === 'customer') {
        $query = "SELECT * FROM customers WHERE email = '$email' LIMIT 1";
    } else {
        $query = "SELECT * FROM users WHERE email = '$email'" . ($role ? " AND role = '$role'" : "") . " LIMIT 1";
    }
    
    return fetchOne($query);
}

// Get customer by ID
function getCustomerById($id) {
    global $conn;
    return fetchOne("SELECT * FROM customers WHERE id = $id LIMIT 1");
}

// Get user by ID
function getUserById($id) {
    global $conn;
    return fetchOne("SELECT * FROM users WHERE id = $id LIMIT 1");
}

// Get current customer
function getCurrentCustomer() {
    if (isLoggedIn() && hasRole('customer')) {
        return getCustomerById($_SESSION['user_id']);
    }
    return null;
}

// Get current user (admin/staff)
function getCurrentUser() {
    if (isLoggedIn() && (hasRole('admin') || hasRole('staff'))) {
        return getUserById($_SESSION['user_id']);
    }
    return null;
}

?>
