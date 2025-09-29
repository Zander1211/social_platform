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
  $modernCssPath = __DIR__ . '/../../public/assets/modern-theme.css';
  $cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();
  $modernCssVersion = file_exists($modernCssPath) ? filemtime($modernCssPath) : time();
  ?>
  <link rel="stylesheet" href="assets/style.css?v=<?php echo $cssVersion; ?>">
  <link rel="stylesheet" href="assets/modern-theme.css?v=<?php echo $modernCssVersion; ?>">
  <link rel="stylesheet" href="assets/enhanced-content.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="assets/dashboard-enhancements.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="assets/pages-enhancements.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="assets/chat.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <?php if (empty($suppressTopNav)): ?>
  <!-- Modern Dashboard Layout -->
  <div class="dashboard-layout">
    <!-- Left Sidebar Navigation -->
    <aside class="modern-sidebar">
      <!-- Logo/Brand Section -->
      <div class="sidebar-header">
        <div class="sidebar-logo">
          <i class="fas fa-graduation-cap"></i>
          <span class="logo-text">School Platform</span>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav class="sidebar-nav">
        <!-- Filters Section -->
        <div class="nav-section">
          <div class="nav-section-title">
            <i class="fas fa-filter"></i>
            <span>Filters</span>
          </div>
          <div class="nav-items">
            <?php 
            $currentFilter = $_GET['filter'] ?? 'all';
            $currentPage = basename($_SERVER['PHP_SELF'], '.php');
            $isIndexPage = ($currentPage === 'index');
            ?>
            <a href="index.php" class="nav-item <?php echo $isIndexPage && $currentFilter === 'all' ? 'active' : ''; ?>">
              <i class="fas fa-globe"></i>
              <span class="nav-label">All Posts</span>
            </a>
            <a href="index.php?filter=day" class="nav-item <?php echo $currentFilter === 'day' ? 'active' : ''; ?>">
              <i class="fas fa-calendar-day"></i>
              <span class="nav-label">This Day</span>
            </a>
            <a href="index.php?filter=week" class="nav-item <?php echo $currentFilter === 'week' ? 'active' : ''; ?>">
              <i class="fas fa-calendar-week"></i>
              <span class="nav-label">This Week</span>
            </a>
            <a href="index.php?filter=month" class="nav-item <?php echo $currentFilter === 'month' ? 'active' : ''; ?>">
              <i class="fas fa-calendar-alt"></i>
              <span class="nav-label">This Month</span>
            </a>
          </div>
        </div>

        <!-- Content Section -->
        <div class="nav-section">
          <div class="nav-section-title">
            <i class="fas fa-star"></i>
            <span>Content</span>
          </div>
          <div class="nav-items">
            <a href="index.php?filter=popular" class="nav-item <?php echo $currentFilter === 'popular' ? 'active' : ''; ?>">
              <i class="fas fa-fire"></i>
              <span class="nav-label">Popular Posts</span>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="index.php?filter=following" class="nav-item <?php echo $currentFilter === 'following' ? 'active' : ''; ?>">
              <i class="fas fa-users"></i>
              <span class="nav-label">Following</span>
            </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Navigation Section -->
        <div class="nav-section">
          <div class="nav-section-title">
            <i class="fas fa-compass"></i>
            <span>Navigate</span>
          </div>
          <div class="nav-items">
            <a href="index.php" class="nav-item <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
              <i class="fas fa-home"></i>
              <span class="nav-label">Home</span>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php" class="nav-item <?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
              <i class="fas fa-user"></i>
              <span class="nav-label">Profile</span>
            </a>
            <a href="chat.php" class="nav-item <?php echo $currentPage === 'chat' ? 'active' : ''; ?>">
              <i class="fas fa-comments"></i>
              <span class="nav-label">Chat</span>
            </a>
            <?php endif; ?>
            <?php if (!empty($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
            <a href="admin.php" class="nav-item <?php echo $currentPage === 'admin' ? 'active' : ''; ?>">
              <i class="fas fa-cog"></i>
              <span class="nav-label">Admin</span>
            </a>
            <?php endif; ?>
          </div>
        </div>
      </nav>

      <!-- User Section -->
      <div class="sidebar-footer">
        <?php if (isset($_SESSION['user_id'])): ?>
        <?php
        // Get user avatar
        $userAvatar = null;
        $avatarGlob = glob(__DIR__ . '/../../public/uploads/avatar_' . $_SESSION['user_id'] . '.*');
        if ($avatarGlob) {
          $userAvatar = 'uploads/' . basename($avatarGlob[0]);
        }
        ?>
        <div class="user-profile-dropdown">
          <button class="user-profile-btn" onclick="toggleUserDropdown()">
            <div class="user-avatar">
              <?php if ($userAvatar): ?>
                <img src="<?php echo $userAvatar; ?>" alt="<?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>'s avatar">
              <?php else: ?>
                <?php echo strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)); ?>
              <?php endif; ?>
            </div>
            <div class="user-details">
              <div class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></div>
              <div class="user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Student'); ?></div>
            </div>
            <i class="fas fa-chevron-down dropdown-arrow"></i>
          </button>
          
          <div class="user-dropdown-menu" id="userDropdownMenu">
            <a href="profile.php" class="dropdown-item">
              <i class="fas fa-user"></i>
              <span>My Profile</span>
            </a>
            <a href="profile.php?edit=1" class="dropdown-item">
              <i class="fas fa-edit"></i>
              <span>Edit Profile</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="dropdown-item logout-item">
              <i class="fas fa-sign-out-alt"></i>
              <span>Logout</span>
            </a>
          </div>
        </div>
        <?php else: ?>
        <div class="auth-buttons">
          <a href="login.php" class="nav-item">
            <i class="fas fa-sign-in-alt"></i>
            <span class="nav-label">Login</span>
          </a>
          <a href="register.php" class="nav-item">
            <i class="fas fa-user-plus"></i>
            <span class="nav-label">Register</span>
          </a>
        </div>
        <?php endif; ?>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-content">
      <!-- Top Header Bar -->
      <header class="content-header">
        <div class="header-left">
          <button class="mobile-menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
          </button>
          <h1 class="page-title">
            <?php 
            if ($currentPage === 'index') {
              switch($currentFilter) {
                case 'day': echo 'Today\'s Posts'; break;
                case 'week': echo 'This Week\'s Posts'; break;
                case 'month': echo 'This Month\'s Posts'; break;
                case 'popular': echo 'Popular Posts'; break;
                case 'following': echo 'Following'; break;
                default: echo 'All Posts'; break;
              }
            } else {
              echo ucfirst($currentPage);
            }
            ?>
          </h1>
        </div>
        <div class="header-right">
          <div class="search-container">
            <form method="GET" action="index.php" class="search-form">
              <div class="search-input-group">
                <i class="fas fa-search search-icon"></i>
                <input class="search-input" name="q" placeholder="Search posts, people, events..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                <input type="hidden" name="scope" value="<?php echo htmlspecialchars($_GET['scope'] ?? 'all'); ?>" id="search-scope">
              </div>
              <button class="search-btn" type="submit">
                <i class="fas fa-search"></i>
              </button>
              <button class="people-btn" type="submit" name="scope" value="users">
                <i class="fas fa-users"></i>
                <span>People</span>
              </button>
            </form>
          </div>
          <?php if (!empty($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
          <button class="create-post-btn" onclick="openPostComposer()">
            <i class="fas fa-plus"></i>
            <span>Create Post</span>
          </button>
          <!-- Debug info for admin -->
          <small style="color: #666; font-size: 10px; margin-left: 10px;">Admin: <?php echo $_SESSION['role']; ?></small>
          <?php endif; ?>
        </div>
      </header>

      <!-- Content Area -->
      <main class="content-body">
  <?php else: ?>
  <!-- Simple layout when navigation is suppressed -->
  <div class="simple-layout">
    <main class="simple-content">
  <?php endif; ?>