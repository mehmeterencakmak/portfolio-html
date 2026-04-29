<?php
// ═══════════════════════════════════════════════════════
// admin/dashboard.php — Admin Control Panel
// Protected: redirects to login if session is missing.
// Features:
//   • Overview stats (project count, message count)
//   • Add / Edit project form
//   • Projects management table (edit / delete)
//   • Contact messages table
// ═══════════════════════════════════════════════════════

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../config.php';

// Collect and clear one-time flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

try {
    $pdo = getDB();

    // Stats
    $totalProjects = (int) $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $totalMessages = (int) $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();

    // All projects (newest first)
    $projects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();

    // All messages (newest first)
    $messages = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC")->fetchAll();

    // Pre-fill form when editing a project (?edit=ID)
    $editProject = null;
    if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([(int)$_GET['edit']]);
        $editProject = $stmt->fetch() ?: null;
    }

} catch (PDOException $e) {
    die('<p style="font-family:sans-serif;padding:2rem;color:#991B1B">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>');
}

// Helper: safely output HTML-escaped value (for form fields)
function val(array|null $row, string $key): string
{
    return htmlspecialchars($row[$key] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | MEC Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="admin-layout">

  <!-- ─── SIDEBAR ─────────────────────────────── -->
  <aside class="admin-sidebar">

    <a href="../portfolio.html" class="sidebar-logo">MEC.</a>

    <nav class="sidebar-nav">
      <a href="#stats"    class="sidebar-link">Dashboard</a>
      <a href="#projects" class="sidebar-link">Projects</a>
      <a href="#messages" class="sidebar-link">Messages</a>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <span class="user-icon">👤</span>
        <?= htmlspecialchars($_SESSION['admin_user']) ?>
      </div>
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>

  </aside>

  <!-- ─── MAIN CONTENT ─────────────────────────── -->
  <main class="admin-main">

    <!-- Flash notification -->
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>">
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>


    <!-- ── STATS ──────────────────────────────── -->
    <section id="stats" class="admin-section">
      <h1 class="admin-title">Dashboard</h1>

      <div class="stats-row">

        <div class="stat-card">
          <div class="stat-card-num"><?= $totalProjects ?></div>
          <div class="stat-card-label">Total Projects</div>
        </div>

        <div class="stat-card">
          <div class="stat-card-num"><?= $totalMessages ?></div>
          <div class="stat-card-label">Messages Received</div>
        </div>

        <div class="stat-card">
          <div class="stat-card-num">✓</div>
          <div class="stat-card-label">Logged in as <?= htmlspecialchars($_SESSION['admin_user']) ?></div>
        </div>

      </div>
    </section>


    <!-- ── PROJECTS ───────────────────────────── -->
    <section id="projects" class="admin-section">

      <h2 class="admin-section-title">
        <?= $editProject ? 'Edit Project' : 'Add New Project' ?>
      </h2>

      <!-- Add / Edit form -->
      <form method="POST" action="project_save.php" class="project-form">

        <?php if ($editProject): ?>
          <input type="hidden" name="id" value="<?= (int)$editProject['id'] ?>">
        <?php endif; ?>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Title *</label>
            <input class="form-input" type="text" name="title"
                   value="<?= val($editProject, 'title') ?>"
                   placeholder="E.g. E-Commerce Demo" required>
          </div>
          <div class="form-group">
            <label class="form-label">Tags (comma-separated)</label>
            <input class="form-input" type="text" name="tags"
                   value="<?= val($editProject, 'tags') ?>"
                   placeholder="HTML, CSS, PHP, MySQL">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Description *</label>
          <textarea class="form-textarea" name="description" rows="3"
                    placeholder="Short project description..." required><?= val($editProject, 'description') ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Image Path / URL</label>
            <input class="form-input" type="text" name="image_url"
                   value="<?= val($editProject, 'image_url') ?>"
                   placeholder="images/work1.jpeg">
          </div>
          <div class="form-group">
            <label class="form-label">Live Demo URL</label>
            <input class="form-input" type="url" name="demo_url"
                   value="<?= val($editProject, 'demo_url') ?>"
                   placeholder="https://example.com">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">GitHub URL</label>
          <input class="form-input" type="url" name="github_url"
                 value="<?= val($editProject, 'github_url') ?>"
                 placeholder="https://github.com/...">
        </div>

        <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:.5rem;">
          <button type="submit" class="btn-primary">
            <?= $editProject ? 'Update Project' : 'Add Project' ?>
          </button>
          <?php if ($editProject): ?>
            <a href="dashboard.php" class="btn-outline">Cancel</a>
          <?php endif; ?>
        </div>

      </form>

      <!-- Projects table -->
      <h2 class="admin-section-title" style="margin-top:3rem;">All Projects</h2>

      <?php if (empty($projects)): ?>
        <p class="empty-state">No projects yet. Use the form above to add your first project.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Title</th>
              <th>Tags</th>
              <th>Demo</th>
              <th>GitHub</th>
              <th>Added</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($projects as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td><?= htmlspecialchars($p['title']) ?></td>
              <td class="muted"><?= htmlspecialchars($p['tags']) ?></td>
              <td>
                <?= $p['demo_url']
                  ? '<a href="' . htmlspecialchars($p['demo_url']) . '" target="_blank" rel="noopener">View ↗</a>'
                  : '—' ?>
              </td>
              <td>
                <?= $p['github_url']
                  ? '<a href="' . htmlspecialchars($p['github_url']) . '" target="_blank" rel="noopener">View ↗</a>'
                  : '—' ?>
              </td>
              <td class="muted"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
              <td class="action-cell">
                <a href="dashboard.php?edit=<?= (int)$p['id'] ?>" class="action-btn edit-btn">Edit</a>
                <form method="POST" action="project_delete.php" style="display:inline"
                      onsubmit="return confirm('Delete «<?= htmlspecialchars(addslashes($p['title'])) ?>»?')">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="action-btn delete-btn">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </section>


    <!-- ── MESSAGES ───────────────────────────── -->
    <section id="messages" class="admin-section">

      <h2 class="admin-section-title">Contact Messages</h2>

      <?php if (empty($messages)): ?>
        <p class="empty-state">No messages yet.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Subject</th>
              <th>Message</th>
              <th>IP</th>
              <th>Received</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($messages as $m): ?>
            <tr>
              <td><?= (int)$m['id'] ?></td>
              <td><?= htmlspecialchars($m['name']) ?></td>
              <td>
                <a href="mailto:<?= htmlspecialchars($m['email']) ?>">
                  <?= htmlspecialchars($m['email']) ?>
                </a>
              </td>
              <td class="muted"><?= htmlspecialchars($m['subject'] ?: '—') ?></td>
              <td class="msg-cell" title="<?= htmlspecialchars($m['message']) ?>">
                <?= htmlspecialchars(mb_substr($m['message'], 0, 80)) ?>
                <?= mb_strlen($m['message']) > 80 ? '…' : '' ?>
              </td>
              <td class="muted"><?= htmlspecialchars($m['ip_address'] ?: '—') ?></td>
              <td class="muted"><?= date('d M Y H:i', strtotime($m['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

    </section>

  </main>
</div>

<script>
// Highlight active sidebar link on scroll
const sections = document.querySelectorAll('section[id]');
const links    = document.querySelectorAll('.sidebar-link');

window.addEventListener('scroll', () => {
  let cur = '';
  sections.forEach(s => { if (window.scrollY >= s.offsetTop - 80) cur = s.id; });
  links.forEach(l => l.classList.toggle('active', l.getAttribute('href') === '#' + cur));
}, { passive: true });
</script>

</body>
</html>
