<?php
session_start();
require_once '../config/database.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

echo "<h2>Avatar Debug for User ID: " . $userId . "</h2>";

// Check uploads directory
$uploadsDir = __DIR__ . '/uploads';
echo "<h3>1. Directory Check</h3>";
echo "Uploads directory: " . $uploadsDir . "<br>";
echo "Directory exists: " . (is_dir($uploadsDir) ? '✅ YES' : '❌ NO') . "<br>";
echo "Directory writable: " . (is_writable($uploadsDir) ? '✅ YES' : '❌ NO') . "<br>";

// List all files in uploads
echo "<h3>2. All Files in Uploads Directory</h3>";
if (is_dir($uploadsDir)) {
    $allFiles = scandir($uploadsDir);
    foreach ($allFiles as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = $uploadsDir . '/' . $file;
            $size = filesize($fullPath);
            $readable = is_readable($fullPath) ? '✅' : '❌';
            echo "- " . $file . " (size: " . $size . " bytes) " . $readable . "<br>";
        }
    }
} else {
    echo "❌ Directory does not exist<br>";
}

// Check for user's avatar files
echo "<h3>3. Your Avatar Files</h3>";
$avatarPattern = $uploadsDir . '/avatar_' . $userId . '.*';
echo "Search pattern: " . $avatarPattern . "<br>";
$avatarFiles = glob($avatarPattern);
echo "Found " . count($avatarFiles) . " avatar files:<br>";
foreach ($avatarFiles as $file) {
    $relativePath = 'uploads/' . basename($file);
    $fullUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/' . $relativePath;
    echo "- " . basename($file) . "<br>";
    echo "  Full path: " . $file . "<br>";
    echo "  Relative path: " . $relativePath . "<br>";
    echo "  URL: <a href='" . $fullUrl . "' target='_blank'>" . $fullUrl . "</a><br>";
    echo "  File exists: " . (file_exists($file) ? '✅ YES' : '❌ NO') . "<br>";
    echo "  File readable: " . (is_readable($file) ? '✅ YES' : '❌ NO') . "<br>";
    echo "  File size: " . filesize($file) . " bytes<br>";
    echo "  <img src='" . $relativePath . "?t=" . time() . "' style='width:100px;height:100px;object-fit:cover;border:1px solid #ccc;margin:10px 0;'><br>";
}

// Test the exact logic from profile.php
echo "<h3>4. Profile.php Logic Test</h3>";
$avatar = null;
$files = @glob(__DIR__ . '/uploads/avatar_' . $userId . '.*');
if ($files && count($files) > 0) { 
    $avatar = 'uploads/' . basename($files[0]); 
    echo "✅ Avatar found using profile.php logic: " . $avatar . "<br>";
    echo "Full path: " . __DIR__ . '/' . $avatar . "<br>";
    echo "File exists: " . (file_exists(__DIR__ . '/' . $avatar) ? '✅ YES' : '❌ NO') . "<br>";
    echo "Preview: <img src='" . $avatar . "?t=" . time() . "' style='width:100px;height:100px;object-fit:cover;border:1px solid #ccc;'><br>";
} else {
    echo "❌ No avatar found using profile.php logic<br>";
}

// Check browser cache
echo "<h3>5. Browser Cache Test</h3>";
echo "Current timestamp: " . time() . "<br>";
echo "If you see an old image, try clearing your browser cache or use Ctrl+F5 to refresh.<br>";

// Test upload form
echo "<h3>6. Quick Upload Test</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_avatar'])) {
    $file = $_FILES['test_avatar'];
    echo "<h4>Upload Result:</h4>";
    
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
                echo "✅ SUCCESS: Avatar uploaded successfully!<br>";
                echo "File: " . $safe . "<br>";
                echo "Size: " . filesize($dest) . " bytes<br>";
                echo "<img src='uploads/" . $safe . "?t=" . time() . "' style='width:100px;height:100px;object-fit:cover;border:1px solid #ccc;'><br>";
                echo "<a href='profile.php'>Go to Profile to see the result</a><br>";
            } else {
                echo "❌ ERROR: Failed to move uploaded file<br>";
            }
        } else {
            echo "❌ ERROR: Invalid file type. Use JPG, PNG, GIF, or WebP<br>";
        }
    } else {
        echo "❌ ERROR: Upload error code " . $file['error'] . "<br>";
    }
}
?>

<form method="POST" enctype="multipart/form-data" style="margin-top: 20px; padding: 20px; border: 2px solid #007cba; border-radius: 8px; background: #f0f8ff;">
    <h4>🔧 Quick Avatar Upload Test</h4>
    <p>Use this to test if avatar upload is working:</p>
    <input type="file" name="test_avatar" accept="image/*" required style="margin-bottom: 10px;">
    <br>
    <button type="submit" style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">Upload Test Avatar</button>
</form>

<div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 8px;">
    <h4>🔍 Common Issues & Solutions:</h4>
    <ul>
        <li><strong>Directory not writable:</strong> Run <a href="fix_uploads.php">fix_uploads.php</a> to fix permissions</li>
        <li><strong>Files not showing:</strong> Clear browser cache (Ctrl+F5)</li>
        <li><strong>Upload fails:</strong> Check file size (max 5MB) and type (JPG, PNG, GIF, WebP)</li>
        <li><strong>Image not updating:</strong> The timestamp parameter (?t=) should prevent caching</li>
    </ul>
    
    <p><strong>Quick Links:</strong></p>
    <a href="profile.php" style="margin-right: 10px;">📄 Go to Profile</a>
    <a href="fix_uploads.php" style="margin-right: 10px;">🔧 Fix Uploads Directory</a>
    <a href="debug_avatar.php" style="margin-right: 10px;">🐛 Full Debug Tool</a>
</div>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2, h3, h4 { color: #333; }
.success { color: #28a745; }
.error { color: #dc3545; }
</style>