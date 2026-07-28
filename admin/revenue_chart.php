<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';

requireAdmin();

// Get monthly revenue data
$monthly_revenue = fetchAll("
    SELECT 
        DATE_FORMAT(bill_date, '%Y-%m') as month,
        SUM(final_amount) as revenue,
        COUNT(*) as bills
    FROM bills
    WHERE YEAR(bill_date) = YEAR(CURDATE())
    GROUP BY DATE_FORMAT(bill_date, '%Y-%m')
    ORDER BY month ASC
");

// Get service revenue breakdown
$service_revenue = fetchAll("
    SELECT 
        s.name,
        COUNT(a.id) as bookings,
        SUM(s.price) as revenue
    FROM services s
    LEFT JOIN appointments a ON s.id = a.service_id AND a.status = 'completed'
    GROUP BY s.id, s.name
    ORDER BY revenue DESC
    LIMIT 10
");

// Prepare data for charts
$months = [];
$revenues = [];
foreach ($monthly_revenue as $data) {
    $months[] = date('M', strtotime($data['month'] . '-01'));
    $revenues[] = floatval($data['revenue']);
}

$service_names = [];
$service_revenues = [];
foreach ($service_revenue as $data) {
    $service_names[] = $data['name'];
    $service_revenues[] = floatval($data['revenue']);
}

$page_title = 'Revenue Charts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Salon Management</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Revenue Charts</h1>
            <p>Visual representation of salon revenue and performance</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
            <div class="form-container">
                <h3 style="margin-bottom: 1rem; color: #8B5FBF;">Monthly Revenue</h3>
                <canvas id="monthlyChart" style="max-height: 300px;"></canvas>
            </div>

            <div class="form-container">
                <h3 style="margin-bottom: 1rem; color: #8B5FBF;">Revenue by Service</h3>
                <canvas id="serviceChart" style="max-height: 300px;"></canvas>
            </div>
        </div>

        <div class="table-container" style="margin-top: 2rem;">
            <div class="table-header">
                <h2>Service Revenue Details</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Bookings</th>
                        <th>Total Revenue</th>
                        <th>Avg per Booking</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($service_revenue): ?>
                        <?php foreach ($service_revenue as $service): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                <td><?php echo $service['bookings']; ?></td>
                                <td><?php echo formatCurrency($service['revenue'] ?? 0); ?></td>
                                <td><?php echo formatCurrency(($service['revenue'] ?? 0) / max($service['bookings'], 1)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No revenue data</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Monthly Revenue Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Monthly Revenue',
                    data: <?php echo json_encode($revenues); ?>,
                    borderColor: '#8B5FBF',
                    backgroundColor: 'rgba(139, 95, 191, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#8B5FBF',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            color: '#333'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#999',
                            callback: function(value) {
                                return '$' + value.toFixed(0);
                            }
                        }
                    },
                    x: {
                        ticks: {
                            color: '#999'
                        }
                    }
                }
            }
        });

        // Service Revenue Chart
        const serviceCtx = document.getElementById('serviceChart').getContext('2d');
        new Chart(serviceCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($service_names); ?>,
                datasets: [{
                    data: <?php echo json_encode($service_revenues); ?>,
                    backgroundColor: [
                        '#8B5FBF',
                        '#FF6B9D',
                        '#27AE60',
                        '#F39C12',
                        '#3498DB',
                        '#E74C3C',
                        '#9B59B6',
                        '#1ABC9C',
                        '#34495E',
                        '#E67E22'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#333',
                            padding: 15
                        }
                    }
                }
            }
        });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
