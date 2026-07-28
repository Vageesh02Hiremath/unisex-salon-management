<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';

requireAdmin();

$error = '';
$success = '';

// Record payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    $bill_id = intval($_POST['bill_id']);
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_method = sanitize($_POST['payment_method']);
    $allowed_methods = ['cash', 'card', 'check', 'online_transfer', 'razorpay'];
    $notes = sanitize($_POST['notes'] ?? '');
    
    $bill = fetchOne("SELECT * FROM bills WHERE id = $bill_id");
    if ($bill && $amount > 0 && in_array($payment_method, $allowed_methods, true)) {
        $payment_query = "INSERT INTO payments (bill_id, customer_id, amount, payment_method, payment_date, status, notes) 
                         VALUES ($bill_id, " . $bill['customer_id'] . ", $amount, '$payment_method', CURDATE(), 'completed', '$notes')";
        
        if ($conn->query($payment_query)) {
            $success = 'Payment recorded successfully!';
            $_POST = [];
        } else {
            $error = 'Failed to record payment';
        }
    } else {
        $error = 'Invalid bill, amount, or payment method';
    }
}

// Get all payments
$payments = fetchAll("
    SELECT p.*, b.bill_number, c.name as customer_name
    FROM payments p
    JOIN bills b ON p.bill_id = b.id
    JOIN customers c ON p.customer_id = c.id
    ORDER BY p.created_at DESC
");

// Get bills with pending payments
$pending_bills = fetchAll("
    SELECT b.* , c.name as customer_name
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    WHERE b.id NOT IN (SELECT DISTINCT bill_id FROM payments WHERE status = 'completed')
    ORDER BY b.bill_date DESC
");

$page_title = 'Manage Payments';
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
            <h1>Manage Payments</h1>
            <p>Record and manage customer payments</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($pending_bills)): ?>
            <div class="form-container">
                <h3 style="margin-bottom: 1.5rem; color: #8B5FBF;">Record Payment</h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="record_payment">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <div class="form-group">
                            <label for="bill_id">Select Bill *</label>
                            <select id="bill_id" name="bill_id" required onchange="updateAmount()">
                                <option value="">Select a bill</option>
                                <?php foreach ($pending_bills as $bill): ?>
                                    <option value="<?php echo $bill['id']; ?>" data-amount="<?php echo $bill['final_amount']; ?>">
                                        <?php echo htmlspecialchars($bill['bill_number']); ?> - 
                                        <?php echo htmlspecialchars($bill['customer_name']); ?> - 
                                        <?php echo formatCurrency($bill['final_amount']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="amount">Amount Paid (INR) *</label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0" required placeholder="0.00">
                        </div>

                        <div class="form-group">
                            <label for="payment_method">Payment Method *</label>
                            <select id="payment_method" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="check">Check</option>
                                <option value="online_transfer">Online Transfer</option>
                                <option value="razorpay">Razorpay</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" placeholder="Additional payment notes"></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">Record Payment</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="table-container" style="margin-top: 2rem;">
            <div class="table-header">
                <h2>Payment Records</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Bill Number</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($payments): ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['bill_number']); ?></td>
                                <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                                <td><?php echo formatCurrency($payment['amount']); ?></td>
                                <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $payment['payment_method']))); ?></td>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <td>
                                    <span class="badge badge-success"><?php echo ucfirst($payment['status']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No payments recorded</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function updateAmount() {
            const billSelect = document.getElementById('bill_id');
            const selectedOption = billSelect.options[billSelect.selectedIndex];
            const amount = selectedOption.getAttribute('data-amount');
            document.getElementById('amount').value = amount || '';
        }
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
