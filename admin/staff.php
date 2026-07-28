<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';

requireAdmin();

$error = '';
$success = '';

// Delete staff
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $query = "DELETE FROM users WHERE id = $id AND role = 'staff'";
    if ($conn->query($query)) {
        $success = 'Staff member deleted successfully!';
    } else {
        $error = 'Failed to delete staff member';
    }
}

// Add new staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_staff') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $specialization = sanitize($_POST['specialization'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $availability_start = sanitize($_POST['availability_start'] ?? '');
    $availability_end = sanitize($_POST['availability_end'] ?? '');
    $commission_percentage = floatval($_POST['commission_percentage'] ?? 10);
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif (!isValidName($name)) {
        $error = 'Please enter a valid staff name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid staff email.';
    } elseif ($phone && !isValidPhone($phone)) {
        $error = 'Please enter a valid phone number.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password)) {
        $error = 'Password must contain uppercase, lowercase, number, special character, and at least 8 characters.';
    } elseif (userEmailExists($email)) {
        $error = 'Email already exists';
    } elseif ($phone && phoneExists($phone)) {
        $error = 'Phone number already exists';
    } else {
        $conn->begin_transaction();
        try {
            preparedQuery(
                "INSERT INTO users (name, email, password, role, status, phone) VALUES (?, ?, ?, 'staff', 'active', ?)",
                'ssss',
                [$name, $email, hashPassword($password), $phone]
            );
            $user_id = getLastId();
            preparedQuery(
                "INSERT INTO staff (user_id, specialization, availability_start, availability_end, commission_percentage) VALUES (?, ?, ?, ?, ?)",
                'isssd',
                [$user_id, $specialization, $availability_start ?: null, $availability_end ?: null, $commission_percentage]
            );
            $conn->commit();
            $success = 'Staff member added successfully!';
        } catch (Exception $exception) {
            $conn->rollback();
            $error = 'Failed to add staff member. Please check for duplicate details and try again.';
        }
    }
}

// Get all staff
$search = $_GET['search'] ?? '';
if ($search) {
    $search = sanitize($search);
    $staff_list = fetchAll("
        SELECT u.*, s.specialization, s.availability_start, s.availability_end, s.commission_percentage
        FROM users u
        LEFT JOIN staff s ON u.id = s.user_id
        WHERE u.role = 'staff' AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%')
        ORDER BY u.created_at DESC
    ");
} else {
    $staff_list = fetchAll("
        SELECT u.*, s.specialization, s.availability_start, s.availability_end, s.commission_percentage
        FROM users u
        LEFT JOIN staff s ON u.id = s.user_id
        WHERE u.role = 'staff'
        ORDER BY u.created_at DESC
    ");
}

$page_title = 'Manage Staff';
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
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Manage Staff</h1>
            <p>Add, view, and manage staff members</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h3 style="margin-bottom: 1.5rem; color: #8B5FBF;">Add New Staff Member</h3>
            <form method="POST" data-validate-identity data-base-url="<?php echo BASE_URL; ?>">
                <input type="hidden" name="action" value="add_staff">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone">
                    </div>

                    <div class="form-group">
                        <label for="specialization">Specialization</label>
                        <input type="text" id="specialization" name="specialization" placeholder="e.g., Hair Styling">
                    </div>

                    <div class="form-group">
                        <label for="availability_start">Work Start Time</label>
                        <input type="time" id="availability_start" name="availability_start">
                    </div>

                    <div class="form-group">
                        <label for="availability_end">Work End Time</label>
                        <input type="time" id="availability_end" name="availability_end">
                    </div>

                    <div class="form-group">
                        <label for="commission_percentage">Commission %</label>
                        <input type="number" id="commission_percentage" name="commission_percentage" step="0.01" min="0" max="100" value="10">
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <div class="icon-field"><i class="fa-solid fa-lock"></i><input type="password" id="password" name="password" required><button type="button" class="password-toggle" data-target="password"><i class="fa-solid fa-eye"></i></button></div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Retype Password *</label>
                        <div class="icon-field"><i class="fa-solid fa-lock"></i><input type="password" id="confirm_password" name="confirm_password" required><button type="button" class="password-toggle" data-target="confirm_password"><i class="fa-solid fa-eye"></i></button></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Add Staff Member</button>
            </form>
        </div>

        <div class="table-container" style="margin-top: 2rem;">
            <div class="table-header flex-between">
                <h2>Staff Members</h2>
                <form method="GET" style="display: flex; gap: 0.5rem;">
                    <input type="text" name="search" placeholder="Search by name or email" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Specialization</th>
                        <th>Availability</th>
                        <th>Commission</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($staff_list): ?>
                        <?php foreach ($staff_list as $staff): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($staff['name']); ?></td>
                                <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                <td><?php echo htmlspecialchars($staff['specialization'] ?? '-'); ?></td>
                                <td>
                                    <?php 
                                    if ($staff['availability_start'] && $staff['availability_end']) {
                                        echo date('H:i', strtotime($staff['availability_start'])) . ' - ' . date('H:i', strtotime($staff['availability_end']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $staff['commission_percentage'] ?? '-'; ?>%</td>
                                <td>
                                    <span class="badge <?php echo $staff['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($staff['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>admin/staff.php?delete_id=<?php echo $staff['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No staff members found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
