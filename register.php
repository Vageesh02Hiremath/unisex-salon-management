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
$role = 'customer';
$name = '';
$email = '';
$phone = '';
$gender = '';
$specialization = '';
$availability_start = '';
$availability_end = '';
$days_working = '';
$commission_percentage = '10';
$otpSent = false;
if (!isset($_SESSION['captcha_answer'])) {
    generateCaptcha();
}

if (isLoggedIn()) {
    redirectTo(hasRole('admin') ? 'admin/dashboard.php' : (hasRole('staff') ? 'staff/dashboard.php' : 'customer/dashboard.php'));
}

function resetPendingRegistration() {
    unset($_SESSION['pending_registration']);
}

function pendingRegistration() {
    return $_SESSION['pending_registration'] ?? null;
}

function createRegisteredAccount($pending) {
    global $conn;

    if ($pending['role'] === 'customer') {
        try {
            preparedQuery(
                "INSERT INTO customers (name, email, password, phone, gender, status) VALUES (?, ?, ?, ?, ?, 'active')",
                'sssss',
                [$pending['name'], $pending['email'], $pending['password_hash'], $pending['phone'], $pending['gender']]
            );
        } catch (Exception $exception) {
            throw new Exception('Unable to create customer account. Please check for duplicate details.');
        }
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'request_otp';

    if ($action === 'edit_registration') {
        $pending = pendingRegistration();
        if ($pending) {
            $role = $pending['role'];
            $name = $pending['name'];
            $email = $pending['email'];
            $phone = $pending['phone'];
            $gender = $pending['gender'];
            $specialization = $pending['specialization'];
            $availability_start = $pending['availability_start'];
            $availability_end = $pending['availability_end'];
            $days_working = $pending['days_working'];
            $commission_percentage = (string)$pending['commission_percentage'];
            resetPendingRegistration();
            generateCaptcha();
        }
    } elseif ($action === 'resend_otp') {
        $pending = pendingRegistration();
        if (!$pending) {
            $error = 'Please register again to receive a new OTP.';
        } elseif (emailExists($pending['email']) || userEmailExists($pending['email'])) {
            resetPendingRegistration();
            $error = 'Email already exists.';
        } elseif (phoneExists($pending['phone'])) {
            resetPendingRegistration();
            $error = 'Phone number already exists.';
        } else {
            $otp = (string)random_int(100000, 999999);
            if (sendRegistrationOtp($pending['email'], $pending['name'], $otp)) {
                $_SESSION['pending_registration']['otp_hash'] = password_hash($otp, PASSWORD_DEFAULT);
                $_SESSION['pending_registration']['expires_at'] = time() + 600;
                $_SESSION['pending_registration']['attempts'] = 0;
                $success = 'New OTP sent successfully. Please check your email.';
                $otpSent = true;
            } else {
                $error = 'Could not resend OTP email. Please try again.';
                $otpSent = true;
            }
        }
    } elseif ($action === 'verify_otp') {
        $pending = pendingRegistration();
        $otp = trim($_POST['otp'] ?? '');

        if (!$pending) {
            $error = 'Please request a new OTP to continue registration.';
        } elseif (time() > (int)$pending['expires_at']) {
            resetPendingRegistration();
            $error = 'OTP expired. Please register again to receive a new OTP.';
        } elseif (($pending['attempts'] ?? 0) >= 5) {
            resetPendingRegistration();
            $error = 'Too many incorrect OTP attempts. Please register again.';
        } elseif (!password_verify($otp, $pending['otp_hash'])) {
            $_SESSION['pending_registration']['attempts'] = ($pending['attempts'] ?? 0) + 1;
            $error = 'Invalid OTP. Please check your email and try again.';
            $otpSent = true;
        } elseif (emailExists($pending['email']) || userEmailExists($pending['email'])) {
            resetPendingRegistration();
            $error = 'Email already exists.';
        } elseif (phoneExists($pending['phone'])) {
            resetPendingRegistration();
            $error = 'Phone number already exists.';
        } else {
            try {
                createRegisteredAccount($pending);
                resetPendingRegistration();
                $success = ucfirst($pending['role']) . ' registration successful. You can login now.';
                $role = 'customer';
                $name = $email = $phone = $gender = $specialization = $availability_start = $availability_end = $days_working = '';
                $commission_percentage = '10';
            } catch (Exception $exception) {
                $error = $exception->getMessage();
                $otpSent = true;
            }
        }
    } else {
        $role = sanitize($_POST['role'] ?? 'customer');
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $gender = sanitize($_POST['gender'] ?? '');
        $specialization = sanitize($_POST['specialization'] ?? '');
        $availability_start = sanitize($_POST['availability_start'] ?? '');
        $availability_end = sanitize($_POST['availability_end'] ?? '');
        $days_working = sanitize($_POST['days_working'] ?? '');
        $commission_percentage = sanitize($_POST['commission_percentage'] ?? '10');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $captcha = $_POST['captcha'] ?? '';

        $strong = preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password);
        if (!in_array($role, ['customer', 'staff'], true)) {
            $error = 'Please select a valid account type.';
        } elseif (!verifyCaptcha($captcha)) {
            $error = 'CAPTCHA answer is incorrect.';
            generateCaptcha();
        } elseif (!$name || !$email || !$phone || !$password || ($role === 'customer' && !$gender)) {
            $error = 'Please fill in all required fields.';
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
            $otp = (string)random_int(100000, 999999);
            if (sendRegistrationOtp($email, $name, $otp)) {
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
                $success = 'OTP sent successfully. Please check your email to complete registration.';
                $otpSent = true;
            } else {
                $error = 'Could not send OTP email. Please check SMTP settings and try again.';
            }
        }
    }
}

