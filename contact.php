<?php
// ─────────────────────────────────────────────
// contact.php — İletişim Formu Backend
// SEN3002 Portfolio Projesi — PHP + MySQL
// ─────────────────────────────────────────────

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Sadece POST isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece POST metodu desteklenmektedir.']);
    exit;
}

// ── VERİTABANI AYARLARI ──────────────────────
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'portfolio_db');    // phpMyAdmin'de oluşturduğun veritabanı adı
define('DB_USER', 'root');            // XAMPP varsayılan kullanıcısı
define('DB_PASS', '');                // XAMPP varsayılan şifre (boş)
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', '3307');

// ── GİRDİ ALMA & TEMİZLEME ──────────────────
function clean(string $str): string {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

$name    = clean($_POST['name']    ?? '');
$email   = clean($_POST['email']   ?? '');
$subject = clean($_POST['subject'] ?? '');
$message = clean($_POST['message'] ?? '');

// ── DOĞRULAMA ────────────────────────────────
$errors = [];

if (empty($name))    $errors[] = 'Ad Soyad zorunludur.';
if (empty($email))   $errors[] = 'E-posta zorunludur.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta adresi girin.';
if (empty($message)) $errors[] = 'Mesaj zorunludur.';
if (strlen($message) < 10) $errors[] = 'Mesaj en az 10 karakter olmalıdır.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── VERİTABANI BAĞLANTISI ────────────────────
try {
   $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı kurulamadı.']);
    exit;
}

// ── TABLOYU OLUŞTUR (yoksa) ──────────────────
// Bu satırı sadece ilk kurulumda çalıştır, sonra kaldırabilirsin
$pdo->exec("
    CREATE TABLE IF NOT EXISTS messages (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(100)  NOT NULL,
        email      VARCHAR(150)  NOT NULL,
        subject    VARCHAR(200)  DEFAULT '',
        message    TEXT          NOT NULL,
        ip_address VARCHAR(45)   DEFAULT NULL,
        created_at DATETIME      DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── VERİYİ KAYDET ────────────────────────────
try {
    $stmt = $pdo->prepare("
        INSERT INTO messages (name, email, subject, message, ip_address)
        VALUES (:name, :email, :subject, :message, :ip)
    ");

    $stmt->execute([
        ':name'    => $name,
        ':email'   => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    // Başarılı yanıt
    echo json_encode([
        'success' => true,
        'message' => 'Mesajın alındı! En kısa sürede dönüş yapacağım.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Mesaj kaydedilemedi, lütfen tekrar dene.']);
}
