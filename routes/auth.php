<?php
// POST /auth/register
// POST /auth/login

$method = $_SERVER['REQUEST_METHOD'];
$action = $segments[1] ?? '';

if ($method === 'POST' && $action === 'register') {

    $body  = get_body();
    $name  = trim($body['name']     ?? '');
    $email = trim($body['email']    ?? '');
    $pass  =      $body['password'] ?? '';
    $phone = trim($body['phone']    ?? '');
    $role  =      $body['role']     ?? 'passenger';

    if (!$name || !$email || !$pass || !$phone) {
        json_error('name, email, password and phone are required');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Invalid email address');
    }
    if (!in_array($role, ['passenger', 'driver'], true)) {
        json_error('role must be passenger or driver');
    }

    $db = get_db();

    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_error('Email already registered', 409);
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare(
            'INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $phone, $role]);
        $user_id = (int) $db->lastInsertId();

        if ($role === 'driver') {
            $license      = trim($body['license_no']    ?? '');
            $vehicle_no   = trim($body['vehicle_no']    ?? '');
            $vehicle_type = trim($body['vehicle_type']  ?? '');
            if (!$license || !$vehicle_no || !$vehicle_type) {
                $db->rollBack();
                json_error('Drivers must include license_no, vehicle_no and vehicle_type');
            }
            $stmt = $db->prepare(
                'INSERT INTO driver_info (user_id, license_no, vehicle_no, vehicle_type) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$user_id, $license, $vehicle_no, $vehicle_type]);
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        json_error('Registration failed', 500);
    }

    $token = jwt_create(['id' => $user_id, 'role' => $role, 'type' => 'user']);
    json_success(['token' => $token, 'user_id' => $user_id, 'role' => $role], 'Registered successfully', 201);

} elseif ($method === 'POST' && $action === 'login') {

    $body  = get_body();
    $email = trim($body['email']    ?? '');
    $pass  =      $body['password'] ?? '';

    if (!$email || !$pass) {
        json_error('Email and password are required');
    }

    $db   = get_db();
    $stmt = $db->prepare('SELECT id, password, role, status FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password'])) {
        json_error('Invalid credentials', 401);
    }
    if ($user['status'] !== 'active') {
        json_error('Account is deactivated', 403);
    }

    $token = jwt_create(['id' => (int)$user['id'], 'role' => $user['role'], 'type' => 'user']);
    json_success(
        ['token' => $token, 'user_id' => (int)$user['id'], 'role' => $user['role']],
        'Login successful'
    );

} else {
    json_error('Not found', 404);
}
