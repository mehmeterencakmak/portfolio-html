<?php
// ═══════════════════════════════════════════════════════
// admin/logout.php — Destroy session and clear cookie
// ═══════════════════════════════════════════════════════

session_start();
session_unset();
session_destroy();

// Clear the remember-me cookie
if (isset($_COOKIE['admin_remember'])) {
    setcookie('admin_remember', '', time() - 3600, '/');
}

header('Location: index.php');
exit;
