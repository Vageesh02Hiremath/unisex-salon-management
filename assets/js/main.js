// Unisex Salon Management System - Main JavaScript

// Close alert messages
function closeAlert(element) {
    if (element) {
        element.remove();
    }
}

// Auto-close alert after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    document.querySelectorAll('.password-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);
            const icon = button.querySelector('i');
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            icon?.classList.toggle('fa-eye');
            icon?.classList.toggle('fa-eye-slash');
        });
    });

    const loginForm = document.getElementById('loginForm');
    loginForm?.addEventListener('submit', () => {
        const submit = loginForm.querySelector('.auth-submit');
        submit?.classList.add('is-loading');
    });

    setupCaptchaRefresh();
    setupAjaxAuthForms();
    setupRegistrationRoleFields();
    setupPasswordLab();
    setupIdentityValidation();
    setupBackToTop();
});

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
});

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#E74C3C';
            isValid = false;
        } else {
            input.style.borderColor = '';
        }
    });
    
    return isValid;
}

// Confirm dialog
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR'
    }).format(amount);
}

function appBaseUrl(context = document.body) {
    const fromContext = context?.dataset?.baseUrl;
    const fromBody = document.body?.dataset?.baseUrl;
    if (fromContext || fromBody) return fromContext || fromBody;

    const appFolder = '/unisex_salon_management/';
    const index = window.location.pathname.indexOf(appFolder);
    return index >= 0 ? window.location.pathname.slice(0, index + appFolder.length) : appFolder;
}

function formToObject(form) {
    const data = {};
    const formData = new FormData(form);
    formData.forEach((value, key) => {
        if (key.endsWith('[]')) {
            const cleanKey = key.slice(0, -2);
            data[cleanKey] = data[cleanKey] || [];
            data[cleanKey].push(value);
            return;
        }
        if (data[key] !== undefined) {
            data[key] = Array.isArray(data[key]) ? data[key] : [data[key]];
            data[key].push(value);
            return;
        }
        data[key] = value;
    });
    return data;
}

function showFormAlert(form, message, type = 'danger') {
    if (!form || !message) return;
    let alert = form.parentElement?.querySelector('.ajax-form-alert');
    if (!alert) {
        alert = document.createElement('div');
        alert.className = 'ajax-form-alert alert';
        form.parentElement?.insertBefore(alert, form);
    }
    alert.className = `ajax-form-alert alert alert-${type}`;
    alert.textContent = message;
}

function updateCaptchaFromResponse(result) {
    if (!result?.captcha) return;
    const captchaCode = document.getElementById('captchaCode');
    const captchaInput = document.getElementById('captcha');
    if (captchaCode) captchaCode.textContent = result.captcha;
    if (captchaInput) captchaInput.value = '';
}

function setSubmitLoading(form, loading) {
    const button = form?.querySelector('[type="submit"]');
    if (!button) return;
    button.disabled = loading || button.dataset.ajaxDisabled === 'true';
    button.classList.toggle('is-loading', loading);
}

async function postJson(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return response.json();
}

function setupAjaxAuthForms() {
    setupAjaxLoginForm();
    setupAjaxRegistrationForms();
}

function setupAjaxLoginForm() {
    const loginForm = document.getElementById('loginForm');
    if (!loginForm) return;

    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = formToObject(loginForm);
        payload.admin_only = window.location.pathname.endsWith('/admin_login.php') || window.location.pathname.endsWith('admin_login.php');

        if (!isValidEmail((payload.email || '').trim())) {
            showFormAlert(loginForm, 'Enter a valid email address.');
            return;
        }
        if (!payload.password || !payload.captcha) {
            showFormAlert(loginForm, 'Please enter email, password, and CAPTCHA.');
            return;
        }

        setSubmitLoading(loginForm, true);
        try {
            const result = await postJson(`${appBaseUrl(loginForm)}ajax/login.php`, payload);
            updateCaptchaFromResponse(result);
            if (result.success) {
                showFormAlert(loginForm, result.message || 'Login successful.', 'success');
                window.location.href = result.redirect || appBaseUrl(loginForm);
                return;
            }
            showFormAlert(loginForm, result.message || 'Unable to login.');
        } catch (error) {
            console.error('Login failed', error);
            showFormAlert(loginForm, 'Unable to login right now. Please try again.');
        } finally {
            setSubmitLoading(loginForm, false);
        }
    });
}

