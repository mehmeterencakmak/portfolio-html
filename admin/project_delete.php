<?php
// ═══════════════════════════════════════════════════════
// admin/project_delete.php — Delete a Project
// Only accepts POST with a valid project id.
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

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid project ID.'];
    header('Location: dashboard.php');
    exit;
}

require_once '../config.php';

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Project deleted.'];

} catch (PDOException $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Could not delete project.'];
}

header('Location: dashboard.php#projects');
exit;
