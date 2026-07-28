<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/mailer.php';

$error = '';
$success = '';
$role = sanitize($_GET['role'] ?? 'customer');
$email = '';
$otpSent = false;
$flash = $_SESSION['forgot_password_flash'] ?? [];
unset($_SESSION['forgot_password_flash']);

if (!empty($flash['error'])) {
    $error = $flash['error'];
}
if (!empty($flash['success'])) {
    $success = $flash['success'];
}
if (!empty($flash['email'])) {
    $email = $flash['email'];
}
if (!empty($flash['role'])) {
    $role = $flash['role'];
}

if (!in_array($role, ['customer', 'staff', 'admin'], true)) {
    $role = 'customer';
}

if (isLoggedIn()) {
    redirectTo(hasRole('admin') ? 'admin/dashboard.php' : (hasRole('staff') ? 'staff/dashboard.php' : 'customer/dashboard.php'));
}

function resetPendingPasswordReset() {
    unset($_SESSION['pending_password_reset']);
}

function pendingPasswordReset() {
    return $_SESSION['pending_password_reset'] ?? null;
}

function findPasswordResetUser($email, $role) {
    if ($role === 'customer') {
        return fetchPrepared("SELECT id, name, email, status FROM customers WHERE email = ? LIMIT 1", 's', [$email]);
    }

    return fetchPrepared("SELECT id, name, email, status FROM users WHERE email = ? AND role = ? LIMIT 1", 'ss', [$email, $role]);
}

function updateResetPassword($id, $role, $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($role === 'customer') {
        preparedQuery("UPDATE customers SET password = ? WHERE id = ?", 'si', [$hash, $id]);
        return;
    }

    preparedQuery("UPDATE users SET password = ? WHERE id = ?", 'si', [$hash, $id]);
}

