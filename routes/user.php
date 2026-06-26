<?php
// PUT /user/fcm-token

$method = $_SERVER['REQUEST_METHOD'];
$action = $segments[1] ?? '';

$user = require_auth();
$db   = get_db();

if ($method === 'PUT' && $action === 'fcm-token') {

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
