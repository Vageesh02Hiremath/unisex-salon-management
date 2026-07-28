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

// Create bill for completed appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_bill') {
    $appointment_id = intval($_POST['appointment_id']);
    $result = generateBillForAppointment($appointment_id);
    $success = $result['success'] ? $result['message'] : '';
    $error = $result['success'] ? '' : $result['message'];
}

if (isset($_GET['payment_status'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['payment_status'] === 'paid' ? 'paid' : 'pending';
    $amountSql = $status === 'paid' ? 'amount = (SELECT final_amount FROM bills WHERE id = payments.bill_id),' : 'amount = 0,';
    executeQuery("UPDATE bills SET status = '$status' WHERE id = $id");
    executeQuery("UPDATE payments SET $amountSql status = '$status', payment_method = " . ($status === 'paid' ? "'manual'" : "'pending'") . ", payment_date = CURDATE() WHERE bill_id = $id");
    $success = 'Payment marked as ' . ucfirst($status) . '.';
}

// Delete bill
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $query = "DELETE FROM bills WHERE id = $id";
    if ($conn->query($query)) {
        $success = 'Bill deleted successfully!';
    } else {
        $error = 'Failed to delete bill';
    }
}

// Get all bills
$bills = fetchAll("
    SELECT b.*, c.name as customer_name, a.appointment_date
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    LEFT JOIN appointments a ON b.appointment_id = a.id
    ORDER BY b.created_at DESC
");

// Get completed appointments without bills
$completed_without_bills = fetchAll("
    SELECT a.*, c.name as customer_name, s.name as service_name, s.price
    FROM appointments a
    JOIN customers c ON a.customer_id = c.id
    JOIN services s ON a.service_id = s.id
    WHERE a.status = 'completed' AND a.id NOT IN (SELECT appointment_id FROM bills WHERE appointment_id IS NOT NULL)
    ORDER BY a.appointment_date DESC
");

$page_title = 'Manage Bills';
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
            <h1>Manage Bills</h1>
            <p>Create and manage customer bills</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($completed_without_bills)): ?>
            <div class="form-container">
                <h3 style="margin-bottom: 1.5rem; color: #8B5FBF;">Create Bill for Completed Appointments</h3>
                <p style="margin-bottom: 1rem; color: #666;">Select a completed appointment to generate a bill</p>
                
                <?php foreach ($completed_without_bills as $appt): ?>
                    <form method="POST" style="display: flex; gap: 1rem; align-items: end; margin-bottom: 1rem; padding: 1rem; background: #f9f9f9; border-radius: 5px;">
                        <input type="hidden" name="action" value="create_bill">
                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                        
                        <div style="flex: 1;">
                            <strong><?php echo htmlspecialchars($appt['customer_name']); ?></strong> - 
                            <?php echo htmlspecialchars($appt['service_name']); ?> 
                            (<?php echo formatDate($appt['appointment_date']); ?>)
                        </div>
                        
                        <div style="flex: 1;">
                            <label for="discount_<?php echo $appt['id']; ?>">Discount ($)</label>
                            <input type="number" id="discount_<?php echo $appt['id']; ?>" name="discount" step="0.01" min="0" value="0" style="width: 100%; padding: 0.5rem;">
                        </div>
                        
                        <div style="text-align: right;">
                            <strong>Amount: <?php echo formatCurrency($appt['price']); ?></strong>
                        </div>
                        
                        <button type="submit" class="btn btn-success">Create Bill</button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="table-container" style="margin-top: 2rem;">
            <div class="table-header">
                <h2>Bills List</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Bill Number</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Discount</th>
                        <th>Final Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($bills): ?>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($bill['bill_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($bill['customer_name']); ?></td>
                                <td><?php echo formatDate($bill['bill_date']); ?></td>
                                <td><?php echo formatCurrency($bill['total_amount']); ?></td>
                                <td><?php echo formatCurrency($bill['discount']); ?></td>
                                <td><?php echo formatCurrency($bill['final_amount']); ?></td>
                                <td><span class="badge <?php echo badgeClass($bill['status']); ?>"><?php echo statusLabel($bill['status']); ?></span></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>admin/view_bill.php?id=<?php echo $bill['id']; ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i></a>
                                    <a href="<?php echo BASE_URL; ?>admin/bills.php?id=<?php echo $bill['id']; ?>&payment_status=paid" class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i></a>
                                    <a href="<?php echo BASE_URL; ?>admin/bills.php?id=<?php echo $bill['id']; ?>&payment_status=pending" class="btn btn-secondary btn-sm"><i class="fa-solid fa-exclamation"></i></a>
                                    <a href="<?php echo BASE_URL; ?>admin/bills.php?delete_id=<?php echo $bill['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No bills found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