$pending = pendingRegistration();
if ($pending && !$success && !$error) {
    $otpSent = true;
}
if ($pending && $otpSent) {
    $role = $pending['role'];
    $name = $pending['name'];
    $email = $pending['email'];
    $phone = $pending['phone'];
    $gender = $pending['gender'];
    $specialization = $pending['specialization'];
    $availability_start = $pending['availability_start'];
    $availability_end = $pending['availability_end'];
    $days_working = $pending['days_working'];
    $commission_percentage = (string)$pending['commission_percentage'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Salon Management</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>assets/images/salon-logo.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
</head>
<body class="auth-page" data-base-url="<?php echo BASE_URL; ?>">
    <main class="auth-layout auth-layout-register">
        <section class="auth-showcase" aria-label="Salon registration highlights">
            <a href="<?php echo BASE_URL; ?>" class="auth-brand"><img class="auth-brand-logo" src="<?php echo BASE_URL; ?>assets/images/salon-logo.svg" alt=""> Unisex Salon</a>
            <div class="showcase-copy">
                <span>Customer and staff registration</span>
                <h1>Create the right account for each salon workflow.</h1>
                <p>Customers can book faster, and staff can manage schedules and assigned appointments.</p>
            </div>
            <div class="showcase-grid">
                <div><i class="fa-solid fa-user-check"></i><strong>Profile</strong></div>
                <div><i class="fa-solid fa-user-tie"></i><strong>Staff</strong></div>
                <div><i class="fa-solid fa-calendar-check"></i><strong>Bookings</strong></div>
                <div><i class="fa-solid fa-clock"></i><strong>Schedules</strong></div>
            </div>
        </section>
        <section class="auth-panel">
            <div class="auth-heading">
                <span class="auth-kicker">Register</span>
                <h2>Create account</h2>
                <p class="muted">Choose customer or staff and enter the matching account details.</p>
            </div>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <?php if (!$otpSent): ?><a href="<?php echo BASE_URL; ?>login.php">Login</a><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($otpSent && $pending): ?>
            <form method="POST" class="otp-form">
                <input type="hidden" name="action" value="verify_otp">
                <div class="form-group">
                    <label for="otp">Email OTP</label>
                    <div class="icon-field"><i class="fa-solid fa-key"></i><input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Enter 6-digit OTP" required autofocus></div>
                    <small class="muted">We sent the OTP to <?php echo htmlspecialchars($pending['email']); ?>. It expires in 10 minutes.</small>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-circle-check"></i> Verify OTP & Register</button>
            </form>
            <div class="otp-actions otp-action-bar" aria-label="OTP actions">
                <form method="POST">
                    <input type="hidden" name="action" value="edit_registration">
                    <button type="submit" class="btn otp-back-btn"><i class="fa-solid fa-arrow-left"></i> Back</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="action" value="resend_otp">
                    <button type="submit" class="btn otp-resend-btn"><i class="fa-solid fa-paper-plane"></i> Resend OTP</button>
                </form>
            </div>
            <?php else: ?>
            <form method="POST" id="registerForm" data-validate-identity data-base-url="<?php echo BASE_URL; ?>">
                <input type="hidden" name="action" value="request_otp">
                <div class="form-group">
                    <label>Account Type</label>
                    <div class="role-selector role-selector-two" role="radiogroup" aria-label="Registration account type">
                        <label class="role-option">
                            <input type="radio" name="role" value="customer" <?php echo $role === 'customer' ? 'checked' : ''; ?>>
                            <span><i class="fa-solid fa-user"></i> Customer</span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="staff" <?php echo $role === 'staff' ? 'checked' : ''; ?>>
                            <span><i class="fa-solid fa-user-tie"></i> Staff</span>
                        </label>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <div class="icon-field"><i class="fa-solid fa-user"></i><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required></div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="icon-field"><i class="fa-solid fa-envelope"></i><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required></div>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <div class="icon-field"><i class="fa-solid fa-phone"></i><input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required></div>
                    </div>
                    <div class="form-group customer-field">
                        <label for="gender">Gender</label>
                        <div class="icon-field"><i class="fa-solid fa-venus-mars"></i>
                            <select id="gender" name="gender" required>
                                <option value="">Select</option>
                                <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo $gender === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="staff-fields hidden" id="staffFields">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="specialization">Specialization</label>
                            <div class="icon-field"><i class="fa-solid fa-scissors"></i><input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($specialization); ?>" placeholder="Hair Styling"></div>
                        </div>
                        <div class="form-group">
                            <label for="days_working">Working Days</label>
                            <div class="icon-field"><i class="fa-solid fa-calendar-days"></i><input type="text" id="days_working" name="days_working" value="<?php echo htmlspecialchars($days_working); ?>" placeholder="Mon,Tue,Wed,Thu,Fri"></div>
                        </div>
                        <div class="form-group">
                            <label for="availability_start">Work Start Time</label>
                            <div class="icon-field"><i class="fa-solid fa-clock"></i><input type="time" id="availability_start" name="availability_start" value="<?php echo htmlspecialchars($availability_start); ?>"></div>
                        </div>
                        <div class="form-group">
                            <label for="availability_end">Work End Time</label>
                            <div class="icon-field"><i class="fa-solid fa-clock"></i><input type="time" id="availability_end" name="availability_end" value="<?php echo htmlspecialchars($availability_end); ?>"></div>
                        </div>
                        <div class="form-group">
                            <label for="commission_percentage">Commission %</label>
                            <div class="icon-field"><i class="fa-solid fa-percent"></i><input type="number" id="commission_percentage" name="commission_percentage" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($commission_percentage); ?>"></div>
                        </div>
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

                <button type="submit" id="registerButton" class="btn btn-primary btn-block" disabled><i class="fa-solid fa-user-plus"></i> Register</button>
            </form>
            <?php endif; ?>
            <div class="auth-link">Already registered? <a href="<?php echo BASE_URL; ?>login.php">Login here</a></div>
            <div class="auth-link">Registering an admin? <a href="<?php echo BASE_URL; ?>admin_register.php">Use admin registration</a></div>
            <?php if (!$otpSent): ?>
            <div class="auth-footer">
                <a href="<?php echo BASE_URL; ?>login.php" class="auth-back-link"><i class="fa-solid fa-arrow-left"></i> Back to login</a>
            </div>
            <?php endif; ?>
        </section>
    </main>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>
