document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = document.body.dataset.baseUrl || '/unisex_salon_management/';
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    const tabs = document.querySelectorAll('.gender-tab');
    const target = document.getElementById('genderServices');

    navToggle?.addEventListener('click', () => navLinks?.classList.toggle('open'));

    async function loadServices(gender) {
        if (!target) return;
        target.innerHTML = '<article class="glass-card"><h3>Loading services...</h3></article>';
        try {
            const response = await fetch(`${baseUrl}ajax/get_services_by_gender.php?gender=${encodeURIComponent(gender)}`);
            const result = await response.json();
            if (!result.success || !result.services.length) {
                target.innerHTML = '<article class="glass-card"><h3>No services found</h3><p>Please check another category.</p></article>';
                return;
            }
            target.innerHTML = result.services.map((service) => `
                <article class="glass-card">
                    <i class="fa-solid ${iconFor(service.name, service.gender_category)}"></i>
                    <h3>${escapeHtml(service.name)}</h3>
                    <p>${escapeHtml(service.description || 'Premium salon service.')}</p>
                    <div class="card-meta">
                        <span>₹${Number(service.price).toFixed(2)}</span>
                        <span>${Number(service.duration)} mins</span>
                    </div>
                    <a href="${baseUrl}customer/book_appointment.php?service_id=${service.id}">Book Appointment <i class="fa-solid fa-arrow-right"></i></a>
                </article>
            `).join('');
        } catch (error) {
            target.innerHTML = '<article class="glass-card"><h3>Unable to load services</h3><p>Please try again.</p></article>';
        }
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((item) => item.classList.remove('active'));
            tab.classList.add('active');
            loadServices(tab.dataset.gender);
        });
    });

    function iconFor(name, gender) {
        const value = `${name} ${gender}`.toLowerCase();
        if (value.includes('kid')) return 'fa-child';
        if (value.includes('beard')) return 'fa-user';
        if (value.includes('facial') || value.includes('spa')) return 'fa-spa';
        if (value.includes('makeup')) return 'fa-brush';
        if (value.includes('massage')) return 'fa-hands';
        return 'fa-scissors';
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[char]);
    }

    loadServices('Male');
});