function redirectForgotPassword($role, $message = []) {
    $_SESSION['forgot_password_flash'] = $message;
    header('Location: ' . BASE_URL . 'forgot_password.php?role=' . urlencode($role));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'request_otp';

    if ($action === 'back_to_request') {
        $pending = pendingPasswordReset();
        if ($pending) {
            $role = $pending['role'];
            $email = $pending['email'];
            resetPendingPasswordReset();
        }
    } elseif ($action === 'resend_otp') {
        $pending = pendingPasswordReset();
        if (!$pending) {
            redirectForgotPassword($role, ['error' => 'Please request a new OTP to reset your password.', 'role' => $role]);
        }

        $user = findPasswordResetUser($pending['email'], $pending['role']);
        if (!$user || $user['status'] !== 'active') {
            resetPendingPasswordReset();
            redirectForgotPassword($pending['role'], ['error' => 'No active account was found for those details.', 'role' => $pending['role']]);
        }

        $otp = (string)random_int(100000, 999999);
        if (sendPasswordResetOtp($user['email'], $user['name'], $otp)) {
            $_SESSION['pending_password_reset']['otp_hash'] = password_hash($otp, PASSWORD_DEFAULT);
            $_SESSION['pending_password_reset']['expires_at'] = time() + 600;
            $_SESSION['pending_password_reset']['attempts'] = 0;
            redirectForgotPassword($pending['role'], ['success' => 'New OTP sent successfully. Please check your email.', 'role' => $pending['role']]);
        }

        redirectForgotPassword($pending['role'], ['error' => 'Could not resend OTP email. Please try again.', 'role' => $pending['role']]);
    } elseif ($action === 'verify_otp') {
        $pending = pendingPasswordReset();
        $otp = trim($_POST['otp'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $strong = preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password);

        if (!$pending) {
            redirectForgotPassword($role, ['error' => 'Please request a new OTP to reset your password.', 'role' => $role]);
        } elseif (time() > (int)$pending['expires_at']) {
            resetPendingPasswordReset();
            redirectForgotPassword($pending['role'], ['error' => 'OTP expired. Please request a new OTP.', 'role' => $pending['role']]);
        } elseif (($pending['attempts'] ?? 0) >= 5) {
            resetPendingPasswordReset();
            redirectForgotPassword($pending['role'], ['error' => 'Too many incorrect OTP attempts. Please request a new OTP.', 'role' => $pending['role']]);
        } elseif (!password_verify($otp, $pending['otp_hash'])) {
            $_SESSION['pending_password_reset']['attempts'] = ($pending['attempts'] ?? 0) + 1;
            redirectForgotPassword($pending['role'], ['error' => 'Invalid OTP. Please check your email and try again.', 'role' => $pending['role']]);
        } elseif ($password !== $confirm) {
            redirectForgotPassword($pending['role'], ['error' => 'Passwords do not match.', 'role' => $pending['role']]);
        } elseif (!$strong) {
            redirectForgotPassword($pending['role'], ['error' => 'Password must be strong before reset.', 'role' => $pending['role']]);
        } else {
            updateResetPassword((int)$pending['user_id'], $pending['role'], $password);
            resetPendingPasswordReset();
            redirectForgotPassword($pending['role'], ['success' => 'Password reset successful. You can login now.', 'role' => $pending['role']]);
        }
    } else {
        $role = sanitize($_POST['role'] ?? 'customer');
        $email = sanitize($_POST['email'] ?? '');

        if (!in_array($role, ['customer', 'staff', 'admin'], true)) {
            redirectForgotPassword('customer', ['error' => 'Please select a valid account type.', 'email' => $email, 'role' => 'customer']);
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectForgotPassword($role, ['error' => 'Please enter a valid email address.', 'email' => $email, 'role' => $role]);
        } else {
            $user = findPasswordResetUser($email, $role);
            if (!$user || $user['status'] !== 'active') {
                redirectForgotPassword($role, ['error' => 'No active account was found for those details.', 'email' => $email, 'role' => $role]);
            } else {
                $otp = (string)random_int(100000, 999999);
                if (sendPasswordResetOtp($user['email'], $user['name'], $otp)) {
                    $_SESSION['pending_password_reset'] = [
                        'user_id' => (int)$user['id'],
                        'role' => $role,
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'otp_hash' => password_hash($otp, PASSWORD_DEFAULT),
                        'expires_at' => time() + 600,
                        'attempts' => 0,
                        'created_at' => time()
                    ];
                    redirectForgotPassword($role, ['success' => 'OTP sent successfully. Please check your email.', 'role' => $role]);
                } else {
                    redirectForgotPassword($role, ['error' => 'Could not send OTP email. Please try again.', 'email' => $email, 'role' => $role]);
                }
            }
        }
    }
}

$pending = pendingPasswordReset();
if ($pending) {
    $otpSent = true;
}
if ($pending && $otpSent) {
    $role = $pending['role'];
    $email = $pending['email'];
}

$loginPath = $role === 'admin' ? 'admin_login.php' : 'login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Salon Management</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>assets/images/salon-logo.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
</head>
<body class="auth-page">
    <main class="auth-layout">
        <section class="auth-showcase" aria-label="Password reset highlights">
            <a href="<?php echo BASE_URL; ?>" class="auth-brand"><img class="auth-brand-logo" src="<?php echo BASE_URL; ?>assets/images/salon-logo.svg" alt=""> Unisex Salon</a>
            <div class="showcase-copy">
                <span>Password recovery</span>
                <h1>Reset your salon account securely.</h1>
                <p>Receive a one-time code by email, verify it, and set a new strong password.</p>
            </div>
            <div class="showcase-grid">
                <div><i class="fa-solid fa-envelope-circle-check"></i><strong>Email OTP</strong></div>
                <div><i class="fa-solid fa-key"></i><strong>Verify</strong></div>
                <div><i class="fa-solid fa-shield-halved"></i><strong>Secure</strong></div>
                <div><i class="fa-solid fa-right-to-bracket"></i><strong>Login</strong></div>
            </div>
        </section>
        <section class="auth-panel">
            <div class="auth-heading">
                <span class="auth-kicker">Reset password</span>
                <h2>Forgot password?</h2>
                <p class="muted">Use your registered email to receive a password reset OTP.</p>
            </div>

            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <?php if (!$otpSent): ?><a href="<?php echo BASE_URL . $loginPath; ?>">Login</a><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($otpSent && $pending): ?>
            <form method="POST" id="resetPasswordForm">
                <input type="hidden" name="action" value="verify_otp">
                <div class="form-group">
                    <label for="otp">Email OTP</label>
                    <div class="icon-field"><i class="fa-solid fa-key"></i><input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Enter 6-digit OTP" required autofocus></div>
                    <small class="muted">We sent the OTP to <?php echo htmlspecialchars($pending['email']); ?>. It expires in 10 minutes.</small>
                </div>
                <div class="password-lab">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <div class="icon-field"><i class="fa-solid fa-lock"></i><input type="password" id="password" name="password" required autocomplete="new-password"><button type="button" class="password-toggle" data-target="password"><i class="fa-solid fa-eye"></i></button></div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="icon-field"><i class="fa-solid fa-lock"></i><input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"><button type="button" class="password-toggle" data-target="confirm_password"><i class="fa-solid fa-eye"></i></button></div>
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
                <button type="submit" id="registerButton" class="btn btn-primary btn-block" disabled><i class="fa-solid fa-rotate"></i> Reset Password</button>
            </form>
            <div class="otp-actions otp-action-bar" aria-label="OTP actions">
                <form method="POST">
                    <input type="hidden" name="action" value="back_to_request">
                    <button type="submit" class="btn otp-back-btn"><i class="fa-solid fa-arrow-left"></i> Back</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="action" value="resend_otp">
                    <button type="submit" class="btn otp-resend-btn"><i class="fa-solid fa-paper-plane"></i> Resend OTP</button>
                </form>
            </div>
            <?php else: ?>
            <form method="POST" id="forgotPasswordForm">
                <input type="hidden" name="action" value="request_otp">
                <div class="form-group">
                    <label>Account Type</label>
                    <div class="role-selector" role="radiogroup" aria-label="Account type">
                        <label class="role-option">
                            <input type="radio" name="role" value="customer" <?php echo $role === 'customer' ? 'checked' : ''; ?>>
                            <span><i class="fa-solid fa-user"></i> Customer</span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="staff" <?php echo $role === 'staff' ? 'checked' : ''; ?>>
                            <span><i class="fa-solid fa-user-tie"></i> Staff</span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="admin" <?php echo $role === 'admin' ? 'checked' : ''; ?>>
                            <span><i class="fa-solid fa-user-shield"></i> Admin</span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="icon-field">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required autocomplete="email">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-paper-plane"></i> Send OTP</button>
            </form>
            <?php endif; ?>

            <?php if (!$otpSent): ?><div class="auth-link"><a href="<?php echo BASE_URL . $loginPath; ?>">Back to login</a></div><?php endif; ?>
        </section>
    </main>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>
