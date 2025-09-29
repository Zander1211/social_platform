<?php
// NOTE: This component is no longer used - navigation has been moved to header.php
// Compact News Feed top menu for the global header
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isNewsPage = ($currentPage === 'index');
$currentFilter = $_GET['filter'] ?? 'all';
?>
<nav class="news-topmenu" aria-label="News feed navigation" style="display:flex;gap:10px;align-items:center">
  <a href="index.php" class="topmenu-link <?php echo $isNewsPage && $currentFilter === 'all' ? 'active' : ''; ?>">
    <i class="fa fa-newspaper"></i>
    <span class="nav-label">All Posts</span>
  </a>
  <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <a href="index.php?filter=recent" class="topmenu-link <?php echo $currentFilter === 'recent' ? 'active' : ''; ?>">
      <i class="fa fa-clock"></i>
      <span class="nav-label">Recent</span>
    </a>
  <?php endif; ?>
  <a href="index.php?filter=popular" class="topmenu-link <?php echo $currentFilter === 'popular' ? 'active' : ''; ?>">
    <i class="fa fa-fire"></i>
    <span class="nav-label">Popular</span>
  </a>
  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="index.php?filter=following" class="topmenu-link <?php echo $currentFilter === 'following' ? 'active' : ''; ?>">
      <i class="fa fa-users"></i>
      <span class="nav-label">Following</span>
    </a>
  <?php endif; ?>
</nav>

<style>
.news-topmenu .topmenu-link{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:8px;color:var(--muted);text-decoration:none;font-weight:600}
.news-topmenu .topmenu-link .nav-label{display:inline-block}
.news-topmenu .topmenu-link:hover{background:rgba(37,99,235,0.06);color:var(--primary)}
.news-topmenu .topmenu-link.active{background:var(--primary);color:#fff}
@media(max-width:480px){.news-topmenu .nav-label{display:none}}
</style>