<?php

function ensureBookingGroupsTable() {
    global $conn;
    $conn->query("CREATE TABLE IF NOT EXISTS booking_groups (
        id INT PRIMARY KEY AUTO_INCREMENT,
        booking_code VARCHAR(30) NOT NULL UNIQUE,
        customer_id INT NOT NULL,
        staff_id INT DEFAULT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        total_duration INT NOT NULL DEFAULT 0,
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
        discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        promo_code VARCHAR(50) DEFAULT NULL,
        payment_method ENUM('pay_at_salon','online_simulated','razorpay') DEFAULT 'pay_at_salon',
        payment_status ENUM('paid','unpaid') DEFAULT 'unpaid',
        razorpay_order_id VARCHAR(100) DEFAULT NULL,
        razorpay_payment_id VARCHAR(100) DEFAULT NULL,
        razorpay_signature VARCHAR(255) DEFAULT NULL,
        status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
        customer_name VARCHAR(100) DEFAULT NULL,
        customer_email VARCHAR(100) DEFAULT NULL,
        customer_phone VARCHAR(20) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_customer (customer_id),
        INDEX idx_date_time (appointment_date, appointment_time),
        INDEX idx_status (status)
    )");

    $conn->query("ALTER TABLE booking_groups MODIFY payment_method ENUM('pay_at_salon','online_simulated','razorpay') DEFAULT 'pay_at_salon'");
    if (!bookingGroupColumnExists('razorpay_order_id')) {
        $conn->query("ALTER TABLE booking_groups ADD COLUMN razorpay_order_id VARCHAR(100) DEFAULT NULL AFTER payment_status");
    }
    if (!bookingGroupColumnExists('razorpay_payment_id')) {
        $conn->query("ALTER TABLE booking_groups ADD COLUMN razorpay_payment_id VARCHAR(100) DEFAULT NULL AFTER razorpay_order_id");
    }
    if (!bookingGroupColumnExists('razorpay_signature')) {
        $conn->query("ALTER TABLE booking_groups ADD COLUMN razorpay_signature VARCHAR(255) DEFAULT NULL AFTER razorpay_payment_id");
    }
}

function bookingGroupColumnExists($column) {
    global $conn;
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM booking_groups LIKE '$column'");
    return $result && $result->num_rows > 0;
}

function selectedServiceIds($raw) {
    if (!is_array($raw)) {
        $raw = [$raw];
    }
    return array_values(array_unique(array_filter(array_map('intval', $raw), function($id) {
        return $id > 0;
    })));
}

function bookingServices($service_ids) {
    if (!$service_ids) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($service_ids), '?'));
    return fetchAllPrepared(
        "SELECT * FROM services WHERE status = 'active' AND id IN ($placeholders) ORDER BY FIELD(id, $placeholders)",
        str_repeat('i', count($service_ids) * 2),
        array_merge($service_ids, $service_ids)
    );
}

function bookingTotals($services, $promo_code = '') {
    $subtotal = 0.0;
    $duration = 0;
    foreach ($services as $service) {
        $subtotal += (float)$service['price'];
        $duration += (int)$service['duration'];
    }

    $code = strtoupper(trim((string)$promo_code));
    $discount = 0.0;
    if ($code === 'WELCOME10') {
        $discount = round($subtotal * 0.10, 2);
    } elseif ($code === 'SALON20' && $subtotal >= 100) {
        $discount = 20.0;
    }

    return [
        'subtotal' => $subtotal,
        'duration' => $duration,
        'discount' => min($discount, $subtotal),
        'total' => max(0, $subtotal - $discount),
        'promo_code' => $discount > 0 ? $code : ''
    ];
}

function bookingBufferMinutes() {
    $row = fetchOne("SELECT setting_value FROM settings WHERE setting_name = 'booking_buffer_minutes' LIMIT 1");
    if (!$row) {
        executeQuery("INSERT IGNORE INTO settings (setting_name, setting_value) VALUES ('booking_buffer_minutes', '10')");
        return 10;
    }
    return max(0, (int)$row['setting_value']);
}

function staffWorkingWindow($staff_id, $date) {
    $staff_id = (int)$staff_id;
    $staff = fetchPrepared(
        "SELECT s.*, u.name FROM staff s JOIN users u ON s.user_id = u.id WHERE s.id = ? AND u.status = 'active' LIMIT 1",
        'i',
        [$staff_id]
    );
    if (!$staff) {
        return null;
    }

    $day = date('D', strtotime($date));
    if (!empty($staff['days_working'])) {
        $days = array_map('trim', explode(',', $staff['days_working']));
        if ($days && !in_array($day, $days, true)) {
            return null;
        }
    }

    return [
        'id' => (int)$staff['id'],
        'name' => $staff['name'],
        'start' => $staff['availability_start'] ?: '09:00:00',
        'end' => $staff['availability_end'] ?: '19:00:00'
    ];
}

function activeStaffList() {
    return fetchAll("SELECT s.id, u.name FROM staff s JOIN users u ON s.user_id = u.id WHERE u.status = 'active' ORDER BY u.name");
}

function intervalOverlaps($start_a, $end_a, $start_b, $end_b) {
    return $start_a < $end_b && $end_a > $start_b;
}

