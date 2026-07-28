<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<div class="sidebar">
    <nav class="sidebar-nav">
        <a href="<?php echo BASE_URL; ?>" class="sidebar-brand">
            <span class="brand-mark"><img src="<?php echo BASE_URL; ?>assets/images/salon-logo.svg" alt=""></span>
            <span>Unisex Salon</span>
        </a>
        <?php if (hasRole('admin')): ?>
            <div class="sidebar-title">Admin Panel</div>
            <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="sidebar-link <?php echo $current_dir === 'admin' && $current_page === 'dashboard.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-gauge"></i></span> Dashboard</a>
            <a href="<?php echo BASE_URL; ?>admin/customers.php" class="sidebar-link <?php echo $current_dir === 'admin' && $current_page === 'customers.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-users"></i></span> Customers</a>
            <a href="<?php echo BASE_URL; ?>admin/staff.php" class="sidebar-link <?php echo $current_dir === 'admin' && $current_page === 'staff.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-user-tie"></i></span> Staff</a>
            <a href="<?php echo BASE_URL; ?>admin/services.php" class="sidebar-link <?php echo $current_dir === 'admin' && $current_page === 'services.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-scissors"></i></span> Services</a>
            <a href="<?php echo BASE_URL; ?>admin/appointments.php" class="sidebar-link <?php echo $current_dir === 'admin' && $current_page === 'appointments.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-calendar-check"></i></span> Appointments</a>
            <a href="<?php echo BASE_URL; ?>admin/calendar.php" class="sidebar-link <?php echo $current_dir === 'admin' && $current_page === 'calendar.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-calendar-days"></i></span> Calendar</a>
            <a href="<?php echo BASE_URL; ?>admin/bills.php" class="sidebar-link <?php echo $current_dir === 'admin' && $current_page === 'bills.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-file-invoice"></i></span> Bills</a>
            <a href="<?php echo BASE_URL; ?>admin/payments.php" class="sidebar-link <?php echo $current_dir === 'admin' && $current_page === 'payments.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-money-bill-wave"></i></span> Payments</a>
            <a href="<?php echo BASE_URL; ?>admin/feedback.php" class="sidebar-link <?php echo $current_dir === 'admin' && $current_page === 'feedback.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-star"></i></span> Feedback</a>
            <a href="<?php echo BASE_URL; ?>admin/reports.php" class="sidebar-link <?php echo $current_dir === 'admin' && $current_page === 'reports.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-chart-line"></i></span> Reports</a>
            <a href="<?php echo BASE_URL; ?>admin/revenue_chart.php" class="sidebar-link <?php echo $current_dir === 'admin' && $current_page === 'revenue_chart.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-indian-rupee-sign"></i></span> Revenue Chart</a>
        <?php elseif (hasRole('staff')): ?>
            <div class="sidebar-title">Staff Panel</div>
            <a href="<?php echo BASE_URL; ?>staff/dashboard.php" class="sidebar-link <?php echo $current_dir === 'staff' && $current_page === 'dashboard.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-gauge"></i></span> Dashboard</a>
            <a href="<?php echo BASE_URL; ?>staff/assigned_appointments.php" class="sidebar-link <?php echo $current_dir === 'staff' && $current_page === 'assigned_appointments.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-calendar-check"></i></span> Assigned Appointments</a>
            <a href="<?php echo BASE_URL; ?>staff/schedule.php" class="sidebar-link <?php echo $current_dir === 'staff' && $current_page === 'schedule.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-clock"></i></span> Schedule</a>
        <?php elseif (hasRole('customer')): ?>
            <div class="sidebar-title">Customer Panel</div>
            <a href="<?php echo BASE_URL; ?>customer/dashboard.php" class="sidebar-link <?php echo $current_dir === 'customer' && $current_page === 'dashboard.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-gauge"></i></span> Dashboard</a>
            <a href="<?php echo BASE_URL; ?>customer/services.php" class="sidebar-link <?php echo $current_dir === 'customer' && $current_page === 'services.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-scissors"></i></span> Services</a>
            <a href="<?php echo BASE_URL; ?>customer/book_appointment.php" class="sidebar-link <?php echo $current_dir === 'customer' && $current_page === 'book_appointment.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-plus"></i></span> Book Appointment</a>
            <a href="<?php echo BASE_URL; ?>customer/my_appointments.php" class="sidebar-link <?php echo $current_dir === 'customer' && $current_page === 'my_appointments.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-calendar-days"></i></span> My Appointments</a>
            <a href="<?php echo BASE_URL; ?>customer/my_bills.php" class="sidebar-link <?php echo $current_dir === 'customer' && $current_page === 'my_bills.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-file-invoice"></i></span> My Bills</a>
            <a href="<?php echo BASE_URL; ?>customer/feedback.php" class="sidebar-link <?php echo $current_dir === 'customer' && $current_page === 'feedback.php' ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-star"></i></span> Feedback</a>
        <?php endif; ?>
    </nav>
</div>
