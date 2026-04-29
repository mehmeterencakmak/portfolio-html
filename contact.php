<?php
// ═══════════════════════════════════════════════════════
// contact.php — Contact Form Backend
// Receives POST from portfolio.html, saves message to DB,
// returns JSON response consumed by Fetch API.
// ═══════════════════════════════════════════════════════

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/config.php';

// Sanitize a string: trim whitespace, strip HTML tags, escape special chars
function clean(string $str): string
{
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

// Collect and sanitize form input
$name    = clean($_POST['name']    ?? '');
$email   = clean($_POST['email']   ?? '');
$subject = clean($_POST['subject'] ?? '');
$message = clean($_POST['message'] ?? '');

// Server-side validation (JS validation can be bypassed)
$errors = [];

if (empty($name))                               $errors[] = 'Name is required.';
if (empty($email))                              $errors[] = 'Email is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
if (empty($message))                            $errors[] = 'Message is required.';
if (strlen($message) < 10)                      $errors[] = 'Message must be at least 10 characters.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Persist to database
try {
    $pdo = getDB();

    // Create messages table if it doesn't exist yet
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

    // Prepared statement prevents SQL Injection
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

    echo json_encode([
        'success' => true,
        'message' => 'Your message has been received!',
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not save your message. Please try again.']);
}
