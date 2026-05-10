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
      <div class="theme-switch-row" id="themeToggle">
        <span class="theme-switch-label">
          <span class="switch-icon">☀️</span>
          <span class="switch-text">Dark Mode</span>
        </span>
        <div class="switch-track" id="switchTrack">
          <div class="switch-thumb"></div>
        </div>
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
      <form method="POST" action="project_save.php" class="project-form" enctype="multipart/form-data">

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

        <!-- Image Upload -->
        <div class="form-group">
          <label class="form-label">Project Image</label>
          <div class="upload-zone" id="uploadZone">
            <input type="file" name="image_file" id="imageFile" accept="image/*" class="upload-input">
            <?php $currentImg = val($editProject, 'image_url'); ?>
            <div class="upload-placeholder" id="uploadPlaceholder" style="<?= $currentImg ? 'display:none' : '' ?>">
              <div class="upload-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="17 8 12 3 7 8"/>
                  <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
              </div>
              <p class="upload-text">Click to upload or drag &amp; drop</p>
              <p class="upload-hint">PNG, JPG, WEBP, GIF — max 5 MB</p>
            </div>
            <div class="upload-preview-wrap" id="uploadPreviewWrap" style="<?= $currentImg ? '' : 'display:none' ?>">
              <img id="uploadPreview" src="<?= $currentImg ? '../' . $currentImg : '' ?>" alt="Preview">
              <button type="button" class="upload-remove" id="uploadRemove">✕ Remove</button>
            </div>
          </div>
          <input type="hidden" name="image_url" id="imageUrlHidden" value="<?= $currentImg ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Live Demo URL</label>
            <input class="form-input" type="url" name="demo_url"
                   value="<?= val($editProject, 'demo_url') ?>"
                   placeholder="https://example.com">
          </div>
          <div class="form-group">
            <label class="form-label">GitHub URL</label>
            <input class="form-input" type="url" name="github_url"
                   value="<?= val($editProject, 'github_url') ?>"
                   placeholder="https://github.com/...">
          </div>
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
// ── IMAGE UPLOAD ZONE ────────────────────────────────────
const zone        = document.getElementById('uploadZone');
const fileInput   = document.getElementById('imageFile');
const placeholder = document.getElementById('uploadPlaceholder');
const previewWrap = document.getElementById('uploadPreviewWrap');
const previewImg  = document.getElementById('uploadPreview');
const removeBtn   = document.getElementById('uploadRemove');
const hiddenUrl   = document.getElementById('imageUrlHidden');

if (zone) {
  zone.addEventListener('click', () => fileInput.click());
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) showPreview(file);
  });

  fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) showPreview(fileInput.files[0]);
  });

  removeBtn.addEventListener('click', e => {
    e.stopPropagation();
    fileInput.value = '';
    hiddenUrl.value = '';
    previewImg.src  = '';
    previewWrap.style.display  = 'none';
    placeholder.style.display  = 'flex';
  });
}

function showPreview(file) {
  if (!file.type.startsWith('image/')) return;
  const reader = new FileReader();
  reader.onload = ev => {
    previewImg.src             = ev.target.result;
    placeholder.style.display  = 'none';
    previewWrap.style.display  = 'flex';
  };
  reader.readAsDataURL(file);
}

// Highlight active sidebar link on scroll
const sections = document.querySelectorAll('section[id]');
const links    = document.querySelectorAll('.sidebar-link');

window.addEventListener('scroll', () => {
  let cur = '';
  sections.forEach(s => { if (window.scrollY >= s.offsetTop - 80) cur = s.id; });
  links.forEach(l => l.classList.toggle('active', l.getAttribute('href') === '#' + cur));
}, { passive: true });

// Theme toggle (iOS switch)
const themeRow  = document.getElementById('themeToggle');
const track     = document.getElementById('switchTrack');
const switchIcon = themeRow.querySelector('.switch-icon');
const switchText = themeRow.querySelector('.switch-text');

function applyTheme(isLight) {
  document.body.classList.toggle('light', isLight);
  track.classList.toggle('on', isLight);
  switchIcon.textContent = isLight ? '🌙' : '☀️';
  switchText.textContent = isLight ? 'Light Mode' : 'Dark Mode';
}

applyTheme(localStorage.getItem('adminTema') === 'light');

themeRow.addEventListener('click', () => {
  const isLight = !document.body.classList.contains('light');
  applyTheme(isLight);
  localStorage.setItem('adminTema', isLight ? 'light' : 'dark');
});
</script>

</body>
</html>
