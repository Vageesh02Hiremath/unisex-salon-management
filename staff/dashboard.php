<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';

requireStaff();

$user = getCurrentUser();
$user_id = $user['id'];
$staff = fetchOne("SELECT id FROM staff WHERE user_id = $user_id");
$staff_id = $staff['id'] ?? 0;

if ($staff_id) {
    $total_appointments = fetchOne("SELECT COUNT(*) as count FROM appointments WHERE staff_id = $staff_id")['count'];
    $completed_today = fetchOne("SELECT COUNT(*) as count FROM appointments WHERE staff_id = $staff_id AND status = 'completed' AND appointment_date = CURDATE()")['count'];
    $today_appointments = fetchOne("SELECT COUNT(*) as count FROM appointments WHERE staff_id = $staff_id AND appointment_date = CURDATE()")['count'];
    $recent_appointments = fetchAll("
        SELECT a.*, c.name as customer_name, s.name as service_name
        FROM appointments a
        JOIN customers c ON a.customer_id = c.id
        JOIN services s ON a.service_id = s.id
        WHERE a.staff_id = $staff_id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 5
    ");
} else {
    $total_appointments = 0;
    $completed_today = 0;
    $today_appointments = 0;
    $recent_appointments = [];
}

$page_title = 'Staff Dashboard';
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
            <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
            <p>Review assigned work and keep appointment progress updated.</p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card success">
                <div class="card-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="card-label">Total Appointments</div>
                <div class="card-value"><?php echo $total_appointments; ?></div>
                <div class="card-description">All assigned work</div>
            </div>
            <div class="dashboard-card info">
                <div class="card-icon"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="card-label">Today</div>
                <div class="card-value"><?php echo $today_appointments; ?></div>
                <div class="card-description">Scheduled today</div>
            </div>
            <div class="dashboard-card warning">
                <div class="card-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="card-label">Completed Today</div>
                <div class="card-value"><?php echo $completed_today; ?></div>
                <div class="card-description">Finished services</div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header flex-between">
                <h2>Recent Appointments</h2>
                <a href="<?php echo BASE_URL; ?>staff/assigned_appointments.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_appointments): ?>
                        <?php foreach ($recent_appointments as $appt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appt['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($appt['service_name']); ?></td>
                                <td><?php echo formatDate($appt['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></td>
                                <td><span class="badge <?php echo badgeClass($appt['status']); ?>"><?php echo statusLabel($appt['status']); ?></span></td>
                                <td><a href="<?php echo BASE_URL; ?>staff/update_status.php?id=<?php echo $appt['id']; ?>" class="btn btn-primary btn-sm">Update</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No appointments</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="action-grid">
            <a href="<?php echo BASE_URL; ?>staff/assigned_appointments.php" class="action-card">
                <i class="fa-solid fa-clipboard-list"></i>
                <div><h3>View Appointments</h3><p>See all assigned appointments.</p></div>
            </a>
            <a href="<?php echo BASE_URL; ?>staff/schedule.php" class="action-card">
                <i class="fa-solid fa-clock"></i>
                <div><h3>Daily Schedule</h3><p>Check availability and working hours.</p></div>
            </a>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
