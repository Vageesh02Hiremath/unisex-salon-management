<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once 'config/db.php';
require_once 'includes/auth.php';

$error = '';
if (!isset($_SESSION['captcha_answer'])) {
    generateCaptcha();
}

if (isLoggedIn()) {
    redirectTo(hasRole('admin') ? 'admin/dashboard.php' : (hasRole('staff') ? 'staff/dashboard.php' : 'customer/dashboard.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'customer');
    $captcha = $_POST['captcha'] ?? '';

    if (tooManyLoginAttempts($email)) {
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } elseif (!verifyCaptcha($captcha)) {
        $error = 'CAPTCHA answer is incorrect.';
        recordLoginAttempt($email, false);
        generateCaptcha();
    } elseif (empty($email) || empty($password) || !in_array($role, ['staff', 'customer'], true)) {
        $error = 'Please enter valid login details.';
    } else {
        $table = $role === 'customer' ? 'customers' : 'users';
        $query = $role === 'customer'
            ? "SELECT * FROM customers WHERE email = ? LIMIT 1"
            : "SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1";
        $user = $role === 'customer'
            ? fetchPrepared($query, 's', [$email])
            : fetchPrepared($query, 'ss', [$email, $role]);

        if ($user && password_verify($password, $user['password']) && $user['status'] === 'active') {
            recordLoginAttempt($email, true);
            preparedQuery("UPDATE $table SET last_login = NOW() WHERE id = ?", 'i', [(int)$user['id']]);
            loginUser($user['id'], $user['email'], $role, $user['name']);
            redirectTo($role === 'admin' ? 'admin/dashboard.php' : ($role === 'staff' ? 'staff/dashboard.php' : 'customer/dashboard.php'));
        }
        $error = 'Invalid email, password, or role.';
        recordLoginAttempt($email, false);
        generateCaptcha();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Salon Management</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>assets/images/salon-logo.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body class="auth-page" data-base-url="<?php echo BASE_URL; ?>">
    <main class="auth-layout">
        <section class="auth-showcase" aria-label="Salon access highlights">
            <a href="<?php echo BASE_URL; ?>" class="auth-brand"><img class="auth-brand-logo" src="<?php echo BASE_URL; ?>assets/images/salon-logo.svg" alt=""> Unisex Salon</a>
            <div class="showcase-copy">
                <span>Customer and staff access</span>
                <h1>Continue to your salon workspace securely.</h1>
                <p>Customers can manage bookings and bills. Staff can review assigned appointments and schedules.</p>
            </div>
            <div class="showcase-grid">
                <div><i class="fa-solid fa-calendar-check"></i><strong>Bookings</strong></div>
                <div><i class="fa-solid fa-user-tie"></i><strong>Staff</strong></div>
                <div><i class="fa-solid fa-file-invoice"></i><strong>Bills</strong></div>
                <div><i class="fa-solid fa-star"></i><strong>Feedback</strong></div>
            </div>
        </section>
        <section class="auth-panel">
            <div class="auth-heading">
                <span class="auth-kicker">Sign in</span>
                <h2>Welcome back</h2>
                <p class="muted">Choose customer or staff and continue to the correct dashboard.</p>
            </div>

            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="role">Login As</label>
                    <div class="role-selector role-selector-two" role="radiogroup" aria-label="Login role">
                        <label class="role-option">
                            <input type="radio" name="role" value="customer" checked>
                            <span><i class="fa-solid fa-user"></i> Customer</span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="staff">
                            <span><i class="fa-solid fa-user-tie"></i> Staff</span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="icon-field">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="email" name="email" required autocomplete="email">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="icon-field">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                        <button type="button" class="password-toggle" data-target="password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                    <div class="auth-row" style="justify-content:flex-end;margin:.45rem 0 0;">
                        <a href="<?php echo BASE_URL; ?>forgot_password.php">Forgot password?</a>
                    </div>
                </div>
                <div class="form-group">
                    <label for="captcha">Enter CAPTCHA Digits</label>
                    <div class="captcha-row">
                        <div class="captcha-code" id="captchaCode" aria-label="CAPTCHA code">
                            <?php echo htmlspecialchars(captchaCode()); ?>
                        </div>
                        <button type="button" class="captcha-refresh" id="refreshCaptcha" aria-label="Refresh CAPTCHA">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                    </div>
                    <div class="icon-field">
                        <i class="fa-solid fa-shield-halved"></i>
                        <input type="text" id="captcha" name="captcha" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="off" required placeholder="Enter 6 digits">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block auth-submit">
                    <span class="btn-text"><i class="fa-solid fa-right-to-bracket"></i> Login</span>
                    <span class="spinner"></span>
                </button>
            </form>

            <div class="auth-link">Need an account? <a href="<?php echo BASE_URL; ?>register.php">Register here</a></div>
            <div class="auth-link">Admin access? <a href="<?php echo BASE_URL; ?>admin_login.php">Login as admin</a></div>
            <div class="auth-footer">
                <a href="<?php echo BASE_URL; ?>" class="auth-back-link"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
        </section>
    </main>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>
