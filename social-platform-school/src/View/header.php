<?php
// Global header for templates and public pages
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>School Platform</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <?php
  // Cache-bust stylesheet by appending file modification time so changes appear immediately in browser
  $cssPath = __DIR__ . '/../../public/assets/style.css';
  $cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();
  ?>
  <link rel="stylesheet" href="assets/style.css?v=<?php echo $cssVersion; ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <?php if (empty($suppressTopNav)): ?>
  <header class="topbar">
    <div class="container topbar-inner">
      <div class="brand"><a href="index.php">School Platform</a></div>
  <div class="search">
        <form method="GET" action="index.php" style="display:flex;gap:8px;align-items:center">
          <input class="input" name="q" placeholder="Search for classmates, posts or events" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
          <input type="hidden" name="scope" value="<?php echo htmlspecialchars($_GET['scope'] ?? 'all'); ?>" id="search-scope">
          <button class="btn" type="submit">Search</button>
          <button class="btn secondary" type="submit" name="scope" value="users">People</button>
        </form>
      </div>
      <nav class="topnav">
        <a href="index.php">Home</a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="profile.php">Profile</a>
        <?php endif; ?>
        <?php if (!empty($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
          <a href="admin.php">Admin</a>
        <?php endif; ?>
        <?php if (!isset($_SESSION['user_id'])): ?>
          <a class="btn" href="login.php">Login</a>
        <?php endif; ?>
      </nav>
    </div>
    <!-- top horizontal menu moved from left sidebar -->
    <div class="container" style="margin-top:8px">
      <nav class="topmenu card" style="display:flex;gap:12px;padding:10px;align-items:center">
        <?php if (!isset($_SESSION['user_id'])): ?>
          <a href="register.php">Register</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>
  <?php endif; ?>

  <div class="main-layout container">
    <aside class="left-sidebar">
      <?php 
      // Left sidebar content is now context-specific. Templates can include the full dashboard if needed.
      // require_once __DIR__ . '/news-feed-dashboard.php';
      ?>
    </aside>

    <div class="header-topmenu">
      <?php // Include the compact news feed navigation in the header for site-wide access ?>
      <?php require_once __DIR__ . '/news-feed-topmenu.php'; ?>
    </div>