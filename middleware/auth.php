<?php
function _b64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function _b64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_create(array $payload): string {
    $header  = _b64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = _b64url_encode(json_encode(array_merge($payload, ['exp' => time() + JWT_EXPIRY, 'iat' => time()])));
    $sig     = _b64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$sig";
}

function jwt_parse(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $payload, $sig] = $parts;
    $expected = _b64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if (!hash_equals($expected, $sig)) return null;
    $data = json_decode(_b64url_decode($payload), true);
    if (!is_array($data)) return null;
    if (isset($data['exp']) && $data['exp'] < time()) return null;
    return $data;
}

function get_auth_header(): string {
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return $_SERVER['HTTP_AUTHORIZATION'];
    }
    // Fallback for Apache on XAMPP which strips Authorization header
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') return $value;
        }
    }
    return '';
}

function require_auth(): array {
    $header = get_auth_header();
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        json_error('Unauthorized — missing token', 401);
    }
    $payload = jwt_parse($m[1]);
    if (!$payload || ($payload['type'] ?? '') !== 'user') {
        json_error('Unauthorized — invalid token', 401);
    }
    return $payload;
}

function require_role(array $user, string $role): void {
    if (($user['role'] ?? '') !== $role) {
        json_error('Forbidden — wrong role', 403);
    }
}

function require_admin(): array {
    $header = get_auth_header();
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        json_error('Unauthorized — missing token', 401);
    }
    $payload = jwt_parse($m[1]);
    if (!$payload || ($payload['type'] ?? '') !== 'admin') {
        json_error('Unauthorized — admin token required', 401);
    }
    return $payload;
}
