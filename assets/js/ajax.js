// AJAX Functions for Salon Management System

// Base URL constant
const BASE_URL = document.querySelector('[data-base-url]')?.getAttribute('data-base-url') || (() => {
    const appFolder = '/unisex_salon_management/';
    const index = window.location.pathname.indexOf(appFolder);
    return index >= 0 ? window.location.pathname.slice(0, index + appFolder.length) : appFolder;
})();

// Generic AJAX function
function sendAjax(url, method = 'POST', data = {}) {
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => result)
    .catch(error => {
        console.error('Error:', error);
        return { success: false, message: 'An error occurred' };
    });
}

// Check available time slots
async function checkAvailableSlots(serviceId, appointmentDate, staffId = null) {
    try {
        showLoading();
        
        let url = `${BASE_URL}ajax/check_slots.php?service_id=${serviceId}&appointment_date=${appointmentDate}`;
        if (staffId) {
            url += `&staff_id=${staffId}`;
        }
        
        const response = await fetch(url);
        const result = await response.json();
        hideLoading();
        
        return result;
    } catch (error) {
        console.error('Error checking slots:', error);
        hideLoading();
        return { success: false, message: 'Error checking slots' };
    }
}

// Update appointment status
async function updateAppointmentStatus(appointmentId, status) {
    try {
        const response = await fetch(`${BASE_URL}ajax/update_status.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                appointment_id: appointmentId,
                status: status
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error updating status:', error);
        return { success: false, message: 'Error updating status' };
    }
}

// Book appointment via AJAX
async function bookAppointmentAjax(data) {
    try {
        showLoading();
        const response = await fetch(`${BASE_URL}ajax/book_appointment.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        hideLoading();
        
        return result;
    } catch (error) {
        console.error('Error booking appointment:', error);
        hideLoading();
        return { success: false, message: 'Error booking appointment' };
    }
}

// Search functionality
async function searchData(query, type) {
    try {
        const response = await fetch(`${BASE_URL}ajax/search.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                query: query,
                type: type
            })
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error searching:', error);
        return { success: false, message: 'Error searching' };
    }
}

// Event listeners for time slot selection
document.addEventListener('DOMContentLoaded', function() {
    setupAjaxBookingForms();

    if (document.getElementById('bookingForm')) return;

    const serviceSelect = document.getElementById('service_id');
    const dateInput = document.getElementById('appointment_date');
    const staffSelect = document.getElementById('staff_id');
    const timeSelect = document.getElementById('appointment_time');
    
    // When service changes
    if (serviceSelect) {
        serviceSelect.addEventListener('change', async function() {
            if (dateInput && dateInput.value) {
                await loadTimeSlots();
            }
        });
    }
    
    // When date changes
    if (dateInput) {
        dateInput.addEventListener('change', async function() {
            if (serviceSelect && serviceSelect.value) {
                await loadTimeSlots();
            }
        });
    }
    
    // When staff changes
    if (staffSelect) {
        staffSelect.addEventListener('change', async function() {
            if (serviceSelect && serviceSelect.value && dateInput && dateInput.value) {
                await loadTimeSlots();
            }
        });
    }
});

function setupAjaxBookingForms() {
    const form = document.getElementById('bookingForm');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        if (event.defaultPrevented) return;
        event.preventDefault();

        const payload = formToObject(form);
        const isGuestBooking = document.getElementById('guest_email') !== null;
        const validation = validateBookingPayload(payload, isGuestBooking);
        if (!validation.valid) {
            showFormAlert(form, validation.message);
            focusBookingProblem(validation.field);
            return;
        }

        if (payload.payment_method === 'razorpay') {
            await submitRazorpayBooking(form, payload, isGuestBooking);
            return;
        }

        setBookingSubmitLoading(form, true);
        try {
            const endpoint = isGuestBooking ? 'ajax/guest_booking.php' : 'ajax/book_appointment.php';
            const result = await postJson(`${appBaseUrl(form)}${endpoint}`, payload);
            if (result.success) {
                showFormAlert(form, result.message || 'Appointment booked successfully.', 'success');
                form.reset();
                const timeSelect = document.getElementById('appointment_time');
                if (timeSelect) {
                    timeSelect.innerHTML = '<option value="">Select services and date first</option>';
                    timeSelect.disabled = true;
                }
                if (typeof showBookingStep === 'function') showBookingStep(1);
                if (typeof updateServicePickerLabel === 'function') updateServicePickerLabel();
                if (typeof updateBookingSummary === 'function') updateBookingSummary();
                return;
            }
            showFormAlert(form, result.message || 'Unable to book appointment.');
        } catch (error) {
            console.error('Booking failed', error);
            showFormAlert(form, 'Unable to book appointment right now. Please try again.');
        } finally {
            setBookingSubmitLoading(form, false);
        }
    });
}

async function submitRazorpayBooking(form, payload, isGuestBooking) {
    if (!window.RAZORPAY_ENABLED || typeof Razorpay === 'undefined') {
        showFormAlert(form, 'Razorpay is not configured or could not be loaded. Please choose Pay at Salon.');
        return;
    }

    setBookingSubmitLoading(form, true);
    try {
        const baseUrl = appBaseUrl(form);
        const orderEndpoint = isGuestBooking ? 'ajax/create_guest_razorpay_order.php' : 'ajax/create_razorpay_order.php';
        const bookingEndpoint = isGuestBooking ? 'ajax/guest_booking.php' : 'ajax/book_appointment.php';
        const order = await postJson(`${baseUrl}${orderEndpoint}`, payload);
        if (!order.success) {
            showFormAlert(form, order.message || 'Unable to create Razorpay order.');
            setBookingSubmitLoading(form, false);
            return;
        }

        const checkout = new Razorpay({
            key: order.key_id,
            amount: order.amount,
            currency: order.currency,
            name: order.name,
            description: order.description,
            order_id: order.order_id,
            prefill: order.prefill,
            theme: { color: '#8B5FBF' },
            handler: async function(response) {
                try {
                    const result = await postJson(`${baseUrl}${bookingEndpoint}`, {
                        ...payload,
                        payment_method: 'razorpay',
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_signature: response.razorpay_signature
                    });
                    showFormAlert(form, result.message || (result.success ? 'Appointment booked successfully.' : 'Unable to book appointment.'), result.success ? 'success' : 'danger');
                    if (result.success) {
                        form.reset();
                        const timeSelect = document.getElementById('appointment_time');
                        if (timeSelect) {
                            timeSelect.innerHTML = '<option value="">Select services and date first</option>';
                            timeSelect.disabled = true;
                        }
                    }
                } catch (error) {
                    console.error('Razorpay booking save failed', error);
                    showFormAlert(form, 'Payment succeeded, but booking could not be saved. Please contact the salon.');
                } finally {
                    setBookingSubmitLoading(form, false);
                }
            },
            modal: {
                ondismiss: function() {
                    setBookingSubmitLoading(form, false);
                }
            }
        });

        checkout.on('payment.failed', function(response) {
            showFormAlert(form, response.error?.description || 'Razorpay payment failed. Please try again.');
            setBookingSubmitLoading(form, false);
        });
        checkout.open();
    } catch (error) {
        console.error('Razorpay checkout failed', error);
        showFormAlert(form, 'Unable to start Razorpay payment. Please try again.');
        setBookingSubmitLoading(form, false);
    }
}

function validateBookingPayload(payload, guest) {
    const serviceIds = Array.isArray(payload.service_id) ? payload.service_id.filter(Boolean) : (payload.service_id ? [payload.service_id] : []);
    if (guest) {
        if (!payload.name || !/^[A-Za-z][A-Za-z\s.'-]{1,98}$/.test(String(payload.name).trim())) {
            return { valid: false, message: 'Please enter a valid name.', field: 'name' };
        }
        if (!payload.email || !isValidEmail(String(payload.email).trim())) {
            return { valid: false, message: 'Please enter a valid email address.', field: 'email' };
        }
        if (!payload.phone || !isValidPhone(String(payload.phone).trim())) {
            return { valid: false, message: 'Please enter a valid phone number.', field: 'phone' };
        }
    }
    if (serviceIds.length === 0) {
        return { valid: false, message: 'Please select at least one service.', field: 'service_id' };
    }
    if (!payload.appointment_date) {
        return { valid: false, message: 'Please select an appointment date.', field: 'appointment_date' };
    }
    if (payload.appointment_date < new Date().toISOString().slice(0, 10)) {
        return { valid: false, message: 'Past dates are not allowed. Please choose today or a future date.', field: 'appointment_date' };
    }
    if (!payload.appointment_time) {
        return { valid: false, message: 'Please select an available appointment time.', field: 'appointment_time' };
    }
    return { valid: true };
}

function focusBookingProblem(fieldName) {
    if (!fieldName) return;
    const input = document.querySelector(`[name="${fieldName}"], [name="${fieldName}[]"]`);
    input?.focus();
}

function setBookingSubmitLoading(form, loading) {
    const button = form.querySelector('[type="submit"]');
    if (!button) return;
    button.disabled = loading;
    button.classList.toggle('is-loading', loading);
}

// Load available time slots
async function loadTimeSlots() {
    const serviceSelect = document.getElementById('service_id');
    const dateInput = document.getElementById('appointment_date');
    const staffSelect = document.getElementById('staff_id');
    const timeSelect = document.getElementById('appointment_time');
    
    if (!serviceSelect || !dateInput || !timeSelect) return;
    
    const selectedOptions = Array.from(serviceSelect.selectedOptions || []);
    const serviceId = selectedOptions[0]?.value || serviceSelect.value;
    const serviceQuery = selectedOptions.length > 0
        ? selectedOptions.map(option => `service_ids[]=${encodeURIComponent(option.value)}`).join('&')
        : `service_id=${encodeURIComponent(serviceId)}`;
    const appointmentDate = dateInput.value;
    const staffId = staffSelect ? staffSelect.value : null;
    const priceDisplay = document.getElementById('priceDisplay');
    const servicePrice = document.getElementById('servicePrice');
    const serviceDuration = document.getElementById('serviceDuration');

    if (priceDisplay && servicePrice && serviceDuration) {
        if (selectedOptions.length > 0) {
            const totalPrice = selectedOptions.reduce((sum, option) => sum + parseFloat(option.getAttribute('data-price') || '0'), 0);
            const totalDuration = selectedOptions.reduce((sum, option) => sum + parseInt(option.getAttribute('data-duration') || '0', 10), 0);
            servicePrice.textContent = formatCurrency(totalPrice);
            serviceDuration.textContent = totalDuration;
            priceDisplay.style.display = 'block';
        } else {
            priceDisplay.style.display = 'none';
        }
    }
    
    if (!serviceId || !appointmentDate) {
        timeSelect.innerHTML = '<option value="">Select service and date first</option>';
        timeSelect.disabled = true;
        return;
    }
    
    let url = `${BASE_URL}ajax/check_slots.php?${serviceQuery}&appointment_date=${appointmentDate}`;
    if (staffId) url += `&staff_id=${staffId}`;
    const response = await fetch(url);
    const result = await response.json();
    
    timeSelect.innerHTML = '';
    
    if (result.success && result.slots && result.slots.length > 0) {
        const optionDefault = document.createElement('option');
        optionDefault.value = '';
        optionDefault.text = 'Select a time';
        timeSelect.appendChild(optionDefault);
        
        result.slots.forEach(slot => {
            const option = document.createElement('option');
            const value = typeof slot === 'string' ? slot : slot.time;
            option.value = value;
            if (typeof slot === 'string') {
                option.text = value;
            } else if (slot.available) {
                option.text = slot.label;
            } else {
                option.text = `${slot.label} - ${slot.status}`;
            }
            option.disabled = typeof slot === 'object' && !slot.available;
            timeSelect.appendChild(option);
        });
        
        timeSelect.disabled = false;
    } else {
        const option = document.createElement('option');
        option.value = '';
        option.text = result.message || 'No slots available';
        timeSelect.appendChild(option);
        timeSelect.disabled = true;
    }
}

// Delete confirmation with AJAX
function deleteWithAjax(url, itemName = 'item') {
    if (confirmDelete(`Are you sure you want to delete this ${itemName}?`)) {
        showLoading();
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(result => {
            hideLoading();
            if (result.success) {
                alert('Deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            alert('An error occurred while deleting');
        });
    }
}

// Format time for display
function formatTime(time) {
    if (!time) return '';
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

// Get appointment status color
function getStatusColor(status) {
    const colors = {
        'pending': '#F39C12',
        'confirmed': '#3498DB',
        'in-progress': '#9B59B6',
        'completed': '#27AE60',
        'cancelled': '#E74C3C'
    };
    return colors[status] || '#95A5A6';
}

// Display time slots dynamically
function displayTimeSlots(slots) {
    const container = document.getElementById('time-slots-container');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (!slots || slots.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #E74C3C;">No available slots</p>';
        return;
    }
    
    slots.forEach(time => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline';
        button.textContent = formatTime(time);
        button.style.cssText = 'margin: 0.5rem; padding: 0.75rem 1rem; border: 2px solid #8B5FBF; background: white; color: #8B5FBF; cursor: pointer;';
        button.onclick = function() {
            document.getElementById('appointment_time').value = time;
            document.querySelectorAll('#time-slots-container button').forEach(btn => {
                btn.style.background = 'white';
                btn.style.color = '#8B5FBF';
            });
            this.style.background = '#8B5FBF';
            this.style.color = 'white';
        };
        container.appendChild(button);
    });
}

// Export functions
window.checkAvailableSlots = checkAvailableSlots;
window.updateAppointmentStatus = updateAppointmentStatus;
window.bookAppointmentAjax = bookAppointmentAjax;
window.searchData = searchData;
window.loadTimeSlots = loadTimeSlots;
window.deleteWithAjax = deleteWithAjax;
window.formatTime = formatTime;
window.getStatusColor = getStatusColor;
window.displayTimeSlots = displayTimeSlots;
