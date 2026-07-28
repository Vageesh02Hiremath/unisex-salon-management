<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../includes/auth.php';
requireCustomer();
$page_title = 'Browse Services';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header">
        <h1 class="icon-title"><i class="fa-solid fa-scissors"></i> Our Services</h1>
        <p>Filter services by gender category without reloading the page.</p>
    </div>
    <div class="gender-filter" id="customerGenderTabs">
        <?php foreach (['Male', 'Female', 'Kids', 'Unisex'] as $gender): ?>
            <button type="button" data-gender="<?php echo $gender; ?>" class="<?php echo $gender === 'Male' ? 'active' : ''; ?>">
                <i class="fa-solid <?php echo $gender === 'Kids' ? 'fa-child' : ($gender === 'Female' ? 'fa-person-dress' : ($gender === 'Male' ? 'fa-user' : 'fa-people-arrows')); ?>"></i> <?php echo $gender; ?>
            </button>
        <?php endforeach; ?>
    </div>
    <div id="servicesGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem;"></div>
</div>
<script>
const serviceBaseUrl = '<?php echo BASE_URL; ?>';
const serviceGrid = document.getElementById('servicesGrid');
const buttons = document.querySelectorAll('#customerGenderTabs button');

function serviceIcon(service) {
    const value = `${service.name} ${service.gender_category}`.toLowerCase();
    if (value.includes('kid')) return 'fa-child';
    if (value.includes('beard')) return 'fa-user';
    if (value.includes('facial') || value.includes('spa')) return 'fa-spa';
    if (value.includes('makeup')) return 'fa-brush';
    if (value.includes('massage')) return 'fa-hands';
    return 'fa-scissors';
}
function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
}
async function loadGenderServices(gender) {
    serviceGrid.innerHTML = '<div class="form-container">Loading services...</div>';
    const response = await fetch(`${serviceBaseUrl}ajax/get_services_by_gender.php?gender=${encodeURIComponent(gender)}`);
    const result = await response.json();
    if (!result.success || !result.services.length) {
        serviceGrid.innerHTML = '<div class="form-container text-center">No services available in this category.</div>';
        return;
    }
    serviceGrid.innerHTML = result.services.map(service => `
        <div class="form-container" style="display:flex;flex-direction:column;">
            <div style="text-align:center;margin-bottom:1rem;">
                ${service.image ? `<img class="service-card-image" src="${serviceBaseUrl}${escapeHtml(service.image)}" alt="${escapeHtml(service.name)}">` : `<div style="width:64px;height:64px;background:linear-gradient(135deg,#8B5FBF,#FF6B9D);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:1.7rem;margin:0 auto 1rem;"><i class="fa-solid ${serviceIcon(service)}"></i></div>`}
                <h3 style="color:#8B5FBF;margin-bottom:.4rem;">${escapeHtml(service.name)}</h3>
                <p style="color:#777;font-size:.9rem;">${escapeHtml(service.gender_category)} | ${escapeHtml(service.category)}</p>
            </div>
            <p style="color:#666;margin-bottom:1rem;flex:1;">${escapeHtml(service.description || 'Professional salon service')}</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;padding-top:1rem;border-top:1px solid #eee;">
                <div><strong style="color:#999;font-size:.85rem;">PRICE</strong><div style="color:#8B5FBF;font-size:1.5rem;font-weight:800;">₹${Number(service.price).toFixed(2)}</div></div>
                <div><strong style="color:#999;font-size:.85rem;">DURATION</strong><div style="color:#8B5FBF;font-size:1.5rem;font-weight:800;">${Number(service.duration)} min</div></div>
            </div>
            <a href="${serviceBaseUrl}customer/book_appointment.php?service_id=${service.id}" class="btn btn-primary btn-block"><i class="fa-solid fa-plus"></i> Book Appointment</a>
        </div>
    `).join('');
}
buttons.forEach(button => button.addEventListener('click', () => {
    buttons.forEach(item => item.classList.remove('active'));
    button.classList.add('active');
    loadGenderServices(button.dataset.gender);
}));
loadGenderServices('Male');
</script>
<?php include '../includes/footer.php'; ?>
</body>
</html>
