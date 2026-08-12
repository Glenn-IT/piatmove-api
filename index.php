<?php
set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Internal server error']);
    exit;
});

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/middleware/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Strip the subfolder from the URI so routes work in /piatmove-api/
$base     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri      = trim(substr($uri, strlen($base)), '/');
$segments = $uri !== '' ? explode('/', $uri) : [];

$root = $segments[0] ?? '';

switch ($root) {
    case '':
    case 'health':    json_success(['status' => 'online', 'service' => 'PiatMove API']); break;
    case 'auth':      require __DIR__ . '/routes/auth.php';      break;
    case 'bookings':  require __DIR__ . '/routes/bookings.php';  break;
    case 'driver':    require __DIR__ . '/routes/driver.php';    break;
    case 'passenger': require __DIR__ . '/routes/passenger.php'; break;
    case 'user':      require __DIR__ . '/routes/user.php';      break;
    case 'admin':     require __DIR__ . '/routes/admin.php';     break;
    default:          json_error('Not found', 404);
}
