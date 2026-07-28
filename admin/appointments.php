<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/generate_bill.php';

requireAdmin();

$error = '';
$success = '';

// Update appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $appointment_id = intval($_POST['appointment_id']);
    $status = sanitize($_POST['status']);
    $staff_id = isset($_POST['staff_id']) && !empty($_POST['staff_id']) ? intval($_POST['staff_id']) : null;
    
    $query = "UPDATE appointments SET status = '$status'";
    if ($staff_id) {
        $query .= ", staff_id = $staff_id";
    }
    $query .= " WHERE id = $appointment_id";
    
    if ($conn->query($query)) {
        $success = 'Appointment updated successfully!';
        if ($status === 'completed') {
            $bill = generateBillForAppointment($appointment_id);
            $success .= $bill['success'] ? ' Bill generated automatically.' : ' Bill generation skipped: ' . $bill['message'];
        }
    } else {
        $error = 'Failed to update appointment';
    }
}

// Cancel appointment
if (isset($_GET['cancel_id'])) {
    $id = intval($_GET['cancel_id']);
    $query = "UPDATE appointments SET status = 'cancelled' WHERE id = $id";
    if ($conn->query($query)) {
        $success = 'Appointment cancelled successfully!';
    } else {
        $error = 'Failed to cancel appointment';
    }
}

// Get all appointments
$filter_status = $_GET['status'] ?? '';
$filter_date = $_GET['date'] ?? '';
$filter_service = (int)($_GET['service_id'] ?? 0);
$search = sanitize($_GET['search'] ?? '');
$query = "
    SELECT a.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
           s.name as service_name, s.price as service_price, s.duration,
           u.name as staff_name
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    LEFT JOIN users u ON a.staff_id IS NOT NULL AND u.id = (SELECT user_id FROM staff WHERE id = a.staff_id)
";

$where = [];
if ($filter_status) {
    $filter_status = sanitize($filter_status);
    $where[] = "a.status = '$filter_status'";
}
if ($filter_date) {
    $filter_date = sanitize($filter_date);
    $where[] = "a.appointment_date = '$filter_date'";
}
if ($filter_service > 0) {
    $where[] = "a.service_id = $filter_service";
}
if ($search) {
    $where[] = "(c.name LIKE '%$search%' OR c.phone LIKE '%$search%' OR c.email LIKE '%$search%' OR a.notes LIKE '%$search%')";
}
if ($where) {
    $query .= ' WHERE ' . implode(' AND ', $where);
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$appointments = fetchAll($query);

$staff_members = fetchAll("SELECT s.id, u.name FROM staff s JOIN users u ON s.user_id = u.id WHERE u.status = 'active'");
$services = fetchAll("SELECT id, name FROM services WHERE status = 'active' ORDER BY name");

$page_title = 'Manage Appointments';
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
            <h1>Manage Appointments</h1>
            <p>View and manage all salon appointments</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <div class="table-header flex-between">
                <h2>Appointments</h2>
                <form method="GET" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <input type="search" name="search" placeholder="Name, phone, email, booking ID" value="<?php echo htmlspecialchars($search); ?>" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                    <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                    <select name="service_id" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">All Services</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo (int)$service['id']; ?>" <?php echo $filter_service === (int)$service['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($service['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" onchange="this.form.submit()" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="confirmed" <?php echo $filter_status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="in-progress" <?php echo $filter_status === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="<?php echo BASE_URL; ?>admin/appointments.php" class="btn btn-secondary btn-sm">Reset</a>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Date & Time</th>
                        <th>Staff</th>
                        <th>Price</th>
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
                                <td>
                                    <?php echo formatDate($appt['appointment_date']); ?>
                                    <br>
                                    <small><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></small>
                                </td>
                                <td><?php echo $appt['staff_name'] ? htmlspecialchars($appt['staff_name']) : '-'; ?></td>
                                <td><?php echo formatCurrency($appt['service_price']); ?></td>
                                <td>
                                    <?php 
                                    $status = $appt['status'];
                                    $badge_class = 'badge-primary';
                                    if ($status === 'approved' || $status === 'confirmed') $badge_class = 'badge-info';
                                    elseif ($status === 'rejected') $badge_class = 'badge-danger';
                                    elseif ($status === 'in-progress') $badge_class = 'badge-warning';
                                    elseif ($status === 'completed') $badge_class = 'badge-success';
                                    elseif ($status === 'cancelled') $badge_class = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                        <select name="status" style="padding: 0.5rem; font-size: 0.9rem;" onchange="this.form.submit()">
                                            <option value="">Change Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                            <option value="confirmed">Confirmed</option>
                                            <option value="in-progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </form>
                                    <a href="<?php echo BASE_URL; ?>admin/appointments.php?cancel_id=<?php echo $appt['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this appointment?')">Cancel</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No appointments found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
