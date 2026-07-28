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
$error = '';
$success = '';

// Submit feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_feedback') {
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = sanitize($_POST['comment'] ?? '');
    
    if (!$appointment_id || $rating < 1 || $rating > 5) {
        $error = 'Please select an appointment and rating';
    } else {
        // Get appointment details
        $appt = fetchOne("
            SELECT a.*, s.id as service_id FROM appointments a
            JOIN services s ON a.service_id = s.id
            WHERE a.id = $appointment_id AND a.customer_id = $customer_id AND a.status = 'completed'
        ");
        
        if (!$appt) {
            $error = 'Invalid appointment selected';
        } else {
            $query = "INSERT INTO feedback (appointment_id, customer_id, service_id, rating, comment, feedback_date, status) 
                     VALUES ($appointment_id, $customer_id, " . $appt['service_id'] . ", $rating, '$comment', CURDATE(), 'new')";
            
            if ($conn->query($query)) {
                $success = 'Thank you! Your feedback has been submitted.';
                $_POST = [];
            } else {
                $error = 'Failed to submit feedback. Please try again.';
            }
        }
    }
}

// Get completed appointments without feedback
$completed_appointments = fetchAll("
    SELECT a.*, s.name as service_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.customer_id = $customer_id 
    AND a.status = 'completed'
    AND a.id NOT IN (SELECT appointment_id FROM feedback WHERE customer_id = $customer_id AND appointment_id IS NOT NULL)
    ORDER BY a.appointment_date DESC
");

// Get feedback history
$feedback_history = fetchAll("
    SELECT f.*, s.name as service_name
    FROM feedback f
    LEFT JOIN services s ON f.service_id = s.id
    WHERE f.customer_id = $customer_id
    ORDER BY f.feedback_date DESC
");

$page_title = 'Send Feedback';
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
            <h1>Share Your Feedback</h1>
            <p>Help us improve by rating your experience</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($completed_appointments)): ?>
            <div class="form-container">
                <h3 style="margin-bottom: 1.5rem; color: #8B5FBF;">Submit Feedback</h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="submit_feedback">
                    
                    <div class="form-group">
                        <label for="appointment_id">Select Appointment *</label>
                        <select id="appointment_id" name="appointment_id" required>
                            <option value="">Choose an appointment</option>
                            <?php foreach ($completed_appointments as $appt): ?>
                                <option value="<?php echo $appt['id']; ?>">
                                    <?php echo htmlspecialchars($appt['service_name']); ?> - 
                                    <?php echo formatDate($appt['appointment_date']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="rating">Rating *</label>
                        <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" id="rating_<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" style="display: none;" onchange="updateStarDisplay()">
                                <label for="rating_<?php echo $i; ?>" style="cursor: pointer; font-size: 1.8rem; margin: 0; color: #ddd; transition: color 0.2s ease;">
                                    <i class="fa-solid fa-star"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comment">Your Comment *</label>
                        <textarea id="comment" name="comment" required placeholder="Tell us about your experience..." style="min-height: 150px;"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Feedback</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="table-container" style="margin-top: 2rem;">
            <div class="table-header">
                <h2>Your Feedback History</h2>
            </div>

            <?php if ($feedback_history): ?>
                <div style="padding: 1rem;">
                    <?php foreach ($feedback_history as $feedback): ?>
                        <div style="background: #f9f9f9; padding: 1.5rem; border-radius: 5px; margin-bottom: 1rem; border-left: 3px solid #F39C12;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                <strong><?php echo htmlspecialchars($feedback['service_name'] ?? 'Service'); ?></strong>
                                <span style="color: #F39C12; font-size: 1.1rem;">
                                    <?php echo str_repeat('*', intval($feedback['rating'])); ?> (<?php echo $feedback['rating']; ?>/5)
                                </span>
                            </div>
                            <small style="color: #999; display: block; margin-bottom: 0.8rem;">
                                <?php echo formatDate($feedback['feedback_date']); ?>
                            </small>
                            <p style="color: #333; margin: 0;"><?php echo htmlspecialchars($feedback['comment']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="padding: 2rem; text-align: center; color: #999;">
                    <p>No feedback submitted yet.</p>
                    <?php if (empty($completed_appointments)): ?>
                        <p>Complete an appointment to submit feedback.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateStarDisplay() {
            const selectedRating = document.querySelector('input[name="rating"]:checked');
            const rating = selectedRating ? parseInt(selectedRating.value) : 0;
            
            document.querySelectorAll('label[for^="rating_"]').forEach((label, index) => {
                if (index < rating) {
                    label.style.color = '#F39C12';
                } else {
                    label.style.color = '#ddd';
                }
            });
        }

        // Update display on page load
        document.addEventListener('DOMContentLoaded', updateStarDisplay);
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
