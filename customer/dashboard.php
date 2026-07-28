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

$total_appointments = fetchOne("SELECT COUNT(*) as count FROM appointments WHERE customer_id = $customer_id")['count'];
$completed_appointments = fetchOne("SELECT COUNT(*) as count FROM appointments WHERE customer_id = $customer_id AND status = 'completed'")['count'];
$pending_bills = fetchOne("SELECT COUNT(*) as count FROM bills WHERE customer_id = $customer_id")['count'];
$total_spent = fetchOne("SELECT COALESCE(SUM(final_amount), 0) as total FROM bills WHERE customer_id = $customer_id")['total'];

$recent_appointments = fetchAll("
    SELECT a.*, s.name as service_name, s.price
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.customer_id = $customer_id
    ORDER BY a.appointment_date DESC
    LIMIT 5
");

$page_title = 'Customer Dashboard';
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
            <h1>Welcome, <?php echo htmlspecialchars($customer['name']); ?></h1>
            <p>Track your bookings, bills, and salon activity from one place.</p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card success">
                <div class="card-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="card-label">Total Appointments</div>
                <div class="card-value"><?php echo $total_appointments; ?></div>
                <div class="card-description">Bookings created</div>
            </div>
            <div class="dashboard-card info">
                <div class="card-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="card-label">Completed</div>
                <div class="card-value"><?php echo $completed_appointments; ?></div>
                <div class="card-description">Finished services</div>
            </div>
            <div class="dashboard-card warning">
                <div class="card-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                <div class="card-label">Total Spent</div>
                <div class="card-value"><?php echo formatCurrency($total_spent); ?></div>
                <div class="card-description">Lifetime billing</div>
            </div>
            <div class="dashboard-card danger">
                <div class="card-icon"><i class="fa-solid fa-file-invoice"></i></div>
                <div class="card-label">Pending Bills</div>
                <div class="card-value"><?php echo $pending_bills; ?></div>
                <div class="card-description">Awaiting payment</div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header flex-between">
                <h2>Recent Appointments</h2>
                <a href="<?php echo BASE_URL; ?>customer/my_appointments.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_appointments): ?>
                        <?php foreach ($recent_appointments as $appt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appt['service_name']); ?></td>
                                <td><?php echo formatDate($appt['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></td>
                                <td><?php echo formatCurrency($appt['price']); ?></td>
                                <td><span class="badge <?php echo badgeClass($appt['status']); ?>"><?php echo statusLabel($appt['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No appointments yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="action-grid">
            <a href="<?php echo BASE_URL; ?>customer/book_appointment.php" class="action-card">
                <i class="fa-solid fa-calendar-plus"></i>
                <div><h3>Book Appointment</h3><p>Schedule a new salon visit.</p></div>
            </a>
            <a href="<?php echo BASE_URL; ?>customer/services.php" class="action-card">
                <i class="fa-solid fa-scissors"></i>
                <div><h3>View Services</h3><p>Browse services and pricing.</p></div>
            </a>
            <a href="<?php echo BASE_URL; ?>customer/my_bills.php" class="action-card">
                <i class="fa-solid fa-receipt"></i>
                <div><h3>My Bills</h3><p>Review bills and payment status.</p></div>
            </a>
            <a href="<?php echo BASE_URL; ?>customer/feedback.php" class="action-card">
                <i class="fa-solid fa-star"></i>
                <div><h3>Send Feedback</h3><p>Share your service experience.</p></div>
            </a>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
