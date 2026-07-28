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

// Delete customer
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $query = "DELETE FROM customers WHERE id = $id";
    if ($conn->query($query)) {
        $success = 'Customer deleted successfully!';
    } else {
        $error = 'Failed to delete customer';
    }
}

// Get all customers
$search = $_GET['search'] ?? '';
if ($search) {
    $search = sanitize($search);
    $customers = fetchAll("SELECT * FROM customers WHERE name LIKE '%$search%' OR email LIKE '%$search%' ORDER BY created_at DESC");
} else {
    $customers = fetchAll("SELECT * FROM customers ORDER BY created_at DESC");
}

$page_title = 'Manage Customers';
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
            <h1>Manage Customers</h1>
            <p>View and manage all registered customers</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <div class="table-header flex-between">
                <div>
                    <h2>Customers List</h2>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <form method="GET" style="display: flex; gap: 0.5rem;">
                        <input type="text" name="search" placeholder="Search by name or email" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Search</button>
                        <?php if ($search): ?>
                            <a href="<?php echo BASE_URL; ?>admin/customers.php" class="btn btn-secondary btn-sm">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($customers): ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($customer['gender'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $customer['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($customer['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($customer['created_at']); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>admin/customers.php?delete_id=<?php echo $customer['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No customers found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