function staffIsAvailable($staff_id, $date, $time, $duration_minutes, $buffer_minutes = null) {
    $buffer_minutes = $buffer_minutes === null ? bookingBufferMinutes() : (int)$buffer_minutes;
    $window = staffWorkingWindow($staff_id, $date);
    if (!$window) {
        return false;
    }

    $start = strtotime("$date $time");
    $end = $start + (($duration_minutes + $buffer_minutes) * 60);
    $work_start = strtotime("$date {$window['start']}");
    $work_end = strtotime("$date {$window['end']}");
    if ($start < $work_start || $end > $work_end) {
        return false;
    }

    if ($date === date('Y-m-d') && $start <= time()) {
        return false;
    }

    $booked = fetchAllPrepared(
        "SELECT a.appointment_time, s.duration
         FROM appointments a
         JOIN services s ON a.service_id = s.id
         WHERE a.appointment_date = ? AND a.staff_id = ? AND a.status NOT IN ('cancelled','rejected')",
        'si',
        [$date, (int)$staff_id]
    );
    foreach ($booked as $appointment) {
        $booked_start = strtotime("$date {$appointment['appointment_time']}");
        $booked_end = $booked_start + (((int)$appointment['duration'] + $buffer_minutes) * 60);
        if (intervalOverlaps($start, $end, $booked_start, $booked_end)) {
            return false;
        }
    }

    return true;
}

function staffHasBookingOverlap($staff_id, $date, $time, $duration_minutes, $buffer_minutes = null) {
    $buffer_minutes = $buffer_minutes === null ? bookingBufferMinutes() : (int)$buffer_minutes;
    $start = strtotime("$date $time");
    $end = $start + (($duration_minutes + $buffer_minutes) * 60);

    $booked = fetchAllPrepared(
        "SELECT a.appointment_time, s.duration
         FROM appointments a
         JOIN services s ON a.service_id = s.id
         WHERE a.appointment_date = ? AND a.staff_id = ? AND a.status NOT IN ('cancelled','rejected')",
        'si',
        [$date, (int)$staff_id]
    );

    foreach ($booked as $appointment) {
        $booked_start = strtotime("$date {$appointment['appointment_time']}");
        $booked_end = $booked_start + (((int)$appointment['duration'] + $buffer_minutes) * 60);
        if (intervalOverlaps($start, $end, $booked_start, $booked_end)) {
            return true;
        }
    }

    return false;
}

function staffBookingWindow($staff_id) {
    $staff = fetchPrepared(
        "SELECT s.*, u.name FROM staff s JOIN users u ON s.user_id = u.id WHERE s.id = ? AND u.status = 'active' LIMIT 1",
        'i',
        [(int)$staff_id]
    );
    if (!$staff) {
        return null;
    }

    return [
        'id' => (int)$staff['id'],
        'name' => $staff['name'],
        'start' => $staff['availability_start'] ?: '09:00:00',
        'end' => $staff['availability_end'] ?: '19:00:00'
    ];
}

function staffCanTakeBookingSlot($staff_id, $date, $time, $duration_minutes, $buffer_minutes = null) {
    $buffer_minutes = $buffer_minutes === null ? bookingBufferMinutes() : (int)$buffer_minutes;
    $window = staffWorkingWindow($staff_id, $date);
    if (!$window) {
        return false;
    }

    $start = strtotime("$date $time");
    $end = $start + (($duration_minutes + $buffer_minutes) * 60);
    $work_start = strtotime("$date {$window['start']}");
    $work_end = strtotime("$date {$window['end']}");

    if ($start < $work_start || $end > $work_end) {
        return false;
    }

    if ($date === date('Y-m-d') && $start <= time()) {
        return false;
    }

    return !staffHasBookingOverlap((int)$staff_id, $date, $time, $duration_minutes, $buffer_minutes);
}

function bookableStaffForSlot($date, $time, $duration_minutes, $staff_id = null, $buffer_minutes = null) {
    if ($staff_id) {
        $staff = staffWorkingWindow((int)$staff_id, $date);
        return $staff && staffCanTakeBookingSlot((int)$staff_id, $date, $time, $duration_minutes, $buffer_minutes) ? [$staff] : [];
    }

    $available = [];
    foreach (activeStaffList() as $staff) {
        if (staffCanTakeBookingSlot((int)$staff['id'], $date, $time, $duration_minutes, $buffer_minutes)) {
            $available[] = $staff;
        }
    }

    return $available;
}

function slotHasBookingOverlap($date, $time, $duration_minutes, $staff_id = null, $buffer_minutes = null) {
    if ($staff_id) {
        return staffHasBookingOverlap((int)$staff_id, $date, $time, $duration_minutes, $buffer_minutes);
    }

    foreach (activeStaffList() as $staff) {
        if (staffHasBookingOverlap((int)$staff['id'], $date, $time, $duration_minutes, $buffer_minutes)) {
            return true;
        }
    }

    return false;
}

function availableStaffForSlot($date, $time, $duration_minutes) {
    $available = [];
    foreach (activeStaffList() as $staff) {
        if (staffIsAvailable((int)$staff['id'], $date, $time, $duration_minutes)) {
            $available[] = $staff;
        }
    }
    return $available;
}

function bookingCode() {
    return 'BK' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function isValidAppointmentDate($date) {
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date;
}

?>
