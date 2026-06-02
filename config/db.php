<?php
define('BASE_PATH', '/dbProject');
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3308);
define('DB_NAME', 'school_db');
define('DB_USER', 'root');
define('DB_PASS', '');


function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}
