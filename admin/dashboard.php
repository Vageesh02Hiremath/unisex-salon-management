<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';

requireAdmin();

$total_customers = fetchOne("SELECT COUNT(*) as count FROM customers")['count'];
$total_staff = fetchOne("SELECT COUNT(*) as count FROM staff")['count'];
$total_services = fetchOne("SELECT COUNT(*) as count FROM services")['count'];
$today_appointments = fetchOne("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE()")['count'];

$current_month_revenue = fetchOne("
    SELECT COALESCE(SUM(final_amount), 0) as revenue
    FROM bills
    WHERE MONTH(bill_date) = MONTH(CURDATE()) AND YEAR(bill_date) = YEAR(CURDATE())
")['revenue'];

$recent_appointments = fetchAll("
    SELECT a.*, c.name as customer_name, s.name as service_name, u.name as staff_name
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    LEFT JOIN users u ON a.staff_id IS NOT NULL AND u.id = (SELECT user_id FROM staff WHERE id = a.staff_id)
    ORDER BY a.created_at DESC
    LIMIT 5
");

$page_title = 'Admin Dashboard';
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
            <h1>Admin Dashboard</h1>
            <p>Monitor salon operations, bookings, revenue, and team activity.</p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card success">
                <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                <div class="card-label">Total Customers</div>
                <div class="card-value"><?php echo $total_customers; ?></div>
                <div class="card-description">Registered customers</div>
            </div>
            <div class="dashboard-card info">
                <div class="card-icon"><i class="fa-solid fa-user-tie"></i></div>
                <div class="card-label">Total Staff</div>
                <div class="card-value"><?php echo $total_staff; ?></div>
                <div class="card-description">Active staff members</div>
            </div>
            <div class="dashboard-card warning">
                <div class="card-icon"><i class="fa-solid fa-scissors"></i></div>
                <div class="card-label">Total Services</div>
                <div class="card-value"><?php echo $total_services; ?></div>
                <div class="card-description">Available services</div>
            </div>
            <div class="dashboard-card danger">
                <div class="card-icon"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="card-label">Today</div>
                <div class="card-value"><?php echo $today_appointments; ?></div>
                <div class="card-description">Appointments today</div>
            </div>
            <div class="dashboard-card">
                <div class="card-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                <div class="card-label">Monthly Revenue</div>
                <div class="card-value"><?php echo formatCurrency($current_month_revenue); ?></div>
                <div class="card-description">This month</div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header flex-between">
                <h2>Recent Appointments</h2>
                <a href="<?php echo BASE_URL; ?>admin/appointments.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Staff</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_appointments): ?>
                        <?php foreach ($recent_appointments as $appt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appt['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($appt['service_name']); ?></td>
                                <td><?php echo $appt['staff_name'] ? htmlspecialchars($appt['staff_name']) : '-'; ?></td>
                                <td><?php echo formatDate($appt['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></td>
                                <td><span class="badge <?php echo badgeClass($appt['status']); ?>"><?php echo statusLabel($appt['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No appointments yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="action-grid">
            <a href="<?php echo BASE_URL; ?>admin/customers.php" class="action-card">
                <i class="fa-solid fa-users"></i>
                <div><h3>Manage Customers</h3><p>View profiles and customer history.</p></div>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/staff.php" class="action-card">
                <i class="fa-solid fa-user-tie"></i>
                <div><h3>Manage Staff</h3><p>Add staff and maintain schedules.</p></div>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/services.php" class="action-card">
                <i class="fa-solid fa-scissors"></i>
                <div><h3>Manage Services</h3><p>Update services, pricing, and duration.</p></div>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/appointments.php" class="action-card">
                <i class="fa-solid fa-calendar-check"></i>
                <div><h3>Appointments</h3><p>Approve, assign, and complete bookings.</p></div>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/bills.php" class="action-card">
                <i class="fa-solid fa-file-invoice"></i>
                <div><h3>Billing</h3><p>Review generated bills and payments.</p></div>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/reports.php" class="action-card">
                <i class="fa-solid fa-chart-line"></i>
                <div><h3>Reports</h3><p>Analyze revenue and appointment trends.</p></div>
            </a>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