function setupAjaxRegistrationForms() {
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const isAdmin = window.location.pathname.endsWith('/admin_register.php') || window.location.pathname.endsWith('admin_register.php');
            await submitAjaxRegistrationForm(registerForm, isAdmin ? 'ajax/admin_register.php' : 'ajax/register.php');
        });
    }

    document.querySelectorAll('.otp-form, .otp-actions form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            await submitAjaxRegistrationForm(form, 'ajax/register.php');
        });
    });
}

async function submitAjaxRegistrationForm(form, endpoint) {
    if (!validateRegistrationClientSide(form)) return;

    setSubmitLoading(form, true);
    try {
        const result = await postJson(`${appBaseUrl(form)}${endpoint}`, formToObject(form));
        updateCaptchaFromResponse(result);
        showFormAlert(form, result.message || (result.success ? 'Saved successfully.' : 'Unable to complete registration.'), result.success ? 'success' : 'danger');
        if (result.redirect) {
            setTimeout(() => { window.location.href = result.redirect; }, 700);
            return;
        }
        if (result.reload) {
            setTimeout(() => { window.location.reload(); }, 700);
        }
    } catch (error) {
        console.error('Registration failed', error);
        showFormAlert(form, 'Unable to complete registration right now. Please try again.');
    } finally {
        setSubmitLoading(form, false);
    }
}

function validateRegistrationClientSide(form) {
    const action = form.querySelector('[name="action"]')?.value || '';
    if (['verify_otp', 'resend_otp', 'edit_registration'].includes(action)) {
        const otp = form.querySelector('[name="otp"]');
        if (otp && !/^\d{6}$/.test(otp.value.trim())) {
            showFormAlert(form, 'Enter the 6-digit OTP from your email.');
            return false;
        }
        return true;
    }

    const name = form.querySelector('[name="name"]');
    const email = form.querySelector('[name="email"]');
    const phone = form.querySelector('[name="phone"]');
    const password = form.querySelector('[name="password"]');
    const confirm = form.querySelector('[name="confirm_password"]');
    const captcha = form.querySelector('[name="captcha"]');
    const role = form.querySelector('[name="role"]:checked')?.value || 'admin';
    const gender = form.querySelector('[name="gender"]');

    if (name && !/^[A-Za-z][A-Za-z\s.'-]{1,98}$/.test(name.value.trim())) {
        showFormAlert(form, 'Please enter a valid name.');
        return false;
    }
    if (email && !isValidEmail(email.value.trim())) {
        showFormAlert(form, 'Please enter a valid email.');
        return false;
    }
    if (phone && !isValidPhone(phone.value.trim())) {
        showFormAlert(form, 'Please enter a valid phone number.');
        return false;
    }
    if (role === 'customer' && gender && !gender.value) {
        showFormAlert(form, 'Please select gender for customer registration.');
        return false;
    }
    if (password && !/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(password.value)) {
        showFormAlert(form, 'Password must include uppercase, lowercase, number, special character, and 8 characters.');
        return false;
    }
    if (password && confirm && password.value !== confirm.value) {
        showFormAlert(form, 'Passwords do not match.');
        return false;
    }
    if (captcha && !/^\d{6}$/.test(captcha.value.trim())) {
        showFormAlert(form, 'Enter the 6-digit CAPTCHA.');
        return false;
    }
    return true;
}

function setupPasswordLab() {
    const password = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');
    const strengthText = document.getElementById('strengthText');
    const strengthBar = document.getElementById('strengthBar');
    const registerButton = document.getElementById('registerButton');
    const generator = document.getElementById('generatePassword');
    const generatedPassword = document.getElementById('generatedPassword');
    const copyPassword = document.getElementById('copyPassword');
    if (!password || !confirm || !strengthText || !strengthBar) return;

    const checks = {
        length: (value) => value.length >= 8,
        upper: (value) => /[A-Z]/.test(value),
        lower: (value) => /[a-z]/.test(value),
        number: (value) => /\d/.test(value),
        special: (value) => /[^A-Za-z0-9]/.test(value)
    };

    function updateStrength() {
        const value = password.value;
        const passed = Object.entries(checks).filter(([key, test]) => {
            const ok = test(value);
            setCheck(key, ok);
            return ok;
        }).length;
        const matched = value.length > 0 && value === confirm.value;
        setCheck('match', matched);

        const labels = ['Weak', 'Weak', 'Medium', 'Strong', 'Strong', 'Very Strong'];
        const widths = [10, 22, 45, 70, 86, 100];
        strengthText.textContent = labels[passed];
        strengthBar.style.width = `${widths[passed]}%`;
        strengthBar.dataset.level = labels[passed].toLowerCase().replace(' ', '-');
        if (registerButton) registerButton.disabled = !(passed >= 4 && matched);
    }

    function setCheck(name, ok) {
        const item = document.querySelector(`[data-check="${name}"]`);
        if (!item) return;
        item.classList.toggle('valid', ok);
        const icon = item.querySelector('i');
        if (icon) icon.className = ok ? 'fa-solid fa-check-circle' : 'fa-solid fa-circle';
    }

    function makePassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
        const required = ['A', 'a', '7', '@'];
        let value = required.join('');
        while (value.length < 14) value += chars[Math.floor(Math.random() * chars.length)];
        return value.split('').sort(() => Math.random() - 0.5).join('');
    }

    generator?.addEventListener('click', () => {
        const value = makePassword();
        generatedPassword.value = value;
        password.value = value;
        confirm.value = value;
        updateStrength();
    });

    copyPassword?.addEventListener('click', async () => {
        if (!generatedPassword.value) return;
        await navigator.clipboard?.writeText(generatedPassword.value);
        copyPassword.textContent = 'Copied';
        setTimeout(() => copyPassword.innerHTML = '<i class="fa-solid fa-copy"></i> Copy', 1200);
    });

    password.addEventListener('input', updateStrength);
    confirm.addEventListener('input', updateStrength);
    updateStrength();
}

