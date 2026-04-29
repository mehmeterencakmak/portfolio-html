<?php
// ═══════════════════════════════════════════════════════
// config.php — Shared Database Configuration
// All PHP files include this file for DB access.
// ═══════════════════════════════════════════════════════

define('DB_HOST',    '127.0.0.1');   // Veritabanı sunucusu
define('DB_NAME',    'portfolio_db'); // Veritabanı adı
define('DB_USER',    'root');         // XAMPP varsayılan kullanıcı
define('DB_PASS',    '');             // XAMPP varsayılan şifre (boş)
define('DB_PORT',    '3307');         // MySQL portu
define('DB_CHARSET', 'utf8mb4');      // Türkçe + emoji desteği

/**
 * Returns a shared PDO connection (singleton pattern).
 * Throws PDOException on failure.
 */
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
