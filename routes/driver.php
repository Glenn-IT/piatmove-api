<?php
// GET  /driver/requests
// GET  /driver/history
// GET  /driver/reports
// GET  /driver/daily-income
// GET  /driver/trips
// POST /driver/accept/{id}
// POST /driver/reject/{id}
// POST /driver/cancel/{id}
// POST /driver/start/{id}
// POST /driver/complete/{id}
// PUT  /driver/location
// PUT  /driver/status

$method = $_SERVER['REQUEST_METHOD'];
$action = $segments[1] ?? '';
$id     = isset($segments[2]) && is_numeric($segments[2]) ? (int)$segments[2] : null;

$user = require_auth();
require_role($user, 'driver');
$db = get_db();

// Always check approval status for operational actions
$stmt = $db->prepare('SELECT * FROM driver_info WHERE user_id = ?');
$stmt->execute([$user['id']]);
$driver_info = $stmt->fetch();

if ($method === 'GET' && $action === 'profile') {

    $stmt = $db->prepare(
        'SELECT u.id, u.name, u.email, u.phone, u.role, u.status AS account_status,
                COALESCE(u.photo_path, d.photo_path) AS photo_path,
                d.license_no, d.vehicle_no, d.vehicle_type, d.barangay,
                d.approval_status, d.is_online, d.current_lat, d.current_lng,
                COALESCE((SELECT ROUND(AVG(rating), 1) FROM bookings WHERE driver_id = u.id AND rating IS NOT NULL), 5.0) AS rating,
                (SELECT COUNT(rating) FROM bookings WHERE driver_id = u.id AND rating IS NOT NULL) AS rating_count
         FROM users u
         JOIN driver_info d ON d.user_id = u.id
         WHERE u.id = ?'
    );
    $stmt->execute([$user['id']]);
    $profile = $stmt->fetch();
    if (!$profile) json_error('Driver profile not found', 404);
    json_success($profile);

} elseif (($method === 'PUT' || $method === 'POST') && $action === 'profile') {

    $body     = get_body();
    $phone    = trim($body['phone'] ?? '');
    $barangay = trim($body['barangay'] ?? '');
    $curPass  = $body['current_password'] ?? '';
    $newPass  = $body['new_password'] ?? '';

    // Validate phone if provided
    if ($phone) {
        if (!preg_match('/^\+?[0-9\s\-]{7,15}$/', $phone)) {
            json_error('Invalid phone number format');
        }
        $db->prepare('UPDATE users SET phone = ? WHERE id = ?')->execute([$phone, $user['id']]);
    }

    // Validate and update barangay if provided
    if ($barangay) {
        $db->prepare('UPDATE driver_info SET barangay = ? WHERE user_id = ?')->execute([$barangay, $user['id']]);
    }

    // Handle password change if requested
    if (!empty($newPass)) {
        if (strlen($newPass) < 6) {
            json_error('New password must be at least 6 characters');
        }
        if (empty($curPass)) {
            json_error('Current password is required to set a new password');
        }

        $uStmt = $db->prepare('SELECT password FROM users WHERE id = ?');
        $uStmt->execute([$user['id']]);
        $uRow = $uStmt->fetch();

        if (!$uRow || !password_verify($curPass, $uRow['password'])) {
            json_error('Current password is incorrect', 400);
        }

        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, $user['id']]);
    }

    json_success(null, 'Driver credentials updated successfully');

} elseif ($method === 'GET' && $action === 'status') {

    json_success([
        'is_online'       => (bool)($driver_info['is_online'] ?? 0),
        'approval_status' => $driver_info['approval_status'] ?? 'pending'
    ]);

} elseif ($method === 'PUT' && $action === 'status') {

    $body   = get_body();
    if (!isset($body['is_online'])) json_error('is_online is required');
    $online = (int)(bool)$body['is_online'];

    if ($online && (!$driver_info || $driver_info['approval_status'] !== 'approved')) {
        json_error('Cannot go online — your driver account is pending admin approval', 403);
    }

    $db->prepare('UPDATE driver_info SET is_online = ? WHERE user_id = ?')
       ->execute([$online, $user['id']]);
    json_success(['is_online' => (bool)$online, 'approval_status' => $driver_info['approval_status'] ?? 'pending'], 'Status updated');

} else {

    // All actions below require an approved driver
    if (!$driver_info || $driver_info['approval_status'] !== 'approved') {
        json_error('Your driver account is not yet approved', 403);
    }

    if ($method === 'GET' && $action === 'requests') {

        $stmt = $db->prepare(
            'SELECT b.*, u.name AS passenger_name, u.phone AS passenger_phone
             FROM bookings b
             JOIN users u ON u.id = b.passenger_id
             WHERE b.status = ?
             ORDER BY b.created_at DESC'
        );
        $stmt->execute(['pending']);
        json_success($stmt->fetchAll());

    } elseif ($method === 'GET' && $action === 'history') {

        $stmt = $db->prepare(
            'SELECT b.*,
                    u.name  AS passenger_name,
                    u.phone AS passenger_phone
             FROM bookings b
             LEFT JOIN users u ON u.id = b.passenger_id
             WHERE b.driver_id = ?
               AND b.status IN (\'completed\', \'cancelled\', \'rejected\')
             ORDER BY b.created_at DESC'
        );
        $stmt->execute([$user['id']]);
        json_success(array_values($stmt->fetchAll()));

    } elseif ($method === 'GET' && ($action === 'reports' || $action === 'daily-income')) {

        $date = $_GET['date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $stmt = $db->prepare(
            'SELECT b.*,
                    u.name  AS passenger_name,
                    u.phone AS passenger_phone
             FROM bookings b
             LEFT JOIN users u ON u.id = b.passenger_id
             WHERE b.driver_id = ?
               AND b.status = \'completed\'
               AND (DATE(b.created_at) = ? OR DATE(b.updated_at) = ?)
             ORDER BY b.created_at DESC'
        );
        $stmt->execute([$user['id'], $date, $date]);
        $trips = $stmt->fetchAll();

        $total_income = 0.0;
        $regular_trips = 0;
        $discounted_trips = 0;
        $student_trips = 0;
        $senior_trips = 0;
        $pwd_trips = 0;
        $pregnant_trips = 0;

        foreach ($trips as $t) {
            $fare = (float)($t['fare'] ?? 0);
            $total_income += $fare;
            $d = strtolower($t['discount_type'] ?? 'regular');
            if ($d === 'regular' || empty($d)) {
                $regular_trips++;
            } else {
                $discounted_trips++;
                if ($d === 'student') $student_trips++;
                elseif ($d === 'senior') $senior_trips++;
                elseif ($d === 'pwd') $pwd_trips++;
                elseif ($d === 'pregnant') $pregnant_trips++;
            }
        }

        json_success([
            'date'              => $date,
            'total_income'      => round($total_income, 2),
            'total_trips'       => count($trips),
            'regular_trips'     => $regular_trips,
            'discounted_trips'  => $discounted_trips,
            'student_trips'     => $student_trips,
            'senior_trips'      => $senior_trips,
            'pwd_trips'         => $pwd_trips,
            'pregnant_trips'    => $pregnant_trips,
            'trips'             => array_values($trips)
        ]);

    } elseif ($method === 'GET' && $action === 'trips') {

        $status = $_GET['status'] ?? 'all';
        if ($status !== 'all') {
            $stmt = $db->prepare(
                'SELECT b.*,
                        u.name  AS passenger_name,
                        u.phone AS passenger_phone
                 FROM bookings b
                 LEFT JOIN users u ON u.id = b.passenger_id
                 WHERE b.driver_id = ? AND b.status = ?
                 ORDER BY b.created_at DESC'
            );
            $stmt->execute([$user['id'], $status]);
        } else {
            $stmt = $db->prepare(
                'SELECT b.*,
                        u.name  AS passenger_name,
                        u.phone AS passenger_phone
                 FROM bookings b
                 LEFT JOIN users u ON u.id = b.passenger_id
                 WHERE b.driver_id = ?
                 ORDER BY b.created_at DESC'
            );
            $stmt->execute([$user['id']]);
        }
        json_success(array_values($stmt->fetchAll()));

    } elseif ($method === 'POST' && $action === 'accept' && $id) {

        $stmt = $db->prepare(
            "UPDATE bookings SET status = 'accepted', driver_id = ? WHERE id = ? AND status = 'pending'"
        );
        $stmt->execute([$user['id'], $id]);
        if ($stmt->rowCount() === 0) json_error('Booking not found or no longer available', 404);

        // Fetch passenger & driver details for email notification
        try {
            $stmt = $db->prepare(
                'SELECT b.pickup_address, b.dropoff_address, b.fare,
                        p.name AS passenger_name, p.email AS passenger_email,
                        u.name AS driver_name, u.phone AS driver_phone,
                        d.vehicle_no
                 FROM bookings b
                 JOIN users p ON p.id = b.passenger_id
                 JOIN users u ON u.id = ?
                 LEFT JOIN driver_info d ON d.user_id = u.id
                 WHERE b.id = ?'
            );
            $stmt->execute([$user['id'], $id]);
            $details = $stmt->fetch();

            if ($details && !empty($details['passenger_email'])) {
                require_once __DIR__ . '/../helpers/mail.php';
                send_booking_accepted_email(
                    $details['passenger_email'],
                    $details['passenger_name'] ?? 'Passenger',
                    $details['driver_name']    ?? 'Driver',
                    $details['driver_phone']   ?? '',
                    $details['vehicle_no']     ?? 'Tricycle',
                    $details['pickup_address'] ?? '',
                    $details['dropoff_address'] ?? '',
                    (float)($details['fare'] ?? 0)
                );
            }
        } catch (Throwable $e) {
            error_log('[Driver Accept Email Error] ' . $e->getMessage());
        }

        json_success(null, 'Ride accepted');

    } elseif ($method === 'POST' && $action === 'reject' && $id) {

        $stmt = $db->prepare("SELECT id, status, driver_id FROM bookings WHERE id = ? AND (driver_id = ? OR status = 'pending')");
        $stmt->execute([$id, $user['id']]);
        $booking = $stmt->fetch();
        if (!$booking) json_error('Booking not found or cannot be rejected', 404);

        $db->prepare("UPDATE bookings SET status = 'rejected', driver_id = ? WHERE id = ?")
           ->execute([$user['id'], $id]);
        json_success(null, 'Ride rejected');

    } elseif ($method === 'POST' && $action === 'cancel' && $id) {

        $stmt = $db->prepare("SELECT id, status, driver_id FROM bookings WHERE id = ? AND driver_id = ? AND status IN ('accepted', 'started')");
        $stmt->execute([$id, $user['id']]);
        $booking = $stmt->fetch();
        if (!$booking) json_error('Booking not found or cannot be cancelled', 404);

        $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        json_success(null, 'Ride cancelled');

    } elseif ($method === 'POST' && $action === 'start' && $id) {

        $stmt = $db->prepare("SELECT id FROM bookings WHERE id = ? AND driver_id = ? AND status = 'accepted'");
        $stmt->execute([$id, $user['id']]);
        if (!$stmt->fetch()) json_error('Booking not found or not in accepted state', 404);

        $db->prepare("UPDATE bookings SET status = 'started' WHERE id = ?")->execute([$id]);
        json_success(null, 'Ride started');

    } elseif ($method === 'POST' && $action === 'complete' && $id) {

        $stmt = $db->prepare("SELECT id FROM bookings WHERE id = ? AND driver_id = ? AND status = 'started'");
        $stmt->execute([$id, $user['id']]);
        if (!$stmt->fetch()) json_error('Booking not found or ride not started', 404);

        $db->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?")->execute([$id]);
        json_success(null, 'Ride completed');

    } elseif ($method === 'PUT' && $action === 'location') {

        $body = get_body();
        $lat  = filter_var($body['lat'] ?? null, FILTER_VALIDATE_FLOAT);
        $lng  = filter_var($body['lng'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            json_error('Valid lat (-90 to 90) and lng (-180 to 180) are required');
        }

        $db->prepare('UPDATE driver_info SET current_lat = ?, current_lng = ? WHERE user_id = ?')
           ->execute([$lat, $lng, $user['id']]);
        json_success(null, 'Location updated');

    } else {
        json_error('Not found', 404);
    }
}
