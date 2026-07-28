<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';

requireAdmin();

// Get all feedback
$feedback_list = fetchAll("
    SELECT f.*, c.name as customer_name, u.name as staff_name, s.name as service_name
    FROM feedback f
    JOIN customers c ON f.customer_id = c.id
    LEFT JOIN users u ON f.staff_id IS NOT NULL AND u.id = (SELECT user_id FROM staff WHERE id = f.staff_id)
    LEFT JOIN services s ON f.service_id = s.id
    ORDER BY f.feedback_date DESC
");

// Get statistics
$avg_rating = fetchOne("SELECT ROUND(AVG(rating), 1) as avg FROM feedback")['avg'] ?? 0;
$total_feedback = count($feedback_list);

$page_title = 'Customer Feedback';
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
            <h1>Customer Feedback</h1>
            <p>View and manage customer feedback and ratings</p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card success">
                <div class="card-icon"><i class="fa-solid fa-star"></i></div>
                <div class="card-label">Average Rating</div>
                <div class="card-value"><?php echo $avg_rating; ?>/5</div>
                <div class="card-description">Customer satisfaction</div>
            </div>

            <div class="dashboard-card info">
                <div class="card-icon"><i class="fa-solid fa-comments"></i></div>
                <div class="card-label">Total Feedback</div>
                <div class="card-value"><?php echo $total_feedback; ?></div>
                <div class="card-description">Feedback received</div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2>Feedback List</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Staff</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($feedback_list): ?>
                        <?php foreach ($feedback_list as $feedback): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($feedback['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($feedback['service_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($feedback['staff_name'] ?? '-'); ?></td>
                                <td>
                                    <span style="color: #F39C12; font-size: 1.1rem;">
                                        <?php echo str_repeat('*', intval($feedback['rating'])); ?>
                                    </span>
                                    (<?php echo $feedback['rating']; ?>/5)
                                </td>
                                <td><?php echo htmlspecialchars(substr($feedback['comment'], 0, 50)); ?>...</td>
                                <td><?php echo formatDate($feedback['feedback_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No feedback yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
