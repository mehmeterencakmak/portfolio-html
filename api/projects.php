<?php
// ═══════════════════════════════════════════════════════
// api/projects.php — Projects JSON API
// Called by portfolio.html via Fetch API (AJAX).
// Returns all projects ordered by newest first.
// ═══════════════════════════════════════════════════════

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config.php';

try {
    $pdo = getDB();

    // Create projects table if not yet present
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

    $stmt     = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
    $projects = $stmt->fetchAll();

    echo json_encode([
        'success'  => true,
        'projects' => $projects,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load projects.']);
}
