<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$appFolder = '/unisex_salon_management';
$appPos = strpos($scriptDir, $appFolder);
define('BASE_URL', ($appPos !== false ? substr($scriptDir, 0, $appPos + strlen($appFolder)) : $appFolder) . '/');
require_once '../config/db.php';
require_once '../config/razorpay.php';
require_once '../includes/auth.php';
require_once '../includes/booking.php';

requireCustomer();

$customer = getCurrentCustomer();
$customer_id = $customer['id'];
$error = '';
$success = '';

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book') {
    $service_ids = selectedServiceIds($_POST['service_id'] ?? []);
    $staff_id = isset($_POST['staff_id']) && $_POST['staff_id'] !== '' ? intval($_POST['staff_id']) : null;
    $appointment_date = sanitize($_POST['appointment_date'] ?? '');
    $appointment_time = sanitize($_POST['appointment_time'] ?? '');
    $promo_code = sanitize($_POST['promo_code'] ?? '');
    $payment_method = sanitize($_POST['payment_method'] ?? 'pay_at_salon');
    $payment_method = in_array($payment_method, ['pay_at_salon', 'razorpay'], true) ? $payment_method : 'pay_at_salon';
    
    if (empty($service_ids) || empty($appointment_date) || empty($appointment_time)) {
        $error = 'Please select at least one service, date, and time';
    } elseif (!isValidAppointmentDate($appointment_date)) {
        $error = 'Please select a valid appointment date.';
    } elseif ($appointment_date < date('Y-m-d')) {
        $error = 'Past dates are not allowed. Please choose today or a future date.';
    } elseif ($appointment_date === date('Y-m-d') && strtotime("$appointment_date $appointment_time") <= time()) {
        $error = 'This time has already passed today. Please choose another time or date.';
    } elseif ($payment_method === 'razorpay') {
        $error = 'Please complete the Razorpay checkout to confirm online payment.';
    } else {
        $selected_services = bookingServices($service_ids);
        if (count($selected_services) !== count($service_ids)) {
            $error = 'Please select valid active services.';
        } else {
            $totals = bookingTotals($selected_services, $promo_code);
            $available_staff = bookableStaffForSlot($appointment_date, $appointment_time, $totals['duration'], $staff_id);

            if (!$available_staff) {
                $error = 'Selected staff or time is no longer available. Please choose another time or date.';
            } else {
                $assigned_staff_id = $staff_id ?: (int)$available_staff[0]['id'];
                $booking_code = bookingCode();
                ensureBookingGroupsTable();
                $conn->begin_transaction();
                try {
                    preparedQuery(
                        "INSERT INTO booking_groups
                         (booking_code, customer_id, staff_id, appointment_date, appointment_time, total_duration, subtotal, discount_amount, total_amount, promo_code, payment_method, payment_status, status, customer_name, customer_email, customer_phone)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)",
                        'siissidddssssss',
                        [
                            $booking_code,
                            $customer_id,
                            $assigned_staff_id,
                            $appointment_date,
                            $appointment_time,
                            $totals['duration'],
                            $totals['subtotal'],
                            $totals['discount'],
                            $totals['total'],
                            $totals['promo_code'],
                            $payment_method,
                            'unpaid',
                            $customer['name'],
                            $customer['email'],
                            $customer['phone']
                        ]
                    );

                    foreach ($service_ids as $selected_service_id) {
                        preparedQuery(
                            "INSERT INTO appointments (customer_id, staff_id, service_id, appointment_date, appointment_time, status, notes, created_at)
                             VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())",
                            'iiisss',
                            [$customer_id, $assigned_staff_id, $selected_service_id, $appointment_date, $appointment_time, 'Booking Code: ' . $booking_code]
                        );
                    }
                    $conn->commit();
                    $success = 'Booking ' . $booking_code . ' confirmed for ' . count($service_ids) . ' service' . (count($service_ids) > 1 ? 's' : '') . '. You can view status in "My Appointments".';
                    $_POST = [];
                } catch (Exception $exception) {
                    $conn->rollback();
                    $error = 'Failed to book appointment. Please try again.';
                }
            }
        }
    }
}

