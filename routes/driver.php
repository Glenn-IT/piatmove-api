<?php
// GET  /driver/requests
// POST /driver/accept/{id}
// POST /driver/reject/{id}
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

// All actions except toggling online status require an approved driver
if ($action !== 'status') {
    $stmt = $db->prepare('SELECT approval_status FROM driver_info WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $info = $stmt->fetch();
    if (!$info || $info['approval_status'] !== 'approved') {
        json_error('Your driver account is not yet approved', 403);
    }
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

} elseif ($method === 'POST' && $action === 'accept' && $id) {

    $stmt = $db->prepare(
        "UPDATE bookings SET status = 'accepted', driver_id = ? WHERE id = ? AND status = 'pending'"
    );
    $stmt->execute([$user['id'], $id]);
    if ($stmt->rowCount() === 0) json_error('Booking not found or no longer available', 404);
    json_success(null, 'Ride accepted');

} elseif ($method === 'POST' && $action === 'reject' && $id) {

    $stmt = $db->prepare("SELECT id FROM bookings WHERE id = ? AND driver_id = ? AND status = 'accepted'");
    $stmt->execute([$id, $user['id']]);
    if (!$stmt->fetch()) json_error('Booking not found', 404);

    $db->prepare("UPDATE bookings SET status = 'rejected', driver_id = NULL WHERE id = ?")
       ->execute([$id]);
    json_success(null, 'Ride rejected');

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

} elseif ($method === 'PUT' && $action === 'status') {

    $body   = get_body();
    if (!isset($body['is_online'])) json_error('is_online is required');
    $online = (int)(bool)$body['is_online'];

    $db->prepare('UPDATE driver_info SET is_online = ? WHERE user_id = ?')
       ->execute([$online, $user['id']]);
    json_success(['is_online' => (bool)$online], 'Status updated');

} else {
    json_error('Not found', 404);
}
