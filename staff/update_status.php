<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/generate_bill.php';

requireStaff();

$user = getCurrentUser();
$user_id = $user['id'];
$error = '';
$success = '';

// Get staff ID
$staff = fetchOne("SELECT id FROM staff WHERE user_id = $user_id");
if (!$staff) {
    die('Staff record not found');
}
$staff_id = $staff['id'];

// Get appointment
$appointment_id = intval($_GET['id'] ?? 0);
if ($appointment_id <= 0) {
    die('Invalid appointment');
}

$appointment = fetchOne("
    SELECT a.*, c.name as customer_name, s.name as service_name
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    WHERE a.id = $appointment_id AND a.staff_id = $staff_id
");

if (!$appointment) {
    die('Appointment not found or not assigned to you');
}

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = sanitize($_POST['status']);
    $valid_statuses = ['pending', 'approved', 'rejected', 'confirmed', 'in-progress', 'completed', 'cancelled'];
    
    if (!in_array($new_status, $valid_statuses)) {
        $error = 'Invalid status';
    } else {
        $query = "UPDATE appointments SET status = '$new_status' WHERE id = $appointment_id";
        if ($conn->query($query)) {
            $success = 'Appointment status updated successfully!';
            $appointment['status'] = $new_status;
            if ($new_status === 'completed') {
                $bill = generateBillForAppointment($appointment_id);
                $success .= $bill['success'] ? ' Bill generated automatically.' : ' Bill generation skipped: ' . $bill['message'];
            }
        } else {
            $error = 'Failed to update status';
        }
    }
}

$page_title = 'Update Appointment Status';
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
            <h1>Update Appointment Status</h1>
            <p>Change the status of your appointment</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <div class="form-container">
                <h3 style="margin-bottom: 1.5rem; color: #8B5FBF;">Appointment Details</h3>
                
                <div style="background: #f9f9f9; padding: 1.5rem; border-radius: 5px;">
                    <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #eee;">
                        <strong style="color: #999; display: block; font-size: 0.85rem; text-transform: uppercase;">Customer</strong>
                        <div style="color: #333; font-size: 1.1rem;"><?php echo htmlspecialchars($appointment['customer_name']); ?></div>
                    </div>

                    <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #eee;">
                        <strong style="color: #999; display: block; font-size: 0.85rem; text-transform: uppercase;">Service</strong>
                        <div style="color: #333; font-size: 1.1rem;"><?php echo htmlspecialchars($appointment['service_name']); ?></div>
                    </div>

                    <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #eee;">
                        <strong style="color: #999; display: block; font-size: 0.85rem; text-transform: uppercase;">Date & Time</strong>
                        <div style="color: #333; font-size: 1.1rem;">
                            <?php echo formatDate($appointment['appointment_date']); ?>
                            @ <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                        </div>
                    </div>

                    <div style="margin-bottom: 0;">
                        <strong style="color: #999; display: block; font-size: 0.85rem; text-transform: uppercase;">Current Status</strong>
                        <div style="color: #333; font-size: 1.1rem;">
                            <span class="badge badge-<?php 
                                $status = $appointment['status'];
                                if ($status === 'pending') echo 'primary';
                                elseif ($status === 'confirmed') echo 'info';
                                elseif ($status === 'in-progress') echo 'warning';
                                elseif ($status === 'completed') echo 'success';
                                elseif ($status === 'cancelled') echo 'danger';
                            ?>">
                                <?php echo ucfirst($status); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-container">
                <h3 style="margin-bottom: 1.5rem; color: #8B5FBF;">Change Status</h3>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="status">New Status *</label>
                        <select id="status" name="status" required>
                            <option value="">Select a status</option>
                            <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $appointment['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $appointment['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="in-progress" <?php echo $appointment['status'] === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Update Status</button>
                        <a href="<?php echo BASE_URL; ?>staff/assigned_appointments.php" class="btn btn-secondary" style="flex: 1; text-align: center;">Back</a>
                    </div>
                </form>

                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #eee;">
                    <h4 style="color: #8B5FBF; margin-bottom: 1rem;">Status Guide</h4>
                    <div style="font-size: 0.9rem; line-height: 1.8;">
                        <p><strong>Pending:</strong> Waiting for approval</p>
                        <p><strong>Confirmed:</strong> Appointment confirmed</p>
                        <p><strong>In Progress:</strong> Currently serving</p>
                        <p><strong>Completed:</strong> Service finished</p>
                        <p><strong>Cancelled:</strong> Appointment cancelled</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
