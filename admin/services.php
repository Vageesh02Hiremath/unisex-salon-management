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
$genders = ['Male', 'Female', 'Kids', 'Unisex'];

function uploadServiceImage($fieldName, $currentImage = null) {
    if (empty($_FILES[$fieldName]['name'])) {
        return $currentImage;
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Image upload failed.');
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = mime_content_type($_FILES[$fieldName]['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new Exception('Please upload a JPG, PNG, WEBP, or GIF image.');
    }

    if ($_FILES[$fieldName]['size'] > 2 * 1024 * 1024) {
        throw new Exception('Service image must be under 2MB.');
    }

    $directory = dirname(__DIR__) . '/assets/images/services/';
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
    $filename = 'service-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $target = $directory . $filename;
    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $target)) {
        throw new Exception('Could not save uploaded image.');
    }

    return 'assets/images/services/' . $filename;
}

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    try {
        preparedQuery("DELETE FROM services WHERE id = ?", 'i', [$id]);
        $success = 'Service deleted successfully.';
    } catch (Exception $exception) {
        $error = 'Unable to delete service because it may be linked with existing records.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['service_id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $duration = (int)($_POST['duration'] ?? 0);
    $category = sanitize($_POST['category'] ?? '');
    $gender_category = sanitize($_POST['gender_category'] ?? 'Unisex');
    $status = sanitize($_POST['status'] ?? 'active');

    if (!$name || $price <= 0 || $duration <= 0 || !in_array($gender_category, $genders, true)) {
        $error = 'Please enter valid service details.';
    } else {
        try {
            $currentImage = $id > 0 ? (fetchPrepared("SELECT image FROM services WHERE id = ?", 'i', [$id])['image'] ?? null) : null;
            $image = uploadServiceImage('image', $currentImage);
            if ($id > 0) {
                preparedQuery(
                    "UPDATE services SET name = ?, description = ?, price = ?, duration = ?, category = ?, gender_category = ?, status = ?, image = ? WHERE id = ?",
                    'ssdissssi',
                    [$name, $description, $price, $duration, $category, $gender_category, $status, $image, $id]
                );
                $success = 'Service updated successfully.';
            } else {
                preparedQuery(
                    "INSERT INTO services (name, description, price, duration, category, gender_category, status, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    'ssdissss',
                    [$name, $description, $price, $duration, $category, $gender_category, $status, $image]
                );
                $success = 'Service added successfully.';
            }
        } catch (Exception $exception) {
            $error = $exception->getMessage();
        }
    }
}

$filter_gender = sanitize($_GET['gender'] ?? '');
$edit_service = isset($_GET['edit_id']) ? fetchPrepared("SELECT * FROM services WHERE id = ?", 'i', [(int)$_GET['edit_id']]) : null;
$services = $filter_gender && in_array($filter_gender, $genders, true)
    ? fetchAllPrepared("SELECT * FROM services WHERE gender_category = ? ORDER BY created_at DESC", 's', [$filter_gender])
    : fetchAll("SELECT * FROM services ORDER BY created_at DESC");

$page_title = 'Manage Services';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1 class="icon-title"><i class="fa-solid fa-scissors"></i> Manage Services</h1>
        <p>Add, edit, delete, and filter gender-based salon services.</p>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <div class="form-container">
        <h3><?php echo $edit_service ? 'Edit Service' : 'Add New Service'; ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="service_id" value="<?php echo (int)($edit_service['id'] ?? 0); ?>">
            <div class="form-grid">
                <div class="form-group"><label>Name *</label><input type="text" name="name" value="<?php echo htmlspecialchars($edit_service['name'] ?? ''); ?>" required></div>
                <div class="form-group"><label>Category</label><input type="text" name="category" value="<?php echo htmlspecialchars($edit_service['category'] ?? ''); ?>" placeholder="Hair, Skincare"></div>
                <div class="form-group"><label>Gender Category *</label><select name="gender_category" required><?php foreach ($genders as $gender): ?><option value="<?php echo $gender; ?>" <?php echo ($edit_service['gender_category'] ?? 'Unisex') === $gender ? 'selected' : ''; ?>><?php echo $gender; ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Price (₹) *</label><input type="number" name="price" step="0.01" min="1" value="<?php echo htmlspecialchars($edit_service['price'] ?? ''); ?>" required></div>
                <div class="form-group"><label>Duration *</label><input type="number" name="duration" min="1" value="<?php echo htmlspecialchars($edit_service['duration'] ?? ''); ?>" required></div>
                <div class="form-group"><label>Status</label><select name="status"><option value="active" <?php echo ($edit_service['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo ($edit_service['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option></select></div>
                <div class="form-group"><label>Service Image</label><input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif"><?php if (!empty($edit_service['image'])): ?><small>Current: <?php echo htmlspecialchars(basename($edit_service['image'])); ?></small><?php endif; ?></div>
            </div>
            <div class="form-group"><label>Description</label><textarea name="description"><?php echo htmlspecialchars($edit_service['description'] ?? ''); ?></textarea></div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> <?php echo $edit_service ? 'Update Service' : 'Add Service'; ?></button>
            <?php if ($edit_service): ?><a href="<?php echo BASE_URL; ?>admin/services.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </form>
    </div>

    <div class="table-container mt-3">
        <div class="table-header flex-between">
            <h2><i class="fa-solid fa-list"></i> Services List</h2>
            <div class="gender-filter">
                <a class="<?php echo !$filter_gender ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/services.php">All</a>
                <?php foreach ($genders as $gender): ?><a class="<?php echo $filter_gender === $gender ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/services.php?gender=<?php echo $gender; ?>"><?php echo $gender; ?></a><?php endforeach; ?>
            </div>
        </div>
        <table>
            <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Gender</th><th>Price</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?php if (!empty($service['image'])): ?><img class="service-thumb" src="<?php echo BASE_URL . htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>"><?php else: ?><span class="service-thumb-placeholder"><i class="fa-solid fa-image"></i></span><?php endif; ?></td>
                        <td><i class="fa-solid fa-scissors"></i> <?php echo htmlspecialchars($service['name']); ?></td>
                        <td><?php echo htmlspecialchars($service['category'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($service['gender_category']); ?></td>
                        <td><?php echo formatCurrency($service['price']); ?></td>
                        <td><?php echo (int)$service['duration']; ?> mins</td>
                        <td><span class="badge <?php echo $service['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>"><?php echo ucfirst($service['status']); ?></span></td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>admin/services.php?edit_id=<?php echo (int)$service['id']; ?>"><i class="fa-solid fa-pen"></i></a>
                            <a class="btn btn-danger btn-sm" href="<?php echo BASE_URL; ?>admin/services.php?delete_id=<?php echo (int)$service['id']; ?>" onclick="return confirm('Delete this service?')"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$services): ?><tr><td colspan="8" class="text-center">No services found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>
