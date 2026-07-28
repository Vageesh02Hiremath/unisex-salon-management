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

// Get staff ID
$staff = fetchOne("SELECT id FROM staff WHERE user_id = $user_id");
$staff_id = $staff['id'] ?? 0;

if (!$staff_id) {
    die('Staff record not found');
}

// Get filter
$date_filter = $_GET['date'] ?? '';

$query = "
    SELECT a.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
           s.name as service_name, s.price, s.duration
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    WHERE a.staff_id = $staff_id
";

if ($date_filter) {
    $date_filter = sanitize($date_filter);
    $query .= " AND a.appointment_date = '$date_filter'";
} else {
    $query .= " AND a.appointment_date >= CURDATE()";
}

$query .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
$appointments = fetchAll($query);

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
            <h1>My Assigned Appointments</h1>
            <p>View and manage your scheduled appointments</p>
        </div>

        <div class="form-container" style="margin-bottom: 2rem;">
            <form method="GET" style="display: flex; gap: 1rem; align-items: end;">
                <div style="flex: 1;">
                    <label for="date">Filter by Date</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if ($date_filter): ?>
                    <a href="<?php echo BASE_URL; ?>staff/assigned_appointments.php" class="btn btn-secondary">Clear Filter</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2>Appointments</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($appointments): ?>
                        <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td>
                                    <div><?php echo htmlspecialchars($appt['customer_name']); ?></div>
                                    <small style="color: #999;"><?php echo htmlspecialchars($appt['customer_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($appt['service_name']); ?></td>
                                <td><?php echo formatDate($appt['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></td>
                                <td><?php echo $appt['duration']; ?> min</td>
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
                                    <a href="<?php echo BASE_URL; ?>staff/update_status.php?id=<?php echo $appt['id']; ?>" class="btn btn-primary btn-sm">Update Status</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No appointments</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
