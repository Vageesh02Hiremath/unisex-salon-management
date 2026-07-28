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

$staff = fetchOne("SELECT * FROM staff WHERE user_id = $user_id");

if (!$staff) {
    die('Staff record not found');
}

$page_title = 'My Schedule';
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
            <h1>My Schedule</h1>
            <p>View your work schedule</p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card success">
                <div class="card-icon"><i class="fa-solid fa-clock"></i></div>
                <div class="card-label">Work Start Time</div>
                <div class="card-value"><?php echo $staff['availability_start'] ? date('h:i A', strtotime($staff['availability_start'])) : 'Not Set'; ?></div>
                <div class="card-description">Daily work start time</div>
            </div>

            <div class="dashboard-card info">
                <div class="card-icon"><i class="fa-solid fa-business-time"></i></div>
                <div class="card-label">Work End Time</div>
                <div class="card-value"><?php echo $staff['availability_end'] ? date('h:i A', strtotime($staff['availability_end'])) : 'Not Set'; ?></div>
                <div class="card-description">Daily work end time</div>
            </div>

            <div class="dashboard-card warning">
                <div class="card-icon"><i class="fa-solid fa-briefcase"></i></div>
                <div class="card-label">Specialization</div>
                <div class="card-value" style="font-size: 1.2rem;"><?php echo htmlspecialchars($staff['specialization'] ?? 'General'); ?></div>
                <div class="card-description">Your specialty</div>
            </div>

            <div class="dashboard-card danger">
                <div class="card-icon"><i class="fa-solid fa-percent"></i></div>
                <div class="card-label">Commission</div>
                <div class="card-value"><?php echo $staff['commission_percentage']; ?>%</div>
                <div class="card-description">Per service commission</div>
            </div>
        </div>

        <div class="form-container">
            <h3><i class="fa-solid fa-list-check"></i> Schedule Details</h3>
            
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 0.8rem; border-bottom: 1px solid #eee;"><strong>Work Days</strong></td>
                    <td style="padding: 0.8rem; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($staff['days_working'] ?? 'Not specified'); ?></td>
                </tr>
                <tr>
                    <td style="padding: 0.8rem; border-bottom: 1px solid #eee;"><strong>Daily Shift</strong></td>
                    <td style="padding: 0.8rem; border-bottom: 1px solid #eee;">
                        <?php 
                        if ($staff['availability_start'] && $staff['availability_end']) {
                            echo date('h:i A', strtotime($staff['availability_start'])) . ' - ' . date('h:i A', strtotime($staff['availability_end']));
                        } else {
                            echo 'Not set';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.8rem; border-bottom: 1px solid #eee;"><strong>Specialization</strong></td>
                    <td style="padding: 0.8rem; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($staff['specialization'] ?? 'General'); ?></td>
                </tr>
                <tr>
                    <td style="padding: 0.8rem;"><strong>Commission Rate</strong></td>
                    <td style="padding: 0.8rem;"><?php echo $staff['commission_percentage']; ?>% per service</td>
                </tr>
            </table>
        </div>

        <div class="form-container">
            <h3><i class="fa-solid fa-calendar-day"></i> Today's Appointments</h3>
            
            <?php 
            $today_appointments = fetchAll("
                SELECT a.*, c.name as customer_name, s.name as service_name
                FROM appointments a
                JOIN customers c ON a.customer_id = c.id
                JOIN services s ON a.service_id = s.id
                WHERE a.staff_id = " . $staff['id'] . " AND a.appointment_date = CURDATE()
                ORDER BY a.appointment_time ASC
            ");
            ?>

            <?php if ($today_appointments): ?>
                <table style="width: 100%;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #8B5FBF;">
                            <th style="padding: 0.8rem; text-align: left;">Time</th>
                            <th style="padding: 0.8rem; text-align: left;">Customer</th>
                            <th style="padding: 0.8rem; text-align: left;">Service</th>
                            <th style="padding: 0.8rem; text-align: left;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($today_appointments as $appt): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 0.8rem;"><strong><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></strong></td>
                                <td style="padding: 0.8rem;"><?php echo htmlspecialchars($appt['customer_name']); ?></td>
                                <td style="padding: 0.8rem;"><?php echo htmlspecialchars($appt['service_name']); ?></td>
                                <td style="padding: 0.8rem;">
                                    <span class="badge badge-<?php echo $appt['status'] === 'completed' ? 'success' : ($appt['status'] === 'in-progress' ? 'warning' : 'primary'); ?>">
                                        <?php echo ucfirst($appt['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 2rem;">No appointments scheduled for today</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
