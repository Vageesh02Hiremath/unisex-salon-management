<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once 'config/db.php';
require_once 'includes/auth.php';

requireLogin();

$error = '';
$success = '';
$user = null;

if (hasRole('customer')) {
    $user = getCustomerById($_SESSION['user_id']);
} else {
    $user = getUserById($_SESSION['user_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $roleTable = hasRole('customer') ? 'customers' : 'users';
    $userId = (int)$_SESSION['user_id'];
    
    if (empty($name)) {
        $error = 'Name cannot be empty';
    } elseif (!isValidName($name)) {
        $error = 'Please enter a valid name.';
    } elseif ($phone && !isValidPhone($phone)) {
        $error = 'Please enter a valid phone number.';
    } elseif ($phone && phoneExists($phone, $userId, $roleTable)) {
        $error = 'Phone number already exists.';
    } elseif ($password && $password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($password && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password)) {
        $error = 'Password must contain uppercase, lowercase, number, special character, and at least 8 characters.';
    } else {
        try {
            if ($password) {
                preparedQuery(
                    "UPDATE $roleTable SET name = ?, phone = ?, address = ?, password = ? WHERE id = ?",
                    'ssssi',
                    [$name, $phone, $address, hashPassword($password), $userId]
                );
            } else {
                preparedQuery(
                    "UPDATE $roleTable SET name = ?, phone = ?, address = ? WHERE id = ?",
                    'sssi',
                    [$name, $phone, $address, $userId]
                );
            }
            $_SESSION['name'] = $name;
            $success = 'Profile updated successfully!';
            if (hasRole('customer')) {
                $user = getCustomerById($_SESSION['user_id']);
            } else {
                $user = getUserById($_SESSION['user_id']);
            }
        } catch (Exception $exception) {
            $error = 'Failed to update profile';
        }
    }
}

$page_title = 'My Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Salon Management</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>My Profile</h1>
            <p>View and edit your profile information</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($user): ?>
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div style="flex: 1;">
                        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Role:</strong> <?php echo ucfirst($_SESSION['role']); ?></p>
                        <?php if (!empty($user['phone'])): ?>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                        <?php endif; ?>
                        <p style="margin-top: 1rem; color: #27AE60;"><strong>✓ Account Active</strong></p>
                    </div>
                </div>

                <div class="form-container">
                    <h3 style="margin-bottom: 1.5rem; color: #8B5FBF;">Edit Profile Information</h3>
                    <form method="POST" data-validate-identity data-base-url="<?php echo BASE_URL; ?>" data-scope="<?php echo hasRole('customer') ? 'customers' : 'users'; ?>" data-exclude-id="<?php echo (int)$_SESSION['user_id']; ?>">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($user['name']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" readonly value="<?php echo htmlspecialchars($user['email']); ?>" style="background-color: #f5f5f5; cursor: not-allowed;">
                            <small style="color: #999;">Email cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>

                        <?php if (hasRole('customer')): ?>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        <?php endif; ?>

                        <div style="border-top: 2px solid #eee; padding-top: 1.5rem; margin-top: 1.5rem;">
                            <h4 style="margin-bottom: 1rem; color: #8B5FBF;">Change Password (Optional)</h4>
                            
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <div class="icon-field"><i class="fa-solid fa-lock"></i><input type="password" id="password" name="password" placeholder="Leave blank to keep current password"><button type="button" class="password-toggle" data-target="password"><i class="fa-solid fa-eye"></i></button></div>
                                <small style="color: #999;">Minimum 8 characters with uppercase, lowercase, number, and special character</small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Retype Password</label>
                                <div class="icon-field"><i class="fa-solid fa-lock"></i><input type="password" id="confirm_password" name="confirm_password" placeholder="Retype new password"><button type="button" class="password-toggle" data-target="confirm_password"><i class="fa-solid fa-eye"></i></button></div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="<?php echo hasRole('admin') ? BASE_URL . 'admin/dashboard.php' : (hasRole('staff') ? BASE_URL . 'staff/dashboard.php' : BASE_URL . 'customer/dashboard.php'); ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
