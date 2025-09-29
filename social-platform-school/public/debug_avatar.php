<?php
session_start();
require_once '../config/database.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

echo "<h2>Avatar Debug Information</h2>";

// Check uploads directory
echo "<h3>1. Uploads Directory Check</h3>";
$uploadsDir = __DIR__ . '/uploads';
echo "Uploads directory: " . $uploadsDir . "<br>";
echo "Directory exists: " . (is_dir($uploadsDir) ? 'YES' : 'NO') . "<br>";
echo "Directory writable: " . (is_writable($uploadsDir) ? 'YES' : 'NO') . "<br>";

// List all files in uploads directory
echo "<h3>2. Files in Uploads Directory</h3>";
if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "- " . $file . "<br>";
        }
    }
} else {
    echo "Directory does not exist<br>";
}

// Check for user's avatar files specifically
echo "<h3>3. User Avatar Files Check</h3>";
echo "Looking for avatar files for user ID: " . $userId . "<br>";
$avatarPattern = $uploadsDir . '/avatar_' . $userId . '.*';
echo "Search pattern: " . $avatarPattern . "<br>";
$avatarFiles = glob($avatarPattern);
echo "Found avatar files: " . count($avatarFiles) . "<br>";
foreach ($avatarFiles as $file) {
    echo "- " . basename($file) . " (size: " . filesize($file) . " bytes)<br>";
}

// Check current avatar logic from profile.php
echo "<h3>4. Current Avatar Logic Test</h3>";
$avatar = null;
$files = @glob(__DIR__ . '/uploads/avatar_' . $userId . '.*');
if ($files && count($files) > 0) { 
    $avatar = 'uploads/' . basename($files[0]); 
    echo "Avatar found: " . $avatar . "<br>";
    echo "Full path: " . __DIR__ . '/' . $avatar . "<br>";
    echo "File exists: " . (file_exists(__DIR__ . '/' . $avatar) ? 'YES' : 'NO') . "<br>";
} else {
    echo "No avatar found for user<br>";
}

// Test upload form
echo "<h3>5. Test Avatar Upload</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_avatar'])) {
    echo "<h4>Upload Attempt:</h4>";
    $file = $_FILES['test_avatar'];
    echo "File name: " . $file['name'] . "<br>";
    echo "File size: " . $file['size'] . " bytes<br>";
    echo "File type: " . $file['type'] . "<br>";
    echo "Temp file: " . $file['tmp_name'] . "<br>";
    echo "Error code: " . $file['error'] . "<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (in_array($ext, $allowed)) {
            $safe = 'avatar_' . $userId . '.' . $ext;
            $dest = $uploadsDir . '/' . $safe;
            
            // Remove previous avatars
            foreach (glob($uploadsDir . '/avatar_' . $userId . '.*') as $f) {
                echo "Removing old avatar: " . basename($f) . "<br>";
                @unlink($f);
            }
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                echo "<strong>SUCCESS: Avatar uploaded to " . $safe . "</strong><br>";
                chmod($dest, 0644);
                echo "File permissions set to 644<br>";
            } else {
                echo "<strong>ERROR: Failed to move uploaded file</strong><br>";
            }
        } else {
            echo "<strong>ERROR: Invalid file extension. Allowed: " . implode(', ', $allowed) . "</strong><br>";
        }
    } else {
        echo "<strong>ERROR: Upload error code " . $file['error'] . "</strong><br>";
    }
}

?>

<form method="POST" enctype="multipart/form-data" style="margin-top: 20px; padding: 20px; border: 1px solid #ccc;">
    <h4>Test Avatar Upload</h4>
    <input type="file" name="test_avatar" accept="image/*" required>
    <button type="submit">Upload Test Avatar</button>
</form>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
</style>