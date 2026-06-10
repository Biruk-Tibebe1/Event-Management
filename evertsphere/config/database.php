<?php
// config/database.php

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'ethioevents');
define('DB_USER', 'root');
define('DB_PASS', 'YOUR_PASSWORD');
define('BASE_URL', '/evertsphere');

date_default_timezone_set('YOUR_LOCATION');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // In production, avoid echoing detailed errors
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    return $pdo;
}
