<?php
// ═══════════════════════════════════════════════════════
// contact.php — İletişim Formu Backend
// SEN3002 Portfolio Projesi — PHP + MySQL
//
// Bu dosya JavaScript'teki fetch() isteğini karşılar:
// 1. Gelen veriyi temizler ve doğrular
// 2. Veritabanına bağlanır
// 3. Mesajı kaydeder
// 4. JSON formatında sonuç döndürür
// ═══════════════════════════════════════════════════════


// ── HEADER (BAŞLIK) AYARLARI ─────────────────────────────
// header(): Tarayıcıya HTTP başlıkları gönderir
// Bu başlıklar içerik gelmeden önce iletilmesi gereken meta bilgilerdir

header('Content-Type: application/json');
// Tarayıcıya "bu yanıt JSON formatındadır" diyoruz
// JavaScript'teki res.json() bunu okuyarak parse eder

header('Access-Control-Allow-Origin: *');
// CORS (Cross-Origin Resource Sharing) ayarı
// '*' = Her domain'den istek kabul et
// Güvenlik için production'da kendi domain'ini yazmalısın

header('Access-Control-Allow-Methods: POST');
// Sadece POST metoduna izin veriyoruz
// GET, PUT, DELETE gibi metodları reddediyoruz


// ── METHOD KONTROLÜ ──────────────────────────────────────
// $_SERVER: PHP'nin sunucu bilgilerini içeren özel dizisi
// REQUEST_METHOD: Gelen isteğin tipi (GET, POST, PUT...)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // POST değilse hata kodu gönder ve dur

    http_response_code(405);
    // HTTP durum kodları:
    // 200 = Başarılı
    // 405 = Method Not Allowed (izin verilmeyen metod)
    // 422 = Unprocessable Entity (geçersiz veri)
    // 500 = Internal Server Error (sunucu hatası)

    echo json_encode(['success' => false, 'message' => 'Sadece POST metodu desteklenmektedir.']);
    // json_encode(): PHP dizisini JSON string'e çevirir
    // ['success' => false] PHP dizisi → {"success": false} JSON
    // echo: Ekrana/yanıta yazar — JavaScript bunu okur

    exit;
    // exit: PHP'nin çalışmasını tamamen durdurur
    // Bu olmadan kod çalışmaya devam eder!
}


// ── VERİTABANI SABİTLERİ ─────────────────────────────────
// define(): Sabit (constant) tanımlar — değeri sonradan değiştirilemez
// Büyük harf kullanmak geleneksel — sabitleri değişkenlerden ayırt eder

define('DB_HOST', '127.0.0.1');     // Veritabanı sunucusu (localhost = 127.0.0.1)
define('DB_NAME', 'portfolio_db');  // Kullanılacak veritabanının adı
define('DB_USER', 'root');          // XAMPP varsayılan kullanıcısı
define('DB_PASS', '');              // XAMPP varsayılan şifre (boş)
define('DB_CHARSET', 'utf8mb4');    // utf8mb4: Türkçe ve emoji destekli karakter seti
define('DB_PORT', '3307');          // MySQL portu (normalde 3306, bizim 3307)


// ── GİRDİ TEMİZLEME FONKSİYONU ───────────────────────────
// Kullanıcıdan gelen veriye asla güvenme!
// Kötü niyetli kullanıcılar zararlı kod gönderebilir (XSS saldırısı)

function clean(string $str): string {
    // string $str: Parametre tipi belirtimi — sadece string kabul eder
    // : string — fonksiyonun döndüreceği tip

    return htmlspecialchars(
        strip_tags(
            trim($str)
            // trim(): Metnin başındaki ve sonundaki boşlukları siler
            // "  merhaba  " → "merhaba"
        ),
        // strip_tags(): HTML etiketlerini siler
        // "<script>alert('hack')</script>" → "alert('hack')"
        ENT_QUOTES,
        // ENT_QUOTES: Hem tek hem çift tırnakları güvenli hale getirir
        'UTF-8'
        // htmlspecialchars(): Özel karakterleri HTML varlıklarına çevirir
        // "<" → "&lt;", ">" → "&gt;", "&" → "&amp;"
        // Bu sayede veritabanına veya sayfaya zararlı kod enjekte edilemez
    );
}

