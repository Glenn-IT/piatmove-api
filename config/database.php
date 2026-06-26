<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'piatmove');
define('DB_USER',    'root');
define('DB_PASS',    '');
$_jwt_secret = getenv('JWT_SECRET') ?: (file_exists(__DIR__ . '/../.env.secret') ? trim(file_get_contents(__DIR__ . '/../.env.secret')) : null);
if (!$_jwt_secret) die(json_encode(['success'=>false,'data'=>null,'message'=>'Server misconfigured: JWT_SECRET not set']));
define('JWT_SECRET', $_jwt_secret);
unset($_jwt_secret);
define('JWT_EXPIRY', 60 * 60 * 24); // 24 hours

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
