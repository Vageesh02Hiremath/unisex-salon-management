<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'unisex_salon_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");

if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/unisex_salon_management'));
    $appFolder = '/unisex_salon_management';
    $appPos = strpos($scriptDir, $appFolder);
    define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
}

// Function to sanitize input
function sanitize($data) {
    global $conn;
    return htmlspecialchars($conn->real_escape_string(trim($data)));
}

// Function to execute queries
function executeQuery($query) {
    global $conn;
    return $conn->query($query);
}

// Execute a prepared statement and return the statement object.
function preparedQuery($query, $types = '', $params = []) {
    global $conn;
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        throw new Exception('Database execute failed: ' . $stmt->error);
    }
    return $stmt;
}

function fetchPrepared($query, $types = '', $params = []) {
    $stmt = preparedQuery($query, $types, $params);
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc() : null;
}

function fetchAllPrepared($query, $types = '', $params = []) {
    $stmt = preparedQuery($query, $types, $params);
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Function to fetch all results
function fetchAll($query) {
    global $conn;
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to fetch single row
function fetchOne($query) {
    global $conn;
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

// Function to get last insert ID
function getLastId() {
    global $conn;
    return $conn->insert_id;
}

// Function to check if email exists
function emailExists($email, $exclude_id = null) {
    global $conn;
    $email = sanitize($email);
    if ($exclude_id) {
        $query = "SELECT id FROM customers WHERE email = '$email' AND id != $exclude_id LIMIT 1";
    } else {
        $query = "SELECT id FROM customers WHERE email = '$email' LIMIT 1";
    }
    $result = $conn->query($query);
    return $result->num_rows > 0;
}

// Function to check if user email exists
function userEmailExists($email, $exclude_id = null) {
    global $conn;
    $email = sanitize($email);
    if ($exclude_id) {
        $query = "SELECT id FROM users WHERE email = '$email' AND id != $exclude_id LIMIT 1";
    } else {
        $query = "SELECT id FROM users WHERE email = '$email' LIMIT 1";
    }
    $result = $conn->query($query);
    return $result->num_rows > 0;
}

// Function to format currency
function formatCurrency($amount) {
    return '₹' . number_format((float)$amount, 2);
}

function phoneExists($phone, $exclude_id = null, $table = null) {
    global $conn;
    $phone = sanitize($phone);
    $exclude_id = $exclude_id ? (int)$exclude_id : null;
    $tables = $table ? [$table] : ['customers', 'users'];

    foreach ($tables as $target) {
        if (!in_array($target, ['customers', 'users'], true)) {
            continue;
        }
        $query = "SELECT id FROM $target WHERE phone = '$phone'";
        if ($exclude_id) {
            $query .= " AND id != $exclude_id";
        }
        $query .= " LIMIT 1";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            return true;
        }
    }

    return false;
}

function isValidName($name) {
    return (bool)preg_match('/^[A-Za-z][A-Za-z\s.\'-]{1,98}$/', $name);
}

function isValidPhone($phone) {
    return (bool)preg_match('/^[0-9+\-\s()]{7,20}$/', $phone);
}

function redirectTo($path) {
    header("Location: " . BASE_URL . ltrim($path, '/'));
    exit();
}

function statusLabel($status) {
    return ucwords(str_replace('-', ' ', (string)$status));
}

function badgeClass($status) {
    switch ($status) {
        case 'approved':
        case 'confirmed':
            return 'badge-info';
        case 'in-progress':
            return 'badge-warning';
        case 'completed':
        case 'paid':
            return 'badge-success';
        case 'rejected':
        case 'cancelled':
            return 'badge-danger';
        default:
            return 'badge-primary';
    }
}

// Function to format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Function to format datetime
function formatDateTime($datetime) {
    return date('M d, Y H:i A', strtotime($datetime));
}

?>
