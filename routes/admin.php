<?php
// POST   /admin/login                  — get admin JWT (public)
// GET    /admin/users                  — list all users
// GET    /admin/drivers/pending        — pending driver approvals
// GET    /admin/bookings               — all bookings
// PUT    /admin/driver/approve/{id}    — approve driver
// PUT    /admin/driver/reject/{id}     — reject driver
// PUT    /admin/user/activate/{id}     — activate user
// PUT    /admin/user/deactivate/{id}   — deactivate user
// DELETE /admin/user/{id}              — delete user

$method = $_SERVER['REQUEST_METHOD'];
$action = $segments[1] ?? '';   // login | users | drivers | driver | bookings | user
$sub    = $segments[2] ?? '';   // pending | approve | reject | activate | deactivate | {id}
$seg4   = isset($segments[3]) && is_numeric($segments[3]) ? (int)$segments[3] : null;

$db = get_db();

// ── Public: admin login ────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'login') {
    $body  = get_body();
    $email = trim($body['email']    ?? '');
    $pass  =      $body['password'] ?? '';

    if (!$email || !$pass) json_error('Email and password are required');

    $stmt = $db->prepare('SELECT id, password FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($pass, $admin['password'])) {
        json_error('Invalid credentials', 401);
    }

    $token = jwt_create(['id' => (int)$admin['id'], 'type' => 'admin']);
    json_success(['token' => $token], 'Admin login successful');
}

// ── All routes below require admin JWT ────────────────────────────────────────
require_admin();

if ($method === 'GET' && $action === 'users') {

    $stmt = $db->query(
        'SELECT id, name, email, phone, role, status, created_at FROM users ORDER BY created_at DESC'
    );
    json_success($stmt->fetchAll());

} elseif ($method === 'GET' && $action === 'drivers' && $sub === 'pending') {

    $stmt = $db->query(
        "SELECT u.id, u.name, u.email, u.phone,
                d.license_no, d.vehicle_no, d.vehicle_type, d.approval_status,
                u.created_at
         FROM users u
         JOIN driver_info d ON d.user_id = u.id
         WHERE d.approval_status = 'pending'
         ORDER BY u.created_at DESC"
    );
    json_success($stmt->fetchAll());

} elseif ($method === 'GET' && $action === 'bookings') {

    $stmt = $db->query(
        'SELECT b.*,
                p.name AS passenger_name,
                d.name AS driver_name
         FROM bookings b
         JOIN users p  ON p.id = b.passenger_id
         LEFT JOIN users d ON d.id = b.driver_id
         ORDER BY b.created_at DESC'
    );
    json_success($stmt->fetchAll());

} elseif ($method === 'PUT' && $action === 'driver' && $sub === 'approve' && $seg4) {

    $stmt = $db->prepare('SELECT id FROM driver_info WHERE user_id = ?');
    $stmt->execute([$seg4]);
    if (!$stmt->fetch()) json_error('Driver not found', 404);

    $db->prepare("UPDATE driver_info SET approval_status = 'approved' WHERE user_id = ?")
       ->execute([$seg4]);
    json_success(null, 'Driver approved');

} elseif ($method === 'PUT' && $action === 'driver' && $sub === 'reject' && $seg4) {

    $stmt = $db->prepare('SELECT id FROM driver_info WHERE user_id = ?');
    $stmt->execute([$seg4]);
    if (!$stmt->fetch()) json_error('Driver not found', 404);

    $db->prepare("UPDATE driver_info SET approval_status = 'rejected' WHERE user_id = ?")
       ->execute([$seg4]);
    json_success(null, 'Driver rejected');

} elseif ($method === 'PUT' && $action === 'user' && $sub === 'activate' && $seg4) {

    $stmt = $db->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$seg4]);
    if (!$stmt->fetch()) json_error('User not found', 404);

    $db->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$seg4]);
    json_success(null, 'User activated');

} elseif ($method === 'PUT' && $action === 'user' && $sub === 'deactivate' && $seg4) {

    $stmt = $db->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$seg4]);
    if (!$stmt->fetch()) json_error('User not found', 404);

    $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ?")->execute([$seg4]);
    json_success(null, 'User deactivated');

} elseif ($method === 'DELETE' && $action === 'user' && is_numeric($sub)) {

    $uid  = (int)$sub;
    $stmt = $db->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    if (!$stmt->fetch()) json_error('User not found', 404);

    $db->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
    json_success(null, 'User deleted');

} else {
    json_error('Not found', 404);
}
