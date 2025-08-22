<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../src/Controller/UserController.php';

$uc = new UserController($pdo);

// viewing ?id= or own profile
$viewId = isset($_GET['id']) ? (int)$_GET['id'] : ($_SESSION['user_id'] ?? null);
if (!$viewId) { header('Location: login.php'); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_avatar') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $viewId) { header('Location: profile.php'); exit(); }
    if (!empty($_FILES['avatar']['name'])) {
        $up = $_FILES['avatar'];
        $targetDir = __DIR__ . '/uploads';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
        $safe = 'avatar_' . $viewId . '.' . $ext;
        $dest = $targetDir . '/' . $safe;
        if (move_uploaded_file($up['tmp_name'], $dest)) {
            // success
        }
    }
    header('Location: profile.php' . ($viewId ? '?id='.$viewId : '')); exit();
}

$profile = $uc->viewProfile($viewId);
$avatar = null;
$files = @glob(__DIR__ . '/uploads/avatar_' . $viewId . '.*');
if ($files && count($files) > 0) { $avatar = 'uploads/' . basename($files[0]); }

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/style.css">
  <title>Profile - <?php echo htmlspecialchars($profile['name'] ?? ''); ?></title>
</head>
<body>
  <?php require_once __DIR__ . '/../src/View/header.php'; ?>
  <main class="container">
    <section class="card" style="max-width:680px;margin:18px auto;padding:18px">
      <div style="display:flex;gap:18px;align-items:center">
        <div style="width:96px;height:96px;border-radius:50%;overflow:hidden;background:#eee">
          <?php if ($avatar): ?><img src="<?php echo $avatar; ?>" style="width:100%;height:100%;object-fit:cover" alt="avatar"><?php else: ?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#777">No avatar</div>
          <?php endif; ?>
        </div>
        <div>
          <h2><?php echo htmlspecialchars($profile['name'] ?? ''); ?></h2>
          <div class="kv"><?php echo htmlspecialchars($profile['email'] ?? ''); ?></div>
          <div class="kv">Role: <?php echo htmlspecialchars($profile['role'] ?? ''); ?></div>
        </div>
      </div>

      <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $viewId): ?>
        <hr style="margin:12px 0">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_avatar">
          <label class="btn">Change avatar <input type="file" name="avatar" style="display:none"></label>
          <button class="btn" type="submit">Upload</button>
        </form>
      <?php else: ?>
        <!-- viewing another user's profile; no manage controls -->
      <?php endif; ?>
    </section>
  </main>
  <?php require_once __DIR__ . '/../src/View/footer.php'; ?>
</body>
</html>
