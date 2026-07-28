<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';

requireAdmin();

// Get all appointments for calendar
$appointments = fetchAll("
    SELECT a.*, c.name as customer_name, s.name as service_name
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    WHERE a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    AND a.appointment_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
    ORDER BY a.appointment_date, a.appointment_time
");

$page_title = 'Calendar View';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Salon Management</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .calendar-day {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0.5rem;
            min-height: 100px;
            background: white;
        }

        .calendar-day.other-month {
            background-color: #f5f5f5;
            color: #999;
        }

        .calendar-day.today {
            background-color: #d4edda;
            border-color: #27AE60;
        }

        .calendar-date {
            font-weight: 600;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .calendar-appointment {
            font-size: 0.8rem;
            padding: 0.2rem;
            margin: 0.2rem 0;
            background: #e8f4f8;
            border-radius: 3px;
            color: #0c5460;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .calendar-month-label {
            font-size: 1.5rem;
            font-weight: 600;
            color: #8B5FBF;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Appointment Calendar</h1>
            <p>View all appointments in calendar format</p>
        </div>

        <div class="form-container">
            <h3 style="margin-bottom: 1rem; color: #8B5FBF;">Legend</h3>
            <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="badge badge-info">Pending</span>
                    <span>Not yet confirmed</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="badge" style="background: #d1ecf1; color: #0c5460;">Confirmed</span>
                    <span>Confirmed appointment</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="badge" style="background: #fff3cd; color: #856404;">In Progress</span>
                    <span>Currently being served</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="badge badge-success">Completed</span>
                    <span>Service completed</span>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h3 style="color: #8B5FBF; margin-bottom: 1rem;">Upcoming Appointments</h3>
                <div style="max-height: 500px; overflow-y: auto;">
                    <?php 
                    $upcoming = array_filter($appointments, function($a) {
                        return strtotime($a['appointment_date']) >= strtotime(date('Y-m-d'));
                    });
                    ?>
                    <?php if ($upcoming): ?>
                        <?php foreach ($upcoming as $appt): ?>
                            <div style="background: white; padding: 1rem; margin-bottom: 0.5rem; border-radius: 5px; border-left: 3px solid #8B5FBF;">
                                <strong><?php echo htmlspecialchars($appt['customer_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($appt['service_name']); ?></small><br>
                                <small style="color: #999;">
                                    <?php echo formatDate($appt['appointment_date']); ?> 
                                    @ <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?>
                                </small><br>
                                <span class="badge" style="margin-top: 0.5rem;">
                                    <?php 
                                    $status = $appt['status'];
                                    $badge_class = 'badge-primary';
                                    if ($status === 'confirmed') $badge_class = 'badge-info';
                                    elseif ($status === 'in-progress') echo 'style="background: #fff3cd; color: #856404;"';
                                    elseif ($status === 'completed') $badge_class = 'badge-success';
                                    elseif ($status === 'cancelled') $badge_class = 'badge-danger';
                                    ?>
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #999; text-align: center; padding: 2rem;">No upcoming appointments</p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <h3 style="color: #8B5FBF; margin-bottom: 1rem;">Quick Stats</h3>
                <div style="background: white; padding: 1.5rem; border-radius: 5px; margin-bottom: 1rem;">
                    <p><strong>Total Appointments:</strong> <?php echo count($appointments); ?></p>
                    <p><strong>Upcoming:</strong> <?php echo count(array_filter($appointments, function($a) { return strtotime($a['appointment_date']) >= strtotime(date('Y-m-d')); })); ?></p>
                    <p><strong>Today's Appointments:</strong> 
                        <?php 
                        $today_count = count(array_filter($appointments, function($a) { 
                            return $a['appointment_date'] === date('Y-m-d'); 
                        }));
                        echo $today_count;
                        ?>
                    </p>
                    <p><strong>Pending:</strong> 
                        <?php 
                        echo count(array_filter($appointments, function($a) { 
                            return $a['status'] === 'pending'; 
                        }));
                        ?>
                    </p>
                    <p><strong>Completed:</strong> 
                        <?php 
                        echo count(array_filter($appointments, function($a) { 
                            return $a['status'] === 'completed'; 
                        }));
                        ?>
                    </p>
                </div>

                <a href="<?php echo BASE_URL; ?>admin/appointments.php" class="btn btn-primary btn-block">View All Appointments</a>
            </div>
        </div>

        <div class="table-container" style="margin-top: 2rem;">
            <div class="table-header">
                <h2>All Appointments Timeline</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($appointments): ?>
                        <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td><?php echo formatDate($appt['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars($appt['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($appt['service_name']); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        $status = $appt['status'];
                                        if ($status === 'pending') echo 'badge-primary';
                                        elseif ($status === 'confirmed') echo 'badge-info';
                                        elseif ($status === 'in-progress') echo 'badge-warning';
                                        elseif ($status === 'completed') echo 'badge-success';
                                        elseif ($status === 'cancelled') echo 'badge-danger';
                                    ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No appointments</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
