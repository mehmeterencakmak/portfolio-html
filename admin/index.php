<?php
// ═══════════════════════════════════════════════════════
// admin/index.php — Admin Login Page
// GET  → shows the login form
// POST → validates credentials, starts session, sets cookie
// ═══════════════════════════════════════════════════════

session_start();

// Already logged in → redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config.php';

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']      ?? '';

    if (!$username || !$password) {
        $error = 'Please enter your username and password.';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT id, password_hash FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                $_SESSION['admin_id']   = $user['id'];
                $_SESSION['admin_user'] = $username;

                // "Remember Me" — set a persistent cookie for 30 days
                if (!empty($_POST['remember'])) {
                    setcookie(
                        'admin_remember',
                        base64_encode($username . ':' . time()),
                        time() + 30 * 24 * 3600,
                        '/',
                        '',
                        false, // set to true on HTTPS
                        true   // httpOnly — not accessible via JS
                    );
                }

                header('Location: dashboard.php');
                exit;

            } else {
                $error = 'Invalid username or password.';
            }

        } catch (Exception) {
            $error = 'Database connection failed. Check your config.php settings.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | MEC Portfolio</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">

<div class="login-card">

  <a href="../portfolio.html" class="login-logo">MEC.</a>
  <h1 class="login-title">Admin Login</h1>
  <p class="login-sub">Sign in to manage your portfolio content.</p>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" class="login-form" novalidate>

    <div class="form-group">
      <label class="form-label" for="username">Username</label>
      <input
        class="form-input"
        type="text"
        id="username"
        name="username"
        placeholder="admin"
        required
        autocomplete="username"
        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
      >
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Password</label>
      <input
        class="form-input"
        type="password"
        id="password"
        name="password"
        placeholder="••••••••"
        required
        autocomplete="current-password"
      >
    </div>

    <div class="remember-row">
      <label class="remember-label">
        <input type="checkbox" name="remember" value="1">
        Remember me for 30 days
      </label>
    </div>

    <button type="submit" class="btn-primary" style="width:100%">Sign In</button>

  </form>

  <p class="login-back"><a href="../portfolio.html">← Back to portfolio</a></p>
  <p class="login-setup">First time? <a href="setup.php">Run setup to create admin account →</a></p>

</div>

</body>
</html>
