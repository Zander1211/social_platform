<?php
// Simple script to check and fix uploads directory permissions

$uploadsDir = __DIR__ . '/uploads';

echo "<h2>Uploads Directory Fix</h2>";

// Check if directory exists
if (!is_dir($uploadsDir)) {
    echo "Creating uploads directory...<br>";
    if (mkdir($uploadsDir, 0755, true)) {
        echo "✅ Directory created successfully<br>";
    } else {
        echo "❌ Failed to create directory<br>";
        exit();
    }
} else {
    echo "✅ Directory exists<br>";
}

// Check permissions
$perms = fileperms($uploadsDir);
$octal = substr(sprintf('%o', $perms), -4);
echo "Current permissions: " . $octal . "<br>";

// Check if writable
if (is_writable($uploadsDir)) {
    echo "✅ Directory is writable<br>";
} else {
    echo "❌ Directory is not writable<br>";
    echo "Attempting to fix permissions...<br>";
    if (chmod($uploadsDir, 0755)) {
        echo "✅ Permissions fixed<br>";
    } else {
        echo "❌ Failed to fix permissions<br>";
    }
}

// Create .htaccess file for security
$htaccessFile = $uploadsDir . '/.htaccess';
$htaccessContent = "# Prevent direct access to PHP files\n<Files \"*.php\">\n    Order Deny,Allow\n    Deny from all\n</Files>\n\n# Allow image files\n<FilesMatch \"\\.(jpg|jpeg|png|gif|webp)$\">\n    Order Allow,Deny\n    Allow from all\n</FilesMatch>";

if (!file_exists($htaccessFile)) {
    if (file_put_contents($htaccessFile, $htaccessContent)) {
        echo "✅ Security .htaccess file created<br>";
    } else {
        echo "❌ Failed to create .htaccess file<br>";
    }
} else {
    echo "✅ Security .htaccess file exists<br>";
}

// Test file creation
$testFile = $uploadsDir . '/test_write.txt';
if (file_put_contents($testFile, 'test')) {
    echo "✅ Write test successful<br>";
    unlink($testFile);
} else {
    echo "❌ Write test failed<br>";
}

echo "<br><strong>Setup complete!</strong> You can now try uploading an avatar.<br>";
echo "<a href='profile.php'>Go to Profile</a> | <a href='debug_avatar.php'>Debug Avatar</a>";
?>