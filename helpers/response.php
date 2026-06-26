<?php
function json_success($data = null, string $message = 'Success', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => true, 'data' => $data, 'message' => $message]);
    exit;
}

function json_error(string $message = 'Error', int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'data' => null, 'message' => $message]);
    exit;
}

function get_body(): array {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    return is_array($body) ? $body : [];
}