function setupRegistrationRoleFields() {
    const registerForm = document.getElementById('registerForm');
    const staffFields = document.getElementById('staffFields');
    const customerFields = document.querySelectorAll('.customer-field');
    const gender = document.getElementById('gender');
    if (!registerForm || !staffFields) return;

    function updateRoleFields() {
        const role = registerForm.querySelector('input[name="role"]:checked')?.value || 'customer';
        const isStaff = role === 'staff';
        const isCustomer = role === 'customer';
        staffFields.classList.toggle('hidden', !isStaff);
        customerFields.forEach((field) => field.classList.toggle('hidden', !isCustomer));
        if (gender) {
            gender.required = isCustomer;
            if (!isCustomer) gender.value = '';
        }
    }

    registerForm.querySelectorAll('input[name="role"]').forEach((input) => {
        input.addEventListener('change', updateRoleFields);
    });
    updateRoleFields();
}

function setupCaptchaRefresh() {
    const refreshButton = document.getElementById('refreshCaptcha');
    const captchaCode = document.getElementById('captchaCode');
    const captchaInput = document.getElementById('captcha');
    if (!refreshButton || !captchaCode) return;

    const baseUrl = appBaseUrl(refreshButton.closest('form'));

    refreshButton.addEventListener('click', async () => {
        refreshButton.classList.add('is-spinning');
        try {
            const response = await fetch(`${baseUrl}ajax/refresh_captcha.php`, { cache: 'no-store' });
            const result = await response.json();
            if (result.success) {
                captchaCode.textContent = result.captcha;
                if (captchaInput) captchaInput.value = '';
            }
        } catch (error) {
            console.error('CAPTCHA refresh failed', error);
        } finally {
            setTimeout(() => refreshButton.classList.remove('is-spinning'), 250);
        }
    });
}

