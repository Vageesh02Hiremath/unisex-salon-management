<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once 'config/db.php';
require_once 'includes/auth.php';

$popular_services = fetchAll("SELECT * FROM services WHERE status = 'active' ORDER BY price DESC LIMIT 6");
$reviews = fetchAll("
    SELECT f.rating, f.comment, c.name AS customer_name
    FROM feedback f
    JOIN customers c ON f.customer_id = c.id
    ORDER BY f.created_at DESC
    LIMIT 3
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unisex Salon Management System</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>assets/images/salon-logo.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/landing.css">
</head>
<body data-base-url="<?php echo BASE_URL; ?>">
    <nav class="landing-nav">
        <a class="brand" href="#home"><img class="landing-brand-logo" src="<?php echo BASE_URL; ?>assets/images/salon-logo.svg" alt=""><span>Unisex Salon</span></a>
        <button class="nav-toggle" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#services">Services</a>
            <a href="#about">About</a>
            <a href="#gallery">Gallery</a>
            <a href="#contact">Contact</a>
            <a href="<?php echo BASE_URL; ?>login.php">Login</a>
            <a href="<?php echo BASE_URL; ?>register.php" class="nav-pill">Register</a>
        </div>
    </nav>

    <header class="hero" id="home">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <p class="eyebrow"><i class="fa-solid fa-spa"></i> Premium salon care for everyone</p>
            <h1>Style, grooming, and beauty appointments made effortless.</h1>
            <p>Book services, discover expert staff, track appointments, and manage bills in one polished salon system.</p>
            <div class="hero-actions">
                <a href="<?php echo BASE_URL; ?>customer/book_appointment.php" class="btn btn-primary"><i class="fa-solid fa-calendar-plus"></i> Book Appointment</a>
                <a href="<?php echo BASE_URL; ?>customer/services.php" class="btn btn-glass"><i class="fa-solid fa-scissors"></i> View Services</a>
            </div>
        </div>
    </header>

    <main>
        <section class="section gender-menu" id="services">
            <div class="section-title">
                <p>Service Menu</p>
                <h2>Choose by category</h2>
            </div>
            <div class="gender-tabs">
                <button class="gender-tab active" data-gender="Male"><i class="fa-solid fa-user"></i> Male Services</button>
                <button class="gender-tab" data-gender="Female"><i class="fa-solid fa-person-dress"></i> Female Services</button>
                <button class="gender-tab" data-gender="Kids"><i class="fa-solid fa-child"></i> Kids Services</button>
                <button class="gender-tab" data-gender="Unisex"><i class="fa-solid fa-people-arrows"></i> Unisex Services</button>
            </div>
            <div id="genderServices" class="service-grid"></div>
        </section>

        <section class="section">
            <div class="section-title">
                <p>Popular Picks</p>
                <h2>Most loved services</h2>
            </div>
            <div class="service-grid">
                <?php foreach ($popular_services as $service): ?>
                    <article class="glass-card">
                        <i class="fa-solid <?php echo stripos($service['name'], 'beard') !== false ? 'fa-user' : (stripos($service['name'], 'facial') !== false ? 'fa-spa' : (stripos($service['name'], 'makeup') !== false ? 'fa-brush' : 'fa-scissors')); ?>"></i>
                        <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                        <p><?php echo htmlspecialchars($service['description'] ?: 'Professional salon service tailored to your style.'); ?></p>
                        <div class="card-meta">
                            <span><?php echo formatCurrency($service['price']); ?></span>
                            <span><?php echo (int)$service['duration']; ?> mins</span>
                        </div>
                        <a href="<?php echo BASE_URL; ?>customer/book_appointment.php?service_id=<?php echo (int)$service['id']; ?>">Book now <i class="fa-solid fa-arrow-right"></i></a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section split" id="about">
            <div>
                <p class="eyebrow dark"><i class="fa-solid fa-star"></i> Why Choose Us</p>
                <h2>Designed for smooth salon operations and happy customers.</h2>
            </div>
            <div class="why-grid">
                <div class="glass-card"><i class="fa-solid fa-user-tie"></i><h3>Expert Staff</h3><p>Assign specialists and keep daily schedules clear.</p></div>
                <div class="glass-card"><i class="fa-solid fa-calendar-check"></i><h3>Smart Booking</h3><p>AJAX slots prevent double booking instantly.</p></div>
                <div class="glass-card"><i class="fa-solid fa-file-invoice"></i><h3>Auto Billing</h3><p>Bills are generated automatically after completion.</p></div>
                <div class="glass-card"><i class="fa-solid fa-chart-line"></i><h3>Reports</h3><p>Track revenue, appointments, payments, and feedback.</p></div>
            </div>
        </section>

        <section class="section steps">
            <div class="section-title">
                <p>How It Works</p>
                <h2>Book in three simple steps</h2>
            </div>
            <div class="step-row">
                <div><span>1</span><h3>Select Service</h3><p>Browse by male, female, kids, or unisex categories.</p></div>
                <div><span>2</span><h3>Pick Slot</h3><p>Choose staff, date, and available time without reloads.</p></div>
                <div><span>3</span><h3>Visit Salon</h3><p>Track status and view bills after your service.</p></div>
            </div>
        </section>

        <section class="section gallery" id="gallery">
            <div class="section-title">
                <p>Gallery</p>
                <h2>Salon moments</h2>
            </div>
            <div class="gallery-grid">
                <div class="gallery-item item-one"></div>
                <div class="gallery-item item-two"></div>
                <div class="gallery-item item-three"></div>
                <div class="gallery-item item-four"></div>
            </div>
        </section>

        <section class="section reviews">
            <div class="section-title">
                <p>Reviews</p>
                <h2>What customers say</h2>
            </div>
            <div class="service-grid">
                <?php foreach ($reviews as $review): ?>
                    <article class="glass-card">
                        <div class="stars"><?php echo str_repeat('<i class="fa-solid fa-star"></i>', (int)$review['rating']); ?></div>
                        <p><?php echo htmlspecialchars($review['comment']); ?></p>
                        <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section contact" id="contact">
            <div class="contact-card">
                <div>
                    <p class="eyebrow dark"><i class="fa-solid fa-location-dot"></i> Contact</p>
                    <h2>Ready for your next salon visit?</h2>
                    <p>Fabulous Unisex Salon, Sno:12 Mahaveer Plaza, Tikare Road, Dharwad Karnataka 580001</p>
                </div>
                <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-glass"><i class="fa-solid fa-user-plus"></i> Create Account</a>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <p class="footer-brand"><img class="landing-brand-logo" src="<?php echo BASE_URL; ?>assets/images/salon-logo.svg" alt=""> Unisex Salon Management System</p>
        <p>Pay at salon or use Razorpay online payments after adding your Razorpay account keys.</p>
    </footer>

    <script src="<?php echo BASE_URL; ?>assets/js/landing.js"></script>
</body>
</html>
