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

    $db   = get_db();
    $stmt = $db->prepare(
        'INSERT INTO bookings (passenger_id, pickup_address, pickup_lat, pickup_lng, dropoff_address, dropoff_lat, dropoff_lng)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $user['id'],
        $body['pickup_address'],
        $body['pickup_lat'],
        $body['pickup_lng'],
        $body['dropoff_address'],
        $body['dropoff_lat'],
        $body['dropoff_lng'],
    ]);
    json_success(['booking_id' => (int)$db->lastInsertId()], 'Booking created', 201);

// GET /bookings
} elseif ($method === 'GET' && $id === null) {

    $user = require_auth();
    $db   = get_db();

    if ($user['role'] === 'passenger') {
        $stmt = $db->prepare('SELECT * FROM bookings WHERE passenger_id = ? ORDER BY created_at DESC');
        $stmt->execute([$user['id']]);
    } elseif ($user['role'] === 'driver') {
        $stmt = $db->prepare('SELECT * FROM bookings WHERE driver_id = ? ORDER BY created_at DESC');
        $stmt->execute([$user['id']]);
    } else {
        json_error('Forbidden', 403);
    }

    json_success($stmt->fetchAll());

// GET /bookings/{id}
} elseif ($method === 'GET' && $id !== null) {

    $user = require_auth();
    $db   = get_db();
    $stmt = $db->prepare('SELECT * FROM bookings WHERE id = ?');
    $stmt->execute([$id]);
    $booking = $stmt->fetch();

    if (!$booking) json_error('Booking not found', 404);
    if ((int)$booking['passenger_id'] !== $user['id'] && (int)$booking['driver_id'] !== $user['id']) {
        json_error('Forbidden', 403);
    }
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

} else {
    json_error('Not found', 404);
}
