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
    if (strlen($pass) < 8) {
        json_error('Password must be at least 8 characters');
    }
    if (!preg_match('/^\+?[0-9\s\-]{7,15}$/', $phone)) {
        json_error('Invalid phone number format');
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
            $vehicle_type = trim($body['vehicle_type']  ?? 'Tricycle');
            $barangay     = trim($body['barangay']      ?? 'Poblacion I');
            if (!$license || !$vehicle_no) {
                $db->rollBack();
                json_error('Drivers must include license_no and vehicle_no');
            }

            $plate_proof_path    = null;
            $license_proof_path  = null;
            $photo_path          = null;
            $tricycle_photo_path = null;

            $apiUploadRoot = __DIR__ . '/../uploads/drivers';
            if (!is_dir($apiUploadRoot)) {
                @mkdir($apiUploadRoot, 0755, true);
            }

            // Potential admin upload folders on Hostinger (public_html/admin/...) and local XAMPP (PiatMoveAdmin/...)
            $adminPaths = [
                __DIR__ . '/../../admin/uploads/drivers',
                __DIR__ . '/../../PiatMoveAdmin/uploads/drivers'
            ];
            foreach ($adminPaths as $admPath) {
                if (is_dir(dirname(dirname($admPath)))) {
                    @mkdir($admPath, 0755, true);
                }
            }

            $uploadMap = [
                'plate_proof'    => &$plate_proof_path,
                'license_proof'  => &$license_proof_path,
                'driver_photo'   => &$photo_path,
                'tricycle_photo' => &$tricycle_photo_path,
            ];

            foreach ($uploadMap as $field => &$pathRef) {
                if (!empty($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf', 'webp'], true)) {
                        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
                        $primaryDest = $apiUploadRoot . '/' . $filename;
                        if (move_uploaded_file($_FILES[$field]['tmp_name'], $primaryDest)) {
                            $pathRef = 'uploads/drivers/' . $filename;
                            // Replicate to all detected admin directories
                            foreach ($adminPaths as $admPath) {
                                if (is_dir($admPath)) {
                                    @copy($primaryDest, $admPath . '/' . $filename);
                                }
                            }
                        }
                    }
                }
            }

            $stmt = $db->prepare(
                'INSERT INTO driver_info (user_id, license_no, vehicle_no, vehicle_type, barangay, plate_proof_path, license_proof_path, photo_path, tricycle_photo_path, approval_status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$user_id, $license, $vehicle_no, $vehicle_type, $barangay, $plate_proof_path, $license_proof_path, $photo_path, $tricycle_photo_path, 'pending']);

            if ($photo_path) {
                $db->prepare('UPDATE users SET photo_path = ? WHERE id = ?')->execute([$photo_path, $user_id]);
            }
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        json_error('Registration failed', 500);
    }

    $token = jwt_create(['id' => $user_id, 'role' => $role, 'type' => 'user']);
    json_success(['token' => $token, 'user_id' => $user_id, 'role' => $role, 'approval_status' => ($role === 'driver' ? 'pending' : 'approved'), 'photo_path' => $photo_path], 'Registered successfully', 201);

} elseif ($method === 'POST' && in_array($action, ['login'], true)) {

    $body  = get_body();
    $email = trim($body['email'] ?? '');
    $pass  = $body['password']   ?? '';

    if (!$email || !$pass) {
        json_error('email and password are required');
    }

    $db   = get_db();
    $stmt = $db->prepare('SELECT u.id, u.name, u.email, u.phone, u.password, u.role, u.status, COALESCE(u.photo_path, d.photo_path) AS photo_path, d.approval_status FROM users u LEFT JOIN driver_info d ON d.user_id = u.id WHERE u.email = ?');
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
        [
            'token'           => $token,
            'user_id'         => (int)$user['id'],
            'role'            => $user['role'],
            'name'            => $user['name'],
            'email'           => $user['email'],
            'phone'           => $user['phone'],
            'photo_path'      => $user['photo_path'],
            'approval_status' => $user['approval_status'] ?? 'approved',
        ],
        'Login successful'
    );

} elseif ($method === 'POST' && in_array($action, ['forgot-password', 'forgot_password'], true)) {

    $body  = get_body();
    $email = trim($body['email'] ?? '');

    if (!$email) {
        json_error('Email is required');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Invalid email address');
    }

    $db   = get_db();
    $stmt = $db->prepare('SELECT id, name, email FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Return 404 for clarity or error message
        json_error('No account registered with this email address', 404);
    }

    // Generate 6-digit numeric OTP
    $otp = sprintf('%06d', mt_rand(100000, 999999));

    // Store OTP with 15-minute expiration
    $db->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
    $stmt = $db->prepare('INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))');
    $stmt->execute([$email, $otp]);

    // Send email notification
    require_once __DIR__ . '/../helpers/mail.php';
    $mailResult = send_password_reset_email($user['email'], $user['name'], $otp);

    json_success(
        ['email' => $email],
        'A 6-digit verification code has been sent to your email.'
    );

} elseif ($method === 'POST' && in_array($action, ['verify-otp', 'verify_otp'], true)) {

    $body  = get_body();
    $email = trim($body['email'] ?? '');
    $otp   = trim($body['otp']   ?? '');

    if (!$email || !$otp) {
        json_error('Email and OTP code are required');
    }

    $db   = get_db();
    $stmt = $db->prepare('SELECT id FROM password_resets WHERE email = ? AND otp = ? AND expires_at >= NOW() ORDER BY id DESC LIMIT 1');
    $stmt->execute([$email, $otp]);
    if (!$stmt->fetch()) {
        json_error('Invalid or expired verification code', 400);
    }

    json_success(null, 'Verification code is valid');

} elseif ($method === 'POST' && in_array($action, ['reset-password', 'reset_password'], true)) {

    $body     = get_body();
    $email    = trim($body['email']        ?? '');
    $otp      = trim($body['otp']          ?? '');
    $password =      $body['password']     ?? ($body['new_password'] ?? '');

    if (!$email || !$otp || !$password) {
        json_error('Email, OTP code, and new password are required');
    }
    if (strlen($password) < 8) {
        json_error('Password must be at least 8 characters');
    }

    $db   = get_db();
    $stmt = $db->prepare('SELECT id FROM password_resets WHERE email = ? AND otp = ? AND expires_at >= NOW() ORDER BY id DESC LIMIT 1');
    $stmt->execute([$email, $otp]);
    if (!$stmt->fetch()) {
        json_error('Invalid or expired verification code. Please request a new one.', 400);
    }

    // Update password
    $stmt = $db->prepare('UPDATE users SET password = ? WHERE email = ?');
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $email]);

    // Clean up used reset token
    $db->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);

    json_success(null, 'Password has been reset successfully. You can now log in with your new password.');

} else {
    json_error('Not found', 404);
}

