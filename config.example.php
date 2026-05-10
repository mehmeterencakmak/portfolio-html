<?php
// Copy this file to config.php and fill in your database credentials.

define('DB_HOST',    'YOUR_DB_HOST');
define('DB_NAME',    'YOUR_DB_NAME');
define('DB_USER',    'YOUR_DB_USER');
define('DB_PASS',    'YOUR_DB_PASS');
define('DB_PORT',    '3306');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}
