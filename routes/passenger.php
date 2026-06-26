<?php
// GET /passenger/history

$method = $_SERVER['REQUEST_METHOD'];
$action = $segments[1] ?? '';

$user = require_auth();
require_role($user, 'passenger');
$db = get_db();

if ($method === 'GET' && $action === 'history') {

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
    json_success($stmt->fetchAll());

} else {
    json_error('Not found', 404);
}
