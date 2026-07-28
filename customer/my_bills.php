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

// Get bills
$bills = fetchAll("
    SELECT b.*, 
           COALESCE(SUM(p.amount), 0) as paid_amount,
           (b.final_amount - COALESCE(SUM(p.amount), 0)) as pending_amount
    FROM bills b
    LEFT JOIN payments p ON b.id = p.bill_id AND p.status IN ('completed','paid')
    WHERE b.customer_id = $customer_id
    GROUP BY b.id
    ORDER BY b.bill_date DESC
");

// Get statistics
$total_bills = count($bills);
$total_amount = 0;
$total_paid = 0;
foreach ($bills as $bill) {
    $total_amount += $bill['final_amount'];
    $total_paid += $bill['paid_amount'];
}
$total_pending = $total_amount - $total_paid;

$page_title = 'My Bills';
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
            <h1>My Bills</h1>
            <p>View and track your bill payments</p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card success">
                <div class="card-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                <div class="card-label">Total Billed</div>
                <div class="card-value"><?php echo formatCurrency($total_amount); ?></div>
                <div class="card-description"><?php echo $total_bills; ?> bills</div>
            </div>

            <div class="dashboard-card info">
                <div class="card-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="card-label">Total Paid</div>
                <div class="card-value"><?php echo formatCurrency($total_paid); ?></div>
                <div class="card-description">Completed payments</div>
            </div>

            <div class="dashboard-card warning">
                <div class="card-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="card-label">Pending Amount</div>
                <div class="card-value"><?php echo formatCurrency($total_pending); ?></div>
                <div class="card-description">To be paid</div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2>Bills Details</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Bill Number</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Paid</th>
                        <th>Pending</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($bills): ?>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($bill['bill_number']); ?></strong></td>
                                <td><?php echo formatDate($bill['bill_date']); ?></td>
                                <td><?php echo formatCurrency($bill['final_amount']); ?></td>
                                <td style="color: #27AE60;"><?php echo formatCurrency($bill['paid_amount']); ?></td>
                                <td style="color: #E74C3C;"><?php echo formatCurrency($bill['pending_amount']); ?></td>
                                <td>
                                    <?php if ($bill['pending_amount'] <= 0): ?>
                                        <span class="badge badge-success"><i class="fa-solid fa-check"></i> Paid</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><i class="fa-solid fa-exclamation"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>customer/view_bill.php?id=<?php echo (int)$bill['id']; ?>"><i class="fa-solid fa-eye"></i> View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No bills yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pending > 0): ?>
            <div class="form-container">
                <h3><i class="fa-solid fa-triangle-exclamation"></i> Payment Notice</h3>
                <p>You have pending bills totaling <strong><?php echo formatCurrency($total_pending); ?></strong></p>
                <p style="margin-top: 1rem; color: #666;">Please contact the salon or visit us to complete your payment. We accept cash, card, checks, and online transfers.</p>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h3><i class="fa-solid fa-circle-info"></i> Payment Information</h3>
            <p>Bills are generated after your appointment is completed. You can pay at the salon counter or contact us to arrange payment.</p>
            <p style="margin-top: 1rem; color: #666;"><strong>Payment Methods Accepted:</strong> Cash, Card, Check, Online Transfer</p>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
