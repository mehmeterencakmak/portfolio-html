<?php
// ═══════════════════════════════════════════════════════
// admin/setup.php — One-time Setup Script
//
// Run this ONCE by visiting it in your browser:
//   http://localhost/portfolio-html/admin/setup.php
//
// It will:
//   1. Create all required database tables
//   2. Insert 4 sample projects
//   3. Create the admin user  (username: admin / password: Admin123!)
//
// DELETE or restrict access to this file after running it.
// ═══════════════════════════════════════════════════════

require_once '../config.php';

$results = [];

try {
    $pdo = getDB();

    // ── 1. Create tables ──────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            username      VARCHAR(50)  NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $results[] = ['ok', 'Table <code>admin_users</code> ready.'];

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS projects (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            title       VARCHAR(200) NOT NULL,
            description TEXT         NOT NULL,
            tags        VARCHAR(300) DEFAULT '',
            image_url   VARCHAR(300) DEFAULT '',
            demo_url    VARCHAR(300) DEFAULT '',
            github_url  VARCHAR(300) DEFAULT '',
            created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $results[] = ['ok', 'Table <code>projects</code> ready.'];

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
    $results[] = ['ok', 'Table <code>messages</code> ready.'];

    // ── 2. Sample projects ────────────────────────────
    $existing = (int) $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();

    if ($existing === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO projects (title, description, tags, image_url, demo_url, github_url)
            VALUES (:title, :desc, :tags, :img, :demo, :gh)
        ");

        $samples = [
            [
                'title' => 'E-Commerce Demo',
                'desc'  => 'A demo e-commerce website where users can browse products, add items to a cart, and simulate a shopping experience. Built with vanilla HTML, CSS, and JavaScript.',
                'tags'  => 'HTML, CSS, JavaScript',
                'img'   => '../images/work3.jpeg',
                'demo'  => '#',
                'gh'    => 'https://github.com/mehmeterencakmak',
            ],
            [
                'title' => 'Personal Portfolio',
                'desc'  => 'My personal portfolio website showcasing my projects and skills. Designed and developed from scratch using HTML, CSS, JavaScript, PHP, and MySQL.',
                'tags'  => 'HTML, CSS, JavaScript, PHP, MySQL',
                'img'   => '../images/work4.jpeg',
                'demo'  => 'https://mehmeterencakmak.vercel.app/',
                'gh'    => 'https://github.com/mehmeterencakmak',
            ],
            [
                'title' => 'Freight & Logistics',
                'desc'  => 'A freight and logistics company website featuring service listings, route information, and a contact form. Built with React and JavaScript.',
                'tags'  => 'React, JavaScript',
                'img'   => '../images/work5.jpeg',
                'demo'  => 'https://erenakliyat.vercel.app/',
                'gh'    => 'https://github.com/mehmeterencakmak',
            ],
            [
                'title' => 'My Blog',
                'desc'  => 'A personal blog platform where users can read articles and browse categories. Features a clean reading experience with a fully responsive design.',
                'tags'  => 'HTML, CSS, JavaScript',
                'img'   => '../images/work6.jpeg',
                'demo'  => '#',
                'gh'    => 'https://github.com/mehmeterencakmak',
            ],
        ];

        foreach ($samples as $s) {
            $stmt->execute([
                ':title' => $s['title'],
                ':desc'  => $s['desc'],
                ':tags'  => $s['tags'],
                ':img'   => $s['img'],
                ':demo'  => $s['demo'],
                ':gh'    => $s['gh'],
            ]);
        }
        $results[] = ['ok', '4 sample projects inserted.'];
    } else {
        $results[] = ['info', "Projects table already has $existing rows — skipped sample insert."];
    }

    // ── 3. Admin user ─────────────────────────────────
    $adminExists = (int) $pdo->query("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'")->fetchColumn();

    if ($adminExists === 0) {
        // password_hash uses bcrypt (PASSWORD_DEFAULT) — never store plain text
        $hash = password_hash('Admin123!', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES ('admin', ?)");
        $stmt->execute([$hash]);
        $results[] = ['ok', 'Admin user created. Username: <strong>admin</strong> / Password: <strong>Admin123!</strong>'];
    } else {
        $results[] = ['info', 'Admin user already exists — skipped.'];
    }

} catch (PDOException $e) {
    $results[] = ['error', 'Database error: ' . htmlspecialchars($e->getMessage())];
}

// ── Output ────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup | MEC Portfolio</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 560px; margin: 4rem auto; padding: 0 1.5rem; color: #1a1a18; }
    h1   { font-size: 1.5rem; margin-bottom: 1.5rem; }
    .item { padding: .65rem 1rem; border-radius: 6px; margin-bottom: .5rem; font-size: 14px; }
    .ok    { background: #EEF4E8; color: #2D5016; }
    .info  { background: #EFF6FF; color: #1e40af; }
    .error { background: #FEF2F2; color: #991B1B; }
    .cta   { display: inline-block; margin-top: 1.5rem; padding: 10px 22px; background: #1a1a18;
             color: #fff; text-decoration: none; border-radius: 6px; font-size: 14px; }
    .warn  { background: #FFF7ED; color: #92400E; padding: .75rem 1rem; border-radius: 6px;
             font-size: 13px; margin-top: 1.5rem; border: 1px solid #FCD34D; }
  </style>
</head>
<body>
  <h1>⚙ Portfolio Setup</h1>

  <?php foreach ($results as [$type, $msg]): ?>
    <div class="item <?= $type ?>">
      <?= $type === 'ok' ? '✓ ' : ($type === 'error' ? '✗ ' : 'ℹ ') ?>
      <?= $msg ?>
    </div>
  <?php endforeach; ?>

  <a href="index.php" class="cta">Go to Admin Login →</a>

  <p class="warn">
    ⚠ <strong>Security:</strong> Delete or rename <code>setup.php</code> after running it
    to prevent unauthorized re-runs.
  </p>
</body>
</html>
