<?php
// POST   /bookings              — create booking (passenger)
// GET    /bookings              — list bookings (role-filtered)
// GET    /bookings/{id}         — single booking
// POST   /bookings/{id}/cancel  — cancel booking (passenger)

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($segments[1]) && is_numeric($segments[1]) ? (int)$segments[1] : null;
$action = $segments[2] ?? null;

// POST /bookings
if ($method === 'POST' && $id === null) {

    $user = require_auth();
    require_role($user, 'passenger');
    $body = get_body();

    $required = ['pickup_address', 'pickup_lat', 'pickup_lng', 'dropoff_address', 'dropoff_lat', 'dropoff_lng'];
    foreach ($required as $field) {
        if (!isset($body[$field]) || $body[$field] === '') {
            json_error("Field '$field' is required");
        }
    }

    $passenger_count = isset($body['passenger_count']) ? (int)$body['passenger_count'] : 1;
    if ($passenger_count < 1) $passenger_count = 1;
    if ($passenger_count > 5) $passenger_count = 5;

    $allowed_discounts = ['regular', 'student', 'senior', 'pwd', 'pregnant'];
    $discount_type = isset($body['discount_type']) && in_array(strtolower($body['discount_type']), $allowed_discounts, true)
        ? strtolower($body['discount_type'])
        : 'regular';

    // Regulated fare: ₱20 base, ₱16 (20% OFF) for student, senior, pwd, pregnant
    $rate = ($discount_type !== 'regular') ? 16.00 : 20.00;
    $fare = isset($body['fare']) && is_numeric($body['fare']) ? (float)$body['fare'] : ($passenger_count * $rate);

    $db = get_db();
    $stmt = $db->prepare(
        'INSERT INTO bookings (passenger_id, pickup_address, pickup_lat, pickup_lng, dropoff_address, dropoff_lat, dropoff_lng, passenger_count, fare, discount_type)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $user['id'],
        $body['pickup_address'],
        $body['pickup_lat'],
        $body['pickup_lng'],
        $body['dropoff_address'],
        $body['dropoff_lat'],
        $body['dropoff_lng'],
        $passenger_count,
        $fare,
        $discount_type,
    ]);

    json_success([
        'booking_id'      => (int)$db->lastInsertId(),
        'passenger_count' => $passenger_count,
        'fare'            => $fare,
        'discount_type'   => $discount_type
    ], 'Booking created', 201);

// GET /bookings
} elseif ($method === 'GET' && $id === null) {

    $user = require_auth();
    $db   = get_db();

    if ($user['role'] === 'passenger') {
        $stmt = $db->prepare(
            'SELECT b.*,
                    d.name  AS driver_name,
                    d.phone AS driver_phone
             FROM bookings b
             LEFT JOIN users d ON d.id = b.driver_id
             WHERE b.passenger_id = ?
             ORDER BY b.created_at DESC'
        );
        $stmt->execute([$user['id']]);
    } elseif ($user['role'] === 'driver') {
        $stmt = $db->prepare(
            'SELECT b.*,
                    p.name  AS passenger_name,
                    p.phone AS passenger_phone
             FROM bookings b
             JOIN users p ON p.id = b.passenger_id
             WHERE b.driver_id = ?
             ORDER BY b.created_at DESC'
        );
        $stmt->execute([$user['id']]);
    } else {
        json_error('Forbidden', 403);
    }

    json_success($stmt->fetchAll());

// GET /bookings/{id}
} elseif ($method === 'GET' && $id !== null) {

    $user = require_auth();
    $db   = get_db();
    $stmt = $db->prepare(
        'SELECT b.*,
                p.name  AS passenger_name,
                p.phone AS passenger_phone,
                d.name  AS driver_name,
                d.phone AS driver_phone,
                di.vehicle_no   AS driver_vehicle_no,
                di.vehicle_type AS driver_vehicle_type,
                di.current_lat  AS driver_lat,
                di.current_lng  AS driver_lng
         FROM bookings b
         JOIN users p  ON p.id = b.passenger_id
         LEFT JOIN users d ON d.id = b.driver_id
         LEFT JOIN driver_info di ON di.user_id = b.driver_id
         WHERE b.id = ?'
    );
    $stmt->execute([$id]);
    $booking = $stmt->fetch();

    if (!$booking) json_error('Booking not found', 404);
    if ((int)$booking['passenger_id'] !== $user['id'] && (int)$booking['driver_id'] !== $user['id']) {
        json_error('Forbidden', 403);
    }
    if ($booking['driver_lat'] !== null) $booking['driver_lat'] = (float)$booking['driver_lat'];
    if ($booking['driver_lng'] !== null) $booking['driver_lng'] = (float)$booking['driver_lng'];
    json_success($booking);

// POST /bookings/{id}/cancel
} elseif ($method === 'POST' && $id !== null && $action === 'cancel') {

    $user = require_auth();
    require_role($user, 'passenger');
    $db   = get_db();

    $stmt = $db->prepare('SELECT * FROM bookings WHERE id = ? AND passenger_id = ?');
    $stmt->execute([$id, $user['id']]);
    $booking = $stmt->fetch();

    if (!$booking) json_error('Booking not found', 404);
    if (!in_array($booking['status'], ['pending', 'accepted'], true)) {
        json_error('Cannot cancel a ride that has already started or is completed');
    }

    $db->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute(['cancelled', $id]);
    json_success(null, 'Booking cancelled');

// POST /bookings/{id}/rate
} elseif ($method === 'POST' && $id !== null && $action === 'rate') {

    $user = require_auth();
    require_role($user, 'passenger');
    $db   = get_db();

    $stmt = $db->prepare('SELECT * FROM bookings WHERE id = ? AND passenger_id = ?');
    $stmt->execute([$id, $user['id']]);
    $booking = $stmt->fetch();

    if (!$booking) json_error('Booking not found', 404);
    if ($booking['status'] !== 'completed') {
        json_error('You can only rate completed rides', 400);
    }
    if (!empty($booking['rating'])) {
        json_error('This ride has already been rated', 400);
    }

    $body    = get_body();
    $rating  = isset($body['rating']) ? (int)$body['rating'] : 0;
    $comment = isset($body['comment']) ? trim($body['comment']) : null;

    if ($rating < 1 || $rating > 5) {
        json_error('Rating must be between 1 and 5 stars', 400);
    }

    if ($comment !== null && mb_strlen($comment) > 255) {
        $comment = mb_substr($comment, 0, 255);
    }

    $updateStmt = $db->prepare(
        'UPDATE bookings SET rating = ?, rating_comment = ?, rated_at = NOW() WHERE id = ?'
    );
    $updateStmt->execute([$rating, $comment, $id]);

    json_success([
        'booking_id' => $id,
        'rating'     => $rating,
        'comment'    => $comment
    ], 'Thank you for rating your driver!');

} else {
    json_error('Not found', 404);
}

