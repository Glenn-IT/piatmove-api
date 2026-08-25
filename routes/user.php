<?php
// GET  /user/profile       — get current user profile
// PUT  /user/profile       — update current user profile (name, phone)
// POST /user/profile-photo — upload avatar image
// PUT  /user/fcm-token     — update FCM push notification token

$method = $_SERVER['REQUEST_METHOD'];
$action = $segments[1] ?? '';

$user = require_auth();
$db   = get_db();

if ($method === 'GET' && ($action === 'profile' || $action === '')) {

    $stmt = $db->prepare('SELECT id, name, email, phone, role, status, photo_path, created_at FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $profile = $stmt->fetch();
    if (!$profile) json_error('User not found', 404);

    json_success($profile);

} elseif (($method === 'PUT' || $method === 'POST') && $action === 'profile') {

    $body  = get_body();
    $name  = trim($body['name']  ?? '');
    $phone = trim($body['phone'] ?? '');

    if (!$name || !$phone) {
        json_error('name and phone are required');
    }
    if (!preg_match('/^\+?[0-9\s\-]{7,15}$/', $phone)) {
        json_error('Invalid phone number format');
    }

    $stmt = $db->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?');
    $stmt->execute([$name, $phone, $user['id']]);

    json_success(['name' => $name, 'phone' => $phone], 'Profile updated successfully');

} elseif ($method === 'POST' && $action === 'profile-photo') {

    if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        json_error('No photo file uploaded or upload error');
    }

    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        json_error('Invalid image format (JPG, PNG, WebP allowed)');
    }

    $adminUploadRoot = __DIR__ . '/../../PiatMoveAdmin/uploads/profiles';
    $apiUploadRoot   = __DIR__ . '/../uploads/profiles';
    $uploadTarget    = is_dir(dirname($adminUploadRoot)) ? $adminUploadRoot : $apiUploadRoot;
    if (!is_dir($uploadTarget)) {
        @mkdir($uploadTarget, 0755, true);
    }

    $filename = 'profile_' . $user['id'] . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = $uploadTarget . '/' . $filename;

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
        $relPath = 'uploads/profiles/' . $filename;
        if ($uploadTarget === $adminUploadRoot) {
            @mkdir($apiUploadRoot, 0755, true);
            @copy($dest, $apiUploadRoot . '/' . $filename);
        }

        $db->prepare('UPDATE users SET photo_path = ? WHERE id = ?')->execute([$relPath, $user['id']]);
        json_success(['photo_path' => $relPath], 'Profile photo updated successfully');
    } else {
        json_error('Failed to save uploaded photo');
    }

} elseif ($method === 'PUT' && $action === 'fcm-token') {

    $body  = get_body();
    $token = trim($body['token'] ?? '');
    if (!$token) json_error('token is required');

    // Upsert — one token per user
    $db->prepare(
        'INSERT INTO fcm_tokens (user_id, token) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE token = VALUES(token), updated_at = CURRENT_TIMESTAMP'
    )->execute([$user['id'], $token]);

    json_success(null, 'FCM token updated');

} else {
    json_error('Not found', 404);
}
