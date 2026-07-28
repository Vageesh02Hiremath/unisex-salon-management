<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';

requireAdmin();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Sanitize dates
$start_date = sanitize($start_date);
$end_date = sanitize($end_date);

// Get revenue for period
$revenue_data = fetchOne("
    SELECT 
        COUNT(DISTINCT b.id) as total_bills,
        SUM(b.final_amount) as total_revenue,
        COUNT(DISTINCT b.customer_id) as unique_customers
    FROM bills b
    WHERE b.bill_date BETWEEN '$start_date' AND '$end_date'
");

// Get appointment statistics
$appointment_data = fetchOne("
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM appointments
    WHERE appointment_date BETWEEN '$start_date' AND '$end_date'
");

// Service popularity
$service_stats = fetchAll("
    SELECT 
        s.name,
        COUNT(a.id) as bookings,
        SUM(s.price) as revenue
    FROM services s
    LEFT JOIN appointments a ON s.id = a.service_id AND a.appointment_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY s.id, s.name
    ORDER BY bookings DESC
    LIMIT 10
");

// Daily revenue
$daily_revenue = fetchAll("
    SELECT 
        b.bill_date,
        COUNT(*) as bills,
        SUM(b.final_amount) as revenue
    FROM bills b
    WHERE b.bill_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY b.bill_date
    ORDER BY b.bill_date ASC
");

$page_title = 'Reports & Analytics';
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
            <h1>Reports & Analytics</h1>
            <p>View detailed reports and analytics</p>
        </div>

        <div class="form-container">
            <h3>Filter by Date Range</h3>
            <form method="GET" class="form-actions">
                <div>
                    <label for="start_date">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div>
                    <label for="end_date">End Date:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo BASE_URL; ?>admin/reports.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card success">
                <div class="card-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                <div class="card-label">Total Revenue</div>
                <div class="card-value"><?php echo formatCurrency($revenue_data['total_revenue'] ?? 0); ?></div>
                <div class="card-description">Period: <?php echo formatDate($start_date); ?> to <?php echo formatDate($end_date); ?></div>
            </div>

            <div class="dashboard-card info">
                <div class="card-icon"><i class="fa-solid fa-file-invoice"></i></div>
                <div class="card-label">Total Bills</div>
                <div class="card-value"><?php echo $revenue_data['total_bills'] ?? 0; ?></div>
                <div class="card-description">Bills generated</div>
            </div>

            <div class="dashboard-card warning">
                <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                <div class="card-label">Unique Customers</div>
                <div class="card-value"><?php echo $revenue_data['unique_customers'] ?? 0; ?></div>
                <div class="card-description">Active customers</div>
            </div>

            <div class="dashboard-card danger">
                <div class="card-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="card-label">Total Appointments</div>
                <div class="card-value"><?php echo $appointment_data['total_appointments'] ?? 0; ?></div>
                <div class="card-description">Scheduled appointments</div>
            </div>
        </div>

        <div class="action-grid">
            <div class="table-container">
                <div class="table-header">
                    <h3>Appointment Status</h3>
                </div>
                <table>
                    <tr>
                        <td><strong>Completed:</strong></td>
                        <td style="text-align: right;"><?php echo $appointment_data['completed'] ?? 0; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Pending:</strong></td>
                        <td style="text-align: right;"><?php echo $appointment_data['pending'] ?? 0; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Cancelled:</strong></td>
                        <td style="text-align: right;"><?php echo $appointment_data['cancelled'] ?? 0; ?></td>
                    </tr>
                </table>
            </div>

            <div class="table-container">
                <div class="table-header">
                    <h3>Top Services</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Bookings</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($service_stats): ?>
                            <?php foreach ($service_stats as $service): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($service['name']); ?></td>
                                    <td><?php echo $service['bookings'] ?? 0; ?></td>
                                    <td><?php echo formatCurrency($service['revenue'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No data</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3>Daily Revenue</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Bills</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($daily_revenue): ?>
                        <?php foreach ($daily_revenue as $day): ?>
                            <tr>
                                <td><?php echo formatDate($day['bill_date']); ?></td>
                                <td><?php echo $day['bills']; ?></td>
                                <td><?php echo formatCurrency($day['revenue']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">No data</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