// ── FORM VERİLERİNİ AL ───────────────────────────────────
// $_POST: HTML formundan POST metoduyla gelen verileri içerir
// JavaScript'te: new FormData(form) ile gönderdiğimiz veriler buraya gelir

$name    = clean($_POST['name']    ?? '');
$email   = clean($_POST['email']   ?? '');
$subject = clean($_POST['subject'] ?? '');
$message = clean($_POST['message'] ?? '');
// ?? '' — Null coalescing operator (PHP 7+)
// $_POST['name'] varsa onu kullan, yoksa boş string '' kullan
// Böylece tanımsız index hatası almayız


// ── DOĞRULAMA ────────────────────────────────────────────
// Sunucu tarafı doğrulama çok önemli!
// JavaScript doğrulaması atlatılabilir (tarayıcı konsolundan)
// PHP doğrulaması ise sunucuda çalışır, atlatılamaz

$errors = [];  // Hata mesajlarını tutacak boş dizi

if (empty($name))
    $errors[] = 'Ad Soyad zorunludur.';
    // empty(): Boş string, 0, null, false için true döner
    // $errors[]: Diziye yeni eleman ekler

if (empty($email))
    $errors[] = 'E-posta zorunludur.';

if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = 'Geçerli bir e-posta adresi girin.';
    // filter_var(): PHP'nin yerleşik doğrulama fonksiyonu
    // FILTER_VALIDATE_EMAIL: E-posta formatını kontrol eder
    // "abc@gmail.com" → true | "abc" → false
    // ! (ünlem) = değili — geçersizse hataya ekle

if (empty($message))
    $errors[] = 'Mesaj zorunludur.';

if (strlen($message) < 10)
    $errors[] = 'Mesaj en az 10 karakter olmalıdır.';
    // strlen(): String'in karakter uzunluğunu döndürür

// Hata varsa yanıt gönder ve dur
if (!empty($errors)) {
    http_response_code(422);  // 422 = Geçersiz veri
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    // implode(' ', $errors): Dizi elemanlarını boşlukla birleştirir
    // ['Hata 1.', 'Hata 2.'] → "Hata 1. Hata 2."
    exit;
}


// ── VERİTABANI BAĞLANTISI ────────────────────────────────
// PDO (PHP Data Objects): PHP'nin veritabanı bağlantı katmanı
// MySQL, PostgreSQL, SQLite gibi farklı veritabanlarıyla aynı şekilde çalışır

try {
    // try-catch: Hata yönetimi
    // try bloğundaki kod hata fırlatırsa catch bloğuna atlar

    // DSN (Data Source Name): Bağlantı dizesi — nereye bağlanacağımızı söyler
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    // . operatörü: PHP'de string birleştirme (JavaScript'teki + gibi)
    // Sonuç: "mysql:host=127.0.0.1;port=3307;dbname=portfolio_db;charset=utf8mb4"

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Hata olursa exception fırlat — catch bloğuna düşer

        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Veri çekerken ilişkisel dizi döndür
        // ['id' => 1, 'name' => 'Eren'] gibi — [0 => 1, 1 => 'Eren'] değil

        PDO::ATTR_EMULATE_PREPARES   => false,
        // Gerçek prepared statement kullan — güvenlik için önemli
    ];

    // Yeni PDO bağlantısı oluştur
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    // new: Nesne oluşturur (OOP — Nesne Yönelimli Programlama)
    // $pdo artık veritabanı bağlantısını temsil eden nesne

} catch (PDOException $e) {
    // PDOException: Veritabanı hataları için özel exception tipi
    // $e: Hata nesnesini içerir (mesaj, kod vb.)
    http_response_code(500);  // 500 = Sunucu hatası
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı kurulamadı.']);
    exit;
}