$selected_service_ids = [];
if (isset($_GET['service_id'])) {
    $selected_service_ids[] = (int)$_GET['service_id'];
}
if (isset($_POST['service_id'])) {
    $posted_service_ids = is_array($_POST['service_id']) ? $_POST['service_id'] : [$_POST['service_id']];
    $selected_service_ids = array_values(array_unique(array_filter(array_map('intval', $posted_service_ids))));
}
$services = fetchAll("SELECT * FROM services WHERE status = 'active' ORDER BY gender_category, name");
$staff_list = fetchAll("SELECT s.id, u.name FROM staff s JOIN users u ON s.user_id = u.id WHERE u.status = 'active'");

$page_title = 'Book Appointment';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Book an Appointment</h1>
            <p>Schedule a service at your convenience</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <div class="booking-progress">
                <div><strong id="stepLabel">Step 1 of 5</strong><span id="stepTitle">Select Services</span></div>
                <div class="progress-track"><span id="progressBar"></span></div>
            </div>
            
            <form method="POST" id="bookingForm" data-base-url="<?php echo BASE_URL; ?>">
                <input type="hidden" name="action" value="book">
                
                <section class="booking-step active" data-step="1">
                    <h3>Select Service(s)</h3>
                    <div class="form-group">
                        <label for="service_id">Select Services *</label>
                        <select id="service_id" name="service_id[]" class="native-service-select" multiple tabindex="-1" aria-hidden="true" style="display:none !important;">
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" <?php echo in_array((int)$service['id'], $selected_service_ids, true) ? 'selected' : ''; ?> data-price="<?php echo $service['price']; ?>" data-duration="<?php echo $service['duration']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> (<?php echo htmlspecialchars($service['gender_category'] ?? 'Unisex'); ?>) - <?php echo formatCurrency($service['price']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="service-selection-grid">
                            <div>
                                <div class="service-picker" id="servicePicker">
                                    <button type="button" class="service-picker-control" id="servicePickerControl" aria-expanded="false" aria-controls="servicePickerMenu">
                                        <span>
                                            <strong id="servicePickerTitle">Choose services</strong>
                                            <small id="servicePickerHint">Select one or more salon services</small>
                                        </span>
                                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                    </button>
                                    <div class="service-picker-menu" id="servicePickerMenu"></div>
                                </div>
                                <small class="muted">Open the service field, then hover and click to choose one or more services.</small>
                            </div>
                            <aside class="service-amount-panel" id="serviceAmountPanel" aria-live="polite">
                                <div class="service-amount-header">
                                    <span>Amount Summary</span>
                                    <strong id="serviceAmountTotal"><?php echo formatCurrency(0); ?></strong>
                                </div>
                                <div class="service-amount-items" id="serviceAmountItems">
                                    <p class="service-amount-empty">No services selected yet.</p>
                                </div>
                                <div class="service-amount-divider"></div>
                                <div class="service-amount-line">
                                    <span>Subtotal</span>
                                    <strong id="serviceAmountSubtotal"><?php echo formatCurrency(0); ?></strong>
                                </div>
                                <div class="service-amount-line service-amount-discount" id="serviceAmountDiscountRow" hidden>
                                    <span>Discount</span>
                                    <strong id="serviceAmountDiscount">-<?php echo formatCurrency(0); ?></strong>
                                </div>
                                <div class="service-amount-duration">
                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    <span id="serviceAmountDuration">0 minutes</span>
                                </div>
                            </aside>
                        </div>
                    </div>
                </section>

                <section class="booking-step" data-step="2">
                    <h3>Select Staff</h3>
                    <div class="form-group">
                        <label for="staff_id">Preferred Staff</label>
                        <select id="staff_id" name="staff_id">
                            <option value="">Auto-assign available staff</option>
                            <?php foreach ($staff_list as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="muted">Leave this as auto-assign if any available professional is fine.</small>
                    </div>
                </section>

                <section class="booking-step" data-step="3">
                    <h3>Choose Date & Time</h3>
                    <div class="form-group">
                        <label for="appointment_date">Appointment Date *</label>
                        <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="appointment_time">Appointment Time *</label>
                        <select id="appointment_time" name="appointment_time" required disabled>
                            <option value="">Select services and date first</option>
                        </select>
                    </div>
                    <div class="slot-legend"><span>Available</span><span>Almost Full</span><span>Booked</span></div>
                </section>

                <section class="booking-step" data-step="4">
                    <h3>Customer Details</h3>
                    <div class="form-grid">
                        <div class="form-group"><label>Name</label><input type="text" value="<?php echo htmlspecialchars($customer['name']); ?>" readonly></div>
                        <div class="form-group"><label>Email</label><input type="email" value="<?php echo htmlspecialchars($customer['email']); ?>" readonly></div>
                        <div class="form-group"><label>Phone</label><input type="tel" value="<?php echo htmlspecialchars($customer['phone']); ?>" readonly></div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group"><label for="promo_code">Promo Code</label><input type="text" id="promo_code" name="promo_code" placeholder="WELCOME10" oninput="updateBookingSummary()"></div>
                        <div class="form-group"><label for="payment_method">Payment Option</label><select id="payment_method" name="payment_method" onchange="updateBookingSummary()"><option value="pay_at_salon">Pay at Salon</option><option value="razorpay">Pay Online with Razorpay</option></select></div>
                    </div>
                </section>

                <section class="booking-step" data-step="5">
                    <h3>Confirm Booking</h3>
                    <div class="form-group info-panel" id="priceDisplay" style="display: block;">
                        <strong>Total Price: <span id="servicePrice">-</span></strong>
                        <small style="display: block; margin-top: 0.5rem; color: #666;">Total Duration: <span id="serviceDuration">-</span> minutes</small>
                        <small style="display: block; margin-top: 0.5rem; color: #666;">Discount: <span id="discountAmount">-</span></small>
                    </div>
                    <div class="booking-summary" id="bookingSummary"></div>
                    <button type="submit" class="btn btn-primary btn-block" id="confirmBookingButton"><i class="fa-solid fa-calendar-plus"></i> Confirm Booking</button>
                </section>

                <div class="booking-actions">
                    <button type="button" class="btn btn-secondary" id="prevStep">Back</button>
                    <button type="button" class="btn btn-primary" id="nextStep">Next</button>
                </div>
            </form>
        </div>

        <div class="form-container" style="margin-top: 2rem;">
            <h3><i class="fa-solid fa-list-check"></i> How to Book</h3>
            <ol class="steps-list">
                <li>Select one or more services from the list</li>
                <li>Choose your preferred date (must be today or later)</li>
                <li>Select an available time slot</li>
                <li>Optionally choose a preferred staff member</li>
                <li>Click "Book Appointment" to confirm</li>
                <li>You can track approval or rejection from My Appointments</li>
            </ol>
        </div>
    </div>

    <?php if (razorpayIsConfigured()): ?>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <?php endif; ?>
    <script>
        const BOOKING_BASE_URL = '<?php echo BASE_URL; ?>';
        const RAZORPAY_ENABLED = <?php echo razorpayIsConfigured() ? 'true' : 'false'; ?>;
        let bookingStep = 1;
        const stepTitles = ['Select Services', 'Select Staff', 'Choose Date & Time', 'Customer Details', 'Confirm Booking'];

        function selectedServiceOptions() {
            return Array.from(document.getElementById('service_id')?.selectedOptions || []);
        }

        function serviceOptionLabel(option) {
            return option.textContent.replace(/\s+/g, ' ').trim();
        }

        function serviceOptionName(option) {
            return serviceOptionLabel(option).split(' - ')[0];
        }

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
        }

        function selectedServiceIdsQuery() {
            return selectedServiceOptions().map(option => `service_ids[]=${encodeURIComponent(option.value)}`).join('&');
        }
        
        async function bookingLoadTimeSlots() {
            const serviceSelect = document.getElementById('service_id');
            const dateInput = document.getElementById('appointment_date');
            const staffSelect = document.getElementById('staff_id');
            const timeSelect = document.getElementById('appointment_time');
            const priceDisplay = document.getElementById('priceDisplay');
            const servicePrice = document.getElementById('servicePrice');
            const serviceDuration = document.getElementById('serviceDuration');
            const discountAmount = document.getElementById('discountAmount');
            
            const selectedOptions = selectedServiceOptions();
            const previouslySelectedTime = timeSelect.value;

            if (selectedOptions.length > 0) {
                const totals = bookingTotals();
                servicePrice.textContent = formatCurrency(totals.total);
                serviceDuration.textContent = totals.totalDuration;
                if (discountAmount) discountAmount.textContent = formatCurrency(totals.discount);
                priceDisplay.style.display = 'block';
            } else {
                priceDisplay.style.display = 'none';
            }
            
            if (selectedOptions.length === 0 || !dateInput.value) {
                timeSelect.innerHTML = '<option value="">Select service and date first</option>';
                timeSelect.disabled = true;
                timeSelect.value = '';
                updateBookingSummary();
                return;
            }
            
            try {
                let url = `${BOOKING_BASE_URL}ajax/check_slots.php?${selectedServiceIdsQuery()}&appointment_date=${dateInput.value}`;
                if (staffSelect && staffSelect.value) {
                    url += `&staff_id=${staffSelect.value}`;
                }
                
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
                        option.text = typeof slot === 'string' ? bookingFormatTime(value) : `${slot.label} - ${slot.status}`;
                        option.disabled = typeof slot === 'object' && !slot.available;
                        if (value === previouslySelectedTime && !option.disabled) {
                            option.selected = true;
                        }
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
            } catch (error) {
                console.error('Error:', error);
                timeSelect.innerHTML = '<option value="">Error loading slots</option>';
            }
            updateBookingSummary();
        }
        
        function bookingFormatTime(time) {
            const [hours, minutes] = time.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        }

        function bookingTotals() {
            const selectedOptions = selectedServiceOptions();
            const subtotal = selectedOptions.reduce((sum, option) => sum + parseFloat(option.getAttribute('data-price') || '0'), 0);
            const totalDuration = selectedOptions.reduce((sum, option) => sum + parseInt(option.getAttribute('data-duration') || '0', 10), 0);
            const code = (document.getElementById('promo_code')?.value || '').trim().toUpperCase();
            let discount = 0;
            if (code === 'WELCOME10') discount = Math.round((subtotal * 0.10) * 100) / 100;
            if (code === 'SALON20' && subtotal >= 100) discount = 20;
            discount = Math.min(discount, subtotal);
            const total = Math.max(0, subtotal - discount);
            return {
                subtotal: Math.round(subtotal * 100) / 100,
                totalDuration,
                discount,
                total: Math.round(total * 100) / 100
            };
        }

        function updateServiceAmountPanel() {
            const selectedOptions = selectedServiceOptions();
            const totals = bookingTotals();
            const items = document.getElementById('serviceAmountItems');
            const total = document.getElementById('serviceAmountTotal');
            const subtotal = document.getElementById('serviceAmountSubtotal');
            const discount = document.getElementById('serviceAmountDiscount');
            const discountRow = document.getElementById('serviceAmountDiscountRow');
            const duration = document.getElementById('serviceAmountDuration');

            if (items) {
                items.innerHTML = selectedOptions.length
                    ? selectedOptions.map(option => {
                        const price = parseFloat(option.dataset.price || '0');
                        return `
                            <div class="service-amount-item">
                                <span>${escapeHtml(serviceOptionName(option))}</span>
                                <strong>${formatCurrency(price)}</strong>
                            </div>
                        `;
                    }).join('')
                    : '<p class="service-amount-empty">No services selected yet.</p>';
            }
            if (total) total.textContent = formatCurrency(totals.total);
            if (subtotal) subtotal.textContent = formatCurrency(totals.subtotal);
            if (discount) discount.textContent = `-${formatCurrency(totals.discount)}`;
            if (discountRow) discountRow.hidden = totals.discount <= 0;
            if (duration) duration.textContent = `${totals.totalDuration} minute${totals.totalDuration === 1 ? '' : 's'}`;
        }

        function updateBookingSummary() {
            const selectedOptions = selectedServiceOptions();
            const totals = bookingTotals();
            const date = document.getElementById('appointment_date')?.value || '-';
            const time = document.getElementById('appointment_time')?.value || '';
            const staffSelect = document.getElementById('staff_id');
            const staff = staffSelect?.value ? staffSelect.options[staffSelect.selectedIndex].text : 'Auto-assign available staff';
            const payment = document.getElementById('payment_method')?.selectedOptions[0]?.text || 'Pay at Salon';
            const summary = document.getElementById('bookingSummary');
            const servicePrice = document.getElementById('servicePrice');
            const serviceDuration = document.getElementById('serviceDuration');
            const discountAmount = document.getElementById('discountAmount');
            if (servicePrice) servicePrice.textContent = formatCurrency(totals.total);
            if (serviceDuration) serviceDuration.textContent = totals.totalDuration;
            if (discountAmount) discountAmount.textContent = formatCurrency(totals.discount);
            updateServiceAmountPanel();
            if (!summary) return;
            summary.innerHTML = `
                <p><strong>Services:</strong> ${selectedOptions.map(option => serviceOptionName(option)).join(', ') || '-'}</p>
                <p><strong>Date & Time:</strong> ${date} ${time ? bookingFormatTime(time) : ''}</p>
                <p><strong>Staff:</strong> ${staff}</p>
                <p><strong>Payment:</strong> ${payment}</p>
                <p><strong>Total:</strong> ${formatCurrency(totals.total)} (${totals.totalDuration} minutes)</p>
            `;
            const selectedServices = document.getElementById('selectedServices');
            if (selectedServices) {
                selectedServices.innerHTML = selectedOptions.map(option => `<div class="service-chip">${escapeHtml(serviceOptionLabel(option))}</div>`).join('');
            }
            updateServicePickerLabel();
        }

        function showBookingStep(step) {
            bookingStep = Math.max(1, Math.min(5, step));
            document.querySelectorAll('.booking-step').forEach(section => section.classList.toggle('active', Number(section.dataset.step) === bookingStep));
            document.getElementById('stepLabel').textContent = `Step ${bookingStep} of 5`;
            document.getElementById('stepTitle').textContent = stepTitles[bookingStep - 1];
            document.getElementById('progressBar').style.width = `${bookingStep * 20}%`;
            document.getElementById('prevStep').style.display = bookingStep === 1 ? 'none' : 'inline-flex';
            document.getElementById('nextStep').style.display = bookingStep === 5 ? 'none' : 'inline-flex';
            updateBookingSummary();
        }

        function currentStepValid() {
            if (bookingStep === 1 && selectedServiceOptions().length === 0) return false;
            if (bookingStep === 3 && (!document.getElementById('appointment_date').value || !document.getElementById('appointment_time').value)) return false;
            return true;
        }

        function updateServicePickerLabel() {
            const selectedOptions = selectedServiceOptions();
            const title = document.getElementById('servicePickerTitle');
            const hint = document.getElementById('servicePickerHint');
            const control = document.getElementById('servicePickerControl');
            if (!title || !hint || !control) return;

            if (!selectedOptions.length) {
                title.textContent = 'Choose services';
                hint.textContent = 'Select one or more salon services';
                control.classList.remove('has-selection');
                return;
            }

            title.textContent = `${selectedOptions.length} service${selectedOptions.length > 1 ? 's' : ''} selected`;
            hint.textContent = selectedOptions.map(option => serviceOptionName(option)).join(', ');
            control.classList.add('has-selection');
        }

        function renderServicePicker() {
            const select = document.getElementById('service_id');
            const picker = document.getElementById('servicePicker');
            const control = document.getElementById('servicePickerControl');
            const menu = document.getElementById('servicePickerMenu');
            if (!select || !picker || !control || !menu) return;

            function renderOptions() {
                menu.innerHTML = Array.from(select.options).map(option => {
                    const selected = option.selected ? ' is-selected' : '';
                    const price = formatCurrency(parseFloat(option.dataset.price || '0'));
                    const duration = parseInt(option.dataset.duration || '0', 10);
                    const name = serviceOptionName(option);
                    return `
                        <button type="button" class="service-picker-option${selected}" data-value="${option.value}">
                            <span class="service-option-check"><i class="fa-solid fa-check"></i></span>
                            <span class="service-option-copy">
                                <strong>${escapeHtml(name)}</strong>
                                <small>${price} - ${duration} min</small>
                            </span>
                        </button>
                    `;
                }).join('');
            }

            function syncSelection(value) {
                const option = Array.from(select.options).find(item => item.value === value);
                if (!option) return;
                option.selected = !option.selected;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                renderOptions();
                updateServicePickerLabel();
            }

            control.addEventListener('click', () => {
                const open = picker.classList.toggle('is-open');
                control.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            menu.addEventListener('click', (event) => {
                const button = event.target.closest('.service-picker-option');
                if (!button) return;
                syncSelection(button.dataset.value);
            });

            document.addEventListener('click', (event) => {
                if (picker.contains(event.target)) return;
                picker.classList.remove('is-open');
                control.setAttribute('aria-expanded', 'false');
            });

            renderOptions();
            updateServicePickerLabel();
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderServicePicker();
            document.getElementById('nextStep')?.addEventListener('click', async () => {
                if (!currentStepValid()) {
                    alert('Please complete this step before continuing.');
                    return;
                }
                if (bookingStep === 2) await bookingLoadTimeSlots();
                showBookingStep(bookingStep + 1);
            });
            document.getElementById('prevStep')?.addEventListener('click', () => showBookingStep(bookingStep - 1));
            document.getElementById('service_id')?.addEventListener('change', () => { updateBookingSummary(); bookingLoadTimeSlots(); });
            document.getElementById('staff_id')?.addEventListener('change', bookingLoadTimeSlots);
            document.getElementById('appointment_date')?.addEventListener('change', bookingLoadTimeSlots);
            document.getElementById('appointment_time')?.addEventListener('change', updateBookingSummary);
            async function handleRazorpayBooking(form) {
                if (!RAZORPAY_ENABLED || typeof Razorpay === 'undefined') {
                    alert('Razorpay is not configured or could not be loaded. Please choose Pay at Salon.');
                    return;
                }

                const button = document.getElementById('confirmBookingButton');
                const originalText = button?.innerHTML;
                if (button) {
                    button.disabled = true;
                    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Preparing Payment';
                }

                const payload = bookingFormPayload();
                try {
                    const orderResponse = await fetch(`${BOOKING_BASE_URL}ajax/create_razorpay_order.php`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    });
                    const order = await orderResponse.json();
                    if (!order.success) throw new Error(order.message || 'Could not create payment order.');

                    const razorpay = new Razorpay({
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
                                const bookingResponse = await fetch(`${BOOKING_BASE_URL}ajax/book_appointment.php`, {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/json'},
                                    body: JSON.stringify({
                                        ...payload,
                                        payment_method: 'razorpay',
                                        razorpay_order_id: response.razorpay_order_id,
                                        razorpay_payment_id: response.razorpay_payment_id,
                                        razorpay_signature: response.razorpay_signature
                                    })
                                });
                                const booking = await bookingResponse.json();
                                if (!booking.success) throw new Error(booking.message || 'Payment succeeded, but booking could not be saved.');
                                alert(`Booking ${booking.booking_id} confirmed and paid successfully.`);
                                window.location.href = `${BOOKING_BASE_URL}customer/my_appointments.php`;
                            } catch (error) {
                                alert(error.message || 'Payment succeeded, but booking could not be saved. Please contact the salon.');
                                if (button) {
                                    button.disabled = false;
                                    button.innerHTML = originalText;
                                }
                            }
                        },
                        modal: {
                            ondismiss: function() {
                                if (button) {
                                    button.disabled = false;
                                    button.innerHTML = originalText;
                                }
                            }
                        }
                    });

                    razorpay.on('payment.failed', function(response) {
                        alert(response.error?.description || 'Razorpay payment failed. Please try again.');
                        if (button) {
                            button.disabled = false;
                            button.innerHTML = originalText;
                        }
                    });
                    razorpay.open();
                } catch (error) {
                    alert(error.message || 'Unable to start Razorpay payment.');
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = originalText;
                    }
                }
            }

            function bookingFormPayload() {
                return {
                    customer_id: <?php echo (int)$customer_id; ?>,
                    service_ids: selectedServiceOptions().map(option => option.value),
                    staff_id: document.getElementById('staff_id')?.value || '',
                    appointment_date: document.getElementById('appointment_date')?.value || '',
                    appointment_time: document.getElementById('appointment_time')?.value || '',
                    promo_code: document.getElementById('promo_code')?.value || '',
                    payment_method: document.getElementById('payment_method')?.value || 'pay_at_salon'
                };
            }

            document.getElementById('bookingForm')?.addEventListener('submit', (event) => {
                if (selectedServiceOptions().length === 0) {
                    event.preventDefault();
                    showBookingStep(1);
                    alert('Please select at least one service before confirming.');
                    return;
                }
                if (!document.getElementById('appointment_date').value || !document.getElementById('appointment_time').value) {
                    event.preventDefault();
                    showBookingStep(3);
                    alert('Please select an appointment date and time before confirming.');
                    return;
                }
                if (document.getElementById('payment_method')?.value === 'razorpay') {
                    event.preventDefault();
                    handleRazorpayBooking(event.currentTarget);
                }
            });
            showBookingStep(1);
            if (selectedServiceOptions().length > 0) bookingLoadTimeSlots();
        });
    </script>

    <?php include '../includes/footer.php'; ?>
