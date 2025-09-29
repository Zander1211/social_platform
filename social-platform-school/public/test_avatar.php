<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Avatar Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .avatar-display { width: 100px; height: 100px; border-radius: 50%; border: 2px solid #ccc; margin: 10px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>🔍 Avatar Test for User ID: <?php echo $userId; ?></h1>
    
    <div class="test-section">
        <h2>1. Current Avatar Status</h2>
        <?php
        $uploadsDir = __DIR__ . '/uploads';
        $avatarFiles = glob($uploadsDir . '/avatar_' . $userId . '.*');
        
        if ($avatarFiles) {
            echo "<p class='success'>✅ Avatar file found: " . basename($avatarFiles[0]) . "</p>";
            $avatarPath = 'uploads/' . basename($avatarFiles[0]);
            echo "<p>File path: " . $avatarPath . "</p>";
            echo "<p>Full URL: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/" . $avatarPath . "</p>";
            echo "<img src='" . $avatarPath . "?t=" . time() . "' class='avatar-display' alt='Your Avatar'>";
        } else {
            echo "<p class='error'>❌ No avatar file found</p>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>2. Upload Directory Status</h2>
        <?php
        echo "<p>Directory: " . $uploadsDir . "</p>";
        echo "<p>Exists: " . (is_dir($uploadsDir) ? "✅ YES" : "❌ NO") . "</p>";
        echo "<p>Writable: " . (is_writable($uploadsDir) ? "✅ YES" : "❌ NO") . "</p>";
        
        if (is_dir($uploadsDir)) {
            $files = scandir($uploadsDir);
            $avatarCount = 0;
            foreach ($files as $file) {
                if (strpos($file, 'avatar_') === 0) {
                    $avatarCount++;
                }
            }
            echo "<p>Total avatar files in directory: " . $avatarCount . "</p>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>3. Quick Upload Test</h2>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_avatar'])) {
            $file = $_FILES['test_avatar'];
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                
                if (in_array($ext, $allowed)) {
                    $safe = 'avatar_' . $userId . '.' . $ext;
                    $dest = $uploadsDir . '/' . $safe;
                    
                    // Remove old avatars
                    foreach (glob($uploadsDir . '/avatar_' . $userId . '.*') as $f) {
                        @unlink($f);
                    }
                    
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        chmod($dest, 0644);
                        echo "<p class='success'>✅ SUCCESS! Avatar uploaded successfully!</p>";
                        echo "<p>New file: " . $safe . "</p>";
                        echo "<img src='uploads/" . $safe . "?t=" . time() . "' class='avatar-display' alt='New Avatar'>";
                        echo "<p><strong>Now go to your <a href='profile.php'>Profile Page</a> to see if it shows up!</strong></p>";
                    } else {
                        echo "<p class='error'>❌ Failed to move uploaded file</p>";
                    }
                } else {
                    echo "<p class='error'>❌ Invalid file type. Use JPG, PNG, GIF, or WebP</p>";
                }
            } else {
                echo "<p class='error'>❌ Upload error: " . $file['error'] . "</p>";
            }
        }
        ?>
        
        <form method="POST" enctype="multipart/form-data">
            <p>Select an image file to test upload:</p>
            <input type="file" name="test_avatar" accept="image/*" required>
            <button type="submit">Upload Test Avatar</button>
        </form>
    </div>
    
    <div class="test-section">
        <h2>4. Browser Cache Test</h2>
        <p class="info">If you uploaded an avatar but don't see it, try:</p>
        <ul>
            <li>Press <strong>Ctrl+F5</strong> (or Cmd+Shift+R on Mac) to hard refresh</li>
            <li>Clear your browser cache</li>
            <li>Try opening the page in an incognito/private window</li>
        </ul>
        <p>Current timestamp: <?php echo time(); ?> (this should change each time you refresh)</p>
    </div>
    
    <div class="test-section">
        <h2>5. Navigation Links</h2>
        <p>
            <a href="profile.php">📄 Go to Profile Page</a> |
            <a href="avatar_debug.php">🔧 Full Avatar Debug</a> |
            <a href="fix_uploads.php">🛠️ Fix Uploads Directory</a> |
            <a href="index.php">🏠 Home</a>
        </p>
    </div>
</body>
</html>