function setupIdentityValidation() {
    const forms = document.querySelectorAll('form[data-validate-identity]');
    forms.forEach((form) => {
        const name = form.querySelector('[name="name"]');
        const email = form.querySelector('[name="email"]');
        const phone = form.querySelector('[name="phone"]');
        const submit = form.querySelector('[type="submit"]');
        const baseUrl = appBaseUrl(form);
        let duplicateState = { email: false, phone: false };

        const setMessage = (input, message, type = 'error') => {
            if (!input) return;
            let holder = input.closest('.form-group')?.querySelector(`.field-message[data-for="${input.name}"]`);
            if (!holder) {
                holder = document.createElement('small');
                holder.className = 'field-message';
                holder.dataset.for = input.name;
                input.closest('.form-group')?.appendChild(holder);
            }
            holder.textContent = message;
            holder.dataset.type = type;
        };

        const validateLocal = () => {
            let valid = true;
            if (name && name.required && !/^[A-Za-z][A-Za-z\s.'-]{1,98}$/.test(name.value.trim())) {
                setMessage(name, 'Use letters, spaces, dot, apostrophe, or hyphen only.');
                valid = false;
            } else if (name) {
                setMessage(name, '');
            }

            if (email && email.value && !isValidEmail(email.value.trim())) {
                setMessage(email, 'Enter a valid email address.');
                valid = false;
            } else if (email && !duplicateState.email) {
                setMessage(email, '');
            }

            if (phone && phone.value && !isValidPhone(phone.value.trim())) {
                setMessage(phone, 'Enter a valid phone number.');
                valid = false;
            } else if (phone && !duplicateState.phone) {
                setMessage(phone, '');
            }

            valid = valid && !duplicateState.email && !duplicateState.phone;
            if (submit && submit.id !== 'registerButton') submit.disabled = !valid;
            return valid;
        };

        const checkExisting = debounce(async () => {
            const params = new URLSearchParams();
            if (email && !email.readOnly && email.value) params.set('email', email.value.trim());
            if (phone && phone.value) params.set('phone', phone.value.trim());
            if (form.dataset.excludeId) params.set('exclude_id', form.dataset.excludeId);
            if (form.dataset.scope) params.set('scope', form.dataset.scope);
            if (!params.toString()) {
                duplicateState = { email: false, phone: false };
                validateLocal();
                return;
            }
            try {
                const response = await fetch(`${baseUrl}ajax/check_existing.php?${params.toString()}`, { cache: 'no-store' });
                const result = await response.json();
                duplicateState.email = Boolean(result.email_exists);
                duplicateState.phone = Boolean(result.phone_exists);
                if (email && !email.readOnly) setMessage(email, result.messages?.email || '', result.email_exists ? 'error' : 'ok');
                if (phone) setMessage(phone, result.messages?.phone || '', result.phone_exists ? 'error' : 'ok');
            } catch (error) {
                console.error('Duplicate check failed', error);
            }
            validateLocal();
        }, 350);

        [name, email, phone].forEach((input) => {
            input?.addEventListener('input', () => {
                validateLocal();
                checkExisting();
            });
        });
        form.addEventListener('submit', (event) => {
            if (!validateLocal()) event.preventDefault();
        });
        validateLocal();
    });
}

function debounce(callback, wait) {
    let timer = null;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => callback(...args), wait);
    };
}

function setupBackToTop() {
    const link = document.querySelector('.back-to-top');
    if (!link) return;
    link.addEventListener('click', (event) => {
        event.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// Format date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Check if email is valid
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Check if phone is valid
function isValidPhone(phone) {
    const re = /^[0-9\-\+\(\)]{7,}$/;
    return re.test(phone);
}

// Show loading spinner
function showLoading() {
    const spinner = document.createElement('div');
    spinner.id = 'loading-spinner';
    spinner.innerHTML = '<div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); text-align: center; z-index: 1001;"><div style="border: 4px solid #f3f3f3; border-top: 4px solid #8B5FBF; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto; margin-bottom: 1rem;"></div><p>Loading...</p></div>';
    spinner.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; z-index: 1000;';
    document.body.appendChild(spinner);
}

// Hide loading spinner
function hideLoading() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) spinner.remove();
}

// Add animation for spin
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
`;
document.head.appendChild(style);

// Export functions to window object
window.closeAlert = closeAlert;
window.openModal = openModal;
window.closeModal = closeModal;
window.validateForm = validateForm;
window.confirmDelete = confirmDelete;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.isValidEmail = isValidEmail;
window.isValidPhone = isValidPhone;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
