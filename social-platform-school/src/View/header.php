<?php
// Global header for templates and public pages
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
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
  <header class="topbar">
    <div class="container topbar-inner">
      <div class="brand"><a href="index.php">School Platform</a></div>
      <div class="search"><input class="input" placeholder="Search for classmates, posts or events"></div>
      <nav class="topnav">
        <a href="index.php">Home</a>
        <a href="chat.php">Chat</a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="profile.php">Profile</a>
        <?php endif; ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <a href="admin.php">Admin</a>
        <?php endif; ?>
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="logout.php">Logout</a>
        <?php else: ?>
          <a href="login.php">Login</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <div class="main-layout container">
    <aside class="left-sidebar card">
      <h4>Menu</h4>
      <ul>
        <li><a href="index.php">News Feed</a></li>
        <li><a href="chat.php">Chat</a></li>
        <li><a href="register.php">Register</a></li>
      </ul>
    </aside>
