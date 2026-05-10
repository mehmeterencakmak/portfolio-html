<?php
// ═══════════════════════════════════════════════════════
// admin/project_save.php — Add or Update a Project
// Only accepts POST. Redirects back to dashboard.php.
// ═══════════════════════════════════════════════════════

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

require_once '../config.php';

// Sanitize inputs
function clean(string $s): string {
    return htmlspecialchars(strip_tags(trim($s)), ENT_QUOTES, 'UTF-8');
}

$title       = clean($_POST['title']       ?? '');
$description = clean($_POST['description'] ?? '');
$tags        = clean($_POST['tags']        ?? '');
$image_url   = clean($_POST['image_url']   ?? '');
$demo_url    = clean($_POST['demo_url']    ?? '');
$github_url  = clean($_POST['github_url']  ?? '');
$id          = (int)($_POST['id']          ?? 0);

// Handle file upload
if (!empty($_FILES['image_file']['name'])) {
    $file     = $_FILES['image_file'];
    $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize  = 5 * 1024 * 1024; // 5 MB

    if (!in_array($file['type'], $allowed)) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid file type. Use JPG, PNG, WEBP or GIF.'];
        header('Location: dashboard.php' . ($id ? "?edit=$id" : ''));
        exit;
    }
    if ($file['size'] > $maxSize) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'File too large. Max 5 MB.'];
        header('Location: dashboard.php' . ($id ? "?edit=$id" : ''));
        exit;
    }

    $uploadDir = __DIR__ . '/../images/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename  = uniqid('proj_', true) . '.' . strtolower($ext);
    $dest      = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $image_url = 'images/uploads/' . $filename;
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Upload failed. Check folder permissions.'];
        header('Location: dashboard.php' . ($id ? "?edit=$id" : ''));
        exit;
    }
}

// Basic validation
if (!$title || !$description) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Title and description are required.'];
    header('Location: dashboard.php' . ($id ? "?edit=$id" : ''));
    exit;
}

try {
    $pdo = getDB();

    if ($id > 0) {
        // UPDATE existing project
        $stmt = $pdo->prepare("
            UPDATE projects
            SET title = :title, description = :description, tags = :tags,
                image_url = :image_url, demo_url = :demo_url, github_url = :github_url
            WHERE id = :id
        ");
        $stmt->execute([
            ':title'       => $title,
            ':description' => $description,
            ':tags'        => $tags,
            ':image_url'   => $image_url,
            ':demo_url'    => $demo_url,
            ':github_url'  => $github_url,
            ':id'          => $id,
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Project updated successfully.'];

    } else {
        // INSERT new project
        $stmt = $pdo->prepare("
            INSERT INTO projects (title, description, tags, image_url, demo_url, github_url)
            VALUES (:title, :description, :tags, :image_url, :demo_url, :github_url)
        ");
        $stmt->execute([
            ':title'       => $title,
            ':description' => $description,
            ':tags'        => $tags,
            ':image_url'   => $image_url,
            ':demo_url'    => $demo_url,
            ':github_url'  => $github_url,
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Project added successfully.'];
    }

} catch (PDOException $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Database error: ' . $e->getMessage()];
}

header('Location: dashboard.php#projects');
exit;
