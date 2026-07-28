<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Salon Management' : 'Salon Management System'; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>assets/images/salon-logo.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
</head>
<body data-base-url="<?php echo BASE_URL; ?>">
    <div class="navbar">
        <div class="navbar-menu">
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo BASE_URL; ?>profile.php" class="nav-icon" title="Profile" aria-label="Profile"><i class="fa-solid fa-user"></i></a>
                <span class="user-welcome"><?php echo htmlspecialchars($_SESSION['name']); ?> <small><?php echo ucfirst($_SESSION['role']); ?></small></span>
                <a href="<?php echo BASE_URL; ?>logout.php" class="nav-icon logout" title="Logout" aria-label="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login.php" class="nav-link"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                <a href="<?php echo BASE_URL; ?>register.php" class="nav-link"><i class="fa-solid fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="container-main">
