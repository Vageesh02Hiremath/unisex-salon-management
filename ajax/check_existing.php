<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';

header('Content-Type: application/json');

$email = sanitize($_GET['email'] ?? '');
$phone = sanitize($_GET['phone'] ?? '');
$excludeId = (int)($_GET['exclude_id'] ?? 0);
$scope = sanitize($_GET['scope'] ?? 'all');
$table = in_array($scope, ['customers', 'users'], true) ? $scope : null;

$response = [
    'success' => true,
    'email_exists' => false,
    'phone_exists' => false,
    'messages' => []
];

try {
    if ($email !== '') {
        $response['email_exists'] = emailExists($email, $excludeId ?: null) || userEmailExists($email, $excludeId ?: null);
        if ($response['email_exists']) {
            $response['messages']['email'] = 'Email already exists.';
        }
    }

    if ($phone !== '') {
        $response['phone_exists'] = phoneExists($phone, $excludeId ?: null, $table);
        if ($response['phone_exists']) {
            $response['messages']['phone'] = 'Phone number already exists.';
        }
    }
} catch (Exception $exception) {
    http_response_code(500);
    $response = ['success' => false, 'message' => 'Unable to check existing details.'];
}

echo json_encode($response);
?>
