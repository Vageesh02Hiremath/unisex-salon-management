<?php

function ensureBillingColumns() {
    global $conn;
    $result = $conn->query("SHOW COLUMNS FROM payments LIKE 'payment_method'");
    if ($result && $result->num_rows > 0) {
        $conn->query("ALTER TABLE payments MODIFY payment_method ENUM('cash','card','check','online_transfer','razorpay','manual','pending') DEFAULT 'pending'");
    }
}

function paidBookingGroupForAppointment($appointment) {
    $notes = (string)($appointment['notes'] ?? '');
    if (!preg_match('/(?:Guest\s+)?Booking Code:\s*([A-Z0-9-]+)/i', $notes, $matches)) {
        return null;
    }

    return fetchPrepared(
        "SELECT * FROM booking_groups WHERE booking_code = ? AND payment_method = 'razorpay' AND payment_status = 'paid' LIMIT 1",
        's',
        [$matches[1]]
    );
}

function nextBillNumber() {
    $year = date('Y');
    $row = fetchOne("SELECT bill_number FROM bills WHERE bill_number LIKE 'BILL-$year-%' ORDER BY id DESC LIMIT 1");
    $next = 1;
    if ($row && preg_match('/BILL-' . $year . '-(\d+)/', $row['bill_number'], $matches)) {
        $next = (int)$matches[1] + 1;
    }
    return 'BILL-' . $year . '-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
}

function generateBillForAppointment($appointment_id) {
    global $conn;
    ensureBillingColumns();
    $appointment_id = (int)$appointment_id;
    if ($appointment_id <= 0) {
        return ['success' => false, 'message' => 'Invalid appointment'];
    }

    $existing = fetchOne("SELECT id FROM bills WHERE appointment_id = $appointment_id LIMIT 1");
    if ($existing) {
        return ['success' => true, 'bill_id' => (int)$existing['id'], 'message' => 'Bill already exists'];
    }

    $appt = fetchOne("
        SELECT a.*, s.name AS service_name, s.price
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        WHERE a.id = $appointment_id AND a.status = 'completed'
        LIMIT 1
    ");

    if (!$appt) {
        return ['success' => false, 'message' => 'Only completed appointments can be billed'];
    }

    $total = (float)$appt['price'];
    $bill_number = nextBillNumber();
    $customer_id = (int)$appt['customer_id'];
    $service_id = (int)$appt['service_id'];
    $service_name = sanitize($appt['service_name']);
    $paid_booking = paidBookingGroupForAppointment($appt);
    $bill_status = $paid_booking ? 'paid' : 'pending';
    $payment_method = $paid_booking ? 'razorpay' : 'pending';
    $payment_status = $paid_booking ? 'paid' : 'pending';
    $payment_amount = $paid_booking ? $total : 0.0;
    $payment_notes = $paid_booking
        ? 'Paid online with Razorpay. Payment ID: ' . ($paid_booking['razorpay_payment_id'] ?? '')
        : 'Payment pending';

    $conn->begin_transaction();
    try {
        preparedQuery(
            "INSERT INTO bills (appointment_id, customer_id, bill_number, bill_date, total_amount, discount, final_amount, status, notes)
             VALUES (?, ?, ?, CURDATE(), ?, 0, ?, ?, 'Auto-generated after completed appointment')",
            'iisdds',
            [$appointment_id, $customer_id, $bill_number, $total, $total, $bill_status]
        );
        $bill_id = getLastId();

        preparedQuery(
            "INSERT INTO bill_items (bill_id, service_id, service_name, quantity, price, total)
             VALUES (?, ?, ?, 1, ?, ?)",
            'iisdd',
            [$bill_id, $service_id, $service_name, $total, $total]
        );

        preparedQuery(
            "INSERT INTO payments (bill_id, customer_id, amount, payment_method, payment_date, status, notes)
             VALUES (?, ?, ?, ?, CURDATE(), ?, ?)",
            'iidsss',
            [$bill_id, $customer_id, $payment_amount, $payment_method, $payment_status, $payment_notes]
        );

        $conn->commit();
        return ['success' => true, 'bill_id' => $bill_id, 'bill_number' => $bill_number, 'message' => 'Bill generated'];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

?>