// ── TABLOYU OLUŞTUR (yoksa) ──────────────────────────────
// IF NOT EXISTS: Tablo zaten varsa tekrar oluşturmaya çalışmaz, hata vermez
// İlk çalıştırmada tabloyu oluşturur, sonraki çalıştırmalarda atlar

$pdo->exec("
    CREATE TABLE IF NOT EXISTS messages (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        -- AUTO_INCREMENT: Her yeni satırda id otomatik 1 artar (1, 2, 3...)
        -- PRIMARY KEY: Bu sütun benzersiz tanımlayıcı, iki satır aynı id'ye sahip olamaz

        name       VARCHAR(100)  NOT NULL,
        -- VARCHAR(100): En fazla 100 karakter metin
        -- NOT NULL: Boş bırakılamaz

        email      VARCHAR(150)  NOT NULL,
        subject    VARCHAR(200)  DEFAULT '',
        -- DEFAULT '': Değer girilmezse boş string kullan

        message    TEXT          NOT NULL,
        -- TEXT: VARCHAR'dan uzun metinler için (65,535 karaktere kadar)

        ip_address VARCHAR(45)   DEFAULT NULL,
        -- IPv6 adresleri 45 karaktere kadar uzayabilir
        -- DEFAULT NULL: Değer girilmezse NULL (boş) olsun

        created_at DATETIME      DEFAULT CURRENT_TIMESTAMP
        -- DATETIME: Tarih ve saat bilgisi (2025-04-15 23:00:00 formatında)
        -- CURRENT_TIMESTAMP: Kayıt oluşturulunca otomatik şu anki zamanı yazar
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    -- ENGINE=InnoDB: MySQL'in modern motoru — transaction ve foreign key destekler
    -- CHARSET=utf8mb4: Türkçe karakter ve emoji desteği
");


// ── VERİYİ VERİTABANINA KAYDET ───────────────────────────
try {

    // prepare(): SQL sorgusunu hazırlar ama henüz çalıştırmaz
    // :name, :email gibi yer tutucular (placeholder) kullanıyoruz
    // Bu yöntem SQL Injection saldırılarını önler!
    //
    // SQL Injection örneği — bu olmadan ne olurdu:
    // Kullanıcı name alanına: "'; DROP TABLE messages; --" yazarsa
    // Prepared statement olmadan tablo silinirdi!
    // Prepared statement ile bu güvenli hale gelir.

    $stmt = $pdo->prepare("
        INSERT INTO messages (name, email, subject, message, ip_address)
        VALUES (:name, :email, :subject, :message, :ip)
    ");
    // INSERT INTO: Tabloya yeni satır ekler
    // VALUES: Eklenecek değerler

    // execute(): Sorguyu çalıştırır, yer tutuculara gerçek değerleri bağlar
    $stmt->execute([
        ':name'    => $name,      // :name yer tutucusuna $name değerini koy
        ':email'   => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
        // REMOTE_ADDR: İsteği gönderen kullanıcının IP adresi
        // Kötüye kullanımı takip etmek için kaydediyoruz
    ]);

    // Her şey başarılıysa JavaScript'e başarı mesajı gönder
    echo json_encode([
        'success' => true,
        'message' => 'Mesajın alındı! En kısa sürede dönüş yapacağım.'
    ]);
    // JavaScript tarafında: data.success === true olursa başarı mesajı gösterilir

} catch (PDOException $e) {
    // Kayıt sırasında hata olursa burası çalışır
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Mesaj kaydedilemedi, lütfen tekrar dene.']);
    // NOT: $e->getMessage() ile gerçek hata mesajını görebilirsin
    // ama production'da bunu kullanıcıya göstermemelisin — güvenlik riski
}