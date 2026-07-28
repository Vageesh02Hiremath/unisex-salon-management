<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';
requireCustomer();

$customer = getCurrentCustomer();
$bill_id = (int)($_GET['id'] ?? 0);
$customer_id = (int)$customer['id'];
$bill = fetchOne("
    SELECT b.*, c.name AS customer_name, c.email, c.phone, a.appointment_date, a.appointment_time
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    LEFT JOIN appointments a ON b.appointment_id = a.id
    WHERE b.id = $bill_id AND b.customer_id = $customer_id
");
if (!$bill) { die('Bill not found'); }
$items = fetchAll("SELECT * FROM bill_items WHERE bill_id = $bill_id");
$payment = fetchOne("SELECT * FROM payments WHERE bill_id = $bill_id ORDER BY id DESC LIMIT 1");
$page_title = 'My Bill';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header no-print">
        <h1 class="icon-title"><i class="fa-solid fa-file-invoice"></i> My Bill</h1>
        <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Print / Download</button>
    </div>
    <div class="print-area">
        <div class="flex-between">
            <div><h2>Fabulous Unisex Salon</h2><p>Sno:12 Mahaveer Plaza, Tikare Road, Dharwad Karnataka 580001</p></div>
            <div class="text-right"><h3><?php echo htmlspecialchars($bill['bill_number']); ?></h3><p><?php echo formatDate($bill['bill_date']); ?></p><span class="badge <?php echo badgeClass($bill['status']); ?>"><?php echo statusLabel($bill['status']); ?></span></div>
        </div>
        <hr class="mt-2 mb-2">
        <p><strong>Customer:</strong> <?php echo htmlspecialchars($bill['customer_name']); ?> | <?php echo htmlspecialchars($bill['email']); ?></p>
        <table class="mt-2">
            <thead><tr><th>Service</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
            <tbody><?php foreach ($items as $item): ?><tr><td><?php echo htmlspecialchars($item['service_name']); ?></td><td><?php echo (int)$item['quantity']; ?></td><td><?php echo formatCurrency($item['price']); ?></td><td><?php echo formatCurrency($item['total']); ?></td></tr><?php endforeach; ?></tbody>
        </table>
        <div class="text-right mt-2"><h3>Final Amount: <?php echo formatCurrency($bill['final_amount']); ?></h3><p>Payment: <?php echo htmlspecialchars(statusLabel($payment['status'] ?? 'pending')); ?></p></div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>
