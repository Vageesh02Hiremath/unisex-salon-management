<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';

requireCustomer();

$customer = getCurrentCustomer();
$customer_id = $customer['id'];
$error = '';
$success = '';

// Cancel appointment
if (isset($_GET['cancel_id'])) {
    $id = intval($_GET['cancel_id']);
    $appt = fetchOne("SELECT * FROM appointments WHERE id = $id AND customer_id = $customer_id");
    
    $starts_at = $appt ? strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']) : 0;
    if ($appt && $starts_at > time() && ($starts_at - time()) < (2 * 60 * 60)) {
        $error = 'Appointments cannot be cancelled within 2 hours of the scheduled time.';
    } elseif ($appt && $appt['status'] !== 'completed' && $appt['status'] !== 'cancelled') {
        $query = "UPDATE appointments SET status = 'cancelled' WHERE id = $id";
        if ($conn->query($query)) {
            $success = 'Appointment cancelled successfully';
        } else {
            $error = 'Failed to cancel appointment';
        }
    } else {
        $error = 'Cannot cancel this appointment';
    }
}

// Get appointments
$appointments = fetchAll("
    SELECT a.*, s.name as service_name, s.price
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.customer_id = $customer_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");

$page_title = 'My Appointments';
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
            <h1>My Appointments</h1>
            <p>View and manage your salon appointments</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <div class="table-header flex-between">
                <h2>My Appointments</h2>
                <a href="<?php echo BASE_URL; ?>customer/book_appointment.php" class="btn btn-primary btn-sm">Book New Appointment</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($appointments): ?>
                        <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appt['service_name']); ?></td>
                                <td><?php echo formatDate($appt['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></td>
                                <td><?php echo formatCurrency($appt['price']); ?></td>
                                <td>
                                    <?php 
                                    $status = $appt['status'];
                                    $badge_class = 'badge-primary';
                                    if ($status === 'confirmed') $badge_class = 'badge-info';
                                    elseif ($status === 'in-progress') $badge_class = 'badge-warning';
                                    elseif ($status === 'completed') $badge_class = 'badge-success';
                                    elseif ($status === 'cancelled') $badge_class = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span>
                                </td>
                                <td>
                                    <?php if ($status !== 'completed' && $status !== 'cancelled'): ?>
                                        <a href="<?php echo BASE_URL; ?>customer/book_appointment.php?service_id=<?php echo (int)$appt['service_id']; ?>" class="btn btn-secondary btn-sm">Reschedule</a>
                                        <a href="<?php echo BASE_URL; ?>customer/my_appointments.php?cancel_id=<?php echo $appt['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this appointment?')">Cancel</a>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No appointments yet. <a href="<?php echo BASE_URL; ?>customer/book_appointment.php">Book one now!</a></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="form-container" style="margin-top: 2rem;">
            <h3 style="margin-bottom: 1rem; color: #8B5FBF;">Appointment Status Explanation</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div style="padding: 1rem; background: #e8f4f8; border-radius: 5px; border-left: 3px solid #3498DB;">
                    <strong style="color: #3498DB;">Pending</strong>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">Your appointment is awaiting admin approval</p>
                </div>
                <div style="padding: 1rem; background: #d1ecf1; border-radius: 5px; border-left: 3px solid #0c5460;">
                    <strong style="color: #0c5460;">Confirmed</strong>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">Your appointment is confirmed and scheduled</p>
                </div>
                <div style="padding: 1rem; background: #fff3cd; border-radius: 5px; border-left: 3px solid #856404;">
                    <strong style="color: #856404;">In Progress</strong>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">You are currently being served</p>
                </div>
                <div style="padding: 1rem; background: #d4edda; border-radius: 5px; border-left: 3px solid #155724;">
                    <strong style="color: #155724;">Completed</strong>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">Service has been completed</p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
