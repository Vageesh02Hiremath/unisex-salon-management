<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once 'config/db.php';
require_once 'includes/auth.php';

$error = '';
$success = '';
$name = '';
$email = '';
$phone = '';
if (!isset($_SESSION['captcha_answer'])) {
    generateCaptcha();
}

if (isLoggedIn()) {
    redirectTo(hasRole('admin') ? 'admin/dashboard.php' : (hasRole('staff') ? 'staff/dashboard.php' : 'customer/dashboard.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $captcha = $_POST['captcha'] ?? '';

    $strong = preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password);
    if (!$name || !$email || !$phone || !$password) {
        $error = 'Please fill in all required admin details.';
    } elseif (!verifyCaptcha($captcha)) {
        $error = 'CAPTCHA answer is incorrect.';
        generateCaptcha();
    } elseif (!isValidName($name)) {
        $error = 'Please enter a valid name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } elseif (!isValidPhone($phone)) {
        $error = 'Please enter a valid phone number.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!$strong) {
        $error = 'Password must be strong before registration.';
    } elseif (emailExists($email) || userEmailExists($email)) {
        $error = 'Email already exists.';
    } elseif (phoneExists($phone)) {
        $error = 'Phone number already exists.';
    } else {
        try {
            preparedQuery(
                "INSERT INTO users (name, email, password, role, status, phone) VALUES (?, ?, ?, 'admin', 'active', ?)",
                'ssss',
                [$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone]
            );
            $success = 'Admin registration successful. You can login now.';
            $name = $email = $phone = '';
        } catch (Exception $exception) {
            $error = 'Unable to register admin. Please check for duplicate details and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register - Salon Management</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>assets/images/salon-logo.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body class="auth-page" data-base-url="<?php echo BASE_URL; ?>">
    <main class="auth-layout auth-layout-register">
        <section class="auth-showcase" aria-label="Admin registration highlights">
            <a href="<?php echo BASE_URL; ?>" class="auth-brand"><img class="auth-brand-logo" src="<?php echo BASE_URL; ?>assets/images/salon-logo.svg" alt=""> Unisex Salon</a>
            <div class="showcase-copy">
                <span>Admin registration</span>
                <h1>Create an administrator account separately.</h1>
                <p>Admin users manage staff, services, bookings, billing, payments, feedback, and reports.</p>
            </div>
            <div class="showcase-grid">
                <div><i class="fa-solid fa-user-shield"></i><strong>Admin</strong></div>
                <div><i class="fa-solid fa-users-gear"></i><strong>Controls</strong></div>
                <div><i class="fa-solid fa-chart-line"></i><strong>Reports</strong></div>
                <div><i class="fa-solid fa-lock"></i><strong>Secure</strong></div>
            </div>
        </section>
        <section class="auth-panel">
            <div class="auth-heading">
                <span class="auth-kicker">Admin register</span>
                <h2>Create admin account</h2>
                <p class="muted">Use this page only for administrator registration.</p>
            </div>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="<?php echo BASE_URL; ?>admin_login.php">Admin login</a></div><?php endif; ?>

            <form method="POST" id="registerForm" data-validate-identity data-base-url="<?php echo BASE_URL; ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <div class="icon-field"><i class="fa-solid fa-user-shield"></i><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required></div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="icon-field"><i class="fa-solid fa-envelope"></i><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required></div>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <div class="icon-field"><i class="fa-solid fa-phone"></i><input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required></div>
                    </div>
                </div>

                <div class="password-lab">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="icon-field"><i class="fa-solid fa-lock"></i><input type="password" id="password" name="password" required><button type="button" class="password-toggle" data-target="password"><i class="fa-solid fa-eye"></i></button></div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="icon-field"><i class="fa-solid fa-lock"></i><input type="password" id="confirm_password" name="confirm_password" required><button type="button" class="password-toggle" data-target="confirm_password"><i class="fa-solid fa-eye"></i></button></div>
                        </div>
                    </div>
                    <div class="strength-head">
                        <span><i class="fa-solid fa-shield-halved"></i> Strength: <strong id="strengthText">Weak</strong></span>
                        <button type="button" class="btn btn-secondary btn-sm" id="generatePassword"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate</button>
                    </div>
                    <div class="strength-track"><div id="strengthBar"></div></div>
                    <div class="generated-row">
                        <input type="text" id="generatedPassword" readonly placeholder="Generated password appears here">
                        <button type="button" class="btn btn-secondary btn-sm" id="copyPassword"><i class="fa-solid fa-copy"></i> Copy</button>
                    </div>
                    <ul class="password-checklist">
                        <li data-check="length"><i class="fa-solid fa-circle"></i> Minimum 8 characters</li>
                        <li data-check="upper"><i class="fa-solid fa-circle"></i> Uppercase letter</li>
                        <li data-check="lower"><i class="fa-solid fa-circle"></i> Lowercase letter</li>
                        <li data-check="number"><i class="fa-solid fa-circle"></i> Number</li>
                        <li data-check="special"><i class="fa-solid fa-circle"></i> Special character</li>
                        <li data-check="match"><i class="fa-solid fa-circle"></i> Passwords match</li>
                    </ul>
                </div>

                <div class="form-group">
                    <label for="captcha">Enter CAPTCHA Digits</label>
                    <div class="captcha-row">
                        <div class="captcha-code" id="captchaCode" aria-label="CAPTCHA code"><?php echo htmlspecialchars(captchaCode()); ?></div>
                        <button type="button" class="captcha-refresh" id="refreshCaptcha" aria-label="Refresh CAPTCHA"><i class="fa-solid fa-rotate-right"></i></button>
                    </div>
                    <div class="icon-field"><i class="fa-solid fa-shield-halved"></i><input type="text" id="captcha" name="captcha" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="off" required placeholder="Enter 6 digits"></div>
                </div>

                <button type="submit" id="registerButton" class="btn btn-primary btn-block" disabled><i class="fa-solid fa-user-shield"></i> Register Admin</button>
            </form>
            <div class="auth-link">Already an admin? <a href="<?php echo BASE_URL; ?>admin_login.php">Admin login</a></div>
            <div class="auth-link">Customer or staff? <a href="<?php echo BASE_URL; ?>register.php">Use regular registration</a></div>
            <div class="auth-footer">
                <a href="<?php echo BASE_URL; ?>admin_login.php" class="auth-back-link"><i class="fa-solid fa-arrow-left"></i> Back to admin login</a>
            </div>
        </section>
    </main>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>
