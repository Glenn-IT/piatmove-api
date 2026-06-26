<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'piatmove');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('JWT_SECRET', 'piatmove-secret-change-in-production');
define('JWT_EXPIRY', 60 * 60 * 24 * 7); // 7 days

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
